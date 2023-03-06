<?php
/**
 * DokuWiki tplt 插件（动作模块） · DokuWiki plugin tplt (action component)
 *
 * @license	GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author	AlloyDome
 * 
 * @version 0.4.0 (2023-3-5)
 * @since	0.3.0 (2021-11-30)
 */

 use dokuwiki\lib\plugins\tplt\inc\{PfAbstract, ParserUtils};

if(!defined('DOKU_INC'))
	die();	// 必须在 Dokuwiki 下运行 · Must be run within Dokuwiki

class tplt_parserfunc_optionArg extends PfAbstract {
	public function renderer(ParserUtils &$parser, $pfArgs, $incomingArgs) {
		if (!array_key_exists('1', $pfArgs) || !array_key_exists('2', $pfArgs)) {
			return '';
		} else {
			if (array_key_exists($pfArgs['1'], $incomingArgs)) {
				$options = explode(',', $incomingArgs[$pfArgs['1']]);
				foreach ($options as $key => $option) {
					$options[$key] = trim($option);
				}
				if (in_array($pfArgs['2'], $options))
				{
					return (array_key_exists('3', $pfArgs)) ? $pfArgs['3'] : '';
				} else {
					return (array_key_exists('4', $pfArgs)) ? $pfArgs['4'] : '';
				}
			} else {
				return (array_key_exists('4', $pfArgs)) ? $pfArgs['4'] : '';
			}
		}
	}
}

/**
 * 功能：
 * 将一个模板传入参数的内容视为多个选项（以逗号分隔），根据选项有无输出不同内容
 * 
 * 语法：
 * [|#isNonemptyArg|1=...|2=...|3=...|4=...|]
 * 
 * 参数：
 * 1：参数
 * 2：选项
 * 3：如果有该选项时返回的文本
 * 4：如果无该选项时返回的文本
*/