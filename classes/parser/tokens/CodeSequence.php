<?php

namespace megabike\templates\parser\tokens;

use megabike\templates\Content;
use megabike\templates\nodes\CodeNode;
use megabike\templates\nodes\operators\OperatorNode;
use megabike\templates\parser\Parser;
use megabike\templates\parser\CodeProcessor;
use megabike\templates\parser\tokens\MasterToken;
use megabike\templates\parser\tokens\OperatorCloseToken;
use megabike\templates\errors\ParseException;

class CodeSequence extends MasterToken
{
    const OPERATOR_REGEXP = '/^[\w\-]+$/';
    const ATTRNAME_REGEXP = '/^[\w\-:]+$/';

    public static function getPhpCodeInfo(Content $content, $index, $closeTag, $checkBrackets = false)
    {
        $offset = $index;
        $length = $content->getLength();
        do {
            $closeIndex = $content->indexOf($closeTag, $offset);
            if ($closeIndex === false) {
                return array(false, false, $index);
            }

            $offset = $closeIndex + 1;
            $code = $content->getSubstring($index, $closeIndex - $index);
            $tokens = CodeProcessor::parse($code, $checkBrackets);
            if ($tokens !== false) {
                return array($code, $tokens, $closeIndex);
            }
        } while ($closeIndex < $length);
        return array(false, false, $index);
    }

    protected $code;
    protected $codeTokens;
    protected $raw;
    protected $openTag;
    protected $opInfo;

    public function __construct($index, $openTag, $code, $tokens, $raw = false)
    {
        parent::__construct($index);
        $this->openTag = strtolower($openTag);
        $this->codeTokens = $tokens;
        $this->code = $code;
        $this->raw = $raw;
        $this->opInfo = $this->detectOperator();
    }

    protected function getOpenTag()
    {
        return $this->openTag;
    }

    protected function getCloseTag()
    {
        if ($this->openTag === '{{') {
            return '}}';
        } elseif ($this->openTag === '{') {
            return '}';
        } else {
            return '?>';
        }
    }

    protected function detectOperator()
    {
        $startIndex = null;
        $token0 = null;
        $token1 = null;
        foreach ($this->codeTokens as $i => $token) {
            if ($token[0] === T_WHITESPACE) {
                continue;
            } else {
                if ($token0 === null) {
                    $token0 = $token;
                    $startIndex = $i;
                } elseif ($token1 === null) {
                    $token1 = $token;
                    break;
                }
            }
        }

        $operator = null;
        $tagMode = false;
        $closeTag = false;
        if ($token0 === null) {
            return null;
        } elseif (is_array($token0) && preg_match(self::OPERATOR_REGEXP, $token0[1])) {
            if ($this->isEndOpToken($token0) && strtolower(substr($token0[1], 0, 3)) === 'end') {
                $operator = substr($token0[1], 3);
                $closeTag = true;
            } elseif ($token1 === '(') {
                $operator = $token0[1];
                $tagMode = false;
            } elseif ($startIndex === 0 && $this->openTag === '{{') {
                if ($token1 === null) {
                    $operator = $token0[1];
                    $tagMode = true;
                } else {
                    $operator = $this->getOperatorOpenTag($token0, $startIndex + 1);
                    $tagMode = true;
                }
            }
        } elseif ($startIndex === 0 && $this->openTag === '{{' && $token0 === '/' && isset($this->codeTokens[1])) {
            $token1 = $this->codeTokens[1];
            if (is_array($token1) && preg_match(self::OPERATOR_REGEXP, $token1[1])) {
                $operator = $this->getOperatorCloseTag($token1, 2);
                $closeTag = true;
            }
        }

        if ($operator !== null) {
            return array($operator, $tagMode, $closeTag);
        } else {
            return null;
        }
    }

    public function isOperator()
    {
        return $this->opInfo !== null;
    }

    public function isOperatorClose()
    {
        return $this->opInfo !== null && $this->opInfo[2];
    }

    public function isOperatorTag()
    {
        return $this->opInfo !== null && $this->opInfo[1];
    }

    public function getOperator()
    {
        return $this->opInfo !== null ? $this->opInfo[0] : null;
    }

    public function getOperatorId()
    {
        return $this->opInfo !== null ? $this->opInfo[0] : null;
    }

    public function disableOperator()
    {
        $this->opInfo = null;
    }

    public function toString()
    {
        return $this->openTag.$this->code.$this->getCloseTag();
    }

