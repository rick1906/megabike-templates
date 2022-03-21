<?php

namespace megabike\templates\parser\modes;

use megabike\templates\parser\ParserStatus;
use megabike\templates\parser\modes\DefaultMode;
use megabike\templates\parser\tokens\XmlTagOpenToken;
use megabike\templates\parser\tokens\HtmlTagOpenToken;
use megabike\templates\html\Elements;

class HtmlTagOpenMode extends DefaultMode
{

    public function start(ParserStatus $status)
    {
        $captured = $status->params['tag'];
        $index = $status->index;
        $tagName = substr($captured, 1);
        $status->params['tagName'] = $tagName;

        $tagId = strtolower($tagName);
        $status->params['tagId'] = $tagId;

        $tagAttrs = Elements::element($tagId); //TODO: This for extended elements won't work!!! Fix it!
        if ($tagAttrs !== false) { //TODO: differ title, script, CDATA
            if ($tagAttrs & (Elements::TEXT_RAW | Elements::TEXT_RCDATA | Elements::TEXT_PLAINTEXT)) {
                $status->params['rawText'] = true;
            }
        }

        $status->shift($captured);
        $token = new HtmlTagOpenToken($index, $tagName);
        $status->addMasterToken($token);

        return true;
    }

    protected function getEventsData()
    {
        return array(
            'exit' => '/\>/',
            'exitClose' => '/\/\>/',
            $this->getCodeEventsData(),
            'nextTag' => '/\</',
            'attr' => '/[^\s=\\\\]+/i',
        );
    }

    protected function onExit(ParserStatus $status, $captured)
    {
        $status->shift($captured);
        if (!empty($status->params['rawText'])) {
            $params = $status->params;
            return $status->modeDelegate('htmlTextRaw', 0, $params);
        } else {
            return $status->modeExit();
        }
    }

    protected function onExitClose(ParserStatus $status, $captured)
    {
        $status->shift($captured);
        $token = $status->getMasterToken();
        if ($token instanceof XmlTagOpenToken) {
            $token->setShortClosed(true);
        }
        return $status->modeExit();
    }

    protected function onNextTag(ParserStatus $status, $captured)
    {
        $this->forceExit($status);
        return $status->modeExit();
    }

    public function forceExit(ParserStatus $status)
    {
        $status->addWarning($status->index, "Missing closing bracket '>' in '{$status->params['tagName']}' open-tag");
    }

    protected function onAttr(ParserStatus $status, $captured)
    {
        $params = array('captured' => $captured);
        return $status->modeSwitch('attr', 0, $params);
    }

}
