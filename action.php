<?php
/**
 * DokuWiki tplt 插件（动作模块） · DokuWiki plugin tplt (action component)
 *
 * @license	GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author	AlloyDome
 * 
 * @version 2.0.0, beta (210429)
 */

use dokuwiki\Parsing\Parser;

if(!defined('DOKU_INC'))
	die();	// 必须在 Dokuwiki 下运行 · Must be run within Dokuwiki

class action_plugin_tplt extends DokuWiki_Action_Plugin {

	const PATTERNS_FOR_RAWWIKI = array(
		array(
			'name' => 'nowiki',
			'isCouple' => true,
			'allowSelfNest' => false,
			'allowEnterFrom' => array('tplt'),
			'patterns' => array(
				array('start' => '%%', 							'end' => '%%', 			'isPcre' => false),
				array('start' => '<nowiki>', 					'end' => '</nowiki>', 	'isPcre' => false),
				array('start' => '<html>', 						'end' => '</html>', 	'isPcre' => false),
				array('start' => '<HTML>', 						'end' => '</HTML>', 	'isPcre' => false),
				array('start' => '<php>', 						'end' => '</php>', 		'isPcre' => false),
				array('start' => '<PHP>', 						'end' => '</PHP>', 		'isPcre' => false),
				array('start' => '/<code\b(?=.*<\/code>)/s', 	'end' => '/<\/code>/', 	'isPcre' => true),
				array('start' => '/<file\b(?=.*<\/file>)/s', 	'end' => '/<\/file>/', 	'isPcre' => true),
			)
		),
		array(
			'name' => 'tplt',
			'isCouple' => true,
			'allowSelfNest' => true,
			'allowEnterFrom' => array(),
			'patterns' => array(
				array('start' => '[|', 		'end' => '|]', 				'isPcre' => false)
			)
		),
		array(
			'name' => 'arg',
			'isCouple' => true,
			'allowSelfNest' => false,
			'allowEnterFrom' => array(),
			'patterns' => array(
				array('start' => '{{{', 		'end' => '}}}', 		'isPcre' => false)
			)
		),
	);

	const PATTERNS_FOR_TPLT_SYNTAX = array(
		array(
			'name' => 'nowiki',
			'isCouple' => true,
			'allowSelfNest' => false,
			'allowEnterFrom' => array('tplt'),
			'patterns' => array(
				array('start' => '%%', 							'end' => '%%', 			'isPcre' => false),
				array('start' => '<nowiki>', 					'end' => '</nowiki>', 	'isPcre' => false),
				array('start' => '<html>', 						'end' => '</html>', 	'isPcre' => false),
				array('start' => '<HTML>', 						'end' => '</HTML>', 	'isPcre' => false),
				array('start' => '<php>', 						'end' => '</php>', 		'isPcre' => false),
				array('start' => '<PHP>', 						'end' => '</PHP>', 		'isPcre' => false),
				array('start' => '/<code\b(?=.*<\/code>)/s', 	'end' => '/<\/code>/', 	'isPcre' => true),
				array('start' => '/<file\b(?=.*<\/file>)/s', 	'end' => '/<\/file>/', 	'isPcre' => true),
			)
		),
		array(
			'name' => 'delimiter',
			'isCouple' => false,
			'allowEnterFrom' => array(),
			'patterns' => array(
				array('selfClosing' => '|', 'isPcre' => false)
			)
		),
		array(
			'name' => 'tplt',
			'isCouple' => true,
			'allowSelfNest' => true,
			'allowEnterFrom' => array(),
			'patterns' => array(
				array('start' => '[|', 		'end' => '|]', 				'isPcre' => false)
			)
		)
	);

	// ----------------------------------------------------------------

	/**
	 * register(Doku_Event_Handler $controller)
	 * 在 DokuWiki 事件控制器中注册插件相关的处理器 · Register its handlers with the DokuWiki's event controller
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
	 * @version	2.0.0, beta (210429)
	 * @since	2.0.0, beta (210429)
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
		# global $ID;
		# $pageStack = array($ID);
		$pageStack = array();	// 根页面不应填入页面堆栈中

		$text = $this->tpltMainHandler($text, array(), $pageStack);

		// 解析完了以后把模板内容都替换掉
		$event->data = $text;
	}

	// ----------------------------------------------------------------

	/**
	 * tpltMainHandler($text)
	 * 主处理函数
	 * 
	 * @version	2.0.0, beta (210429)
	 * @since	2.0.0, beta (210429)
	 * 
	 * @author	AlloyDome
	 * 
	 */
	private function tpltMainHandler($text, $incomingArgs = array(), &$pageStack = array()) {
		if ($this->getConf('maxNestLevel') != 0 && count($pageStack) > $this->getConf('maxNestLevel')) {
			return $text;	// 如果超过最大嵌套层数，则返回文本本身
		}

		$instructions = $this->tpltParser($text, 'rawWiki');
		$this->replaceArgs($instructions, $incomingArgs);
		$this->replaceTplts($instructions, $incomingArgs, $pageStack);	// 注：可以考虑将“[| ... |]”里面的“{{{ ... }}}”参数（当前页面的传入参数）在这个函数里面做替换，而不是在 replaceArgs() 函数里面
		return $this->textMerge($instructions);
	}

