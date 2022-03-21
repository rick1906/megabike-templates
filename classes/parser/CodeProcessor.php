<?php

namespace megabike\templates\parser;

class CodeProcessor
{
    const MODE_RAW = 0;
    const MODE_NORMAL = 1;
    const MODE_RETURN = 2;
    const MODE_OPERATOR = 3;
    //
    const TYPE_ALLOWED = 1;
    const TYPE_RAW = 2;
    const TYPE_BEFORE_NAME = 4;

    private static $_tokensMap = null;

    public static function getTokensMap()
    {
        if (self::$_tokensMap === null) {
            self::$_tokensMap = self::createTokensMap();
        }
        return self::$_tokensMap;
    }

    private static function createTokensMap()
    {
        $map = array(
            T_ABSTRACT => 0,
            T_AND_EQUAL => 1,
            T_ARRAY => 5,
            T_ARRAY_CAST => 1,
            T_AS => 3,
            T_BOOLEAN_AND => 1,
            T_BOOLEAN_OR => 1,
            T_BOOL_CAST => 1,
            T_BREAK => 7,
            T_CALLABLE => 5, // (*) just name of type (like 'array'), enabled
            T_CASE => 3,
            T_CATCH => 7,
            T_CLASS => 4,
            T_CLASS_C => 0,
            T_CLONE => 1,
            T_CLOSE_TAG => 0,
            T_COMMENT => 3,
            T_CONCAT_EQUAL => 1,
            T_CONST => 4, // (*) constant in classes?, disabled
            T_CONSTANT_ENCAPSED_STRING => 1,
            T_CONTINUE => 7, // (*) enabled
            T_CURLY_OPEN => 1, // {$var} in variable strings, '{' part
            T_DEC => 1,
            T_DECLARE => 4, // http://php.net/manual/en/control-structures.declare.php
            T_DEFAULT => 7,
            T_DIR => 0,
            T_DIV_EQUAL => 1,
            T_DNUMBER => 1,
            T_DOC_COMMENT => 3,
            T_DO => 7,
            T_DOLLAR_OPEN_CURLY_BRACES => 1, // ${var}, used in variable string like {$var}
            T_DOUBLE_ARROW => 1,
            T_DOUBLE_CAST => 1,
            T_DOUBLE_COLON => 5,
            T_ECHO => 3, // (*) enabled
            T_ELSE => 7,
            T_ELSEIF => 7,
            T_EMPTY => 1,
            T_ENCAPSED_AND_WHITESPACE => 1, // constant part of variable string
            T_ENDDECLARE => 0, // see T_DECLARE
            T_ENDFOR => 3,
            T_FOREACH => 3,
            T_ENDIF => 3,
            T_ENDSWITCH => 3,
            T_ENDWHILE => 3,
            T_END_HEREDOC => 3,
            T_EVAL => 0,
            T_EXIT => 0,
            T_EXTENDS => 4,
            T_FILE => 0,
            T_FINAL => 0,
            T_FOR => 7,
            T_FOREACH => 7,
            T_FUNCTION => 7, // (*) only anon functions allowed
            T_FUNC_C => 0,
            T_GLOBAL => 4,
            T_GOTO => 4,
            T_HALT_COMPILER => 0,
            T_IF => 7,
            T_IMPLEMENTS => 4,
            T_INC => 1,
            T_INCLUDE => 0,
            T_INCLUDE_ONCE => 0,
            T_INLINE_HTML => 0,
            T_INSTANCEOF => 5,
            T_INT_CAST => 1,
            T_INTERFACE => 4,
            T_ISSET => 1,
            T_IS_EQUAL => 1,
            T_IS_GREATER_OR_EQUAL => 1,
            T_IS_IDENTICAL => 1,
            T_IS_NOT_EQUAL => 1,
            T_IS_NOT_IDENTICAL => 1,
            T_IS_SMALLER_OR_EQUAL => 1,
            T_LINE => 0,
            T_LIST => 5,
            T_LNUMBER => 1,
            T_LOGICAL_AND => 1,
            T_LOGICAL_OR => 1,
            T_LOGICAL_XOR => 1,
            T_METHOD_C => 0,
            T_MINUS_EQUAL => 1,
            T_MOD_EQUAL => 1,
            T_MUL_EQUAL => 1,
            T_NAMESPACE => 4,
            T_NS_C => 0,
            T_NS_SEPARATOR => 5,
            T_NEW => 5,
            T_NUM_STRING => 1,
            T_OBJECT_CAST => 1,
            T_OBJECT_OPERATOR => 5,
            T_OPEN_TAG => 0,
            T_OPEN_TAG_WITH_ECHO => 0, // (*) disabled
            T_OR_EQUAL => 1,
            T_PAAMAYIM_NEKUDOTAYIM => 5,
            T_PLUS_EQUAL => 1,
            T_PRINT => 3, // (*) enabled
            T_PRIVATE => 0,
            T_PUBLIC => 0,
            T_PROTECTED => 0,
            T_REQUIRE => 0,
            T_REQUIRE_ONCE => 0,
            T_RETURN => 3, // (*) enabled, ok to use in raw code to exit it
            T_SL => 1,
            T_SL_EQUAL => 1,
            T_SR => 1,
            T_SR_EQUAL => 1,
            T_START_HEREDOC => 3,
            T_STATIC => 0, // (*) static is disabled (in functions, and static:: too)
            T_STRING => 5,
            T_STRING_CAST => 1,
            T_STRING_VARNAME => 1,
            T_SWITCH => 7,
            T_THROW => 3,
            T_TRY => 7,
            T_UNSET => 1, // (*) allowed in all code blocks as of now
            T_UNSET_CAST => 1, // (unset) casting - sets var to null
            T_USE => 4,
            T_VAR => 0,
            T_VARIABLE => 1,
            T_WHILE => 7,
            T_WHITESPACE => 1,
            T_XOR_EQUAL => 1,
        );
        if (PHP_VERSION_ID < 70000) {
            $map += array(
                T_CHARACTER => 1,
                T_BAD_CHARACTER => 1, // (*) bad, but enabled
            );
        };
        if (PHP_VERSION_ID >= 50400) {
            $map += array(
                T_INSTEADOF => 4,
                T_TRAIT => 4,
                T_TRAIT_C => 0,
            );
        }
        if (PHP_VERSION_ID >= 50500) {
            $map += array(
                T_FINALLY => 7,
                T_YIELD => 0, // disabled
            );
        }
        if (PHP_VERSION_ID >= 50600) {
            $map += array(
                T_ELLIPSIS => 7,
                T_POW => 1,
                T_POW_EQUAL => 1,
            );
        }
        if (PHP_VERSION_ID >= 70000) {
            $map += array(
                T_SPACESHIP => 1,
            );
        }
        return $map;
    }

