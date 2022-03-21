<?php

namespace megabike\templates\errors;

use megabike\templates\TemplatesException;
use megabike\templates\nodes\Node;

class NodeException extends TemplatesException
{
    protected $sourceIndex;

    public function __construct($message, Node $node = null, $code = 0, $previous = null)
    {
        $this->sourceIndex = $node ? $node->getIndex() : null;
        parent::__construct($message, $code, $previous);
    }

    public function getSourceIndex()
    {
        return $this->sourceIndex;
    }

}
