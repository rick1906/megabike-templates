<?php

namespace megabike\templates\nodes;

use megabike\templates\Content;
use megabike\templates\nodes\ElementNode;
use megabike\templates\nodes\AttrNode;
use megabike\templates\nodes\modifiers\ModifierNode;
use megabike\templates\errors\NodeException;

class XmlElementNode extends ElementNode
{
    const CLOSED_SHORT = 2;
    const CLOSED_AUTO = 3;
    const CLOSED_FORCED = 4;

    protected $attrsById = array();

    public function addAttrNode($node)
    {
        if ($node instanceof ModifierNode) {
            $this->attrNodes[] = $node;
            $node->setParent($this);
        } elseif ($node instanceof AttrNode) {//TODO: id attrs, etc. (lazy indexing on rootNode)
            $this->attrNodes[] = $node;
            $this->attrsById[$node->getAttrId()] = $node;
            $node->setParent($this);
        } else {
            throw new NodeException("Invalid attribute node supplied to ".get_class($this), $this);
        }
    }

    public function getAttrValue($input, $id)
    {
        $this->applyInput($input);
        if (isset($this->storage['']['attrsById'][$id])) {
            return $this->storage['']['attrsById'][$id];
        } elseif (isset($this->attrsById[$id])) {
            return $this->attrsById[$id]->getAttrValue($input);
        } else {
            return null;
        }
    }

    public function getAttrTextValue($input, $id)
    {
        $this->applyInput($input);
        if (isset($this->storage['']['attrsById'][$id])) {
            return Content::htmlDecode($this->storage['']['attrsById'][$id]);
        } elseif (isset($this->attrsById[$id])) {
            return $this->attrsById[$id]->getAttrTextValue($input);
        } else {
            return null;
        }
    }

    public function isShortClosed()
    {
        return $this->closedState === self::CLOSED_SHORT;
    }

    protected function computeOpenTagValue($input = null)
    {
        $attrString = '';
        foreach ($this->attrsById as $id => $node) {
            if (!$node->isDisabled() && !$node->isVirtual()) {
                $value = $this->getAttrValue($input, $id);
                if ($value !== null) {
                    $attrString .= ' '.$node->getAttrName().'="'.$value.'"';
                } else {
                    $attrString .= ' '.$node->getAttrName();
                }
            }
        }
        if (isset($this->storage['']['attrs'])) {
            foreach ($this->storage['']['attrs'] as $name => $value) {
                if ($value !== null) {
                    $attrString .= ' '.$name.'="'.$value.'"';
                } else {
                    $attrString .= ' '.$name;
                }
            }
        }
        if ($this->closedState === self::CLOSED_SHORT) {
            return '<'.$this->tagName.$attrString.' />';
        } else {
            return '<'.$this->tagName.$attrString.'>';
        }
    }

    protected function computeCloseTagValue($input = null)
    {
        if ($this->closedState === self::CLOSED_NONE || $this->closedState === self::CLOSED_SHORT) {
            return '';
        }
        return '</'.$this->tagName.'>';
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
