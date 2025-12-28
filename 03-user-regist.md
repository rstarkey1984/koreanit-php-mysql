# 회원가입 페이지 ( INSERT )

## 🎯 학습 목표

- HTML form을 통해 사용자 입력값을 전달받는다

- INSERT 문을 사용해 users 테이블에 데이터를 저장한다

- PDO의 prepare → bindValue → execute 패턴을 익힌다

- 비밀번호를 평문이 아닌 해시 값으로 저장하는 이유를 이해한다

---

# 1. 회원가입 기능 개요

회원가입 페이지는 사용자가 서비스에 계정을 생성하는 기능이다.   
이 장에서는 다음 흐름을 직접 구현한다.

```sql
회원가입 폼 입력
→ POST 요청 전송
→ PHP에서 데이터 수신
→ INSERT SQL 실행
→ users 테이블에 데이터 저장
```

## 1-1. users 테이블 사용 컬럼

회원가입 시 사용하는 users 테이블의 주요 컬럼은 다음과 같다.

| 컬럼명        | 설명                              |
| ---------- | ------------------------------- |
| id         | 사용자 PK (AUTO_INCREMENT)         |
| username   | 로그인 아이디 (UNIQUE)                |
| nickname   | 화면에 표시할 닉네임                     |
| password   | 비밀번호 해시값                        |
| created_at | 가입일 (DEFAULT CURRENT_TIMESTAMP) |

---

# 2. 회원가입 화면 작성

## 2-1. public/register.php
```php
<!doctype html>
<html lang="ko">
<head>
  <meta charset="utf-8" />
  <title>회원가입</title>
</head>
<body>

<h1>회원가입</h1>

<form method="post" action="/register_action.php">
  <p>
    아이디 :
    <input type="text" name="username" required>
  </p>

  <p>
    닉네임 :
    <input type="text" name="nickname" required>
  </p>

  <p>
    비밀번호 :
    <input type="password" name="password" required>
  </p>

  <p>
    <button type="submit">회원가입</button>
  </p>
</form>

<p>
  <a href="/">메인페이지로 돌아가기</a>
</p>

</body>
</html>
```

---

# 3. 회원가입 처리 로직 ( INSERT )

## 3-1. 회원가입 처리 흐름
1. POST 방식으로 전달된 입력값 수신

2. 필수값 검증

3. 비밀번호 해시 처리

4. INSERT SQL 실행

5. 회원가입 완료 후 로그인 페이지로 이동

## 3-2. INSERT SQL
> id, created_at, updated_at 은 DB에서 자동 처리되므로 INSERT 문에서는 직접 지정하지 않는다.
```sql
INSERT INTO users (username, password, nickname)
VALUES (:username, :password, :nickname);
```

## 3-3. public/register_action.php
```php
<?php
// DB 연결
require_once dirname(__DIR__) . "/lib/db.php";

$pdo = db();

// --------------------------------------------------
// POST 데이터 수신
// --------------------------------------------------
$username = $_POST["username"] ?? "";
$nickname = $_POST["nickname"] ?? "";
$password = $_POST["password"] ?? "";

// --------------------------------------------------
// 필수값 검증
// --------------------------------------------------
if ($username === "" || $nickname === "" || $password === "") {
  echo "필수 입력값이 누락되었습니다.";
  exit;
}

// --------------------------------------------------
// 비밀번호 해시 처리
// 평문 비밀번호를 그대로 저장하면 안 됨
// --------------------------------------------------
$hashedPassword = password_hash($password, PASSWORD_BCRYPT);

// --------------------------------------------------
// SQL 준비 (INSERT)
// --------------------------------------------------
$sql = "
  INSERT INTO users (username, nickname, password)
  VALUES (:username, :nickname, :password)
";

$stmt = $pdo->prepare($sql);

// --------------------------------------------------
// 바인딩
// --------------------------------------------------
$stmt->bindValue(":username", $username, PDO::PARAM_STR);
$stmt->bindValue(":nickname", $nickname, PDO::PARAM_STR);
$stmt->bindValue(":password", $hashedPassword, PDO::PARAM_STR);

// --------------------------------------------------
// SQL 실행
// --------------------------------------------------
$stmt->execute();

// --------------------------------------------------
// 회원가입 완료 후 로그인 페이지로 이동
// --------------------------------------------------
header("Location: /login.php");
exit;
```


---

# 4. 비밀번호를 해시로 저장하는 이유
비밀번호를 그대로 DB에 저장하면 다음과 같은 문제가 발생한다.

- DB 유출 시 모든 계정이 즉시 노출됨

- 관리자도 사용자 비밀번호를 알 수 있음

password_hash() 함수는 비밀번호를 복호화할 수 없는 해시값으로 변환한다.

로그인 시에는 다음 단계에서 password_verify() 를 사용한다.


---


# 🧩 실습 / 과제

## 1. email 입력칸 추가 + INSERT 반영하기 (필수)

### 목표

- register.php에 이메일 입력칸 추가

- register_action.php에서 $_POST["email"] 받아서 INSERT에 포함

### 힌트

HTML
```html
<input type="email" name="email">
```

PHP
```php
$email = $_POST["email"] ?? null;
```

SQL
```sql
INSERT INTO users (username, password, nickname, email)
VALUES (:username, :password, :nickname, :email);
```

## 2. 비밀번호 해시가 진짜로 저장되는지 확인하기

### 목표

- 회원가입 후 DB에서 password 컬럼을 직접 확인

- 평문이 아니라 $2y$... 같은 해시 형태인지 확인

### 체크포인트

- 해시 저장의 의미를 눈으로 확인
