<?php

namespace megabike\templates\parser\modes;

use megabike\templates\parser\ParserStatus;
use megabike\templates\parser\modes\DefaultMode;
use megabike\templates\parser\tokens\XmlInstructionToken;

class XmlInstructionMode extends DefaultMode
{

    public function start(ParserStatus $status)
    {
        $captured = $status->params['tag'];
        $index = $status->index;
        $tagName = substr($captured, 2);
        $status->params['tagName'] = $tagName;

        $status->shift($captured);
        if (!empty($status->params['check'])) {
            $char = $status->getContent()->getChar($status->index);
            if ($char !== '?' && $char !== '>' && !ctype_space($char)) {
                return false;
            }
        }

        $token = new XmlInstructionToken($index, $tagName);
        $status->addMasterToken($token);

        return true;
    }

    protected function getEventsData()
    {
        return array(
            'exit' => '/\?\>/',
            'wrongExit' => '/\>/',
            $this->getCodeEventsData(),
            'nextTag' => '/\</',
            'attr' => '/^[^\?\s=\\\\]+/i',
        );
    }

    protected function onExit(ParserStatus $status, $captured)
    {
        $status->shift($captured);
        return $status->modeExit();
    }

    protected function onWrongExit(ParserStatus $status, $captured)
    {
        $status->addWarning($status->index, "Using invalid closing bracket '>' instead of '?>' in XML declaration tag");
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
        $status->addWarning($status->index, "Missing closing bracket '?>' in XML declaration tag");
    }

    protected function onAttr(ParserStatus $status, $captured)
    {
        $params = array('captured' => $captured, 'xmlInstruction' => true);
        return $status->modeSwitch('attr', 0, $params);
    }

}
