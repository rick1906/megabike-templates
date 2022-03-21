<?php

namespace megabike\templates\nodes\root;

use megabike\templates\nodes\root\RootNode;

class Scheme extends RootNode
{
    protected $usedTemplates = array();

    public function getFastScheme()
    {
        return null;
    }

    public function getUsedTemplates()
    {
        return $this->usedTemplates;
    }

    public function addUsedTemplate($sourceId, $from)
    {
        $this->usedTemplates[] = array($sourceId, $from);
    }

}
