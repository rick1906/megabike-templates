<?php

namespace megabike\templates\nodes;

use megabike\templates\nodes\XmlAttrNode;
use megabike\templates\Content;

class HtmlAttrNode extends XmlAttrNode
{
    protected $attrId;

    public function __construct($attrName, $token = null)
    {
        parent::__construct($attrName, $token);
        $this->attrId = Content::toLower($attrName);
    }

    public function getAttrId()
    {
        return $this->attrId;
    }

}
