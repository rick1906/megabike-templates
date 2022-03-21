<?php

namespace megabike\templates;

use megabike\utils\FileUtils;
use megabike\templates\Source;
use megabike\templates\Cacher;
use megabike\templates\FileSource;
use megabike\templates\TemplatesException;
use megabike\templates\Input;
use megabike\templates\nodes\root\Scheme;
use megabike\templates\nodes\root\FastScheme;
use megabike\templates\parser\Parser;
use megabike\templates\errors\NodeException;
use megabike\templates\errors\ApplyException;
use megabike\templates\errors\SourceException;

class Template
{
    const FORMAT_NONE = '';
    const FORMAT_TEXT = 'text';
    const FORMAT_HTML = 'html';
    const FORMAT_XML = 'xml';
    const FORMAT_CSS = 'css';
    //
    const CHARSET_AUTODETECT = 0;
    const CHARSET_FROM_CONFIG = 1;
    const CHARSET_FROM_MBSTRING = 2;

    //
    protected static $_session = 0;

    public static function defaultConfig()
    {
        return null;
    }

    /**
     * @param Source $source
     * @return Template
     */
    public static function create(Source $source)
    {
        $class = get_called_class();
        return new $class($source);
    }

    /**
     * @param string $string
     * @param string $charset
     * @param string $format
     * @return Template
     */
    public static function createFromString($string, $charset = null, $format = null)
    {
        return static::create(new Source($string, $charset, $format, static::defaultConfig()));
    }

    /**
     * @param string $file
     * @param string $charset
     * @param string $format
     * @return Template
     */
    public static function createFromFile($file, $charset = null, $format = null)
    {
        return static::create(new FileSource($file, $charset, $format, static::defaultConfig()));
    }

    protected static function sessionStart()
    {
        static::$_session++;
        Cacher::sessionStart();
    }

    protected static function sessionEnd()
    {
        if (static::$_session > 0) {
            static::$_session--;
            Cacher::sessionEnd();
        }
    }

    /**
     * @var Source
     */
    protected $source = null;

    /**
     * @var Parser
     */
    protected $_parser = null;

    /**
     * @var Scheme
     */
    protected $_fullScheme = null;

    /**
     * @var FastScheme
     */
    protected $_fastScheme = null;

    /**
     * @var boolean
     */
    protected $_isFromCache = false;

    /**
     * @var boolean
     */
    protected $_isFastSchemeGenerated = false;

    /**
     * @var boolean
     */
    protected $_isFullSchemeGenerated = false;

    /**
     * @var array
     */
    protected $_objectWrapperClasses = null;

    /**
     * @var mixed
     */
    protected $_caller = null;

    public function __construct(Source $source, $scheme = null, $parser = null)
    {
        $this->source = $source;
        if ($scheme !== null) {
            if ($scheme instanceof FastScheme) {
                $this->_fastScheme = $scheme;
                $this->_isFastSchemeGenerated = true;
            } elseif ($scheme instanceof Scheme) {
                $this->_fullScheme = $scheme;
                $this->_isFullSchemeGenerated = true;
            }
        }
        if ($parser !== null && $parser instanceof Parser) {
            $this->_parser = $parser;
        }
    }

    public function getSource()
    {
        return $this->source;
    }

    public function getConfig()
    {
        return $this->source->getConfig();
    }

    public function getCaller()
    {
        if ($this->_caller === null) {
            $config = $this->source->getConfig();
            $class = $config->getCallerClass();
            return new $class($config);
        }
        return $this->_caller;
    }

    public function getWrapperClass($class = null)
    {
        if ($class === null) {
            return $this->source->getConfig()->getWrapperClass();
        }
        if ($this->_objectWrapperClasses === null) {
            $this->_objectWrapperClasses = $this->source->getConfig()->getObjectWrapperClasses();
        }
        return $this->getWrapperClassRecursive($class);
    }

