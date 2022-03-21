<?php

namespace megabike\templates\nodes\operators;

use megabike\templates\nodes\AttrNode;
use megabike\templates\nodes\TreeNode;
use megabike\templates\nodes\operators\OperatorNode;
use megabike\templates\nodes\operators\IfOperator;
use megabike\templates\parser\Parser;
use megabike\templates\errors\ParseException;

class ElseOperator extends OperatorNode
{

    public static function findIfOperator($nodes, $elseOp)
    {
        $last = count($nodes) - 1;
        $mode = 0;
        for ($i = $last; $i >= 0; $i--) {
            $node = $nodes[$i];
            if ($mode === 0) {
                if ($node === $elseOp) {
                    $mode = 1;
                }
            } else {
                if ($node instanceof IfOperator) {
                    return $node;
                }
            }
        }
        return null;
    }

    protected $ifOperator = null;

    public function getOperatorId()
    {
        return 'else';
    }

    public function initializeNode(Parser $parser)
    {
        parent::initializeNode($parser);
        if ($this->parentNode !== null && $this->parentNode instanceof TreeNode) {
            $this->ifOperator = ElseOperator::findIfOperator($this->parentNode->childNodes, $this);
            if ($this->ifOperator === null) {
                throw new ParseException($parser->getSource(), $this->getIndex(), "Operator 'if' not found for operator '{$this->getOperatorId()}'");
            }
        } else {
            throw new ParseException($parser->getSource(), $this->getIndex(), "Parent of operator '{$this->getOperatorId()}' must be TreeNode");
        }
    }

    protected function executeOperator($input, $storeMode = 0)
    {
        $result = $this->ifOperator !== null && !empty($this->ifOperator->storage['']['_result']);
        if (!$result) {
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
        return false;
    }

}