    public static function parse($string, $checkBrackets = false)
    {
        $fragment = '<?php'."\n;".$string.";\n".'?>';
        $tokens = @token_get_all($fragment);
        if (!$tokens) {
            return false;
        }

        $max = count($tokens) - 1;
        if (!is_array($tokens[0]) || $tokens[0][0] !== T_OPEN_TAG) {
            return false;
        }
        if (!is_array($tokens[$max]) || $tokens[$max][0] !== T_CLOSE_TAG) {
            return false;
        }

        $start = 1;
        $end = $max - 1;
        while ($start <= $max && isset($tokens[$start][0]) && $tokens[$start][0] === T_WHITESPACE) {
            $start++;
        }
        while ($end >= 0 && isset($tokens[$end][0]) && $tokens[$end][0] === T_WHITESPACE) {
            $end--;
        }

        if ($tokens[$start] !== ';' || $tokens[$end] !== ';') {
            return false;
        }

        $innerTokens = array_slice($tokens, $start + 1, $end - $start - 1);
        if ($checkBrackets && !self::checkBrackets($innerTokens)) {
            return false;
        }
        return $innerTokens;
    }

    private static function opensBracket($id, $value)
    {
        return $id === '{' || ($id === T_CURLY_OPEN || $id === T_DOLLAR_OPEN_CURLY_BRACES) && strpos($value, '{') !== false;
    }

    private static function checkBrackets($tokens)
    {
        $state = 0;
        foreach ($tokens as $token) {
            if ($token === '{' || is_array($token) && self::opensBracket($token[0], $token[1])) {
                $state++;
            } elseif ($token === '}') {
                $state--;
            }
        }
        return $state <= 0;
    }