    private function getWrapperClassRecursive($class)
    {
        if (isset($this->_objectWrapperClasses[$class])) {
            return $this->_objectWrapperClasses[$class];
        }

        $parent = get_parent_class($class);
        if ($parent !== false) {
            $wrapperClass = $this->getWrapperClassRecursive($parent);
        } else {
            $wrapperClass = $this->_objectWrapperClasses[''];
        }
        $this->_objectWrapperClasses[$class] = $wrapperClass;
        return $wrapperClass;
    }

    protected function getFullScheme($allowCacheUpdate = true, $autoGenerate = true)
    {
        if ($this->_fullScheme === null) {
            if (!$this->_isFromCache) {
                if ($this->reloadCache() && $this->_fullScheme !== null) {
                    return $this->_fullScheme;
                }
            }
            if ($autoGenerate && !$this->_isFullSchemeGenerated) {
                $this->update($allowCacheUpdate);
            }
        }
        return $this->_fullScheme;
    }

    protected function getFastScheme($allowCacheUpdate = true, $autoGenerate = true)
    {
        if ($this->_fastScheme === null) {
            if (!$this->_isFromCache) {
                if ($this->reloadCache() && $this->_fastScheme !== null) {
                    return $this->_fastScheme;
                }
            }
            if ($autoGenerate && !$this->_isFastSchemeGenerated) {
                if ($this->_fullScheme !== null) {
                    $this->_fastScheme = $this->_fullScheme->getFastScheme();
                    $this->_isFastSchemeGenerated = true;
                    if ($allowCacheUpdate && $this->_fastScheme !== null) {
                        $this->updateCache();
                    }
                } else {
                    $this->update($allowCacheUpdate);
                }
            }
        }
        return $this->_fastScheme;
    }

    public function getScheme($allowCacheUpdate = true)
    {
        if ($this->_fastScheme !== null) {
            return $this->_fastScheme;
        }
        if ($this->_fullScheme !== null) {
            return $this->_fullScheme;
        }
        if (!$this->_isFromCache && $this->reloadCache()) {
            if ($this->_fastScheme !== null) {
                return $this->_fastScheme;
            }
            if ($this->_fullScheme !== null) {
                return $this->_fullScheme;
            }
        }

        $this->update($allowCacheUpdate);
        if ($this->_fastScheme !== null) {
            return $this->_fastScheme;
        }
        if ($this->_fullScheme !== null) {
            return $this->_fullScheme;
        }
        return null;
    }

    public function getParser()
    {
        if ($this->_parser === null) {
            $this->_parser = $this->source->createParser();
        }
        return $this->_parser;
    }

    public function getTemplateFromFile($file)//TODO: source types in config, static Source::create($id);
    {
        if (FileUtils::isAbsolutePath($file)) {
            if (is_file($file)) {
                return static::createFromFile($file);
            } else {
                return null;
            }
        } else {
            $paths = $this->getConfig()->getSourcePaths(); //TODO: more complex file search
            foreach ($paths as $path) {
                if (is_file($path.'/'.$file)) {
                    return static::createFromFile($path.'/'.$file);
                }
            }
            return null;
        }
    }

    public function getTemplateFromSource($sourceId, $sourceType)
    {
        if ($sourceType === 'file') {
            return $this->getTemplateFromFile($sourceId);
        } else {
            throw new SourceException("Unknown subtemplate source type: '".$sourceType."'");
        }
    }

    public function getImportedTemplate($id)
    {
        return null;
    }

    public function getSubTemplate($sourceId, $sourceType = '')
    {
        if ((string)$sourceType === '') {
            $template = $this->getImportedTemplate($sourceId);
            if ($template !== null) {
                return $template;
            } else {
                $sourceType = $this->detectSourceType($sourceId);
                return $this->getTemplateFromSource($sourceId, $sourceType);
            }
        } else {
            return $this->getTemplateFromSource($sourceId, $sourceType);
        }
    }

