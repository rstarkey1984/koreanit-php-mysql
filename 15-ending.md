# 마무리 정리

## 🎯 이 강의에서 우리가 한 것

브라우저 요청이 들어왔을 때   
어떤 SQL이 언제 실행되고    
그 결과가 어떻게 화면으로 이어지는지를    
직접 손으로 만들어보는 것이 목표였다.

지금까지 우리는 다음을 모두 실습으로 구현했다.

---


# 지금까지 구현한 SQL 흐름

## 데이터 조회 (SELECT)

- 게시글 목록 조회 (SELECT + JOIN)

- 게시글 상세 조회 (SELECT + WHERE)

- 댓글 목록 조회 (SELECT + WHERE + JOIN)

- 댓글 개수 집계 (COUNT)

## 데이터 생성 (INSERT)

- 회원가입

- 게시글 작성 (FK)

- 댓글 작성 (2중 FK)

## 데이터 삭제 (DELETE)

- 게시글 삭제 (권한 체크 + WHERE)

- 댓글 삭제 (본인만 삭제 가능)

## 데이터 변경 (UPDATE)

- 게시글 수정 

- 게시글 조회수 증가

## 실무 필수 기능

- 페이징 (LIMIT / OFFSET + COUNT)

- 검색 (WHERE + LIKE + 페이징 연동)


---

# 🧩 실습/과제

## 아래 과제는 HTML 없이 fetchAll 로 가져온 배열을 출력만 하는걸로 대체한다.

```
$rows = $stmt->fetchAll();
echo '<pre>';
var_dump($rows);
echo '</pre>';
```

## 1. 내가 쓴 글만 모아보기 (SELECT + WHERE)

### 🎯 목표

로그인한 사용자가 본인이 작성한 게시글만 조회한다

메인페이지와 같은 테이블 UI를 재사용한다

## 요구사항

파일명: my_posts.php

- 로그인 필수

- posts.user_id = 로그인 사용자 id

- 최신순 정렬

---

## 2. 댓글 단 글 목록 보기 (JOIN + DISTINCT)

### 🎯 목표

내가 댓글을 단 게시글 목록 조회

중복 게시글 제거 ( DISTINCT )

## 요구사항

파일명: commented_posts.php

- 로그인 필수

- 댓글을 단 게시글만 조회

- 게시글은 한 번만 출력

힌트 SQL
```
SELECT DISTINCT
  p.id, p.title, p.created_at
FROM comments c
JOIN posts p ON p.id = c.post_id
WHERE c.user_id = :user_id
ORDER BY p.id DESC
```