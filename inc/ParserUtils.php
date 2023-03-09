<?php
/**
 * DokuWiki plugin tplt (action component, parser utilities) · DokuWiki tplt 插件（动作模块，解析器实用程序）
 *
 * @license	GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author	AlloyDome
 * 
 * @version 0.4.1 (2023-3-7)
 * @since	0.3.1 (2021-11-30)
 */

namespace dokuwiki\lib\plugins\tplt\inc;

use dokuwiki\Parsing\Parser;

if(!defined('DOKU_INC'))
	die();	// Must be run within Dokuwiki · 必须在 Dokuwiki 下运行

require_once(DOKU_PLUGIN . 'tplt/inc/pluginClass.php');

class ParserUtils {

	// Constants · 常量
	// ----------------------------------------------------------------
	   
	protected const START_OR_END = array('start', 'end');
	protected const ARG_VALUE_ESCAPE_MACROS = array(
		'~~!~~' => '|',
		'~~EQ~~' => '=',
		'~~SP~~' => ' ',
		'~~BR~~' => "\n",
		'~~DQUO~~' => '"',
		'~~TILDE~~' => '~',
		'~~DTILDE~~' => '~~'
	);	// Escaping characters in argument values · 用于参数值的转义字符

	protected const LEXER_PATTERNS = array(
		'nowiki' => array(
			'name' => 'nowiki',
			'allowEnterFromRoot' => true,	'allowEnterFrom' => array('tplt', 'arg'),
			'isCouple' => true,				'allowSelfNest' => false,
			'patterns' => array(
				array('start' => '%%', 							'end' => '%%', 			'isRegex' => false),
				array('start' => '<nowiki>', 					'end' => '</nowiki>', 	'isRegex' => false),
				array('start' => '<html>', 						'end' => '</html>', 	'isRegex' => false),
				array('start' => '<HTML>', 						'end' => '</HTML>', 	'isRegex' => false),
				array('start' => '<php>', 						'end' => '</php>', 		'isRegex' => false),
				array('start' => '<PHP>', 						'end' => '</PHP>', 		'isRegex' => false),
				array('start' => '/<code\b(?=.*<\/code>)/s', 	'end' => '/<\/code>/', 	'isRegex' => true),
				array('start' => '/<file\b(?=.*<\/file>)/s', 	'end' => '/<\/file>/', 	'isRegex' => true),
			)
		),
		'tplt' => array(
			'name' => 'tplt',
			'allowEnterFromRoot' => true,	'allowEnterFrom' => array(),
			'isCouple' => true,				'allowSelfNest' => true,
			'patterns' => array(
				array('start' => '[|', 'end' => '|]', 'isRegex' => false)
			)
		),
		'delimiter' => array(
			'name' => 'delimiter',
			'allowEnterFromRoot' => false,	'allowEnterFrom' => array('tplt'),
			'isCouple' => false,
			'patterns' => array(
				array('selfClosing' => '/(?<!\[)\|(?!\])/', 'isRegex' => true)
			)
		),
		'arg' => array(
			'name' => 'arg',
			'allowEnterFromRoot' => true,	'allowEnterFrom' => array('tplt'),
			'isCouple' => true,				'allowSelfNest' => false,
			'patterns' => array(
				array('start' => '/\{\{\{(?!\{)/', 	'end' => '/(?<!\})\}\}\}/', 'isRegex' => true)
			)
		),
		'quoted' => array(
			'name' => 'quoted',
			'allowEnterFromRoot' => false,	'allowEnterFrom' => array('tplt'),
			'isCouple' => false,
			'patterns' => array(
				array('selfClosing' => '/(?<!")"([^"]|"")+?"(?!")/', 'isRegex' => true),
			)
		),
		'equal' => array(
			'name' => 'equal',
			'isCouple' => false,
			'allowEnterFromRoot' => false,
			'allowEnterFrom' => array('tplt', 'arg'),
			'patterns' => array(
				array('selfClosing' => '=', 'isRegex' => false),
			)
		),
	);

	// Properties · 属性值
	// ----------------------------------------------------------------

	protected $pageStack;			// Page stack to prevent endless recursion when including templates	 · 页面堆栈（防止模板无限递归调用）
	protected $templateTextCache;	// Cache of template plaintext data									 · 模板文本缓存
	protected $templateSyntaxCache;	// Cache of template syntax structures after parsing				 · 模板语法结构缓存
	protected $maxNestLevelConf;	// Maximum nesting level of template embedding (a template can embed other templates)
									//  · 
									// 最大嵌套层数（因为一个模板内部可以再调用其他模板）
	protected $strposMap;

	/**
	 * @version	0.4.0 (2023-3-5)
	 * @since	0.4.0 (2023-3-5)
	 */
	public function __construct() {
		$this->pageStack = array();
		$this->templateTextCache = array();
		$this->templateSyntaxCache = array();
		$this->maxNestLevelConf = (new inc_plugin_tplt())->getConf('maxNestLevel');
	}

	// ----------------------------------------------------------------

