<?php

namespace megabike\templates;

use megabike\common\ConfigBridge;
use megabike\common\ConfigContainer;

class TemplatesConfig extends ConfigContainer
{

    /**
     * @var array
     */
    protected $parserClasses = array(
        '' => 'megabike\\templates\\parser\\Parser',
        'text' => 'megabike\\templates\\parser\\Parser',
        'html' => 'megabike\\templates\\parser\\HtmlParser',
    );

    /**
     * @var array
     */
    protected $templateClasses = array(
        '' => 'megabike\\templates\\Template',
    );

    /**
     * @var array
     */
    protected $elementNodeClasses = array(//TODO: not requireOp prefix, but just '' => links
        '' => array(
            'op' => array(
                'include' => 'megabike\\templates\\nodes\\operators\\IncludeOperator',
                'if' => 'megabike\\templates\\nodes\\operators\\IfOperator',
                'else' => 'megabike\\templates\\nodes\\operators\\ElseOperator',
                'elseif' => 'megabike\\templates\\nodes\\operators\\ElseifOperator',
                'foreach' => 'megabike\\templates\\nodes\\operators\\ForeachOperator',
            ),
        ),
        'html' => array(
            'x' => array(
            ),
        ),
    );

    /**
     * @var array
     */
    protected $attrNodeClasses = array(
        '' => array(
            'op' => array(
            ),
        ),
        'html' => array(
            'x' => array(
            ),
        ),
    );

    /**
     * @var array
     */
    protected $operatorClasses = array(
        'include' => 'megabike\\templates\\nodes\\operators\\IncludeOperator',
        'if' => 'megabike\\templates\\nodes\\operators\\IfOperator',
        'else' => 'megabike\\templates\\nodes\\operators\\ElseOperator',
        'elseif' => 'megabike\\templates\\nodes\\operators\\ElseifOperator',
        'foreach' => 'megabike\\templates\\nodes\\operators\\ForeachOperator',
    );

    /**
     * @var array
     */
    protected $objectWrapperClasses = array(
        '' => 'megabike\\templates\\wrapper\\ObjectWrapper',
    );

    /**
     * @var mixed
     */
    protected $wrapperClass = 'megabike\\templates\\wrapper\\DefaultWrapper';

    /**
     * @var mixed
     */
    protected $callerClass = 'megabike\\templates\\wrapper\\DefaultCaller';

    /**
     * @var boolean
     */
    protected $callerAllowFunctionsByDefault = false;

    /**
     * @var boolean
     */
    protected $callerAllowClassesByDefault = true;

    /**
     * @var array
     */
    protected $callerAllowedFunctions = array(
    );

    /**
     * @var array
     */
    protected $callerAllowedClasses = array(
    );

    /**
     * @var array
     */
    protected $callerDisallowedFunctions = array(
        'eval',
        'call_user_func',
        'call_user_func_array',
        'call_user_method',
        'call_user_method_array',
        'preg_replace_callback',
    );

    /**
     * @var array
     */
    protected $callerDisallowedClasses = array(
    );

    /**
     * @var string
     */
    protected $formatDetectorClass = 'megabike\\templates\\FormatDetector';

    /**
     * @var string
     */
    protected $cacherClass = 'megabike\\templates\\Cacher';

    /**
     * @var boolean
     */
    protected $requireOpPrefixes = false;

    /**
     * @var boolean
     */
    protected $requireXPrefixes = false;

    /**
     * @var string
     */
    protected $cacheRootPath = null;

    /**
     * @var string
     */
    protected $cacheFolderName = 'templates';

    public function getFormatDetectorClass()
    {
        return $this->formatDetectorClass;
    }

    public function getCacherClass()
    {
        return $this->cacherClass;
    }

    public function getCharset()
    {
        return ConfigBridge::getCharset();
    }

    public function getParserClass($format)
    {
        if (isset($this->parserClasses[$format])) {
            return $this->parserClasses[$format];
        }
        if (isset($this->parserClasses[''])) {
            return $this->parserClasses[''];
        }
        return null;
    }

    public function getTemplateClass($format)
    {
        if (isset($this->templateClasses[$format])) {
            return $this->templateClasses[$format];
        }
        if (isset($this->templateClasses[''])) {
            return $this->templateClasses[''];
        }
        return null;
    }

    public function getOperatorClass($operatorId)
    {
        if (isset($this->operatorClasses[$operatorId])) {
            return $this->operatorClasses[$operatorId];
        }
        return null;
    }

