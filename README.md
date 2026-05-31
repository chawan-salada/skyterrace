# SkyTerrace予約サイト

黒崎ビルの5階フロアを時間貸しするための予約システムです。

## 技術スタック（開発）

- 実行: Docker Desktop + Laravel Sail
- アプリ: Laravel 12 + PHP 8.5
- DB: MySQL（Sail）
- エディタ: Cursor（`cursor .`）

## 機能要件（MVP）

- ユーザ認証でログインする
- 5階レンタルスペースのレイアウト、設備、レンタル可能機材、注意事項を表示する
- カレンダーから日付を選択し、空いている開始時間から終了時間を選択する
- 予約は30分単位で受け付ける
- 既存予約の前後30分は他予約を不可とする
- 支払いは4階で現金払い
- ユーザ情報は当面ローカル管理（将来的に他システム Grape 連携予定）
- 予約前日にLINE通知を送信する

## 開発環境の起動（Sail）

```bash
./vendor/bin/sail up -d
```

アプリ確認:

```bash
./vendor/bin/sail artisan -V
```

初回DB準備:

```bash
./vendor/bin/sail artisan migrate
```

停止:

```bash
./vendor/bin/sail down
```

## WSL / Linux での権限（Permission denied）対策

WSL でファイル権限が合わずコンテナ内から書き込めない場合、`.env` に以下を追加してから `sail up` してください。

```bash
WWWUSER=$(id -u)
WWWGROUP=$(id -g)
```