    protected function processAttributeTokens(OperatorNode $node, Parser $parser)
    {
        foreach ($this->getTokens() as $token) {
            if ($token instanceof XmlAttrToken) {
                $attrNode = $token->createNode($parser);
                $node->addAttrNode($attrNode);
                continue;
            }
            throw new ParseException($parser->getSource(), $this->index, "Open-tag of operator '{$this->getOperator()}' contains invalid content");
        }
        return $node;
    }

    protected function isEndOpToken($token)
    {
        if (is_array($token)) {
            $id = $token[0];
            return in_array($id, array(T_ENDDECLARE, T_ENDFOR, T_ENDFOREACH, T_ENDIF, T_ENDSWITCH, T_ENDWHILE));
        } else {
            return false;
        }
    }

    protected function getOperatorOpenTag($opToken, $tokensIndex)
    {
        $count = count($this->codeTokens);
        for ($i = $tokensIndex; $i < $count; ++$i) {
            $token = $this->codeTokens[$i];
            if ($token === '=' || $token === '}' || $token === '"' || $token === "'") {
                return $opToken[1];
            } elseif (is_array($token)) {
                if ($token[0] === T_CONSTANT_ENCAPSED_STRING) {
                    return $opToken[1];
                } elseif ($token[0] === T_WHITESPACE || preg_match(self::ATTRNAME_REGEXP, $token[1])) {
                    continue;
                } else {
                    return null;
                }
            } elseif ($token === ':' || $token === '-') {
                continue;
            } else {
                return null;
            }
        }
        return $opToken[1];
    }

    protected function getOperatorCloseTag($opToken, $tokensIndex)
    {
        $count = count($this->codeTokens);
        for ($i = $tokensIndex; $i < $count; ++$i) {
            $token = $this->codeTokens[$i];
            $id = is_array($token) ? $token[0] : $token;
            if ($id === T_WHITESPACE) {
                continue;
            } else {
                return null;
            }
        }
        return $opToken[1];
    }

    protected function getOperatorTokens()
    {
        $counter = 0;
        $startIndex = null;
        $endIndex = null;
        $colon = false;
        foreach ($this->codeTokens as $i => $token) {
            $id = is_array($token) ? $token[0] : $token;
            if ($id === T_WHITESPACE || $id === T_COMMENT || $id === T_DOC_COMMENT) {
                continue;
            }
            if ($colon) {
                return false;
            } elseif ($endIndex !== null) {
                if ($id === ':') {
                    $colon = true;
                } else {
                    return false;
                }
            } if ($id === '(') {
                $counter++;
                if ($startIndex === null) {
                    $startIndex = $i;
                }
            } elseif ($id === ')') {
                if ($counter > 0) {
                    $counter--;
                    if ($counter === 0) {
                        $endIndex = $i;
                    }
                } else {
                    return false;
                }
            }
        }
        if ($colon) {
            return array_slice($this->codeTokens, $startIndex, $endIndex - $startIndex + 1);
        } else {
            return false;
        }
    }

    public function transform()
    {
        if ($this->isOperatorClose()) {
            return new OperatorCloseToken($this->index, $this->getOperator());
        } else {
            return $this;
        }
    }

    public function createNode(Parser $parser)
    {
        if ($this->opInfo !== null) {
            $operatorId = $this->getOperatorId();
            $tagMode = $this->isOperatorTag();
            $closeTag = $this->isOperatorClose();
            $class = $parser->getOperatorClass($operatorId);
            if ($class !== null && !$closeTag) {
                if ($tagMode) {
                    $node = new $class(null, $this);
                    $this->processAttributeTokens($node, $parser);
                    return $node;
                } else {
                    $tokens = $this->getOperatorTokens();
                    if ($tokens) {
                        $p1 = strpos($this->code, '(');
                        $p2 = strrpos($this->code, ')');
                        if ($p1 !== false && $p2 !== false) {
                            $code = substr($this->code, $p1, $p2 - $p1 + 1);
                            $node = new $class(null, $this);
                            $node->setOperatorCode($code, $tokens);
                            return $node;
                        }
                    }
                }
            }
        } elseif (!empty($this->tokens)) {
            throw new ParseException($parser->getSource(), $this->index, "Invalid operator open-tag");
        }

        if ($this->openTag === '{') {
            $mode = CodeProcessor::MODE_RETURN;
        } elseif ($this->openTag === '{{' || $this->openTag === '<?') {
            $mode = CodeProcessor::MODE_NORMAL;
        } else {
            $mode = CodeProcessor::MODE_RAW;
        }
        $codeParams = array($this->code, $this->codeTokens, $mode, $this->openTag);
        return new CodeNode($codeParams, $this);
    }

}