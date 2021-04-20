<?php
/**
 * DokuWiki tplt 插件（动作模块） · DokuWiki plugin tplt (action component)
 *
 * @license	GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author	AlloyDome
 * 
 * @version 2.0 (------)
 */

/*
 * 目前已知的 bug：
 * 暂时取消了模板递归检查，可能会导致死循环。
 */

use dokuwiki\Parsing\Parser;

if(!defined('DOKU_INC'))
	die();	// 必须在 Dokuwiki 下运行 · Must be run within Dokuwiki

class action_plugin_tplt extends DokuWiki_Action_Plugin {
	/**
	 * register(Doku_Event_Handler $controller)
	 * 在 DokuWiki 事件控制器种注册插件相关的处理器 · Register its handlers with the DokuWiki's event controller
	 */
	public function register(Doku_Event_Handler $controller) {
		$controller->register_hook('PARSER_WIKITEXT_PREPROCESS', 'AFTER', $this, 'tpltTextReplace');
			// “PARSER_WIKITEXT_PREPROCESS” 事件见 parserutils.php 中的 p_get_instructions() 函数
			//  ·
			// For event "PARSER_WIKITEXT_PREPROCESS", see p_get_instructions() in parserutils.php
	}

	/**
	 * tpltTextReplace(Doku_Event &$event, $param)
	 * 将原始 Wiki 代码中的模板调用部分替换为模板本身内容 · Replace template calling in raw Wiki code by template contents 
	 * 
	 * @version	2.0 (------)
	 * @since	2.0 (------)
	 * 
	 * @author	AlloyDome
	 * 
	 * @param	Doku_Event	&$event	DokuWiki 事件类，$event 的 $data 变量就是原始 Wiki 代码
	 * 								 · 
	 * 								DokuWiki event class, the variable $data in $event is the raw Wiki code
	 * @param	mixed		$param	相关参数（暂时无用） · Parameters (useless now)
	 */
	public function tpltTextReplace(Doku_Event &$event, $param) {
		$text = $event->data;	// 原始 Wiki 代码

		$text = $this->tpltMainHandler($text);

		// 解析完了以后把模板内容都替换掉
		$event->data = $text;
	}

	/**
	 * tpltMainHandler($text)
	 * 主处理函数
	 * 
	 * @version	2.0 (------)
	 * @since	2.0 (------)
	 * 
	 * @author	AlloyDome
	 * 
	 */
	private function tpltMainHandler($text, $args = array()) {
		$instructions = $this->tpltParser($text);
		$this->replaceArgs($instructions, $args);
		$this->replaceTplts($instructions);
		return $this->textMerge($instructions);
	}

