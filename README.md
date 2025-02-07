# pra_eccube2

### db

`docker exec -it ec-cube2-mysql-1 mysql -u root -p`

#### テーブル定義書

ec-cube2/docs/database-schema/README.md

- ページ設定
  ec-cube2/docs/database-schema/public.dtb_pagelayout.md

```md
| Name     | Type | Default | Nullable | Children | Parents | Comment    |
| -------- | ---- | ------- | -------- | -------- | ------- | ---------- |
| url      | text |         | false    |          |         | ページ URL |
| filename | text |         | true     |          |         | ファイル名 |
```

- url: .php のファイル名（例　 shopping/complete.php）
- filename: .tpl のファイル名（例　 shopping/complete）
insett文はec-cube2/html/install/sql/insert_data.sqlから引用
```sql
INSERT INTO `dtb_pagelayout` (`device_type_id`, `page_id`, `page_name`, `url`, `filename`, `header_chk`, `footer_chk`, `edit_flg`, `author`, `description`, `keyword`, `update_url`, `create_date`, `update_date`, `meta_robots`) VALUES ('10', '29', '新着情報', 'news/index.php', 'news/news', '1', '1','2', NULL, NULL, NULL, NULL, CURRENT_TIME(), CURRENT_TIME(), NULL);
```
  - 端末ごとにページ設定できる `mysql> select * from mtb_device_type \G`

### git

- 基本操作
  注意：ls と pwd でディレクトリ確認してから！

```
git init
git commit -m "first commit"
git branch -M main
git remote add origin https://github.com/yu-ka3028/pra_eccube2.git
git push -u origin main
```

- エラー対応
  ローカル -> リモート(強制)
  `git push -u origin main --force`

# issue テンプレート(設定するまで一時置き場)

```md
# 概要

---

## 実装内容

### path

### todo

- [ ]
- [ ]
- [ ]

### ゴール

-

# 参考文献と考察

# 備考
```

# EC-CUBE 2.17 系