	/**
	 * tpltMainHandler($text)
	 * Main handler · 主处理函数
	 * 
	 * @version	0.4.1 (2023-3-7)
	 * @since	0.1.0 (2021-4-29)
	 * 
	 * @author	AlloyDome
	 * 
	 * @param	string	$text			raw Wiki code		 · 原始 Wiki 代码
	 * @param	array	$incomingArgs	incoming arguments	 · 传入参数
	 * 
	 * @return	string	$expandedText	Code after expanding of template etc. · 将模板等展开后的代码
	 * 
	 */
	public function tpltMainHandler($text, $incomingArgs = array()) {
		// U+007F (DEL) has a special usage · U+007F 字符（␡）有特殊用途
		str_replace("\x7f", '?', $text);

		// Nesting level inspection
		// Return the raw text if the nesting level overruns
		//  · 
		// 检测嵌套层数
		// 如果超过最大嵌套层数，则返回文本本身
		if ($this->maxNestLevelConf != 0 && count($this->pageStack) > $this->maxNestLevelConf) {
			return $text;
		}

		$isRootPage = (count($this->pageStack) > 0) ? false : true;

		// Cache the syntax structures, do lexical analysis and parse
		// Load directly if syntax structure cached
		// If not cached, do lexical analysis and parse, and cache the syntax structures (root page will not be cached)
		// · 
		// 缓存模板语法结构及词法、语法解析
		// 如语法结构已缓存，直接取用
		// 如还未缓存，进行词法和语法解析，并缓存语法结构（根页面不缓存）
		if (!$isRootPage && array_key_exists(end($this->pageStack), $this->templateSyntaxCache)) {
			$syntaxTree = $this->templateSyntaxCache[end($this->pageStack)];
		} else {
			$tokens = self::tpltLexer($text);
			list($syntaxTree, $tokenLengths) = self::tpltParser($tokens);

			if (!$isRootPage) {
				$this->templateSyntaxCache[end($this->pageStack)] = $syntaxTree;
			}
		}
		
		// Expand templates, parser functions, and argument placeholders · 展开模板、解析器函数和参数占位符
		$expandedText = $this->tpltExpander($syntaxTree, $incomingArgs, $tokenLengths, $isRootPage);

		if ($isRootPage) {
			// Note: To avoid range map exposure, there are 2 line breaks before "\x7f~~STRPOSMAP~~\x7f",
			//       because some parser modes take "\n" as the exit pattern (e.g. Listblock) 
			//  · 
			// 注：“\x7f~~STRPOSMAP~~\x7f” 前面有两个换行符，是为了防止章节编辑范围对照表被原样输出到渲染结果中，
			//     因为有些解析模式会以 “\n” 作为结束标记（例如 Listblock）
			$expandedText .= "\n\n\x7f~~STRPOSMAP~~\x7f\n" . serialize($this->strposMap) . "\n\x7f~~ENDSTRPOSMAP~~\x7f\n";
		}

		return $expandedText;
	}

	// Lexer · 词法解析器
	// ----------------------------------------------------------------

	/**
	 * tpltLexer($text, $parserMode)
	 * 词法分析器 · Lexer
	 * 
	 * @version	0.1.0 (2021-4-29)
	 * @since	0.1.0 (2021-4-29)
	 * 
	 * @author	AlloyDome
	 * 
	 * @param	string	$text	Raw Wiki code · 原始 Wiki 代码
	 * 
	 * @return	array	$tokens	Tokens after lexical analysis · 解析后的标示片段
	 */
	public static function tpltLexer($text) {
		$patternArray = self::LEXER_PATTERNS;
		
		$reducedText = $text;
		$scanPosition = 0;
		$tokens = array();

		$nestLevel = array();
		foreach ($patternArray as $patternGroup) {
			$groupNestLevel = array();
			foreach ($patternGroup['patterns'] as $pattern)
				$groupNestLevel[] = 0;
			$nestLevel[$patternGroup['name']] = $groupNestLevel;
		};
		$nestStack = array();

		// Cache the matched positions · 匹配位置缓存
		$matchPosCache = array();
		foreach ($patternArray as $patternGroup) {
			$matchPosCache[$patternGroup['name']] = array();
			foreach ($patternGroup['patterns'] as $orderNo => $pattern) {
				$matchPosCache[$patternGroup['name']][$orderNo] = array();
				if ($patternGroup['isCouple'] != false) {
					$matchPosCache[$patternGroup['name']][$orderNo]['start'] = null;
					$matchPosCache[$patternGroup['name']][$orderNo]['end'] = null;
				} else {
					$matchPosCache[$patternGroup['name']][$orderNo]['selfClosing'] = null;
				}
			}
		};

		// Current nest · 嵌套位置
		$currentNest = false;
		$currentNestPatternOdr = false;

		// Lexical analysis · 词法分析
		while ($reducedText != '') {
			$firstPosOfPatterns = self::findFirstPosOfPatterns($reducedText, $patternArray, $nestLevel, $currentNest, $currentNestPatternOdr, $matchPosCache);
			
			if (empty($firstPosOfPatterns)) {
				$tokens[] = array('type' => 'plainText', 'text' => $reducedText);
				break;
			} else {
				ksort($firstPosOfPatterns);
				$firstPattern = array('position' => key($firstPosOfPatterns), 'pattern' => reset($firstPosOfPatterns));
				$unmachedTextBeforeTheFirstPattern = substr($reducedText, 0, $firstPattern['position']);

				if ($unmachedTextBeforeTheFirstPattern !== '') {
					$tokens[] = array('type' => 'plainText', 'text' => $unmachedTextBeforeTheFirstPattern);
				}
				$tokens[] = array(
					'type' => $firstPattern['pattern']['name'], 
					'startOrEnd' => $firstPattern['pattern']['startOrEnd'], 
					'text' => $firstPattern['pattern']['matchedPattern']
				);
				
				// Note: Self-closing patterns should not be in nesting stack · 注：如果是自关闭标识，则不计入嵌套堆栈中
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
				if (empty($nestStack)) {
					$currentNest = false;
					$currentNestPatternOdr = false;
				} else {
					list('pattern' => $currentNest, 'orderNo' => $currentNestPatternOdr) = end($nestStack);
				}

				$reducedText = substr($reducedText, $firstPattern['position'] + strlen($firstPattern['pattern']['matchedPattern']));

			}
		}
		return $tokens;
	}

