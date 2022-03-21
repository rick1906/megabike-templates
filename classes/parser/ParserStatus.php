<?php

namespace megabike\templates\parser;

use megabike\templates\Content;
use megabike\templates\parser\Parser;
use megabike\templates\parser\modes\Mode;
use megabike\templates\parser\tokens\MasterToken;
use megabike\templates\errors\ParseException;

class ParserStatus
{
    public $start = 0;
    public $index = 0;
    public $params = array();
    //
    protected $parent = null;
    protected $tokens = array();
    protected $warnings = array();
    protected $eventIndex = null;
    //
    protected $length = null;

    /**
     * @var Content
     */
    protected $content = null;

    /**
     * @var Parser
     */
    protected $parser = null;

    /**
     * @var MasterToken
     */
    protected $masterToken = null;

    /**
     * @var Mode
     */
    protected $mode = null;

    public function __construct(Parser $parser, Mode $mode, ParserStatus $parent = null)
    {
        $this->parser = $parser;
        $this->mode = $mode;
        if ($parent !== null) {
            $this->parent = $parent;
            $this->start = $this->index = $parent->index;
            $this->content = $parent->content;
            $this->length = $parent->length;
        } else {
            $this->content = $parser->getSource()->getContent();
            $this->length = $this->content->getLength();
        }
    }

    public function getParent()
    {
        return $this->parent;
    }

    public function getMode()
    {
        return $this->mode;
    }

    public function getModeId()
    {
        return $this->mode->getId();
    }

    public function getSource()
    {
        return $this->parser->getSource();
    }

    public function getContent()
    {
        return $this->content;
    }

    public function getLength()
    {
        return $this->length;
    }

    public function isEnd()
    {
        return $this->index >= $this->length;
    }

    public function isError()
    {
        return $this->eventIndex < 0;
    }

    public function isActive()
    {
        return $this->index < $this->length && $this->eventIndex !== -1;
    }

    public function next()
    {
        if ($this->eventIndex === null) {
            return $this->mode->nextGlobal($this);
        } elseif ($this->eventIndex >= 0) {
            return $this->mode->nextEvent($this, $this->eventIndex);
        } else {
            return $this->fallback();
        }
    }

    public function fail($eventIndex = null)
    {
        if ($eventIndex !== null) {
            $this->eventIndex = $eventIndex + 1;
        } elseif ($this->eventIndex !== null) {
            $this->eventIndex++;
        } else {
            return $this->fallback();
        }
        return $this;
    }

    public function success($eventIndex, $index)
    {
        $this->eventIndex = $eventIndex;
        $this->index = $index;
        return $this;
    }

    public function fallback()
    {
        if ($this->parent !== null) {
            $this->eventIndex = -1;
            return $this->parent->fail();
        } else {
            throw new ParseException($this->getSource(), $this->index, "Could not parse fragment");
        }
    }

    public function update($index)
    {
        $this->start = $this->index = $index;
        $this->eventIndex = null;
        return $this;
    }

    public function move()
    {
        $this->index++;
        $this->eventIndex = null;
        return $this;
    }

    public function shift($string)
    {
        return $this->update($this->index + $this->content->computeLength($string));
    }

    public function modeSwitch($modeId, $flags, $params = array())
    {
        $mode = $this->parser->getMode($modeId, $flags);
        $status = $this->createChild($mode);
        $status->params = $params;
        $result = $mode->start($status);
        if ($result instanceof ParserStatus) {
            return $result;
        } elseif ($result) {
            return $status;
        } else {
            return $this->fail();
        }
    }

    public function modeDelegate($modeId, $flags, $params = array())
    {
        $mode = $this->parser->getMode($modeId, $flags);
        $status = $this->createSibling($mode);
        $status->params = $params;
        $result = $mode->start($status);
        if ($result instanceof ParserStatus) {
            return $result;
        } elseif ($result) {
            return $status;
        } else {
            return $this->fail();
        }
    }

    public function modeExit()
    {
        if ($this->eventIndex !== null && $this->eventIndex < 0) {
            return $this->fallback();
        } elseif ($this->parent !== null) {
            return $this->parent->acceptStatus($this);
        } else {
            throw new ParseException($this->getSource(), $this->index, "No parent status to exit to");
        }
    }

    public function modeExitTo($modeId)
    {
        $status = $this;
        while ($status->mode->getId() !== $modeId) {
            $status = $status->modeExit();
        }
        return $status;
    }

    public function modeExitFrom($modeId)
    {
        $status = $this->modeExitTo($modeId);
        return $status->modeExit();
    }

    public function finish()
    {
        $status = $this;
        while ($status->parent !== null) {
            $status->mode->forceExit($status);
            $status = $status->modeExit();
        }
        return $status;
    }

    protected function createChild(Mode $mode)
    {
        return new ParserStatus($this->parser, $mode, $this);
    }

    protected function createSibling(Mode $mode)
    {
        $status = new ParserStatus($this->parser, $mode, $this);
        $status->parent = $this->parent;
        $status->tokens = $this->tokens;
        $status->warnings = $this->warnings;
        $status->masterToken = $this->masterToken;
        return $status;
    }

    protected function acceptStatus(ParserStatus $status)
    {
        if ($status->isError()) {
            return $this->fail();
        }

        $this->eventIndex = null;
        $this->start = $this->index = $status->index;

        if ($this->masterToken !== null && $this->masterToken === end($this->tokens)) {
            foreach ($status->tokens as $token) {
                $this->masterToken->addToken($token);
            }
        } else {
            $this->masterToken = null;
            if (empty($this->tokens)) {
                $this->tokens = $status->tokens;
            } else {
                foreach ($status->tokens as $token) {
                    $this->tokens[] = $token;
                }
            }
        }

        if (empty($this->warnings)) {
            $this->warnings = $status->warnings;
        } else {
            foreach ($status->warnings as $warning) {
                $this->warnings[] = $warning;
            }
        }

        return $this;
    }

    public function addWarning($index, $warning)
    {
        $this->warnings[] = array($index, $warning);
    }

    public function addToken($token)
    {
        if ($this->masterToken !== null) {
            $this->masterToken->addToken($token);
        } else {
            $this->tokens[] = $token;
        }
    }

    public function addMasterToken(MasterToken $token)
    {
        $this->tokens[] = $token;
        $this->masterToken = $token;
    }

    public function disableMasterToken()
    {
        $this->masterToken = null;
    }

    public function getMasterToken()
    {
        if ($this->masterToken !== null) {
            return $this->masterToken;
        }
        return null;
    }

    public function getTokens()
    {
        return $this->tokens;
    }

    public function getLastToken()
    {
        if ($this->masterToken !== null) {
            $token = $this->masterToken->getLastToken();
            if ($token !== null) {
                return $token;
            }
        } elseif ($this->tokens) {
            return end($this->tokens);
        }

        if ($this->parent !== null) {
            return $this->parent->getLastToken();
        } else {
            return null;
        }
    }

    public function getWarnings()
    {
        return $this->warnings;
    }

}
