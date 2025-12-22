# 📘 한국IT교육원 DB를 활용한 웹서비스 개발

> 주제: 실습으로 이해하는 웹서비스 개발   
> 부제: Nginx + PHP-FPM + MySQL 기반 게시판 만들기    
> 대상: 한국IT교육원 수강생   
> 작성: 류근철    

---

## 🎯 강의 목표 

- 웹서비스 요청 흐름  
  브라우저 → 웹서버 → PHP → DB → HTML 응답  
  : SQL이 언제, 왜 실행되는지를 눈으로 확인한다

- ERD로 설계한 users / posts / comments 테이블이 어떤 제약조건으로 SQL 구문이 사용되는지 체험한다


## [0. 개발환경 준비 ( NGINX + PHP-FPM | Docker )](00-nginx-php-fpm.md)

## [1. PHP 기본 문법 및 DB 연결](10-php-basic.md)

## [2. 메인페이지 작성 ( SELECT + JOIN )](20-main-page.md)

## [3. 회원가입 페이지 ( INSERT )](30-user-regist.md)

## [4. 로그인 페이지 ( SELECT + WHERE )](40-user-login.md)

## [5. 게시글 작성 ( INSERT + FK )](50-post-write.md)

## [6. 게시글 상세 페이지 ( SELECT + WHERE + JOIN )](60-post-detail.md)

## [7. 댓글 작성 ( 2중 FK )](70-comment-write.md)

## [8. 댓글 목록 조회 ( SELECT + WHERE + JOIN | GROUP BY / ORDER BY )](80-comment-list.md)



