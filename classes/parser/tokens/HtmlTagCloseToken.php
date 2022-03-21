<?php

namespace megabike\templates\parser\tokens;

use megabike\templates\Content;
use megabike\templates\html\Elements;
use megabike\templates\parser\Parser;
use megabike\templates\parser\tokens\XmlTagCloseToken;

class HtmlTagCloseToken extends XmlTagCloseToken
{
    protected $tagId;

    public function __construct($index, $tagName)
    {
        parent::__construct($index, $tagName);
        $this->tagId = Content::toLower($tagName);
    }

    public function getTagId()
    {
        return $this->tagId;
    }

    public function fallback(Parser $parser)
    {
        if (Elements::isA($this->getTagId(), Elements::VOID_TAG)) {
            $parser->addWarning($this->index, "Orphan close-tag '{$this->tagName}'; element '{$this->tagName}' does not need close-tag");
        } else {
            $parser->addWarning($this->index, "Orphan close-tag '{$this->tagName}'");
        }

        if (empty($this->tokens)) {
            return array('</'.$this->tagName.'>');
        }

        $tokens = $this->getTokens();
        array_unshift($tokens, '</'.$this->tagName);
        array_push($tokens, '>');
        return $tokens;
    }

}