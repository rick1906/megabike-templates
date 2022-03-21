<?php

namespace megabike\templates\parser\modes;

use megabike\templates\parser\ParserStatus;
use megabike\templates\parser\modes\DefaultMode;

class HtmlDefaultMode extends DefaultMode
{

    protected function getEventsData()
    {
        return array(
            'xmlInstruction' => '/\<\?(?:xml[a-z0-9_\-]*)/i',
            $this->getCodeEventsData(),
            'htmlTagOpen' => static::$commonEvents['htmlTagOpen'],
            'htmlTagClose' => static::$commonEvents['htmlTagClose'],
            'htmlDoctype' => '/\<!doctype(?=\W)/i',
            'htmlComment' => '/\<!--/',
        );
    }

    protected function onHtmlTagOpen(ParserStatus $status, $captured)
    {
        $params = array('tag' => $captured);
        return $status->modeSwitch('htmlTagOpen', 0, $params);
    }

    protected function onHtmlTagClose(ParserStatus $status, $captured)
    {
        $params = array('tag' => $captured);
        return $status->modeSwitch('htmlTagClose', 0, $params);
    }

    protected function onHtmlComment(ParserStatus $status, $captured)
    {
        $params = array('tag' => $captured);
        return $status->modeSwitch('htmlComment', 0, $params);
    }

    protected function onHtmlDoctype(ParserStatus $status, $captured)
    {
        $params = array('tag' => $captured);
        return $status->modeSwitch('htmlDoctype', 0, $params);
    }

    protected function onXmlInstruction(ParserStatus $status, $captured)
    {
        $params = array('tag' => $captured, 'check' => true);
        return $status->modeSwitch('xmlInstruction', 0, $params);
    }

}
