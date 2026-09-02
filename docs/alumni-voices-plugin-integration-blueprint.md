# Alumni-Voices プラグイン連携 Blueprint

## 目的

「卒業生の声」は Alumni Core の固定機能として実装せず、将来の独立プラグイン（Alumni-Voices）が有効化されたときに、Alumni Core のサイト構造へ自動参加する。

## 確定仕様

- Alumni Core 単体の初期状態では「卒業生の声」を標準Menu Structureへ登録しない。
- Alumni-Voices プラグインをインストール・有効化した際に、Alumni-Voices側から必要なMenu Structure項目を登録できるようにする。
- 自動追加先は原則として `在校生向け` 配下。
- 表示名は「卒業生の声」を標準とする。
- 既存のMenu Structureをリセット・上書きしない。
- 管理者が既に同名項目を作成している場合は重複させない。
- プラグイン無効化・削除時に、管理者が変更したMenu Structureを勝手に削除しない。
- Homepage Gridについても、Alumni-Voices有効化時に候補として利用可能になる設計を優先する。既存の管理者設定を上書きしない。

## プラグイン責務

```text
Alumni Core
  ├─ Menu Structureの共通基盤
  ├─ Homepage Gridの共通基盤
  └─ 標準サイト構成
          ↑
          │ API / integration
          │
Alumni-Voices
  └─ 卒業生の声
```

Alumni CoreがAlumni-Voicesの存在を前提に架空の入口を生成するのではなく、Alumni-Voicesが有効化されたときに自らCoreへ参加する。

## 標準サイト構成との関係

Alumni Core単体：

```text
共通
├─ 母校校長挨拶
├─ 同窓会長挨拶
├─ お知らせ
├─ イベント
└─ 校歌・校訓

卒業生向け
└─ 同窓会情報
   ├─ 同窓会組織図
   ├─ 役員・理事紹介
   ├─ 規約類
   └─ 卒業期早見表

在校生向け
└─ （Alumni-Voices未導入時は空でもよい）
```

Alumni-Voices有効化後：

```text
在校生向け
└─ 卒業生の声
```

## 将来拡張

同じ方式でTeacher's Voice、活動報告、会報等の独立プラグインもAlumni CoreのMenu Structure / Homepage Gridへ参加できるようにする。

新しいプラグインを追加するたびにCore本体の標準サイト構成を変更する必要がないことを目標とする。
