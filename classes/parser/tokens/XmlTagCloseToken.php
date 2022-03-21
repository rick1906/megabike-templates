<?php

namespace megabike\templates\parser\tokens;

use megabike\templates\parser\Parser;
use megabike\templates\parser\tokens\MasterToken;
use megabike\templates\errors\ParseException;

class XmlTagCloseToken extends MasterToken
{
    protected $tagName;

    public function __construct($index, $tagName)
    {
        parent::__construct($index);
        $this->tagName = $tagName;
    }

    public function toString()
    {
        return '</'.$this->tagName.$this->getTokensString().'>';
    }

    public function getTagId()
    {
        return $this->tagName;
    }

    public function getTagName()
    {
        return $this->tagName;
    }

    public function fallback(Parser $parser)
    {
        throw new ParseException($parser->getSource(), $this->index, "Orphan close-tag '{$this->tagName}'");
    }

}