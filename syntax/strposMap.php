<?php
/**
 * DokuWiki Plugin tplt (Syntax Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  AlloyDome
 * 
 * @version 0.4.1 (2023-3-7)
 * @since	0.4.0 (2023-3-5)
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();
if (!defined('DOKU_LF')) define('DOKU_LF', "\n");
if (!defined('DOKU_TAB')) define('DOKU_TAB', "\t");
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

class syntax_plugin_tplt_strposMap extends DokuWiki_Syntax_Plugin {

	/**
	 * @return string Syntax mode type
	 */
	public function getType() {
		return 'baseonly';
	}
	/**
	 * @return string Paragraph type
	 */
	public function getPType() {
		return 'block';
	}
	/**
	 * @return int Sort order - Low numbers go before high numbers
	 */
	public function getSort() {
		return 9;
	}
//	function getAllowedTypes() { return array('formatting', 'substition', 'disabled'); }  
	/**
	 * Connect lookup pattern to lexer.
	 *
	 * @param string $mode Parser mode
	 */
	public function connectTo($mode) {
		$this->Lexer->addSpecialPattern("\n\x7f~~STRPOSMAP~~\x7f\n.*?\n\x7f~~ENDSTRPOSMAP~~\x7f\n", $mode, 'plugin_tplt_strposMap');
	}

	/**
	 * Handle matches of the wst syntax
	 *
	 * @param string		$match   The match of the syntax
	 * @param int			$state   The state of the handler
	 * @param int			$pos	 The position in the document
	 * @param Doku_Handler	$handler The handler
	 * @return array Data for the renderer
	 */
	public function handle($match, $state, $pos, Doku_Handler $handler){
		return substr($match, 
			17, 	// \n\x7f~~STRPOSMAP~~\x7f\n
			-20		// \n\x7f~~ENDSTRPOSMAP~~\x7f\n
		);
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

		$data = '&#127;<!-- STRPOSMAP[' . hsc($data) . "] -->&#127;\n";

		$renderer->doc = $data . $renderer->doc;

		return true;
	}
}

