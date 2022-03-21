<?php

namespace megabike\templates;

use megabike\common\Module;

class TemplatesModule extends Module
{

    public function getModuleId()
    {
        return 'megabike/templates';
    }

    protected function defaultConfig()
    {
        return 'megabike\\templates\\TemplatesConfig';
    }

}
