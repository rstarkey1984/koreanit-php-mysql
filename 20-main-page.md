# 메인페이지 작성 ( SELECT + JOIN )


## 🎯 학습 목표

- posts 목록을 최신순으로 조회

- 작성자 nickname 을 users 테이블에서 JOIN 해서 같이 출력

- PDO는 prepare → bindValue(필요시) → execute → fetchAll 패턴만 사용

---


# 1. 게시글 목록 조회 

## 1-1. SQL (SELECT + JOIN + ORDER BY + LIMIT)

### 최근 게시물 가져오는 쿼리
```sql
SELECT
  p.id,          -- 게시글의 고유 ID (posts 테이블의 PK)
  p.title,       -- 게시글 제목
  p.view_count,  -- 게시글 조회수
  p.created_at,  -- 게시글 작성일
  u.nickname     -- 게시글 작성자의 닉네임 (users 테이블에서 가져옴)
FROM posts p
  -- posts 테이블을 기준으로 조회
JOIN users u
  -- 게시글 작성자 정보를 가져오기 위해 users 테이블과 조인
  ON u.id = p.user_id
  -- posts.user_id (FK) 와 users.id (PK) 를 연결
ORDER BY p.id DESC
  -- 게시글 ID 기준 내림차순 정렬
  -- 최신 게시글이 가장 위에 나오도록 하기 위함
LIMIT 20;
  -- 결과 중 상위 20개 행만 조회
  -- 메인페이지에 보여줄 게시글 개수 제한

```

> LIMIT 20 은 바인딩해서 처리


## 1-2. JOIN 이 필요한 이유

posts 테이블에는 작성자의 ID(user_id)만 저장되어 있다.  
따라서 작성자의 닉네임을 화면에 출력하려면 users 테이블과 JOIN 이 필요하다.

- posts.user_id  → users.id (외래키 관계)
- JOIN 이 없으면 nickname 컬럼은 조회할 수 없다

## 1-3. posts 테이블과 users 테이블 관계

| 테이블 | 컬럼 | 설명 |
|------|------|------|
| users | id | 사용자 PK |
| posts | user_id | 게시글 작성자 (FK) |

posts.user_id 는 users.id 를 참조하는 외래키(FK)이다.
이 관계를 기준으로 게시글과 작성자 정보를 연결한다.


---

# 2. public/index.php 파일 작성
```php
<?php
// DB 연결 함수 포함
// lib/db.php 안에는 PDO 객체를 생성해서 반환하는 db() 함수가 있음
require_once dirname(__DIR__) . "/lib/db.php";

// DB 연결 (PDO 객체 생성)
$pdo = db();

// --------------------------------------------------
// 메인페이지에 보여줄 게시글 개수
// LIMIT에 바인딩해서 사용할 값
// --------------------------------------------------
$limit = 20;

// --------------------------------------------------
// SQL 준비
// - posts 테이블과 users 테이블을 JOIN
// - 작성자 닉네임(users.nickname)을 같이 조회
// - 최신 글이 위에 오도록 id 내림차순
// - LIMIT 은 바인딩 파라미터 사용
// --------------------------------------------------
$sql = "
  SELECT
    p.id,           -- 게시글 ID
    p.title,        -- 게시글 제목
    p.view_count,   -- 조회수
    p.created_at,   -- 작성일
    u.nickname      -- 작성자 닉네임
  FROM posts p
  JOIN users u ON u.id = p.user_id
  ORDER BY p.id DESC
  LIMIT :limit
";

// --------------------------------------------------
// SQL 준비 단계
// query() 사용 금지 → prepare() 사용
// --------------------------------------------------
$stmt = $pdo->prepare($sql);

// --------------------------------------------------
// 바인딩
// LIMIT 은 숫자이므로 PDO::PARAM_INT 사용
// --------------------------------------------------
$stmt->bindValue(":limit", $limit, PDO::PARAM_INT);

// --------------------------------------------------
// SQL 실행
// --------------------------------------------------
$stmt->execute();

// --------------------------------------------------
// 결과 전체 가져오기
// fetchAll() → 여러 행을 배열로 반환
// --------------------------------------------------
$posts = $stmt->fetchAll();
?>
<!doctype html>
<html lang="ko">
<head>
  <meta charset="utf-8" />
  <title>메인페이지</title>
</head>
<body>

<h1>게시글 목록</h1>

<!-- 상단 메뉴 -->
<p>
  <a href="/register.php">회원가입</a> |
  <a href="/login.php">로그인</a> |
  <a href="/post_create.php">글쓰기</a>
</p>

<!-- 게시글 목록 테이블 -->
<table border="1" cellpadding="6" cellspacing="0">
  <thead>
    <tr>
      <th>ID</th>
      <th>제목</th>
      <th>작성자</th>
      <th>조회수</th>
      <th>작성일</th>
    </tr>
  </thead>
  <tbody>

  <!-- PHP foreach 문으로 게시글 목록 출력 -->
  <?php foreach ($posts as $post): ?>
    <tr>
      <!-- 게시글 ID -->
      <td><?= htmlspecialchars($post["id"]) ?></td>

      <!-- 제목 클릭 시 게시글 상세 페이지로 이동 -->
      <td>
        <a href="/post_view.php?id=<?= htmlspecialchars($post["id"]) ?>">
          <?= htmlspecialchars($post["title"]) ?>
        </a>
      </td>

      <!-- 작성자 닉네임 -->
      <td><?= htmlspecialchars($post["nickname"]) ?></td>

      <!-- 조회수 -->
      <td><?= htmlspecialchars($post["view_count"]) ?></td>

      <!-- 작성일 -->
      <td><?= htmlspecialchars($post["created_at"]) ?></td>
    </tr>
  <?php endforeach; ?>

  </tbody>
</table>

</body>
</html>
```

---

# 🧩 실습 / 과제 

## 1. LIMIT 값 바꿔보기

$limit = 20; 을 5, 10, 30 으로 바꿔서

화면에 출력되는 게시글 개수가 바뀌는지 확인

SQL이 execute() 시점에 실행된다는 것도 다시 확인

### 체크포인트

새로고침(F5)할 때마다 SQL이 실행되는가?


## 2. 댓글 수 출력하기 (LEFT JOIN + GROUP BY)

목표 : 게시글 목록에 댓글수(comment_count) 컬럼 추가 출력

### SQL 참고 
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
  LIMIT 20
) AS t
LEFT JOIN comments c ON t.id = c.post_id
GROUP BY
  t.id, t.title, t.view_count, t.created_at, t.nickname
ORDER BY t.id DESC;
```

- 댓글이 없는 글도 보여야 하므로 LEFT JOIN

- COUNT(c.id)는 집계라서 GROUP BY 필요

## 3. HTML 코드 변경
```
<tr>
  <th>ID</th>
  <th>제목</th>
  <th>작성자</th>
  <th>조회수</th>
  <th>댓글수</th>
  <th>작성일</th>
</tr>
```
```
<!-- 댓글수 -->
<td><?= htmlspecialchars($post["comment_cnt"]) ?></td>
```