	/**
	 * findFirstPosOfPatterns($text, $patternArray, $nestLevel, $currentNest, $currentNestPatternOdr)
	 * Find the first positions of patterns · 寻找各标识第一次出现的位置
	 * 
	 * @version	0.4.0 (2023-3-5)
	 * @since	0.3.0 (2021-11-30)
	 * 
	 * @author	AlloyDome
	 * 
	 * @param	string	$text				raw Wiki code			 · 原始 Wiki 代码
	 * @param	array	$patternArray		Array of patterns		 · 标识字符串的存储序列
	 * @param	array	$modesOfStartAndEnd	Modes of start and end	 · 开始与结束模式
	 * 
	 * @return	array						The first positions of patterns · 各标识第一次出现的位置
	 */
	protected static function findFirstPosOfPatterns($text, $patternArray, $nestLevel, $currentNest, $currentNestPatternOdr, &$matchPosCache = null) {
		$firstPosOfPatterns = array();

		// Go through each pattern type · 遍历各个标识类型
		foreach ($patternArray as $patternGroup) {
			// Pattern group name and whether they are paired used · 标识种类、标识是否成对
			$patternGroupName = $patternGroup['name'];
			$isCouple = $patternGroup['isCouple'];

			// Whether self-nesting is allowed
			// Self-closing patterns do not have nesting
			// · 
			// 判断是否允许自嵌套
			// 自关闭标识不存在嵌套
			if ($isCouple != true) {
				$allowSelfNest = false;
			} else {
				$allowSelfNest = $patternGroup['allowSelfNest'];
			}

			// Go through each pattern of current type · 遍历当前标识类型里的各个标识字符串
			foreach ($patternGroup['patterns'] as $orderNo => $pattern) {

				// Flags for whether the start and end patterns should be matched · 标识开始标记及结束标记是否允许匹配的标记变量
				$allowMatch = array(
					'start' => false,	// Also for self-closing patterns · 也兼做自关闭标识的允许匹配标识
					'end' => false
				);

				// Determine what patterns can be matched · 判断哪些开闭类型的标识字符串可以被匹配
				if ($currentNest === false) {							// 1. 如果没有位于任何嵌套内
					if ($patternGroup['allowEnterFromRoot'] == true) {	//    如果当前种类的标识允许从“根部”进行匹配
						$allowMatch['start'] = true;					//    则只匹配开始标记
					}
				} else {												// 2. 否则如果位于嵌套内
					if ($currentNest == $patternGroupName) {			// 2.1. 如果位于当前种类标识的自身嵌套内
						if (
							$allowSelfNest == false && 
							$orderNo == $currentNestPatternOdr
						) {												// 2.1.1. 如果该种类的标识不允许自嵌套，且位于自身嵌套内且开始标记为当前所循环到的标记
							$allowMatch['end'] = true;					//        则只匹配其对应的结束标记，否则什么也不做
						} else {										// 2.1.2. 否则如果允许自嵌套
							if ($orderNo == $currentNestPatternOdr) {	// 2.1.2.1. 如果位于自身嵌套内且开始标记为当前所循环到的标记
								$allowMatch['start'] = true;
								$allowMatch['end'] = true;				//          则正常匹配开始与结束标记
							} else {									// 2.1.2.2. 否则如果位于自身嵌套内但开始标记不是当前所循环到的标记
								$allowMatch['start'] = true;			//          则只匹配其对应的开始标记，否则什么也不做
							}
						}
					} elseif (
						!empty($patternGroup['allowEnterFrom']) && 
						in_array($currentNest, $patternGroup['allowEnterFrom'])
					) {													// 2.2. 否则如果位于其他嵌套内，且该标识允许包含在上一级嵌套内
						$allowMatch['start'] = true;					//      则只匹配开始标记，否则什么也不做
					}
				}

				// Array for matched patterns and their positions · 用于存放匹配到的标识出现的位置以及匹配的字符串的数组
				$matchedList = array('start' => array(false, false), 'end' => array(false, false));
				$startOrSelfclosing = ($isCouple == true) ? 'start' : 'selfClosing';

				// Matching · 进行匹配
				if ($allowMatch['start'] == true) {
					// If this pattern never appears, skip it (same below) · 如果标识从未出现，则直接跳过匹配，下同
					if ($matchPosCache[$patternGroupName][$orderNo][$startOrSelfclosing] !== false) {
						$matchedList['start'] = self::patternMatch($text, $pattern[$startOrSelfclosing], $pattern['isRegex']);
					}
				}
				if ($allowMatch['end'] == true) {
					if ($matchPosCache[$patternGroupName][$orderNo]['end'] !== false) {
						$matchedList['end'] = self::patternMatch($text, $pattern['end'], $pattern['isRegex']);
					}
				}

				// Cache matching results · 缓存匹配结果
				if ($allowMatch['start'] == true && $matchedList['start'][0] === false) {
					$matchPosCache[$patternGroupName][$orderNo][$startOrSelfclosing] = false;
				}
				if ($allowMatch['end'] == true && $matchedList['end'][0] === false) {
					$matchPosCache[$patternGroupName][$orderNo]['end'] = false;
				}

				// Handle the case of matching both start and end patterns · 对于同时对开始标识和结束标识进行匹配情况的处理
				$startOrEnd = false;	// Indicate whether the start or end patterns are taken · 表明到底取用开始标识还是结束标识
				if ($matchedList['start'][0] !== false) {
					if ($matchedList['end'][0] !== false) {
						// Take the pattern in front when the start and end patterns are both matched
						// But if both patterns have the same position, take the end pattern first to make sure it can self-close
						// This occurs when the start and end patterns are the same, 
						// e.g. one of the "nowiki" patterns, both the start and end are "%%"
						//  · 
						// 当开始标识和结束标识都匹配到了，谁在前就采用谁
						// 但如果两者出现位置相同，则优先考虑结束标识，防止无法自关闭
						// 这种情况出现在开始标识和结束标识相同的情形，例如 “nowiki” 的其中一种标识，开始和结束都是 “%%”
						if ($matchedList['end'][0] <= $matchedList['start'][0]) {
							$startOrEnd = 'end';
						} else {
							$startOrEnd = 'start';
						}
					} else {
						$startOrEnd = 'start';	// Only start pattern is matched · 只匹配到了开始标识
					}
				} else {
					if ($matchedList['end'][0] !== false) {
						$startOrEnd = 'end';	// Only end pattern is matched · 只匹配到了结束标识
					}
				}

				// The matching result taken finally (sensitive for start and self-closing) · 最终的标识开闭形式（区分开始标识和自关闭标识）
				$actualStartOrEnd = $startOrEnd;
				if ($startOrEnd == 'start') {
					list($patternPos, $matchedText) = $matchedList['start'];
					$actualStartOrEnd = $startOrSelfclosing;
				} elseif ($startOrEnd == 'end') {
					list($patternPos, $matchedText) = $matchedList['end'];
				}

				// Update array · 更新数组
				if ($startOrEnd !== false) {
					$firstPosOfPatterns[$patternPos] = array(
						'name' => $patternGroupName, 
						'orderNo' => $orderNo, 
						'startOrEnd' => $actualStartOrEnd, 
						'matchedPattern' => $matchedText
					);
				}	// TODO: This array can only store the most front pattern · 数组可以只存储匹配到的最靠前的标识
			}
		}
		return $firstPosOfPatterns;
	}

