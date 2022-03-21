<?php

namespace megabike\templates\parser;

use megabike\templates\Source;
use megabike\templates\Template;
use megabike\templates\TemplatesConfig;
use megabike\templates\TemplatesModule;
use megabike\templates\nodes\TextNode;
use megabike\templates\nodes\root\Scheme;
use megabike\templates\parser\modes\Mode;
use megabike\templates\parser\tokens\Token;
use megabike\templates\parser\TokensCollection;
use megabike\templates\parser\ParserStatus;
use megabike\templates\errors\ParseWarning;
use megabike\templates\errors\ParseException;
use megabike\templates\errors\NodeException;
use megabike\templates\errors\BuildException;

//TODO: сделать всЄ как в bike
//TODO: класс конфигурации
//TODO: брать конфигурацию на основе класса шаблона
//TODO: CDATA
//
//???
//“акой вариант: делаютс¤ регэксповые эвенты, у каждого задаЄтс¤ миндлина и регул¤рка.
//ƒл¤ моды прогон¤етс¤ обща¤ регул¤рка по "или" дл¤ 64 символов (не, или дл¤ всего текста??).
//ѕервое найденное событие обрабатываетс¤.
//≈сли событий не найдено, 
class Parser
{
    private static $_modeStorage = array();

    public static function freeMemory()
    {
        self::$_modeStorage = array();
    }

    //
    protected $_modeClasses = null;
    protected $_lastNodeData = null;
    protected $_elementNodeClasses = null;
    protected $_attrNodeClasses = null;

    /**
     * @var TemplatesConfig
     */
    protected $config = null;

    /**
     * @var Source
     */
    protected $source = null;

    /**
     * @var Scheme
     */
    protected $nodeTree = null;
    //
    protected $warnings = array();

    public final function __construct(Source $source, TemplatesConfig $config = null)
    {
        $this->source = $source;
        if ($config !== null) {
            $this->config = $config;
        } else {
            $this->config = TemplatesModule::config();
        }
    }

    public function createTemplate($templateClass = null)
    {
        if ($templateClass === null) {
            $templateClass = $this->config->getTemplateClass($this->getFormat());
        }
        if ($this->nodeTree === null) {
            $this->nodeTree = $this->parse();
        }
        return new $templateClass($this->source, $this->nodeTree, $this);
    }

    public function reset()
    {
        $this->clean();
        $this->warnings = array();
        $this->nodeTree = null;
    }

    protected function clean()
    {
        $this->_modeClasses = null;
        $this->_lastNodeData = null;
    }

    public function addWarning($index, $warning)
    {
        if ($this->warnings) {
            $last = end($this->warnings);
            if ($index >= $last[0]) {
                $this->warnings[] = array($index, $warning);
            } else {
                for ($i = count($this->warnings) - 2; $i >= 0; --$i) {
                    if ($index >= $this->warnings[$i][0]) {
                        array_splice($this->warnings, $i + 1, 0, array(array($index, $warning)));
                        return;
                    }
                }
                array_unshift($this->warnings, array($index, $warning));
            }
        } else {
            $this->warnings[] = array($index, $warning);
        }
    }

    public function getWarnings()
    {
        $warnings = array();
        foreach ($this->warnings as $warning) {
            $warnings[] = new ParseWarning($this->source, $warning[0], $warning[1]);
        }
        return $warnings;
    }

    public function getTokens($modeId = 'default')
    {
        $this->reset();
        $mode = $this->createMode($modeId, 0);
        $startStatus = $this->createStatus($mode);

        $status = $this->parsingCycle($startStatus);
        $this->clean();

        if (!$status->isEnd()) {
            throw new ParseException($this->source, $status->index, "Parser exited before parsing all code");
        }

        $this->warnings = $status->getWarnings();
        return Token::transformTokens($status->getTokens());
    }

    public function getNodeTree()
    {
        if ($this->nodeTree === null) {
            $this->nodeTree = $this->parse();
        }
        return $this->nodeTree;
    }

    protected function createStatus(Mode $mode)
    {
        $status = new ParserStatus($this, $mode);
        $result = $mode->start($status);
        if ($result instanceof ParserStatus) {
            return $result;
        } elseif ($result) {
            return $status;
        } else {
            return $status->fallback();
        }
    }

    protected function createRootNode()
    {
        return new Scheme($this);
    }

    protected function buildNodeTree($tokensArray)
    {
        $root = $this->createRootNode();
        $tokens = new TokensCollection($this, $tokensArray);
        try {
            $root->collectChildNodes($this, $tokens, 0);
            $root->initializeNode($this);
        } catch (NodeException $ex) {
            throw new ParseException($this->source, $ex->getSourceIndex(), $ex->getMessage(), $ex);
        }
        return $root;
    }

    public function processNode($node, TokensCollection $tokens, $index)
    {
        $shift = $node->collectChildNodes($this, $tokens, $index + 1);
        $node->initializeNode($this);
        return $shift + 1;
    }

    public function processNodeFallback($node, TokensCollection $tokens, $index)
    {
        if ($node === false) {
            return 1;
        } else {
            $token = $tokens[$index];
            $fallback = ($token instanceof Token) ? $token->fallback($this) : null;
            if ($fallback !== null && is_array($fallback)) {
                $tokens->replace($fallback, $index, 1);
                return 0;
            } else {
                if ($token instanceof Token) {
                    $tokenClass = get_class($token);
                    throw new BuildException("No node class defined for token of '{$tokenClass}' class");
                } else {
                    $tokenType = is_array($token) ? (string)$token[0] : '';
                    throw new BuildException("No node class defined for token of '{$tokenType}' type");
                }
            }
        }
    }

