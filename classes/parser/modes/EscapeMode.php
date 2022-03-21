<?php

namespace megabike\templates\parser\modes;

use megabike\templates\parser\ParserStatus;
use megabike\templates\parser\modes\Mode;
use megabike\templates\parser\tokens\EscapeToken;

class EscapeMode extends Mode
{

    public function start(ParserStatus $status)
    {
        if (!isset($status->params['captured']) || $status->params['captured'] !== '\\') {
            return false;
        }
        if (!isset($status->params['chars'])) {
            return false;
        }
        if (EscapeToken::escape($status, $status->index)) {
            return false;
        }

        $index = $status->index;
        $char = $status->getContent()->getChar($index + 1);
        if (strpos($status->params['chars'], $char) !== false) {
            $status->update($index + 1);
            $status->addToken(new EscapeToken($index));
            return $status->modeExit();
        } else {
            return false;
        }
    }

    protected function isCapture()
    {
        return false;
    }

    protected function getEventsData()
    {
        return false;
    }

}
