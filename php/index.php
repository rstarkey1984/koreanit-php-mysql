<?php
session_start();

// DB 연결 함수 포함
require_once dirname(__DIR__) . "/lib/db.php";

// DB 연결 (PDO 객체 생성)
$pdo = db();

// --------------------------------------------------
// 메인페이지에 보여줄 게시글 개수
// --------------------------------------------------
$limit = 20;

// --------------------------------------------------
// 게시글 + 작성자 + 댓글수 조회 (서브쿼리 방식)
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
  <title>게시글 목록</title>

  <style>
    body {
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI",
                   Roboto, "Noto Sans KR", Arial, sans-serif;
      background-color: #f5f6f8;
      margin: 0;
      padding: 0;
    }

    h1 {
      text-align: center;
      margin: 30px 0 20px;
    }

    .container {
      width: 900px;
      margin: 0 auto 40px;
      background-color: #fff;
      padding: 20px 24px 30px;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    }

    .top-menu {
      text-align: right;
      margin-bottom: 16px;
      font-size: 14px;
    }

    .top-menu a {
      text-decoration: none;
      color: #2563eb;
      margin-left: 10px;
    }

    .top-menu a:hover {
      text-decoration: underline;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      font-size: 14px;
    }

    thead {
      background-color: #f1f3f5;
    }

    th, td {
      padding: 10px 8px;
      border-bottom: 1px solid #e5e7eb;
      text-align: center;
    }

    th {
      font-weight: 600;
    }

    td.title {
      text-align: left;
    }

    td.title a {
      color: #111827;
      text-decoration: none;
    }

    td.title a:hover {
      text-decoration: underline;
      color: #2563eb;
    }

    tbody tr:hover {
      background-color: #f9fafb;
    }

    .comment-count {
      color: #2563eb;
      font-weight: 600;
    }

    .footer {
      text-align: center;
      font-size: 13px;
      color: #6b7280;
      margin-top: 20px;
    }
  </style>
</head>

<body>

<h1>게시글 목록</h1>

<div class="container">

  <!-- 상단 메뉴 -->
  <div class="top-menu">
    <?php if (isset($_SESSION["user"])): ?>
      <?= htmlspecialchars($_SESSION["user"]["nickname"]) ?> 님
      <a href="/logout.php">로그아웃</a>
      <a href="/post_create.php">글쓰기</a>
    <?php else: ?>
      <a href="/register.php">회원가입</a>
      <a href="/login.php">로그인</a>
      <a href="/post_create.php">글쓰기</a>
    <?php endif; ?>
  </div>

  <!-- 게시글 목록 테이블 -->
  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>제목</th>
        <th>작성자</th>
        <th>조회수</th>
        <th>댓글</th>
        <th>작성일</th>
      </tr>
    </thead>
    <tbody>

    <?php foreach ($posts as $post): ?>
      <tr>
        <td><?= htmlspecialchars($post["id"]) ?></td>

        <td class="title">
          <a href="/post_view.php?id=<?= htmlspecialchars($post["id"]) ?>">
            <?= htmlspecialchars($post["title"]) ?>
          </a>
        </td>

        <td><?= htmlspecialchars($post["nickname"]) ?></td>
        <td><?= htmlspecialchars($post["view_count"]) ?></td>
        <td class="comment-count">
          <?= htmlspecialchars($post["comment_cnt"]) ?>
        </td>
        <td><?= htmlspecialchars($post["created_at"]) ?></td>
      </tr>
    <?php endforeach; ?>

    </tbody>
  </table>

  <div class="footer">
    한국IT교육원 · DB 기반 웹서비스 실습
  </div>

</div>

</body>
</html>
