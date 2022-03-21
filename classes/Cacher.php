<?php

namespace megabike\templates;

use megabike\common\ConfigBridge;
use megabike\utils\FileUtils;
use megabike\templates\Source;
use megabike\templates\TemplatesConfig;

class Cacher
{

    protected static $_cache = null;
    protected static $_session = 0;

    public static function sessionStart()
    {
        static::$_session++;
    }

    public static function sessionEnd()
    {
        if (static::$_session > 0) {
            static::$_session--;
            if (static::$_session === 0) {
                static::$_cache = null;
            }
        }
    }

    /**
     * @var TemplatesConfig 
     */
    protected $config;
    protected $cachePath;

    public function __construct($config)
    {
        $this->config = $config;
        $this->cachePath = $this->config->getCachePath();
    }

    public function getCacheFile(Source $source)
    {
        if ($this->cachePath) {
            $idString = $source->getIdString();
            $hash = $this->getHash($source, $idString);
            $num = str_pad(hexdec(substr($hash, 0, 3)), 6, '0', STR_PAD_LEFT);
            return $this->cachePath.'/'.$num.'/'.$this->getFileName($source, $idString, $hash);
        }
        return null;
    }

    protected function getHash(Source $source, $idString)
    {
        return md5($idString.$source->getInternalCharset());
    }

    protected function getFileName(Source $source, $idString, $hash)
    {
        $classId = preg_replace('/\W+/', '_', get_class($source));
        return $hash.'.'.strlen($idString).'.'.$classId;
    }

    protected function serializeData($data)
    {
        return serialize($data);
    }

    protected function unserializeData($string)
    {
        return unserialize($string);
    }

    public function load(Source $source, $timeLimit = null)
    {
        $file = $this->getCacheFile($source);
        if ($file === null || !is_file($file)) {
            return null;
        }
        if (isset(static::$_cache[$file])) {
            return static::$_cache[$file];
        }

        $mtime = @filemtime($file);
        if ($timeLimit && (!$mtime || $mtime < $timeLimit)) {
            return null;
        }

        $gtime = ConfigBridge::getAppGeneratedTime();
        if ($gtime && (!$mtime || $mtime < $gtime)) {
            return null;
        }

        $content = $this->readCacheFile($file);
        if ($content !== false) {
            $data = $this->unserializeData($content);
            if ($data !== false) {
                if (static::$_session > 0) {
                    static::$_cache[$file] = $data;
                }
                return $data;
            }
        }

        return null;
    }

    public function set(Source $source, $data)
    {
        $file = $this->getCacheFile($source);
        $content = $this->serializeData($data);
        if (!$file || $content === false) {
            return false;
        }
        if (static::$_session > 0) {
            static::$_cache[$file] = $data;
        }
        return $this->writeCacheFile($file, $content);
    }

    protected function writeCacheFile($filepath, $content)
    {
        $dir = dirname($filepath);
        if (!is_dir($dir)) {
            FileUtils::makeDirRecursive($dir, 0777);
        }
        $fp = @fopen($filepath, 'a+');
        if (!$fp) {
            return false;
        } else {
            $r = flock($fp, LOCK_EX);
            if (!$r) {
                @fclose($fp);
                return false;
            }
        }

        $r1 = ftruncate($fp, 0);
        fseek($fp, 0);
        $r2 = fwrite($fp, $content);
        @flock($fp, LOCK_UN);
        @fclose($fp);

        if ($r1 && $r2 !== false) {
            @chmod($filepath, 0666);
            return true;
        } else {
            @unlink($filepath);
            return false;
        }
    }

    protected function readCacheFile($filepath)
    {
        $fp = fopen($filepath, 'r');
        if (!$fp) {
            return false;
        } else {
            flock($fp, LOCK_SH);
            $buffer = stream_get_contents($fp);
            flock($fp, LOCK_UN);
            fclose($fp);
            return $buffer;
        }
    }

}
