# Alumni Core DB設計書

## 1. 目的

Alumni Coreは、複数のWordPressプラグインから共通利用する卒業生データ基盤である。

- Alumni Voices：卒業生インタビュー・投稿から卒業生情報を登録／更新
- Alumni Directory：名簿を登録／検索／更新
- Alumni Mail：対象卒業生を抽出してメール配信
- 将来の追加プラグイン：同じ卒業生IDを利用

重要な原則は、**卒業生情報を各プラグインが個別に保有しない**ことである。

## 2. 設計原則

1. 卒業生1人を `alumni` の1レコードとして識別する。
2. 複数持つ可能性のある属性はカラムに詰め込まず、中間テーブルまたは子テーブルで管理する。
3. 特に部活動は多対多（卒業生↔部活動）とする。
4. メールアドレスも将来の複数保持に対応する。
5. WordPress投稿とは独立した独自テーブルで名簿を管理する。
6. WordPress標準テーブルへの直接依存を避け、プラグイン間連携は卒業生IDを基本とする。
7. 実際のテーブル名には `$wpdb->prefix` を使用する。

以下では説明上 `wp_` を使用するが、実装では固定しない。

---

# 3. 全体ER構造

```text
wp_alumni
   │
   ├──< wp_alumni_emails
   │
   ├──< wp_alumni_club_memberships >── wp_alumni_clubs
   │
   ├──< wp_alumni_class_memberships
   │
   ├──< wp_alumni_sources
   │
   └──< wp_alumni_consents
```

将来的な拡張例：

```text
wp_alumni
   ├──< addresses
   ├──< career_history
   ├──< education_history
   └──< organization_memberships
```

---

# 4. コアテーブル

## 4.1 `wp_alumni`

卒業生本人を表す主テーブル。

| カラム | 型 | NULL | 説明 |
|---|---|---|---|
| id | BIGINT UNSIGNED | NO | PK |
| family_name | VARCHAR(100) | NO | 姓 |
| given_name | VARCHAR(100) | NO | 名 |
| family_name_kana | VARCHAR(100) | YES | 姓カナ |
| given_name_kana | VARCHAR(100) | YES | 名カナ |
| maiden_name | VARCHAR(100) | YES | 旧姓等 |
| birth_year | SMALLINT UNSIGNED | YES | 生年。必要最小限 |
| graduation_year | SMALLINT UNSIGNED | YES | 卒業年 |
| graduation_term | INT UNSIGNED | YES | 期 |
| status | VARCHAR(30) | NO | active / inactive / deceased / unknown |
| created_at | DATETIME | NO | 作成日時 |
| updated_at | DATETIME | NO | 更新日時 |
| deleted_at | DATETIME | YES | 論理削除 |

### 主なインデックス

```text
PRIMARY KEY (id)
INDEX graduation_year
INDEX graduation_term
INDEX status
INDEX (family_name, given_name)
```

### 注意

卒業年と期は、データが分かる場合に保存する。両方が必須とは限らない。

---

# 5. メールアドレス

## 5.1 `wp_alumni_emails`

1人の卒業生が複数のメールアドレスを持てるようにする。

| カラム | 型 | NULL | 説明 |
|---|---|---|---|
| id | BIGINT UNSIGNED | NO | PK |
| alumni_id | BIGINT UNSIGNED | NO | 卒業生ID |
| email | VARCHAR(254) | NO | メールアドレス |
| is_primary | TINYINT(1) | NO | 主メール |
| is_verified | TINYINT(1) | NO | 確認済み |
| verification_token | VARCHAR(128) | YES | 確認用トークン |
| verified_at | DATETIME | YES | 確認日時 |
| status | VARCHAR(30) | NO | active / bounced / unsubscribed / invalid |
| created_at | DATETIME | NO | 作成日時 |
| updated_at | DATETIME | NO | 更新日時 |

### インデックス

```text
PRIMARY KEY (id)
UNIQUE KEY email (email)
INDEX alumni_id
INDEX (alumni_id, is_primary)
INDEX status
```

### 制約

同一卒業生に主メールアドレスが複数存在しないことは、アプリケーション側でも保証する。

MySQLの部分ユニーク制約に依存せず、保存処理で既存primaryを解除してから新しいprimaryを設定する。

---

# 6. 部活動マスタ

## 6.1 `wp_alumni_clubs`

部活動のマスタテーブル。

| カラム | 型 | NULL | 説明 |
|---|---|---|---|
| id | BIGINT UNSIGNED | NO | PK |
| name | VARCHAR(150) | NO | 部活動名 |
| category | VARCHAR(50) | YES | sports / culture / other 等 |
| description | TEXT | YES | 説明 |
| status | VARCHAR(30) | NO | active / inactive |
| created_at | DATETIME | NO | 作成日時 |
| updated_at | DATETIME | NO | 更新日時 |

