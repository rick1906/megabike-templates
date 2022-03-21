<?php

namespace megabike\templates\nodes\operators;

use megabike\templates\nodes\TreeNode;
use megabike\templates\nodes\operators\IfOperator;
use megabike\templates\nodes\operators\ElseOperator;
use megabike\templates\parser\Parser;
use megabike\templates\parser\tokens\OperatorCloseToken;
use megabike\templates\errors\ParseException;

class ElseifOperator extends IfOperator
{
    protected $ifOperator = null;

    public function getOperatorId()
    {
        return 'elseif';
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
            parent::executeOperator($input, $storeMode);
        } else {
            $this->storage['']['_result'] = false;
        }
    }

    protected function checkCollectedToken(Parser $parser, $token)
    {
        if ($this->tagName === null && $token instanceof OperatorCloseToken) {
            if ($token->getOperatorId() === 'if') {
                $this->processCloseTag($parser, $token);
                return self::ACTION_EXIT;
            }
        }
        return parent::checkCollectedToken($parser, $token);
    }

}
