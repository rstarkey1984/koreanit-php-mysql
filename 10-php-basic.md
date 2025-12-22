# PHP 기본 문법 및 DB 연결

## 🎯 학습 목표

- PHP 파일은 “그냥 실행”이 아니라 웹서버(NGINX) → PHP-FPM을 통해 실행됨을 다시 확인한다

- PHP 문법(변수/배열/조건/반복/함수)을 DB 결과를 HTML로 출력하는 관점에서 익힌다

- PHP가 SQL을 실행하고, 그 결과를 받아 **화면(HTML)** 으로 만드는 흐름을 구현한다

- (중요) SQL 실행 시점을 “눈으로” 확인한다: 페이지 새로고침 = PHP 실행 = SQL 실행

--- 

# 1. PHP 기초 문법 (DB 출력에 필요한 것만)
> 이 강의는 “PHP 문법 자체”가 목적이 아니라, DB 결과를 화면에 뿌리는 데 필요한 문법만 쓴다.


## 1-1. echo / 변수
```php
<?php
// 문자열 값을 변수에 저장
$title = "게시판";

// 문자열 연결 연산자 ( . )
// PHP에서는 문자열을 더하기(+)가 아니라 점(.)으로 연결한다
echo "<h1>" . $title . "</h1>";
?>
```

## 1-2. 조건문(로그인 여부 같은 분기)
```php
<?php
// 로그인 여부를 나타내는 변수 (예제용)
$isLogin = false;

// if 조건문
// 조건이 true 이면 if 블록 실행
// false 이면 else 블록 실행
if ($isLogin) {
  echo "로그인 상태";
} else {
  echo "비로그인 상태";
}
?>
```

## 1-3. 배열(조회 결과를 담는 형태)
```php
<?php
// 연관 배열 (key => value)
// DB에서 한 행(row)을 가져왔을 때의 형태와 거의 동일
$user = [
  "id" => 1,
  "username" => "alice"
];

// 배열에서 특정 key 값 접근
echo $user["username"];
?>
```

## 1-4. 반복문(DB 결과 목록 출력)
```php
<?php
// DB 조회 결과 목록이라고 가정한 배열
$rows = [
  ["id" => 1, "title" => "첫 글"],
  ["id" => 2, "title" => "둘째 글"],
];

// HTML 출력 시작
echo "<ul>";

// foreach : 배열을 하나씩 순회하는 반복문
// $row 에는 배열의 각 요소(게시글 1개)가 들어옴
foreach ($rows as $row) {
  // 배열의 값들을 HTML로 출력
  echo "<li>" . $row['id'] . " - " . $row['title'] . "</li>";
}

// HTML 출력 종료
echo "</ul>";
?>
```


---

# 2. PHP에서 MySQL 연결 (PDO)
> PHP에서 MySQL 연결은 보통 PDO를 쓴다. (Prepared Statement(=SQL 인젝션 방지)까지 자연스럽게 연결됨)


## 2-1. 접속 정보
### /config/database.php 파일 작성
```php
<?php
// DB 접속 설정을 배열 형태로 반환
// 이 파일은 "실행"이 목적이 아니라
// 다른 파일에서 값을 가져다 쓰는 용도
return [
  // Docker Compose / .env 에서 전달된 환경 변수 값 읽기
  'host'    => getenv('DB_HOST'),
  'port'    => getenv('DB_PORT'),
  'dbname'  => getenv('DB_NAME'),
  'user'    => getenv('DB_USER'),
  'pass'    => getenv('DB_PASS'),
  'charset' => getenv('DB_CHARSET'),
];
?>
```

## 2-2. DB 접속 함수 만들기

### /lib/db.php 파일 작성
```php
<?php
// DB 접속을 담당하는 함수
// 호출할 때마다 PDO 객체를 반환
function db(): PDO
{
  // 설정 파일을 불러와서 반환값(배열)을 $config에 저장
  // “값을 받는 설정 파일”이므로 require 사용
  $config = require dirname(__DIR__). '/config/database.php';

  // PDO에서 사용하는 DSN 문자열 생성
  // host, port, dbname, charset 정보를 문자열로 조합
  $dsn = sprintf(
    'mysql:host=%s;port=%s;dbname=%s;charset=%s',
    $config['host'],
    $config['port'],
    $config['dbname'],
    $config['charset']
  );

  // PDO 객체 생성 및 반환
  return new PDO(
    $dsn,
    $config['user'],   // DB 사용자명
    $config['pass'],   // DB 비밀번호
    [
      // SQL 오류 발생 시 Exception으로 처리
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,

      // fetch() 결과를 연관 배열 형태로 반환
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,

      // 값 바인딩을 PHP가 SQL을 흉내내지 않고 DB가 직접 처리하도록 설정
      PDO::ATTR_EMULATE_PREPARES => false,
    ]
  );
}
?>
```