[![CI/CD for EC-CUBE](https://github.com/EC-CUBE/ec-cube2/actions/workflows/main.yml/badge.svg)](https://github.com/EC-CUBE/ec-cube2/actions/workflows/main.yml)
[![codecov](https://codecov.io/gh/EC-CUBE/ec-cube2/branch/master/graph/badge.svg?token=4oNLGhIQwy)](https://codecov.io/gh/EC-CUBE/ec-cube2)
[![PHP Versions Supported](https://img.shields.io/badge/php-%3E%3D%207.4-8892BF.svg)](#php-version-support)
[![GitHub All Releases](https://img.shields.io/github/downloads/EC-CUBE/ec-cube2/total)](https://github.com/EC-CUBE/ec-cube2/releases)

---

## EC-CUBE Trac について

EC-CUBE 2.13 系については、2014 年 10 月以前に利用されていた、[EC-CUBE Trac](http://svn.ec-cube.net/open_trac/) と[SVN](http://svn.ec-cube.net/open/)がございますので、合わせてご参照ください。
新規のご投稿やコミットはいただけませんが、GitHub に移されていない不具合の情報や過去の経緯などをご確認いただけます。

EC-CUBE Trac にある議論の再開や不具合の修正についても、GitHub に Issue を再作成していただいたり、Pull request をいただけますと幸いです。

## 開発協力

コードの提供・追加、修正・変更その他「EC-CUBE」への開発の御協力（以下「コミット」といいます）を行っていただく場合には、
[EC-CUBE のコピーライトポリシー](https://github.com/EC-CUBE/ec-cube/wiki/EC-CUBE%E3%81%AE%E3%82%B3%E3%83%94%E3%83%BC%E3%83%A9%E3%82%A4%E3%83%88%E3%83%9D%E3%83%AA%E3%82%B7%E3%83%BC)をご理解いただき、ご了承いただく必要がございます。
Pull request を送信する際は、EC-CUBE のコピーライトポリシーに同意したものとみなします。

## 開発方針

本リポジトリでは、以下方針で開発を行っています。

### 2.17 系

- EC-CUBE 2.13 系の PHP7 及び PHP8 対応バージョンです。
- `master` ブランチで開発を行っています。
- PHP5.4 互換ブランチは [compatible/php5.4](https://github.com/EC-CUBE/ec-cube2/tree/compatible/php5.4) にて保守しています。(2024 年 6 月末日まで)

#### システム要件

| 分類      | ソフトウェア | Version                                                  |
| --------- | ------------ | -------------------------------------------------------- |
| WebServer | Apache       | 2.4.x or higher<br> (mod_rewrite / mod_ssl 必須)         |
| PHP       | PHP          | 7.4.33 or higher                                         |
| Database  | PostgreSQL   | 9.x or higher                                            |
| Database  | MySQL        | 5.x / 8.0.x / 8.4.x or higher<br> (InnoDB エンジン 必須) |

##### 必要な PHP Extensions

| 分類 | Extensions                                                                                                                                                                                                                                          |
| ---- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 必須 | pgsql / mysqli (利用するデータベースに合わせること) <br> pdo_pgsql / pdo_mysql (利用するデータベースに合わせること) <br> pdo <br> mbstring <br> zlib <br> ctype <br> session <br> JSON <br> xml <br> libxml <br> OpenSSL <br> zip <br> cURL <br> gd |
| 推奨 | hash <br> APCu <br> Zend OPcache                                                                                                                                                                                                                    |

## インストール方法

EC-CUBE のインストールは、以下の方法があります。

1. パッケージを使用してインストールする
1. コマンドラインからインストールする
1. docker compose を使用してインストールする

### パッケージを使用してインストールする

[EC-CUBE のパッケージ](https://github.com/EC-CUBE/ec-cube2/releases/latest)をダウンロードし、解凍してください。

FTP/SSH を使用し、ファイルをサーバへアップロードしてください。
※ファイル数が多いためエラーが発生することがございます。エラー発生時は分割してアップロードをお願いします。

データベースを作成し、Web サーバを起動してください。
_DocumentRoot を `{EC-CUBEをアップロードしたディレクトリ}/html` に設定しておく必要があります。_

ブラウザから EC-CUBE にアクセスすると Web インストーラが表示されますので、指示にしたがってインストールしてください。

### コマンドラインからインストールする

- _不具合修正やバージョンアップに追従しやすくしたい場合におすすめです。_

以下をコマンドラインで実行します。

```shell
git clone https://github.com/EC-CUBE/ec-cube2.git
cd ec-cube2
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php
php -r "unlink('composer-setup.php');"
php composer.phar install --no-dev --no-interaction -o
```

データベースを作成し、Web サーバを起動してください。
_DocumentRoot を `{EC-CUBEをアップロードしたディレクトリ}/html` に設定しておく必要があります。_

ブラウザから EC-CUBE にアクセスすると Web インストーラが表示されますので、指示にしたがってインストールしてください。

### docker compose を使用してインストールする

- _開発環境におすすめです。_

それぞれのコマンドの実行完了してから https://localhost:4430/ へアクセスすると、EC-CUBE のフロント画面が表示されます。
管理画面は https://localhost:4430/admin/ へアクセスしてください。

#### PostgreSQL を使用する場合

docker-compose.pgsql.yml を指定します。 data/config/config.php が存在しない場合は、 EC-CUBE のインストールまで実行します。

```shell
git clone https://github.com/EC-CUBE/ec-cube2.git
cd ec-cube2
docker compose -f docker-compose.yml -f docker-compose.pgsql.yml up
```

#### MySQL を使用する場合

docker-compose.mysql.yml を指定します。 data/config/config.php が存在しない場合は、 EC-CUBE のインストールまで実行します。

```shell
git clone https://github.com/EC-CUBE/ec-cube2.git
cd ec-cube2
docker compose -f docker-compose.yml -f docker-compose.mysql.yml up
```

#### DB を別途用意する場合

php:7.4-apache のみ起動します

```shell
git clone https://github.com/EC-CUBE/ec-cube2.git
cd ec-cube2
docker compose up
```

#### ローカル環境をマウントする場合

docker-compose.dev.yml を指定します。

```shell
git clone https://github.com/EC-CUBE/ec-cube2.git
cd ec-cube2

## MySQL を使用する例
docker compose -f docker-compose.yml -f docker-compose.mysql.yml -f docker-compose.dev.yml up
```

## E2E テストの実行方法

E2E テストは [Playwright](https://playwright.dev/) によって作成されています。以下の手順で実行します。

### PostgreSQL の場合

```
## 必要な環境変数を設定
export COMPOSE_FILE=docker-compose.yml:docker-compose.pgsql.yml:docker-compose.dev.yml

## docker compose up を実行
docker compose up -d --wait

## ダミーデータ生成
docker compose exec -T ec-cube composer install
docker compose exec -T ec-cube composer require ec-cube2/cli "dev-master@dev" -W
docker compose exec -T ec-cube composer update 'symfony/*' -W
docker compose exec -T ec-cube php data/vendor/bin/eccube eccube:fixtures:generate --products=5 --customers=1 --orders=5
## 会員のメールアドレスを zap_user@example.com へ変更
docker compose exec -T postgres psql --user=eccube_db_user eccube_db -c "UPDATE dtb_customer SET email = 'zap_user@example.com' WHERE customer_id = (SELECT MAX(customer_id) FROM dtb_customer WHERE status = 2 AND del_flg = 0);"

## playwright をインストール
yarn install
yarn run playwright install --with-deps chromium
yarn playwright install-deps chromium

## 管理画面の E2E テストを実行
yarn test:e2e e2e-tests/test/admin
## フロント(ゲスト)のE2Eテストを実行
yarn test:e2e --workers=1 e2e-tests/test/front_guest
## フロント(ログイン)のE2Eテストを実行
yarn test:e2e --workers=1 e2e-tests/test/front_login
```

### MySQL の場合

```
## 環境変数を設定
export COMPOSE_FILE=docker-compose.yml:docker-compose.mysql.yml:docker-compose.dev.yml

## docker compose up を実行
docker compose up -d --wait

## ダミーデータ生成
docker compose exec -T ec-cube composer install
docker compose exec -T ec-cube composer require ec-cube2/cli "dev-master@dev" -W
docker compose exec -T ec-cube composer update 'symfony/*' -W
docker compose exec -T ec-cube php data/vendor/bin/eccube eccube:fixtures:generate --products=5 --customers=1 --orders=5
## 会員のメールアドレスを zap_user@example.com へ変更
docker compose exec mysql mysql --user=eccube_db_user --password=password eccube_db -e "UPDATE dtb_customer SET email = 'zap_user@example.com' WHERE customer_id = (SELECT customer_id FROM (SELECT MAX(customer_id) FROM dtb_customer WHERE status = 2 AND del_flg = 0) AS A);"

## playwright をインストール
yarn install
yarn run playwright install --with-deps chromium
yarn playwright install-deps chromium

## 管理画面の E2E テストを実行
yarn test:e2e e2e-tests/test/admin
## フロント(ゲスト)のE2Eテストを実行
yarn test:e2e --workers=1 e2e-tests/test/front_guest
## フロント(ログイン)のE2Eテストを実行
yarn test:e2e --workers=1 e2e-tests/test/front_login
```

---

- EC-CUBE4 系については、 [EC-CUBE/ec-cube](https://github.com/EC-CUBE/ec-cube) にて開発を行っております。
- EC-CUBE3 系については、 [EC-CUBE/ec-cube3](https://github.com/EC-CUBE/ec-cube3) にて開発を行っております。
