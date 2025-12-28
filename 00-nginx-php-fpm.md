# 개발환경 준비 ( NGINX + PHP-FPM | Docker )

## 🎯 학습 목표

- PHP 파일이 그냥 실행되지 않고, 웹 서버를 통해서 실행된다는 것을 이해한다

- PHP는 DB에 SQL을 던지고 결과를 받아오는 역할임을 인식한다

- 개발환경은 Docker로 통일하지만, 이후 모든 실습은 “SQL 실행 → 결과 확인” 구조로 진행됨을 이해한다

---
# 0. docker-ce 공식 설치
> Docker CE (Community Edition) 는 개발자와 개인, 교육 환경에서 무료로 사용할 수 있는 Docker의 오픈소스 배포판입니다.

> Docker는 애플리케이션과 실행 환경을 컨테이너로 묶어, 어디서나 동일하게 실행하게 해주는 도구다.  
> 컨테이너는 하나의 메인 프로세스를 중심으로 동작한다.

## 0-1. 필수 패키지 설치:

```bash
sudo apt update
sudo apt install -y ca-certificates curl gnupg
```

## 0-2. Docker 공식 GPG 키 등록:
```bash
sudo install -m 0755 -d /etc/apt/keyrings
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | \
sudo gpg --dearmor -o /etc/apt/keyrings/docker.gpg
sudo chmod a+r /etc/apt/keyrings/docker.gpg
```

## 0-3. Docker 공식 저장소 추가: (Ubuntu 24.04 / noble)
```bash
echo \
"deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] \
https://download.docker.com/linux/ubuntu noble stable" | \
sudo tee /etc/apt/sources.list.d/docker.list > /dev/null
```

## 0-4. docker-ce 설치:
```bash
sudo apt update
sudo apt install -y docker-ce docker-ce-cli containerd.io docker-compose-plugin docker-buildx-plugin
```

## 0-5. Docker 데몬 확인:
```bash
sudo systemctl status docker
```

## 0-6. sudo 없이 docker 쓰기: (필수)
```bash
sudo usermod -aG docker $USER
```

## 0-7. 설치 확인:
```bash
docker --version
docker compose version
```

## 0-8. 동작테스트:
```bash
docker run hello-world
```

--- 

# 1. 웹 요청 흐름 

