<?php

namespace megabike\templates\parser\tokens;

use megabike\templates\parser\Parser;
use megabike\templates\parser\tokens\MasterToken;
use megabike\templates\nodes\XmlCommentNode;
use megabike\templates\errors\ParseException;

class CommentTagToken extends MasterToken
{
    protected $isClosed = false;

    public function __construct($index)
    {
        parent::__construct($index);
    }

    public function setClosed($closed)
    {
        $this->isClosed = (bool)$closed;
    }

    public function toString()
    {
        return '<--'.$this->getTokensString().($this->isClosed ? '-->' : '');
    }

    public function createNode(Parser $parser)
    {
        $node = new XmlCommentNode($this);
        if ($this->tokens) {
            foreach ($this->tokens as $token) {
                if (is_string($token)) {
                    $node->addChildNode($parser->createDefaultNode($token));
                    continue;
                }
                if ($token instanceof CodeSequence) {
                    $node->addChildNode($parser->createNode($token));
                    continue;
                }
                throw new ParseException($parser->getSource(), $this->index, "Comment tag contains invalid content");
            }
        }
        return $node;
    }

}