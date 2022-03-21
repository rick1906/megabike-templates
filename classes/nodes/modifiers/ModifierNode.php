<?php

namespace megabike\templates\nodes\modifiers;

use megabike\templates\nodes\AttrNode;

abstract class ModifierNode extends AttrNode
{

    public static function setOpenTagNotConstant(Node $node)
    {
        $node->storage['isOpenTagConstant'] = false;
        unset($node->storage['openTagValue']);
        static::setNotConstant($node);
    }

    public static function setNotConstant(Node $node)
    {
        $node->storage['isConstant'] = false;
        unset($node->storage['value']);
        $parent = $node->parentNode;
        while ($parent !== null) {
            if (!empty($parent->storage['isContentConstant'])) {
                $parent->storage['isContentConstant'] = false;
                unset($parent->storage['contentValue']);
                unset($parent->storage['contentArray']);
            }
            if (!empty($parent->storage['isConstant'])) {
                $parent->storage['isConstant'] = false;
                unset($parent->storage['value']);
                $parent = $parent->parentNode;
            } else {
                break;
            }
        }
    }

    public function isVirtual()
    {
        return true;
    }

}
