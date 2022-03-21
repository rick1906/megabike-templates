<?php

namespace megabike\templates\wrapper;

use megabike\templates\wrapper\Wrapper;

interface WrapperAggregate
{

    /**
     * @return Wrapper
     */
    public function getWrapper();
}
