<?php
/**
 * DokuWiki tplt 插件（动作模块） · DokuWiki plugin tplt (action component)
 *
 * @license	GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author	AlloyDome
 * 
 * @since	2.2.0, beta (211130)
 * @version 2.2.0, beta (211130)
 */

namespace dokuwiki\lib\plugins\tplt\inc;

use dokuwiki\Extension as ext;

if(!defined('DOKU_INC'))
	die();	// 必须在 Dokuwiki 下运行 · Must be run within Dokuwiki

class inc_plugin_tplt extends ext\Plugin {
	// 此类目前只用于获取 tplt 插件的配置参数，此外别无他用
	//  · 
	// This class is currently only for getting configuration parameters of plugin tplt, and has no other usages

	/* 
	
	用法： · How to use:

		use dokuwiki\lib\plugins\tplt\inc as inc;
		require_once(DOKU_PLUGIN . 'tplt/inc/pluginClass.php');
		$obj = new inc\inc_plugin_tplt();
		$obj->getConf('...');
		unset($obj);

	*/
}