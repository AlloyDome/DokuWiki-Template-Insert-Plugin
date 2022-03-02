<?php
/**
 * DokuWiki tplt 插件（动作模块） · DokuWiki plugin tplt (action component)
 *
 * @license	GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author	AlloyDome
 * 
 * @since	2.1.0, beta (210706)
 * @version 2.2.1, beta (------)
 */

use dokuwiki\lib\plugins\tplt\inc as inc;

if(!defined('DOKU_INC'))
	die();	// 必须在 Dokuwiki 下运行 · Must be run within Dokuwiki

require_once(DOKU_PLUGIN . 'tplt/inc/ParserUtils.php');
require_once(DOKU_PLUGIN . 'tplt/inc/StrposMap.php');

class action_plugin_tplt_renderercontentpostprocess extends DokuWiki_Action_Plugin {

	static private $secEditPattern = SEC_EDIT_PATTERN;
	private $strposMap;

	/**
	 * register(Doku_Event_Handler $controller)
	 * 在 DokuWiki 事件控制器中注册插件相关的处理器 · Register its handlers with the DokuWiki's event controller
	 */
	public function register(Doku_Event_Handler $controller) {
		$controller->register_hook('RENDERER_CONTENT_POSTPROCESS', 'AFTER', $this, 'sectionEditButtonCurrect');
			// “RENDERER_CONTENT_POSTPROCESS” 事件见 parserutils.php 中的 p_render() 函数
			//  ·
			// For event "RENDERER_CONTENT_POSTPROCESS", see p_render() in parserutils.php
	}

	/**
	 * sectionEditButtonCurrect(Doku_Event &$event, $param)
	 * 修正章节编辑按钮定位 · Correct the string range of section edit buttons 
	 * 
	 * @version	2.1.0, beta (210706)
	 * @since	2.1.0, beta (210706)
	 * 
	 * @author	AlloyDome
	 * 
	 * @param	Doku_Event	&$event	DokuWiki 事件类，$event 的 $data 变量就是输出类型和输出结果
	 * 								 · 
	 * 								DokuWiki event class, the variable $data in $event is the output format and document
	 * @param	mixed		$param	相关参数（暂时无用） · Parameters (useless now)
	 */
	public function sectionEditButtonCurrect(Doku_Event &$event, $param) {
		$this->strposMap = inc\StrposMap::$strposMap;

		$format = $event->data[0];	// 输出类型（如 XHTML、元数据等等） · Output format (eg. XHTML, metadata etc.)
		
		if ($format == 'xhtml') {
			$xhtml = $event->data[1];

			$xhtml = preg_replace_callback(
				self::$secEditPattern,
                'action_plugin_tplt_renderercontentpostprocess::rangeCorrectionCallback', 
				$xhtml
			);
		}

		$event->data[1] = $xhtml;
	}

	/**
	 * rangeCorrectionCallback($matches)
	 * 修正章节编辑按钮定位正则替换回调函数 · PCRE callback function of section edit button string range correction
	 * 
	 * @version	2.1.0, beta (210706)
	 * @since	2.1.0, beta (210706)
	 * 
	 * @author	AlloyDome
	 * 
	 * @param	array	$matches	匹配的编辑按钮标识 · Matched edit button patterns
	 */
	private function rangeCorrectionCallback($matches) {
		$json = htmlspecialchars_decode($matches[1], ENT_QUOTES);
		$data = json_decode($json, true);

		if (isset($data['range'])) {
			$data['range'] = $this->rangeCorrect($data['range']);
		}

		$json = json_encode($data);
		return '<!-- EDIT' . htmlspecialchars($json, ENT_QUOTES) . ' -->';
	}

	/**
	 * rangeCorrect($range)
	 * 
	 * @version	2.1.0, beta (210706)
	 * @since	2.1.0, beta (210706)
	 * 
	 * @author	AlloyDome
	 * 
	 * @param	string	$range	章节范围 · Section range
	 */
	private function rangeCorrect($range) {
		// TODO: 此函数内代码十分混乱，有待优化
		if ($this->strposMap == false)
			return $range;
		
		list($start, $end) = explode('-', $range);

		foreach ($this->strposMap as $key => $pos) {
			if ($start >= $pos['pro'][0] && $start <= $pos['pro'][1]) {
				$correctedStart = ((string) ((int) $start) - $pos['pro'][0] + $pos['ori'][0]);
				break;
			} elseif ($start > $pos['pro'][1]) {
				if ($key == array_key_last($this->strposMap)) {
					$correctedStart = ((string) ($pos['ori'][1] + 1));
				} elseif ($start < $this->strposMap[$key + 1]) {
					$correctedStart = ((string) ($pos['ori'][1] + 1));
				}
			}
		}

		foreach ($this->strposMap as $key => $pos) {
			if ($end >= $pos['pro'][0] && $end <= $pos['pro'][1]) {
				$correctedEnd = ((string) ((int) $end) - $pos['pro'][0] + $pos['ori'][0]);
				break;
			} elseif ($end > $pos['pro'][1]) {
				if ($key == array_key_last($this->strposMap)) {
					$correctedEnd = '';
				} elseif ($end < $this->strposMap[$key + 1]) {
					$correctedEnd = ((string) ($this->strposMap[$key + 1]['ori'][0] - 1));
				}
			}
		}

		return $correctedStart . '-' . $correctedEnd;
	}
}

