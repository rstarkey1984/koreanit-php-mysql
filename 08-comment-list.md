# 댓글 목록 조회 ( SELECT + WHERE + JOIN | GROUP BY / ORDER BY )

## 🎯 학습 목표

- 특정 게시글(post_id)의 댓글만 조회한다 (WHERE)

- comments 와 users 를 JOIN 해서 작성자 nickname 을 함께 출력한다 (JOIN)

- 댓글을 최신/오래된 순으로 정렬한다 (ORDER BY)

- 댓글 개수를 집계해서 함께 출력한다 (GROUP BY / COUNT)

- PDO는 prepare → bindValue → execute → fetchAll 패턴만 사용한다

---


# 1. 댓글 목록 조회 기능 개요

댓글 목록은 게시글 상세 페이지에서 함께 출력된다.
```sql
게시글 상세 페이지 (?id=게시글ID)
→ 해당 게시글 댓글 목록 조회
→ 작성자 nickname JOIN
→ 정렬(ORDER BY)
→ 화면 출력
```

## 1-1. 필요한 데이터

댓글 목록 출력에 필요한 데이터는 다음과 같다.

- 댓글 내용(comments.comment)

- 댓글 작성자 닉네임(users.nickname)

- 댓글 작성일(comments.created_at)


---

# 2. 댓글 목록 조회 SQL (SELECT + WHERE + JOIN + ORDER BY)

## 2-1. SQL
```sql
SELECT
  c.id,
  c.user_id,
  c.comment,
  c.created_at,
  u.nickname
FROM comments c
JOIN users u ON u.id = c.user_id
WHERE c.post_id = :post_id
ORDER BY c.id DESC;
```


# 3. 댓글 개수 집계 SQL (GROUP BY / COUNT)
댓글 개수만 따로 표시하고 싶다면 다음 쿼리를 사용한다.

## 3-1. SQL
```
SELECT
  COUNT(*) AS comment_count
FROM comments
WHERE post_id = :post_id;
```
> 댓글 개수 집계는 GROUP BY 없이도 가능하다. (post_id 하나만 대상으로 집계하기 때문)

---

# 4. post_view.php 에 댓글 목록 출력 추가

> 게시글 상세 조회가 끝난 뒤 $post 를 가져온 다음, 댓글 목록을 조회하는 코드를 추가한다.

## 4-1. 댓글 목록 조회 코드 추가
```php
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
$commentStmt->bindValue(":post_id", (int)$id, PDO::PARAM_INT);
$commentStmt->execute();

$comments = $commentStmt->fetchAll();
```

## 4-2. 댓글 개수 조회 코드 추가
```php
// --------------------------------------------------
// 댓글 개수 조회 (COUNT)
// --------------------------------------------------
$countSql = "
  SELECT COUNT(*) AS comment_count
  FROM comments
  WHERE post_id = :post_id
";

$countStmt = $pdo->prepare($countSql);
$countStmt->bindValue(":post_id", (int)$id, PDO::PARAM_INT);
$countStmt->execute();

$countRow = $countStmt->fetch();
$commentCount = (int) ($countRow["comment_count"] ?? 0);
```


## 4-3. HTML 출력 추가 (게시글 내용 아래)
```php
<hr>

<h2>댓글 목록 (<?= htmlspecialchars((string)$commentCount) ?>)</h2>

<?php if (count($comments) === 0): ?>
  <p>아직 댓글이 없습니다.</p>
<?php else: ?>
  <ul>
    <?php foreach ($comments as $c): ?>
      <li>
        <b><?= htmlspecialchars($c["nickname"]) ?></b>
        (<?= htmlspecialchars($c["created_at"]) ?>)
        <br>
        <?= nl2br(htmlspecialchars($c["comment"])) ?>
      </li>
      <hr>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>
```
> nl2br(htmlspecialchars()) 를 사용하면 댓글에 줄바꿈이 포함되어도 화면에 그대로 표시된다.