    public static function transformCode($code, $mode, $config = null)
    {
        $tokens = self::parse($code);
        if ($tokens === false) {
            return array($code, array("Supplied string is not a valid PHP code", 0));
        } else {
            return self::transformCodeTokens($code, $tokens, $mode, $config);
        }
    }

    public static function transformCodeTokens($code, $tokens, $mode, $config = null)
    {
        $p = new CodeProcessor($code, $tokens, $mode, $config);
        $buffer = $p->transform();
        return array($buffer, $p->getError());
    }

    const ERROR_VAR_CLASS = "Variable classes are not allowed in templates";
    const ERROR_VAR_FUNC = "Calling variable functions is not allowed in templates";
    const ERROR_SPECIAL = "Special variable syntax is not allowed inside functions";
    const ERROR_FUNC_NAMED = "Named function declaration is not allowed in templates";
    const ERROR_FUNC_USE = "Passing variables from parent scope to functions is not allowed in templates";
    const ERROR_FUNC_CLOSURE = "Calling closures is not allowed inside functions";

    private $tokensMap;
    private $count;
    //
    private $code;
    private $tokens;
    private $mode;
    private $config;
    //
    private $buffer;
    private $offset;
    private $error;
    private $skipTo;
    private $returnSet;
    private $prevId;
    private $relevantCount;
    private $bracketState;
    private $pathMode;

    public function __construct($code, $tokens, $mode, $config = null)
    {
        $this->code = $code;
        $this->tokens = $tokens;
        $this->mode = $mode;
        $this->tokensMap = static::getTokensMap();
        $this->count = count($tokens);
        $this->config = null;
    }

    private function reset()
    {
        $this->buffer = '';
        $this->offset = 0;
        $this->error = null;
        $this->skipTo = 0;
        $this->returnSet = false;
        $this->prevId = null;
        $this->relevantCount = 0;
        $this->bracketState = null;
        $this->pathMode = null;
    }

    private function error($string, $offset = null)
    {
        $this->error = array($string, ($offset === null ? $this->offset : $offset));
        return false;
    }

    private function token($i)
    {
        if (!isset($this->tokens[$i])) {
            return array(null, null, -1);
        } elseif (is_array($this->tokens[$i])) {
            list($id, $value) = $this->tokens[$i];
            $type = isset($this->tokensMap[$id]) ? $this->tokensMap[$id] : -1;
        } else {
            $id = $value = $this->tokens[$i];
            $type = 1;
        }
        return array($id, $value, $type);
    }

    private function tokenId($i)
    {
        if (!isset($this->tokens[$i])) {
            return null;
        } elseif (is_array($this->tokens[$i])) {
            return $this->tokens[$i][0];
        } else {
            return $this->tokens[$i];
        }
    }

    private function neighbor($index, $shift = 1)
    {
        while (true) {
            $index += $shift;
            if (isset($this->tokens[$index][0])) {
                $id = $this->tokens[$index][0];
                if ($id !== T_WHITESPACE && $id !== T_COMMENT && $id !== T_DOC_COMMENT) {
                    return array($id, $index);
                }
            } else {
                return array(null, null);
            }
        }
        return array(null, null);
    }

    private function neighborId($index, $shift = 1)
    {
        while (true) {
            $index += $shift;
            if (isset($this->tokens[$index][0])) {
                $id = $this->tokens[$index][0];
                if ($id !== T_WHITESPACE && $id !== T_COMMENT && $id !== T_DOC_COMMENT) {
                    return $id;
                }
            } else {
                return null;
            }
        }
        return null;
    }

    private function makeString($index, $allowedIds)
    {
        $buffer = '';
        while (isset($this->tokens[$index][0])) {
            $id = $this->tokens[$index][0];
            if ($id !== T_WHITESPACE && $id !== T_COMMENT && $id !== T_DOC_COMMENT && !in_array($id, $allowedIds, true)) {
                break;
            } else {
                $buffer .= is_array($this->tokens[$index]) ? $this->tokens[$index][1] : $this->tokens[$index];
                $index++;
            }
        }
        return array($buffer, $index);
    }