### インデックス

```text
PRIMARY KEY (id)
UNIQUE KEY name (name)
INDEX category
INDEX status
```

---

# 7. 卒業生と部活動の多対多関係

## 7.1 `wp_alumni_club_memberships`

**部活動の複数所属に対応する中間テーブル。**

例：

```text
卒業生A → 野球部
卒業生A → 軽音楽部
卒業生B → 吹奏楽部
```

| カラム | 型 | NULL | 説明 |
|---|---|---|---|
| id | BIGINT UNSIGNED | NO | PK |
| alumni_id | BIGINT UNSIGNED | NO | 卒業生ID |
| club_id | BIGINT UNSIGNED | NO | 部活動ID |
| start_year | SMALLINT UNSIGNED | YES | 所属開始年 |
| end_year | SMALLINT UNSIGNED | YES | 所属終了年 |
| role | VARCHAR(100) | YES | 部長等 |
| notes | TEXT | YES | 備考 |
| created_at | DATETIME | NO | 作成日時 |
| updated_at | DATETIME | NO | 更新日時 |

### インデックス

```text
PRIMARY KEY (id)
UNIQUE KEY alumni_club (alumni_id, club_id)
INDEX club_id
INDEX alumni_id
```

### 設計理由

`wp_alumni` に `club_1`, `club_2` のようなカラムを作らない。

また、`野球部,軽音楽部` のようなCSV文字列を保存しない。

これにより、Alumni Mailから「野球部所属者だけ」を安全かつ高速に抽出できる。

---

# 8. クラス履歴

## 8.1 `wp_alumni_class_memberships`

高校在学中は学年ごとにクラスが変わる可能性があるため、クラスを卒業生テーブルの単一カラムにしない。

| カラム | 型 | NULL | 説明 |
|---|---|---|---|
| id | BIGINT UNSIGNED | NO | PK |
| alumni_id | BIGINT UNSIGNED | NO | 卒業生ID |
| school_year | TINYINT UNSIGNED | NO | 1 / 2 / 3 |
| class_name | VARCHAR(50) | NO | 例：A組、3組 |
| academic_year | SMALLINT UNSIGNED | YES | 在籍年度 |
| homeroom_teacher | VARCHAR(150) | YES | 将来拡張用 |
| created_at | DATETIME | NO | 作成日時 |
| updated_at | DATETIME | NO | 更新日時 |

### インデックス

```text
PRIMARY KEY (id)
UNIQUE KEY alumni_school_year (alumni_id, school_year)
INDEX class_name
INDEX academic_year
```

### 例

```text
卒業生A
1年 3組
2年 5組
3年 2組
```

---

# 9. 登録・更新経路

## 9.1 `wp_alumni_sources`

卒業生情報がどの経路から登録・更新されたかを記録する。

| カラム | 型 | NULL | 説明 |
|---|---|---|---|
| id | BIGINT UNSIGNED | NO | PK |
| alumni_id | BIGINT UNSIGNED | NO | 卒業生ID |
| source_type | VARCHAR(50) | NO | 登録元 |
| source_reference | VARCHAR(191) | YES | 投稿ID等 |
| created_at | DATETIME | NO | 登録日時 |

### source_type例

```text
manual
alumni_voices
csv_import
directory_update
admin_update
```

これにより、Alumni Voices経由で何件の卒業生情報が追加・更新されたかを分析できる。

---

# 10. 同意・配信許諾

## 10.1 `wp_alumni_consents`

メール配信や個人情報利用に関する同意履歴を管理する。

| カラム | 型 | NULL | 説明 |
|---|---|---|---|
| id | BIGINT UNSIGNED | NO | PK |
| alumni_id | BIGINT UNSIGNED | NO | 卒業生ID |
| consent_type | VARCHAR(50) | NO | 同意種別 |
| status | VARCHAR(30) | NO | granted / withdrawn |
| granted_at | DATETIME | YES | 同意日時 |
| withdrawn_at | DATETIME | YES | 撤回日時 |
| source | VARCHAR(50) | YES | 取得経路 |
| created_at | DATETIME | NO | 作成日時 |
| updated_at | DATETIME | NO | 更新日時 |

### consent_type例

```text
official_mail
alumni_event_mail
profile_update_mail
```

メールアドレスを持っていることと、メール配信への同意は別概念として扱う。

---

# 11. WordPressとの連携

## 11.1 WordPressユーザー

