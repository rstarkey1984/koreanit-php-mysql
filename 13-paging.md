# 페이징 처리 ( LIMIT / OFFSET )

## 🎯 이 장의 목표

- 게시글이 많아졌을 때 왜 페이징이 필요한지 이해한다

- LIMIT / OFFSET 이 SQL에서 어떤 역할을 하는지 체험한다

- URL 파라미터(?page=1)로 데이터 범위를 제어한다

---

# 1. 왜 페이징이 필요한가?

지금 메인페이지는:
```
LIMIT 20
```

으로 항상 최신 게시글 20개만 가져온다.

문제점:

- 게시글이 1000개여도 항상 최신 20개만 보임

- 더 오래된 글은 접근할 수 없음

- 실무에서는 “페이지 이동”이 필요함

---

# 2. 페이징 기본 개념
## 2-1. 페이지 번호 → OFFSET 계산

한 페이지당 게시글 수: pageSize = 20

현재 페이지: page
```
1페이지 → OFFSET 0
2페이지 → OFFSET 20
3페이지 → OFFSET 40
```

공식:
```php
$offset = ($page - 1) * $pageSize;
```

---

# 3. 페이지 번호 받기 및 OFFSET 계산 (GET 파라미터)

```php
// 현재 페이지 번호
// - page 파라미터가 없으면 1
// - 숫자가 아니거나 1보다 작으면 1로 보정
$page = $_GET["page"] ?? 1;

// 페이지당 게시글 수 (LIMIT 기준)
$pageSize = 20;

if (!ctype_digit((string) $page) || $page < 1) {
  $page = 1;
}
$page = (int) $page;

// OFFSET 계산
// 1페이지 → 0
// 2페이지 → 20
// 3페이지 → 40
$offset = ($page - 1) * $pageSize;
```

---

# 4. SQL ( LIMIT + OFFSET )

## 4-1. 워크벤치에서 기존 SQL OFFSET 바꿔가면서 테스트
```sql
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
  LIMIT 20 OFFSET 0
) AS t
LEFT JOIN comments c ON t.id = c.post_id
GROUP BY
  t.id, t.title, t.view_count, t.created_at, t.nickname
ORDER BY t.id DESC
```

## 4-2. 변경된 SQL 로 수정 
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
  ORDER BY p.id DESC
  LIMIT :limit OFFSET :offset
) AS t
LEFT JOIN comments c ON t.id = c.post_id
GROUP BY
  t.id, t.title, t.view_count, t.created_at, t.nickname
ORDER BY t.id DESC
";
```


## 4-3. 바인딩
```
// LIMIT / OFFSET 은 반드시 정수 바인딩
$stmt->bindValue(":limit", $limit, PDO::PARAM_INT);
$stmt->bindValue(":offset", $offset, PDO::PARAM_INT);
```

## 4-4. GET 파라미터로 요청해서 확인
```
http://test.localhost/?page=1
http://test.localhost/?page=2
http://test.localhost/?page=3
```

---

# 5. 페이징 네비게이션 계산
> 페이징 네비게이션 계산이란    
> 현재 페이지(page)와 전체 페이지 수(totalPages)를 기준으로   
> 화면에 표시할 페이지 번호 범위를 계산하는 로직이다.   

- 현재 페이지 기준으로 앞 5개 + 뒤 5개 페이지 번호를 계산한다

- 범위를 벗어나지 않도록 자동 보정한다

- DB 쿼리와 완전히 분리된 UI 로직임을 이해한다


## 5-1. 전체 페이지 수 계산 ( COUNT(*) )
```php
// --------------------------------------------------
// 전체 게시글 수 조회
// - 페이지 네비게이션 계산용
// - 댓글 테이블은 JOIN하지 않는다
//   (JOIN하면 게시글 수가 부풀어짐)
// --------------------------------------------------
$countSql = "SELECT COUNT(*) FROM posts";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute();
$totalCount = (int) $countStmt->fetchColumn();

// 총 페이지 수
// - 나머지가 있으면 페이지 하나 추가
$totalPages = (int) ceil($totalCount / $pageSize);

// 최소 1페이지 보장
if ($totalPages < 1) {
  $totalPages = 1;
}
```
- COUNT(*) → 전체 게시글 개수

- ceil() → 나머지가 있으면 페이지 하나 더

- 최소 페이지 수는 1

## 5-2. 페이지 번호 범위 계산 (앞 5개 + 뒤 5개)
> “현재 페이지 기준으로 최대 11개를 보여주되, 1페이지와 마지막 페이지를 절대 넘지 않게 자른다.”
```php
// 페이지 네비게이션 범위 계산
// - 현재 페이지 기준 앞/뒤 5개
$window = 5;

// 기본 범위
$startPage = $page - $window;
$endPage   = $page + $window;

// 앞쪽이 잘렸으면 → 잘린 만큼을 뒤쪽으로 이동
if ($startPage < 1) {
  $endPage += (1 - $startPage);
  $startPage = 1;
}

// 뒤쪽이 잘렸으면 → 잘린 만큼을 앞쪽으로 이동
if ($endPage > $totalPages) {
  $startPage -= ($endPage - $totalPages);
  $endPage = $totalPages;
}

// 최종 하한 보정 (전체 페이지 수가 적은 경우)
if ($startPage < 1) {
  $startPage = 1;
}
```
계산 규칙 요약

- 현재 페이지를 항상 포함한다

- 최대 11개(5 + 현재 + 5) 페이지 번호를 표시한다

- 전체 페이지 수가 적으면 가능한 만큼만 표시한다


## 5-3. 페이지 번호 출력 (Bootstrap)
```php
<nav aria-label="Page navigation">
  <ul class="pagination justify-content-center">

    <!-- 처음 -->
    <li class="page-item <?= ($page <= 1) ? "disabled" : "" ?>">
      <a class="page-link" href="?page=1">처음</a>
    </li>

    <!-- 이전 -->
    <li class="page-item <?= ($page <= 1) ? "disabled" : "" ?>">
      <a class="page-link" href="?page=<?= $page - 1 ?>">이전</a>
    </li>

    <!-- 페이지 번호 -->
    <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
      <li class="page-item <?= ($i === $page) ? "active" : "" ?>">
        <a class="page-link" href="?page=<?= $i ?>">
          <?= $i ?>
        </a>
      </li>
    <?php endfor; ?>

    <!-- 다음 -->
    <li class="page-item <?= ($page >= $totalPages) ? "disabled" : "" ?>">
      <a class="page-link" href="?page=<?= $page + 1 ?>">다음</a>
    </li>

    <!-- 마지막 -->
    <li class="page-item <?= ($page >= $totalPages) ? "disabled" : "" ?>">
      <a class="page-link" href="?page=<?= $totalPages ?>">마지막</a>
    </li>

  </ul>
</nav>
```


## 5-4. 동작 예시

| 현재 페이지 | 전체 페이지 | 표시되는 페이지 번호                        |
| ------ | ------ | ---------------------------------- |
| 1      | 50     | `[1] 2 3 4 5 6 7 8 9 10 11`          |
| 3      | 50     | `1 2 [3] 4 5 6 7 8 9 10 11`          |
| 10     | 50     | `5 6 7 8 9 [10] 11 12 13 14 15`    |
| 48     | 50     | `40 41 42 43 44 45 46 47 [48] 49 50` |