    private function bracketCounterOpen()
    {
        if (!empty($this->bracketState)) {
            array_unshift($this->bracketState, 0);
        } else {
            $this->bracketState = array(0);
        }
    }

    private function bracketCounterClose()
    {
        if (count($this->bracketState) > 1) {
            array_shift($this->bracketState);
        } else {
            $this->bracketState = null;
        }
    }

    private function bracketContext()
    {
        if (empty($this->bracketState)) {
            return 0;
        } else {
            return $this->bracketState[0] === 0 ? 1 : 2;
        }
    }

    private function processPathMode($id, $index)
    {
        if ($this->pathMode[0] === 0) {
            if ($id === '.' || $id === '/' || $id === T_OBJECT_OPERATOR) {
                if (isset($this->tokens[$index + 1][0])) {
                    $nextId = $this->tokens[$index + 1][0];
                    if ($nextId === '@' && isset($this->tokens[$index + 2][0]) && $this->tokens[$index + 2][0] === T_STRING) {
                        $this->skipTo = $index + 3;
                        return '["'.addslashes($this->tokens[$index + 2][1]).'"]';
                    } elseif ($nextId === T_STRING) {
                        if (isset($this->tokens[$index + 2][0]) && isset($this->tokens[$index + 3][0])) {
                            if ($this->tokens[$index + 2][0] === '(' && $this->tokens[$index + 3][0] === ')') {
                                $nextValue = $this->tokens[$index + 1][1];
                                if ($nextValue === 'node' || $nextValue === 'parent') {
                                    $this->skipTo = $index + 4;
                                    return '->{"'.$nextValue.'()"}';
                                }
                            }
                        }
                        $this->skipTo = $index + 2;
                        return '->'.$this->tokens[$index + 1][1];
                    }
                }
            } elseif ($id === '[') {
                $this->pathMode[0]++;
                return '[';
            }
            $this->stopPathMode();
        }

        if ($id === '[') {
            $this->pathMode[0]++;
        } elseif ($id === ']') {
            $this->pathMode[0]--;
        }
        return null;
    }

    private function startPathMode()
    {
        if (!empty($this->pathMode)) {
            array_unshift($this->pathMode, 0);
        } else {
            $this->pathMode = array(0);
        }
    }

    private function stopPathMode()
    {
        if (count($this->pathMode) > 1) {
            array_shift($this->pathMode);
        } else {
            $this->pathMode = null;
        }
    }

    private function processClassCall($index)//не только классы, но и функции в неймспейсах! и переменные!
    {
        list($class, $nextIndex) = $this->makeString($index, array(T_STRING, T_NS_SEPARATOR));
        $nextId = $this->tokenId($nextIndex);
        if ($this->prevId === T_NEW) {
            return $this->processClassNew($class, $nextIndex, $nextId);
        } elseif ($nextId === '(') {
            return $this->processCall($class, $nextIndex, true);
        } elseif ($nextId !== T_PAAMAYIM_NEKUDOTAYIM && $nextId !== T_DOUBLE_COLON) {
            return null;
        } else {
            list($nextId2, $nextIndex2) = $this->neighbor($nextIndex);
            return $this->processClassStatic($class, $nextIndex, $nextIndex2, $nextId2);
        }
    }

    private function processCall($value, $bracketIndex, $inNs = false)
    {
        $this->skipTo = $bracketIndex;
        return '$__caller["'.addslashes($value).'"]->invoke';
    }

    private function processClassStatic($class, $opIndex, $nextIndex, $nextId)
    {
        if ($nextId === T_STRING) {
            list($nextId2, $nextIndex2) = $this->neighbor($nextIndex);
            if ($nextId2 === '(') {
                $value = $this->tokens[$nextIndex][1];
                $this->skipTo = $nextIndex2 + 1;
                list($nextId3, $nextIndex3) = $this->neighbor($nextIndex2);
                if ($nextId3 === ')') {
                    $this->skipTo = $nextIndex3 + 1;
                    return '$__caller[":'.addslashes($class).'"]["'.addslashes($value).'"]->invoke(null)';
                } else {
                    return '$__caller[":'.addslashes($class).'"]["'.addslashes($value).'"]->invoke(null, ';
                }
            } else {
                $this->skipTo = $opIndex + 1;
                return '$__caller[":'.addslashes($class).'"]->';
            }
        } elseif ($nextId === T_VARIABLE) {
            $value = $this->tokens[$nextIndex][1];
            $this->skipTo = $nextIndex + 1;
            return '$__caller[":'.addslashes($class).'"]["'.addslashes($value).'"]';
        } else {
            $this->skipTo = $opIndex + 1;
            return '$__caller[":'.addslashes($class).'"]->';
        }
    }