---

# 3. “SQL 실행 → 결과 출력” 첫 실습

## 3-1. SQL 실행 + 결과 구조 확인 (디버깅 관점)
> DB가 PHP에게 어떤 형태의 데이터를 주는지 확인

### select.php 파일 작성
```php
<?php
// DB 접속 함수 포함
require_once dirname(__DIR__) . "/lib/db.php";

// DB 접속
$pdo = db();

/*
 |----------------------------------------
 | 여기 SQL만 바꿔가면서 테스트
 |----------------------------------------
 */

// 예제 1: 단일 행
// $sql = "SELECT id, username FROM users WHERE id = 1";

// 예제 2: 여러 행
// $sql = "SELECT id, title FROM posts ORDER BY id DESC LIMIT 5";

// 예제 3: 집계
// $sql = "SELECT COUNT(*) AS cnt FROM posts";

// 기본값 (처음 실행용)
$sql = "SELECT 1 AS result";

// SQL 실행
$stmt = $pdo->query($sql);

// 결과 전체 가져오기
$rows = $stmt->fetchAll();

echo '<pre>';
var_dump($rows);
echo '</pre>';
?>
```

## 3-2. 쿼리 실행 결과 

```sql
SELECT id, title FROM posts ORDER BY id DESC LIMIT 5
```