	/**
	* patternMatch($text, $pattern, $isRegex)
	* Find the positions of a pattern and the matched text · 查找一个标识的位置及匹配片段
	* 
	* @version	0.2.0 (2021-7-6)
	* @since	0.1.0 (2021-4-29)
	* 
	* @author	AlloyDome
	* 
	* @param	string	$text		Raw Wiki code			 · 原始 Wiki 代码
	* @param	string	$pattern	Pattern					 · 标识字符串
	* @param	bool	$isRegex	Use regex match or not	 · 是否采用正则匹配
	* 
	* @return	array				The first position of the pattern · 第一次出现的位置
	*/
	protected static function patternMatch($text, $pattern, $isRegex) {
		if ($isRegex == false) {
			$findResult = strpos($text, $pattern);
			if ($findResult !== false) {
				return array($findResult, $pattern);
			}
		} elseif ($isRegex == true) {
			$findResult = preg_match($pattern, $text, $match, PREG_OFFSET_CAPTURE);
			if ($findResult != 0) {
				return array($match[0][1], $match[0][0]);
			}
		}
		return array(false, false);
	}

	// Parser · 语法解析器
	// ----------------------------------------------------------------

	/**
	 * tpltParser($tokens)
	 * 语法分析器 · Parser
	 * 
	 * @version	0.4.0 (2023-3-5)
	 * @since	0.4.0 (2023-3-5)
	 * 
	 * @author	AlloyDome
	 * 
	 * @param	array	$tokens			Tokens after lexical analysis · 词法解析得到的标示片段
	 * @param	bool	$isRootPage
	 * 
	 * @return	array					Syntax structure after parsing · 解析后的语法结构
	 */
	protected static function tpltParser($tokens) {
		$syntaxTree = array(0 => array());
		$stackLevel = 0;
		$stack = array('*');
		$enableEqual = false;

		// Lengths of tokens · 标示片段长度
		$tokenLengths = array(0 => array());

		// Go through tokens · 遍历标示片段
		foreach ($tokens as $token) {
			$currentStack = end($stack);

			// 生成语法结构 · Generate a syntax structure
			$loadedInTree = false;
			if ($token['type'] == 'tplt' && $token['startOrEnd'] == 'start') {	// [|
				if ($currentStack != 'a') {	// Cannot call template in "{{{ }}}" · “{{{ }}}” 内部不能调用模板
					$stack[] = 't';
					$stackLevel++;
					$syntaxTree[$stackLevel][]  = array('type' => 'tplt_start', 'value' => $token['text']);
					$tokenLengths[$stackLevel][] = strlen($token['text']);
					$loadedInTree = true;
				}
			} elseif ($token['type'] == 'tplt' && $token['startOrEnd'] == 'end') {	// |]
				if ($currentStack == 't') {	// Paired "|]" · 已配对的 “|]”
					$syntaxTree[$stackLevel][]  = array('type' => 'tplt_end', 'value' => $token['text']);
					$tokenLengths[$stackLevel][] = strlen($token['text']);
					$stackLevel--;
					$syntaxTree[$stackLevel][]  = array('type' => 'tplt',     'value' => array_pop($syntaxTree));
					$tokenLengths[$stackLevel][] = array_sum(array_pop($tokenLengths));
					array_pop($stack);
					$loadedInTree = true;
				}
			} elseif ($token['type'] == 'delimiter') {	// |
				if ($currentStack == 't') {	// "|" can only in "[| |]" · “|” 只能在 “[| |]” 中使用
					$syntaxTree[$stackLevel][]  = array('type' => 'delimiter', 'value' => $token['text']);
					$tokenLengths[$stackLevel][] = strlen($token['text']);
					$loadedInTree = true;
				}
			} elseif ($token['type'] == 'arg' && $token['startOrEnd'] == 'start') {	// {{{
				if ($currentStack != 'a') {	// Argument placeholder are not allowed to nest · 参数占位符内部不能再包括另一个参数
					$stack[] = 'a';
					$stackLevel++;
					$syntaxTree[$stackLevel][]  = array('type' => 'arg_start', 'value' => $token['text']);
					$tokenLengths[$stackLevel][] = strlen($token['text']);
					$loadedInTree = true;
				}
			} elseif ($token['type'] == 'arg' && $token['startOrEnd'] == 'end') {	// }}}
				if ($currentStack == 'a') {	// Paired "}}}" · 已配对的 “}}}”
					$syntaxTree[$stackLevel][]  = array('type' => 'arg_end', 'value' => $token['text']);
					$tokenLengths[$stackLevel][] = strlen($token['text']);
					$stackLevel--;
					$syntaxTree[$stackLevel][]  = array('type' => 'arg',     'value' => array_pop($syntaxTree));
					$tokenLengths[$stackLevel][] = array_sum(array_pop($tokenLengths));
					array_pop($stack);
					$loadedInTree = true;
				}
			} elseif ($token['type'] == 'equal') {	// =
				if ($enableEqual) {
					$syntaxTree[$stackLevel][]  = array('type' => 'equal', 'value' => $token['text']);
					$tokenLengths[$stackLevel][] = strlen($token['text']);	// FIXME: It's always 1	· 长度永远是 1
					$loadedInTree = true;
				}
			} /* elseif ($token['type'] == 'escape') {	// ~~...~~, Macros for escaping in argument values · 供参数值使用的转义宏
				if ($currentStack == 't') {	// Escaping macros can only in "[| |]" · 转义宏只能在 “[| |]” 中使用
					$syntaxTree[$stackLevel][] = array('type' => 'escape', 'value' => $token['text']);
					$tokenLengths[$stackLevel][] = strlen($token['text']);
					$loadedInTree = true;
				}
			} */ elseif ($token['type'] == 'quoted') {	// "..."
				if ($currentStack == 't') {	// `" ... "` can only in "[| |]" · “" ... "” 只能在 “[| |]” 中使用
					$syntaxTree[$stackLevel][] = array('type' => 'quoted', 'value' => $token['text']);
					$tokenLengths[$stackLevel][] = strlen($token['text']);
					$loadedInTree = true;
				}
			} 
			if (!$loadedInTree) {	// Regard the token as plaintext if it isn't loaded in the syntax structure · 标识片段未被装入语法结构的情况，视为纯文本
				$syntaxTree[$stackLevel][] = array('type' => 'plainText', 'value' => $token['text']);
				$tokenLengths[$stackLevel][] = strlen($token['text']);
			}

			// Update the use permission of "=" · 更新 “=” 的允许使用范围
			if (
				($token['type'] == 'tplt' && $token['startOrEnd'] == 'start')	// [| （FIXME：The first bucket after "[|" are used for template or parser funcion name; equal sign may not be required · “[|” 后的第一个位置用来填模板名或函数名，可能不需要等号）
				|| ($token['type'] == 'arg' && $token['startOrEnd'] == 'start') // {{{
				|| $token['type'] == 'delimiter'								// |
			) {
				$enableEqual = true;
			} elseif (
				($token['type'] == 'tplt' && $token['startOrEnd'] == 'end')		// |]
				|| ($token['type'] == 'arg' && $token['startOrEnd'] == 'end')	// }}}
				|| $token['type'] == 'equal' 									// =
				|| $token['type'] == 'escape' 									// ~~...~~
				|| $token['type'] == 'quoted' 									// "..."
			) {
				$enableEqual = false;
			}
		}

		// Fallback the unfinished template or argument placeholder (i.e. "[|" / "{{{" without "|]" / "}}}")
		//  ·
		// 对未写完的模板和参数占位符的内容回退（就是只有 “[|” 或 “{{{”，没有 “|]” 或 “}}}”）
		if (count($syntaxTree) > 1) {
			for ($i = count($syntaxTree) - 1; $i > 0; $i--) {
				for ($j = 0; $j < count($syntaxTree[$i]); $j++) {
					$syntaxTree[$i][$j]['type'] = 'plainText';
				}
				$syntaxTree[$i - 1]  = array_merge($syntaxTree[$i - 1], array_pop($syntaxTree));
				$tokenLengths[$i - 1] = array_merge($tokenLengths[$i - 1], array_pop($tokenLengths));
			}
		}

		return array($syntaxTree[0], $tokenLengths[0]);
	}

