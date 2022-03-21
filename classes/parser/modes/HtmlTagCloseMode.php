<?php

namespace megabike\templates\parser\modes;

use megabike\templates\parser\ParserStatus;
use megabike\templates\parser\modes\DefaultMode;
use megabike\templates\parser\tokens\HtmlTagCloseToken;

class HtmlTagCloseMode extends DefaultMode
{

    public function start(ParserStatus $status)
    {
        $captured = $status->params['tag'];
        $index = $status->index;
        $tagName = substr($captured, 2);
        $status->params['tagName'] = $tagName;

        $status->shift($captured);
        $token = new HtmlTagCloseToken($index, $tagName);
        $status->addMasterToken($token);

        return true;
    }

    protected function getEventsData()
    {
        return array(
            'exit' => '/\>/',
            $this->getCodeEventsData(),
            'nextTag' => '/\</',
        );
    }

    protected function onExit(ParserStatus $status, $captured)
    {
        $status->shift($captured);
        return $status->modeExit();
    }

    protected function onNextTag(ParserStatus $status, $captured)
    {
        $this->forceExit($status);
        return $status->modeExit();
    }

    public function forceExit(ParserStatus $status)
    {
        $status->addWarning($status->index, "Missing closing bracket '>' in '{$status->params['tagName']}' close-tag");
    }

}
