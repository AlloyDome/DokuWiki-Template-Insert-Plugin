<?php
/**
 * DokuWiki tplt 插件（动作模块） · DokuWiki plugin tplt (action component)
 *
 * @license	GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author	AlloyDome
 * 
 * @version 0.4.0 (2023-3-5)
 * @since	0.3.0 (2021-11-30)
 */

use dokuwiki\lib\plugins\tplt\inc\{PfAbstract, ParserUtils};

if(!defined('DOKU_INC'))
	die();	// 必须在 Dokuwiki 下运行 · Must be run within Dokuwiki

class tplt_parserfunc_isNonemptyArg extends PfAbstract {
	public function renderer(ParserUtils &$parser, $pfArgs, $incomingArgs,) {
		if (!array_key_exists('1', $pfArgs)) {
			return '';
		} else {
			if(array_key_exists($pfArgs['1'], $incomingArgs) && $incomingArgs[$pfArgs['1']] != '') {
				return (array_key_exists('2', $pfArgs)) ? $pfArgs['2'] : '';
			} else {
				return (array_key_exists('3', $pfArgs)) ? $pfArgs['3'] : '';
			}
		}
	}
}

/**
 * 功能：
 * 判断一个模板传入参数内容是否为空，如果非空，返回一段文本，否则返回另一段文本
 * 
 * 语法：
 * [|#isNonemptyArg|1=...|2=...|3=...|]
 * 
 * 参数：
 * 1：要判断的参数
 * 2：如果该传入参数非空时返回的文本
 * 3：如果该传入参数空白（或者未传入）时返回的文本
*/