<?php

namespace megabike\templates\parser\modes;

use megabike\templates\parser\ParserStatus;
use megabike\templates\errors\BuildException;

abstract class Mode
{
    protected static $commonEvents = array(
        'codeEscape' => '/\\\\/',
        'code' => '/\{\{/',
        'phpShort' => '/\<\?/',
        'phpLong' => '/\<\?php/i',
        'htmlTagOpen' => '/\<(?:[a-z0-9_\-]+:)?[a-z0-9_\-]+/i',
        'htmlTagClose' => '/\<\/(?:[a-z0-9_\-]+:)?[a-z0-9_\-]+/i',
    );
    //
    protected $id;
    protected $flags;
    //
    protected $_regexp = null;
    protected $_events = null;
    protected $_eventsIndexes = null;

    public final function __construct($id, $flags = 0)
    {
        $this->id = $id;
        $this->flags = $flags;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getFlags()
    {
        return $this->flags;
    }

    public function start(ParserStatus $status)
    {
        return true;
    }

    public function forceExit(ParserStatus $status)
    {
        
    }

    //TODO: optimizations for unicode
    public function nextGlobal(ParserStatus $status)
    {
        $events = $this->getEvents();
        if (empty($events)) {
            return $status->modeExit();
        } else {
            list($eventId, $captured, $capturedIndex) = $this->matchGlobal($status, $status->index);
            if ($eventId !== null) {
                $this->processDefaultMatched($status, $status->start, $capturedIndex);
                $event = $this->getEvent($eventId);
                $status->success($event[1], $capturedIndex);
                return $this->processEvent($eventId, $status, $captured);
            }
        }
        return $this->processNoneMatched($status);
    }

    public function nextEvent(ParserStatus $status, $eventIndex)
    {
        $events = $this->getEvents();
        if (isset($events[$eventIndex])) {
            $event = $events[$eventIndex];
            list($captured, $capturedIndex) = $this->matchEvent($event, $status, $status->index);
            if ($capturedIndex !== null) {
                $status->success($event[1], $capturedIndex);
                return $this->processEvent($event[0], $status, $captured);
            } else {
                return $status->fail($eventIndex);
            }
        } elseif ($this->isAnchored()) {
            return $this->processNoneMatched($status);
        } else {
            return $status->move();
        }
    }

    protected function matchEvent($event, ParserStatus $status, $index)
    {
        $content = $status->getContent();
        $offset = $content->getByteOffset($index);
        $string = $content->getString();
        $matches = null;
        if (preg_match($this->getEventRegexp($event), $string, $matches, PREG_OFFSET_CAPTURE, $offset)) {
            return array($matches[0][0], $content->getIndex($matches[0][1]));
        } else {
            return array(null, null);
        }
    }

    protected function matchGlobal(ParserStatus $status, $index)
    {
        $matches = null;
        $content = $status->getContent();
        $offset = $content->getByteOffset($index);
        $string = $content->getString();
        if (preg_match($this->getGlobalRegexp(), $string, $matches, PREG_OFFSET_CAPTURE, $offset)) {
            foreach ($matches as $key => $match) {
                if ($key > 0 && $match[1] >= 0) {
                    return array($this->_events[$key - 1][0], $match[0], $content->getIndex($match[1]));
                }
            }
        }
        return array(null, null, null);
    }

    protected function processNoneMatched(ParserStatus $status)
    {
        if ($this->isCapture()) {
            return $this->processDefaultMatched($status, $status->start, $status->getLength());
        } else {
            return $status->fallback();
        }
    }

    protected function processDefaultMatched(ParserStatus $status, $start, $end)
    {
        if ($end > $start && $this->isCapture()) {
            $string = $status->getContent()->getSubstring($start, $end - $start);
            $this->capture($status, $string);
        }
        return $status->update($end);
    }

    protected function processEvent($eventId, ParserStatus $status, $captured)
    {
        return $this->{'on'.$eventId}($status, $captured);
    }

    protected function capture(ParserStatus $status, $string)
    {
        
    }

    protected abstract function getEventsData();

    protected function isAnchored()
    {
        return false;
    }

    protected function isCapture()
    {
        return true;
    }

    protected function registerEvents($eventsData)
    {
        $this->_events = array();
        $this->_eventsIndexes = array();
        $this->_regexp = null;
        $this->createEvents($eventsData);
    }

    protected function createEvents($eventsData, $index = 0)
    {
        if ($eventsData) {
            foreach ($eventsData as $id => $data) {
                if (is_array($data)) {
                    $index = $this->createEvents($data, $index);
                } elseif ($data) {
                    $event = $this->createEvent($id, $data, $index);
                    $this->_events[$index] = $event;
                    $this->_eventsIndexes[$event[0]] = $index;
                    $index++;
                }
            }
        }
        return $index;
    }

    protected function createEvent($id, $data, $index)
    {
        $event = array($id, $index);
        $event[2] = $this->parseEventData($data);
        $event[3] = null;
        return $event;
    }

    protected function parseEventData($data)
    {
        if ($data[0] === '%') {
            $type = $data[1];
            $val = substr($data, 2);
        } elseif ($data[0] === '/') {
            $type = 'r';
            $val = $data;
        } else {
            $type = 's';
            $val = $data;
        }
        if ($type === 'r') {
            return $this->parseRegexp($val);
        }
        return false;
    }

    protected function parseRegexp($regexp)
    {
        $d = $regexp[0];
        $p = strrpos($regexp, $d);
        if ($d !== '/') {
            throw new BuildException("Regexp {$regexp} for parser mode '".get_class($this)."' should have '/' delimiter");
        } if ($p === false) {
            throw new BuildException("Invalid regexp {$regexp} for parser mode '".get_class($this)."'");
        } else {
            $body = substr($regexp, 1, $p - 1);
            $mods = substr($regexp, $p + 1);
            $newBody = rtrim(ltrim($body, '^'), '$');
            $newMods1 = preg_replace('/[^iU]/', '', $mods);
            $newMods2 = preg_replace('/[^u]/', '', $mods);

            if (strpos($newBody, '(') !== false) {
                $matches = null;
                if (preg_match('/(?:'.$newBody.'|(.*))/', '', $matches)) {
                    if (count($matches) > 2) {
                        throw new BuildException("Regexp {$regexp} for parser mode '".get_class($this)."' should not capture any subpatterns");
                    }
                }
            }

            return array($newBody, $newMods1, $newMods2);
        }
    }

    protected function generateGlobalRegexp($events, $isAnchored = false)
    {
        $parts = array();
        $isUnicode = false;
        foreach ($events as $event) {
            //$part = '(?P<'.$event[0].'>';
            $part = '(';
            if ($event[2][1] !== '') {
                $part .= '(?'.$event[2][1].')';
            }
            if (strpos($event[2][2], 'u') !== false) {
                $isUnicode = true;
            }
            $part .= $event[2][0];
            $part .= ')';
            $parts[] = $part;
        }
        $mods = 'Ss';
        if ($isAnchored) {
            $mods .= 'A';
        }
        if ($isUnicode) {
            $mods .= 'u';
        }
        return '/(?:'.implode('|', $parts).')/'.$mods;
    }

    protected function generateEventRegexp($event)
    {
        return '/'.$event[2][0].'/sA'.$event[2][1].$event[2][2];
    }

    protected function getEvents()
    {
        if ($this->_events === null) {
            $this->registerEvents($this->getEventsData());
        }
        return $this->_events;
    }

    protected function getEvent($id)
    {
        $events = $this->getEvents();
        if (isset($this->_eventsIndexes[$id])) {
            return $events[$this->_eventsIndexes[$id]];
        } else {
            return null;
        }
    }

    protected function getGlobalRegexp()
    {
        if ($this->_regexp === null) {
            $this->_regexp = $this->generateGlobalRegexp($this->getEvents(), $this->isAnchored());
        }
        return $this->_regexp;
    }

    protected function getEventRegexp($event)
    {
        if (isset($event[3])) {
            return $event[3];
        } else {
            $r = $this->generateEventRegexp($event);
            $this->_events[$event[1]][3] = $r;
            return $r;
        }
    }

}

