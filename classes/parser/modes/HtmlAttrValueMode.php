<?php

namespace megabike\templates\parser\modes;

use megabike\templates\parser\ParserStatus;
use megabike\templates\parser\modes\DefaultMode;
use megabike\templates\parser\tokens\XmlAttrToken;

class HtmlAttrValueMode extends DefaultMode
{
    const BEFORE_QUOTES = 0;
    const QUOTES_DOUBLE = 1;
    const QUOTES_SINGLE = 2;
    const QUOTES_NONE = 3;

    public function start(ParserStatus $status)
    {
        if ($this->flags === self::BEFORE_QUOTES) {
            return true;
        }

        $token = $status->getMasterToken();
        if ($token instanceof XmlAttrToken) {
            $token->setHasValue();
            if ($this->flags === self::QUOTES_SINGLE) {
                $token->setQuoteSymbol("'");
            } elseif ($this->flags === self::QUOTES_NONE) {
                $token->setQuoteSymbol('');
            } else {
                $token->setQuoteSymbol('"');
            }
        }

        return true;
    }

    protected function isAnchored()
    {
        return $this->flags === self::BEFORE_QUOTES;
    }

    protected function isCapture()
    {
        return $this->flags !== self::BEFORE_QUOTES;
    }

    protected function getEventsData()
    {
        if ($this->flags === self::BEFORE_QUOTES) {
            return array(
                'space' => '/\s+/',
                'singleQuote' => '/\'/',
                'doubleQuote' => '/"/',
                'exit' => '/[><]/',
            );
        } elseif ($this->flags === self::QUOTES_DOUBLE) {
            return array(
                $this->getCodeEventsData(),
                'codeValue' => '/\{/',
                'quoteExit' => '/"/',
            );
        } elseif ($this->flags === self::QUOTES_SINGLE) {
            return array(
                $this->getCodeEventsData(),
                'quoteExit' => '/\'/',
            );
        } elseif ($this->flags === self::QUOTES_NONE) {
            return array(
                $this->getCodeEventsData(),
                'exit' => '/[><?\s]/',
            );
        }
        return false;
    }

    protected function onSpace(ParserStatus $status, $captured)
    {
        return $status->shift($captured);
    }

    protected function onSingleQuote(ParserStatus $status, $captured)
    {
        $status->shift($captured);
        return $status->modeDelegate($this->id, self::QUOTES_SINGLE, $status->params);
    }

    protected function onDoubleQuote(ParserStatus $status, $captured)
    {
        $status->shift($captured);
        return $status->modeDelegate($this->id, self::QUOTES_DOUBLE, $status->params);
    }

    protected function onExit(ParserStatus $status, $captured)
    {
        if ($captured === '?' && empty($status->params['xmlInstruction'])) {
            return $status->fail();
        } else {
            return $status->modeExit();
        }
    }

    protected function onQuoteExit(ParserStatus $status, $captured)
    {
        $status->shift($captured);
        return $status->modeExit();
    }

    protected function onCodeValue(ParserStatus $status, $captured)
    {
        $params = array('tag' => $captured);
        return $status->modeSwitch('code', 0, $params);
    }

    protected function processNoneMatched(ParserStatus $status)
    {
        if ($this->flags === self::BEFORE_QUOTES) {
            return $status->modeDelegate($this->id, self::QUOTES_NONE, array(), $status->params);
        } else {
            return parent::processNoneMatched($status);
        }
    }

}
