<?php

namespace megabike\templates\errors;

use megabike\templates\Source;

class ParseWarning
{
    protected $message;
    protected $sourceIndex;

    public function __construct(Source $source, $index, $message)
    {
        $this->sourceIndex = $index;
        $this->message = $source->createErrorMessage($message, $index);
    }

    public function getMessage()
    {
        return $this->message;
    }

    public function getSourceIndex()
    {
        return $this->sourceIndex;
    }

}
