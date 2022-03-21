<?php

namespace megabike\templates\nodes\operators;

use megabike\templates\nodes\AttrNode;
use megabike\templates\nodes\operators\OperatorNode;
use megabike\templates\nodes\root\Scheme;
use megabike\templates\parser\Parser;
use megabike\templates\errors\ParseException;
use megabike\templates\errors\NodeException;
use megabike\templates\errors\ApplyException;
use megabike\templates\errors\SourceException;

class IncludeOperator extends OperatorNode
{
    protected $sourceId = null;
    protected $sourceType = null;
    protected $for = null;
    protected $input = null;

    public function __construct($tagName, $token = null)
    {
        parent::__construct($tagName, $token);
        $this->setClosed(self::CLOSED_NORMAL);
    }

    public function getOperatorId()
    {
        return 'include';
    }

    public function initializeNode(Parser $parser)
    {
        parent::initializeNode($parser);
        if ($this->sourceId === null) {
            throw new ParseException($parser->getSource(), $this->getIndex(), "Source is not specified for operator '{$this->getOperatorId()}'");
        }
        if (($this->rootNode instanceof Scheme) && !is_object($this->sourceId) && !is_object($this->sourceType)) {
            $this->rootNode->addUsedTemplate((string)$this->sourceId, (string)$this->sourceType);
        }
    }

    protected function computeOperatorValue($input, $storeMode = 0)
    {
        $id = $this->getParameter($this->sourceId, $input);
        $type = (string)$this->getParameter($this->sourceType, $input);

        try {
            $subTemplate = $input->getTemplate()->getSubTemplate($id, $type);
        } catch (ApplyException $ex) {
            throw new NodeException($ex->getMessage(), $this, $ex);
        } catch (SourceException $ex) {
            throw new NodeException($ex->getMessage(), $this, $ex);
        }

        if ($this->input !== null) {
            $data = $this->input->getVariableValue($input);
        } else {
            $data = null;
        }

        if ($this->for !== null) {
            $root = $this->input->getVariableValue($input);
        } else {
            $root = null;
        }

        if ($subTemplate) {
            $subInput = $input->getChildInput($subTemplate, $data, $root);
            return $subTemplate->apply($subInput);
        } else {
            throw new NodeException("Invalid subtemplate specified in operator '".$this->getOperatorId()."'", $this);
        }
    }

    protected function executeOperator($input, $storeMode = 0)
    {
        
    }

    protected function setParameter(AttrNode $node)
    {
        $id = $node->getAttrId();
        if ($id === '' || $id === 'template' || $id === 'id' || $id === 'source') {
            $this->checkParameterIsAvailable($node, $this->sourceId);
            $this->sourceId = $this->getParameter($node);
            return $this->sourceId;
        }
        if ($id === 'from') {
            $this->checkParameterIsAvailable($node, $this->sourceType);
            $this->sourceType = (string)$this->getParameter($node, false);
            return true;
        }
        if ($id === 'file') {
            $this->checkParameterIsAvailable($node, $this->sourceId, $this->sourceType);
            $this->sourceId = $this->getParameter($node);
            $this->sourceType = 'file';
            return $this->sourceId;
        }
        if ($id === 'for') {
            $this->checkParameterIsAvailable($node, $this->for);
            $this->for = $this->getCodeParameter($node);
            return $this->for;
        }
        if ($id === 'input') {
            $this->checkParameterIsAvailable($node, $this->input);
            $this->input = $this->getCodeParameter($node);
            return $this->input;
        }
        return false;
    }

}
