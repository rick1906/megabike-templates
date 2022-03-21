<?php

namespace megabike\templates\nodes\operators;

use megabike\templates\nodes\AttrNode;
use megabike\templates\nodes\operators\OperatorNode;
use megabike\templates\parser\Parser;
use megabike\templates\errors\ParseException;

class IfOperator extends OperatorNode
{
    protected $expression = null;

    public function getOperatorId()
    {
        return 'if';
    }

    public function initializeNode(Parser $parser)
    {
        parent::initializeNode($parser);
        if ($this->expression === null) {
            throw new ParseException($parser->getSource(), $this->getIndex(), "Expression is not specified for operator '{$this->getOperatorId()}'");
        }
    }

    protected function executeOperator($input, $storeMode = 0)
    {
        $expression = $this->getParameter($this->expression, $input);
        if ($expression) {
            $this->storage['']['_result'] = true;
            $this->executeContent($input, $storeMode);
        } else {
            $this->storage['']['_result'] = false;
        }
    }

    protected function computeOperatorValue($input, $storeMode = 0)
    {
        if (!empty($this->storage['']['_result'])) {
            return $this->getContentValue($input, $storeMode);
        } else {
            return '';
        }
    }

    protected function setParameter(AttrNode $node)
    {
        $id = $node->getAttrId();
        if ($id === '' || $id === 'test' || $id === 'expression') {
            $this->checkParameterIsAvailable($node, $this->expression);
            $this->expression = $this->getCodeParameter($node);
            return $this->expression;
        }
        return false;
    }

}
