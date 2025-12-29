# 게시글 수정 ( UPDATE + WHERE )

## 🎯 학습 목표

UPDATE 문으로 기존 데이터를 수정한다

로그인한 사용자만 수정 가능하게 제어한다

내가 작성한 게시글만 수정할 수 있도록 조건을 설계한다

SELECT → UPDATE 흐름을 통해 “수정”의 전체 사이클을 이해한다

UPDATE 문에서도 WHERE 조건이 왜 중요한지 체험한다

---


# 1. 게시글 수정 기능 개요

게시글 수정은 다음 두 단계로 이루어진다.
```
게시글 상세 페이지
→ 수정 버튼 클릭
→ 기존 내용이 채워진 수정 폼 출력 (SELECT)
→ 수정 완료 버튼 클릭 (POST)
→ UPDATE SQL 실행
→ 게시글 상세 페이지로 이동
```

핵심 포인트는 다음이다.

- 수정 화면에서는 기존 데이터를 다시 조회해야 한다

- 수정 처리는 반드시 POST 요청으로 수행한다

- UPDATE 문에는 반드시 WHERE 조건이 필요하다


---

# 2. 수정 버튼 출력 (상세 페이지)
> 게시글 상세 페이지(post_view.php)에서 작성자 본인에게만 수정 버튼이 보이도록 한다.

## 2-1. 수정 버튼 HTML 추가
```
<?php if ($isOwner): ?>
  <a href="/post_edit.php?id=<?= htmlspecialchars((string)$post["id"]) ?>">
    수정
  </a>
<?php endif; ?>
```
- UI는 편의 기능이다

- 실제 권한 검사는 수정 페이지와 UPDATE SQL에서 다시 한 번 수행한다

---

# 3. 게시글 수정 폼 (기존 데이터 조회)

## 3-1. public/post_edit.php
```php
<?php
session_start();

require_once dirname(__DIR__) . "/lib/db.php";

// 로그인 체크
if (!isset($_SESSION["user"])) {
  echo "로그인 후 이용 가능합니다.";
  exit;
}

$pdo = db();

// GET 파라미터
$id = $_GET["id"] ?? null;

// 파라미터 검증
if ($id === null || !ctype_digit((string)$id)) {
  echo "잘못된 접근입니다.";
  exit;
}

$postId = (int)$id;
$userId = (int)$_SESSION["user"]["id"];

// 게시글 조회 (본인 글만)
$sql = "
  SELECT id, title, content
  FROM posts
  WHERE id = :id
    AND user_id = :user_id
  LIMIT 1
";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(":id", $postId, PDO::PARAM_INT);
$stmt->bindValue(":user_id", $userId, PDO::PARAM_INT);
$stmt->execute();

$post = $stmt->fetch();

if (!$post) {
  echo "수정할 수 없는 게시글입니다.";
  exit;
}
?>
<!doctype html>
<html lang="ko">
<head>
  <meta charset="utf-8">
  <title>게시글 수정</title>
</head>
<body>

<h1>게시글 수정</h1>

<form method="post" action="/post_edit_action.php">
  <input type="hidden" name="id" value="<?= htmlspecialchars((string)$post["id"]) ?>">

  <p>
    제목:
    <input type="text" name="title"
      value="<?= htmlspecialchars($post["title"]) ?>" required>
  </p>

  <p>
    내용:<br>
    <textarea name="content" rows="8" cols="60" required><?= htmlspecialchars($post["content"]) ?></textarea>
  </p>

  <p>
    <button type="submit">수정 완료</button>
  </p>
</form>

<p>
  <a href="/post_view.php?id=<?= htmlspecialchars((string)$post["id"]) ?>">취소</a>
</p>

</body>
</html>
```

---

# 4. 게시글 수정 처리 ( UPDATE + WHERE )

## 4-1. UPDATE SQL
```sql
UPDATE posts
SET title = :title,
    content = :content
WHERE id = :id
  AND user_id = :user_id;
```
- id 조건으로 대상 게시글을 특정한다
- user_id 조건으로 작성자 권한을 동시에 검사한다

## 4-2. public/post_edit_action.php
```php
<?php
session_start();

require_once dirname(__DIR__) . "/lib/db.php";

// 로그인 체크
if (!isset($_SESSION["user"])) {
  echo "로그인 후 이용 가능합니다.";
  exit;
}

$pdo = db();

// POST 데이터 수신
$id      = $_POST["id"] ?? null;
$title   = $_POST["title"] ?? "";
$content = $_POST["content"] ?? "";

// 검증
if (
  $id === null || !ctype_digit((string)$id) ||
  $title === "" || $content === ""
) {
  echo "입력값이 올바르지 않습니다.";
  exit;
}

$postId = (int)$id;
$userId = (int)$_SESSION["user"]["id"];

// UPDATE 실행
$sql = "
  UPDATE posts
  SET title = :title,
      content = :content
  WHERE id = :id
    AND user_id = :user_id
";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(":title", $title, PDO::PARAM_STR);
$stmt->bindValue(":content", $content, PDO::PARAM_STR);
$stmt->bindValue(":id", $postId, PDO::PARAM_INT);
$stmt->bindValue(":user_id", $userId, PDO::PARAM_INT);
$stmt->execute();

// 수정 결과 확인
if ($stmt->rowCount() === 0) {
  echo "수정할 수 없습니다.";
  exit;
}

// 수정 완료 후 상세 페이지로 이동
header("Location: /post_view.php?id=" . $postId);
exit;
```

---

# 5. 게시글 수정에서 UPDATE + WHERE 가 중요한 이유

UPDATE 문에서 WHERE 조건이 없다면
모든 게시글이 한 번에 수정된다.

또한,

- id 조건만 있으면 타인의 글도 수정 가능

- user_id 조건을 함께 넣으면 DB 차원에서 권한을 검증

즉, 게시글 수정 역시
코드가 아니라 DB가 최종 책임을 지는 구조다.
