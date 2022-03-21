<?php

namespace megabike\templates;

use megabike\templates\Template;

class FormatDetector
{

    public function detectFormatByContent($content)
    {
        $string = preg_replace('/\{\{(.*?)\}\}/is', '', $content);
        if (preg_match('/^\s*\<([a-z0-9_\-\:]+)(\s*|\s+[^>]+)\>/i', $string)) {
            return Template::FORMAT_HTML;
        }
        if (preg_match('/^\s*\<!(doctype)\s+(html)\W+/i', $string)) {
            return Template::FORMAT_HTML;
        }
        if (preg_match('/^\s*\<(\?xml)\W+/i', $string)) {
            return Template::FORMAT_XML;
        }
        if (preg_match('/\<(div|span)(\s*|\s+[^>]+)\>/i', $string)) {
            return Template::FORMAT_HTML;
        }
        return null;
    }

    public function detectFormatByFileName($filename)
    {
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        $type = strtolower($ext);
        if ($type === 'php') {
            $base = basename($filename, '.'.$ext);
            return $this->detectFormatByFileName($base);
        }
        if ($type === 'html') {
            return Template::FORMAT_HTML;
        }
        if ($type === 'css') {
            return Template::FORMAT_CSS;
        }
        if ($type === 'xml') {
            return Template::FORMAT_XML;
        }
        return null;
    }

}
