<?php

namespace megabike\templates\parser\modes;

use megabike\templates\parser\ParserStatus;
use megabike\templates\parser\modes\DefaultMode;
use megabike\templates\parser\tokens\CommentTagToken;

class HtmlCommentMode extends DefaultMode
{

    public function start(ParserStatus $status)
    {
        $index = $status->index;

        $status->shift($status->params['tag']);
        $token = new CommentTagToken($index);
        $status->addMasterToken($token);

        return true;
    }

    protected function getEventsData()
    {
        return array(
            'commentEnd' => '/--\>/',
            $this->getCodeEventsData(),
        );
    }

    protected function onCommentEnd(ParserStatus $status, $captured)
    {
        $status->shift($captured);
        $token = $status->getMasterToken();
        if ($token instanceof CommentTagToken) {
            $token->setClosed(true);
        }
        return $status->modeExit();
    }

}