    public function getElementNodeClass($idCode, $format)
    {
        $k = strpos($idCode, ':');
        if ($k === false) {
            if (isset($this->elementNodeClasses[$format][''][$idCode])) {
                return $this->elementNodeClasses[$format][''][$idCode];
            }
            if (isset($this->elementNodeClasses[''][''][$idCode])) {
                return $this->elementNodeClasses[''][''][$idCode];
            }
            if (!$this->requireOpPrefixes) {
                if (isset($this->elementNodeClasses[$format]['op'][$idCode])) {
                    return $this->elementNodeClasses[$format]['op'][$idCode];
                }
                if (isset($this->elementNodeClasses['']['op'][$idCode])) {
                    return $this->elementNodeClasses['']['op'][$idCode];
                }
            }
            if (!$this->requireXPrefixes) {
                if (isset($this->elementNodeClasses[$format]['x'][$idCode])) {
                    return $this->elementNodeClasses[$format]['x'][$idCode];
                }
                if (isset($this->elementNodeClasses['']['x'][$idCode])) {
                    return $this->elementNodeClasses['']['x'][$idCode];
                }
            }
            return null;
        } else {
            $group = substr($idCode, 0, $k);
            $id = substr($idCode, $k + 1);
            return $this->getElementNodeClassByGroup($group, $id, $format);
        }
    }

    public function getAttrNodeClass($idCode, $format)
    {
        $k = strpos($idCode, ':');
        if ($k === false) {
            if (isset($this->attrNodeClasses[$format][''][$idCode])) {
                return $this->attrNodeClasses[$format][''][$idCode];
            }
            if (isset($this->attrNodeClasses[''][''][$idCode])) {
                return $this->attrNodeClasses[''][''][$idCode];
            }
            if (!$this->requireOpPrefixes) {
                if (isset($this->attrNodeClasses[$format]['op'][$idCode])) {
                    return $this->attrNodeClasses[$format]['op'][$idCode];
                }
                if (isset($this->attrNodeClasses['']['op'][$idCode])) {
                    return $this->attrNodeClasses['']['op'][$idCode];
                }
            }
            if (!$this->requireXPrefixes) {
                if (isset($this->attrNodeClasses[$format]['x'][$idCode])) {
                    return $this->attrNodeClasses[$format]['x'][$idCode];
                }
                if (isset($this->attrNodeClasses['']['x'][$idCode])) {
                    return $this->attrNodeClasses['']['x'][$idCode];
                }
            }
            return null;
        } else {
            $group = substr($idCode, 0, $k);
            $id = substr($idCode, $k + 1);
            return $this->getAttrNodeClassByGroup($group, $id, $format);
        }
    }

    public function getElementNodeClassByGroup($group, $id, $format)
    {
        if ((string)$group === '') {
            return $this->getElementNodeClass($id);
        } else {
            if (isset($this->elementNodeClasses[$format][$group][$id])) {
                return $this->elementNodeClasses[$format][$group][$id];
            }
            if (isset($this->elementNodeClasses[''][$group][$id])) {
                return $this->elementNodeClasses[''][$group][$id];
            }
            return null;
        }
    }

    public function getAttrNodeClassByGroup($group, $id, $format)
    {
        if ((string)$group === '') {
            return $this->getAttrNodeClass($id);
        } else {
            if (isset($this->attrNodeClasses[$format][$group][$id])) {
                return $this->attrNodeClasses[$format][$group][$id];
            }
            if (isset($this->attrNodeClasses[''][$group][$id])) {
                return $this->attrNodeClasses[''][$group][$id];
            }
            return null;
        }
    }

    public function getObjectWrapperClasses()
    {
        return $this->objectWrapperClasses;
    }

    public function getWrapperClass()
    {
        return $this->wrapperClass;
    }

    public function getCallerClass()
    {
        return $this->callerClass;
    }

    public function getCachePath()
    {
        if ($this->cacheRootPath !== null) {
            $root = $this->cacheRootPath;
        } else {
            $root = ConfigBridge::getAppCachePath();
        }
        if ((string)$root !== '' && (string)$this->cacheFolderName !== '') {
            return $root.'/'.$this->cacheFolderName;
        }
        return null;
    }

    public function transformClassNs($prefix, $class)
    {
        if ($class === null) {
            return null;
        } elseif (isset($class[0]) && $class[0] === '\\') {
            return ltrim($class, '\\');
        } else {
            return $prefix.'\\'.$class;
        }
    }

    public function getSourcePaths()
    {
        return ConfigBridge::getSourcePaths();
    }

}
