<?php

namespace megabike\templates\parser\modes;

use megabike\templates\parser\ParserStatus;
use megabike\templates\parser\modes\DefaultMode;
use megabike\templates\parser\tokens\DoctypeToken;
use megabike\templates\parser\tokens\DoctypeAttrToken;

class HtmlDoctypeMode extends DefaultMode
{

    public function start(ParserStatus $status)
    {
        $captured = $status->params['tag'];
        $index = $status->index;
        $tagName = substr($captured, 1);
        $status->params['tagName'] = $tagName;

        $status->shift($captured);
        $token = new DoctypeToken($index, $tagName);
        $status->addMasterToken($token);

        return true;
    }

    protected function getEventsData()
    {
        return array(
            'space' => '/\s+/',
            'exit' => '/\>/',
            $this->getCodeEventsData(),
            'nextTag' => '/\</',
        );
    }

    protected function isAnchored()
    {
        return true;
    }

    protected function processNoneMatched(ParserStatus $status)
    {
        $params = array('attrName' => '', 'doctype' => true);
        $token = new DoctypeAttrToken($status->index);
        $next = $status->modeSwitch('attrValue', 0, array(), $params);
        $next->addMasterToken($token);
        return $next;
    }

    protected function onSpace(ParserStatus $status, $captured)
    {
        return $status->shift($captured);
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
        $status->addWarning($status->index, "Missing closing bracket '>' in doctype tag");
    }

}
