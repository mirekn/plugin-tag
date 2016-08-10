<?php
/**
 * Tag Plugin: displays list of keywords with links to categories this page
 * belongs to. The links are marked as tags for Technorati and other services
 * using tagging.
 *
 * Usage: {{tag>category tags space separated}}
 *
 * @license  GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author   Matthias Schulte <dokuwiki@lupo49.de>
 */
 
// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

if (!defined('DOKU_LF')) define('DOKU_LF', "\n");
if (!defined('DOKU_TAB')) define('DOKU_TAB', "\t");
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

/** List syntax, allows to list tags */
class syntax_plugin_tag_list extends DokuWiki_Syntax_Plugin {

    /**
     * @return string Syntax type
     */
    function getType() { return 'substition'; }
    /**
     * @return int Sort order
     */
    function getSort() { return 305; }
    /**
     * @return string Paragraph type
     */
    function getPType() { return 'block';}

    /**
     * @param string $mode Parser mode
     */
    function connectTo($mode) {
        $this->Lexer->addSpecialPattern('\{\{list>.*?\}\}', $mode, 'plugin_tag_list');
    }

    /**
     * Handle matches of the list syntax
     *
     * @param string $match The match of the syntax
     * @param int    $state The state of the handler
     * @param int    $pos The position in the document
     * @param Doku_Handler    $handler The handler
     * @return array Data for the renderer
     */
    function handle($match, $state, $pos, Doku_Handler $handler) {

        $dump = trim(substr($match, 7, -2));

        $linkSeparator = null;
        if (strpos($dump, '&&') !== false) {
            $dump_a = explode('&&', $dump);
            $linkSeparator = $dump_a[1];
            $dump = $dump_a[0];
        }

        $dump = explode('&', $dump);             // split to tags and allowed namespaces 
        $tags = $dump[0];
        $allowedNamespaces = explode(' ', $dump[1]); // split given namespaces into an array

        if($allowedNamespaces && $allowedNamespaces[0] == '') {
            unset($allowedNamespaces[0]);    // When exists, remove leading space after the delimiter
            $allowedNamespaces = array_values($allowedNamespaces);
        }

        if (empty($allowedNamespaces)) $allowedNamespaces = null;

        if (!$tags) $tags = '+';

        /** @var helper_plugin_tag $my */
        if(!($my = $this->loadHelper('tag'))) return false;

        return array($my->_parseTagList($tags), $allowedNamespaces, $linkSeparator);
    }

    /**
     * Render xhtml output or metadata
     *
     * @param string         $mode      Renderer mode (supported modes: xhtml and metadata)
     * @param Doku_Renderer  $renderer  The renderer
     * @param array          $data      The data from the handler function
     * @return bool If rendering was successful.
     */
    function render($mode, Doku_Renderer $renderer, $data) {
        if ($data == false) return false;

        list($tags, $allowedNamespaces, $linkSeparator) = $data;

        // deactivate (renderer) cache as long as there is no proper cache handling
        // implemented for the list syntax
        $renderer->info['cache'] = false;

        if($mode == "xhtml") {
            /** @var helper_plugin_tag $my */
            if(!($my = $this->loadHelper('tag'))) return false;

            // get tags and their occurrences
            if($tags[0] == '+') {
                // no tags given, list all tags for allowed namespaces
                $occurrences = $my->tagOccurrences($tags, $allowedNamespaces, true);
            } else {
                $occurrences = $my->tagOccurrences($tags, $allowedNamespaces);
            }

            if (!is_null($linkSeparator)) {
                $linkSeparator = hsc($linkSeparator);
                $linkSeparator = str_replace('+', '&nbsp;', $linkSeparator);

                $renderer->doc .= '<p>'.DOKU_LF;

                if(empty($occurrences)) {
                    // Skip output
                    $renderer->doc .= $this->getLang('empty_output').DOKU_LF;
                } else {
                    ksort($occurrences);
                    $i = 0;
                    $last = count($occurrences);
                    foreach($occurrences as $tagname => $count) {
                        $i++;
                        if ($i == $last) {
                            $linkSeparator = '';
                        }
                        if($count <= 0) continue; // don't display tags with zero occurrences
                        $renderer->doc .= $my->tagLink($tagname).$linkSeparator.DOKU_LF;
                    }
                }
                $renderer->doc .= '</p>'.DOKU_LF;
            } else {
                $renderer->doc .= '<ul class="fix-media-list-overlap">'.DOKU_LF;

                if(empty($occurrences)) {
                    // Skip output
                    $renderer->doc .= DOKU_TAB.'<li>'.$this->getLang('empty_output').'</li>'.DOKU_LF;
                } else {
                    ksort($occurrences);
                    foreach($occurrences as $tagname => $count) {
                        if($count <= 0) continue; // don't display tags with zero occurrences
                        $renderer->doc .= DOKU_TAB.'<li>'.$my->tagLink($tagname).'</li>'.DOKU_LF;
                    }
                }
                $renderer->doc .= '</ul>'.DOKU_LF;
            }
        }
        return true;
    }
}