	private function replaceArgs(&$instructions, $incomingArgs) {
		foreach ($instructions as $key => $instruction) {
			if ($instruction['type'] == 'arg' && $instruction['startOrEnd'] = 'end') {
				$argNameAndDefaultValue = $instructions[$key - 1]['text'];
				if (strpos($argNameAndDefaultValue, '=') !== false) {
					$argNameAndDefaultValue = explode('=', $argNameAndDefaultValue, 2);
					$argName = trim($argNameAndDefaultValue[0]);
					$defaultValue = trim($argNameAndDefaultValue[1]);
				} else {
					$argName = $argNameAndDefaultValue;
					$defaultValue = false;
				}
				if ($instructions[$key - 2]['text'] == '{{{') {
					if (($this->arrayKeyExists($argName, $incomingArgs)) || $defaultValue !== false) {
						if ($this->arrayKeyExists($argName, $incomingArgs)) {
							$instructions[$key - 1]['text'] = $incomingArgs[$argName];
						} elseif ($defaultValue !== false && !$this->arrayKeyExists($argName, $incomingArgs)) {
							$instructions[$key - 1]['text'] = $defaultValue;
						}
						unset($instructions[$key]);
						unset($instructions[$key - 2]);
					}				
				}
			}
		}
		$instructions = array_values($instructions);
	}