	/**
	 * tpltParser($text)
	 * 原始 Wiki 代码解析器 · Parser of the raw Wiki code
	 * 
	 * @version	2.0 (------)
	 * @since	2.0 (------)
	 * 
	 * @author	AlloyDome
	 * 
	 * @param	string	$text			原始 Wiki 代码 · raw Wiki code
	 * 
	 * @return	array					解析后的标示片段 · Instructions from parsing
	 */
	private function tpltParser($text) {
		$patternArray = $this->getPatterns();
		
		$reducedText = $text;
		$scanPosition = 0;
		$instructions = array();

		$nestLevel = array();
		foreach ($patternArray as $patternGroup) {
			$groupNestLevel = array();
			foreach ($patternGroup['patterns'] as $pattern)
				$groupNestLevel[] = 0;
			$nestLevel[$patternGroup['name']] = $groupNestLevel;
		};
		$nestStack = array();

		$currentNest = false;
		$currentNestPatternOdr = false;
		while ($reducedText != '') {
			$firstPosOfPatterns = $this->findFirstPosOfPatterns($reducedText, $patternArray, $nestLevel, $currentNest, $currentNestPatternOdr);
			
			if (empty($firstPosOfPatterns)) {
				$instructions[] = array('type' => 'plainText', 'text' => $reducedText);
				break;
			} else {
				ksort($firstPosOfPatterns);
				$firstPattern = array('position' => key($firstPosOfPatterns), 'pattern' => reset($firstPosOfPatterns));
				$unmachedTextBeforeTheFirstPattern = substr($reducedText, 0, $firstPattern['position']);

				if ($unmachedTextBeforeTheFirstPattern !== '') {
					$instructions[] = array('type' => 'plainText', 'text' => $unmachedTextBeforeTheFirstPattern);
				}
				$instructions[] = array(
					'type' => $firstPattern['pattern']['name'], 
					'startOrEnd' => $firstPattern['pattern']['startOrEnd'], 
					'text' => $firstPattern['pattern']['matchedPattern']);
				
				if ($firstPattern['pattern']['startOrEnd'] == 'start') {
					$nestStack[] = array(
						'pattern' => $firstPattern['pattern']['name'],
						'orderNo' => $firstPattern['pattern']['orderNo']
					);
					$nestLevel[$firstPattern['pattern']['name']][$firstPattern['pattern']['orderNo']] += 1;
				} elseif ($firstPattern['pattern']['startOrEnd'] == 'end') {
					array_pop($nestStack);
					$nestLevel[$firstPattern['pattern']['name']][$firstPattern['pattern']['orderNo']] -= 1;
				}
				if (empty($nestStack))
				{
					$currentNest = false;
					$currentNestPatternOdr = false;
				} else {
					list('pattern' => $currentNest, 'orderNo' => $currentNestPatternOdr) = end($nestStack);
				}

				$reducedText = substr($reducedText, $firstPattern['position'] + strlen($firstPattern['pattern']['matchedPattern']));

			}
		}
		return $instructions;
	}

	private function replaceArgs(&$instructions, $args) {
		foreach ($instructions as $key => $instruction) {
			if ($instruction['type'] == 'arg' && $instruction['startOrEnd'] = 'end') {
				// 注：请考虑带默认值的参数
				if (array_key_exists($instructions[$key - 1]['text'], $args) && $instructions[$key - 2]['text'] == '{{{') {
					$args[$instructions[$key - 1]['text']];
					$instructions[$key - 1]['text'] = $args[$instructions[$key - 1]['text']];
					unset($instructions[$key]);
					unset($instructions[$key - 2]);
				}
			}
		}
		$instructions = array_values($instructions);
	}

	private function replaceTplts(&$instructions) {
		$nestLevel = 0;
		foreach ($instructions as $key => $instruction) {
			if ($instruction['type'] == 'tplt') {
				if ($instruction['startOrEnd'] == 'start') {
					$nestLevel += 1;
					if ($nestLevel == 1) {
						$startOrderNo = $key;
					}
				} elseif ($instruction['startOrEnd'] == 'end') {
					$nestLevel -= 1;
					if ($nestLevel == 0) {
						$matechedTpltNameAndArgs = '';
						for ($i = $startOrderNo + 1; $i <= $key - 1; $i++) {
							$matechedTpltNameAndArgs .= $instructions[$i]['text'];
						}
					}
					$tpltText = $this->tpltRendener($matechedTpltNameAndArgs);
					for ($i = $startOrderNo; $i <= $key - 1; $i++) {
						unset($instructions[$i]);
					}
					unset($instructions[$key]['startOrEnd']);
					$instructions[$key]['type'] = 'plainText';
					$instructions[$key]['text'] = $tpltText;
				}
			}
		}
		$instructions = array_values($instructions);
	}

	private function textMerge($instructions) {
		$text = '';
		foreach ($instructions as $instruction) {
			$text .= $instruction['text'];
		}
		return $text;
	}

	// ----------------------------------------------------------------

