<?php

namespace megabike\templates\parser\tokens;

use megabike\templates\parser\tokens\XmlAttrToken;

class DoctypeAttrToken extends XmlAttrToken
{

    public function __construct($index)
    {
        parent::__construct($index, '');
    }

}