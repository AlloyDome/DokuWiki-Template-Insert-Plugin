<?php
/**
 * DokuWiki tplt 插件（动作模块） · DokuWiki plugin tplt (action component)
 *
 * @license	GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author	AlloyDome
 * 
 * @version 0.3.1 (2021-11-30)
 * @since	0.3.0 (2021-11-30)
 */

namespace dokuwiki\lib\plugins\tplt\inc;

use dokuwiki\Extension as ext;

if(!defined('DOKU_INC'))
	die();	// Must be run within Dokuwiki · 必须在 Dokuwiki 下运行

class inc_plugin_tplt extends ext\Plugin {
	// This class is currently only for getting configuration parameters of plugin tplt, and has no other usages
	//  · 
	// 此类目前只用于获取 tplt 插件的配置参数，此外别无他用

	/* 
	
	How to use: · 用法：

		use dokuwiki\lib\plugins\tplt\inc as inc;
		require_once(DOKU_PLUGIN . 'tplt/inc/pluginClass.php');
		$... = (new inc\inc_plugin_tplt())->getConf('...');

	*/
}