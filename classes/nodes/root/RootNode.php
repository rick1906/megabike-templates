<?php

namespace megabike\templates\nodes\root;

use megabike\templates\Input;
use megabike\templates\nodes\TreeNode;
use megabike\templates\parser\Parser;

class RootNode extends TreeNode
{
    protected $format;
    protected $inputCharset;

    public function __construct(Parser $parser)
    {
        parent::__construct();
        $this->rootNode = $this;
        $this->format = $parser->getFormat();
        $this->inputCharset = $parser->getSource()->getInputCharset();
    }

    public function getInputCharset()
    {
        return $this->inputCharset;
    }

    public function getFormat()
    {
        return $this->format;
    }

    public function checkCollectedNode(Parser $parser, $token)
    {
        return self::ACTION_ACCEPT;
    }

    public function checkCollectedToken(Parser $parser, $node)
    {
        return self::ACTION_ACCEPT;
    }

    public function isClosed()
    {
        return false;
    }

    public function getOpenTagValue(Input $input = null, $storeMode = 0)
    {
        return '';
    }

    public function getCloseTagValue(Input $input = null, $storeMode = 0)
    {
        return '';
    }

    public function getValue(Input $input = null, $storeMode = 0)
    {
        return $this->getContentValue($input, $storeMode);
    }

    protected function computeOpenTagValue($input = null)
    {
        return '';
    }

    protected function computeCloseTagValue($input = null)
    {
        return '';
    }

    protected function computeIsOpenTagConstant()
    {
        return true;
    }

    protected function executeOpenTag($input)
    {
        
    }

    protected function executeCloseTag($input)
    {
        
    }

}
