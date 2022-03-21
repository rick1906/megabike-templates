<?php

namespace megabike\templates\parser\tokens;

use megabike\templates\Content;
use megabike\templates\parser\Parser;
use megabike\templates\parser\tokens\Token;
use megabike\templates\errors\ParseException;

class OperatorCloseToken extends Token
{
    protected $operator;

    public function __construct($index, $operator)
    {
        parent::__construct($index);
        $this->operator = $operator;
    }

    public function toString()
    {
        return '{{/'.$this->operator.'}}';
    }

    public function getOperator()
    {
        return $this->operator;
    }

    public function getOperatorId()
    {
        return Content::toLower($this->operator);
    }

    public function fallback(Parser $parser)
    {
        $class = $parser->getOperatorClass($this->getOperatorId());
        if ($class !== null) {
            throw new ParseException($parser->getSource(), $this->index, "Orphan operator close-tag '{$this->operator}'");
        } else {
            throw new ParseException($parser->getSource(), $this->index, "Found close-tag of unknown operator '{$this->operator}'");
        }
    }

}