# 게시글 상세 페이지 ( SELECT + WHERE + JOIN )

## 🎯 학습 목표

- URL 파라미터(?id=)를 통해 특정 게시글을 조회한다

- SELECT + WHERE 로 게시글 1건을 조회한다

- JOIN 을 사용해 작성자 정보를 함께 조회한다

- 게시글 ID(PK)를 기준으로 조회하는 이유를 이해한다

- PDO의 prepare → bindValue → execute → fetch 패턴을 익힌다

---

# 1. 게시글 상세 페이지 개요

게시글 상세 페이지는
게시글 목록에서 선택한 게시글 1개를 조회하는 화면이다.

이 장에서는 다음 흐름을 구현한다.

```sql
게시글 ID 전달 (?id=...)
→ PHP에서 GET 파라미터 수신
→ SELECT + WHERE 실행
→ users 테이블 JOIN
→ 게시글 상세 화면 출력
```

## 1-1. 게시글 상세 조회에 필요한 정보

게시글 상세 페이지에서는 다음 정보를 출력한다.

- 게시글 제목

- 게시글 내용

- 작성자 닉네임

- 조회수

- 작성일

이를 위해 posts 와 users 테이블을 함께 조회한다.

---

# 2. 게시글 상세 조회 SQL

## 2-1. SQL (SELECT + WHERE + JOIN)
```sql
SELECT
  p.id,
  p.title,
  p.content,
  p.view_count,
  p.created_at,
  u.nickname
FROM posts p
JOIN users u ON u.id = p.user_id
WHERE p.id = :id
LIMIT 1;
```

---

# 3. public/post_view.php 파일 작성
```php
<?php
require_once dirname(__DIR__) . "/lib/db.php";

$pdo = db();

// --------------------------------------------------
// GET 파라미터 수신
// --------------------------------------------------
$id = $_GET["id"] ?? null;

// --------------------------------------------------
// 파라미터 검증
// --------------------------------------------------
if ($id === null || !ctype_digit($id)) {
  echo "잘못된 접근입니다.";
  exit;
}

// --------------------------------------------------
// SQL 준비 (SELECT + WHERE + JOIN)
// --------------------------------------------------
$sql = "
  SELECT
    p.id,
    p.title,
    p.content,
    p.view_count,
    p.created_at,
    u.nickname
  FROM posts p
  JOIN users u ON u.id = p.user_id
  WHERE p.id = :id
  LIMIT 1
";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(":id", (int)$id, PDO::PARAM_INT);
$stmt->execute();

// --------------------------------------------------
// 결과 1건 조회
// --------------------------------------------------
$post = $stmt->fetch();

if (!$post) {
  echo "게시글이 존재하지 않습니다.";
  exit;
}
?>
<!doctype html>
<html lang="ko">
<head>
  <meta charset="utf-8" />
  <title>게시글 상세</title>
</head>
<body>

<h1><?= htmlspecialchars($post["title"]) ?></h1>

<p>
  작성자: <?= htmlspecialchars($post["nickname"]) ?> |
  조회수: <?= htmlspecialchars($post["view_count"]) ?> |
  작성일: <?= htmlspecialchars($post["created_at"]) ?>
</p>

<hr>

<div>
  <?= nl2br(htmlspecialchars($post["content"])) ?>
</div>

<hr>

<p>
  <a href="/">목록으로</a>
</p>

</body>
</html>
```

---

# 4. 게시글 상세 조회에서 WHERE 가 중요한 이유

게시글 목록은 여러 건을 조회하지만,
게시글 상세 페이지는 단 하나의 게시글만 조회한다.

따라서 반드시 다음이 필요하다.

- WHERE p.id = :id

- PK 기반 조회

> PK로 조회하면 항상 빠르고, 결과가 명확하다.

---

# 🧩 실습 / 과제 — 조회수 증가 ( UPDATE + WHERE )

### 목표

- 게시글 상세 페이지를 열 때마다 조회수가 1씩 증가하도록 만든다

- UPDATE + WHERE 를 실제 흐름 속에서 체험한다

- SELECT 전에 UPDATE가 실행되는 이유를 이해한다

## 1. 조회수 증가 SQL 작성
```sql
UPDATE posts
SET view_count = view_count + 1
WHERE id = :id;
```

## 2. public/post_view.php 에 조회수 증가 로직 추가
> SELECT 실행 전에 아래 코드를 추가한다.
```php
// --------------------------------------------------
// 조회수 증가 (UPDATE + WHERE)
// --------------------------------------------------
$updateSql = "
  UPDATE posts
  SET view_count = view_count + 1
  WHERE id = :id
";

$updateStmt = $pdo->prepare($updateSql);
$updateStmt->bindValue(":id", (int)$id, PDO::PARAM_INT);
$updateStmt->execute();
```

### 확인할 것

- 같은 게시글을 새로고침할 때마다 조회수가 증가하는지

- 다른 게시글을 열면 각각 독립적으로 증가하는지


## 3. 쿠키 기반 조회수 제한 (예: 10분 동안 같은 글 조회수 증가 금지)

```php
// -------------------------------------------
// 쿠키 설정: 10분 동안 같은 글 조회수 증가 방지
// -------------------------------------------
$cookieName = "viewed_post_" . $id;
$cookieTtlSeconds = 60 * 10; // 10분

// 쿠키가 없으면: "처음 본 것"으로 판단 → 조회수 증가
if (!isset($_COOKIE[$cookieName])) {

  // 조회수 증가
  $updateSql = "
    UPDATE posts
    SET view_count = view_count + 1
    WHERE id = :id
  ";

  $updateStmt = $pdo->prepare($updateSql);
  $updateStmt->bindValue(":id", (int)$id, PDO::PARAM_INT);
  $updateStmt->execute();

  // 쿠키 저장 (10분 유지)
  // path="/" 로 해야 사이트 전체에서 쿠키 유지
  setcookie($cookieName, "1", time() + $cookieTtlSeconds, "/");
}
```