初期設計では、卒業生がWordPressユーザーであることを必須としない。

理由：

- 卒業生名簿の多くはWordPressログインを持たない
- Alumni Voicesの投稿フォームもログイン必須にしない可能性がある
- 名簿の既存CSVを登録する場合もWordPressユーザーは存在しない

将来、会員ログインを追加する場合は `wp_alumni` に `wp_user_id BIGINT UNSIGNED NULL` を追加するか、別の `wp_alumni_user_links` テーブルを導入する。

## 11.2 Alumni Voicesとの連携

Alumni Voicesは以下を行う。

1. WordPressに投稿を下書きまたは審査待ちで作成
2. 投稿者情報から既存卒業生を検索
3. 新規ならAlumni Coreに卒業生を作成
4. 既存なら必要な情報のみ更新
5. `wp_alumni_sources` に `alumni_voices` と投稿IDを記録

WordPress投稿IDと卒業生IDの関連は、Alumni Voices側の専用テーブルまたはWordPress投稿メタで保持する。

**Alumni CoreはAlumni Voices固有の投稿IDを主データとして持たない。**

---

# 12. メール配信用の抽出例

## 第25期生

```text
wp_alumni.graduation_term = 25
```

## 野球部OB・OG

```text
wp_alumni
JOIN wp_alumni_club_memberships
JOIN wp_alumni_clubs
WHERE club.name = '野球部'
```

## 第25期生かつ野球部

```text
卒業期 = 25
AND
部活動 = 野球部
```

## 複数部活動

```text
野球部 OR サッカー部
```

このため、部活動は多対多構造を採用する。

---

# 13. メールアドレスの扱い

Alumni Mailは卒業生IDから対象者を抽出し、次に `wp_alumni_emails` から配信可能な主メールアドレスを取得する。

対象条件例：

```text
is_primary = 1
AND status = active
AND is_verified = 1
AND 配信同意あり
```

配信対象者のメールアドレスを同期会主催者やOB会主催者へ直接渡さない。

配信は同窓会またはAlumni Mailが代行する。

---

# 14. 削除・退会

名簿情報を即物理削除するのではなく、原則として `deleted_at` を利用した論理削除を検討する。

ただし個人情報削除要求があった場合は、運用ルールに従い必要なデータを匿名化または物理削除できるよう実装する。

---

# 15. 初期実装範囲

最初のAlumni Core実装では以下を対象とする。

### 必須

- `wp_alumni`
- `wp_alumni_emails`
- `wp_alumni_clubs`
- `wp_alumni_club_memberships`
- `wp_alumni_class_memberships`
- `wp_alumni_sources`
- `wp_alumni_consents`

### 後続プラグイン

- Alumni Voices：投稿と卒業生登録
- Alumni Directory：名簿管理UI
- Alumni Mail：対象抽出・配信

---

# 16. Claude実装指示上の重要事項

- テーブル作成・更新はWordPressの `dbDelta()` を使用する。
- テーブル名は `$wpdb->prefix` を使用する。
- 外部キー制約への依存は避け、WordPressプラグインとしてアプリケーション側で整合性を管理する。
- 文字コード・照合順序は `$wpdb->get_charset_collate()` を使用する。
- 個人情報をWordPress投稿本文やログに不用意に複製しない。
- メールアドレス・同意状態・配信停止状態は独立して管理する。
- 部活動など複数値属性をCSV文字列で保存しない。
- 他プラグインが利用するため、Alumni CoreはCRUD操作用のPHP APIを提供する。

## 推奨PHP API例

```php
Alumni_Core::find_alumni( $criteria );
Alumni_Core::create_alumni( $data );
Alumni_Core::update_alumni( $alumni_id, $data );
Alumni_Core::add_email( $alumni_id, $email_data );
Alumni_Core::set_clubs( $alumni_id, $club_ids );
Alumni_Core::set_class_memberships( $alumni_id, $classes );
Alumni_Core::get_alumni( $alumni_id );
```

他プラグインは原則としてDBテーブルを直接更新せず、このAPIを利用する。

---

# 17. 今後の検討事項

- 氏名変更履歴をどこまで保持するか
- 卒業生の住所を管理するか
- WordPress会員ログインを導入するか
- 配信対象条件を保存する「セグメント」機能
- 同期会・OB会など外部依頼者向けの配信依頼フォーム
- 配信依頼の審査・承認フロー
- CSVインポート時の重複判定ルール
- 同姓同名の卒業生識別方法

この設計では、将来的な拡張よりもまず「卒業生を一意に管理し、複数所属と安全なメール配信に対応する」ことを優先する。
