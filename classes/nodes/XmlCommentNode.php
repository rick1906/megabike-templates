<?php

namespace megabike\templates\nodes;

use megabike\templates\Input;
use megabike\templates\nodes\TreeNode;

class XmlCommentNode extends TreeNode
{

    public function isOpenTagConstant()
    {
        return true;
    }

    public function isContentConstant()
    {
        return true;
    }

    public function getOpenTagValue(Input $input = null, $storeMode = 0)
    {
        return '<!--';
    }

    public function getCloseTagValue(Input $input = null, $storeMode = 0)
    {
        return '-->';
    }

    protected function computeIsOpenTagConstant()
    {
        return true;
    }

    protected function computeOpenTagValue($input = null)
    {
        return '<!--';
    }

    protected function executeOpenTag($input)
    {
        
    }

    protected function computeCloseTagValue($input = null)
    {
        
    }

    protected function executeCloseTag($input)
    {
        
    }

    public function isClosed()
    {
        return true;
    }

}
