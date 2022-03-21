<?php

namespace megabike\templates\nodes;

use megabike\templates\Input;
use megabike\templates\nodes\Node;
use megabike\templates\nodes\AttrNode;
use megabike\templates\parser\Parser;
use megabike\templates\parser\CodeProcessor;
use megabike\templates\errors\NodeException;
use megabike\templates\errors\ParseException;

class CodeNode extends Node
{
    protected $codeParams;
    protected $executor;
    protected $usesWrapper = true;
    protected $usesCaller = true;

    public function __construct($codeParams, $token = null)
    {
        parent::__construct($token);
        $this->codeParams = $codeParams;
        $this->executor = null;
    }

    public function __sleep()
    {
        $vars = get_object_vars($this);
        unset($vars['executor']);
        return array_keys($vars);
    }

    public function initializeNode(Parser $parser)
    {
        list($code, $tokens, $mode) = $this->codeParams;
        if ($this->parentNode !== null && $this->parentNode instanceof AttrNode) {
            $mode = CodeProcessor::MODE_RETURN;
        }

        list($resultCode, $error) = CodeProcessor::transformCodeTokens($code, $tokens, $mode, $parser->getConfig());
        if ($error !== null) {
            $index = $this->getErrorIndex($parser, $this->codeParams, $error);
            throw new ParseException($parser->getSource(), $index, $error[0]);
        }

        $this->codeParams = (string)$resultCode;
        $this->getExecutor();
    }

    protected function getErrorIndex(Parser $parser, $codeParams, $error)
    {
        if ($this->index !== null && isset($codeParams[3])) {
            $offset = $error[1];
            $string = $codeParams[3].substr($codeParams[0], 0, $offset);
            $length = $parser->getSource()->getContent()->computeLength($string);
            return $this->index + $length;
        } else {
            return null;
        }
    }

    protected function buildExecutorCode($code)
    {
        $this->usesWrapper = strpos($code, '$__wrapper') !== false;
        $this->usesCaller = strpos($code, '$__caller') !== false;
        return 'return function ($__vars, $__caller, $__wrapper, &$__result) { '.$code.' };';
    }

    protected function buildExecutor($code)
    {
        $eval = $this->buildExecutorCode($code);

        $err0 = error_get_last();
        $result = @eval($eval);
        $err1 = error_get_last();

        if ($err1 && $err0 !== $err1) {
            @trigger_error('', E_USER_NOTICE); // reset error_get_last()
            $message = $err1['message'];
            $line = $err1['line'];
            throw new NodeException(ucfirst($message)." on line {$line} in code node", $this);
        }

        if ($result) {
            return $result;
        } else {
            throw new NodeException("Parsing error in code node", $this);
        }
    }

    protected function getExecutor()
    {
        if ($this->executor === null) {
            if (is_array($this->codeParams)) {
                throw new NodeException("Code node is not initialized", $this);
            }
            $this->executor = $this->buildExecutor($this->codeParams);
        }
        return $this->executor;
    }

    public function isConstant()
    {
        return false;
    }

    protected function computeIsConstant()
    {
        return false;
    }

    public function executeCode(Input $input)
    {
        $executor = $this->getExecutor();
        $wrapper = $this->usesWrapper ? $input->getWrapper() : null;
        if ($this->usesCaller) {
            $caller = $input->getCaller();
            $level = ob_get_level();
            $buffer = '';
            @ob_start();
            $result1 = null;
            $result2 = $executor($input, $caller, $wrapper, $result1);
            while (ob_get_level() > $level) {
                $buffer .= (string)@ob_get_clean();
            }
            $result = $result2 !== null ? $result2 : $result1;
            return $buffer !== '' ? ($buffer.$result) : $result;
        } else {
            $result1 = null;
            $result2 = $executor($input, null, $wrapper, $result1);
            return $result2 !== null ? $result2 : $result1;
        }
    }

    public function getVariableValue(Input $input = null)
    {
        if ($input !== null) {
            $this->applyInput($input);
            return isset($this->storage['']['_result']) ? $this->storage['']['_result'] : null;
        } else {
            return null;
        }
    }

    public function getValue(Input $input = null, $storeMode = 0)
    {
        return $this->getVariableValue($input);
    }

    protected function computeValue($input = null, $storeMode = 0)
    {
        return $this->getVariableValue($input);
    }

    protected function execute($input, $storeMode = 0)
    {
        $this->storage['']['_result'] = $this->executeCode($input);
    }

}
