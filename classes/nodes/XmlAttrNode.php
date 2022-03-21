<?php

namespace megabike\templates\nodes;

use megabike\templates\Content;
use megabike\templates\Input;
use megabike\templates\nodes\Node;
use megabike\templates\nodes\CodeNode;
use megabike\templates\nodes\AttrNode;

class XmlAttrNode extends AttrNode
{

    public function getAttrTextValue(Input $input = null, $storeMode = 0)
    {
        if ($this->attrValue instanceof CodeNode) {
            return $this->attrValue->getValue($input, $storeMode);
        } else {
            return Content::htmlDecode($this->getAttrValue($input, $storeMode));
        }
    }

    protected function computeIsConstant()
    {
        if (is_string($this->attrValue)) {
            return true;
        } elseif ($this->attrValue instanceof Node) {
            return $this->attrValue->isConstant();
        } elseif (is_array($this->attrValue)) {
            foreach ($this->attrValue as $node) {
                if (($node instanceof Node) && !$node->isConstant()) {
                    return false;
                }
            }
            return true;
        } else {
            return true;
        }
    }

    protected function computeValue($input = null, $storeMode = 0)
    {
        if (is_string($this->attrValue)) {
            return $this->attrValue;
        } elseif ($this->attrValue instanceof CodeNode) {
            return Content::htmlAttrEncode($this->attrValue->getValue($input, $storeMode));
        } elseif ($this->attrValue instanceof Node) {
            return $this->attrValue->getValue($input, $storeMode);
        } elseif (is_array($this->attrValue)) {
            $string = '';
            foreach ($this->attrValue as $node) {
                if ($node instanceof CodeNode) {
                    $string .= Content::htmlAttrEncode($node->getValue($input, $storeMode));
                } elseif ($node instanceof Node) {
                    $string .= $node->getValue($input, $storeMode);
                } else {
                    $string .= (string)$node;
                }
            }
            return $string;
        } else {
            return '';
        }
    }

    protected function execute($input, $storeMode = 0)
    {
        if ($this->attrValue instanceof Node) {
            $this->attrValue->execute($input, 0);
        } elseif (is_array($this->attrValue)) {
            foreach ($this->attrValue as $node) {
                if ($node instanceof Node) {
                    $node->execute($input, 0);
                }
            }
        }
    }

}
