# 게시글 작성 ( INSERT + FK )

## 🎯 학습 목표

- 로그인한 사용자만 게시글을 작성할 수 있도록 제어한다

- 세션(Session)에 저장된 사용자 정보를 사용한다

- INSERT 문으로 posts 테이블에 게시글을 저장한다

- 외래키(FK)가 실제 SQL 작성에 어떻게 영향을 주는지 이해한다

- PDO의 prepare → bindValue → execute 패턴을 반복 학습한다

--- 


# 1. 게시글 작성 기능 개요

게시글 작성은 로그인 이후에만 가능한 기능이다.
이 장에서는 다음 흐름을 구현한다.

```sql
로그인 상태 확인
→ 게시글 작성 폼 출력
→ POST 요청 전송
→ PHP에서 세션 + 입력값 처리
→ INSERT SQL 실행
→ posts 테이블에 데이터 저장
```

## 1-1. posts 테이블 구조 복습
게시글 작성 시 사용하는 posts 테이블의 주요 컬럼은 다음과 같다.
| 컬럼명        | 설명                       |
| ---------- | ------------------------ |
| id         | 게시글 PK (AUTO_INCREMENT)  |
| user_id    | 작성자 ID (users.id 참조, FK) |
| title      | 게시글 제목                   |
| content    | 게시글 내용                   |
| view_count | 조회수 (DEFAULT 0)          |
| created_at | 작성일                      |


> posts.user_id 는 users.id 를 참조하는 외래키(FK) 이다.  
따라서 게시글 작성 시 반드시 로그인한 사용자의 id 값이 필요하다.

--- 

# 2. 게시글 작성 페이지 접근 제한
> 게시글 작성 페이지는 로그인한 사용자만 접근 가능해야 한다.  
로그인 여부는 $_SESSION["user"] 존재 여부로 판단한다.

## 2-1. public/post_create.php
```php
<?php
session_start();

// 로그인하지 않은 경우 접근 차단
if (!isset($_SESSION["user"])) {
  echo "로그인 후 이용 가능합니다.<br>";
  echo "<a href='/login.php'>로그인 페이지로 이동</a>";
  exit;
}
?>
<!doctype html>
<html lang="ko">
<head>
  <meta charset="utf-8" />
  <title>게시글 작성</title>
</head>
<body>

<h1>게시글 작성</h1>

<form method="post" action="/post_create_action.php">
  <p>
    제목 :
    <input type="text" name="title" required>
  </p>

  <p>
    내용 :<br>
    <textarea name="content" rows="8" cols="60" required></textarea>
  </p>

  <p>
    <button type="submit">등록</button>
  </p>
</form>

<p>
  <a href="/">메인페이지로</a>
</p>

</body>
</html>
```


# 3. 게시글 작성 처리 ( INSERT + FK )

## 3-1. 게시글 저장 흐름

1. 세션에서 로그인 사용자 정보 확인

2. POST 데이터 수신

3. 필수값 검증

4. posts.user_id 에 로그인 사용자 id 저장

5. INSERT SQL 실행

6. 게시글 상세 페이지로 이동

## 3-2. INSERT SQL
```sql
INSERT INTO posts (user_id, title, content)
VALUES (:user_id, :title, :content);
```
> id, view_count, created_at 은 DB에서 자동 처리되므로 INSERT 문에 포함하지 않는다.

## 3-3. public/post_create_action.php
```php
<?php
require_once dirname(__DIR__) . "/lib/db.php";

session_start();

// 로그인 체크
if (!isset($_SESSION["user"])) {
  echo "로그인 후 이용 가능합니다.";
  exit;
}

$pdo = db();

// --------------------------------------------------
// 세션에서 로그인 사용자 정보 가져오기
// --------------------------------------------------
$userId = (int) $_SESSION["user"]["id"];

// --------------------------------------------------
// POST 데이터 수신
// --------------------------------------------------
$title = $_POST["title"] ?? "";
$content = $_POST["content"] ?? "";

// --------------------------------------------------
// 필수값 검증
// --------------------------------------------------
if ($title === "" || $content === "") {
  echo "제목과 내용을 입력하세요.";
  exit;
}

// --------------------------------------------------
// SQL 준비 (INSERT + FK)
// --------------------------------------------------
$sql = "
  INSERT INTO posts (user_id, title, content)
  VALUES (:user_id, :title, :content)
";

$stmt = $pdo->prepare($sql);

// --------------------------------------------------
// 바인딩
// --------------------------------------------------
$stmt->bindValue(":user_id", $userId, PDO::PARAM_INT);
$stmt->bindValue(":title", $title, PDO::PARAM_STR);
$stmt->bindValue(":content", $content, PDO::PARAM_STR);

// --------------------------------------------------
// SQL 실행
// --------------------------------------------------
$stmt->execute();

// --------------------------------------------------
// 방금 작성한 게시글 ID 가져오기
// --------------------------------------------------
$postId = $pdo->lastInsertId();

// --------------------------------------------------
// 메인페이지(게시글 목록)로 이동
// --------------------------------------------------
header("Location: /");
exit;
?>
```
---

# 4. 외래키(FK)가 게시글 작성에 미치는 영향
posts.user_id 는 users.id 를 참조하는 외래키이므로 다음이 보장된다.

- 존재하지 않는 사용자 ID로는 게시글을 작성할 수 없다

- 반드시 로그인한 사용자만 게시글 작성 가능

- 데이터 무결성이 유지된다

> FK 제약 조건은 “코드를 믿지 않고 DB가 직접 검증하는 안전장치”이다.

---

# 🧩 실습 / 과제

## 1. FK 제약 조건 오류 직접 발생시켜보기 (DB가 막는 걸 체험)
### 목표

posts.user_id 는 users.id 를 참조하는 FK 이므로
존재하지 않는 사용자 ID로는 INSERT가 실패한다는 것을 확인한다.

## 2. DB에서 직접 “잘못된 user_id”로 INSERT 시도
> 아래 SQL을 MySQL에서 실행한다. (예: user_id = 999999)
```sql
INSERT INTO posts (user_id, title, content)
VALUES (999999, 'FK 테스트', '존재하지 않는 user_id로 작성 시도');
```