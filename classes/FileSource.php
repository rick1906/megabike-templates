<?php

namespace megabike\templates;

use megabike\templates\Source;
use megabike\templates\TemplatesConfig;
use megabike\templates\errors\SourceException;

class FileSource extends Source
{
    protected $filename;

    public function __construct($file, $charset = null, $format = null, TemplatesConfig $config = null)
    {
        $filename = @realpath($file);
        if ($filename === false) {
            throw new SourceException("Template file does not exist: '{$file}'");
        } else {
            $this->filename = $filename;
            parent::__construct(null, $charset, $format, $config);
        }
    }

    protected function init()
    {
        $buffer = @file_get_contents($this->filename);
        if ($buffer === false) {
            throw new SourceException("Failed to read template file: '{$this->filename}'");
        } else {
            $this->initContent($buffer);
        }
    }

    protected function initFormat($content, $detector = null)
    {
        if ($this->_format === null) {
            $detector = $detector ? $detector : $this->createFormatDetector();
            $this->_format = $detector->detectFormatByFileName($this->filename);
            parent::initFormat($content, $detector);
        }
    }
    
    public function getType()
    {
        return 'file';
    }

    public function getFile()
    {
        return $this->filename;
    }

    public function getTemplateName()
    {
        return $this->filename;
    }

    public function getIdString()
    {
        return $this->filename;
    }

    public function getUpdateTime()
    {
        $time = @filemtime($this->filename);
        return $time ? $time : null;
    }

}