	private function getPatterns()
	{
		$patternArray = array(
			array(
				'name' => 'noTplt',
				'allowSelfNest' => false,
				# 'allowCrossNest' => false,
				'allowEnterFrom' => array('tplt'),
				'patterns' => array(
					array('start' => '%%', 			'end' => '%%', 			'isPcre' => false),
					array('start' => '<nowiki>', 	'end' => '</nowiki>', 	'isPcre' => false),
					array('start' => '<html>', 		'end' => '</html>', 	'isPcre' => false),
					array('start' => '<HTML>', 		'end' => '</HTML>', 	'isPcre' => false),
					array('start' => '<php>', 		'end' => '</php>', 		'isPcre' => false),
					array('start' => '<PHP>', 		'end' => '</PHP>', 		'isPcre' => false),
					array('start' => '/<code>/', 	'end' => '/<\/code>/', 	'isPcre' => true),
					array('start' => '/<file>/', 	'end' => '/<\/file>/', 	'isPcre' => true),
				)
			),
			array(
				'name' => 'tplt',
				'allowSelfNest' => true,
				# 'allowCrossNest' => false,
				'allowEnterFrom' => array(),
				'patterns' => array(
					array('start' => '[[[', 		'end' => ']]]', 		'isPcre' => false)
				)
			),
			array(
				'name' => 'arg',
				'allowSelfNest' => false,
				# 'allowCrossNest' => false,
				'allowInside' => array('tplt'),
				'patterns' => array(
					array('start' => '{{{', 		'end' => '}}}', 		'isPcre' => false)
				)
			),
		);

		return $patternArray;
	}

	/**
	 * tpltParser($text)
	 * 寻找各标识第一次出现的位置 · Find the first positions of patterns
	 * 
	 * @version	2.0 (------)
	 * @since	2.0 (------)
	 * 
	 * @author	AlloyDome
	 * 
	 * @param	string	$text				原始 Wiki 代码 · raw Wiki code
	 * @param	array	$patternArray		标识字符串的存储序列 · Array of patterns
	 * @param	array	$modesOfStartAndEnd	开始与结束模式 · Modes of start and end
	 * 
	 * @return	array						各标识第一次出现的位置 · The first positions of patterns
	 */
	private function findFirstPosOfPatterns($text, $patternArray, $nestLevel, $currentNest, $currentNestPatternOdr) {
		$outsideNest = $this->outsideNestDetect($nestLevel);

		$firstPosOfPatterns = array();
		foreach ($patternArray as $patternGroup) {
			$patternGroupName = $patternGroup['name'];
			$allowSelfNest = $patternGroup['allowSelfNest'];
			# $allowCrossNest = $patternGroup['allowCrossNest'];

			foreach ($patternGroup['patterns'] as $orderNo => $pattern) {
				if ($currentNest == $patternGroupName) {
					// 位于自身嵌套内
					if ($allowSelfNest == false) {
						// 不允许自嵌套
						if ($orderNo == $currentNestPatternOdr) {
							// 位于自身嵌套内且开始标记为当前所循环到的标记，则只匹配其对应的结束标记，否则什么也不做
							list($patternPos, $matchedText) = $this->patternMatch($text, $pattern['end'], $pattern['isPcre']);
							if ($patternPos !== false) {
								$firstPosOfPatterns[$patternPos] = array(
									'name' => $patternGroupName, 
									'orderNo' => $orderNo, 
									'startOrEnd' => 'end', 
									'matchedPattern' => $matchedText);
							}
						}
					} else {
						// 允许自嵌套，正常匹配开始与结束标记
						if ($orderNo == $currentNestPatternOdr) {
							// 位于自身嵌套内且开始标记为当前所循环到的标记，正常匹配开始与结束标记
							list($patternPos1, $matchedText1) = $this->patternMatch($text, $pattern['start'], $pattern['isPcre']);
							list($patternPos2, $matchedText2) = $this->patternMatch($text, $pattern['end'], $pattern['isPcre']);
							// 注：这里用了穷举的方法，可能会有更好的写法
							if ($patternPos1 === false && $patternPos2 === false) {
								$patternPos = false;
							} elseif ($patternPos1 === false && $patternPos2 !== false) {
								$patternPos = $patternPos2;
								$matchedText = $matchedText2;
								$startOrEnd = 'end';
							} elseif ($patternPos1 !== false && $patternPos2 === false) {
								$patternPos = $patternPos1;
								$matchedText = $matchedText1;
								$startOrEnd = 'start';
							} else {
								$patternPos = min($patternPos1, $patternPos2);
								$matchedText = ($patternPos2 <= $patternPos1) ? $matchedText2 : $matchedText1;
								$startOrEnd = ($patternPos2 <= $patternPos1) ? 'end' : 'start';
							}
							if ($patternPos !== false) {
								$firstPosOfPatterns[$patternPos] = array(
									'name' => $patternGroupName, 
									'orderNo' => $orderNo, 
									'startOrEnd' => $startOrEnd, 
									'matchedPattern' => $matchedText);
							}
						} else {
							// 位于自身嵌套内但开始标记不是当前所循环到的标记，只匹配其开始标记
							list($patternPos, $matchedText) = $this->patternMatch($text, $pattern['start'], $pattern['isPcre']);
							if ($patternPos !== false) {
								$firstPosOfPatterns[$patternPos] = array(
									'name' => $patternGroupName, 
									'orderNo' => $orderNo, 
									'startOrEnd' => 'start', 
									'matchedPattern' => $matchedText);
							}
						}
					}
				} else {
					// 没有位于自身嵌套内（可能位于其他嵌套内部），则匹配开始标记
					if ($currentNest === false) {
						// 没有位于任何嵌套内
						list($patternPos, $matchedText) = $this->patternMatch($text, $pattern['start'], $pattern['isPcre']);
						if ($patternPos !== false) {
							$firstPosOfPatterns[$patternPos] = array(
								'name' => $patternGroupName, 
								'orderNo' => $orderNo, 
								'startOrEnd' => 'start', 
								'matchedPattern' => $matchedText);
						}
					} else {
						// 位于其他嵌套内
						if (!empty($patternGroup['allowEnterFrom']) && in_array($currentNest, $patternGroup['allowEnterFrom'])) {
							// 如果该标识允许包含在上一级嵌套内，则匹配开始标记，否则什么也不做
							list($patternPos, $matchedText) = $this->patternMatch($text, $pattern['start'], $pattern['isPcre']);
							if ($patternPos !== false) {
								$firstPosOfPatterns[$patternPos] = array(
									'name' => $patternGroupName, 
									'orderNo' => $orderNo, 
									'startOrEnd' => 'start', 
									'matchedPattern' => $matchedText);
							}
						}
					}
				}
			}
		}
		return $firstPosOfPatterns;
	}

