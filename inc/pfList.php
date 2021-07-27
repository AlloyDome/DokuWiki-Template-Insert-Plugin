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

namespace dokuwiki\lib\plugins\tplt\inc;

if(!defined('DOKU_INC'))
	die();	// 必须在 Dokuwiki 下运行 · Must be run within Dokuwiki

	
if(!defined('DOKU_PLUGIN_TPLT_PF'))
	define('DOKU_PLUGIN_TPLT_PF', DOKU_PLUGIN . 'tplt/pf/');	// 解析器函数路径

class pfList {
	public static $pfClassList = array();

	public static function pfLoad() {
		if ($dh = @opendir(DOKU_PLUGIN_TPLT_PF)) {
			while (false !== ($pfFileName = readdir($dh))) {
				if ($pfFileName[0] === '.')
					continue;
				if (!is_file(DOKU_PLUGIN_TPLT_PF . $pfFileName))
					continue;
				if (array_key_exists($pfName = rtrim($pfFileName, '.php'), self::$pfClassList)) {
					continue;
				} else {
					require_once(DOKU_PLUGIN_TPLT_PF . $pfFileName);
					$class = 'tplt_parserfunc_' . $pfName;
					if(class_exists($class, true)) {
						self::$pfClassList[$pfName] = new $class;
					}
				}
			}
		}
	}
}