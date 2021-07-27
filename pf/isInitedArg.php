<?php
/**
 * DokuWiki tplt 插件（动作模块） · DokuWiki plugin tplt (action component)
 *
 * @license	GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author	AlloyDome
 * 
 * @since	2.2.0, beta (------)
 * @version 2.2.0, beta (------)
 */

if(!defined('DOKU_INC'))
	die();	// 必须在 Dokuwiki 下运行 · Must be run within Dokuwiki

class tplt_parserfunc_isInitedArg {
	public function renderer($pfArgs, $incomingArgs, &$pageStack) {
		if (!array_key_exists(0, $pfArgs)) {
			return '';
		} else {
			if(array_key_exists($pfArgs[0], $incomingArgs)) {
				return (array_key_exists(1, $pfArgs)) ? $pfArgs[1] : '';
			} else {
				return (array_key_exists(2, $pfArgs)) ? $pfArgs[2] : '';
			}
		}
	}
}