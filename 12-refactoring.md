# 리팩토링 소개

## 🎯 이 장의 목적

지금까지 코드는 의도적으로 한 파일에 작성했다

이유:

- SQL 실행 위치를 눈으로 보게 하기 위해

- 요청 → 응답 흐름을 단순화하기 위해

실제 실무에서는:

- 공통 헤더/푸터 분리

- DB 접근 로직 분리

- MVC 또는 프레임워크 사용

이 강의에서는:

- 구조 개선보다 흐름 이해가 목적

--- 

#  1. 지금까지 작성한 코드의 특징

## 1-1. 지금까지 작성한 코드의 특징

지금까지 작성한 PHP 코드는 다음과 같은 특징이 있다.

- 한 페이지에 로직 + SQL + HTML이 함께 있음
- 페이지마다 DB 연결 코드가 반복됨
- 공통 header / footer를 분리하지 않음
- 함수, 클래스, 구조 분리를 거의 하지 않음

의도적으로 이렇게 작성했다.

```php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

if (!isset($_SESSION["user"])) {
  echo "로그인 후 이용 가능합니다.";
  exit;
}

require_once dirname(__DIR__) . "/lib/db.php";

// DB 연결 (PDO 객체 생성) 및 쿼리 실행코드
$pdo = db();
$sql = "...";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$posts = $stmt->fetchAll();

<!doctype html>
<html>
...
...
</html>
```

## 1-2. 왜 리팩토링을 하지 않았는가?

이 강의의 목표는 다음 하나에 집중한다.

> **브라우저 요청이 들어왔을 때  
> PHP가 언제 실행되고  
> SQL이 언제 실행되는지를 눈으로 확인하는 것**

그래서:
- 새로고침 = PHP 재실행
- PHP 실행 = SQL 실행
- SQL 결과 = HTML 출력

이 흐름이 **한 파일 안에서 바로 보이도록** 구성했다.

---

# 2. 왜 분리하는가? (유지보수 관점)

리팩토링의 핵심 이유는 **코드를 예쁘게 만들기 위해서가 아니다.**

> **기능이 늘어나고, 사람이 늘어나도  
> 수정이 쉬운 구조를 만들기 위해서다.**

## 2-1. 수정 범위를 줄이기 위해

예를 들어 모든 페이지에 다음 코드가 있다고 가정하자.

- 상단 메뉴 HTML
- 로그인 여부 체크
- 공통 CSS / JS

이 코드가 각 페이지에 복사되어 있다면:

- 메뉴 하나 수정하려고
- 모든 파일을 열어서
- 같은 코드를 전부 고쳐야 한다

이 상태는:
- 파일 수가 늘어날수록
- 실수할 확률이 급격히 증가한다

그래서 실무에서는:
- 공통 header / footer
- 공통 로그인 체크

를 **한 파일로 분리**한다.

→ 한 곳만 수정하면 전체에 반영된다.

---
## 2-2. “어디를 고쳐야 하는지” 바로 알기 위해

코드가 분리되어 있으면 역할이 명확해진다.

- 화면 문제 → HTML / View 쪽
- 데이터 문제 → SQL / DB 쪽
- 로그인 문제 → 인증 로직

반대로 모든 코드가 섞여 있으면:

- 어디를 고쳐야 할지 찾는 데 시간이 더 걸린다
- 실수로 다른 기능을 망가뜨리기 쉽다

유지보수에서 가장 큰 비용은:
> **코드를 고치는 시간보다  
> “이해하는 시간”이다.**

### 2-3. 여러 사람이 동시에 작업하기 위해

실무에서는 보통:

- 한 명이 프론트 화면 수정
- 한 명이 DB 쿼리 수정
- 한 명이 로그인/권한 처리

를 동시에 한다.

하지만 코드가 한 파일에 다 들어 있으면:

- 동시에 수정하기 어렵고
- 충돌이 자주 발생한다
- 책임 범위도 불분명해진다

그래서 역할별로 코드를 나눈다.

### 2-4. 테스트와 교체를 쉽게 하기 위해

코드가 분리되어 있으면:

- DB 코드만 교체
- 화면 코드만 수정
- 로그인 방식만 변경

같은 작업이 상대적으로 쉽다.

