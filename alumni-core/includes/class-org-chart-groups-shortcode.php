<?php
/**
 * 同窓会組織図グループの公開ページ.
 *
 * @package AlumniCore
 */

namespace AlumniCore\Includes;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Org_Chart_Groups_Shortcode {
	const SHORTCODE='alumni_org_chart_group';
	const PAGE_ID_OPTION_PREFIX='alumni_core_org_chart_group_page_';

	public static function register(){
		add_shortcode(self::SHORTCODE,array(__CLASS__,'render_shortcode'));
		if(is_admin()){add_action('admin_init',array(__CLASS__,'maybe_create_pages'));}
	}

	public static function maybe_create_pages(){
		foreach(Org_Chart_Groups::instance()->get_all() as $group){self::maybe_create_group_page($group);}
	}

	private static function maybe_create_group_page($group){
		$option=self::PAGE_ID_OPTION_PREFIX.$group['group_id'];$page_id=(int)get_option($option,0);
		if($page_id&&'page'===get_post_type($page_id)){
			$page=get_post($page_id);if($page&&$page->post_title!==$group['name']){wp_update_post(array('ID'=>$page_id,'post_title'=>$group['name']));}return;
		}
		$slug=sanitize_title('org-chart-group-'.$group['name']);if(''===$slug){$slug='org-chart-group-'.substr($group['group_id'],0,8);}
		$existing=get_page_by_path($slug,OBJECT,'page');if($existing instanceof \WP_Post){update_option($option,(int)$existing->ID);return;}
		$new_id=wp_insert_post(array('post_type'=>'page','post_status'=>'publish','post_title'=>$group['name'],'post_name'=>$slug,'post_content'=>'['.self::SHORTCODE.' id="'.$group['group_id'].'"]'),true);
		if(!is_wp_error($new_id)&&$new_id){update_option($option,(int)$new_id);}
	}

	public static function get_group_url($group_id){
		$group=Org_Chart_Groups::instance()->get_group($group_id);if(null===$group){return '';}
		self::maybe_create_group_page($group);$id=(int)get_option(self::PAGE_ID_OPTION_PREFIX.$group['group_id'],0);
		return $id&&'page'===get_post_type($id)?(string)get_permalink($id):'';
	}

	public static function render_shortcode($atts){
		$atts=shortcode_atts(array('id'=>''),(array)$atts,self::SHORTCODE);
		$group=Org_Chart_Groups::instance()->get_group((string)$atts['id']);
		if(null===$group){return '<p class="alumni-notice">'.esc_html__('この組織図グループは見つかりませんでした。','alumni-core').'</p>';}
		$charts=Org_Chart::instance()->get_group_charts($group['group_id']);
		ob_start();?><div class="alumni-org-chart-group"><?php if(empty($charts)): ?><p class="alumni-notice"><?php esc_html_e('このグループには組織図がありません。','alumni-core'); ?></p><?php else: foreach($charts as $chart): ?><section class="alumni-org-chart-group-member"><h2><?php echo esc_html($chart['name']); ?></h2><?php echo Org_Chart_Shortcode::render_shortcode(array('id'=>$chart['chart_id'])); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core-generated shortcode HTML. ?></section><?php endforeach; endif; ?></div><?php return ob_get_clean();
	}
}
