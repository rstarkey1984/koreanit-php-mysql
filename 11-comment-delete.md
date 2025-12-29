# 댓글 삭제 ( DELETE + WHERE )

## 🎯 학습 목표

- DELETE 문이 언제 실행되는지 명확히 이해한다

- 로그인 사용자 본인 댓글만 삭제하도록 조건을 설계한다

- WHERE 조건이 없을 때 발생하는 치명적인 문제를 체험적으로 인식한다

- 게시글 삭제와 댓글 삭제의 차이점을 비교한다


## 기능 요구사항

- 로그인한 사용자만 댓글 삭제 가능

- 자신이 작성한 댓글만 삭제 가능

- 댓글 삭제 후 해당 게시글 상세 페이지로 이동

- GET 요청으로 삭제하지 않는다


## 전체 처리 흐름
```sql
댓글 삭제 버튼 클릭
→ POST 요청 발생
→ comment_delete.php 실행
→ DELETE SQL 실행
→ 게시글 상세 페이지로 이동
```

---

# 1. 댓글 삭제 버튼 출력

## 1-1. post_detail.php (댓글 목록 출력 부분)

기존 댓글 출력 부분 교체

```php
<?php foreach ($comments as $c): ?>
  <li>
    <b><?= htmlspecialchars($c["nickname"]) ?></b>
    (<?= htmlspecialchars($c["created_at"]) ?>)
    <br>
    <?= nl2br(htmlspecialchars($c["comment"])) ?>

    <?php if (
      $loginUserId !== null &&
      $loginUserId === (int) $c["user_id"]
    ): ?>
      <form
        method="post"
        action="/comment_delete_action.php"
        onsubmit="return confirm('댓글을 삭제할까요?');"
        style="margin-top:5px;"
      >
        <input type="hidden" name="comment_id" value="<?= htmlspecialchars((string) $c["id"]) ?>">
        <input type="hidden" name="post_id" value="<?= htmlspecialchars((string) $id) ?>">
        <button type="submit">삭제</button>
      </form>
    <?php endif; ?>
  </li>
  <hr>
<?php endforeach; ?>
```

- 댓글 삭제 버튼은 본인 댓글에만 표시

- 실제 보안은 DELETE SQL에서 user_id 조건으로 처리

- UI는 편의, 보안은 서버가 담당

- 삭제 요청은 반드시 POST

---

# 2. comment_delete_action.php

```php
<?php
session_start();

require_once dirname(__DIR__) . "/lib/db.php";

// --------------------------------------------------
// 로그인 체크
// --------------------------------------------------
if (!isset($_SESSION["user"])) {
  echo "로그인 후 이용 가능합니다.";
  exit;
}

$pdo = db();

// --------------------------------------------------
// POST 파라미터 수신
// --------------------------------------------------
$commentId = $_POST["comment_id"] ?? null;
$postId    = $_POST["post_id"] ?? null;

// --------------------------------------------------
// 파라미터 검증
// --------------------------------------------------
if (
  $commentId === null || !ctype_digit((string) $commentId) ||
  $postId === null || !ctype_digit((string) $postId)
) {
  echo "잘못된 요청입니다.";
  exit;
}

$commentId = (int) $commentId;
$postId    = (int) $postId;
$userId    = (int) $_SESSION["user"]["id"];

// --------------------------------------------------
// 댓글 삭제 (DELETE + WHERE + user_id)
// --------------------------------------------------
$sql = "
  DELETE FROM comments
  WHERE id = :comment_id
    AND user_id = :user_id
";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(":comment_id", $commentId, PDO::PARAM_INT);
$stmt->bindValue(":user_id", $userId, PDO::PARAM_INT);
$stmt->execute();

// --------------------------------------------------
// 삭제 결과 확인
// --------------------------------------------------
if ($stmt->rowCount() === 0) {
  echo "삭제할 수 없습니다.";
  exit;
}

// --------------------------------------------------
// 게시글 상세 페이지로 이동
// --------------------------------------------------
header("Location: /post_view.php?id=" . $postId);
exit;

```