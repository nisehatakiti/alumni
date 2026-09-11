# NHK Form

**NHK Form (Nisehatakiti Form)** は、WordPress用の汎用フォームプラグインです。

通常の問い合わせ・申込フォームだけでなく、送信内容から任意のWordPress投稿タイプの**下書きを作成する投稿作成フォーム**を提供します。

外部プラグインはスキーマを登録するだけで、専用フォームの入力項目をNHK Formへ自動提案できます。

## 主な機能

- テキスト、メール、電話、数値、日付、複数行テキスト
- セレクト、ラジオ、チェックボックス
- ファイル添付
- 必須・文字数・メール・日付・選択肢バリデーション
- 項目幅と改行設定
- 複数通知先
- From / Reply-To 設定
- フォームのメールアドレスを Reply-To に使用
- 自動返信
- 投稿下書き作成
- 投稿本文・カスタムフィールド・添付ファイルのスキーママッピング
- 外部プラグインとの自動スキーマ連携

## インストール

1. `nhk-form` フォルダをZIP化します。
2. WordPress管理画面の「プラグイン → 新規プラグインを追加 → プラグインのアップロード」からZIPをアップロードします。
3. 有効化します。
4. 管理メニューの「フォーム」からフォームを作成します。

## ショートコード

```
[nhk_form id="123"]
```

## 外部プラグイン連携

外部プラグインは `nhk_form_register_schemas` アクションまたは `nhk_form_schemas` フィルターで投稿スキーマを登録できます。

例:

```php
add_action( 'nhk_form_register_schemas', function () {
    NHK_Form_Schema_Registry::register( 'example_news', array(
        'label'     => 'ニュース投稿',
        'post_type' => 'example_news',
        'fields'    => array(
            array(
                'key'      => 'title',
                'label'    => 'タイトル',
                'type'     => 'text',
                'required' => true,
            ),
        ),
        'map'       => array(
            'title'   => 'title',
            'content' => 'content',
            'meta'    => array(),
            'files'   => array(),
        ),
    ) );
} );
```

## アンインストール

現在は安全のため、プラグイン削除時にフォーム投稿・設定・送信済みファイルを自動削除しません。
将来的に専用のデータ削除オプションを追加する方針です。

## 開発者

nisehatakiti
