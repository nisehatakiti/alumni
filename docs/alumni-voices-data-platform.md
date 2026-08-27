# Alumni Voices 共通データ基盤

## 決定事項

`alumni-voices` は「卒業生の声」だけのデータベースではなく、同窓会サイト向けプラグイン群が共通利用できるデータ基盤として設計する。

Alumni Voices は最初の機能として「卒業生の声」を提供するが、将来的には他の同窓会向けプラグインも同じデータ基盤を利用できる。

## 基本構造

```text
WordPress標準コンテンツ
        │
        │ post_id / 共通ID
        ▼
Alumni Voices 共通DB
        │
        ├── 連絡先・個人情報
        ├── 本人確認状態
        ├── 投稿日・公開日・最終確認日
        ├── メール送信履歴
        └── 将来のプラグイン共通データ
```

## 卒業生の声

記事そのものは通常のWordPress投稿として管理する。

- タイトル
- 本文
- 公開名
- 公開する属性
- 写真
- 公開状態

個人情報や継続連絡用の情報はAlumni Voices共通DB側で管理する。

例：

`wp_alumni_voices_contacts`

- id
- post_id
- email
- real_name
- graduation_year
- class_name
- club
- verification_status
- submitted_at
- published_at
- last_confirmed_at
- next_confirmation_at
- status
- created_at
- updated_at

## 共通利用方針

将来追加するプラグインは、卒業生の氏名・メールアドレス・卒業年・クラス等を個別に重複保存しない。

必要な場合はAlumni Voices共通DBの卒業生・連絡先情報を参照する。

将来想定する機能：

- 卒業生データベース
- 卒業生店舗・サービス紹介
- 卒業生向け特典
- メールマガジン
- 会報配信
- 在校生との交流

## ID設計

WordPress投稿とは `post_id` で紐付ける。

将来的に複数プラグインが利用する場合を考慮し、卒業生本人を識別する共通ID（例：`contact_id` または `member_id`）を中心に関連データを紐付けられる設計とする。

## 定期処理

Alumni Voices共通DBの確認日・送信履歴を利用して、WP-CronまたはサーバーCRONによる以下の処理を実行できるようにする。

- 年1回の掲載確認メール
- 更新依頼
- 掲載終了確認
- 未回答者へのリマインド
- 将来のメール配信機能