    public function createNode($token)
    {
        if ($token instanceof Token) {
            if ($this->_lastNodeData !== null && $this->_lastNodeData[0] === $token) {
                return $this->_lastNodeData[1];
            } else {
                $node = $token->createNode($this);
                $this->_lastNodeData = array($token, $node);
                return $node;
            }
        } else {
            return $this->createDefaultNode($token);
        }
    }

    public function createDefaultNode($token)
    {
        if (is_string($token)) {
            return new TextNode($token);
        }
        return null;
    }

    public function parse($modeId = 'default')
    {
        $tokens = $this->getTokens($modeId);
        $tree = $this->buildNodeTree($tokens);
        $tree->buildStorage();
        return $tree;
    }

    public function getFormat()
    {
        return Template::FORMAT_TEXT;
    }

    public function getSource()
    {
        return $this->source;
    }

    public function getConfig()
    {
        return $this->config;
    }

    public function getMode($modeId, $flags = 0)
    {
        if (isset(self::$_modeStorage[$modeId][$flags])) {
            return self::$_modeStorage[$modeId][$flags];
        } else {
            $mode = $this->createMode($modeId, $flags);
            self::$_modeStorage[$modeId][$flags] = $mode;
            return $mode;
        }
    }

    public function getOperatorClass($operatorId)
    {
        return $this->config->getOperatorClass($operatorId);
    }

    public function getAttrNodeClass($idCode)
    {
        $k = strpos($idCode, ':');
        if ($k !== false) {
            $group = substr($idCode, 0, $k);
            $id = substr($idCode, $k + 1);
            return $this->config->getAttrNodeClassByGroup($group, $id, $this->getFormat());
        }
        if ($this->_attrNodeClasses === null) {
            $format = $this->getFormat();
            $classes = $this->config['attrNodeClasses'];
            $this->_attrNodeClasses = array();
            if (!empty($classes[$format][''])) {
                $this->_attrNodeClasses += $classes[$format][''];
            }
            if (!empty($classes[''][''])) {
                $this->_attrNodeClasses += $classes[''][''];
            }
            if (!$this->config['requireOpPrefixes']) {
                if (!empty($classes[$format]['op'])) {
                    $this->_attrNodeClasses += $classes[$format]['op'];
                }
                if (!empty($classes['']['op'])) {
                    $this->_attrNodeClasses += $classes['']['op'];
                }
            }
            if (!$this->config['requireXPrefixes']) {
                if (!empty($classes[$format]['x'])) {
                    $this->_attrNodeClasses += $classes[$format]['x'];
                }
                if (!empty($classes['']['x'])) {
                    $this->_attrNodeClasses += $classes['']['x'];
                }
            }
        }
        return isset($this->_attrNodeClasses[$idCode]) ? $this->_attrNodeClasses[$idCode] : null;
    }

    public function getElementNodeClass($idCode)
    {
        $k = strpos($idCode, ':');
        if ($k !== false) {
            $group = substr($idCode, 0, $k);
            $id = substr($idCode, $k + 1);
            return $this->config->getElementNodeClassByGroup($group, $id, $this->getFormat());
        }
        if ($this->_elementNodeClasses === null) {
            $format = $this->getFormat();
            $classes = $this->config['elementNodeClasses'];
            $this->_elementNodeClasses = array();
            if (!empty($classes[$format][''])) {
                $this->_elementNodeClasses += $classes[$format][''];
            }
            if (!empty($classes[''][''])) {
                $this->_elementNodeClasses += $classes[''][''];
            }
            if (!$this->config['requireOpPrefixes']) {
                if (!empty($classes[$format]['op'])) {
                    $this->_elementNodeClasses += $classes[$format]['op'];
                }
                if (!empty($classes['']['op'])) {
                    $this->_elementNodeClasses += $classes['']['op'];
                }
            }
            if (!$this->config['requireXPrefixes']) {
                if (!empty($classes[$format]['x'])) {
                    $this->_elementNodeClasses += $classes[$format]['x'];
                }
                if (!empty($classes['']['x'])) {
                    $this->_elementNodeClasses += $classes['']['x'];
                }
            }
        }
        return isset($this->_elementNodeClasses[$idCode]) ? $this->_elementNodeClasses[$idCode] : null;
    }

    protected function createMode($modeId, $flags)
    {
        $class = $this->getModeClass($modeId);
        if ($class !== null) {
            if (class_exists($class, true)) {
                return new $class($modeId, $flags);
            } else {
                throw new BuildException("Mode class '{$class}' not found for mode '{$modeId}'");
            }
        } else {
            throw new BuildException("Mode class is not defined for mode '{$modeId}'");
        }
    }

    protected function getModeClass($modeId)
    {
        if ($this->_modeClasses === null) {
            $this->_modeClasses = $this->getModeClasses();
        }
        if (isset($this->_modeClasses[$modeId])) {
            return $this->config->transformClassNs($this->getModeClassesNs(), $this->_modeClasses[$modeId]);
        } else {
            return null;
        }
    }

    protected function getModeClassesNs()
    {
        return 'megabike\\templates\\parser\\modes';
    }

    protected function getModeClasses()
    {
        return array(
            'default' => 'DefaultMode',
            'code' => 'CodeMode',
            'codeAttr' => 'CodeAttrMode',
            'codeAttrValue' => 'CodeAttrValueMode',
            'escape' => 'EscapeMode',
        );
    }

    protected function parsingCycle(ParserStatus $status)
    {
        while ($status->isActive()) {
            $status = $status->next();
        }
        return $status->finish();
    }

}

