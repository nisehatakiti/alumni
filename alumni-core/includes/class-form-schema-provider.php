<?php
namespace AlumniCore\Includes;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * HATAKITI Form 連携用の投稿スキーマ。
 *
 * Alumni Core自身はフォームエンジンを所有せず、「この投稿を作るには
 * どの入力が必要か」を外部フォームプラグインへ宣言するだけにする。
 */
class Form_Schema_Provider {
	public static function register() {
		add_action( 'hatakiti_form_register_schemas', array( __CLASS__, 'register_schemas' ) );
	}

	public static function register_schemas() {
		if ( ! class_exists( '\HATAKITI_Form_Schema_Registry' ) ) return;

		\HATAKITI_Form_Schema_Registry::register( 'alumni_person_greeting', array(
			'label' => 'Alumni Core：人物挨拶',
			'post_type' => 'alumni_content',
			'fields' => array(
				array( 'key'=>'name', 'label'=>'氏名', 'type'=>'text', 'required'=>true ),
				array( 'key'=>'title', 'label'=>'肩書', 'type'=>'text', 'required'=>false ),
				array( 'key'=>'body', 'label'=>'挨拶本文', 'type'=>'textarea', 'required'=>true ),
			),
			'map' => array(
				'title' => 'name',
				'content' => 'body',
				'meta' => array(
					'_alumni_content_kind' => 'person_greeting',
					'_alumni_person_title' => 'title',
				),
			),
		) );

		\HATAKITI_Form_Schema_Registry::register( 'alumni_news_event', array(
			'label' => 'Alumni Core：ニュース・イベント',
			'post_type' => 'alumni_news_event',
			'fields' => array(
				array( 'key'=>'title', 'label'=>'タイトル', 'type'=>'text', 'required'=>true ),
				array( 'key'=>'content', 'label'=>'本文', 'type'=>'textarea', 'required'=>true ),
				array( 'key'=>'event_date', 'label'=>'開催日', 'type'=>'date', 'required'=>false ),
			),
			'map' => array(
				'title' => 'title',
				'content' => 'content',
				'meta' => array(
					'_alumni_event_date' => 'event_date',
				),
			),
		) );
	}
}
