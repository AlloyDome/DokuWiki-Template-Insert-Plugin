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

namespace dokuwiki\lib\plugins\tplt\inc;

if(!defined('DOKU_INC'))
	die();	// 必须在 Dokuwiki 下运行 · Must be run within Dokuwiki

	
if(!defined('DOKU_PLUGIN_TPLT_PF'))
	define('DOKU_PLUGIN_TPLT_PF', DOKU_PLUGIN . 'tplt/pf/');	// 解析器函数路径 · Path of parser function files

class PfList {
	public static $pfClassList = array();
	// 解析器函数类的列表，其中类名的格式为：“tplt_parserfunc_×××”
	//  ·
	// List of classes of parser functions, where the format of class names is "tplt_parserfunc_***"

	public static function pfLoad() {
		if ($dh = @opendir(DOKU_PLUGIN_TPLT_PF)) {
			while (false !== ($pfFileName = readdir($dh))) {
				if ($pfFileName[0] === '.')
					continue;	// 跳过“.”、“..”等 · Skip ".", "..", etc.
				if (!is_file(DOKU_PLUGIN_TPLT_PF . $pfFileName))
					continue;	// 文件不存在则跳过 · Skip if file does not exist
				if (array_key_exists($pfName = rtrim($pfFileName, '.php'), self::$pfClassList)) {
					continue;	// 已经加载过的文件不再重复加载 · Files have been loaded should not be reloaded
				} else {
					require_once(DOKU_PLUGIN_TPLT_PF . $pfFileName);	// 加载尚未加载的文件 · Load files which have been loaded yet
					$class = 'tplt_parserfunc_' . $pfName;	// 对应的类名 · Name of the corresponding class
					if(class_exists($class, true)) {
						self::$pfClassList[$pfName] = new $class;
						// 如果文件中确实存在相应的类，则载入列表中 · Add the class in the list if the class does exist
					}
				}
			}
		}
	}
}