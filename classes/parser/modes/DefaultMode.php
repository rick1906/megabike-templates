<?php

namespace megabike\templates\parser\modes;

use megabike\templates\parser\ParserStatus;
use megabike\templates\parser\modes\Mode;

class DefaultMode extends Mode
{

    protected function capture(ParserStatus $status, $string)
    {
        $status->addToken($string);
    }

    protected function getEventsData()
    {
        return $this->getCodeEventsData();
    }

    protected function getCodeEventsData()
    {
        return array(
            'codeEscape' => static::$commonEvents['codeEscape'],
            'code' => static::$commonEvents['code'],
            'phpLong' => static::$commonEvents['phpLong'],
            'phpShort' => static::$commonEvents['phpShort'],
        );
    }

    protected function onCodeEscape(ParserStatus $status, $captured)
    {
        $params = array('captured' => $captured, 'chars' => '{\\');
        return $status->modeSwitch('escape', 0, $params);
    }

    protected function onCode(ParserStatus $status, $captured)
    {
        $params = array('tag' => $captured);
        return $status->modeSwitch('code', 0, $params);
    }

    protected function onPhpShort(ParserStatus $status, $captured)
    {
        $params = array('tag' => $captured);
        return $status->modeSwitch('code', 0, $params);
    }

    protected function onPhpLong(ParserStatus $status, $captured)
    {
        $params = array('tag' => $captured, 'raw' => true);
        return $status->modeSwitch('code', 0, $params);
    }

}