	// Expander · 展开器
	// ----------------------------------------------------------------

	/**
	 * tpltExpander(&$syntaxTree, $incomingArgs)
	 * Expander for templates, parser functions and argument placeholders · 模板、解析器函数与参数占位符展开器
	 * 
	 * @version	0.4.0 (2023-3-5)
	 * @since	0.4.0 (2023-3-5)
	 * 
	 * @author	AlloyDome
	 *
	 * @param	array	&$syntaxTree	Syntax structure after parsing	 · 解析后的语法结构
	 * @param	array	$incomingArgs	Incoming arguments				 · 传入参数
	 * @param	bool	$isRootPage
	 * 
	 * @return	string					Text after expansion · 替换过参数的模板内容文本
	 */
	protected function tpltExpander(&$syntaxTree, $incomingArgs, &$tokenLengths, $isRootPage = false) {
		// Generate the original character positions of string range · 生成原始文本的字符位置表
		if ($isRootPage) {
			$this->strposMap['istplt'] = array();
			$this->strposMap['ori'] = array();
			$posCount = 0;
			foreach ($tokenLengths as $i => &$tokenLength) {
				if ($syntaxTree[$i]['type'] == 'plainText') {
					$this->strposMap['istplt'][] = false;
				} elseif ($syntaxTree[$i]['type'] == 'arg' || $syntaxTree[$i]['type'] == 'tplt') {
					$this->strposMap['istplt'][] = true;
				}
				$this->strposMap['ori'][] = $posCount;
				$posCount += $tokenLength;
			}
			$this->strposMap['istplt'][] = false;
			$this->strposMap['ori'][] = $posCount;	// The last element is the length of whole text · 最后一个元素是文本的总长度
		}

		// Expand templates, parser functions and argument placeholders · 展开模板、解析器函数与参数占位符
		$textArray = array();
		$nodeTextLengths = array();
		foreach ($syntaxTree as $syntaxTreeNode) {
			switch ($syntaxTreeNode['type']) {
				case 'arg': 
					$textArray[] = self::expanderForArgs($syntaxTreeNode['value'], $incomingArgs);
				break;
				
				case 'tplt': 
					$textArray[] = $this->expanderForTemplatesAndParserFunctions($syntaxTreeNode['value'], $incomingArgs);
				break;
				
				case 'plainText': 
					$textArray[] = $syntaxTreeNode['value'];
				break;
			}

			$nodeTextLengths[] = strlen(end($textArray));
		}

		// Generate the character positions of string range after expanding · 生成模板等展开后文本的字符位置表
		if ($isRootPage) {
			$this->strposMap['pro'] = array();
			$posCount = 0;
			foreach ($nodeTextLengths as $i => &$nodeTextLength) {
				$this->strposMap['pro'][] = $posCount;
				$posCount += $nodeTextLength;
			}
			$this->strposMap['pro'][] = $posCount;
		}

		return implode('', $textArray);
	}

