<?php
/**
 * DokuWiki Plugin tplt (Syntax Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  AlloyDome
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();
if (!defined('DOKU_LF')) define('DOKU_LF', "\n");
if (!defined('DOKU_TAB')) define('DOKU_TAB', "\t");
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

class syntax_plugin_tplt_uninitedArg extends DokuWiki_Syntax_Plugin {
	/**
	 * @return string Syntax mode type
	 */
	public function getType() {
		return 'substition';//maybe switch to 'container'
	}
	/**
	 * @return string Paragraph type
	 */
	public function getPType() {
		return 'normal'; //?
	}
	/**
	 * @return int Sort order - Low numbers go before high numbers
	 */
	public function getSort() {
		return 319; // should go before Doku_Parser_Mode_media 320
	}
//	function getAllowedTypes() { return array('formatting', 'substition', 'disabled'); }  
	/**
	 * Connect lookup pattern to lexer.
	 *
	 * @param string $mode Parser mode
	 */
	public function connectTo($mode) {
		$this->Lexer->addEntryPattern('\{\{\{(?=[^\{].*?\}\}\})', $mode, 'plugin_tplt_' . $this->getPluginComponent());
	}


	public function postConnect() {
		$this->Lexer->addExitPattern('(?<!\})\}\}\}', 'plugin_tplt_' . $this->getPluginComponent());
	}

	/**
	 * Handle matches of the wst syntax
	 *
	 * @param string		  $match   The match of the syntax
	 * @param int			 $state   The state of the handler
	 * @param int			 $pos	 The position in the document
	 * @param Doku_Handler	$handler The handler
	 * @return array Data for the renderer
	 */
	public function handle($match, $state, $pos, Doku_Handler $handler){
		if ($state == DOKU_LEXER_UNMATCHED) {
			return array($match, $state);
		} else {
			return array('', $state);
		}
	}

	/**
	 * Render xhtml output or metadata
	 *
	 * @param string		 $mode	  Renderer mode (supported modes: xhtml)
	 * @param Doku_Renderer  $renderer  The renderer
	 * @param array		  $data	  The data from the handler() function
	 * @return bool If rendering was successful.
	 */
	public function render($mode, Doku_Renderer $renderer, $data) {
		if($mode != 'xhtml') return false;
		if(!$data) return false;

		list($match, $state) = $data;
		switch ($state) {
			case DOKU_LEXER_ENTER: {
				$renderer->doc .= '<code><strong>'; 
				break;
			}
			case DOKU_LEXER_EXIT: {
				$renderer->doc .= '</strong></code>'; 
				break;
			}
			case DOKU_LEXER_UNMATCHED: {
				$renderer->doc .= hsc('{{{' . $match . '}}}'); 
				break;
			}
		}
		return true;
	}
}

