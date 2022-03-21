<?php

namespace megabike\templates\nodes;

use megabike\templates\Content;
use megabike\templates\Input;
use megabike\templates\nodes\TextNode;

class XmlTextNode extends TextNode
{

    public function getTextValue(Input $input = null, $storeMode = 0)
    {
        return Content::htmlDecode($this->getValue($input, $storeMode));
    }

}