	/**
	 * expanderForArgs(&$syntaxTree, $incomingArgs)
	 * 参数占位符展开器
	 * 
	 * @version	0.4.0 (2023-3-5)
	 * @since	0.4.0 (2023-3-5)
	 * 
	 * @author	AlloyDome
	 * 
	 * @param	string	&$syntaxTree	Syntax structure after parsing	 · 解析后的语法结构
	 * @param	string	$incomingArgs	Incoming arguments				 · 传入参数
	 * 
	 * @param	array					Text after expansion · 替换过参数的模板内容文本
	*/
	protected static function expanderForArgs(&$syntaxTree, $incomingArgs) {
		$findEqual = false;
		$argName = false;
		$defaultValue = false;
		foreach ($syntaxTree as $syntaxTreeNode) {
			if ($syntaxTreeNode['type'] == 'plainText') {
				if (!$findEqual && $argName === false) {
					$argName = trim($syntaxTreeNode['value']);
				} elseif ($findEqual && $defaultValue === false) {
					$defaultValue = trim($syntaxTreeNode['value']);
					break;
				}
			} elseif ($syntaxTreeNode['type'] == 'equal') {
				$findEqual = true;	// Default value is considered to be set when a equal sign is found · 找到等号视为该占位符设置了默认值
			}
		}

		// Replace a argument placeholder by incoming argument or the default value · 用传入参数或参数默认值替换参数占位符
		// +--------------------------++--------------------------+----------------------------------+
		// |                          || Incoming argument exists | Incoming argument does not exist |
		// |                          ||  ·                       |  ·                               |
		// |                          || 传入参数存在             | 传入参数不存在                   |
		// +==========================++==========================+==================================+
		// | Default value is set     ||                          | Replace with default value       |
		// |  ·                       || Replace with value of    |  ·                               |
		// | 设置了默认值             || the incoming argument    | 用默认值替换                     |
		// +--------------------------++  ·                       +----------------------------------+
		// | Default value is not set || 用传入参数值替换         | Return original text             |
		// |  ·                       ||                          |  ·                               |
		// | 未设置默认值             ||                          | 返回原始字符串                   |
		// +--------------------------++--------------------------+----------------------------------+
		if ($argName !== false && self::arrayKeyExists($argName, $incomingArgs)) {	// Incoming argument exists · 传入参数存在
			return $incomingArgs[$argName];
		} else {
			if (!$findEqual) {	// Incoming argument and default value do not exist · 传入参数不存在且未指定默认值
				$text = '';
				foreach ($syntaxTree as $syntaxTreeNode) {
					$text .= $syntaxTreeNode['value'];
				}
				return $text;
			} else {
				if ($defaultValue !== false) {
					return $defaultValue;
				} else {
					return '';	// The default value is regarded as blank if not filled in · 默认值未填，视为空白的默认值
				}
			}
		}
	}

