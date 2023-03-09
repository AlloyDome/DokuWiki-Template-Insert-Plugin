<?php
/**
 * DokuWiki plugin tplt (action component, renderer content postprocess) · DokuWiki tplt 插件（动作模块，渲染内容后处理）
 *
 * @license	GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author	AlloyDome
 * 
 * @version 0.4.1 (2023-3-7)
 * @since	0.2.0 (2021-7-6)
 */

# use dokuwiki\lib\plugins\tplt\inc\StrposMap;

if(!defined('DOKU_INC'))
	die();	// 必须在 Dokuwiki 下运行 · Must be run within Dokuwiki

# require_once(DOKU_PLUGIN . 'tplt/inc/ParserUtils.php');
# require_once(DOKU_PLUGIN . 'tplt/inc/StrposMap.php');

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
	 * @version	0.4.1 (2023-3-7)
	 * @since	0.2.0 (2021-7-6)
	 * 
	 * @author	AlloyDome
	 * 
	 * @param	Doku_Event	&$event	DokuWiki 事件类，$event 的 $data 变量就是输出类型和输出结果
	 * 								 · 
	 * 								DokuWiki event class, the variable $data in $event is the output format and document
	 * @param	mixed		$param	相关参数（暂时无用） · Parameters (useless now)
	 */
	public function sectionEditButtonCurrect(Doku_Event &$event, $param) {

		$format = $event->data[0];	// 输出类型（如 XHTML、元数据等等） · Output format (eg. XHTML, metadata etc.)
		
		if ($format == 'xhtml') {
			$xhtml = $event->data[1];
			
			$matches = array();
			preg_match("/&#127;<!-- STRPOSMAP\[.*?\] -->&#127;\n/", $xhtml, $matches);
			$xhtml = preg_replace("/&#127;<!-- STRPOSMAP\[.*?\] -->&#127;\n/", '', $xhtml);
			$xhtml = preg_replace("/(\x7f|&#127;)/", '&nbsp;', $xhtml);
			if (!array_key_exists(0, $matches)) {
				$event->data[1] = $xhtml;	// Do not forget this!
				return;
			}
			$strposMap = $matches[0];
			unset($matches);

			$this->strposMap = unserialize(
				html_entity_decode(
					substr($strposMap, 
						21, 	// &#127;<!-- STRPOSMAP[
						-12		// ] -->&#127;\n
					), 
					ENT_QUOTES
				)
			);
			unset($strposMap);

			if (!empty($this->strposMap['istplt']) && !empty($this->strposMap['ori']) && !empty($this->strposMap['pro'])) {
				$xhtml = preg_replace_callback(
					self::$secEditPattern,
					'action_plugin_tplt_renderercontentpostprocess::rangeCorrectionCallback', 
					$xhtml
				);
			}
		}

		$event->data[1] = $xhtml;
	}

	/**
	 * rangeCorrectionCallback($matches)
	 * 修正章节编辑按钮定位正则替换回调函数 · PCRE callback function of section edit button string range correction
	 * 
	 * @version	0.2.0 (2021-7-6)
	 * @since	0.2.0 (2021-7-6)
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
	 * @version	0.4.0 (2023-3-5)
	 * @since	0.2.0 (2021-7-6)
	 * 
	 * @author	AlloyDome
	 * 
	 * @param	string	$range	章节范围 · Section range
	 */
	private function rangeCorrect($range) {
		if ($this->strposMap == false)
			return $range;
		
		list($start, $end) = explode('-', $range);

		$start = ($start != '') ? (int)$start : false;
		$end = ($end != '') ? (int)$end : false;

		$correctedStart = $this->positionCorrect($start, $this->strposMap, true);
		$correctedEnd = $this->positionCorrect($end, $this->strposMap, false);

		return $correctedStart . '-' . $correctedEnd;
	}

	/**
	 * positionCorrect($pos, &$map, $isStart)
	 * 
	 * @version	0.4.0 (2023-3-5)
	 * @since	0.4.0 (2023-3-5)
	 * 
	 * @author	AlloyDome
	 */
	private function positionCorrect($pos, &$map, $isStart) {
		$mapItem = count($map['istplt']);

		if (is_int($pos)) {
			$pos = ($pos < 0) ? 0 : $pos;

			for ($i = 0; $i < $mapItem && $pos >= $map['pro'][$i]; $i++);
			$i--;
			
			if ($map['istplt'][$i]) {
				if ($isStart) {
					return (string)$map['ori'][$i];
				} elseif ($i < $mapItem - 1) {
					return (string)($map['ori'][$i + 1] - 1);
				} else {
					return '';
				}
			}
			// Note: The last element of $map['ori/pro'] is the total length of the text. A section range mapping table 
			//       (\x7f~~STRPOSMAP~~\x7f...\x7f~~ENDSTRPOSMAP~~\x7f) is attached to the end of the text after expanding 
			//       templates etc. The text range of the last section edit button should only have an start position, and 
			//       the end position is kept empty (e.g. "123 - "), but uncorrected range of the last section may have an
			//       unempty end position because of the mapping table. If an end position is over than the last element 
			//       of $map['pro'], we consider it's in the range of string of the mapping table and it should be set as 
			//       empty.
			//  · 
			// 注：$map['ori/pro'] 的最后一个元素存储的是整个文本的总长度。在展开模板等内容之后，程序会在文本最后附一个章节
			//     编辑范围对应表（␡~~STRPOSMAP~~␡...␡~~ENDSTRPOSMAP~~␡）。最后一个章节编辑按钮的范围应当只有一个开始位
			//     置，结束位置为空（例如“123 - ”）。但因为编辑范围对应表的存在，范围未经修正时，结束位置可能不为空。如果结
			//     束位置超出了 $map['pro'] 的最后一个元素，则认为该位置位于范围对应表的文本内，应当设置为空。
			elseif ($i < $mapItem) {
				return (string)($pos - $map['pro'][$i] + $map['ori'][$i]);
			} elseif (!$isStart) {
				return '';
			} else {
				return (string)($map['ori'][$mapItem] - 1);
			}
		} else {
			return '';
		}
	}
}

