<?php

namespace megabike\templates\parser\tokens;

use megabike\templates\parser\Parser;
use megabike\templates\parser\tokens\MasterToken;

class XmlInstructionToken extends MasterToken
{
    protected $tagName;

    public function __construct($index, $tagName)
    {
        parent::__construct($index);
        $this->tagName = $tagName;
    }

    public function toString()
    {
        return '<?'.$this->tagName.$this->getTokensString().'?>';
    }

    public function createNode(Parser $parser)
    {
        return false;
    }

}