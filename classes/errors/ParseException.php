<?php

namespace megabike\templates\errors;

use megabike\templates\Source;
use megabike\templates\TemplatesException;

class ParseException extends TemplatesException
{
    protected $sourceIndex;

    public function __construct(Source $source, $index, $message, $code = 0, $previous = null)
    {
        $this->sourceIndex = $index;
        parent::__construct($source->createErrorMessage($message, $index), $code, $previous);
    }

    public function getSourceIndex()
    {
        return $this->sourceIndex;
    }

}

