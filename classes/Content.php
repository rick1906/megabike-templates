<?php

namespace megabike\templates;

use megabike\templates\TemplatesModule;

class Content
{
    const CHUNK_SIZE = 4096;

    private static $_defaultCharset = null;

    public static function getDefaultCharset()
    {
        if (self::$_defaultCharset === null) {
            self::$_defaultCharset = TemplatesModule::config()->getCharset();
        }
        return self::$_defaultCharset;
    }

    public static function setDefaultCharset($charset)
    {
        self::$_defaultCharset = $charset;
    }

    public static function htmlEncode($string, $flags = null)
    {
        return htmlspecialchars($string, $flags === null ? ENT_COMPAT : $flags, self::getDefaultCharset(), true);
    }

    public static function htmlAttrEncode($string, $flags = null)
    {
        return htmlspecialchars($string, $flags === null ? ENT_COMPAT : $flags, self::getDefaultCharset(), false);
    }

    public static function htmlDecode($string, $flags = null)
    {
        return html_entity_decode($string, $flags === null ? ENT_QUOTES : $flags, self::getDefaultCharset());
    }

    public static function toLower($string)
    {
        return mb_strtolower($string, self::getDefaultCharset());
    }

    public static function toUpper($string)
    {
        return mb_strtoupper($string, self::getDefaultCharset());
    }

    protected $string;
    protected $isMultibyte;
    protected $charset;
    //
    protected $_charOffsets = null;
    protected $_lastOffset = 0;
    protected $_lastOffsetIndex = 0;

    public function __construct($string, $isMultibyte, $charset)
    {
        $this->string = $string;
        $this->isMultibyte = $isMultibyte;
        $this->charset = $charset;
        self::setDefaultCharset($charset);
    }

    public function computeLength($string)
    {
        return $this->isMultibyte ? mb_strlen($string, $this->charset) : strlen($string);
    }

    public function getString()
    {
        return $this->string;
    }

    public function getCharset()
    {
        return $this->charset;
    }

    public function isMultibyte()
    {
        return $this->isMultibyte;
    }

    protected function getCharOffsets()
    {
        if (!$this->isMultibyte) {
            return null;
        } elseif ($this->_charOffsets !== null) {
            return $this->_charOffsets;
        } else {
            $buffer = $this->string;
            $length = mb_strlen($buffer, $this->charset);
            $this->_charOffsets = new \SplFixedArray($length);
            $index = 0;
            $position = 0;
            while ($length > 0) {
                $part = mb_substr($buffer, 0, self::CHUNK_SIZE, $this->charset);
                $chars = preg_split('//u', $part, -1, PREG_SPLIT_NO_EMPTY);
                $count = count($chars);
                for ($i = 0; $i < $count; ++$i) {
                    $position += strlen($chars[$i]);
                    $this->_charOffsets[$index + $i] = $position;
                }
                $length -= $count;
                $index += $count;
                $buffer = substr($buffer, strlen($part));
            }
            return $this->_charOffsets;
        }
    }

    protected function searchOffset($offsets, $needle, $start, $end)
    {
        if ($end - $start < 32) {
            for ($i = $start; $i <= $end; ++$i) {
                if ($needle == $offsets[$i]) {
                    return $i;
                }
            }
            return false;
        } else {
            $mid = (int)(($start + $end) / 2);
            $val = $offsets[$mid];
            if ($needle == $val) {
                return $mid;
            } elseif ($needle < $val) {
                return $this->searchOffset($offsets, $needle, $start, $mid - 1);
            } else {
                return $this->searchOffset($offsets, $needle, $mid + 1, $end);
            }
        }
    }

    public function getLength()
    {
        if ($this->isMultibyte) {
            return $this->getCharOffsets()->count();
        } else {
            return strlen($this->string);
        }
    }

    public function getIndex($offset)
    {
        if ($this->isMultibyte) {
            if ($offset > $this->_lastOffset && ($d = ($offset - $this->_lastOffset)) < 64) {
                $string = substr($this->string, $this->_lastOffset, $d);
                $index = $this->_lastOffsetIndex + mb_strlen($string, $this->charset);
                $this->_lastOffset = $offset;
                $this->_lastOffsetIndex = $index;
                return $index;
            } elseif ($offset == 0) {
                return 0;
            } elseif ($offset == $this->_lastOffset) {
                return $this->_lastOffsetIndex;
            } elseif ($offset >= strlen($this->string)) {
                return $this->getLength();
            } else {
                $charOffsets = $this->getCharOffsets();
                $index = $this->searchOffset($charOffsets, $offset, 0, $charOffsets->count() - 1);
                if ($index === false) {
                    $string = substr($this->string, 0, $offset);
                    $index = mb_strlen($string, $this->charset);
                } else {
                    $index += 1;
                }
                $this->_lastOffset = $offset;
                $this->_lastOffsetIndex = $index;
                return $index;
            }
        } else {
            return $offset;
        }
    }

    public function getByteOffset($index)
    {
        if ($this->isMultibyte) {
            $charOffsets = $this->getCharOffsets();
            return $index > 0 ? $charOffsets[$index - 1] : 0;
        } else {
            return $index;
        }
    }

    public function getChar($index)
    {
        if ($this->isMultibyte) {
            $charOffsets = $this->getCharOffsets();
            $pos0 = $index > 0 ? $charOffsets[$index - 1] : 0;
            $pos1 = $charOffsets[$index];
            $length = $pos1 - $pos0;
            return $length === 1 ? $this->string[$pos0] : substr($this->string, $pos0, $length);
        } else {
            return $this->string[$index];
        }
    }

    public function getSubstring($index, $length)
    {
        if ($this->isMultibyte) {
            $charOffsets = $this->getCharOffsets();
            $pos0 = $index > 0 ? $charOffsets[$index - 1] : 0;
            if ($index + $length - 1 < $charOffsets->count()) {
                $pos1 = $length > 0 ? $charOffsets[$index + $length - 1] : $pos0;
                return substr($this->string, $pos0, $pos1 - $pos0);
            } else {
                return substr($this->string, $pos0);
            }
        } else {
            return substr($this->string, $index, $length);
        }
    }

    public function indexOf($string, $offset = 0)
    {
        if ($this->isMultibyte) {
            return mb_strpos($this->string, $string, $offset, $this->charset);
        } else {
            return strpos($this->string, $string, $offset);
        }
    }

    public function getLineAndCharIndex($index)
    {
        $string = $this->getSubstring(0, $index);
        $matches = null;
        $u = $this->isMultibyte ? 'u' : '';
        preg_match_all('/\r?\n/'.$u, $string, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
        $lineIndex = count($matches);
        if ($lineIndex > 0) {
            $last = end($matches);
            $lineString = substr($string, $last[0][1] + strlen($last[0][0]));
            $charIndex = mb_strlen($lineString, $this->charset);
        } else {
            $charIndex = $index;
        }
        return array($lineIndex, $charIndex);
    }

}
