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
session_start();

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

## 5-1. lib/header.php ( 공통 레이아웃 헤더 )
```
<?php
session_start();

if (!isset($pageTitle)) {
  $pageTitle = "게시판";
}
?>
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

## 5-2. lib/footer.php ( 공통 레이아웃 푸터 )
```php
  </div>
</body>
</html>
```

## 5-3. public/index.php (리팩토링 후 전체)
```
<?php
// 페이지 제목
$pageTitle = "게시글 목록";

// 공통 레이아웃
require_once dirname(__DIR__) . "/lib/header.php";

// DB 연결
require_once dirname(__DIR__) . "/lib/db.php";
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
          <a
            href="/post_view.php?id=<?php echo htmlspecialchars($post["id"]); ?>"
            class="text-decoration-none"
          >
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

<?php
require_once dirname(__DIR__) . "/lib/footer.php";
```

---

# 6. 리팩토링 결과 요약

바뀐 것

- 공통 HTML → header.php / footer.php

- Bootstrap 중복 제거

- 메뉴/레이아웃 한 곳에서 관리

> “리팩토링은 코드를 똑똑하게 만드는 게 아니라 고칠 곳을 줄이는 작업이다.”