    private function processClassNew($class, $nextIndex, $nextId)
    {
        if ($nextId === '(') {
            $this->skipTo = $nextIndex;
            return '$__caller[":'.addslashes($class).'"][":"]';
        } else {
            return $this->error(self::ERROR_VAR_CLASS);
        }
    }

    private function processToken($id, $value, $type, $index)
    {
        if ($this->pathMode !== null) {
            $result = $this->processPathMode($id, $index);
            if ($result !== null) {
                return $result;
            }
        }

        if ($id === T_STRING) {
            $prevId = $this->prevId;
            $prevType = ($prevId !== null && isset($this->tokensMap[$prevId])) ? $this->tokensMap[$prevId] : 1;
            if ($prevId === T_NEW) {
                list($class, $nextIndex) = $this->makeString($index, array(T_STRING, T_NS_SEPARATOR));
                $nextId = $this->tokenId($nextIndex);
                return $this->processClassNew($class, $nextIndex, $nextId);
            } elseif ($prevType & self::TYPE_BEFORE_NAME) {
                return null;
            } else {
                $valueLc = strtolower($value);
                list($nextId, $nextIndex) = $this->neighbor($index);
                if ($valueLc === 'true' || $valueLc === 'false' || $valueLc === 'null') {
                    return null;
                } elseif ($nextId === T_DOUBLE_COLON || $nextId === T_PAAMAYIM_NEKUDOTAYIM || $nextId === T_NS_SEPARATOR) {
                    return $this->processClassCall($index);
                } elseif ($this->bracketContext() === 2) {
                    if ($nextId === '(') {
                        return $this->processCall($value, $nextIndex);
                    } else {
                        return $this->error(self::ERROR_SPECIAL);
                    }
                } elseif ($nextId === '(') {
                    if ($value === 'attr' || $value === 'value' || $value === 'free') { // functions with no 'func()' special node
                        return '$__wrapper->'.$value;
                    } elseif ($value === 'node' || $value === 'parent') { // functions with 'func()' special node
                        list($nextId2, $nextIndex2) = $this->neighbor($nextIndex);
                        if ($nextId2 === ')') { // if function is called without arguments
                            $this->startPathMode();
                            $this->skipTo = $nextIndex2 + 1;
                            return '$__wrapper->{"'.$value.'()"}';
                        } else {
                            return '$__wrapper->'.$value;
                        }
                    } else {
                        return $this->processCall($value, $nextIndex);
                    }
                } else {
                    $bufend = strlen($this->buffer) - 1;
                    if ($index > 0 && $this->tokens[$index - 1] === '@' && $this->buffer[$bufend] === '@') {
                        $this->buffer[$bufend] = '$';
                        return '__wrapper["'.addslashes($value).'"]';
                    } else {////////TODO: russian unquoted strings
                        $this->startPathMode();
                        return '$__wrapper->'.$value;
                    }
                }
            }
        }

        if ($id === '=' && $this->relevantCount === 0) {
            $this->returnSet = true;
            return '$__result =';
        }

        if ($id === T_VARIABLE) {
            if ($this->bracketContext() === 0) {
                if ($this->prevId === T_OBJECT_OPERATOR) {
                    $nextId = $this->neighborId($index);
                    if ($nextId === '(') {
                        return $this->error(self::ERROR_VAR_FUNC);
                    } else {
                        return '{$__vars->'.ltrim($value, '$').'}';
                    }
                } else {
                    return '$__vars->'.ltrim($value, '$');
                }
            } else {
                return null;
            }
        }

        if ($id === T_STRING_VARNAME) {
            if ($this->bracketContext() === 0) {
                return '__vars->'.$value;
            } else {
                return null;
            }
        }

        if ($id === T_DOLLAR_OPEN_CURLY_BRACES) {
            if (isset($this->tokens[$index + 1][0]) && $this->tokens[$index + 1][0] === T_STRING_VARNAME) {
                return '{$';
            } else {
                return null;
            }
        }

        if ($id === T_FUNCTION) {
            if ($this->neighborId($index) === T_STRING) {
                return $this->error(self::ERROR_FUNC_NAMED);
            } else {
                $this->bracketCounterOpen();
                return null;
            }
        }

        if ($id === T_USE) {
            if ($this->bracketContext() === 1) {
                return $this->error(self::ERROR_FUNC_USE);
            } else {
                return null;
            }
        }

        if ($id === '$') {
            $nextId = $this->neighborId($index);
            if ($nextId === '{' || $nextId === T_VARIABLE) {
                return '$__vars->';
            } else {
                return '$';
            }
        }

        if (($id === T_PAAMAYIM_NEKUDOTAYIM || $id === T_DOUBLE_COLON) && $this->prevId !== T_STRING) {
            return $this->error(self::ERROR_VAR_CLASS);
        }

        if ($id === T_NEW) {
            $nextId = $this->neighborId($index);
            if ($nextId !== T_STRING && $nextId !== T_NS_SEPARATOR) {
                return $this->error(self::ERROR_VAR_CLASS);
            } else {
                return null;
            }
        }

        if ($id === '(' && ($this->prevId === ']' || $this->prevId === '}' || $this->prevId === T_VARIABLE)) {
            if ($this->prevId === T_VARIABLE) {
                if ($this->bracketContext() === 2) {
                    return $this->error(self::ERROR_FUNC_CLOSURE);
                } else {
                    return null;
                }
            } else {
                return $this->error(self::ERROR_VAR_FUNC);
            }
        }

        if ($id === T_NS_SEPARATOR && $this->prevId !== T_STRING) {
            $nextId = $this->neighborId($index);
            if ($nextId === T_STRING) {
                return $this->processClassCall($index);
            } else {
                return null;
            }
        }

        if ($id === '{') {
            if ($this->bracketContext() === 1) {
                return 'use ($__caller) {';
            } else {
                return null;
            }
        }

        return null;
    }