예:
- MySQL → 다른 DB
- 로그인 방식 변경
- 공통 UI 리뉴얼

분리된 구조에서는:
- 전체를 뜯어고치지 않아도 된다.

---

# 3. 그럼 리팩토링은 어떻게 하는가?

실제 실무에서는 당연히 리팩토링을 한다.

- 공통 레이아웃 분리
- DB 접근 로직 분리
- MVC 구조
- 프레임워크 사용 (Laravel, Spring Boot 등)

하지만 이런 작업은 다음과 같은 전제가 필요하다.

- 요청/응답 흐름을 이미 이해하고 있고
- SQL 실행 타이밍을 알고 있으며
- “왜 분리하는지”를 납득한 상태

---

# 4. 리팩토링 하기 전 메인페이지 html 과 css 구조 잡기

## 4-1. public/index.php (Bootstrap 적용, 전체 코드)

```php
<?php
session_start();

// DB 연결 함수 포함
require_once dirname(__DIR__) . "/lib/db.php";

// DB 연결
$pdo = db();

// --------------------------------------------------
// 메인페이지에 보여줄 게시글 개수
// --------------------------------------------------
$limit = 20;

// --------------------------------------------------
// 게시글 목록 조회 SQL
// --------------------------------------------------
$sql = "
SELECT
  t.id,
  t.title,
  t.view_count,
  t.created_at,
  t.nickname,
  COUNT(c.id) AS comment_cnt
FROM (
  SELECT
    p.id,
    p.title,
    p.view_count,
    p.created_at,
    u.nickname
  FROM posts p
  JOIN users u ON u.id = p.user_id
  ORDER BY p.id DESC
  LIMIT :limit
) AS t
LEFT JOIN comments c ON t.id = c.post_id
GROUP BY
  t.id, t.title, t.view_count, t.created_at, t.nickname
ORDER BY t.id DESC
";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(":limit", $limit, PDO::PARAM_INT);
$stmt->execute();

$posts = $stmt->fetchAll();
?>
<!doctype html>
<html lang="ko">

<head>
  <meta charset="utf-8" />
  <title>메인페이지</title>

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
  <div class="container py-4">

    <!-- 상단 제목 + 메뉴 -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h1 class="h3 mb-0">게시글 목록</h1>

      <div>
        <?php if (isset($_SESSION["user"])): ?>
          <span class="me-2">
            <?= htmlspecialchars($_SESSION["user"]["nickname"]) ?> 님
          </span>
          <a href="/post_create.php" class="btn btn-primary btn-sm">글쓰기</a>
          <a href="/logout.php" class="btn btn-outline-secondary btn-sm">로그아웃</a>
        <?php else: ?>
          <a href="/login.php" class="btn btn-outline-primary btn-sm">로그인</a>
          <a href="/register.php" class="btn btn-outline-secondary btn-sm">회원가입</a>
          <a href="/post_create.php" class="btn btn-primary btn-sm">글쓰기</a>
        <?php endif; ?>
      </div>
    </div>

    <!-- 게시글 목록 테이블 -->
    <table class="table table-striped table-hover align-middle">
      <thead class="table-light">
        <tr>
          <th>ID</th>
          <th>제목</th>
          <th>작성자</th>
          <th>조회수</th>
          <th>댓글수</th>
          <th>작성일</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($posts as $post): ?>
          <tr>
            <td><?= htmlspecialchars($post["id"]) ?></td>
            <td>
              <a href="/post_view.php?id=<?= htmlspecialchars($post["id"]) ?>" class="text-decoration-none">
                <?= htmlspecialchars($post["title"]) ?>
              </a>
            </td>
            <td><?= htmlspecialchars($post["nickname"]) ?></td>
            <td><?= htmlspecialchars($post["view_count"]) ?></td>
            <td>
              <span class="badge bg-secondary">
                <?= htmlspecialchars($post["comment_cnt"]) ?>
              </span>
            </td>
            <td><?= htmlspecialchars($post["created_at"]) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

  </div>
</body>

</html>
```

---

# 5. 리팩토링 해보기 – 메인페이지

> 이 리팩토링은 “정답 구조”가 아니다. 유지보수 관점에서 왜 분리하는지 체험하는 단계다.

