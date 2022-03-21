<?php

namespace megabike\templates\nodes;

use megabike\templates\Input;
use megabike\templates\Content;
use megabike\templates\nodes\Node;
use megabike\templates\parser\Parser;
use megabike\templates\parser\CodeProcessor;
use megabike\templates\parser\tokens\CodeSequence;
use megabike\templates\errors\NodeException;

abstract class AttrNode extends Node
{
    protected $attrName;
    protected $attrValue = null;

    public function __construct($attrName, $token = null)
    {
        parent::__construct($token);
        $this->attrName = $attrName;
    }

    public function initializeNode(Parser $parser)
    {
        if ($this->attrValue instanceof Node) {
            $this->attrValue->initializeNode($parser);
        } elseif (is_array($this->attrValue)) {
            foreach ($this->attrValue as $node) {
                if ($node instanceof Node) {
                    $node->initializeNode($parser);
                }
            }
        }
    }

    public function getValue(Input $input = null, $storeMode = 0)
    {
        if ($this->isDisabled()) {
            return '';
        } elseif ($this->attrValue === null) {
            return $this->attrName;
        } else {
            return $this->attrName.'="'.$this->getAttrValue($input, $storeMode).'"';
        }
    }

    public function getAttrValue(Input $input = null, $storeMode = 0)
    {
        if ($this->isConstant()) {
            if (isset($this->storage['value'])) {
                return $this->storage['value'];
            } else {
                $value = $this->computeValue(null, $storeMode < 2 ? 0 : 2);
                if ($storeMode > 0) {
                    $this->storage['value'] = $value;
                }
                return $value;
            }
        } elseif ($input !== null) {
            $this->applyInput($input, $storeMode);
            return $this->computeValue($input, $storeMode);
        } else {
            return null;
        }
    }

    public function getAttrTextValue(Input $input = null, $storeMode = 0)
    {
        return Content::htmlDecode($this->getAttrValue($input, $storeMode));
    }

    public function getAttrName()
    {
        return $this->attrName;
    }

    public function getAttrId()
    {
        return $this->attrName;
    }

    public function setValueNode($node)
    {
        $this->attrValue = $node;
        if ($node instanceof Node) {
            $node->setParent($this);
        }
    }

    public function addValueNode($node)
    {
        if (!is_array($this->attrValue)) {
            $this->attrValue = array($node);
        } else {
            $this->attrValue[] = $node;
        }
        if ($node instanceof Node) {
            $node->setParent($this);
        }
    }

    public function transformToCode()
    {
        if ($this->attrValue instanceof CodeNode) {
            return $this;
        } elseif ($this->isConstant()) {
            $code = $this->getAttrTextValue();
            if (trim($code) !== '') {
                $codeTokens = CodeProcessor::parse($code);
                if ($codeTokens !== false) {
                    $codeParams = array($code, $codeTokens, CodeProcessor::MODE_RETURN, '{');
                    $token = new CodeSequence($this->index, '{', $code, $codeTokens);
                    $this->attrValue = new CodeNode($codeParams, $token);
                    return $this;
                }
            }
        }

        throw new NodeException("Attribute '".$this->getAttrName()."' is not a valid code attribute", $this);
    }

    public function getVariableValue(Input $input = null)
    {
        if ($this->attrValue instanceof CodeNode) {
            return $this->attrValue->getVariableValue($input);
        } else {
            return null;
        }
    }

}
