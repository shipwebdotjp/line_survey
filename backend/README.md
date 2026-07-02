# Backend

`backend/` は PHP API 本体です。DB 初期化、マイグレーション、シーディングは `make` コマンド経由で実行します。

## 初期セットアップ

初回は次の順で実行します。

```bash
make init
make migrate
make seed
```

### `make init`

- Docker コンテナを起動します
- `backend/vendor/` に Composer 依存関係をインストールします
- `backend/.env` が存在しない場合は `backend/.env.example` をコピーします

### `make migrate`

- Phinx のマイグレーションを実行します
- `backend/db/migrations/` にあるスキーマ定義を DB に反映します

### `make seed`

- Phinx のシーダーを実行します
- 開発用のサンプルデータを DB に投入します

## DB 設定

Phinx は `backend/.env` の値を参照します。主な設定は以下です。

```env
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_NAME=survey
DB_USER=mysqluser
DB_PASS=password
```

Docker Compose 環境では、`DB_HOST=db` のままで動作します。  
環境を変える場合は `backend/.env` を更新してください。

## メール設定

回答控えメールの送信先は `MAIL_*` で設定します。  
アンケートの owner にメールアドレスが設定されている場合は、回答者に送る内容と同じ控えを owner にも別送します。  
owner のメールアドレスは `settings` テーブルまたは `users.email` で管理します。

```env
MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_FROM_ADDRESS=onboarding@resend.dev
MAIL_FROM_NAME="Survey App"
APP_ORIGIN_URL=http://localhost:5173
APP_PUBLIC_URL=http://localhost:8080
```

## 既存データを消してやり直す場合

開発環境で DB を作り直したい場合は、DB コンテナやデータボリュームを削除してから、改めて `make init` -> `make migrate` -> `make seed` を実行します。

## 本番環境でのマイグレーション

レンタルサーバー等、CLI の PHP バージョンが古い（PHP 7.4 等）が Web 側の PHP バージョンが新しい（PHP 8.3 等）環境では、Web 経由でマイグレーションを実行するための専用ランナーを使用します。

### 実行手順

1. `backend/.env` に `MIGRATION_TOKEN` を設定します（推測困難なランダムな文字列を推奨）。
2. `public_html/api/_ops/migrate.php` に対して、`X-Migration-Token` ヘッダーに設定したトークンを含めて HTTP リクエストを送信します。
   - `curl -H "X-Migration-Token: your-token-here" https://your-domain.example.com/api/_ops/migrate.php`
3. 実行結果を確認します。成功すると `Success: Migration completed.` と詳細ログが返ります。
4. 安全のため、実行開始時に `backend/storage/logs/migration.lock` ファイルが作成され、以降の実行はブロックされます。再実行が必要な場合は、このロックファイルを手動で削除してください。

### 運用上の推奨事項

`public_html/api/_ops/migrate.php` はトークン認証のみの公開エンドポイントとなります。より安全に運用するために、以下の対策を推奨します。

- **IP 制限の導入**: 可能であれば、`.htaccess` 等で特定の保守元 IP アドレスからのみアクセスを許可してください。
- **エンドポイントの無効化**: マイグレーション完了後は、`migrate.php` を削除するか、実行権限を剥奪して無効化してください。
- **レート制限**: 認証失敗に対するレート制限を Web サーバー側で設定することを検討してください。

### ログの確認

マイグレーションの実行詳細は `backend/storage/logs/migrations.log` に記録されます。失敗した場合はこのログを確認してください。

## 参考

- `backend/phinx.php`
- `backend/db/migrations/`
- `backend/db/seeds/`
- `Makefile`