### 리팩토링 목표 (딱 3가지만)

이번 리팩토링에서는 아래만 한다.

- 공통 레이아웃 분리 (header.php / footer.php)

- 페이지별 제목 처리 ($pageTitle)

- index.php 는 “이 페이지의 역할만” 남기기

## 5-1. lib/bootstrap.php ( 공통 헤더 )
```
<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

if (!isset($pageTitle)) {
  $pageTitle = "웹 페이지";
}
?>

```

## 5-2. lib/header.php ( 공통 레이아웃 헤더 )
```
<!doctype html>
<html lang="ko">
<head>
  <meta charset="utf-8" />
  <title><?php echo htmlspecialchars($pageTitle); ?></title>

  <!-- Bootstrap CSS -->
  <link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
  >
</head>

<body>
  <div class="container py-4">

    <!-- 상단 제목 + 메뉴 -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h1 class="h3 mb-0"><?php echo htmlspecialchars($pageTitle); ?></h1>

      <div>
        <?php if (isset($_SESSION["user"])): ?>
          <span class="me-2">
            <?php echo htmlspecialchars($_SESSION["user"]["nickname"]); ?> 님
          </span>
          <a href="/post_create.php" class="btn btn-primary btn-sm">글쓰기</a>
          <a href="/logout.php" class="btn btn-outline-secondary btn-sm">로그아웃</a>
        <?php else: ?>
          <a href="/login.php" class="btn btn-outline-primary btn-sm">로그인</a>
          <a href="/register.php" class="btn btn-outline-secondary btn-sm">회원가입</a>
          <a href="/post_create.php" class="btn btn-primary btn-sm">글쓰기</a>
        <?php endif; ?>
      </div>
    </div>
```

## 5-3. lib/footer.php ( 공통 레이아웃 푸터 )
```php
  </div>
</body>
</html>
```

