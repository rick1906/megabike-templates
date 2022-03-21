<?php

namespace megabike\templates;

use megabike\templates\Source;

class SubSource extends Source
{
    protected $startIndex;
    protected $length;
    protected $source;

    public function __construct(Source $source, $startIndex, $length, $format = null)
    {
        $this->startIndex = $startIndex;
        $this->length = $length;
        $this->source = $source;
        $charset = $source->getInputCharset();
        parent::__construct(null, $charset, $format, $source->config);
    }

    protected function init()
    {
        $buffer = $this->source->getContent()->getSubstring($this->startIndex, $this->length);
        $this->initContent($buffer);
    }

    protected function initContent($buffer)
    {
        $contentString = $buffer;
        $charset = $this->getInternalCharset();
        $isMultibyte = mb_strlen($contentString, $charset) < strlen($contentString);
        $this->_content = new Content($contentString, $isMultibyte, $charset);
        $this->initFormat($contentString);
    }

    public function getLineAndCharIndex($index)
    {
        return $this->source->getLineAndCharIndex($this->startIndex + $index);
    }

    public function getTemplateName()
    {
        return $this->source->getTemplateName();
    }

    public function getUpdateTime()
    {
        return $this->source->getUpdateTime();
    }

    public function getInternalCharset()
    {
        return $this->source->getInternalCharset();
    }

}
