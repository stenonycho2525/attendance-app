# 勤怠管理アプリ

## 作成者

小谷　知代

## 開発環境URL

http://localhost/

## 環境構築手順

1. **リポジトリをクローン**

   ```bash
   git clone https://github.com/stenonycho2525/attendance-app.git
   ```

2. **.envファイルの準備**

   .env.example をコピーして .env を作成
   ```bash
   cd attendance-app
   cp .env.example .env
   ```

3. **Composer依存パッケージのインストール**

   dockerにComposer依存パッケージをインストール
   ```bash
   docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    -e COMPOSER_CACHE_DIR=/tmp/composer_cache \
    laravelsail/php82-composer:latest \
    composer install
   ```

4. **Laravel Sailの起動**

   Laravel Sailを起動する
   ```bash
   ./vendor/bin/sail up -d
   ```

5. **アプリケーションキーの生成**

   アプリケーションキーを生成
   ```bash
   ./vendor/bin/sail artisan key:generate
   ```

6. **データベースのマイグレーションを作成**

   マイグレーションを作成
   ```bash
   ./vendor/bin/sail artisan migrate
   ```

7. **フロントエンドのビルド**

   JavaScriptのパッケージをインストールする
   ```bash
   ./vendor/bin/sail npm install
   ```

8. **Vite開発サーバーの起動**

   別のターミナルで下記のコマンドを実行する
   ※このコマンドは常に実行した状態にしておく
   ```bash
   sail npm run dev
   ```

9. **アプリケーションへのアクセス**

   ブラウザで以下のURLを開く
   http://localhost

## 使用技術

- PHP 8.3.6
- Laravel 10.50.2
- MySQL 8.0
- Docker / Laravel Sail
- Tailwind CSS

## ER図
```mermaid
erDiagram
    USERS {
        bigint id PK
        string name
        string email UK
        timestamp email_verified_at
        string password
        boolean admin_status
        text two_factor_secret
        text two_factor_recovery_codes
        timestamp two_factor_confirmed_at
        string remember_token
        timestamp created_at
        timestamp updated_at
    }

    ATTENDANCE_RECORDS {
        bigint id PK
        bigint user_id FK "UK(user_id, date)"
        date date "UK(user_id, date)"
        time clock_in
        time clock_out
        string comment
        timestamp created_at
        timestamp updated_at
    }

    BREAKS {
        bigint id PK
        bigint attendance_record_id FK
        time break_in
        time break_out
        timestamp created_at
        timestamp updated_at
    }

    STAMP_CORRECTION_REQUESTS {
        bigint id PK
        bigint attendance_record_id FK
        bigint user_id FK
        date date
        time clock_in
        time clock_out
        string comment
        string status
        timestamp created_at
        timestamp updated_at
    }

    STAMP_CORRECTION_REQUEST_BREAKS {
        bigint id PK
        bigint stamp_correction_request_id FK
        time break_in
        time break_out
        timestamp created_at
        timestamp updated_at
    }

    USERS ||--o{ ATTENDANCE_RECORDS : "has"
    USERS ||--o{ STAMP_CORRECTION_REQUESTS : "has"
    ATTENDANCE_RECORDS ||--o{ BREAKS : "has"
    ATTENDANCE_RECORDS ||--o{ STAMP_CORRECTION_REQUESTS : "has"
    STAMP_CORRECTION_REQUESTS ||--o{ STAMP_CORRECTION_REQUEST_BREAKS : "has"
```

## URL

開発環境：http://localhost/
phpMyAdmin：http://localhost:8080/