<?php

namespace megabike\templates\nodes\operators;

use megabike\templates\nodes\AttrNode;
use megabike\templates\nodes\operators\OperatorNode;
use megabike\templates\parser\Parser;
use megabike\templates\errors\ParseException;

class ForeachOperator extends OperatorNode
{
    protected $select = null;
    protected $as = null;
    protected $key = null;

    public function getOperatorId()
    {
        return 'foreach';
    }

    public function initializeNode(Parser $parser)
    {
        parent::initializeNode($parser);
        if ($this->select === null) {
            throw new ParseException($parser->getSource(), $this->getIndex(), "Argument is not specified for operator '{$this->getOperatorId()}'");
        }
    }

    protected function executeOperator($input, $storeMode = 0)
    {
        $buffer = '';
        $array = $this->getParameter($this->select, $input);
        foreach ($array as $key => $item) {
            $subInput = $input->getChildInput(null, null, $item);
            if ($this->key !== null) {
                $subInput->{$this->key} = $key;
            }
            if ($this->as !== null) {
                $subInput->{$this->as} = $item;
            }

            $this->executeContent($subInput, $storeMode);
            $buffer .= $this->computeContentValue($subInput, $storeMode);
        }

        $this->storage['']['_value'] = $buffer;
    }

    protected function computeOperatorValue($input, $storeMode = 0)
    {
        if (isset($this->storage['']['_value'])) {
            return $this->storage['']['_value'];
        } else {
            return '';
        }
    }

    protected function setParameter(AttrNode $node)
    {
        $id = $node->getAttrId();
        if ($id === '' || $id === 'select') {
            $this->checkParameterIsAvailable($node, $this->select);
            $this->select = $this->getCodeParameter($node);
            return $this->select;
        }
        if ($id === 'as') {
            $this->checkParameterIsAvailable($node, $this->as);
            $this->as = ltrim($this->getParameter($node, false), '$');
            return $this->as;
        }
        if ($id === 'key') {
            $this->checkParameterIsAvailable($node, $this->key);
            $this->key = ltrim($this->getParameter($node, false), '$');
            return $this->key;
        }
        return false;
    }

}
