<?php
/**
 * DokuWiki tplt 插件（动作模块） · DokuWiki plugin tplt (action component)
 *
 * @license	GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author	AlloyDome
 * 
 * @since	2.1.0, beta (210706)
 * @version 2.2.1, beta (------)
 */

use dokuwiki\lib\plugins\tplt\inc as inc;

if(!defined('DOKU_INC'))
	die();	// 必须在 Dokuwiki 下运行 · Must be run within Dokuwiki

require_once(DOKU_PLUGIN . 'tplt/inc/ParserUtils.php');
require_once(DOKU_PLUGIN . 'tplt/inc/StrposMap.php');
require_once(DOKU_PLUGIN . 'tplt/inc/PfList.php');

class action_plugin_tplt_wikitextpreprocess extends DokuWiki_Action_Plugin {
	// use inc\plugin_tplt_utils;	// 见 ../inc 文件夹内的 utils.php · see utils.php in ../inc folder

	/**
	 * register(Doku_Event_Handler $controller)
	 * 在 DokuWiki 事件控制器中注册插件相关的处理器 · Register its handlers with the DokuWiki's event controller
	 */
	public function register(Doku_Event_Handler $controller) {
		$controller->register_hook('PARSER_WIKITEXT_PREPROCESS', 'AFTER', $this, 'tpltTextReplace');
			// “PARSER_WIKITEXT_PREPROCESS” 事件见 parserutils.php 中的 p_get_instructions() 函数
			//  ·
			// For event "PARSER_WIKITEXT_PREPROCESS", see p_get_instructions() in parserutils.php
	}

	/**
	 * tpltTextReplace(Doku_Event &$event, $param)
	 * 将原始 Wiki 代码中的模板调用部分替换为模板本身内容 · Replace template calling in raw Wiki code by template contents 
	 * 
	 * @version	2.0.0, beta (210429)
	 * @since	2.0.0, beta (210429)
	 * 
	 * @author	AlloyDome
	 * 
	 * @param	Doku_Event	&$event	DokuWiki 事件类，$event 的 $data 变量就是原始 Wiki 代码
	 * 								 · 
	 * 								DokuWiki event class, the variable $data in $event is the raw Wiki code
	 * @param	mixed		$param	相关参数（暂时无用） · Parameters (useless now)
	 */
	public function tpltTextReplace(Doku_Event &$event, $param) {

		inc\PfList::pfLoad();

		$this->getConf('namespace');

		$text = $event->data;	// 原始 Wiki 代码
		$pageStack = array();	// 页面堆栈（注：根页面不应填入页面堆栈中）

		$strposMap = false;
		$text = inc\ParserUtils::tpltMainHandler($text, array(), $pageStack, $strposMap);

		inc\StrposMap::$strposMap = $strposMap; // 存储字符位置映射表

		
		$event->data = $text;
	}
} 