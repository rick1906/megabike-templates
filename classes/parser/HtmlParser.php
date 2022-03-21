<?php

namespace megabike\templates\parser;

use megabike\templates\Template;
use megabike\templates\nodes\HtmlTextNode;
use megabike\templates\parser\Parser;

class HtmlParser extends Parser
{

    protected function getModeClasses()
    {
        $modes = array(
            'default' => 'HtmlDefaultMode',
            'xmlInstruction' => 'XmlInstructionMode',
            'htmlTagOpen' => 'HtmlTagOpenMode',
            'htmlTagClose' => 'HtmlTagCloseMode',
            'htmlComment' => 'HtmlCommentMode',
            'htmlDoctype' => 'HtmlDoctypeMode',
            'attr' => 'HtmlAttrMode',
            'attrValue' => 'HtmlAttrValueMode',
            'htmlTextRaw' => 'HtmlTextRawMode',
            'htmlDoctypeAttr' => 'HtmlAttrValueMode',
        );
        return $modes + parent::getModeClasses();
    }

    public function getFormat()
    {
        return Template::FORMAT_HTML;
    }

    public function createDefaultNode($token, $parentNode = null)
    {
        if (is_string($token)) {
            return new HtmlTextNode($token);
        }
        return null;
    }

}
