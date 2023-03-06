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

class tplt_parserfunc_example extends PfAbstract {
	public function renderer(ParserUtils &$parser, $pfArgs, $incomingArgs) {
		return 'Example parser function, for test only. ;-)';
	}
}

/**
 * 功能：
 * 输出 “Example parser function, for test only. ;-)”
 * 
 * 语法：
 * [|#example|]
 * 
 * 参数：
 * 没有参数
*/