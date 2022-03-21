<?php

namespace megabike\templates\parser\modes;

use megabike\templates\parser\ParserStatus;
use megabike\templates\parser\modes\Mode;
use megabike\templates\parser\tokens\XmlAttrToken;

class CodeAttrMode extends Mode
{

    public function start(ParserStatus $status)
    {
        $index = $status->index;
        $attrName = $status->params['captured'];
        $status->params['attrName'] = $attrName;

        $status->shift($attrName);
        $token = new XmlAttrToken($index, $attrName);
        $status->addMasterToken($token);
        return true;
    }

    protected function isCapture()
    {
        return false;
    }

    protected function isAnchored()
    {
        return true;
    }

    protected function getEventsData()
    {
        return array(
            'eq' => '/\s*=/',
        );
    }

    protected function onEq(ParserStatus $status, $captured)
    {
        $status->shift($captured);
        return $status->modeDelegate('codeAttrValue', 0, $status->params);
    }

    protected function processNoneMatched(ParserStatus $status)
    {
        return $status->modeExit();
    }

}
