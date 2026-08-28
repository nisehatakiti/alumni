=== Alumni Core ===
Contributors: alumni-project
Tags: alumni, education, community
Requires at least: 5.9
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

同窓会ホームページパッケージの共通データ基盤プラグイン。同窓会の基本設定と卒業期計算の基盤機能を提供します。

== Description ==

Alumni Core は、同窓会ホームページパッケージにおけるデータ管理・管理画面・設定の基盤プラグインです。
公開サイトの表示は Alumni Theme が担当し、Alumni Core は独自データの管理に専念します。

現在のバージョンで提供する機能:

* 「同窓会」管理メニューとダッシュボード
* 同窓会基本設定（同窓会名称、学校名称、学校創立年、第1期卒業年）
* 卒業期カラー機能（周期数に応じた可変カラー設定）
* 卒業年から卒業期を計算する共通ロジック
* 卒業期からカラーを計算する共通ロジック

今後、名簿管理・メールマガジン・Alumni Voices など複数の機能がこの基盤の上に追加される予定です。

== Installation ==

1. `alumni-core` フォルダを `/wp-content/plugins/` にアップロードします。
2. WordPress管理画面の「プラグイン」からAlumni Coreを有効化します。
3. 「同窓会」メニューの「基本設定」から同窓会の基本情報を登録します。

== Changelog ==

= 0.1.0 =
* 初回リリース：Core／Themeの基盤構築、同窓会基本設定、卒業期計算機能。