	/**
	 * expanderForTemplatesAndParserFunctions($syntaxTree, $incomingArgs)
	 * Expander for templates and parser functions · 模板和解析器函数展开器
	 * 
	 * @version	0.4.0 (2023-3-5)
	 * @since	0.4.0 (2023-3-5)
	 * 
	 * @author	AlloyDome
	 *
	 * @param	string	&$syntaxTree	Syntax structure after parsing	 · 解析后的语法结构
	 * @param	string	$incomingArgs	Incoming arguments				 · 传入参数
	 * 
	 * @return	string	$text			Text after expansion · 替换过参数的模板内容文本
	 */
	protected function expanderForTemplatesAndParserFunctions($syntaxTree, $incomingArgs) {
		$argsAndValues = array();
		$anonymousArgCounter = 1;	// Anonymous argument counter (from 1, not 0) · 匿名参数计数器（从 1 开始，不是 0）

		$templateName = false;
		$findEqual = false;
		$argPart1 = array();
		$argPart2 = array();
		foreach ($syntaxTree as $syntaxTreeNode) {
			switch ($syntaxTreeNode['type']) {
				// It is considered that the the bucket is divided into argument name and value if a equal sign is found
				// (but it may be an anonymous argument because there can the front of the equal sign can be blank)
				//  ·
				// 找到等号视为该参数分参数名和参数值两部分（但也有可能是匿名参数，因为等号前面可能是空白的）
				case 'equal': 
					$findEqual = true;	
				break;
				
				case 'delimiter':
				case 'tplt_end': 
					if ($templateName !== false) {
						if (!$findEqual) {
							// Anonymous arguments are automatically named · 匿名参数，自动以数字命名
							for(; self::arrayKeyExists((string)$anonymousArgCounter, $argsAndValues); $anonymousArgCounter++);	// Prevent anonymous argument overwrite (same below) · 可能已有用数字显式命名的参数，应当跳过避免覆盖，下同
							$argsAndValues[(string)$anonymousArgCounter] = $argPart1;
						} else {
							// Equal sign found (anonymous if the front of the equal sign is blank) · 找到等号（可能是非匿名参数，也可能是等号左边空白，视为匿名参数）
							if (array_key_exists(0, $argPart1) && trim($argPart1[0]['value'])) {
								$argsAndValues[trim($argPart1[0]['value'])] = $argPart2;	// Non-anonymous argument · 非匿名参数
							} else {
								for(; self::arrayKeyExists((string)$anonymousArgCounter, $argsAndValues); $anonymousArgCounter++);
								$argsAndValues[(string)$anonymousArgCounter] = $argPart2;	// Anonymous if the front of the equal sign is blank; notice that the argument value is not $argPart1 · 等号左边空白，视为匿名参数，注意参数值不是 $argPart1
							}
						}
					} else {
						$templateName = (array_key_exists(0, $argPart1) && is_string($argPart1[0]['value'])) ? trim($argPart1[0]['value']) : '';	// FIXME: Fatal errors may occur · 可能会出现致命错误
					}
					$findEqual = false;
					$argPart1 = array();
					$argPart2 = array();
				break;
				
				case 'tplt_start': 
				break;
				
				default: 
					if (!$findEqual) {
						$argPart1[] = $syntaxTreeNode;
					} else {
						$argPart2[] = $syntaxTreeNode;
					}

			}
		}

		// Argument value preprocess (strip whitespaces at both ends, replace escaping symbols and handle quoted text)
		//  ·
		// 参数值预处理（去除两端空格、替换转义符号，以及对引号包围的文本进行处理）
		self::argValuePreprocessor($argsAndValues);

		// Parse each argument · 解析各个参数
		foreach ($argsAndValues as &$argAndValue) {
		$argAndValue = $this->tpltExpander($argAndValue, $incomingArgs, $tokenLengths /* <- useless but must be passed in */);
		}

		// Expand templates and parser functions · 展开模板和解析器函数
		$text = $this->rendererForTemplatesAndParserFunctions($templateName, $argsAndValues, $incomingArgs);

		return $text;
	}

	/**
	 * argValuePreprocessor(&$argsAndValues)
	 * 
	 * Argument value preprocess (strip whitespaces at both ends, replace escaping symbols and handle quoted text)
	 *  · 
	 * 参数值预处理（去除两端空格、替换转义符号，以及对引号包围的文本进行处理）
	 * 
	 * @version	0.4.0 (2023-3-5)
	 * @since	0.4.0 (2023-3-5)
	 * 
	 * @author	AlloyDome
	 * 
	 * @param	array	&$argsAndValues	Arguments and values · 参数名与参数值
	*/
	protected static function argValuePreprocessor(&$argsAndValues) {
		foreach ($argsAndValues as &$argAndValue) {
			foreach ($argAndValue as &$syntaxTreeNode) {
				switch ($syntaxTreeNode['type']) {
					// Handle plaintext (strip whitespaces at both ends and replace escaping symbols)
					//  · 
					// 处理普通文本（去除两端空格、替换转义符号）
					case 'plainText': 
						$syntaxTreeNode['value'] = strtr(trim($syntaxTreeNode['value']), self::ARG_VALUE_ESCAPE_MACROS);
					break;
					
					// Handle quoted text (strip double quotes at both ends and replace `""` with `"`)
					//  · 
					// 处理被引号包围的文本（去除两侧双引号，以及将「""」替换成「"」）
					case 'quoted': 
						$syntaxTreeNode['type'] = 'plainText';
					#	$syntaxTreeNode['value'] = substr(strtr($syntaxTreeNode['value'], array('""' => '"')), 1, -1);
						$syntaxTreeNode['value'] = strtr(substr($syntaxTreeNode['value'], 1, -1), array('""' => '"'));
					break;
				}
			}
		}
	}

	/**
	 * rendererForTemplatesAndParserFunctions($templateName, $argsAndValues, $incomingArgs)
	 * 
	 * Renderer for templates and parser functions · 模板与解析器函数渲染器
	 * 
	 * @version	0.4.0 (2023-3-5)
	 * @since	0.4.0 (2023-3-5)
	 * 
	 * @author	AlloyDome
	 * 
	 * @param	string	$templateName	Name of a template or parser function							 · 模板或解析器函数名称
	 * @param	array	$argsAndValues	Arguments and values											 · 参数名与参数值
	 * @param	array	$incomingArgs	Incoming arguments from parent page (for parser functions use)	 · 从上级页面传入的参数（供某些解析器函数操作）
	 * 
	 * @return	array					Rendered text · 渲染后的文本
	*/
	protected function rendererForTemplatesAndParserFunctions($templateName, $argsAndValues, $incomingArgs) {
		if (!$templateName)
			return '';	// 如果名称为空，则直接返回一个空字符串 · Return an empty string if template name is blank
		if (substr(trim($templateName), 0, 1) == '#') {
			return $this->rendererForParserFunctions($templateName, $argsAndValues, $incomingArgs); // Parser function names begin with an "#" · 解析器函数名称以 “#” 开头
		} else {
			return $this->rendererForTemplates($templateName, $argsAndValues);
		}
	}

