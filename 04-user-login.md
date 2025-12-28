# 로그인 페이지 ( SELECT + WHERE )

## 🎯 학습 목표

- 로그인 폼에서 입력값을 POST로 전송한다

- SELECT + WHERE 로 사용자 정보를 조회한다

- password_verify() 로 비밀번호를 검증한다

- 로그인 성공 시 $_SESSION 에 사용자 정보를 저장한다

- PDO는 prepare → bindValue → execute → fetch 패턴만 사용한다


---


# 1. 로그인 화면 작성

## 1-1. public/login.php

```php
<!doctype html>
<html lang="ko">
<head>
  <meta charset="utf-8" />
  <title>로그인</title>
</head>
<body>

<h1>로그인</h1>

<form method="post" action="/login_action.php">
  <p>
    아이디 :
    <input type="text" name="username" required>
  </p>

  <p>
    비밀번호 :
    <input type="password" name="password" required>
  </p>

  <p>
    <button type="submit">로그인</button>
  </p>
</form>

<p>
  <a href="/">메인페이지로</a> |
  <a href="/register.php">회원가입</a>
</p>

</body>
</html>
```

--- 

# 2. 로그인 처리 ( SELECT + WHERE )

## 2-1. SQL (아이디로 사용자 조회)
```sql
SELECT
  id,
  username,
  nickname,
  password
FROM users
WHERE username = :username
LIMIT 1;
```

> 비밀번호는 해시로 저장되어 있으므로 로그인 시에는 password_verify() 로 비교한다.


## 2-2. public/login_action.php

```php
<?php
require_once dirname(__DIR__) . "/lib/db.php";

session_start();

$pdo = db();

// --------------------------------------------------
// POST 데이터 수신
// --------------------------------------------------
$username = $_POST["username"] ?? "";
$password = $_POST["password"] ?? "";

// --------------------------------------------------
// 필수값 검증
// --------------------------------------------------
if ($username === "" || $password === "") {
  echo "아이디/비밀번호를 입력하세요.";
  exit;
}

// --------------------------------------------------
// SQL 준비 (SELECT + WHERE)
// --------------------------------------------------
$sql = "
  SELECT
    id,
    username,
    nickname,
    password
  FROM users
  WHERE username = :username
  LIMIT 1
";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(":username", $username, PDO::PARAM_STR);
$stmt->execute();

// --------------------------------------------------
// 결과 1건 조회
// fetch() → 한 행만 가져옴
// --------------------------------------------------
$user = $stmt->fetch();

if (!$user) {
  echo "아이디가 존재하지 않습니다.";
  exit;
}

// --------------------------------------------------
// 비밀번호 검증
// DB에는 해시값이 저장되어 있으므로
// password_verify(입력값, DB해시) 로 비교
// --------------------------------------------------
if (!password_verify($password, $user["password"])) {
  echo "비밀번호가 올바르지 않습니다.";
  exit;
}

// --------------------------------------------------
// 로그인 성공 → 세션 저장
// 비밀번호 해시는 세션에 저장하지 않는다
// --------------------------------------------------
$_SESSION["user"] = [
  "id" => (int) $user["id"],
  "username" => $user["username"],
  "nickname" => $user["nickname"]
];

// --------------------------------------------------
// 로그인 후 메인페이지로 이동
// --------------------------------------------------
header("Location: /");
exit;
```

---

# 3. 로그아웃 처리

## 3-1. public/logout.php
```sql
<?php
session_start();

// 세션 전체 제거
session_destroy();

header("Location: /");
exit;
```

--- 

# 4. 메인페이지에서 로그인 상태 표시

> public/index.php에서 아래처럼 상단 메뉴를 바꾸면 로그인/로그아웃 상태에 따라 HTML 코드가 바뀜

```php
<!-- 상단 메뉴 -->
<p>
  <?php if (isset($_SESSION["user"])): ?>
    <?= htmlspecialchars($_SESSION["user"]["nickname"]) ?> 님 |
    <a href="/logout.php">로그아웃</a> |
    <a href="/post_create.php">글쓰기</a>
  <?php else: ?>
    <a href="/register.php">회원가입</a> |
    <a href="/login.php">로그인</a> |
    <a href="/post_create.php">글쓰기</a>
  <?php endif; ?>
</p>
```

그리고 파일 최상단에 아래 한줄 추가
```
session_start();
```

---

# 🧩 실습 / 과제

## 1. UNIQUE 제약 조건 “직접 체험” 실습 (중복 아이디 가입 시도)

### 1-1. users 테이블 현재 제약 확인

### 1-2. 중복 회원가입 시도 후 에러확인
```
Fatal error: Uncaught PDOException: SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry '123' for key 'users.username_UNIQUE' in /var/www/test.localhost/public/register_action.php:48 Stack trace: #0 /var/www/test.localhost/public/register_action.php(48): PDOStatement->execute() #1 {main} thrown in /var/www/test.localhost/public/register_action.php on line 48
```

### 1-3. Unique 제약으로 인한 에러시 try 문으로 처리
```php
try {
  // 여기서 쿼리 실행
  $stmt->execute();

} catch (PDOException $e) {

  // MySQL 중복 키 에러 코드: 1062
  if ($e->errorInfo[1] === 1062) {
    echo "이미 사용 중인 아이디입니다.";
    exit;
  }

  // 그 외 DB 에러는 그대로 출력 (개발 단계)
  echo "DB 오류가 발생했습니다.";
  exit;
}
```