    public function transform()
    {
        $index = 0;
        $this->reset();
        while ($index < $this->count) {
            list($id, $value, $type) = $this->token($index);

            $pos = strpos($this->code, $value, $this->offset);
            $len = strlen($value);
            if ($pos === false) {
                return $this->error("Parsing code failed, PHP tokens are inconsistent");
            }

            if ($index >= $this->skipTo) {
                if ($pos > $this->offset) {
                    $this->buffer .= substr($this->code, $this->offset, $pos - $this->offset);
                }

                if ($type < 0) {
                    return $this->error("PHP token ".token_name($id)." is not yet implemented in templates");
                } elseif (!($type & self::TYPE_ALLOWED)) {
                    return $this->error("PHP token ".token_name($id)." is not allowed in templates");
                } elseif ($this->mode !== self::MODE_RAW && ($type & self::TYPE_RAW)) {
                    return $this->error("PHP token ".token_name($id)." is allowed only inside raw-code elements");
                }

                $result = $this->processToken($id, $value, $type, $index);
                if ($result === false) {
                    return false;
                } elseif ($result === null) {
                    $this->buffer .= $value;
                } else {
                    $this->buffer .= $result;
                }
            }

            $index++;
            $this->offset = $pos + $len;
            if ($id !== T_WHITESPACE && $id !== T_COMMENT && $id !== T_DOC_COMMENT) {
                $this->relevantCount++;
                $this->prevId = $id;
            }
            if ($this->bracketState !== null) {
                if ($id === '}') {
                    $this->bracketState[0]--;
                    if ($this->bracketState[0] <= 0) {
                        $this->bracketCounterClose();
                    }
                } elseif (self::opensBracket($id, $value)) {
                    $this->bracketState[0]++;
                }
            }
        }

        if (!$this->returnSet && $this->mode === self::MODE_RETURN) {
            $this->buffer = '$__result = '.ltrim($this->buffer);
        }
        if ($this->mode !== self::MODE_OPERATOR) {
            $this->buffer = rtrim(rtrim($this->buffer), ';').';';
        } else {
            $this->buffer = trim($this->buffer);
        }
        return $this->buffer;
    }

