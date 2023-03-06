<?php
/**
 * DokuWiki plugin tplt (action component, string position mapping chart) · DokuWiki tplt 插件（动作模块，字符位置映射表）
 *
 * @license	GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author	AlloyDome
 * 
 * @version 0.4.0 (2023-3-5)
 * @since	0.2.0 (2021-7-6)
 */

namespace dokuwiki\lib\plugins\tplt\inc;

if(!defined('DOKU_INC'))
	die();	// 必须在 Dokuwiki 下运行 · Must be run within Dokuwiki

class StrposMap {
	public const STRPOSMAP_ENTRY_PATTERN_WIKITEXT = "\x7f~~STRPOSMAP~~\x7f";
	public const STRPOSMAP_EXIT_PATTERN_WIKITEXT  = "\x7f~~ENDSTRPOSMAP~~\x7f";
	public const STRPOSMAP_ENTRY_PATTERN_XHTML    = '&#127;<!-- STRPOSMAP[';
	public const STRPOSMAP_EXIT_PATTERN_XHTML     = '] -->&#127;';
}