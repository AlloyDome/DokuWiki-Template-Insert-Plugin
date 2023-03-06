<?php
/**
 * DokuWiki plugin tplt (action component, parser function abstract class) · DokuWiki tplt 插件（动作模块，解析器函数抽象类）
 *
 * @license	GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author	AlloyDome
 * 
 * @version 0.4.0 (2023-3-5)
 * @since	0.4.0 (2023-3-5)
 */

namespace dokuwiki\lib\plugins\tplt\inc;

if(!defined('DOKU_INC'))
	die();	// Must be run within Dokuwiki · 必须在 Dokuwiki 下运行

abstract class PfAbstract {
	abstract public function renderer(ParserUtils &$parser, $pfArgs, $incomingArgs);
}