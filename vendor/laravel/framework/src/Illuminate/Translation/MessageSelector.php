<?php

namespace Illuminate\Translation;

use Illuminate\Support\Str;

class MessageSelector
{
    /**
     * Select a proper translation string based on the given number.
     *
     * 根据给定的数字选择适当的翻译字符串
     *
     * @param  string  $line
     * @param  int  $number
     * @param  string  $locale
     * @return mixed
     */
    public function choose($line, $number, $locale)
    {
        $segments = explode('|', $line);
        //使用内联条件提取翻译字符串
        if (($value = $this->extract($segments, $number)) !== null) {
            return trim($value);
        }
        //               从每个段中删除行内的条件，只留下文本
        $segments = $this->stripConditions($segments);
        //                   让索引用于复数
        $pluralIndex = $this->getPluralIndex($locale, $number);

        if (count($segments) == 1 || ! isset($segments[$pluralIndex])) {
            return $segments[0];
        }

        return $segments[$pluralIndex];
    }

    /**
     * Extract a translation string using inline conditions.
     *
     * 使用内联条件提取翻译字符串
     *
     * @param  array  $segments
     * @param  int  $number
     * @return mixed
     */
    private function extract($segments, $number)
    {
        foreach ($segments as $part) {
            //                         如果条件匹配，就获得翻译字符串
            if (! is_null($line = $this->extractFromString($part, $number))) {
                return $line;
            }
        }
    }

    /**
     * Get the translation string if the condition matches.
     *
     * 如果条件匹配，就获得翻译字符串
     *
     * @param  string  $part
     * @param  int  $number
     * @return mixed
     */
    private function extractFromString($part, $number)
    {
        preg_match('/^[\{\[]([^\[\]\{\}]*)[\}\]](.*)/s', $part, $matches);

        if (count($matches) != 3) {
            return;
        }

        $condition = $matches[1];

        $value = $matches[2];
        //确定一个给定的字符串包含另一个字符串
        if (Str::contains($condition, ',')) {
            list($from, $to) = explode(',', $condition, 2);

            if ($to == '*' && $number >= $from) {
                return $value;
            } elseif ($from == '*' && $number <= $to) {
                return $value;
            } elseif ($number >= $from && $number <= $to) {
                return $value;
            }
        }

        return $condition == $number ? $value : null;
    }

    /**
     * Strip the inline conditions from each segment, just leaving the text.
     *
     * 从每个段中删除行内的条件，只留下文本
     *
     * @param  array  $segments
     * @return array
     */
    private function stripConditions($segments)
    {
        //                         在每个项目上运行map
        return collect($segments)->map(function ($part) {
            return preg_replace('/^[\{\[]([^\[\]\{\}]*)[\}\]]/', '', $part);
        })->all();//获取集合中的所有项目
    }

    /**
     * Get the index to use for pluralization.
     *
     * 让索引用于复数
     *
     * The plural rules are derived from code of the Zend Framework (2010-09-25), which
     * is subject to the new BSD license (http://framework.zend.com/license/new-bsd)
     * Copyright (c) 2005-2010 - Zend Technologies USA Inc. (http://www.zend.com)
     *
     * 复数规则来自于Zend Framework(2010-09-25)的代码，该规则遵循新的BSD许可证(http://framework.zend.com/许可证/新BSD)版权(c)2005-2010-Zend技术美国公司(http://www.zend.com)
     *
     * @param  string  $locale
     * @param  int  $number
     * @return int
     */
    public function getPluralIndex($locale, $number)
    {
        switch ($locale) {
            case 'az':
            case 'bo':
            case 'dz':
            case 'id':
            case 'ja':
            case 'jv':
            case 'ka':
            case 'km':
            case 'kn':
            case 'ko':
            case 'ms':
            case 'th':
            case 'tr':
            case 'vi':
            case 'zh':
                return 0;
                break;
            case 'af':
            case 'bn':
            case 'bg':
            case 'ca':
            case 'da':
            case 'de':
            case 'el':
            case 'en':
            case 'eo':
            case 'es':
            case 'et':
            case 'eu':
            case 'fa':
            case 'fi':
            case 'fo':
            case 'fur':
            case 'fy':
            case 'gl':
            case 'gu':
            case 'ha':
            case 'he':
            case 'hu':
            case 'is':
            case 'it':
            case 'ku':
            case 'lb':
            case 'ml':
            case 'mn':
            case 'mr':
            case 'nah':
            case 'nb':
            case 'ne':
            case 'nl':
            case 'nn':
            case 'no':
            case 'om':
            case 'or':
            case 'pa':
            case 'pap':
            case 'ps':
            case 'pt':
            case 'so':
            case 'sq':
            case 'sv':
            case 'sw':
            case 'ta':
            case 'te':
            case 'tk':
            case 'ur':
            case 'zu':
                return ($number == 1) ? 0 : 1;
            case 'am':
            case 'bh':
            case 'fil':
            case 'fr':
            case 'gun':
            case 'hi':
            case 'hy':
            case 'ln':
            case 'mg':
            case 'nso':
            case 'xbr':
            case 'ti':
            case 'wa':
                return (($number == 0) || ($number == 1)) ? 0 : 1;
            case 'be':
            case 'bs':
            case 'hr':
            case 'ru':
            case 'sr':
            case 'uk':
                return (($number % 10 == 1) && ($number % 100 != 11)) ? 0 : ((($number % 10 >= 2) && ($number % 10 <= 4) && (($number % 100 < 10) || ($number % 100 >= 20))) ? 1 : 2);
            case 'cs':
            case 'sk':
                return ($number == 1) ? 0 : ((($number >= 2) && ($number <= 4)) ? 1 : 2);
            case 'ga':
                return ($number == 1) ? 0 : (($number == 2) ? 1 : 2);
            case 'lt':
                return (($number % 10 == 1) && ($number % 100 != 11)) ? 0 : ((($number % 10 >= 2) && (($number % 100 < 10) || ($number % 100 >= 20))) ? 1 : 2);
            case 'sl':
                return ($number % 100 == 1) ? 0 : (($number % 100 == 2) ? 1 : ((($number % 100 == 3) || ($number % 100 == 4)) ? 2 : 3));
            case 'mk':
                return ($number % 10 == 1) ? 0 : 1;
            case 'mt':
                return ($number == 1) ? 0 : ((($number == 0) || (($number % 100 > 1) && ($number % 100 < 11))) ? 1 : ((($number % 100 > 10) && ($number % 100 < 20)) ? 2 : 3));
            case 'lv':
                return ($number == 0) ? 0 : ((($number % 10 == 1) && ($number % 100 != 11)) ? 1 : 2);
            case 'pl':
                return ($number == 1) ? 0 : ((($number % 10 >= 2) && ($number % 10 <= 4) && (($number % 100 < 12) || ($number % 100 > 14))) ? 1 : 2);
            case 'cy':
                return ($number == 1) ? 0 : (($number == 2) ? 1 : ((($number == 8) || ($number == 11)) ? 2 : 3));
            case 'ro':
                return ($number == 1) ? 0 : ((($number == 0) || (($number % 100 > 0) && ($number % 100 < 20))) ? 1 : 2);
            case 'ar':
                return ($number == 0) ? 0 : (($number == 1) ? 1 : (($number == 2) ? 2 : ((($number % 100 >= 3) && ($number % 100 <= 10)) ? 3 : ((($number % 100 >= 11) && ($number % 100 <= 99)) ? 4 : 5))));
            default:
                return 0;
        }
    }
}
