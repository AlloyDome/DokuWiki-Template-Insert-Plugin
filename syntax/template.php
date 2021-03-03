<?php
/**
 * DokuWiki Plugin tplt (Syntax Component)
 *
 * @license	GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author	1. Vitalie Ciubotaru <vitalie@ciubotaru.tk>
 * 			2. A135
 * 
 * @version 1.1 (210303)
 */

// 必须在 Dokuwiki 下运行 · Must be run within Dokuwiki
if (!defined('DOKU_INC'))
	die();

if (!defined('DOKU_LF'))
	define('DOKU_LF', "\n");
if (!defined('DOKU_TAB'))
	define('DOKU_TAB', "\t");
if (!defined('DOKU_PLUGIN'))
	define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

class syntax_plugin_tplt_template extends DokuWiki_Syntax_Plugin {
    /**
     * @return string  语法模式选择 · Syntax mode type
     */
	public function getType()
	{
	   return 'substition';	// 请勿改成 “container”，否则无法在表格中使用 · Please don't change it to "container" otherwise it cannot be used in tables
	}
	
    /**
     * @return string   段落模式选择 · Paragraph type
     */
	public function getPType()
	{
        return 'normal';
	}
	
    /**
     * @return int  运行优先级——数字越小优先级越高 · Sort order - Low numbers go before high numbers
     */
	public function getSort()
	{
		return 319; // 需要在 Doku_Parser_Mode_media（320）前运行 · Should go before Doku_Parser_Mode_media 320
	}
	
