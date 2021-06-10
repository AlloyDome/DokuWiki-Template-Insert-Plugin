<?php
/**
 * DokuWiki tplt 插件（动作模块） · DokuWiki plugin tplt (action component)
 *
 * @license	GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author	AlloyDome
 * 
 * @version 2.1.0, beta (------)
 */

if(!defined('DOKU_INC'))
	die();	// 必须在 Dokuwiki 下运行 · Must be run within Dokuwiki

require_once(DOKU_PLUGIN . 'tplt/inc/utils.php');

class action_plugin_tplt_renderercontentpostprocess extends DokuWiki_Action_Plugin {
	use plugin_tplt_utils;

	/**
	 * register(Doku_Event_Handler $controller)
	 * 在 DokuWiki 事件控制器中注册插件相关的处理器 · Register its handlers with the DokuWiki's event controller
	 */
	public function register(Doku_Event_Handler $controller) {
		$controller->register_hook('RENDERER_CONTENT_POSTPROCESS', 'AFTER', $this, 'sectionEditButtonCurrect');
			// “RENDERER_CONTENT_POSTPROCESS” 事件见 parserutils.php 中的 p_render() 函数
			//  ·
			// For event "RENDERER_CONTENT_POSTPROCESS", see p_render() in parserutils.php
	}

	/**
	 * sectionEditButtonCurrect(Doku_Event &$event, $param)
	 * 修正章节编辑按钮的定位 · Currect the string range of section edit buttons 
	 * 
	 * @version	2.1.0, beta (------)
	 * @since	2.1.0, beta (------)
	 * 
	 * @author	AlloyDome
	 * 
	 * @param	Doku_Event	&$event	DokuWiki 事件类，$event 的 $data 变量就是原始 Wiki 代码
	 * 								 · 
	 * 								DokuWiki event class, the variable $data in $event is the raw Wiki code
	 * @param	mixed		$param	相关参数（暂时无用） · Parameters (useless now)
	 */
	public function sectionEditButtonCurrect(Doku_Event &$event, $param) {
		$format = $event->data[0];
		
		if ($format == 'xhtml') {
			$xhtml = $event->data[1];
			$xhtml .= '<p><strong>HAHAHA</strong></p>';

			$event->data[1] = $xhtml;
		}
	}
} 