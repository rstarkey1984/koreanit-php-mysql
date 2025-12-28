# 게시글 검색 ( WHERE + LIKE + 페이징 연동 )

## 🎯 이 장의 목표

- 게시글이 많아졌을 때 검색 기능이 왜 필요한지 이해한다

- WHERE + LIKE 를 이용해 조건 검색 SQL을 작성한다

- 검색 결과를 기존 페이징 로직과 연동한다

- 목록 조회 SQL과 COUNT SQL이 항상 쌍으로 움직임을 이해한다

--- 


# 1. 왜 검색 기능이 필요한가?

현재 게시판은:

- 페이지 이동으로만 게시글을 탐색함

- 게시글 수가 많아질수록 원하는 글을 찾기 어려움

실무 게시판에서는 항상:

- 검색 + 페이징이 함께 사용됨

- 검색 결과 역시 “목록”이므로 페이징이 필요함



---

# 2. 검색 파라미터 수신 (PHP)
```php
$type = $_GET["type"] ?? "";
$keyword = trim($_GET["keyword"] ?? "");
```
- 검색하지 않았을 경우: 빈 문자열
- trim()으로 공백만 입력되는 경우 제거

---

# 3. 검색 UI 추가 


## 3-1. 검색어 입력폼 (GET 방식)
```php
<form method="get" class="d-flex mb-3">
  <select name="type" class="form-select w-auto me-2">
    <option value="title" <?= ($type === "title") ? "selected" : "" ?>>제목</option>
    <option value="writer" <?= ($type === "writer") ? "selected" : "" ?>>작성자</option>
  </select>

  <input
    type="text"
    name="keyword"
    class="form-control me-2"
    placeholder="검색어 입력"
    value="<?= htmlspecialchars($keyword) ?>"
  >
  
  <button class="btn btn-primary text-nowrap">검색</button>
</form>
```

GET 방식을 사용하는 이유

- 검색 조건이 URL에 남는다

- 페이지 이동 시 검색 상태 유지 가능

- 페이징과 자연스럽게 결합된다

## 3-2. 검색 상태 문구 만들기 (PHP)
```php
$searchInfo = "전체 게시글 목록";

if ($keyword !== "" && ($type === "title" || $type === "writer")) {
  if ($type === "title") {
    $searchInfo = "제목에 '" . $keyword . "' 가 포함된 게시글 검색 결과";
  } elseif ($type === "writer") {
    $searchInfo = "작성자에 '" . $keyword . "' 가 포함된 게시글 검색 결과";
  }
}
```

## 3-3. 화면에 출력 (`<table>` 태그 위 추천)
```php
<p class="text-muted mb-2">
  <?= htmlspecialchars($searchInfo) ?>
</p>
```


---

# 4. WHERE 절 동적 구성

## 4-1. 검색 조건이 없는 경우

- 전체 게시글 목록 조회

## 4-2. 검색 조건이 있는 경우

- 제목 또는 작성자 기준 검색


```php
$whereSql = "";
$params = [];

if ($keyword !== "") {
  if ($type === "title") {
    $whereSql = "WHERE p.title LIKE :keyword";
    $params[":keyword"] = "%".$keyword."%";
  } elseif ($type === "writer") {
    $whereSql = "WHERE u.nickname LIKE :keyword";
    $params[":keyword"] = "%".$keyword."%";
  }
}
```

핵심 포인트

- 조건이 있을 때만 WHERE 추가

- LIKE 검색은 %검색어% 형태로 구성

- SQL Injection 방지를 위해 바인딩 사용

---

# 5. 검색 + 페이징 목록 조회 SQL

```php
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
  ".$whereSql."
  ORDER BY p.id DESC
  LIMIT :limit OFFSET :offset
) AS t
LEFT JOIN comments c ON t.id = c.post_id
GROUP BY
  t.id, t.title, t.view_count, t.created_at, t.nickname
ORDER BY t.id DESC
";
```
설명
- 검색 조건은 게시글을 자르기 전에 적용

- LIMIT/OFFSET은 서브쿼리에서 먼저 적용

- 댓글은 LEFT JOIN으로 개수만 집계

---


# 6. 바인딩 처리

```php
$stmt = $pdo->prepare($sql);

$stmt->bindValue(":limit", $pageSize, PDO::PARAM_INT);
$stmt->bindValue(":offset", $offset, PDO::PARAM_INT);

if ($whereSql !== "") {
  $stmt->bindValue(":keyword", $params[":keyword"], PDO::PARAM_STR);
}

$stmt->execute();
$posts = $stmt->fetchAll();
```

---


# 7. 검색 조건을 반영한 전체 게시글 수 계산 ( COUNT(*) )

검색 결과도 “목록”이기 때문에 전체 개수(totalCount)가 필요하다.
이 값으로 totalPages를 계산해서 페이지 네비게이션을 만든다.

중요: 목록 SQL에 적용한 WHERE 조건이 COUNT SQL에도 동일하게 들어가야 한다.
(조건이 다르면 페이지 수가 틀어져서 페이징이 깨진다)


```php
$countSql = "
SELECT COUNT(*)
FROM posts p
JOIN users u ON u.id = p.user_id
".$whereSql;

$countStmt = $pdo->prepare($countSql);

if ($whereSql !== "") {
  $countStmt->bindValue(":keyword", $params[":keyword"], PDO::PARAM_STR);
}

$countStmt->execute();
$totalCount = (int) $countStmt->fetchColumn();
```

---

# 8. 페이징 링크에 검색 조건 유지
검색한 상태에서 페이지 이동을 하면 검색 조건이 URL에서 사라질 수 있다.
따라서 페이지 링크를 만들 때 검색 파라미터(type, keyword)를 같이 붙여야 한다.

먼저, 검색 파라미터를 URL에 붙일 문자열로 만든다.


```php
$q = [];
if ($keyword !== "" && ($type === "title" || $type === "writer")) {
  $q["type"] = $type;
  $q["keyword"] = $keyword;
}
$queryString = $q ? "&".http_build_query($q) : "";
```


---


# 9. 페이지 번호 출력 (검색 조건 유지 버전)

```php
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
```