<?php
/**
 * DokuWiki tplt 插件（动作模块） · DokuWiki plugin tplt (action component)
 *
 * @license	GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author	AlloyDome
 * 
 * @since	2.2.0, beta (211130)
 * @version 2.2.1, beta (------)
 */

use dokuwiki\lib\plugins\tplt\inc as inc;

if(!defined('DOKU_INC'))
	die();	// 必须在 Dokuwiki 下运行 · Must be run within Dokuwiki

require_once(DOKU_PLUGIN . 'tplt/inc/ParserUtils.php');

class tplt_parserfunc_isInitedArg {
	public function renderer($pfArgs, $incomingArgs, &$pageStack) {
		if (!array_key_exists(0, $pfArgs)) {
			return '';
		} else {
			if(array_key_exists($pfArgs[0], $incomingArgs)) {
				return (array_key_exists(1, $pfArgs)) ? inc\ParserUtils::tpltMainHandler($pfArgs[1], $incomingArgs, $pageStack) : '';
			} else {
				return (array_key_exists(2, $pfArgs)) ? inc\ParserUtils::tpltMainHandler($pfArgs[2], $incomingArgs, $pageStack) : '';
			}
		}
	}
}