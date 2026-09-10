<?php
namespace AlumniCore\Admin\Pages;

use AlumniCore\Admin\Admin;
use AlumniCore\Includes\Org_Chart;
use AlumniCore\Includes\Org_Chart_Groups;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Org_Chart_Order_Page {
	const SLUG='alumni-core-org-chart-order';
	const ACTION='alumni_core_save_org_chart_order';
	public function render(){
		$groups=Org_Chart_Groups::instance()->get_all(); $ungrouped=array_filter(Org_Chart::instance()->get_charts(),function($c){return ''===$c['group_id'];});
		?>
		<div class="wrap alumni-person-greeting-order-page"><h1><?php esc_html_e('組織図の並び順','alumni-core'); ?></h1>
		<p class="description"><?php esc_html_e('同じグループ内の組織図をドラッグ＆ドロップで並び替えます。','alumni-core'); ?></p>
		<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="<?php echo esc_attr(self::ACTION); ?>" /><?php wp_nonce_field(self::ACTION); ?>
		<?php foreach($groups as $group){$this->render_group($group['group_id'],$group['name'],Org_Chart::instance()->get_group_charts($group['group_id']));} ?>
		<?php $this->render_group('',__('未設定','alumni-core'),$ungrouped); ?>
		<?php submit_button(__('並び順を保存','alumni-core')); ?></form></div><?php
	}
	private function render_group($id,$name,$charts){?><section class="alumni-person-greeting-order-group"><h2><?php echo esc_html($name); ?></h2><ul class="alumni-person-greeting-sortable"><?php foreach($charts as $chart): ?><li class="alumni-person-greeting-sortable-item" draggable="true"><span class="alumni-person-greeting-drag-handle">☰</span><span class="alumni-person-greeting-sortable-name"><?php echo esc_html($chart['name']); ?></span><input type="hidden" name="orders[<?php echo esc_attr($id ?: '__ungrouped__'); ?>][]" value="<?php echo esc_attr($chart['chart_id']); ?>" /></li><?php endforeach; ?></ul><?php if(empty($charts)): ?><p class="description"><?php esc_html_e('組織図はありません。','alumni-core'); ?></p><?php endif; ?></section><?php }
	public function handle_save(){if(!current_user_can(Admin::CAPABILITY)){wp_die(esc_html__('この操作を行う権限がありません。','alumni-core'));}check_admin_referer(self::ACTION);$orders=isset($_POST['orders'])&&is_array($_POST['orders'])?wp_unslash($_POST['orders']):array();foreach($orders as $group=>$ids){if(is_array($ids)){Org_Chart::instance()->save_group_order('__ungrouped__'===$group?'':sanitize_text_field($group),$ids);}}wp_safe_redirect(add_query_arg(array('page'=>self::SLUG,'updated'=>'1'),admin_url('admin.php')));exit;}
}
