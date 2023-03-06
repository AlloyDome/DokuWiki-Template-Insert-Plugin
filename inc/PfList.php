<?php
/**
 * DokuWiki plugin tplt (action component, parser function list) · DokuWiki tplt 插件（动作模块，解析器函数列表）
 *
 * @license	GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author	AlloyDome
 * 
 * @version 0.3.1 (2021-11-30)
 * @since	0.3.0 (2021-11-30)
 */

namespace dokuwiki\lib\plugins\tplt\inc;

if(!defined('DOKU_INC'))
	die();	// Must be run within Dokuwiki · 必须在 Dokuwiki 下运行

	
if(!defined('DOKU_PLUGIN_TPLT_PF'))
	define('DOKU_PLUGIN_TPLT_PF', DOKU_PLUGIN . 'tplt/pf/');	// Path of parser function files · 解析器函数路径

class PfList {
	public static $pfClassList = array();

	// List of classes of parser functions, where the format of class names is "tplt_parserfunc_***"
	//  ·
	// 解析器函数类的列表，其中类名的格式为：“tplt_parserfunc_×××”
	public static function pfLoad() {
		if ($dh = @opendir(DOKU_PLUGIN_TPLT_PF)) {
			while (false !== ($pfFileName = readdir($dh))) {

				// Skip ".", "..", etc. · 跳过 “.”、“..” 等
				if ($pfFileName[0] === '.')
					continue;

				// Skip if file does not exist · 文件不存在则跳过
				if (!is_file(DOKU_PLUGIN_TPLT_PF . $pfFileName))
					continue;

				// Files have been loaded should not be reloaded · 已经加载过的文件不再重复加载
				if (array_key_exists($pfName = rtrim($pfFileName, '.php'), self::$pfClassList)) {
					continue;	
				} else {
					require_once(DOKU_PLUGIN_TPLT_PF . $pfFileName);	// Load files which have been loaded yet · 加载尚未加载的文件
					$class = 'tplt_parserfunc_' . $pfName;	// Name of the corresponding class名 · 对应的类
					if(class_exists($class, true)) {
						self::$pfClassList[$pfName] = new $class;
						// Add the class in the list if the class does exist · 如果文件中确实存在相应的类，则载入列表中
					}
				}
			}
		}
	}
}