### 워크벤치 화면
![workbench_result1](https://lh3.googleusercontent.com/d/1wJIn3KatOCkTJxuj1_eardVVsQTSJ3OX?1)


### PHP로 실행 결과 가져오기 PDOStatement::fetch()
```
array(2) {
  ["id"]=>
  int(1000000)
  ["title"]=>
  string(24) "게시글 제목 1000000"
}
```

> 한 row(행) 씩 가져옴. 연관배열 형태.    

| 항목     | 설명                        | 예시                      |
| ------ | ------------------------- | ----------------------- |
| 정체     | **이름(문자 key)** 로 값을 찾는 배열 | `'id'`, `'title'`       |
| 키(key) | 사람이 읽을 수 있는 문자열           | `'title'`, `'username'` |
| 언제 쓰나  | **정보 한 덩어리(레코드)** 를 표현할 때 | 게시글 1개, 사용자 1명          |
| 장점     | 코드만 봐도 의미가 바로 보임          | `$post['title']`        |
| 주의     | key 이름을 정확히 알아야 함         | `'titel'` 오타나면 문제       |

### PHP로 실행 결과 가져오기 PDOStatement::fetchAll()
```
array(5) {
  [0]=>
  array(2) {
    ["id"]=>
    int(1000000)
    ["title"]=>
    string(24) "게시글 제목 1000000"
  }
  [1]=>
  array(2) {
    ["id"]=>
    int(999999)
    ["title"]=>
    string(23) "게시글 제목 999999"
  }
  [2]=>
  array(2) {
    ["id"]=>
    int(999998)
    ["title"]=>
    string(23) "게시글 제목 999998"
  }
  [3]=>
  array(2) {
    ["id"]=>
    int(999997)
    ["title"]=>
    string(23) "게시글 제목 999997"
  }
  [4]=>
  array(2) {
    ["id"]=>
    int(999996)
    ["title"]=>
    string(23) "게시글 제목 999996"
  }
}
```
> fetchAll()은 내부적으로 fetch()를 반복해서 각 행을 숫자 배열에 넣음

| 항목     | 설명                     | 예시                |
| ------ | ---------------------- | ----------------- |
| 정체     | **순서(인덱스)** 로 값을 찾는 배열 | `0, 1, 2...`      |
| 키(key) | 자동으로 숫자가 붙음            | `0`, `1`, `2`     |
| 언제 쓰나  | **목록/모음**을 표현할 때       | 게시글 목록, 댓글 목록     |
| 장점     | 반복문(`foreach`)과 궁합이 좋음 | 리스트 출력에 최적        |
| 주의     | 값의 “의미”는 인덱스로는 잘 안 보임  | `$arr[0]`이 뭔지 헷갈림 |

> 즉, fetchAll() 에서 바깥은 목록(숫자 인덱스) 이고, 안쪽은 한 행(연관배열) 이다.

$rows = PDOStatement::fetchAll() 했을때,

- $rows : 목록(숫자 배열)

- $rows[0] : 1행(연관배열)

- $rows[0]['title'] : 값

## 3-4. SQL 결과 배열을 HTML로 표현하기

### select_html.php 파일 작성
```php
<?php
// ================================
// DB 접속 함수 포함
// ================================
// 이 파일은 DB 연결만 담당하는 db() 함수를 제공
require_once dirname(__DIR__) . "/lib/db.php";

// ================================
// DB 접속
// ================================
// db() 호출 시점에 실제 DB 연결 발생
$pdo = db();

/*
 |----------------------------------------
 | 여기 SQL만 바꿔가면서 테스트
 |----------------------------------------
 | 페이지 새로고침(F5)
 | → 이 PHP 파일이 다시 실행됨
 | → SQL도 다시 실행됨
 */

// 예제 1: 단일 행 조회
// $sql = "SELECT id, username FROM users WHERE id = 1";

// 예제 2: 여러 행 조회
// $sql = "SELECT id, title FROM posts ORDER BY id DESC LIMIT 5";

// 예제 3: 집계 결과
// $sql = "SELECT COUNT(*) AS cnt FROM posts";

// 기본값 (처음 실행 확인용)
$sql = "SELECT 1 AS result";

// ================================
// SQL 실행
// ================================
// query(): SQL을 즉시 실행하는 메서드
$stmt = $pdo->query($sql);

// ================================
// 결과 전체 가져오기
// ================================
// fetchAll(): 결과를
// [ [컬럼=>값], [컬럼=>값], ... ] 형태로 반환
$rows = $stmt->fetchAll();
?>
<!doctype html>
<html lang="ko">
<head>
  <!-- 문서 인코딩 설정 (한글 깨짐 방지) -->
  <meta charset="utf-8" />

  <!-- 브라우저 탭에 표시될 제목 -->
  <title>SQL 실행 테스트</title>

  <!--
    화면을 보기 좋게 하기 위한 최소한의 스타일
    (CSS 설명이 목적이 아니라 결과 확인이 목적)
  -->
  <style>
    body {
      font-family: system-ui, sans-serif;
      padding: 20px;
    }

    /* SQL 문자열, 디버깅 출력용 */
    pre {
      background: #f5f7fa;
      padding: 12px;
      border-radius: 6px;
    }

    /* 결과 테이블 기본 스타일 */
    table {
      border-collapse: collapse;
      margin-top: 10px;
    }

    th, td {
      border: 1px solid #ccc;
      padding: 6px 10px;
    }

    th {
      background: #eee;
    }

    /* 영역 구분용 */
    .box {
      margin-top: 20px;
    }
  </style>
</head>
<body>

<!-- 페이지 제목 -->
<h1>SQL 실행 테스트</h1>

<!-- ============================= -->
<!-- 실행된 SQL 출력 영역 -->
<!-- ============================= -->
<div class="box">
  <h3>실행된 SQL</h3>

  <!--
    htmlspecialchars():
    - <, >, ", ' 같은 HTML 특수문자를
      문자 그대로 출력하도록 변환
    - 브라우저가 태그나 스크립트로
      해석하지 못하게 막음
    - XSS (스크립트 삽입 공격) 방지
    - 화면 출력 직전에만 사용
  -->
  <pre><?= htmlspecialchars($sql) ?></pre>
</div>

<!-- ============================= -->
<!-- SQL 실행 결과 출력 영역 -->
<!-- ============================= -->
<div class="box">
  <h3>SQL 실행 결과</h3>

  <?php if (count($rows) === 0): ?>
    <!-- 조회 결과가 하나도 없을 경우 -->
    <p>결과 없음</p>

  <?php else: ?>
    <!-- 조회 결과가 있을 경우 테이블로 출력 -->
    <table>
      <thead>
        <tr>
          <?php
          /*
            첫 번째 행($rows[0])의 key 목록을 이용해
            테이블 헤더(컬럼명) 자동 생성
            예: id, title, username ...
          */
          foreach (array_keys($rows[0]) as $col):
          ?>
            <!-- 컬럼명도 HTML로 해석되지 않도록 처리 -->
            <th><?= htmlspecialchars($col) ?></th>
          <?php endforeach; ?>
        </tr>
      </thead>

      <tbody>
        <?php
        /*
          $rows는 "목록"
          foreach 한 번 = 한 행(row)
        */
        foreach ($rows as $row):
        ?>
          <tr>
            <?php
            /*
              $row는 "한 행"
              각 컬럼의 값을 하나씩 출력
            */
            foreach ($row as $value):
            ?>
              <!--
                DB 값은 신뢰할 수 없으므로
                반드시 htmlspecialchars()로 출력
              -->
              <td><?= htmlspecialchars($value) ?></td>
            <?php endforeach; ?>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>

</div>

<!-- ============================= -->
<!-- 학습용 핵심 메시지 -->
<!-- ============================= -->
<p>
  SQL을 수정하고 새로고침(F5)하면<br>
  → PHP 파일이 다시 실행되고<br>
  → 그 안의 SQL도 다시 실행된다.
</p>

</body>
</html>
```

---

# 4. Prepared Statement & bindParam (입력값이 있는 SQL)
> 지금까지는 SQL이 고정된 상태였다. 이제부터는 사용자 입력값이 들어가는 SQL을 다룬다. (로그인, 게시글 조회, 검색 등 모든 실무 SQL의 시작점)

## 4-1. 왜 bindParam / Prepared Statement 가 필요한가?

### 잘못된 방식 (문자열로 SQL 조립)
```php
$id = $_GET['id'];

$sql = "SELECT id, title FROM posts WHERE id = " . $id;
$stmt = $pdo->query($sql);
```

문제점
- SQL Injection 공격 가능
- 숫자인지 문자열인지 직접 신경 써야 함
- 실무에서 절대 권장되지 않음

### 올바른 방식 (Prepared Statement)
> SQL 구조와 값(data)을 완전히 분리
```sql
SELECT id, title FROM posts WHERE id = ?
```
또는
```sql
SELECT id, title FROM posts WHERE id = :id
```

## 4-2. prepare → bindParam → execute 흐름
```
prepare → bind → execute → fetch
```

### select_bind.php 파일 작성
```php
<?php
require_once dirname(__DIR__) . "/lib/db.php";

$pdo = db();

// 예제용 입력값 (보통은 $_GET / $_POST)
$postId = 1;

// SQL 준비 (아직 실행 안 됨)
$sql = "SELECT id, title FROM posts WHERE id = :id";
$stmt = $pdo->prepare($sql);

// 값 바인딩 ( 숫자 형태일 경우에만 PDO::PARAM_INT )
$stmt->bindParam(':id', $postId, PDO::PARAM_INT);

// SQL 실행
$stmt->execute();

// 결과 가져오기
$row = $stmt->fetch();

echo '<pre>';
var_dump($row);
echo '</pre>';
?>
```

## 4-3. SQL 실행 시점 다시 강조
| 단계        | 설명                   |
| --------- | -------------------- |
| prepare   | SQL 문장을 준비(파싱/준비). 아직 실행 아님 |
| bindParam / bindValue | 값 연결. 아직 실행 아님 ( 숫자면 PDO::PARAM_INT )      |
| execute   | **이 시점에 SQL 실행됨** |
| fetch     | 실행된 결과를 가져옴          |


## 4-4. 여러 값 바인딩 

### select_bind2.php 파일 작성
```php
<?php
require_once dirname(__DIR__) . "/lib/db.php";

$pdo = db();

// SQL 준비 (아직 실행 안 됨)
$sql = "
  SELECT id, title
  FROM posts
  WHERE user_id = :user_id
  AND id > :min_id
  ORDER BY id DESC
  LIMIT :limit
";
$stmt = $pdo->prepare($sql);

// 값 바인딩
$stmt->bindValue(':user_id', 3, PDO::PARAM_INT);
$stmt->bindValue(':min_id', 100, PDO::PARAM_INT);
$stmt->bindValue(':limit', 5, PDO::PARAM_INT);

// SQL 실행
$stmt->execute();

// 결과 가져오기
$rows = $stmt->fetchAll();

echo '<pre>';
var_dump($rows);
echo '</pre>';
?>
```

## 4-5. HTML 출력까지 연결 (완성 형태)
```php
<?php
// ================================
// DB 접속 함수 포함
// ================================
// db() 함수가 정의된 파일 로드
require_once dirname(__DIR__) . "/lib/db.php";

// ================================
// DB 접속
// ================================
// db() 호출 시 실제 DB 연결이 생성됨
$pdo = db();

// ================================
// 예제용 입력값
// ================================
// 실제 서비스에서는 $_GET, $_POST 값이 들어올 자리
$userId = 1;

// ================================
// SQL 작성 (Prepared Statement)
// ================================
// :user_id 는 나중에 값이 바인딩될 자리표시자
$sql = "
  SELECT id, title
  FROM posts
  WHERE user_id = :user_id
  ORDER BY id DESC
  LIMIT 5
";

// ================================
// SQL 준비 (아직 실행되지 않음)
// ================================
// prepare(): SQL 구조만 DB에 전달
$stmt = $pdo->prepare($sql);

// ================================
// 값 바인딩
// ================================
// user_id 는 숫자이므로 PARAM_INT 지정
$stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);

// ================================
// SQL 실행
// ================================
// execute() 호출 시점에 실제로 SQL이 실행됨
$stmt->execute();

// ================================
// 결과 전체 가져오기
// ================================
// fetchAll(): 여러 행을 배열로 반환
$rows = $stmt->fetchAll();
?>
<!doctype html>
<html lang="ko">
<head>
  <!-- 문서 인코딩 설정 (한글 깨짐 방지) -->
  <meta charset="utf-8">

  <!-- 브라우저 탭에 표시될 제목 -->
  <title>게시글 목록</title>
</head>
<body>

<!-- 페이지 제목 -->
<h1>게시글 목록</h1>

<!-- ============================= -->
<!-- 게시글 목록 출력 -->
<!-- ============================= -->
<ul>
<?php
// $rows는 게시글 목록
// foreach 한 번 = 게시글 한 개
foreach ($rows as $row):
?>
  <li>
    <!--
      id, title 은 DB에서 온 값
      화면 출력 시 반드시 htmlspecialchars() 사용
      (HTML 태그 / 스크립트 해석 방지, XSS 예방)
    -->
    <?= htmlspecialchars($row['id']) ?> -
    <?= htmlspecialchars($row['title']) ?>
  </li>
<?php endforeach; ?>
</ul>

</body>
</html>
```

---

# 🧩 실습 / 과제

### select_test.php (과제용 템플릿)
```php
<?php
require_once dirname(__DIR__) . "/lib/db.php";

$pdo = db();

/*
 |----------------------------------------
 | 실습 목표
 |----------------------------------------
 | 1) SQL을 prepare 한다
 | 2) 자리표시자(:user_id, :limit)를 사용한다
 | 3) bindValue로 값을 바인딩한다
 | 4) execute()로 실행한다
 | 5) fetchAll() 결과를 HTML로 출력한다
 */

// =============================
// [1] SQL 작성
// =============================
// TODO: 조건에 맞게 SQL을 완성하시오
$sql = "
  SELECT id, user_id, title, created_at
  FROM posts
  WHERE user_id = :user_id
  ORDER BY id DESC
  LIMIT :limit
";

// =============================
// [2] SQL 준비
// =============================
$stmt = $pdo->prepare($sql);

// =============================
// [3] 값 바인딩
// =============================
// TODO: 아래 값들을 SQL에 바인딩하시오
$userId = 1;
$limit  = 5;

$stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);

// =============================
// [4] SQL 실행
// =============================
$stmt->execute();

// =============================
// [5] 결과 가져오기
// =============================
$rows = $stmt->fetchAll();
?>
<!doctype html>
<html lang="ko">
<head>
  <meta charset="utf-8" />
  <title>SQL 바인딩 실습</title>
  <style>
    body { font-family: system-ui, sans-serif; padding: 20px; }
    pre  { background: #f5f7fa; padding: 12px; border-radius: 6px; }
    table { border-collapse: collapse; margin-top: 10px; }
    th, td { border: 1px solid #ccc; padding: 6px 10px; }
    th { background: #eee; }
    .box { margin-top: 20px; }
  </style>
</head>
<body>

<h1>SQL 바인딩 실습</h1>

<div class="box">
  <h3>실행된 SQL</h3>
  <pre><?= $sql ?></pre>
</div>

<div class="box">
  <h3>SQL 실행 결과</h3>

  <?php if (count($rows) === 0): ?>
    <p>결과 없음</p>
  <?php else: ?>
    <table>
      <thead>
        <tr>
          <?php foreach (array_keys($rows[0]) as $col): ?>
            <th><?= $col ?></th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $row): ?>
          <tr>
            <?php foreach ($row as $value): ?>
              <td><?= $value ?></td>
            <?php endforeach; ?>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>

</div>

<p>
  SQL을 수정하고 새로고침(F5)하면<br>
  → PHP가 다시 실행되고<br>
  → prepare / bind / execute가 다시 수행된다.
</p>

</body>
</html>
```


### 1. posts 테이블에서 user_id = 10 인 게시글을 최신순으로 10 개 조회해서 화면에 출력