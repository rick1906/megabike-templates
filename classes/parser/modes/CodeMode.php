<?php

namespace megabike\templates\parser\modes;

use megabike\templates\parser\ParserStatus;
use megabike\templates\parser\modes\Mode;
use megabike\templates\parser\tokens\CodeSequence;
use megabike\templates\parser\tokens\EscapeToken;
use megabike\templates\parser\tokens\XmlAttrToken;
use megabike\templates\errors\ParseException;

class CodeMode extends Mode
{

    public function start(ParserStatus $status)
    {
        if (EscapeToken::escape($status, $status->index)) {
            return false;
        }
        if (empty($status->params['tag'])) {
            return false;
        }

        $tag = $status->params['tag'];
        $status->shift($tag);
        if ($tag === '{{') {
            $result = $this->processPhpCode($status, '{{', '}}', true);
        } elseif ($tag === '{') {
            $result = $this->processPhpCode($status, '{', '}', true);
        } else {
            $result = $this->processPhpCode($status, $tag, '?>', false, !empty($status->params['raw']));
        }
        if ($result === true) {
            return $status->modeExit();
        } else {
            return $result;
        }
    }

    protected function checkCodeTokens($code, $tokens, $openTag, $rawCode = false)
    {
        if ($openTag === '{') {
            if (strpos($code, '{') !== false || strpos($code, '}') !== false) {
                return false;
            }
            return true;
        }
        return true;
    }

    protected function processPhpCode(ParserStatus $status, $openTag, $closeTag, $checkBrackets = false, $rawCode = false)
    {
        $start = $status->index - strlen($openTag);
        list($code, $tokens, $closeIndex) = CodeSequence::getPhpCodeInfo($status->getContent(), $status->index, $closeTag, $checkBrackets);
        if ($code !== false) {
            $end = $closeIndex + strlen($closeTag);
            if ($this->checkCodeTokens($code, $tokens, $openTag, $rawCode)) {
                $codeSequence = new CodeSequence($start, $openTag, $code, $tokens, $rawCode);
                if ($openTag === '{{' && $codeSequence->isOperatorTag()) {
                    $matches = null;
                    $status->addMasterToken($codeSequence);
                    $status->params['closeIndex'] = $closeIndex;
                    $status->params['endIndex'] = $end;
                    if (preg_match('/\s*\S+\s*/', $code, $matches)) {
                        $status->shift($matches[0]);
                        return $status;
                    } else {
                        $codeSequence->disableOperator();
                        return true;
                    }
                } else {
                    $status->addToken($codeSequence);
                    $status->update($end);
                    return true;
                }
            } else {
                throw new ParseException($status->getSource(), $start, "Invalid code fragment");
            }
        } else {
            if ($openTag !== '{') {
                throw new ParseException($status->getSource(), $start, "No close-tag '{$closeTag}' found for code fragment");
            } else {
                return false;
            }
        }
    }

    protected function isCapture()
    {
        return false;
    }

    protected function isAnchored()
    {
        return true;
    }

    protected function getEventsData()
    {
        return array(
            'attr' => '/[\w\-:]+/i',
            'quote' => '/[\'"]/',
            'space' => '/\s+/',
        );
    }

    protected function onAttr(ParserStatus $status, $captured)
    {
        $params = array('captured' => $captured);
        return $status->modeSwitch('codeAttr', 0, $params);
    }

    protected function onSpace(ParserStatus $status, $captured)
    {
        return $status->shift($captured);
    }

    protected function onQuote(ParserStatus $status, $captured)
    {
        $params = array('attrName' => '');
        $token = new XmlAttrToken($status->index, '');
        $next = $status->modeSwitch('codeAttrValue', 0, $params);
        $next->addMasterToken($token);
        return $next;
    }

    protected function processNoneMatched(ParserStatus $status)
    {
        $token = $status->getMasterToken();
        if ($status->index < $status->params['closeIndex']) {
            $token->disableOperator();
        }

        $status->update($status->params['endIndex']);
        return $status->modeExit();
    }

}