## 5-4. public/index.php (리팩토링 후 전체)
```
<?php
require_once dirname(__DIR__) . "/lib/bootstrap.php";

// DB 연결
require_once dirname(__DIR__) . "/lib/db.php";

$pdo = db();

// 페이지당 게시글 수
$pageSize = 20;

// 현재 페이지
$page = $_GET["page"] ?? 1;
$type = $_GET["type"] ?? "";
$keyword = trim($_GET["keyword"] ?? "");

if (!ctype_digit((string) $page) || $page < 1) {
  $page = 1;
}
$page = (int) $page;

// OFFSET 계산
$offset = ($page - 1) * $pageSize;

$whereSql = "";
$params = [];

if ($keyword !== "") {
  if ($type === "title") {
    $whereSql = "WHERE p.title LIKE :keyword";
    $params[":keyword"] = "%" . $keyword . "%";
  } elseif ($type === "writer") {
    $whereSql = "WHERE u.nickname LIKE :keyword";
    $params[":keyword"] = "%" . $keyword . "%";
  }
}

$q = [];
if ($keyword !== "" && ($type === "title" || $type === "writer")) {
  $q["type"] = $type;
  $q["keyword"] = $keyword;
}

$queryString = $q ? "&" . http_build_query($q) : "";

// --------------------------------------------------
// 게시글 목록 조회 SQL
// --------------------------------------------------
$sql = "
SELECT
  t.id,
  t.title,
  t.view_count,
  t.created_at,
  t.nickname,
  COUNT(c.id) AS comment_cnt
FROM (
  SELECT
    p.id,
    p.title,
    p.view_count,
    p.created_at,
    u.nickname
  FROM posts p
  JOIN users u ON u.id = p.user_id
  " . $whereSql . "
  ORDER BY p.id DESC
  LIMIT :limit OFFSET :offset
) AS t
LEFT JOIN comments c ON t.id = c.post_id
GROUP BY
  t.id, t.title, t.view_count, t.created_at, t.nickname
ORDER BY t.id DESC
";

$stmt = $pdo->prepare($sql);

$stmt->bindValue(":limit", $pageSize, PDO::PARAM_INT);
$stmt->bindValue(":offset", $offset, PDO::PARAM_INT);

if ($whereSql !== "") {
  $stmt->bindValue(":keyword", $params[":keyword"], PDO::PARAM_STR);
}

$stmt->execute();
$posts = $stmt->fetchAll();

// 전체 게시글 수
$countSql = "
SELECT COUNT(*)
FROM posts p
JOIN users u ON u.id = p.user_id
" . $whereSql;

$countStmt = $pdo->prepare($countSql);

if ($whereSql !== "") {
  $countStmt->bindValue(":keyword", $params[":keyword"], PDO::PARAM_STR);
}

$countStmt->execute();
$totalCount = (int) $countStmt->fetchColumn();

// 총 페이지 수 계산
$totalPages = (int) ceil($totalCount / $pageSize);
if ($totalPages < 1) {
  $totalPages = 1;
}

$window = 5;

// 기본 범위
$startPage = $page - $window;
$endPage = $page + $window;

// 앞쪽이 잘렸으면 → 잘린 만큼 뒤로 보정
if ($startPage < 1) {
  $endPage += (1 - $startPage);
  $startPage = 1;
}

// 뒤쪽이 잘렸으면 → 잘린 만큼 앞으로 보정
if ($endPage > $totalPages) {
  $startPage -= ($endPage - $totalPages);
  $endPage = $totalPages;
}

// 다시 한번 하한 보정
if ($startPage < 1) {
  $startPage = 1;
}

$searchInfo = "전체 게시글 목록";

if ($keyword !== "" && ($type === "title" || $type === "writer")) {
  if ($type === "title") {
    $searchInfo = "제목에 '" . $keyword . "' 가 포함된 게시글 검색 결과";
  } elseif ($type === "writer") {
    $searchInfo = "작성자에 '" . $keyword . "' 가 포함된 게시글 검색 결과";
  }
}

// 페이지 제목
$pageTitle = "게시글 목록";

// 공통 레이아웃 헤더
require_once dirname(__DIR__) . "/lib/header.php";

?>
<form method="get" class="d-flex mb-3">
  <select name="type" class="form-select w-auto me-2">
    <option value="title" <?= ($type === "title") ? "selected" : "" ?>>제목</option>
    <option value="writer" <?= ($type === "writer") ? "selected" : "" ?>>작성자</option>
  </select>

  <input type="text" name="keyword" class="form-control me-2" placeholder="검색어 입력"
    value="<?= htmlspecialchars($keyword) ?>">

  <button class="btn btn-primary text-nowrap">검색</button>
</form>
<p class="text-muted mb-2">
  <?= htmlspecialchars($searchInfo) ?>
</p>
<!-- 게시글 목록 테이블 -->
<table class="table table-striped table-hover align-middle">
  <thead class="table-light">
    <tr>
      <th>ID</th>
      <th>제목</th>
      <th>작성자</th>
      <th>조회수</th>
      <th>댓글수</th>
      <th>작성일</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($posts as $post): ?>
      <tr>
        <td><?php echo htmlspecialchars($post["id"]); ?></td>
        <td>
          <a href="/post_view.php?id=<?php echo htmlspecialchars($post["id"]); ?>" class="text-decoration-none">
            <?php echo htmlspecialchars($post["title"]); ?>
          </a>
        </td>
        <td><?php echo htmlspecialchars($post["nickname"]); ?></td>
        <td><?php echo htmlspecialchars($post["view_count"]); ?></td>
        <td>
          <span class="badge bg-secondary">
            <?php echo htmlspecialchars($post["comment_cnt"]); ?>
          </span>
        </td>
        <td><?php echo htmlspecialchars($post["created_at"]); ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>




<nav aria-label="Page navigation">
  <ul class="pagination justify-content-center">

    <!-- 처음 -->
    <li class="page-item <?= ($page <= 1) ? "disabled" : "" ?>">
      <a class="page-link" href="?page=1<?= $queryString ?>">처음</a>
    </li>

    <!-- 이전 -->
    <li class="page-item <?= ($page <= 1) ? "disabled" : "" ?>">
      <a class="page-link" href="?page=<?= $page - 1 ?><?= $queryString ?>">이전</a>
    </li>

    <!-- 페이지 번호 -->
    <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
      <li class="page-item <?= ($i === $page) ? "active" : "" ?>">
        <a class="page-link" href="?page=<?= $i ?><?= $queryString ?>">
          <?= $i ?>
        </a>
      </li>
    <?php endfor; ?>

    <!-- 다음 -->
    <li class="page-item <?= ($page >= $totalPages) ? "disabled" : "" ?>">
      <a class="page-link" href="?page=<?= $page + 1 ?><?= $queryString ?>">다음</a>
    </li>

    <!-- 마지막 -->
    <li class="page-item <?= ($page >= $totalPages) ? "disabled" : "" ?>">
      <a class="page-link" href="?page=<?= $totalPages ?><?= $queryString ?>">마지막</a>
    </li>

  </ul>
</nav>


<?php
require_once dirname(__DIR__) . "/lib/footer.php";
```
---

