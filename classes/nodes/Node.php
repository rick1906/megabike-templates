<?php

namespace megabike\templates\nodes;

use megabike\templates\Input;
use megabike\templates\parser\Parser;
use megabike\templates\parser\TokensCollection;
use megabike\templates\parser\tokens\Token;

//TODO: <template> nodes like in xsl
//TODO: последовательное составление вычисляемой строки (не рекурсивное) ???
//TODO: non-unicode, disable u in regexps (for MODEs & Source)
abstract class Node
{
    const STORE_DISABLED = 0;
    const STORE_UPPER = 1;
    const STORE_ALL = 2;

    protected $parentNode = null;
    protected $index = null;
    protected $storage = null;

    public function __construct($token = null)
    {
        if ($token instanceof Token) {
            $this->index = $token->getIndex();
        }
    }

    public function isConstant()
    {
        if (isset($this->storage['isConstant'])) {
            return $this->storage['isConstant'];
        } else {
            $value = $this->computeIsConstant();
            $this->storage['isConstant'] = $value;
            return $value;
        }
    }

    public function isDetached()
    {
        return false;
    }

    public function isDisabled()
    {
        return !empty($this->storage['']['disabled']);
    }

    public function isVirtual()
    {
        return false;
    }

    public function getValue(Input $input = null, $storeMode = 0)
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
        } elseif ($this->isDisabled()) {
            return '';
        } elseif ($input !== null) {
            $this->applyInput($input, $storeMode);
            return $this->computeValue($input, $storeMode);
        } else {
            return null;
        }
    }

    public function resetInput()
    {
        unset($this->storage['']);
        unset($this->storage['*']);
    }

    public function applyInput(Input $input = null, $storeMode = 0)
    {
        if ($input !== null && (!isset($this->storage['']['inputId']) || $this->storage['']['inputId'] !== $input->getId())) {
            $this->storage[''] = array('inputId' => $input->getId());
            $this->execute($input, $storeMode);
        }
    }

    public function buildStorage($storeMode = 1)
    {
        if ($this->isConstant() && $storeMode > 0) {
            $this->getValue(null, $storeMode);
        }
    }

    public function resetStorage()
    {
        $this->storage = null;
    }

    protected abstract function computeIsConstant();

    protected abstract function computeValue($input = null, $storeMode = 0);

    protected abstract function execute($input, $storeMode = 0);

    public function getIndex()
    {
        return $this->index;
    }

    public function getParent()
    {
        return $this->parentNode;
    }

    public function getRoot()
    {
        return $this->parentNode !== null ? $this->parentNode->getRoot() : null;
    }

    public function getRealParent()
    {
        $parent = $this->parentNode;
        while ($parent !== null && $parent->isVirtual()) {
            $parent = $parent->parentNode;
        }
        return $parent;
    }

    protected function setParent(Node $parentNode)
    {
        $this->parentNode = $parentNode;
    }

    protected function getParents()
    {
        $parents = array();
        $parent = $this->parentNode;
        while ($parent !== null) {
            $parents[] = $parent;
            $parent = $parent->parentNode;
        }
        return $parents;
    }

    protected function getParentsAndSelf()
    {
        $nodes = $this->getParents();
        array_unshift($nodes, $this);
        return $nodes;
    }

    public function collectChildNodes(Parser $parser, TokensCollection $tokens, $startIndex)
    {
        return 0;
    }

    public function initializeNode(Parser $parser)
    {
        
    }

}