    public function getBuffer()
    {
        return $this->buffer;
    }

    public function getError()
    {
        return $this->error;
    }

}

/*
T_ABSTRACT 	abstract 	Class Abstraction (available since PHP 5.0.0)
T_AND_EQUAL 	&= 	assignment operators
T_ARRAY 	array() 	array(), array syntax
T_ARRAY_CAST 	(array) 	type-casting
T_AS 	as 	foreach
T_BAD_CHARACTER 	  	anything below ASCII 32 except \t (0x09), \n (0x0a) and \r (0x0d)
T_BOOLEAN_AND 	&& 	logical operators
T_BOOLEAN_OR 	|| 	logical operators
T_BOOL_CAST 	(bool) or (boolean) 	type-casting
T_BREAK 	break 	break
T_CALLABLE 	callable 	callable
T_CASE 	case 	switch
T_CATCH 	catch 	Exceptions (available since PHP 5.0.0)
T_CHARACTER 	  	not used anymore
T_CLASS 	class 	classes and objects
T_CLASS_C 	__CLASS__ 	magic constants
T_CLONE 	clone 	classes and objects
T_CLOSE_TAG 	?> or %> 	escaping from HTML
T_COMMENT 	// or #, and /STAR STAR/ 	comments
T_CONCAT_EQUAL 	.= 	assignment operators
T_CONST 	const 	class constants
T_CONSTANT_ENCAPSED_STRING 	"foo" or 'bar' 	string syntax
T_CONTINUE 	continue 	continue
T_CURLY_OPEN 	{$ 	complex variable parsed syntax
T_DEC 	-- 	incrementing/decrementing operators
T_DECLARE 	declare 	declare
T_DEFAULT 	default 	switch
T_DIR 	__DIR__ 	magic constants (available since PHP 5.3.0)
T_DIV_EQUAL 	/= 	assignment operators
T_DNUMBER 	0.12, etc. 	floating point numbers
T_DOC_COMMENT 	/** STAR/ 	PHPDoc style comments
T_DO 	do 	do..while
T_DOLLAR_OPEN_CURLY_BRACES 	${ 	complex variable parsed syntax
T_DOUBLE_ARROW 	=> 	array syntax
T_DOUBLE_CAST 	(real), (double) or (float) 	type-casting
T_DOUBLE_COLON 	:: 	see T_PAAMAYIM_NEKUDOTAYIM below
T_ECHO 	echo 	echo
T_ELLIPSIS 	... 	function arguments (available since PHP 5.6.0)
T_ELSE 	else 	else
T_ELSEIF 	elseif 	elseif
T_EMPTY 	empty 	empty()
T_ENCAPSED_AND_WHITESPACE 	" $a" 	constant part of string with variables
T_ENDDECLARE 	enddeclare 	declare, alternative syntax
T_ENDFOR 	endfor 	for, alternative syntax
T_ENDFOREACH 	endforeach 	foreach, alternative syntax
T_ENDIF 	endif 	if, alternative syntax
T_ENDSWITCH 	endswitch 	switch, alternative syntax
T_ENDWHILE 	endwhile 	while, alternative syntax
T_END_HEREDOC 	  	heredoc syntax
T_EVAL 	eval() 	eval()
T_EXIT 	exit or die 	exit(), die()
T_EXTENDS 	extends 	extends, classes and objects
T_FILE 	__FILE__ 	magic constants
T_FINAL 	final 	Final Keyword
T_FINALLY 	finally 	Exceptions (available since PHP 5.5.0)
T_FOR 	for 	for
T_FOREACH 	foreach 	foreach
T_FUNCTION 	function or cfunction 	functions
T_FUNC_C 	__FUNCTION__ 	magic constants
T_GLOBAL 	global 	variable scope
T_GOTO 	goto 	(available since PHP 5.3.0)
T_HALT_COMPILER 	__halt_compiler() 	__halt_compiler (available since PHP 5.1.0)
T_IF 	if 	if
T_IMPLEMENTS 	implements 	Object Interfaces
T_INC 	++ 	incrementing/decrementing operators
T_INCLUDE 	include() 	include
T_INCLUDE_ONCE 	include_once() 	include_once
T_INLINE_HTML 	  	text outside PHP
T_INSTANCEOF 	instanceof 	type operators
T_INSTEADOF 	insteadof 	Traits (available since PHP 5.4.0)
T_INT_CAST 	(int) or (integer) 	type-casting
T_INTERFACE 	interface 	Object Interfaces
T_ISSET 	isset() 	isset()
T_IS_EQUAL 	== 	comparison operators
T_IS_GREATER_OR_EQUAL 	>= 	comparison operators
T_IS_IDENTICAL 	=== 	comparison operators
T_IS_NOT_EQUAL 	!= or <> 	comparison operators
T_IS_NOT_IDENTICAL 	!== 	comparison operators
T_IS_SMALLER_OR_EQUAL 	<= 	comparison operators
T_SPACESHIP 	<=> 	comparison operators (available since PHP 7.0.0)
T_LINE 	__LINE__ 	magic constants
T_LIST 	list() 	list()
T_LNUMBER 	123, 012, 0x1ac, etc. 	integers
T_LOGICAL_AND 	and 	logical operators
T_LOGICAL_OR 	or 	logical operators
T_LOGICAL_XOR 	xor 	logical operators
T_METHOD_C 	__METHOD__ 	magic constants
T_MINUS_EQUAL 	-= 	assignment operators
T_MOD_EQUAL 	%= 	assignment operators
T_MUL_EQUAL 	*= 	assignment operators
T_NAMESPACE 	namespace 	namespaces (available since PHP 5.3.0)
T_NS_C 	__NAMESPACE__ 	namespaces (available since PHP 5.3.0)
T_NS_SEPARATOR 	\ 	namespaces (available since PHP 5.3.0)
T_NEW 	new 	classes and objects
T_NUM_STRING 	"$a[0]" 	numeric array index inside string
T_OBJECT_CAST 	(object) 	type-casting
T_OBJECT_OPERATOR 	-> 	classes and objects
T_OPEN_TAG 	<?php, <? or <% 	escaping from HTML
T_OPEN_TAG_WITH_ECHO 	<?= or <%= 	escaping from HTML
T_OR_EQUAL 	|= 	assignment operators
T_PAAMAYIM_NEKUDOTAYIM 	:: 	::. Also defined as T_DOUBLE_COLON.
T_PLUS_EQUAL 	+= 	assignment operators
T_POW 	** 	arithmetic operators (available since PHP 5.6.0)
T_POW_EQUAL 	**= 	assignment operators (available since PHP 5.6.0)
T_PRINT 	print() 	print
T_PRIVATE 	private 	classes and objects
T_PUBLIC 	public 	classes and objects
T_PROTECTED 	protected 	classes and objects
T_REQUIRE 	require() 	require
T_REQUIRE_ONCE 	require_once() 	require_once
T_RETURN 	return 	returning values
T_SL 	<< 	bitwise operators
T_SL_EQUAL 	<<= 	assignment operators
T_SR 	>> 	bitwise operators
T_SR_EQUAL 	>>= 	assignment operators
T_START_HEREDOC 	<<< 	heredoc syntax
T_STATIC 	static 	variable scope
T_STRING 	parent, self, etc. 	identifiers, e.g. keywords like parent and self, function names, class names and more are matched. See also T_CONSTANT_ENCAPSED_STRING.
T_STRING_CAST 	(string) 	type-casting
T_STRING_VARNAME 	"${a 	complex variable parsed syntax
T_SWITCH 	switch 	switch
T_THROW 	throw 	Exceptions
T_TRAIT 	trait 	Traits (available since PHP 5.4.0)
T_TRAIT_C 	__TRAIT__ 	__TRAIT__ (available since PHP 5.4.0)
T_TRY 	try 	Exceptions
T_UNSET 	unset() 	unset()
T_UNSET_CAST 	(unset) 	type-casting
T_USE 	use 	namespaces (available since PHP 5.3.0; reserved since PHP 4.0.0)
T_VAR 	var 	classes and objects
T_VARIABLE 	$foo 	variables
T_WHILE 	while 	while, do..while
T_WHITESPACE 	\t \r\n 	 
T_XOR_EQUAL 	^= 	assignment operators
T_YIELD 	yield 	generators (available since PHP 5.5.0)
*/