<?php

namespace megabike\templates\nodes;

use megabike\templates\Content;
use megabike\templates\html\Elements;
use megabike\templates\nodes\ElementNode;
use megabike\templates\nodes\XmlElementNode;
use megabike\templates\parser\Parser;
use megabike\templates\parser\tokens\XmlTagCloseToken;

class HtmlElementNode extends XmlElementNode
{
    protected $tagId;

    public function __construct($tagName, $token = null)
    {
        parent::__construct($tagName, $token);
        $this->tagId = Content::toLower($tagName);

        $flags = $this->getElementFlags();
        if ($flags & Elements::VOID_TAG) {
            $this->closedState = self::CLOSED_SHORT;
        }
    }

    public function getTagId()
    {
        return $this->tagId;
    }

    public function getElementId()
    {
        return $this->tagId;
    }

    public function getElementFlags()
    {
        return (int)Elements::element($this->getElementId());
    }

    public function initializeNode(Parser $parser)
    {
        if (!$this->isClosed()) {
            $flags = $this->getElementFlags();
            if ($flags & Elements::VOID_TAG) {
                $this->setClosed(self::CLOSED_SHORT);
            } elseif ($this->isAutoclose($this->getElementId(), $flags, null, 0)) {
                $this->setClosed(self::CLOSED_AUTO);
            } else {
                $this->setClosed(self::CLOSED_FORCED);
                $parser->addWarning($this->getIndex(), "Element '{$this->tagName}' is not closed properly");
            }
        }
        parent::initializeNode($parser);
    }

    protected function isAutoclose($parentEid, $parentEflags, $childEid, $childEflags, $strict = true)
    {
        $eq = $parentEid === $childEid || $childEid === null;
        if (in_array($parentEid, array('p', 'option')) && $eq) {
            return true;
        }
        if (in_array($parentEid, array('li')) && $eq && $strict) {
            return true;
        }
        if ($parentEflags & Elements::BLOCK_ONLY_INLINE) {
            if ($eq) {
                return true;
            }
            if ($childEflags & Elements::AUTOCLOSE_P) {
                return true;
            }
        }
        return false;
    }

    //TODO: generate own normal elements class
    protected function getClosePriority($elementId, $flags)
    {
        $priority = 0;
        if (in_array($elementId, array('td', 'th', 'tr'))) {
            $priority += 200;
        }
        if ($flags & Elements::BLOCK_TAG) {
            $priority += 100;
        }
        if ($flags & Elements::AUTOCLOSE_P) {
            $priority += 10;
        }
        if ($flags & Elements::BLOCK_ONLY_INLINE) {
            $priority -= 1;
        }
        return $priority;
    }

    protected function checkCollectedNode(Parser $parser, $node)
    {
        $flags = $this->getElementFlags();
        if ($flags & Elements::VOID_TAG) {
            $parser->addWarning($this->getIndex(), "Element '{$this->tagName}' can not contain anything");
            $this->setClosed(self::CLOSED_SHORT);
            return self::ACTION_BREAK;
        }

        if ($node instanceof HtmlElementNode) {
            $nflags = $node->getElementFlags();
            if ($this->isAutoclose($this->getElementId(), $flags, $node->getElementId(), $nflags)) {
                $this->setClosed(self::CLOSED_AUTO);
                return self::ACTION_BREAK;
            } else {
                $npriority = $this->getClosePriority($node->getElementId(), $nflags); //TODO: global tag priority
                $parents = $this->getParents();
                foreach ($parents as $parent) {
                    if ($parent instanceof HtmlElementNode) {
                        $pflags = $parent->getElementFlags();
                        if ($this->isAutoclose($parent->getElementId(), $pflags, $node->getElementId(), $nflags, false)) {
                            $parent->setClosed(self::CLOSED_AUTO, self::CLOSED_AUTO);
                            $parser->addWarning($node->getIndex(), "Element '{$parent->getTagName()}' should not contain '{$node->getElementId()}' tags");
                            return self::ACTION_BREAK;
                        } else { //TODO: this is UNTESTED! check it.
                            $priority = $parent->getClosePriority($parent->getElementId(), $pflags);
                            if ($npriority <= $priority) {
                                break;
                            }
                        }
                    } else {
                        break;
                    }
                }
            }
        }

        return self::ACTION_ACCEPT;
    }

    protected function checkCollectedToken(Parser $parser, $token)
    {
        $flags = $this->getElementFlags();
        if ($token instanceof XmlTagCloseToken) {
            if ($this->getTagId() === $token->getTagId()) {
                $this->processCloseTag($parser, $token);
                return self::ACTION_EXIT;
            } else {
                $parents = $this->getParents();
                $mpriority = $this->getClosePriority($this->getElementId(), $flags);
                foreach ($parents as $parent) {
                    if ($parent instanceof HtmlElementNode) {
                        $pflags = $parent->getElementFlags();
                        $priority = $parent->getClosePriority($parent->getElementId(), $pflags);
                        if ($parent->getTagId() === $token->getTagId()) {
                            if ($priority >= $mpriority) {
                                $this->setClosed(self::CLOSED_FORCED);
                                $parent->processCloseTag($parser, $token, self::CLOSED_AUTO);
                                $parser->addWarning($this->getIndex(), "Element '{$this->tagName}' is not closed properly");
                                return self::ACTION_EXIT;
                            } else {
                                break;
                            }
                        } elseif ($priority > $mpriority) {
                            break;
                        } else {
                            $mpriority = max($mpriority, $priority);
                        }
                    } elseif ($parent instanceof ElementNode) {
                        if ($parent->getTagId() === $token->getTagId()) {
                            $this->setClosed(self::CLOSED_FORCED);
                            $parent->processCloseTag($parser, $token, self::CLOSED_AUTO);
                            $parser->addWarning($this->getIndex(), "Element '{$this->tagName}' is not closed properly");
                            return self::ACTION_EXIT;
                        } else {
                            break;
                        }
                    } else {
                        break;
                    }
                }
            }
        }

        return self::ACTION_ACCEPT;
    }

}
