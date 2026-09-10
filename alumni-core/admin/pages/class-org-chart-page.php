<?php
/**
 * 同窓会組織図の一覧・編集画面.
 *
 * @package AlumniCore
 */

namespace AlumniCore\Admin\Pages;

use AlumniCore\Admin\Admin;
use AlumniCore\Includes\Org_Chart;
use AlumniCore\Includes\Org_Chart_Groups;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Org_Chart_Page {
	const SLUG='alumni-core-org-chart';
	const ADD_SLUG='alumni-core-org-chart-add';
	const ACTION_CREATE_CHART='alumni_core_create_org_chart';
	const ACTION_UPDATE_CHART='alumni_core_update_org_chart';
	const ACTION_DELETE_CHART='alumni_core_delete_org_chart';
	const ACTION_CREATE_NODE='alumni_core_create_org_chart_node';
	const ACTION_UPDATE_NODE='alumni_core_update_org_chart_node';
	const ACTION_DELETE_NODE='alumni_core_delete_org_chart_node';
	const ACTION_MOVE_NODE='alumni_core_move_org_chart_node';
	const ACTION_REPARENT_NODE='alumni_core_reparent_org_chart_node';
	const ACTION_SAVE_DISPLAY='alumni_core_save_org_chart_display_settings';

	public function render_add(){ if(!current_user_can(Admin::CAPABILITY)){return;} $this->render_chart_form(null); }

	public function render(){
		if(!current_user_can(Admin::CAPABILITY)){return;}
		$chart_id=isset($_GET['chart'])?sanitize_text_field(wp_unslash($_GET['chart'])):'';
		if('new'===($chart_id?:'') || isset($_GET['new'])){$this->render_chart_form(null);return;}
		$chart=''!==$chart_id?Org_Chart::instance()->get_chart($chart_id):null;
		if(null!==$chart){Org_Chart::instance()->set_active_chart($chart_id);$node_id=isset($_GET['node'])?sanitize_text_field(wp_unslash($_GET['node'])):'';$node=$node_id?Org_Chart::instance()->get_node($node_id):null;if($node){$this->render_node_editor($chart,$node);return;}$this->render_chart_editor($chart);return;}
		$this->render_index();
	}

	private function base($args=array()){return add_query_arg(array_merge(array('page'=>self::SLUG),$args),admin_url('admin.php'));}
	private function render_index(){ $charts=Org_Chart::instance()->get_charts(); ?>
		<div class="wrap"><h1><?php esc_html_e('同窓会組織図','alumni-core'); ?><a href="<?php echo esc_url($this->base(array('new'=>1))); ?>" class="page-title-action"><?php esc_html_e('組織図を追加','alumni-core'); ?></a></h1>
		<p class="description"><?php esc_html_e('複数の組織図を作成し、組織図専用グループごとに公開できます。親メニュー自体が組織図一覧です。別途「組織図一覧」メニューは作成しません。','alumni-core'); ?></p>
		<table class="widefat striped"><thead><tr><th><?php esc_html_e('組織図名','alumni-core'); ?></th><th><?php esc_html_e('グループ','alumni-core'); ?></th><th><?php esc_html_e('操作','alumni-core'); ?></th></tr></thead><tbody>
		<?php foreach($charts as $chart): $group=''!==$chart['group_id']?Org_Chart_Groups::instance()->get_group($chart['group_id']):null; ?><tr><td><?php echo esc_html($chart['name']?:__('（無題）','alumni-core')); ?></td><td><?php echo esc_html($group?$group['name']:__('未設定','alumni-core')); ?></td><td><a class="button button-small" href="<?php echo esc_url($this->base(array('chart'=>$chart['chart_id']))); ?>"><?php esc_html_e('編集','alumni-core'); ?></a>
		<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline" onsubmit="return confirm('<?php echo esc_js(__('この組織図を削除します。よろしいですか？','alumni-core')); ?>');"><input type="hidden" name="action" value="<?php echo esc_attr(self::ACTION_DELETE_CHART); ?>"><input type="hidden" name="chart_id" value="<?php echo esc_attr($chart['chart_id']); ?>"><?php wp_nonce_field(self::ACTION_DELETE_CHART); ?><button class="button button-small"><?php esc_html_e('削除','alumni-core'); ?></button></form></td></tr><?php endforeach; ?>
		<?php if(empty($charts)): ?><tr><td colspan="3"><?php esc_html_e('組織図はまだありません。','alumni-core'); ?></td></tr><?php endif; ?></tbody></table></div><?php }

	private function render_chart_form($chart){$groups=Org_Chart_Groups::instance()->get_all();$editing=is_array($chart);?><div class="wrap"><h1><?php echo esc_html($editing?__('組織図を編集','alumni-core'):__('組織図を追加','alumni-core')); ?><a href="<?php echo esc_url($this->base()); ?>" class="page-title-action"><?php esc_html_e('一覧に戻る','alumni-core'); ?></a></h1><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="<?php echo esc_attr($editing?self::ACTION_UPDATE_CHART:self::ACTION_CREATE_CHART); ?>"><?php if($editing): ?><input type="hidden" name="chart_id" value="<?php echo esc_attr($chart['chart_id']); ?>"><?php endif; ?><?php wp_nonce_field($editing?self::ACTION_UPDATE_CHART:self::ACTION_CREATE_CHART); ?><table class="form-table"><tr><th><?php esc_html_e('組織図名','alumni-core'); ?></th><td><input class="regular-text" required name="name" value="<?php echo esc_attr($editing?$chart['name']:''); ?>"></td></tr><tr><th><?php esc_html_e('公開グループ','alumni-core'); ?></th><td><select name="group_id"><option value=""><?php esc_html_e('（未設定）','alumni-core'); ?></option><?php foreach($groups as $group): ?><option value="<?php echo esc_attr($group['group_id']); ?>" <?php selected($editing?$chart['group_id']:'',$group['group_id']); ?>><?php echo esc_html($group['name']); ?></option><?php endforeach; ?></select></td></tr></table><?php submit_button($editing?__('保存','alumni-core'):__('組織図を作成','alumni-core')); ?></form></div><?php}

	private function render_chart_editor($chart){$this->render_chart_form($chart);Org_Chart::instance()->set_active_chart($chart['chart_id']);$roots=Org_Chart::instance()->get_children('');?><div class="wrap alumni-core-org-chart"><h2><?php esc_html_e('組織図の構造','alumni-core'); ?></h2><?php if(empty($roots)): ?><p class="description"><?php esc_html_e('まだノードがありません。','alumni-core'); ?></p><?php else:$this->render_nodes($chart['chart_id'],$roots,0);endif;?><h2><?php esc_html_e('＋ ノードを追加','alumni-core'); ?></h2><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="<?php echo esc_attr(self::ACTION_CREATE_NODE); ?>"><input type="hidden" name="chart_id" value="<?php echo esc_attr($chart['chart_id']); ?>"><?php wp_nonce_field(self::ACTION_CREATE_NODE); ?><input name="name" class="regular-text" required placeholder="<?php echo esc_attr__('例：会長、副会長、総務委員会','alumni-core'); ?>"> <select name="parent_id"><option value=""><?php esc_html_e('（トップレベル）','alumni-core'); ?></option><?php $this->parent_options(''); ?></select><?php submit_button(__('＋ ノードを追加','alumni-core'),'secondary','submit',false); ?></form></div><?php}

	private function render_nodes($chart_id,array $nodes,$depth){foreach($nodes as $node){$url=$this->base(array('chart'=>$chart_id,'node'=>$node['node_id']));$children=Org_Chart::instance()->get_children($node['node_id']);?><div class="alumni-org-chart-row" style="margin-left:<?php echo (int)($depth*2); ?>em"><span class="alumni-org-chart-row-label"><?php echo esc_html($node['name']); ?></span><a class="button button-small" href="<?php echo esc_url($url); ?>"><?php esc_html_e('編集','alumni-core'); ?></a><?php $this->node_action(self::ACTION_MOVE_NODE,$chart_id,$node['node_id'],__('↑','alumni-core'),array('direction'=>'up'));$this->node_action(self::ACTION_MOVE_NODE,$chart_id,$node['node_id'],__('↓','alumni-core'),array('direction'=>'down'));$this->node_action(self::ACTION_DELETE_NODE,$chart_id,$node['node_id'],__('削除','alumni-core'),array(),true);?></div><?php if(!empty($children)){$this->render_nodes($chart_id,$children,$depth+1);}}}

	private function node_action($action,$chart_id,$node_id,$label,$extra=array(),$confirm=false){?><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline"<?php echo $confirm?' onsubmit="return confirm(\''.esc_js(__('このノードを削除します。','alumni-core')).'\');"':''; ?>><input type="hidden" name="action" value="<?php echo esc_attr($action); ?>"><input type="hidden" name="chart_id" value="<?php echo esc_attr($chart_id); ?>"><input type="hidden" name="node_id" value="<?php echo esc_attr($node_id); ?>"><?php foreach($extra as $k=>$v): ?><input type="hidden" name="<?php echo esc_attr($k); ?>" value="<?php echo esc_attr($v); ?>"><?php endforeach; ?><?php wp_nonce_field($action); ?><button class="button button-small"><?php echo esc_html($label); ?></button></form><?php}

	private function render_node_editor($chart,$node){?><div class="wrap"><h1><?php esc_html_e('ノードの編集','alumni-core'); ?><a href="<?php echo esc_url($this->base(array('chart'=>$chart['chart_id']))); ?>" class="page-title-action"><?php esc_html_e('組織図へ戻る','alumni-core'); ?></a></h1><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="<?php echo esc_attr(self::ACTION_UPDATE_NODE); ?>"><input type="hidden" name="chart_id" value="<?php echo esc_attr($chart['chart_id']); ?>"><input type="hidden" name="node_id" value="<?php echo esc_attr($node['node_id']); ?>"><?php wp_nonce_field(self::ACTION_UPDATE_NODE); ?><input name="name" class="regular-text" value="<?php echo esc_attr($node['name']); ?>"><?php submit_button(__('保存','alumni-core')); ?></form><h2><?php esc_html_e('親ノードを変更','alumni-core'); ?></h2><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="<?php echo esc_attr(self::ACTION_REPARENT_NODE); ?>"><input type="hidden" name="chart_id" value="<?php echo esc_attr($chart['chart_id']); ?>"><input type="hidden" name="node_id" value="<?php echo esc_attr($node['node_id']); ?>"><?php wp_nonce_field(self::ACTION_REPARENT_NODE); ?><select name="parent_id"><option value=""><?php esc_html_e('（トップレベル）','alumni-core'); ?></option><?php $this->parent_options($node['node_id']); ?></select><?php submit_button(__('親ノードを変更','alumni-core'),'secondary'); ?></form></div><?php}

	private function parent_options($exclude){$exclude_ids=$exclude?array_merge(array($exclude),Org_Chart::instance()->get_descendant_ids($exclude)):array();$this->parent_rows(Org_Chart::instance()->get_children(''),$exclude_ids,0);}
	private function parent_rows($nodes,$exclude,$depth){foreach($nodes as $node){if(!in_array($node['node_id'],$exclude,true)){printf('<option value="%1$s">%2$s</option>',esc_attr($node['node_id']),esc_html(str_repeat('— ',$depth).$node['name']));}$children=Org_Chart::instance()->get_children($node['node_id']);if($children){$this->parent_rows($children,$exclude,$depth+1);}}}

	private function guard($action){if(!current_user_can(Admin::CAPABILITY)){wp_die(esc_html__('この操作を行う権限がありません。','alumni-core'));}check_admin_referer($action);}
	private function posted_chart(){ $id=isset($_POST['chart_id'])?sanitize_text_field(wp_unslash($_POST['chart_id'])):''; Org_Chart::instance()->set_active_chart($id); return $id; }
	private function redirect_chart($id,$extra=array()){wp_safe_redirect($this->base(array_merge(array('chart'=>$id),$extra)));exit;}

	public function handle_create_chart(){$this->guard(self::ACTION_CREATE_CHART);$id=Org_Chart::instance()->create_chart(isset($_POST['name'])?wp_unslash($_POST['name']):'',isset($_POST['group_id'])?sanitize_text_field(wp_unslash($_POST['group_id'])):'');$this->redirect_chart($id);}
	public function handle_update_chart(){$this->guard(self::ACTION_UPDATE_CHART);$id=$this->posted_chart();Org_Chart::instance()->update_chart($id,isset($_POST['name'])?wp_unslash($_POST['name']):'',isset($_POST['group_id'])?sanitize_text_field(wp_unslash($_POST['group_id'])):'');$this->redirect_chart($id);}
	public function handle_delete_chart(){$this->guard(self::ACTION_DELETE_CHART);Org_Chart::instance()->delete_chart(isset($_POST['chart_id'])?sanitize_text_field(wp_unslash($_POST['chart_id'])):'');wp_safe_redirect($this->base());exit;}
	public function handle_create(){$this->guard(self::ACTION_CREATE_NODE);$id=$this->posted_chart();Org_Chart::instance()->create_node(isset($_POST['parent_id'])?sanitize_text_field(wp_unslash($_POST['parent_id'])):'',isset($_POST['name'])?wp_unslash($_POST['name']):'');$this->redirect_chart($id);}
	public function handle_update(){$this->guard(self::ACTION_UPDATE_NODE);$id=$this->posted_chart();Org_Chart::instance()->update_node(isset($_POST['node_id'])?sanitize_text_field(wp_unslash($_POST['node_id'])):'',isset($_POST['name'])?wp_unslash($_POST['name']):'');$this->redirect_chart($id);}
	public function handle_delete(){$this->guard(self::ACTION_DELETE_NODE);$id=$this->posted_chart();Org_Chart::instance()->delete_node(isset($_POST['node_id'])?sanitize_text_field(wp_unslash($_POST['node_id'])):'');$this->redirect_chart($id);}
	public function handle_move(){$this->guard(self::ACTION_MOVE_NODE);$id=$this->posted_chart();Org_Chart::instance()->move_node(isset($_POST['node_id'])?sanitize_text_field(wp_unslash($_POST['node_id'])):'',isset($_POST['direction'])?sanitize_key(wp_unslash($_POST['direction'])):'');$this->redirect_chart($id);}
	public function handle_reparent(){$this->guard(self::ACTION_REPARENT_NODE);$id=$this->posted_chart();Org_Chart::instance()->set_parent(isset($_POST['node_id'])?sanitize_text_field(wp_unslash($_POST['node_id'])):'',isset($_POST['parent_id'])?sanitize_text_field(wp_unslash($_POST['parent_id'])):'');$this->redirect_chart($id);}
	public function handle_save_display_settings(){/* legacy route retained for compatibility; display is shared and handled by existing option. */$this->guard(self::ACTION_SAVE_DISPLAY);update_option(\AlumniCore\Includes\Org_Chart_Shortcode::OPTION_DISPLAY_SETTINGS,array('show_connectors'=>isset($_POST['show_connectors']),'show_boxes'=>isset($_POST['show_boxes'])));wp_safe_redirect($this->base());exit;}
}