	/**
	 * 查找标识的位置及匹配片段
	 */
	private function patternMatch($text, $pattern, $isPcre) {
		if ($isPcre == false) {
			$findResult = strpos($text, $pattern);
			if ($findResult !== false) {
				return array($findResult, $pattern);
			}
		} elseif ($isPcre == true) {
			$findResult = preg_match($pattern, $text, $match);
			if ($findResult != 0) {
				return array($match[0], $match[1]);
			}
		}
		return array(false, false);
	}

	/**
	 * 检测是否处于嵌套外边
	 */
	private function outsideNestDetect($nestLevel) {
		foreach ($nestLevel as $groupNestLevel) {
			if ($this->groupNestDetect($groupNestLevel) == true) {
				return false;
			}
		}
		return true;
	}

	/**
	 * 检测是否在某个嵌套里面
	 */
	private function groupNestDetect($groupNestLevel){
		foreach ($groupNestLevel as $eachNestLevel) {
			if ($eachNestLevel > 0) {
				return true;
			}
		}
		return false;
	}

	// ----------------------------------------------------------------

	private function tpltRendener($rawText) {
		if (!$rawText)
			return '';	// 如果传入一个空字符串，则返回 false · Return false if the incoming string is empty

		$template_arguments = array();	// 存储参数值的数组 · Array for values of arguments
		$dump = $rawText;
		$dump = preg_replace_callback('/\{\{(((?!(\{\{|\}\})).*?|(?R))*)\}\}/', function($data) {return str_replace('|', '~~!~~', $data[0]);}, $dump);

		$dump = $this->getTemplateName($dump);	// 模板名与各参数 · Template name and arguments
		$templateName = $dump[0];	// 模板名 · template name

		# if (!$this->recursionCheck($templateName))	// 递归检查 · recursion check
			# return $this->getLang('selfCallingBegin') . $templateName . $this->getLang('selfCallingEnd');
		
		$dump = $dump[1];
		# $dump = addcslashes($dump[1], '\\');	// 各参数 · All arguments
			// 注：传入的各参数值需要在反斜杠前再加一个反斜杠，以防止发生转义
			//  ·
			// Note: Need to add backslash before backslashes in incoming argument values to prevent escaping
		$template_arguments = array();
		if ($dump)
		{
			$dump = explode('|', $dump);
			foreach ($dump as $key => $value) {
				// 如果声明了参数名，或者以 “=” 开头 · If argument name has been defined or starts with "="
				if (strpos($value, '=') !== false)
				{
					$tmp = explode('=', $value, 2);
					$template_arguments[(trim($tmp[0] != '')) ? trim($tmp[0]) : ($key + 1)] = trim($tmp[1]);
				}
				// 如果没声明参数名 · If argument name is not defined
				// 则编号，从 1 开始，不是从 0 开始 · Start from 1, not 0
				else $template_arguments[$key + 1] = trim($value);
			}
		}
		$template_arguments = str_replace('~~!~~', '|', $template_arguments);
			// 将参数值中用于替代竖杠符号的“{{!}}”再替换回来
			//  ·
			// Restore the vertical line characters replaced by "{{!}}" in argument values before
		$template = $this->get_template($templateName);
		if (!$template) return;

		$renderedText = $this->tpltMainHandler($template, $template_arguments);
		return $renderedText;
	}

