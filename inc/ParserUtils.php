<?php
/**
 * DokuWiki tplt 插件（动作模块） · DokuWiki plugin tplt (action component)
 *
 * @license	GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author	AlloyDome
 * 
 * @since	2.2.1, beta (------)
 * @version 2.2.1, beta (------)
 */

namespace dokuwiki\lib\plugins\tplt\inc;

if(!defined('DOKU_INC'))
	die();	// 必须在 Dokuwiki 下运行 · Must be run within Dokuwiki

use dokuwiki\Parsing\Parser;

require_once(DOKU_PLUGIN . 'tplt/inc/pluginClass.php');

class ParserUtils{
	private static function getPatterns($parserMode) {
		switch ($parserMode) {
			case 'rawWiki': {
				return array(
					array(
						'name' => 'nowiki',
						'isCouple' => true,
						'allowSelfNest' => false,
						'allowEnterFromRoot' => true,
						'allowEnterFrom' => array('tplt', 'arg'),
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
						'allowEnterFromRoot' => true,
						'allowEnterFrom' => array(),
						'patterns' => array(
							array('start' => '[|', 		'end' => '|]', 				'isPcre' => false)
						)
					),
					array(
						'name' => 'arg',
						'isCouple' => true,
						'allowSelfNest' => false,
						'allowEnterFromRoot' => true,
						'allowEnterFrom' => array(),
						'patterns' => array(
							array('start' => '/\{\{\{(?!\{)/', 	'end' => '/(?<!\})\}\}\}/', 'isPcre' => true)
						)
					),
	//				array(
	//					'name' => 'quoted',
	//					'isCouple' => true,
	//					'allowSelfNest' => false,
	//					'allowEnterFromRoot' => false,
	//					'allowEnterFrom' => array('tplt' /*, 'arg' */),
	//					'patterns' => array(
	//						array('start' => '/"(?!")/', 	'end' => '/"(?!")/', 	'isPcre' => true)
	//					)
	//				),
					/*
						FIXME:	这一部分可能应当启用，因为“[| ... |]”内双引号起无格式文本的作用，因此判断双引号在“[| ... |]”
								内的分布情况可以避免将由双引号包围的“|]”误识别为结束标识。
					*/
				);
			}
			case 'tpltSyntax': {
				return array(
					array(
						'name' => 'nowiki',
						'isCouple' => true,
						'allowSelfNest' => false,
						'allowEnterFromRoot' => true,
						'allowEnterFrom' => array('tplt', 'quoted'),
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
					/*
						FIXME:	nowiki 这部分在“[| ... |]”内部可能不需要，因为参数值可能会出现一半的 nowiki 标记，例如
								“[|example|123%%|%%456|]”，两个参数值分别是“123%%”和“%%456”，中间的竖线还是起作用的。
								如果不想让竖线起作用，应该使用双引号包围，也就是说，在调用模板的部分，也就是“[| ... |]”
								内，起无格式文本作用的应该是双引号，而不是类似“%% ... %%”的标记。
					*/
					array(
						'name' => 'delimiter',
						'isCouple' => false,
						'allowEnterFromRoot' => true,
						'allowEnterFrom' => array(),
						'patterns' => array(
							array('selfClosing' => '|', 'isPcre' => false)
						)
					),
					array(
						'name' => 'tplt',
						'isCouple' => true,
						'allowSelfNest' => true,
						'allowEnterFromRoot' => true,
						'allowEnterFrom' => array('quoted'),
						'patterns' => array(
							array('start' => '[|', 		'end' => '|]', 				'isPcre' => false)
						)
					),
					array(
						'name' => 'quoted',
						'isCouple' => true,
						'allowSelfNest' => false,
						'allowEnterFromRoot' => true,
						'allowEnterFrom' => array(),
						'patterns' => array(
							array('start' => '/"(?!")/', 	'end' => '/"(?!")/', 	'isPcre' => true)
						)
					),
				);
			}
			case 'argValueSyntax': {
				return array(
					array(
						'name' => 'nowiki',
						'isCouple' => true,
						'allowSelfNest' => false,
						'allowEnterFromRoot' => true,
						'allowEnterFrom' => array('tplt', 'quoted'),
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
						'allowEnterFromRoot' => true,
						'allowEnterFrom' => array('quoted'),
						'patterns' => array(
							array('start' => '[|', 		'end' => '|]', 				'isPcre' => false)
						)
					),
					array(
						'name' => 'quoted',
						'isCouple' => true,
						'allowSelfNest' => false,
						'allowEnterFromRoot' => true,
						'allowEnterFrom' => array(),
						'patterns' => array(
							array('start' => '/"(?!")/', 	'end' => '/"(?!")/', 	'isPcre' => true)
						)
					),
					array(
						'name' => 'quotemark',
						'isCouple' => false,
						'allowEnterFromRoot' => true,
						'allowEnterFrom' => array('quoted'),
						'patterns' => array(
							array('selfClosing' => '""', 	'isPcre' => false)
						)
					),
					array(
						'name' => 'delimiterAlt',
						'isCouple' => false,
						'allowEnterFromRoot' => true,
						'allowEnterFrom' => array(),
						'patterns' => array(
							array('selfClosing' => '~~!~~', 'isPcre' => false)
						)
					),
					array(
						'name' => 'breakrowAlt',
						'isCouple' => false,
						'allowEnterFromRoot' => true,
						'allowEnterFrom' => array(),
						'patterns' => array(
							array('selfClosing' => '~~br~~', 'isPcre' => false)
						)
					),
					array(
						'name' => 'spaceAlt',
						'isCouple' => false,
						'allowEnterFromRoot' => true,
						'allowEnterFrom' => array(),
						'patterns' => array(
							array('selfClosing' => '~~sp~~', 'isPcre' => false)
						)
					),
					array(
						'name' => 'tildeAlt',
						'isCouple' => false,
						'allowEnterFromRoot' => true,
						'allowEnterFrom' => array(),
						'patterns' => array(
							array('selfClosing' => '~~tilde~~', 'isPcre' => false)
						)
					),
				);
			}
			default: {
				return false;
			}
		}
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
	public static function tpltMainHandler($text, $incomingArgs = array(), &$pageStack = array(), &$strposMap = false) {
		$maxNestLevelConf = (new inc_plugin_tplt())->getConf('maxNestLevel');
		
		if ($maxNestLevelConf != 0 && count($pageStack) > $maxNestLevelConf) {
			return $text;	// 如果超过最大嵌套层数，则返回文本本身
		}

		$instructions = self::tpltParser($text, 'rawWiki');	// 解析
		$strposMap = self::replaceArgsAndTplt($instructions, $incomingArgs, $pageStack);	// 解析完了以后把模板内容都替换掉
			// 注：可以考虑将“[| ... |]”里面的“{{{ ... }}}”参数（当前页面的传入参数）在这个函数里面做替换，而不是在 replaceArgs() 函数里面
		
		return self::textMerge($instructions);
	}

	/**
	 * replaceArgsAndTplt(&$instructions, $incomingArgs, &$pageStack = array())
	 * 替换文本中的参数、模板或解析器函数
	 * 
	 * @version	2.2.0, beta (211130)
	 * @since	2.1.0, beta (210706)
	 * 
	 * @author	AlloyDome
	 * 
	 */
	public static function replaceArgsAndTplt(&$instructions, $incomingArgs, &$pageStack = array()) {
		// XXX:	此函数内代码十分混乱，有待优化
		if (empty($pageStack)) {
			$isRootPage = true;
		} else {
			$isRootPage = false;
		}

		$strposMap = array();	// 字符位置映射表（用于修正章节编辑按钮的定位）
		$textStartPos = array('ori' => 0, 'pro' => 0);	// 纯文本开始位置
		$textCurrentPos = $textStartPos;	// 纯文本长度
		$isInArgOrTplt = false;

		$nestLevel = 0;
		foreach ($instructions as $key => $instruction) {
			switch ($instruction['type']) {
				case 'arg':
				case 'tplt': {
					if($instruction['startOrEnd'] == 'start' && $nestLevel == 0) {
						$strposMap[] = array(
							'ori' => array($textStartPos['ori'], $textCurrentPos['ori'] - 1),
							'pro' => array($textStartPos['pro'], $textCurrentPos['pro'] - 1)
						);
						$isInArgOrTplt = true;
					}
					break;
				}
			}

			if (!$isInArgOrTplt) {
				$textCurrentPos['ori'] += strlen($instruction['text']);
				$textCurrentPos['pro'] += strlen($instruction['text']);
				if ($key == count($instructions) - 1) {
					$strposMap[] = array(
						'ori' => array($textStartPos['ori'], $textCurrentPos['ori'] - 1),
						'pro' => array($textStartPos['pro'], $textCurrentPos['pro'] - 1)
					);	
				}
			}

			switch ($instruction['type']) {
				case 'arg': {
					switch ($instruction['startOrEnd']) {
						case 'start': {
							break;
						}
						case 'end': {
							for ($i = $key - 2; $i <= $key; $i++) {
								$textCurrentPos['ori'] += strlen($instructions[$i]['text']);
							}
							$argNameAndDefaultValue = $instructions[$key - 1]['text'];
							if (strpos($argNameAndDefaultValue, '=') !== false) {
								$argNameAndDefaultValue = explode('=', $argNameAndDefaultValue, 2);
								$argName = trim($argNameAndDefaultValue[0]);
								$defaultValue = trim($argNameAndDefaultValue[1]);
							} else {
								$argName = $argNameAndDefaultValue;
								$defaultValue = false;
							}
							if ($instructions[$key - 2]['type'] == 'arg' && $instruction['startOrEnd'] = 'start' 
							&& ((self::arrayKeyExists($argName, $incomingArgs)) || $defaultValue !== false)) {
								if (self::arrayKeyExists($argName, $incomingArgs)) {
									$instructions[$key]['text'] = $incomingArgs[$argName];
								} elseif ($defaultValue !== false && !self::arrayKeyExists($argName, $incomingArgs)) {
									$instructions[$key]['text'] = $defaultValue;
								}
								$instructions[$key - 1]['text'] = '';
								$instructions[$key - 2]['text'] = '';
							}
							for ($i = $key - 2; $i <= $key; $i++) {
								$textCurrentPos['pro'] += strlen($instructions[$i]['text']);
							}
							$textStartPos = $textCurrentPos;
							$isInArgOrTplt = false;
							break;	
						}
					}
					break;
				} case 'tplt': {
					switch ($instruction['startOrEnd']) {
						case 'start': {
							$nestLevel += 1;
							if ($nestLevel == 1) {
								$startOrderNo = $key;
							}
							break;
						} case 'end': {
							$nestLevel -= 1;
							if ($nestLevel == 0) {
								for ($i = $startOrderNo; $i <= $key; $i++) {
									$textCurrentPos['ori'] += strlen($instructions[$i]['text']);
								}
								$matechedTpltNameAndArgs = '';
								for ($i = $startOrderNo + 1; $i <= $key - 1; $i++) {
									$matechedTpltNameAndArgs .= $instructions[$i]['text'];
								}
								$tpltText = self::tpltAndPfRenderer($matechedTpltNameAndArgs, $incomingArgs, $pageStack);

								for ($i = $startOrderNo; $i <= $key - 1; $i++) {
									$instructions[$i]['text'] = '';
								}
								unset($instructions[$key]['startOrEnd']);
								$instructions[$key]['type'] = 'plainText';
								$instructions[$key]['text'] = $tpltText;

								for ($i = $startOrderNo; $i <= $key; $i++) {
									$textCurrentPos['pro'] += strlen($instructions[$i]['text']);
								}
								
								$textStartPos = $textCurrentPos;
								$isInArgOrTplt = false;
							}
							break;
						}
					}
					break;
				}
			}
		}
		$instructions = array_values($instructions);

		if ($isRootPage) {
			return $strposMap;
		} else {
			return array();
		}
	}

	public static function textMerge($instructions /* , $trim = true */ ) {
		$text = '';
		foreach ($instructions as $instruction) {
			$text .= $instruction['text'];
		}
		return $text;
		// return $trim ? trim($text) : $text; // ?
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
	public static function tpltParser($text, $parserMode) {
		$patternArray = self::getPatterns($parserMode);
		
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
			$firstPosOfPatterns = self::findFirstPosOfPatterns($reducedText, $patternArray, $nestLevel, $currentNest, $currentNestPatternOdr);
			
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

	/**
	 * findFirstPosOfPatterns($text, $patternArray, $nestLevel, $currentNest, $currentNestPatternOdr)
	 * 寻找各标识第一次出现的位置 · Find the first positions of patterns
	 * 
	 * @version	2.2.0, beta (211130)
	 * @since	2.2.0, beta (211130)
	 * 
	 * @author	AlloyDome
	 * 
	 * @param	string	$text				原始 Wiki 代码 · raw Wiki code
	 * @param	array	$patternArray		标识字符串的存储序列 · Array of patterns
	 * @param	array	$modesOfStartAndEnd	开始与结束模式 · Modes of start and end
	 * 
	 * @return	array						各标识第一次出现的位置 · The first positions of patterns
	 */
	private static function findFirstPosOfPatterns($text, $patternArray, $nestLevel, $currentNest, $currentNestPatternOdr) {
		$firstPosOfPatterns = array();

		// ↓ 遍历各个标识类型
		foreach ($patternArray as $patternGroup) {
			$patternGroupName = $patternGroup['name'];	// 标识名称
			$isCouple = $patternGroup['isCouple'];	// 标识是否成对

			// ↓ 判断是否允许自嵌套
			if ($isCouple != true) {
				$allowSelfNest = false; // 自关闭标识不存在嵌套
			} else {
				$allowSelfNest = $patternGroup['allowSelfNest'];	// 否则按实际规定
			}

			// ↓ 遍历当前标识类型里的各个标识字符串
			foreach ($patternGroup['patterns'] as $orderNo => $pattern) {

				// ↓ 允许匹配的标识开闭类型
				$allowMatch = array(
					'start' => false,	// 也兼做自关闭标识的允许匹配标识
					'end' => false
				);

				// ↓ 判断哪些开闭类型的标识字符串可以被匹配
				if ($currentNest === false) {						// 1. 没有位于任何嵌套内
					if ($patternGroup['allowEnterFromRoot'] == true) {	// 允许从根部进入
						$allowMatch['start'] = true;						// 则匹配开始标记
					}
				} else {											// 2. 位于嵌套内
					if ($currentNest == $patternGroupName) {			// 2.1. 位于自身嵌套内
						if (
							$allowSelfNest == false &&
							$orderNo == $currentNestPatternOdr
						) {													// 2.1.1. 不允许自嵌套，且位于自身嵌套内且开始标记为当前所循环到的标记
							$allowMatch['end'] = true;							// 则只匹配其对应的结束标记，否则什么也不做
						} else {											// 2.1.2. 允许自嵌套
							if ($orderNo == $currentNestPatternOdr) {			// 2.1.2.1 位于自身嵌套内且开始标记为当前所循环到的标记
								$allowMatch['start'] = true;						// 则正常匹配开始与结束标记
								$allowMatch['end'] = true;
							} else {											// 2.1.2.2 位于自身嵌套内但开始标记不是当前所循环到的标记
								$allowMatch['start'] = true;						// 则只匹配其对应的开始标记，否则什么也不做
							}
						}
					} elseif (
						!empty($patternGroup['allowEnterFrom']) && 
						in_array($currentNest, $patternGroup['allowEnterFrom'])
					) {													// 2.2. 位于其他嵌套内，且该标识允许包含在上一级嵌套内
						$allowMatch['start'] = true;						// 则匹配开始标记，否则什么也不做
					}
				}

				$matchedList = array('start' => array(false, false), 'end' => array(false, false));
					// ↑ 匹配到的标识出现的位置以及匹配的字符串
				$startOrSelfclosing = ($isCouple == true) ? 'start' : 'selfClosing';

				// ↓ 进行匹配
				if ($allowMatch['start'] == true) {
					$matchedList['start'] = self::patternMatch($text, $pattern[$startOrSelfclosing], $pattern['isPcre']);
				}
				if ($allowMatch['end'] == true) {
					$matchedList['end'] = self::patternMatch($text, $pattern['end'], $pattern['isPcre']);
				}

				$startOrEnd = false;	// 对于同时匹配了开始和结束两种标记的情形，最终采用的结果
				
				// ↓ 对于同时匹配了开始标识和结束标识情况的处理
				if ($matchedList['start'][0] !== false) {
					if ($matchedList['end'][0] !== false) {	// 开始标识和结束标识都匹配到了
						if ($matchedList['end'][0] <= $matchedList['start'][0]) {
							$startOrEnd = 'end';	// 如果结束标识在开始的前面，先考虑结束标识，且如果两者出现位置相同，优先考虑结束标识，防止无法自关闭
						} else {
							$startOrEnd = 'start';
						}
					} else {
						$startOrEnd = 'start';	// 匹配到了开始标识（或自关闭标识）
					}
				} else {
					if ($matchedList['end'][0] !== false) {
						$startOrEnd = 'end';	// 匹配到了结束标识
					}
				}

				$actualStartOrEnd = $startOrEnd;	// 最终的标识开闭形式（区分开始标识和自关闭标识）
				if ($startOrEnd == 'start') {
					list($patternPos, $matchedText) = $matchedList['start'];
					$actualStartOrEnd = $startOrSelfclosing;
				} elseif ($startOrEnd == 'end') {
					list($patternPos, $matchedText) = $matchedList['end'];
				}

				// ↓ 更新数组
				if ($startOrEnd !== false) {
					$firstPosOfPatterns[$patternPos] = array(
						'name' => $patternGroupName, 
						'orderNo' => $orderNo, 
						'startOrEnd' => $actualStartOrEnd, 
						'matchedPattern' => $matchedText);
				}
			}
		}
		return $firstPosOfPatterns;
	}

	/**
	* patternMatch($text, $pattern, $isPcre)
	* 查找一个标识的位置及匹配片段 · Find the positions of a pattern and the matched text
	* 
	* @version	2.0.0, beta (210429)
	* @since	2.1.0, beta (210706)
	* 
	* @author	AlloyDome
	* 
	* @param	string	$text				原始 Wiki 代码 · Raw Wiki code
	* @param	string	$pattern			标识字符串 · Pattern
	* @param	bool	$isPcre				是否采用正则匹配 · Use regex or not
	* 
	* @return	array						第一次出现的位置 · The first position of the pattern
	*/
	private static function patternMatch($text, $pattern, $isPcre) {
		if ($isPcre == false) {
			$findResult = strpos($text, $pattern);
			if ($findResult !== false) {
				return array($findResult, $pattern);
			}
		} elseif ($isPcre == true) {
			$findResult = preg_match($pattern, $text, $match, PREG_OFFSET_CAPTURE);
			if ($findResult != 0) {
				return array($match[0][1], $match[0][0]);
			}
		}
		return array(false, false);
	}

	private static function arrayKeyExists($key, $array) {
		if (empty($array)) {
			return false;
		} else {
			return array_key_exists($key, $array);
		}
	}

	// ----------------------------------------------------------------

	/**
	 * tpltAndPfRenderer($rawText, $incomingArgs, &$pageStack)
	 * 输出模板内容文本的渲染器 · Renderer of template text
	 * 
	 * @version	2.2.0, beta (211130)
	 * @since	2.2.0, beta (211130)
	 * 
	 * @author	1. Vitalie Ciubotaru <vitalie@ciubotaru.tk>
	 * 			2. Alloydome
	 *
	 * @param	string	$rawText		未处理的模板调用语法，包括模板名和参数 · Unprocessed template calling syntax, including template name and arguments
	 * @param	array	$incomingArgs	传入参数 · Incoming arguments
	 * @param	array	&$pageStack		页面堆栈 · Stack of pages
	 * @return	string					替换过参数的模板内容文本 · Argument replaced template text
	 */
	public static function tpltAndPfRenderer($rawText, $incomingArgs, &$pageStack) {
		if (!$rawText)
			return '';	// 如果传入一个空字符串，则返回 false · Return false if the incoming string is empty

		$templateNameAndArgs = self::getTemplateNameAndArgs($rawText);	// 模板名与各参数 · Template name and arguments

		if ($templateNameAndArgs == false)
			return '';

		$fragmentKeys = array_keys($templateNameAndArgs);
		foreach ($fragmentKeys as $fragmentKey) {
			$templateNameAndArgs[$fragmentKey] = trim($templateNameAndArgs[$fragmentKey]);
		}

		if (substr(trim($templateNameAndArgs[0]), 0, 1) == '#') {
			$renderedText = self::pfRenderer($templateNameAndArgs, $incomingArgs, $pageStack);
		} else {
			$renderedText = self::tpltRenderer($templateNameAndArgs, $incomingArgs, $pageStack);
		}
		return $renderedText;
	}

	// ----------------------------------------------------------------

	/**
	 * pfRenderer($templateNameAndArgs, $incomingArgs, &$pageStack)
	 * 输出解析器函数运行结果的渲染器 · Renderer of paresr functions
	 * 
	 * @version	2.2.0, beta (211130)
	 * @since	2.2.0, beta (211130)
	 * 
	 * @author	Alloydome
	 *
	 * @param	string	$templateNameAndArgs	解析器函数名和参数 · parser function name and arguments
	 * @param	array	$incomingArgs			传入参数 · Incoming arguments
	 * @param	array	&$pageStack				页面堆栈 · Stack of pages
	 * @return	string							替换过参数的模板内容文本 · Argument replaced template text
	 */
	public static function pfRenderer($templateNameAndArgs, $incomingArgs, &$pageStack) {
		$pfName = substr(trim($templateNameAndArgs[0]), 1);	// 解析器函数名 · template name
		$argDump = array_slice($templateNameAndArgs, 1);

		$pfArgs = array();
		if ($argDump) {
			foreach ($argDump as $value) {
				$pfArgs[] = self::removeAltPatternInArgValue(trim($value));
			}
		}

		if (array_key_exists($pfName, PfList::$pfClassList)) {
			return PfList::$pfClassList[$pfName]->renderer($pfArgs, $incomingArgs, $pageStack);
		} else {
			return ' (' . $pfName. '?) ';
		}
		
	}

	// ----------------------------------------------------------------

	/**
	 * tpltRenderer($templateNameAndArgs, $incomingArgs, &$pageStack)
	 * 输出模板内容文本的渲染器 · Renderer of template text
	 * 
	 * @version	2.2.0, beta (211130)
	 * @since	2.2.0, beta (211130)
	 * 
	 * @author	1. Vitalie Ciubotaru <vitalie@ciubotaru.tk>
	 * 			2. Alloydome
	 *
	 * @param	string	$templateNameAndArgs	模板名和参数 · template name and arguments
	 * @param	array	$incomingArgs			传入参数 · Incoming arguments
	 * @param	array	&$pageStack				页面堆栈 · Stack of pages
	 * @return	string							替换过参数的模板内容文本 · Argument replaced template text
	 */
	public static function tpltRenderer($templateNameAndArgs, $incomingArgs, &$pageStack) {
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
					$template_arguments[(trim($tmp[0] != '')) ? trim($tmp[0]) : ($key + 1)] = 
						self::removeAltPatternInArgValue(trim($tmp[1]));
							// 将参数值中用于替代部分特殊符号的“~~...~~”再替换回来
							//  ·
							// Restore the some spacial symbols replaced by "~~...~~" in argument values before
				}
				// 如果没声明参数名 · If argument name is not defined
				// 则编号，从 1 开始，不是从 0 开始 · Start from 1, not 0
				else $template_arguments[$key + 1] = trim($value);
			}
		}

		unset($argDump);
		$argNames = array_keys($template_arguments);
		foreach ($argNames as $argName) {
			$pageStackForArgs = $pageStack; // ?
			$template_arguments[$argName] = self::tpltMainHandler($template_arguments[$argName], $incomingArgs, $pageStackForArgs);
		}

		$templateName = self::getTemplateName($templateNameDump);
		$template = self::get_template($templateName);
		if (!$template) {
			return '[[' . $templateName . ']]';	// 如果模板不存在，返回一个该模板的链接（？）
		} 
		
		if (in_array($templateName, $pageStack)) {
			return '';
		} else {
			$pageStack[] = $templateName;
			$renderedText = self::tpltMainHandler($template, $template_arguments, $pageStack);
			array_pop($pageStack);
			return $renderedText;
		}
	}

	public static function getTemplateNameAndArgs($match)
	{
		$instructions = self::tpltParser($match, 'tpltSyntax');
		
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
	 * removeAltPatternInArgValue($str)
	 * 将参数值中用于替代部分特殊符号的“~~...~~”再替换回来 · Restore the some spacial symbols replaced by "~~...~~" in argument values before
	 * 
	 * @version	2.2.0, beta (211130)
	 * @since	2.2.0, beta (211130)
	 * 
	 * @author	Alloydome
	 *
	 * @param	string	$str	含“~~...~~”的字符串 · String including "~~...~~"
	 * @return	string			恢复特殊符号的字符串 · String with restored spacial symbols
	 */
	public static function removeAltPatternInArgValue($str) {
		$instructions = self::tpltParser($str, 'argValueSyntax');
		foreach ($instructions as $key => $instruction) {
			switch ($instruction['type']) {
				case 'delimiterAlt' : {
					$instructions[$key]['text'] = '|';
						// 将参数值中用于替代竖杠符号的“~~!~~”再替换回来
						//  ·
						// Restore the vertical line characters replaced by "~~!~~" in argument values before
						break;
				}
				case 'breakrowAlt':{
					$instructions[$key]['text'] = "\n";
					break;
				}
				case 'spaceAlt':{
					$instructions[$key]['text'] = ' ';
					break;
				}
				case 'tildeAlt':{
					$instructions[$key]['text'] = '~';
					break;
				}
				case 'quoted' : {
					$instructions[$key]['text'] = '';
					break;
				}
				case 'quotemark' : {
					$instructions[$key]['text'] = '"';
					break;
				}
			}
		}

		return self::textMerge($instructions);
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
	public static function get_template($name) {
		$template = rawWiki($name);
		if (!$template)
		{
			return false;
		}
		$template = preg_replace('/<noinclude>.*?<\/noinclude>\n?/s', '', $template);
		$template = preg_replace('/<includeonly>\n?|<\/includeonly>\n?/', '', $template);
		return $template;
	}

	/** 
	 * getTemplateName($match, $mode, $dumpMode)
	 * 获得模板的名称 · Get template name
	 * 
	 * @version	2.0.1, beta (210504)
	 * @since	1.0, beta (210105)
	 * 
	 * @author	AlloyDome
	 * 
	 * @param	string	$name	未处理的模板名称 · Unprocessed template name
	 * @return	string			处理后的模板名称 · Processed template name
	 */
	public static function getTemplateName($name) {
		$namespaceConf = (new inc_plugin_tplt())->getConf('namespace');

		if (preg_match('/\:{2,}/', $name, $match) != 0)	// 不允许连写冒号 · Consecutive colons are not allowed
			return '';
			// 注：如果使用了 cleanID() 函数的话，可以不检测连写冒号

		
		if (substr($namespaceConf, 0, 1) == ':') {
			$defaultNamespace = substr($namespaceConf, 1);
		} else {
			$defaultNamespace = $namespaceConf;
		}

		if (substr($name, 0, 1) == ':') {
			$name = substr($name, 1);
		} else {
			if ($namespaceConf == '') {
				$name = $name;
			} else {
				$name = $namespaceConf . ':' . $name;
			}
		}

		return cleanID($name);	// 清除大写字母以及特殊符号
	}
}