    public function getUsedTemplates()
    {
        $used = $this->getScheme()->getUsedTemplates();
        $results = array();
        foreach ($used as $i => $pair) {
            list($sourceId, $sourceType) = $pair;
            if ((string)$sourceType === '') {
                $template = $this->getImportedTemplate($sourceId);
                if ($template !== null) {
                    if ($template instanceof Template) {
                        $results[] = array($sourceId, $template->source->getType());
                    }
                } else {
                    $results[] = array($sourceId, $this->detectSourceType($sourceId));
                }
            } else {
                $results[] = $pair;
            }
        }
        return $results;
    }

    protected function detectSourceType($sourceId)
    {
        return 'file';
    }

    protected function readCache()
    {
        $cacher = $this->source->getCacher();
        try {
            $data = $cacher->load($this->source, $this->source->getUpdateTime());
            if ($data) {
                $this->source->applyDataFromCache($data);
                return $data;
            }
        } catch (TemplatesException $ex) {
            $ex->writeLog();
        }
        return null;
    }

    protected function writeCache($data)
    {
        $cacher = $this->source->getCacher();
        $data += $this->source->getDataForCache();
        return $cacher->set($this->source, $data);
    }

    public function reloadCache()
    {
        $data = $this->readCache();
        if ($data) {
            $this->applyDataFromCache($data);
            return true;
        }
        return false;
    }

    public function updateCache()
    {
        $data = $this->getDataForCache();
        return $this->writeCache($data);
    }

    public function update($updateCache = true)
    {
        $this->rebuild();
        if ($updateCache) {
            return $this->updateCache();
        } else {
            return true;
        }
    }

    public function build()
    {
        if (!$this->_parser || !$this->_isFastSchemeGenerated || !$this->_isFullSchemeGenerated) {
            return $this->rebuild();
        } else {
            return $this->_parser;
        }
    }

    public function rebuild()
    {
        $parser = $this->getParser();
        $this->_fullScheme = $parser->parse();
        $this->_fastScheme = $this->_fullScheme->getFastScheme();
        $this->_isFromCache = false;
        $this->_isFastSchemeGenerated = true;
        $this->_isFullSchemeGenerated = true;
        return $parser;
    }

    public function apply($input)
    {
        static::sessionStart();
        $templateInput = Input::create($this, $input);
        try {
            $result = $this->getScheme()->getValue($templateInput);
        } catch (NodeException $ex) {
            static::sessionEnd();
            $message = $this->getSource()->createErrorMessage($ex->getMessage(), $ex->getSourceIndex());
            throw new ApplyException($message);
        } catch (\Exception $ex2) { //TODO: i need finally here, but it's req php 5.5 :(
            static::sessionEnd();
            throw $ex2;
        }
        static::sessionEnd();
        return $result;
    }

    public function free($freeScheme = true)
    {
        $this->_caller = null;
        $this->_parser = null;
        $this->_objectWrapperClasses = null;
        if ($freeScheme) {
            $this->_isFastSchemeGenerated = false;
            $this->_isFullSchemeGenerated = false;
            $this->_isFromCache = false;
            $this->_fullScheme = null;
            $this->_fastScheme = null;
        }
    }

    public function getDataForCache()
    {
        $data = array();
        $data['class'] = get_class($this);
        $fastScheme = $this->getFastScheme(false);
        if ($fastScheme !== null) {
            $fastScheme->resetInput();
            $data['scheme'] = $fastScheme;
            $data['schemeMode'] = 'fast';
            return $data;
        } else {
            $fullScheme = $this->getFullScheme(false);
            if ($fullScheme !== null) {
                $fullScheme->resetInput();
                $data['scheme'] = $fullScheme;
                $data['schemeMode'] = 'full';
                return $data;
            } else {
                return $data;
            }
        }
    }

    public function applyDataFromCache($data)
    {
        $this->_isFromCache = true;
        $this->_isFastSchemeGenerated = false;
        $this->_isFullSchemeGenerated = false;
        if (isset($data['scheme'])) {
            if ($data['scheme'] instanceof FastScheme) {
                $this->_fastScheme = $data['scheme'];
            } elseif ($data['scheme'] instanceof Scheme) {
                $this->_fullScheme = $data['scheme'];
            }
        }
    }

}
