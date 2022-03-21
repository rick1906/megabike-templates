<?php

namespace megabike\templates\parser\modes;

use megabike\templates\parser\ParserStatus;
use megabike\templates\parser\modes\DefaultMode;

class HtmlTextRawMode extends DefaultMode
{
    const CODE_ENABLED = 0;
    const CODE_DISABLED = 1;

    public function start(ParserStatus $status)
    {
        $status->disableMasterToken();
        return true;
    }

    protected function getEventsData()
    {
        if ($this->flags === self::CODE_DISABLED) {
            return array(
                'htmlTagClose' => static::$commonEvents['htmlTagClose'],
            );
        } else {
            return array(
                'htmlTagClose' => static::$commonEvents['htmlTagClose'],
                $this->getCodeEventsData(),
            );
        }
    }

    protected function onHtmlTagClose(ParserStatus $status, $captured)
    {
        if (isset($status->params['tagId'])) {
            $tagId = strtolower(substr($captured, 2)); //TODO: unicode tags?
            if ($tagId === $status->params['tagId']) {
                $params = array('tag' => $captured);
                return $status->modeDelegate('htmlTagClose', 0, $params);
            } else {
                return $status->fail();
            }
        } else {
            return $status->fail();
        }
    }

}