	private function replaceTplts(&$instructions, $incomingArgs, &$pageStack = array()) {
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
						$tpltText = $this->tpltRendener($matechedTpltNameAndArgs, $incomingArgs, $pageStack);
						for ($i = $startOrderNo; $i <= $key - 1; $i++) {
							unset($instructions[$i]);
						}
						unset($instructions[$key]['startOrEnd']);
						$instructions[$key]['type'] = 'plainText';
						$instructions[$key]['text'] = $tpltText;
					}
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
		return trim($text); // ?
	}

	// ----------------------------------------------------------------

	/**
	 * tpltParser($text, $parserMode)
	 * 代码解析器 · Parser of codes
	 * 
	 * @version	2.0.0, beta (210429)
	 * @since	2.0.0, beta (210429)
	 * 
	 * @author	AlloyDome
	 * 
	 * @param	string	$text			原始 Wiki 代码 · raw Wiki code
	 * 
	 * @return	array					解析后的标示片段 · Instructions from parsing
	 */
	private function tpltParser($text, $parserMode) {
		$patternArray = $this->getPatterns($parserMode);
		
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
				// 注：如果是自关闭标识，则不计入嵌套堆栈中
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

	private function getPatterns($parserMode)
	{
		switch ($parserMode) {
			case 'rawWiki':
				$patternArray = self::PATTERNS_FOR_RAWWIKI;
				break;
			case 'tpltSyntax':
				$patternArray = self::PATTERNS_FOR_TPLT_SYNTAX;
				break;
			default:
				$patternArray = false;
		}
		return $patternArray;
	}

	/**
	 * tpltParser($text)
	 * 寻找各标识第一次出现的位置 · Find the first positions of patterns
	 * 
	 * @version	2.0.0, beta (210429)
	 * @since	2.0.0, beta (210429)
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
			$isCouple = $patternGroup['isCouple'];
			$allowSelfNest = ($isCouple == true) ? $patternGroup['allowSelfNest'] : false;

			$startOrSelfclosing = ($isCouple == true) ? 'start' : 'selfClosing';

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
							list($patternPos1, $matchedText1) = $this->patternMatch($text, $pattern[$startOrSelfclosing], $pattern['isPcre']);
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
								$startOrEnd = $startOrSelfclosing;
							} else {
								$patternPos = min($patternPos1, $patternPos2);
								$matchedText = ($patternPos2 <= $patternPos1) ? $matchedText2 : $matchedText1;
								$startOrEnd = ($patternPos2 <= $patternPos1) ? 'end' : $startOrSelfclosing;
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
							list($patternPos, $matchedText) = $this->patternMatch($text, $pattern[$startOrSelfclosing], $pattern['isPcre']);
							if ($patternPos !== false) {
								$firstPosOfPatterns[$patternPos] = array(
									'name' => $patternGroupName, 
									'orderNo' => $orderNo, 
									'startOrEnd' => $startOrSelfclosing, 
									'matchedPattern' => $matchedText);
							}
						}
					}
				} else {
					// 没有位于自身嵌套内（可能位于其他嵌套内部），则匹配开始标记
					if ($currentNest === false) {
						// 没有位于任何嵌套内
						list($patternPos, $matchedText) = $this->patternMatch($text, $pattern[$startOrSelfclosing], $pattern['isPcre']);
						if ($patternPos !== false) {
							$firstPosOfPatterns[$patternPos] = array(
								'name' => $patternGroupName, 
								'orderNo' => $orderNo, 
								'startOrEnd' => $startOrSelfclosing, 
								'matchedPattern' => $matchedText);
						}
					} else {
						// 位于其他嵌套内
						if (!empty($patternGroup['allowEnterFrom']) && in_array($currentNest, $patternGroup['allowEnterFrom'])) {
							// 如果该标识允许包含在上一级嵌套内，则匹配开始标记，否则什么也不做
							list($patternPos, $matchedText) = $this->patternMatch($text, $pattern[$startOrSelfclosing], $pattern['isPcre']);
							if ($patternPos !== false) {
								$firstPosOfPatterns[$patternPos] = array(
									'name' => $patternGroupName, 
									'orderNo' => $orderNo, 
									'startOrEnd' => $startOrSelfclosing, 
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
				return array(strpos($text, $match[0]), $match[0]);
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

	private function arrayKeyExists($key, $array) {
		if (empty($array)) {
			return false;
		} else {
			return array_key_exists($key, $array);
		}
	}

	// ----------------------------------------------------------------

	private function tpltRendener($rawText, $incomingArgs, &$pageStack) {
		if (!$rawText)
			return '';	// 如果传入一个空字符串，则返回 false · Return false if the incoming string is empty

		$templateNameAndArgs = $this->getTemplateNameAndArgs($rawText);	// 模板名与各参数 · Template name and arguments

		if ($templateNameAndArgs == false)
			return '';

		$fragmentKeys = array_keys($templateNameAndArgs);
		foreach ($fragmentKeys as $fragmentKey) {
			$templateNameAndArgs[$fragmentKey] = trim($templateNameAndArgs[$fragmentKey]);
		}

		$templateNameDump = $templateNameAndArgs[0];	// 模板名 · template name
		$argDump = array_slice($templateNameAndArgs, 1);

		$template_arguments = array();	// 存储参数值的数组 · Array for values of arguments
		if ($argDump)
		{
			foreach ($argDump as $key => $value) {
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
			// 将参数值中用于替代竖杠符号的“~~!~~”再替换回来
			//  ·
			// Restore the vertical line characters replaced by "~~!~~" in argument values before
		unset($argDump);
		$argNames = array_keys($template_arguments);
		foreach ($argNames as $argName) {
			$pageStackForArgs = $pageStack; // ?
			$template_arguments[$argName] = $this->tpltMainHandler($template_arguments[$argName], $incomingArgs, $pageStackForArgs);
		}

		$templateName = $this->getTemplateName($templateNameDump);
		$template = $this->get_template($templateName);
		if (!$template) {
			return '[[' . $templateName . ']]';	// 如果模板不存在，返回一个该模板的链接（？）
		} 
		
		if (in_array($templateName, $pageStack)) {
			return '';
		} else {
			$pageStack[] = $templateName;
			$renderedText = $this->tpltMainHandler($template, $template_arguments, $pageStack);
			array_pop($pageStack);
			return $renderedText;
		}
	}

	private function getTemplateNameAndArgs($match)
	{
		$instructions = $this->tpltParser($match, 'tpltSyntax');
		
		if (empty($instructions)) {
			return false;
		}

		$templateNameAndArgs = array();
		$startKey = 0;
		$arrayCountMinus1 = count($instructions) - 1;
		foreach ($instructions as $key => $instruction) {
			if ($instruction['type'] == 'delimiter' || $key == $arrayCountMinus1) {
				if ($instruction['type'] == 'delimiter') {
					$endKey = $key;
				} else {
					$endKey = $key + 1;
				}
				
				$fragment = '';
				for ($i = $startKey; $i < $endKey; $i++) {
					if ($i >= 0) {
						$fragment .= $instructions[$i]['text'];
					}
				}
				if ($fragment != '') {
					$templateNameAndArgs[] = $fragment;
				}
				$startKey = $key + 1;
			}
		}

		if (empty($templateNameAndArgs)) {
			return false;
		} else {
			return $templateNameAndArgs;
		}
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
	 * @version	1.0, beta (210105)
	 * @since	1.0, beta (210105)
	 * 
	 * @author	Vitalie Ciubotaru <vitalie@ciubotaru.tk>
	 * 
	 * @param	string	$name	模板页面名 · Name of template page
	 * @return	string			模板内去除 “<noinclude>” 等标签后的原始代码 · Raw data from the template by removing "<noinclude>" and other tags
	 */
	private function get_template($name) {
		$template = rawWiki($name);
		if (!$template)
		{
			return false;
		}
		$template = preg_replace('/<noinclude>.*?<\/noinclude>\n?/s', '', $template);
		$template = preg_replace('/<includeonly>\n?|<\/includeonly>\n?/', '', $template);
		return $template;
	}

	private function getTemplateName($name) {
		if (preg_match('/\:{2,}/', $name, $match) != 0)	// 不允许连写冒号 · Consecutive colons are not allowed
			return '';

		if (substr($this->getConf('namespace'), 0, 1) == ':') {
			$defaultNamespace = substr($this->getConf('namespace'), 1);
		} else {
			$defaultNamespace = $this->getConf('namespace');
		}

		if (substr($name, 0, 1) == ':') {
			return substr($name, 1);
		} else {
			if ($this->getConf('namespace') == '') {
				return $name;
			} else {
				return $this->getConf('namespace') . ':' . $name;
			}
		}
	}
} 