	private function getTemplateName($match)
	{
		$dump = $match;
		
		switch (strpos($dump, '|'))
		{
			case false:
				$templateName = $dump;
				$allArguments = '';
					// 如果没有竖线，则将整个 “$dump” 作为模板名
					//  · 
					// If there is no vertical bar, regard whole "$dump" as template name
				break;
			case true:
				$templateName = substr($dump, 0, strpos($dump, '|'));
				$allArguments = substr($dump, strpos($dump, '|') + 1);
					// 如果有竖线，则将 “$dump” 从竖线位置分割，得模板名和各参数值
					//  · 
					// If vertical bar exists, divide "$dump" into template name and values of arguments at vertical bar
				break;
		}

		return array($templateName, $allArguments);
	}

	/**
	 * get_template($name)
	 * 获取模板内的内容 · Get the data from template
	 * 
	 * 默认情况下，模板页面是来自 “$conf['namespace']” 里面所设置的命名空间当中的。
	 * 若要覆盖默认的命名空间，请在页面名 “$name” 前面加一个半角冒号。
	 *  · 
	 * By default, a page from namespace specified in "$conf['namespace']" will be loaded.
	 * To override this, prepend a colon to "$name".
	 * 
	 * @version	1.0 (210105)
	 * @since	1.0 (210105)
	 * 
	 * @author	Vitalie Ciubotaru <vitalie@ciubotaru.tk>
	 * 
	 * @param	string	$name	模板页面名 · Name of template page
	 * @return	string			模板内去除 “<noinclude>” 等标签后的原始代码 · Raw data from the template by removing "<noinclude>" and other tags
	 */
	function get_template($name) {
		$template = rawWiki((substr($name, 0, 1) == ":") || ($this->getConf('namespace') == '') ? substr($name, 1) : $this->getConf('namespace') . ":" . $name);
		if (!$template)
		{
			return false;
		}
		$template = preg_replace('/<noinclude>.*?<\/noinclude>/s', '', $template);
		$template = preg_replace('/<includeonly>|<\/includeonly>/', '', $template);
		return $template;
	}
} 