# 6. 리팩토링 결과 요약

바뀐 것

- bootstrap.php 파일로 반복되는 php session_start 중복코드 제거

- 공통 HTML → header.php / footer.php

- 메뉴/레이아웃 한 곳에서 관리

> “리팩토링은 코드를 똑똑하게 만드는 게 아니라 고칠 곳을 줄이는 작업이다.”


---

# 7. /public/post_view.php 리팩토링

```
<?php
require_once dirname(__DIR__) . "/lib/bootstrap.php";

// DB 연결
require_once dirname(__DIR__) . "/lib/db.php";
$pdo = db();

// --------------------------------------------------
// GET 파라미터 수신
// --------------------------------------------------
$id = $_GET["id"] ?? null;

// --------------------------------------------------
// 파라미터 검증
// --------------------------------------------------
if ($id === null || !ctype_digit((string) $id)) {
  echo "잘못된 접근입니다.";
  exit;
}
$postId = (int) $id;

// --------------------------------------------------
// 게시글 상세 조회 (SELECT + WHERE + JOIN)
// --------------------------------------------------
$sql = "
  SELECT
    p.id,
    p.user_id,
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
$stmt->bindValue(":id", $postId, PDO::PARAM_INT);
$stmt->execute();

$post = $stmt->fetch();

if (!$post) {
  echo "게시글이 존재하지 않습니다.";
  exit;
}

$pageTitle = (string) $post["title"];

// --------------------------------------------------
// 조회수 증가 (UPDATE + WHERE) - 쿠키로 중복 방지
// --------------------------------------------------
$cookieName = "viewed_post_" . (string) $postId;
$cookieTtlSeconds = 60 * 10; // 10분

if (!isset($_COOKIE[$cookieName])) {
  $updateSql = "
    UPDATE posts
    SET view_count = view_count + 1
    WHERE id = :id
  ";
  $updateStmt = $pdo->prepare($updateSql);
  $updateStmt->bindValue(":id", $postId, PDO::PARAM_INT);
  $updateStmt->execute();

  setcookie($cookieName, "1", time() + $cookieTtlSeconds, "/");

  // 화면 표시용 조회수도 즉시 +1 반영 (새로고침 없이도 숫자 일치)
  $post["view_count"] = (int) $post["view_count"] + 1;
}

// --------------------------------------------------
// 댓글 목록 조회 (SELECT + WHERE + JOIN)
// --------------------------------------------------
$commentSql = "
  SELECT
    c.id,
    c.user_id,
    c.comment,
    c.created_at,
    u.nickname
  FROM comments c
  JOIN users u ON u.id = c.user_id
  WHERE c.post_id = :post_id
  ORDER BY c.id DESC
";

$commentStmt = $pdo->prepare($commentSql);
$commentStmt->bindValue(":post_id", $postId, PDO::PARAM_INT);
$commentStmt->execute();
$comments = $commentStmt->fetchAll();

// --------------------------------------------------
// 댓글 개수 조회 (COUNT)
// --------------------------------------------------
$countSql = "
  SELECT COUNT(*) AS comment_count
  FROM comments
  WHERE post_id = :post_id
";

$countStmt = $pdo->prepare($countSql);
$countStmt->bindValue(":post_id", $postId, PDO::PARAM_INT);
$countStmt->execute();

$commentCount = (int) $countStmt->fetchColumn();

// --------------------------------------------------
// 권한 체크 (본인 글 여부)
// --------------------------------------------------
$isOwner = false;
$loginUserId = null;

if (isset($_SESSION["user"])) {
  $loginUserId = (int) $_SESSION["user"]["id"];
  $postUserId = (int) $post["user_id"];
  $isOwner = ($loginUserId === $postUserId);
}


require_once dirname(__DIR__) . "/lib/header.php";

?>

<!-- 게시글 카드 -->
<div class="card mb-4">
  <div class="card-body">

    <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
      <div>
        <h2 class="h4 mb-2"><?php echo htmlspecialchars((string) $post["title"]); ?></h2>
        <div class="text-muted small">
          작성자: <?php echo htmlspecialchars((string) $post["nickname"]); ?>
          <span class="mx-2">|</span>
          조회수: <?php echo htmlspecialchars((string) $post["view_count"]); ?>
          <span class="mx-2">|</span>
          작성일: <?php echo htmlspecialchars((string) $post["created_at"]); ?>
        </div>
      </div>

      <?php if ($isOwner): ?>
        <div class="d-flex align-items-center gap-2">
          <a href="/post_edit.php?id=<?php echo htmlspecialchars((string) $post["id"]); ?>"
            class="btn btn-outline-primary btn-sm">
            수정
          </a>

          <form method="post" action="/post_delete_action.php" onsubmit="return confirm('정말 삭제할까요?');" class="m-0">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars((string) $post["id"]); ?>">
            <button type="submit" class="btn btn-outline-danger btn-sm">삭제</button>
          </form>
        </div>
      <?php endif; ?>
    </div>

    <hr>

    <div class="lh-lg">
      <?php echo nl2br(htmlspecialchars((string) $post["content"])); ?>
    </div>

    <hr>

    <div class="d-flex justify-content-end">
      <a href="/" class="btn btn-secondary btn-sm">목록으로</a>
    </div>
  </div>
</div>

<!-- 댓글 작성 -->
<div class="card mb-4">
  <div class="card-body">
    <h3 class="h6 mb-3">댓글 작성</h3>

    <?php if (isset($_SESSION["user"])): ?>
      <form method="post" action="/comment_create_action.php">
        <input type="hidden" name="post_id" value="<?php echo htmlspecialchars((string) $post["id"]); ?>">

        <div class="mb-3">
          <textarea name="comment" rows="4" class="form-control" required></textarea>
        </div>

        <div class="d-flex justify-content-end">
          <button type="submit" class="btn btn-primary btn-sm">댓글 등록</button>
        </div>
      </form>
    <?php else: ?>
      <div class="alert alert-secondary mb-0">
        댓글 작성은 로그인 후 이용 가능합니다.
        <a href="/login.php" class="alert-link">로그인</a>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- 댓글 목록 -->
<div class="card">
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h3 class="h6 mb-0">댓글 목록</h3>
      <span class="badge bg-secondary">
        <?php echo htmlspecialchars((string) $commentCount); ?>
      </span>
    </div>

    <?php if (count($comments) === 0): ?>
      <p class="text-muted mb-0">아직 댓글이 없습니다.</p>
    <?php else: ?>
      <div class="list-group">
        <?php foreach ($comments as $c): ?>
          <div class="list-group-item">
            <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
              <div>
                <div class="fw-semibold">
                  <?php echo htmlspecialchars((string) $c["nickname"]); ?>
                  <span class="text-muted small ms-2">
                    <?php echo htmlspecialchars((string) $c["created_at"]); ?>
                  </span>
                </div>

                <div class="mt-2">
                  <?php echo nl2br(htmlspecialchars((string) $c["comment"])); ?>
                </div>
              </div>

              <?php if ($loginUserId !== null && $loginUserId === (int) $c["user_id"]): ?>
                <form method="post" action="/comment_delete_action.php" onsubmit="return confirm('댓글을 삭제할까요?');" class="m-0">
                  <input type="hidden" name="comment_id" value="<?php echo htmlspecialchars((string) $c["id"]); ?>">
                  <input type="hidden" name="post_id" value="<?php echo htmlspecialchars((string) $postId); ?>">
                  <button type="submit" class="btn btn-outline-danger btn-sm">삭제</button>
                </form>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php
require_once dirname(__DIR__) . "/lib/footer.php";
```