	/**
	 * rendererForParserFunctions($functionName, $argsAndValues, $incomingArgs)
	 * 
	 * Renderer for parser functions · 解析器函数渲染器
	 * 
	 * @version	0.4.0 (2023-3-5)
	 * @since	0.4.0 (2023-3-5)
	 * 
	 * @author	AlloyDome
	 * 
	 * @param	string	$templateName	Name of a template or parser function							 · 模板或解析器函数名称
	 * @param	array	$argsAndValues	Arguments and values											 · 参数名与参数值
	 * @param	array	$incomingArgs	Incoming arguments from parent page (for parser functions use)	 · 从上级页面传入的参数（供某些解析器函数操作）
	 * 
	 * @return	array					Rendered text · 渲染后的文本
	*/
	protected function rendererForParserFunctions($functionName, $argsAndValues, $incomingArgs) {
		$functionName = substr(trim($functionName), 1);	// 解析器函数名 · Parser function name

		if (array_key_exists($functionName, PfList::$pfClassList)) {
			return PfList::$pfClassList[$functionName]->renderer($this, $argsAndValues, $incomingArgs);	// FIXME: Errors may occur · 可能会出错
		} else {
			return ' (' . $functionName. '?) ';
		}
	}

	/**
	 * rendererForTemplates($templateName, $argsAndValues)
	 * 
	 * Renderer for parser functions · 模板渲染器
	 * 
	 * @version	0.4.0 (2023-3-5)
	 * @since	0.4.0 (2023-3-5)
	 * 
	 * @author	AlloyDome
	 * 
	 * @param	string	$templateName	Name of a template or parser function	 · 模板或解析器函数名称
	 * @param	array	$argsAndValues	Arguments and values					 · 参数名与参数值
	 * 
	 * @return	array					Rendered text · 渲染后的文本
	*/
	protected function rendererForTemplates($templateName, $argsAndValues) {
		// Full name of the template (absolute address from the root namespace) · 模板的完整页面名（从根命名空间开始的绝对地址）
		$templateName = self::getTemplateName($templateName);
		
		// Load the template text
		// Load directly if the text cached
		// If not cached, get the template text and cache it
		// · 
		// 获取模板文本
		// 如文本已缓存，直接取用
		// 如还未缓存，先读取模板，然后缓存
		if (array_key_exists($templateName, $this->templateTextCache)) {
			$template = $this->templateTextCache[$templateName];
		} else {
			$template = self::getTemplate($templateName);
			$this->templateTextCache[$templateName] = $template;
		}

		// If not exist, return its link · 如果模板不存在，返回一个该模板的链接 (?)
		if (!$template) {
			return '%%[|%%[[' . $templateName . ']]%%|]%%';
		}

		if (in_array($templateName, $this->pageStack)) {
			return '';	// To prevent endless recursion · 防止无限递归
		} else {
			$this->pageStack[] = $templateName;
			$text = $this->tpltMainHandler($template, $argsAndValues);
			array_pop($this->pageStack);
			return $text;
		}
	}

	// Template page fetch · 模板内容获取程序
	// ----------------------------------------------------------------

	/**
	 * getTemplate($name)
	 * Get the text from template · 获取模板内的内容
	 * 
	 * By default, a page from namespace specified in $conf['namespace'] will be loaded
	 * To override this, prepend a colon to $name
	 *  · 
	 * 默认情况下，模板页面是来自 $conf['namespace'] 里面所设置的命名空间当中的。
	 * 若要覆盖默认的命名空间，请在页面名 $name 前面加一个半角冒号。
	 * 
	 * @version	0.0.1 (2021-1-5)
	 * @since	0.0.1 (2021-1-5)
	 * 
	 * @author	Vitalie Ciubotaru <vitalie@ciubotaru.tk>
	 * 
	 * @param	string	$name	Name of template page · 模板页面名
	 * 
	 * @return	string			Template text removed <noinclude> and <includeonly> · 模板内去除 “<noinclude>” 、 “<includeonly>” 标签后的原始代码
	 */
	public static function getTemplate($name) {
		$template = rawWiki($name);
		if (!$template) {
			return false;
		}
		$template = preg_replace('/<noinclude>.*?<\/noinclude>\n?/s', '', $template);
		$template = preg_replace('/<includeonly>\n?|<\/includeonly>\n?/', '', $template);
		return $template;
	}

	/** 
	 * getTemplateName($name)
	 * Get template name · 获得模板的名称
	 * 
	 * @version	0.1.1 (2021-5-4)
	 * @since	0.0.1 (2021-1-5)
	 * 
	 * @author	AlloyDome
	 * 
	 * @param	string	$name	未处理的模板名称 · Unprocessed template name
	 * 
	 * @return	string			处理后的模板名称 · Processed template name
	 */
	public static function getTemplateName($name) {
		$namespaceConf = (new inc_plugin_tplt())->getConf('namespace');

		if (preg_match('/\:{2,}/', $name, $match) != 0)	// Consecutive colons are not allowed · 不允许连写冒号
			return '';
			// Note: Consecutive colon inspection is unnecessary if cleanID() used · 注：如果使用了 cleanID() 函数的话，可以不检测连写冒号

		
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
	
		// Remove capital letters and symbols (except "_", "-" and ".") · 清除大写字母以及特殊符号（“_”、“-”、“.” 除外）
		return cleanID($name);	
	}

	// Utils
	// ----------------------------------------------------------------

	/**
	 * Maybe useless???
	*/
	protected static function arrayKeyExists($key, $array) {
		if (empty($array)) {
			return false;
		} else {
			return array_key_exists($key, $array);
		}
	}
}
