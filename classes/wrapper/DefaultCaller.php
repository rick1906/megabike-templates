<?php

namespace megabike\templates\wrapper;

use megabike\templates\wrapper\Caller;

class DefaultCaller extends Caller
{
    /**
     * @var array
     */
    protected $redirects = array(
        'html' => array('megabike\templates\Content', 'htmlEncode'),
        'date' => 'date',
    );

    public function addUse($use, $as = null)
    {
        $name = $this->getAllowedClass(ltrim($use, '\\'), true);
        if ($name !== null) {
            if ($as === null) {
                $p = strrpos($use, '\\');
                $as = substr($use, $p + 1);
            }
            $this->redirects[':'.ltrim($as, '\\:')] = $name;
            return true;
        }
        return false;
    }

    public function addUseFunction($use, $as = null)
    {
        if (is_array($use)) {
            $name = $this->getAllowedClassMethod($use[0], $use[1]);
        } else {
            $name = $this->getAllowedFunction(ltrim($use, '\\'), true);
        }
        if ($name !== null) {
            if ($as === null) {
                if (is_array($use)) {
                    $as = $use[1];
                } else {
                    $p = strrpos($use, '\\');
                    $as = substr($use, $p + 1);
                }
            }
            $this->redirects[ltrim($as, '\\:')] = $name;
            return true;
        }
        return false;
    }

    public function getAllowedFunction($function, $rootNs = true)
    {
        if (isset($this->redirects[$function]) && !$rootNs) {
            return $this->redirects[$function];
        }
        return parent::getAllowedFunction($function);
    }

    public function getAllowedClass($class, $rootNs = true)
    {
        if (isset($this->redirects[':'.$class]) && !$rootNs) {
            return $this->redirects[':'.$class];
        }
        return parent::getAllowedClass($class, $rootNs);
    }

    protected function initChild($parent)
    {
        parent::initChild($parent);
        $this->redirects = $parent->redirects;
    }

}