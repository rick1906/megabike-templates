<?php

namespace megabike\templates\nodes;

use megabike\templates\nodes\ElementNode;
use megabike\templates\nodes\AttrNode;
use megabike\templates\errors\NodeException;

class DoctypeNode extends ElementNode
{

    public function __construct($tagName, $token = null)
    {
        parent::__construct($tagName, $token);
        $this->setClosed(self::CLOSED_NORMAL);
    }

    public function addAttrNode($node)
    {
        if ($node instanceof AttrNode) {
            $this->attrNodes[] = $node;
            $node->setParent($this);
        } else {
            throw new NodeException("Invalid attribute node supplied to ".get_class($this), $this);
        }
    }

    protected function computeOpenTagValue($input = null)
    {
        $attrString = '';
        foreach ($this->attrNodes as $i => $node) {
            if (!$node->isDisabled() && !$node->isVirtual()) {
                $value = $node->getAttrValue($input);
                if ($value !== null) {
                    if ($i < 2 && !preg_match('/\s/', $value)) {
                        $attrString .= ' '.$value;
                    } else {
                        $attrString .= ' "'.$value.'"';
                    }
                }
            }
        }
        return '<'.$this->tagName.$attrString.'>';
    }

    protected function computeCloseTagValue($input = null)
    {
        return '';
    }

    protected function computeIsOpenTagConstant()
    {
        foreach ($this->attrNodes as $node) {
            if (!$node->isConstant()) {
                return false;
            }
        }
        return true;
    }

    protected function executeOpenTag($input)
    {
        foreach ($this->attrNodes as $node) {
            $node->execute($input, 0);
        }
    }

    protected function executeCloseTag($input)
    {
        
    }

}
