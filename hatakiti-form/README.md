# HATAKITI Form

独立した汎用WordPressフォームプラグインです。

## フォーム用途
- 通常フォーム：通知メールを送信
- 投稿作成フォーム：外部プラグインが提供するスキーマから入力項目を生成し、送信内容からWordPress投稿を下書き作成

## スキーマ提供API

外部プラグインは `hatakiti_form_register_schemas` でスキーマを登録します。

```php
add_action('hatakiti_form_register_schemas', function () {
  HATAKITI_Form_Schema_Registry::register('example_article', array(
    'label' => '記事投稿',
    'post_type' => 'post',
    'fields' => array(
      array('key'=>'title','label'=>'タイトル','type'=>'text','required'=>true),
      array('key'=>'content','label'=>'本文','type'=>'textarea','required'=>true),
      array('key'=>'event_date','label'=>'開催日','type'=>'date','required'=>false),
    ),
    'map' => array(
      'title' => 'title',
      'content' => 'content',
      'meta' => array(
        '_example_event_date' => 'event_date',
      ),
    ),
  ));
});
```

このサンプルをそのまま第三者プラグインの連携例として公開できます。
