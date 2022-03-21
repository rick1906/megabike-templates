<?php

namespace megabike\templates\nodes;

use megabike\templates\Input;
use megabike\templates\nodes\Node;

class TextNode extends Node
{
    protected $content = '';

    public function __construct($content)
    {
        parent::__construct(null);
        $this->content = $content;
    }
    
    public function getTextValue(Input $input = null, $storeMode = 0)
    {
        return (string)$this->getValue($input, $storeMode);
    }

    protected function computeIsConstant()
    {
        return true;
    }

    protected function computeValue($input = null, $storeMode = 0)
    {
        return $this->content;
    }

    protected function execute($input, $storeMode = 0)
    {
        
    }

    public function join(TextNode $textNode)
    {
        if (get_class($this) === get_class($textNode)) {
            $this->content .= $textNode->content;
            $textNode->content = '';
            return true;
        }
        return false;
    }

}
