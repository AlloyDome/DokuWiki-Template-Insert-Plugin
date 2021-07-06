<?php
/**
 * DokuWiki tplt 插件（动作模块） · DokuWiki plugin tplt (action component)
 *
 * @license	GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author	AlloyDome
 * 
 * @since	2.1.0, beta (210706)
 * @version 2.1.0, beta (210706)
 */

namespace dokuwiki\lib\plugins\tplt\inc;

if(!defined('DOKU_INC'))
	die();	// 必须在 Dokuwiki 下运行 · Must be run within Dokuwiki

class plugin_tplt_strposMap {
	public static $strposMap = false;	// 用于存储模板替换后的字符位置映射表（该映射表用于修正章节编辑按钮的定位）
										//  ·
										// To save string position mapping chart after template replacement
										// (this chart is used to currect the text ranges of section edit buttons)
}