![웹요청흐름](https://lh3.googleusercontent.com/d/1nkv3nn882d8cMzv6dB2MOIjSdxUYUVG2)

## 1-1. 브라우저

- URL을 입력하면 HTTP 요청을 서버로 전송한다

- 서버로부터 받은 **HTTP 응답으로 HTML / CSS / JS를 화면에 표시** 한다  

## 1-2. NGINX 

- 웹 서버

- 브라우저의 HTTP 요청을 받는다

- 정적 파일(HTML, CSS, 이미지 등)은 NGINX가 직접 처리한다

- PHP 파일은 직접 실행하지 않고 PHP-FPM에 전달한다

## 1-3. PHP-FPM

- PHP 실행 엔진

- NGINX로부터 요청을 받아 PHP 코드를 실행한다

- PHP 코드는 DB에 SQL을 보내고 결과를 받는다

## 1-4. MySQL

- 관계형 데이터베이스(DBMS)

- 데이터를 테이블 형태로 저장·관리한다

- PHP로부터 전달된 SQL을 실행한다

- 실행 결과(조회 결과 또는 처리 결과)를 PHP 코드에 반환한다

---

# 2. Docker Compose 를 활용한 프로젝트 만들기
> Docker Compose는 여러 개의 Docker 컨테이너(웹 서버, PHP, DB 등)를 하나의 설정 파일로 정의하고, 한 번에 실행·중지·관리하게 해주는 도구다.

## 2-1. 프로젝트 폴더구조
```bash
~/projects/web-docker/
├─ docker-compose.yml          # Docker 컨테이너 구성 정의 (nginx, php-fpm 실행 설정)
├─ Dockerfile                  # php-fpm 커스텀 이미지 빌드용 파일 (확장 설치 등)
├─ .env                        # DB 접속 정보 등 환경 변수 모음 
├─ nginx/
│    └─ test.localhost.conf    # nginx 가상호스트 설정 (요청 → php-fpm 전달)
└─ var/
    └─ www/
        └─ test.localhost/     # 하나의 웹사이트(Document Root 기준 디렉터리)
            ├─ public/         # 웹에서 직접 접근 가능한 공개 영역 (DocumentRoot)
            │   ├─ index.php   # 메인 진입 파일 (요청 처리 시작점)
            │   └─ phpinfo.php # PHP 동작/환경 확인용 테스트 파일
            ├─ config/         # 설정 파일 모음 (DB 설정 등, 로직 없음)
            ├─ lib/            # 공통 함수/헬퍼 (DB 연결 함수 등)
            └─ classes/        # 비즈니스 로직 클래스 (User, Post 등 도메인 객체)

```

## 2-2. 폴더 및 파일 자동 생성 명령어:
```bash
BASE=~/projects/web-docker

mkdir -p \
  "$BASE/nginx" \
  "$BASE/var/www/test.localhost/public" \
  "$BASE/var/www/test.localhost/config" \
  "$BASE/var/www/test.localhost/lib" \
  "$BASE/var/www/test.localhost/classes" && \
touch \
  "$BASE/docker-compose.yml" \
  "$BASE/Dockerfile" \
  "$BASE/.env" \
  "$BASE/nginx/test.localhost.conf" \
  "$BASE/var/www/test.localhost/public/index.php" \
  "$BASE/var/www/test.localhost/public/phpinfo.php"
```

## 2-3. VSCode 로 작업폴더 열기:
```
code ~/projects/web-docker
```

## 2-4. nginx/test.localhost.conf
```
server {
    listen 80;
    server_name test.localhost;

    root /var/www/test.localhost/public;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP 요청을 PHP-FPM으로 전달
    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_pass php:9000;   # compose의 서비스명 php로 연결
    }
}
```

## 2-5. Dockerfile 로 이미지 빌드
### Dockerfile 작성
```
FROM php:8.3-fpm-alpine

# PDO MySQL 확장 설치
RUN docker-php-ext-install pdo_mysql mysqli
```

Docker 로 이미지 빌드:
```
docker build -t custom-php-fpm:8.3-alpine .
```

## 2-6. 컨테이너로 전달할 환경변수 설정
### .env 파일수정
```
DB_HOST=host.docker.internal
DB_PORT=3308
DB_NAME=testdb
DB_USER=test
DB_PASS=test123
DB_CHARSET=utf8mb4
```

## 2-7. Docker Compose


### docker-compose.yml 파일수정
> 여러 Docker 컨테이너의 구성(이미지, 포트, 볼륨, 연결 관계)을 한 번에 정의해두고, 동일한 개발환경을 그대로 재현하기 위한 설정 파일이다.
```yml
name: web-docker

services:
  nginx:
    image: nginx:1.27-alpine
    container_name: web-nginx
    restart: unless-stopped
    ports:
      - "80:80"
    volumes:
      - ./var/www/test.localhost:/var/www/test.localhost:ro
      - ./nginx/test.localhost.conf:/etc/nginx/conf.d/test.localhost.conf:ro
    depends_on:
      - php

  php:
    image: custom-php-fpm:8.3-alpine
    container_name: web-php
    restart: unless-stopped
    volumes:
      - ./var/www/test.localhost:/var/www/test.localhost:ro
    env_file:
      - .env
    extra_hosts:
      - "host.docker.internal:host-gateway"
```

## 2-8. PHP 파일 작성

### var/www/test.localhost/public/index.php
```php
<?php
echo "Hello from PHP-FPM via Nginx!";
?>
```
### var/www/test.localhost/public/phpinfo.php
```php
<?php
phpinfo();
?>
```


## 2-9. Docker Compose 설정을 기반으로 컨테이너 관리

Docker Compose 실행:
```
docker compose up -d
```

Docker Compose 중지:
```
docker compose down
```

NGINX 로그확인:
```
docker logs -f web-nginx
```

PHP-FPM 로그확인:
```
docker logs -f web-php
```

--- 

# 3. Docker 컨테이너에서 호스트 MySQL로 원격 접속

## 3-1. 원격 접속 가능한 계정 추가
### mysql 접속:
```bash
sudo mysql
```

### 계정 생성 및 권한주기
```sql
USE mysql; CREATE USER 'test'@'%' IDENTIFIED BY 'test123'; GRANT ALL PRIVILEGES ON testdb.* TO 'test'@'%'; FLUSH PRIVILEGES;
```

## 3-2. mysqld.cnf 수정
> mysqld.cnf 파일에서 MySQL의 bind-address를 로컬 전용(127.0.0.1)에서 모든 네트워크 허용(0.0.0.0)으로 변경한다.


### ~~bind-address = 127.0.0.1~~ >> bind-address = 0.0.0.0

편집기 없이 명령어로 수정:
```bash
sudo sed -i 's/^bind-address\s*=.*/bind-address = 0.0.0.0/' /etc/mysql/mysql.conf.d/mysqld.cnf
```

## 3-3. MySQL 서버 재시작:
```bash
sudo systemctl restart mysql
```

---

# 🧩 실습 / 과제

## 1. 웹 요청 흐름 눈으로 확인

- ### 브라우저에 `http://test.localhost` 로 접속해서 Nginx 와 PHP-FPM 로그 각각 확인해보기


## 2. 정적 파일 vs PHP 처리 비교

- ### var/www/test.localhost/public/test.html 작성 
```html
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>Static Test</title>
    <style>
        body{margin:0;height:100vh;display:flex;justify-content:center;align-items:center;background:#f5f7fa;font-family:system-ui,-apple-system,sans-serif}.card{background:#fff;padding:32px 40px;border-radius:12px;box-shadow:0 10px 30px rgba(0,0,0,.08);text-align:center}h1{margin:0 0 10px;font-size:24px;color:#2c3e50}p{margin:0;font-size:14px;color:#7f8c8d}
    </style>
</head>
<body>
    <div class="card">
        <h1>Static HTML</h1>
        <p>This file is served directly by NGINX</p>
    </div>
</body>
</html>
```
- ### test.html 요청 시 PHP-FPM 로그가 찍히는가?