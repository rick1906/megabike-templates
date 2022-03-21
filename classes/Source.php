<?php

namespace megabike\templates;

use megabike\common\ConfigBridge;
use megabike\templates\Content;
use megabike\templates\Template;
use megabike\templates\TemplatesModule;
use megabike\templates\TemplatesConfig;
use megabike\templates\errors\SourceException;

class Source
{
    /**
     * @var TemplatesConfig
     */
    protected $config = null;

    /**
     * @var Content
     */
    protected $_content = null;
    //
    protected $_charset = null;
    protected $_format = null;

    public function __construct($buffer, $charset = null, $format = null, TemplatesConfig $config = null)
    {
        $this->_format = $format;
        $this->_charset = $charset !== null ? $charset : Template::CHARSET_AUTODETECT;
        if ($config !== null) {
            $this->config = $config;
        } else {
            $this->config = $this->getDefaultConfig();
        }
        if ($buffer !== null) {
            $this->initContent($buffer);
        }
    }
    
    protected function getDefaultConfig()
    {
        return TemplatesModule::config();
    }

    public function getType()
    {
        return 'string';
    }

    public function getInternalCharset()
    {
        return $this->config->getCharset();
    }

    public function getInputCharset()
    {
        if ($this->_charset === null || is_int($this->_charset)) {
            $this->init();
        }
        return $this->_charset;
    }

    public function getFormat()
    {
        if ($this->_format === null) {
            $this->init();
        }
        return (string)$this->_format;
    }

    public function getContent()
    {
        if ($this->_content === null) {
            $this->init();
        }
        return $this->_content;
    }

    protected function init()
    {
        throw new SourceException("Lazy initialization is not implemented in ".get_class($this));
    }

    protected function initContent($buffer)
    {
        if (is_array($this->_charset)) {
            $this->_charset = $this->detectCharset($buffer, $this->_charset);
        } elseif (is_int($this->_charset)) {
            if ($this->_charset === Template::CHARSET_FROM_CONFIG) {
                $this->_charset = ConfigBridge::getCharset();
            } elseif ($this->_charset === Template::CHARSET_AUTODETECT) {
                $this->_charset = $this->detectCharset($buffer);
            } elseif ($this->_charset === Template::CHARSET_FROM_MBSTRING) {
                $this->_charset = mb_internal_encoding();
            } else {
                $this->_charset = $this->detectCharset($buffer);
            }
        }

        $charset = $this->getInternalCharset();
        if (strcasecmp($this->_charset, $charset)) {
            $contentString = iconv($this->_charset, $charset, $buffer);
        } else {
            $contentString = $buffer;
        }

        $isMultibyte = mb_strlen($contentString, $charset) < strlen($contentString);
        $this->_content = new Content($contentString, $isMultibyte, $charset);
        $this->initFormat($contentString);
    }

    protected function initFormat($content, $detector = null)
    {
        if ($this->_format === null) {
            $detector = $detector ? $detector : $this->createFormatDetector();
            $this->_format = $detector->detectFormatByContent($content);
            if ($this->_format === null) {
                $this->_format = Template::FORMAT_NONE;
            }
        }
    }

    protected function detectCharset($buffer, $variants = array())
    {
        if ($variants) {
            $charset = mb_detect_encoding($buffer, $variants, true);
            if ($charset) {
                return $charset;
            }
        }
        if (mb_detect_encoding($buffer, 'utf-8', true)) {
            return 'utf-8';
        }
        return ConfigBridge::getCharset();
    }

    public function createFormatDetector()
    {
        $detectorClass = $this->config->getFormatDetectorClass();
        return new $detectorClass();
    }

    public function createParser()
    {
        $class = $this->config->getParserClass($this->getFormat());
        return new $class($this, $this->config);
    }

    public function getLineAndCharIndex($index)
    {
        return $this->getContent()->getLineAndCharIndex($index);
    }

    public function createErrorMessage($message, $index)
    {
        list($line, $pos) = $this->getLineAndCharIndex($index);
        $name = $this->getTemplateName();
        return "{$message} in template '{$name}' on line ".($line + 1)." (symbol ".($pos + 1).")";
    }

    public function getConfig()
    {
        return $this->config;
    }

    public function getTemplateName()
    {
        $code = strtoupper(substr(md5($this->getContent()->getString()), 0, 4));
        return '[STRING_'.$code.']';
    }

    public function getIdString()
    {
        return $this->getContent()->getString();
    }

    public function getUpdateTime()
    {
        return null;
    }

    protected function getTemplateClass()
    {
        if ($this->_format !== null) {
            return $this->config->getTemplateClass($this->_format);
        } else {
            $classes = $this->config['templateClasses'];
            if (!isset($classes['']) || count($classes) > 1) {
                $this->config->getTemplateClass($this->getFormat());
            } else {
                return $classes[''];
            }
        }
    }

    public function getDataForCache()
    {
        return array(
            'charset' => $this->getInputCharset(),
            'format' => $this->getFormat(),
        );
    }

    public function applyDataFromCache($data)
    {
        if (isset($data['charset'])) {
            $this->_charset = $data['charset'];
        }
        if (isset($data['format'])) {
            $this->_format = $data['format'];
        }
    }

    public function getCacher()
    {
        $cacherClass = $this->config->getCacherClass();
        return new $cacherClass($this->config);
    }

    public function getTemplate()
    {
        $class = $this->getTemplateClass();
        return new $class($this);
    }

}
