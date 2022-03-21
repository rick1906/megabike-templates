<?php

namespace megabike\templates\parser\tokens;

use megabike\templates\Content;
use megabike\templates\nodes\HtmlAttrNode;
use megabike\templates\parser\tokens\XmlAttrToken;
use megabike\templates\parser\Parser;

class HtmlAttrToken extends XmlAttrToken
{

    public function getAttrId()
    {
        return Content::toLower($this->attrName);
    }

    public function createNode(Parser $parser)
    {
        $class = $parser->getAttrNodeClass($this->getAttrId());
        if ($class !== null) {
            $node = new $class($this->tagName, $this);
            return $this->processAttrValueTokens($node, $parser);
        }

        $node = new HtmlAttrNode($this->attrName, $this);
        return $this->processAttrValueTokens($node, $parser);
    }

}