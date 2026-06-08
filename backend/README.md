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

## 既存データを消してやり直す場合

開発環境で DB を作り直したい場合は、DB コンテナやデータボリュームを削除してから、改めて `make init` -> `make migrate` -> `make seed` を実行します。

## 参考

- `backend/phinx.php`
- `backend/db/migrations/`
- `backend/db/seeds/`
- `Makefile`
