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

if(!defined('DOKU_INC'))
	die();	// 必须在 Dokuwiki 下运行 · Must be run within Dokuwiki

class tplt_parserfunc_example {
	public function renderer($pfArgs, $incomingArgs, &$pageStack) {
		return 'Here\' s an example. ';
	}
}