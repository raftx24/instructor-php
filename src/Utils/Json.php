<?php

namespace Cognesy\Instructor\Utils;

class Json
{
    static public function extract(string $text) : string {
        if (empty($text)) {
            return '';
        }
        $firstOpenBracket = strpos($text, '{');
        if ($firstOpenBracket === false) {
            return '';
        }
        $lastCloseBracket = strrpos($text, '}');
        if ($lastCloseBracket === false) {
            return '';
        }
        return substr($text, $firstOpenBracket, $lastCloseBracket - $firstOpenBracket + 1);
    }

    static public function extractPartial(string $text) : string {
        if (empty($text)) {
            return '';
        }
        $firstOpenBracket = strpos($text, '{');
        if ($firstOpenBracket === false) {
            return '';
        }
        $lastCloseBracket = strrpos($text, '}') ?: strlen($text) - 1;
        return substr($text, $firstOpenBracket, $lastCloseBracket - $firstOpenBracket + 1);
    }
}