    /**
	 * connectTo
     * 查找出符合的片段来交给渲染器处理 · Connect lookup pattern to lexer.
     *
     * @param string $mode  语义分析模式 · Parser mode
     */
    public function connectTo($mode) {
        $this->Lexer->addSpecialPattern('\{\{tplt>(?:(?:[^\}]*?\{.*?\}\})|.*?)+?\}\}', $mode, 'plugin_tplt_template');
    }

/**
    public function postConnect() {
        $this->Lexer->addExitPattern('\}\}', 'plugin_wst');
    }
**/
    /**
	 * handle
     * 对已匹配的片段进行处理 · Handle matches of the tplt plugin
	 * 
	 * @version	1.1 (210303)
	 * @since	1.0 (210105)
	 * 
	 * @author	1. Vitalie Ciubotaru <vitalie@ciubotaru.tk>
	 * 			2. A135
     *
     * @param	string			$match		与插件语法相匹配的片段 · The match of the syntax
     * @param	int				$state		处理器的状态 · The state of the handler
     * @param	int				$pos		片段在文档中的位置 · The position in the document
     * @param	Doku_Handler	$handler	处理器 · The handler
     * @return	array						交给渲染器处理的数据 · Data for the renderer
     */
    public function handle($match, $state, $pos, Doku_Handler $handler){
		if (empty($match)) return false;
        $template_arguments = array();	// 存储参数值的数组 · Array for values of arguments
		$dump = trim(substr($match, 7, -2));
			// 去除双花括号、“tplt”关键字及两端的空白字符，以获得模板名称以及各个参数的值
			//  · 
			// Remove curly brackets, "tplt" keyword, and space characters at both ends to get template name and value of each argument
		$dump = preg_replace_callback('/\{\{(((?!(\{\{|\}\})).*?|(?R))*)\}\}/', function($match) {return str_replace('|', '{{!}}', $match[0]);}, $dump);

		$dump = $this->getTemplateName($dump, 2, 0);	// 模板名与各参数 · Template name and arguments
		// 模板名 · template name
		$templateName = $dump[0];

		if (!$this->recursionCheck($templateName))	// 递归检查 · recursion check
			return $this->getLang('selfCallingBegin') . $templateName . $this->getLang('selfCallingEnd');
		
		$dump = addcslashes($dump[1], '\\');	// 各参数 · All arguments
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
					$tmp = explode("=", $value, 2);
					$template_arguments[(trim($tmp[0] != '')) ? trim($tmp[0]) : ($key + 1)] = trim($tmp[1]);
				}
				// 如果没声明参数名 · If argument name is not defined
				// 则编号，从 1 开始，不是从 0 开始 · Start from 1, not 0
				else $template_arguments[$key + 1] = trim($value);
			}
		}
		$template_arguments = str_replace('{{!}}', '|', $template_arguments);
		$template = $this->get_template($templateName);
		if (!$template) return;
		$template_text = $this->replaceArgs($template, $template_arguments);
		return $template_text;
    }

    /**
	 * render
     * 输出 XHTML 或元数据的渲染器 · Render xhtml output or metadata
	 * 
	 * @version	1.0 (210105)
	 * @since	1.0 (210105)
	 * 
	 * @author	1. Vitalie Ciubotaru <vitalie@ciubotaru.tk>
	 * 			2. A135
     *
     * @param	string			$mode		渲染器模式（此插件支持 XHTML 模式） · Renderer mode (supported modes: xhtml)
     * @param	Doku_Renderer	$renderer	渲染器 · The renderer
     * @param	array			$data		从 “handler()” 函数返回的数据 · The data from the "handler()" function
     * @return	bool						表示渲染工作是否成功 · If rendering was successful.
     */
	public function render($mode, Doku_Renderer $renderer, $data)
	{
		if ($mode != 'xhtml')
		{
			return false;
		}
		if (!$data)
		{
			return false;
		}
		$rawRendered = '';
		$rawRendered = $renderer->render_text($data, 'xhtml');	// 渲染生成的原始 XHTML 内容 · Raw XHTML code by renderer
		$renderer->doc .= $this->singleLineHandler($rawRendered);
			// 如果只有一段文字，则去除两侧的 “<p>” 标签 · Remove "<p>" tags if there is only a single paragraph
        return true;
	}
	
	/**
	 * get_template
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

	/**
	 * replaceArgs
	 * 替换模板内的参数 · Replace arguments in template
	 * 
	 * @version	1.1 (210303)
	 * @since	1.0 (210105)
	 * 
	 * @author	1. Vitalie Ciubotaru <vitalie@ciubotaru.tk>
	 * 			2. A135
	 * 
	 * @param	string	$template_text	从 “get_template()” 函数返回的模板代码 · Data from "get_template()" function
	 * @return	string					将参数替换后的模板代码 · Argument replaced data
	 */
	function replaceArgs($template_text, $args)
	{
		// 替换已声明的参数 · Replace defined arguments
		$keys = array_keys($args);
		foreach ($keys as $key)
		{
			$template_text = preg_replace('/\{\{\{' . $key . '\s*?=?\s*?(?:(?:[^\}]*?\{.*?\}\})|.*?)*?\}\}\}/', $args[$key], $template_text);
		}

		// 替换剩余含默认值的参数 · Replace arguments which have default values
		preg_match_all('/\{\{\{[^\{\}#]+?\s*?=\s*?(?:(?:[^\}]*?\{.*?\}\})|.*?)*?\}\}\}/', $template_text, $argsWithDefaultValue);
		foreach ($argsWithDefaultValue[0] as $value)
		{
			$template_text = str_replace($value, trim(substr($value, strpos($value, '=') + 1, -3)), $template_text);
		}
		unset($argsWithDefaultValue);

		// 替换有开关语句的参数 · Replace arguments which have a switch structure
		preg_match_all('/\{\{\{#switch>(?:(?:[^\}]*?\{.*?\}\})|.*?)*?\}\}\}/', $template_text, $argsWithSwitch);
		foreach ($argsWithSwitch[0] as $switchStructure)
		{
			$dump = substr($switchStructure, 11, -3);
			$dump = explode('|', $dump, 2);
			$switchArg = $dump[0];
			if (strpos($switchArg, '=') !== false)
			{
				$tmp = explode('=', $switchArg, 2);
				$switchArg = trim($tmp[0]);
				$default = trim($tmp[1]);
			}
			else
			{
				$switchArg = trim($tmp[0]);
				$default = '';
			}

			$cases = array();
			if ($dump[1])
			{
				$dump = explode('|', $dump[1]);
				$multiToOne = false;
				foreach ($dump as $key => $eachCase)
				{
					if (strpos($eachCase, ':') !== false)
					{
						$tmp = explode(':', $eachCase, 2);
						if (!$multiToOne)
						{
							$cases[trim($tmp[0])] = trim($tmp[1]);
						}
						else
						{
							$multiToOne = false;
							array_push($mtoKeys, trim($tmp[0]));
							foreach ($mtoKeys as $mtoKey)
								$cases[$mtoKey] = trim($tmp[1]);
							unset($mtoKey);
						}
					}
					else
					{
						if (!$multiToOne)
						{
							$multiToOne = true;
							$mtoKeys = array(trim($eachCase));
						}
						else
							array_push($mtoKeys, trim($eachCase));
					}
				}
				if ($multiToOne)
				{
					$multiToOne = false;
					foreach ($mtoKeys as $mtoKey)
						$cases[$mtoKey] = '';
					unset($mtoKey);
				}
			}
			if (array_key_exists($switchArg, $args) && array_key_exists($args[$switchArg], $cases))
				$template_text = preg_replace('/\{\{\{#switch>' . $switchArg . '(?:(?:[^\}]*?\{.*?\}\})|.*?)*?\}\}\}/', $cases[$args[$switchArg]], $template_text);
			else
				$template_text = preg_replace('/\{\{\{#switch>' . $switchArg . '(?:(?:[^\}]*?\{.*?\}\})|.*?)*?\}\}\}/', $default, $template_text);
		}
		unset($switchStructure);

		// 使用占位符替换缺少的参数 · Replace mising arguments with a placeholder
		$template_text = preg_replace('/\{\{\{[^\{]*?\}\}\}/', $this->getLang('missing_argument'), $template_text);

		return $template_text;
	}

	/**
	 * singleLineHandler
	 * 单行 XHTML 处理函数 · Handler of XHTML code with only a single paragraph
	 * 
	 * 如果只有一行文字，则去除两侧的 “<p>……</p>” 标签，以自动适应行内模式
	 *  · 
	 * Remove "<p>...</p>" tags at both sides if there is only a single paragraph to adjust for inline mode
	 * 
	 * @version	1.0 (210105)
	 * @since	1.0 (210105)
	 * 
	 * @author	A135
	 * 
	 * @param	string	rawRandered		渲染生成的原始 XHTML 内容 · Raw XHTML code by renderer
	 * @return	string					
	 */
	private function singleLineHandler($rawRendered)
	{
		$renderedWithoutSpaces = preg_replace('/\s/', '', $rawRendered);	// 去除空白字符的 XHTML 源码 · Space character removed XHTML code
		// 如果根本没有 “<p>……</p>” ，或者多于一个，那么直接返回
		//  · 
		// Return immediately if there is no "<p>...</p>" or there is more than one pairs of "<p>" tags
		if (preg_match_all('/<p>(?:[^(<p>)]|.*?)<\/p>/i', $renderedWithoutSpaces) != 1)
		{
			return $rawRendered;
		}
		// 如果只有一个 “<p>……</p>” ，且外面没有任何内容，则去除 “<p>” 标签
		// ·
		// If there is only one "<p>...</p>" and nothing outside it, then remove "<p>" tags
		if (preg_replace('/<p>(?:[^(<p>)]|.*?)<\/p>/i', '', $renderedWithoutSpaces) == '')
			// 注：此正则表达式可能不准确 · Note: this regular expressions may have issues
		{
			$renderedSingleLine = preg_replace('/\s*<p>\s*/i', '', $rawRendered);
			$renderedSingleLine = preg_replace('/\s*<\/p>\s*/i', '', $renderedSingleLine);
			return $renderedSingleLine;
		}
		// 如果只有一个 “<p>……</p>” ，但外面有其他内容，则维持原状
		//  ·
		// If there is only one "<p>...</p>" but something outside it, keep it as it is
		else
		{
			return $rawRendered;
		}
	}

	/** 
	 * recursionCheck
	 * 模板递归检查函数 · Template recursion check
	 * 
	 * 检查模板是否直接或间接地调用了自身，来防止无限递归
	 *  · 
	 * Check whether templates call themselves directly or indirectly to prevent endless recusion
	 * 
	 * @version	1.0 (210105)
	 * @since	1.0 (210105)
	 * 
	 * @author	A135
	 * 
	 * @param	string	templateName	模板名称 · template name
	 * @return	bool					检查结果（true：不存在自身调用；false：存在自身调用） · result (true: no self-calling; false: self-calling)
	 */
	private function recursionCheck($templateName)
	{
		$checkFor = array($templateName);	// 待检查的模板 · templates for checking
		$alreadyCalled = array();	// 已经被调用的模板 · already called templates

		do
		{
			$newlyCalled = array();	// 新调用的模板 · newly called templates
			foreach ($checkFor as $value)
				array_push($alreadyCalled, $value);	// 将待检查的模板加入已经被调用的模板 · Add templates for checking into called templates
			foreach ($checkFor as $checkOneByOne)	// 逐一检查待检查的模板 · Check templates one by one
			{
				$templateContent = $this->get_template($checkOneByOne);	// 获得待检查模板内的内容 · Get content of this template for checking
				$newlyCalledFound = array();
				preg_match_all('/\{\{tplt>(?:(?:[^\}]*?\{.*?\}\})|.*?)+?\}\}/', $templateContent, $newlyCalledFound);
					// 查找该模板内所有调用模板的匹配字符串 · Find matched strings in this template, which call templates
				$newlyCalledFound = $newlyCalledFound[0];
				foreach ($newlyCalledFound as $key => $matchedString)
				{
					$newlyCalledFound[$key] = $this->getTemplateName($matchedString, 1, 1);	// 得到各模板的名称 · Get the name of each template
				}
				foreach ($newlyCalledFound as $value)
					array_push($newlyCalled, $value);
			}
			if (!empty(array_intersect($newlyCalled, $alreadyCalled)))
			{
				return false;
				break;
					// 如果新调用的模板与已调用的模板有重复，则存在自身调用
					//  · 
					// Self-calling exists when some of newly called templates are same as already called ones
			}
			else 
			{
				$checkFor = $newlyCalled;
					// 将新调用的模板作为下一轮检查对象
					//  · 
					// Regard newly called templates as templates for checking in next loop
			}
		}
		while (!empty($newlyCalled));	// 如果没有新的模板被调用，那么就结束检查 · Stop checking when there is no newly called template
		return true;
	}

	/** 
	 * getTemplateName
	 * 获得模板的名称 · Get template name
	 * 
	 * @version	1.0 (210105)
	 * @since	1.0 (210105)
	 * 
	 * @author	A135
	 * 
	 * @param	string	match	匹配到的含模板名称的字符串 · Matched string including template name
	 * @param	int		mode	返回模式（1 = 只有模板名，2 = 模板名和参数） · Return mode (1 = template name only, 2 = template name and arguments)
	 * @return	mixed			模板名称（或模板名称和参数） · Template name (or template name and arguments)
	 */
	private function getTemplateName($match, $mode, $dumpMode)
	{
		switch ($dumpMode)
		{
			case 0:
				$dump = $match;
				break;
			case 1:
				$dump = trim(substr($match, 7, -2));
					// 去除双花括号、“tplt” 关键字及两端的空白字符 · Remove curly brackets, "tplt" keyword, and space characters at both ends
				break;
		}
		
		switch (strpos($dump, '|'))
		{
			case false:
				$templateName = $dump;
				if ($mode == 2)
					$allArguments = '';
						// 如果没有竖线，则将整个 “$dump” 作为模板名
						//  · 
						// If there is no vertical bar, regard whole "$dump" as template name
				break;
			case true:
				$templateName = substr($dump, 0, strpos($dump, '|'));
				if ($mode == 2)
					$allArguments = substr($dump, strpos($dump, '|') + 1);
						// 如果有竖线，则将 “$dump” 从竖线位置分割，得模板名和各参数值
						//  · 
						// If vertical bar exists, divide "$dump" into template name and values of arguments at vertical bar
				break;
		}
		switch ($mode)
		{
			case 1:
				return $templateName;
				break;
			case 2:
				return array($templateName, $allArguments);
				break;
		}
	}
}