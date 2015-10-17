#!/usr/bin/env php
<?php

/**
 * Serferals
 * 
 * Episode/Series filename re-namer and information lookup script
 *
 * @author    Rob M Frawley 2nd
 * @copyright 2009-2014 Inserrat Technologies, LLC
 * @license   MIT License
 */

 /**
 * Console class
 */
class Console 
{
    /**
     * @var array
     */
    static public $colors = [
        'k' => '1;30',
        'K' => '0;30',
        'r' => '1;31',
        'R' => '0;31',
        'g' => '1;32',
        'G' => '0;32',
        'y' => '1;33',
        'Y' => '0;33',
        'b' => '1;34',
        'B' => '0;34',
        'p' => '1;35',
        'P' => '0;35',
        'c' => '1;36',
        'C' => '0;36',
        'w' => '1;37',
        'W' => '0;37',
    ];

    /**
     * @var array
     */
    static public $buffer = [];

    /**
     * @param  string $out
     * @return string
     */
    static public function colorize($out = '')
    {
        $colorEnabled = Config::getRuntime('script.colors');

        if ($colorEnabled) {
            $out = str_replace('%%', "\033[0m", $out);
        } else {
            $out = str_replace('%%', '', $out);
        }

        foreach (self::$colors as $search => $replace) {
            if ($colorEnabled === false) {
                $replace = '';
            }

            $out = str_replace('%'.$search, "\033[${replace}m", $out);
        }

        $out = $out . "\033[0m";

        return $out;
    }

    /**
     * @param string  $out
     * @param integer $level
     * @param boolean $nl
     */
    static public function out($out = '', $level = 1, $nl = false)
    {
        if ($level > Config::getRuntime('script.verbosity')) {
            return;
        }

        $out = self::colorize($out);

        if ($nl === true) {
            $out = $out . "\n";
        }
        
        @fwrite(Config::getRuntime('script.stdout'), $out);
    }

    /**
     * @param string  $out
     * @param integer $level
     */
    static public function outl($out = '', $level = 1)
    {
        self::out($out, $level, true);
    }

    /**
     * @param null|string  $out
     * @param null|integer $exit
     */
    static public function error($out = null, $exit = null)
    {
        if ($out === null) {
            $out = 'Undefined';
        }

        Console::outl('%rError: %R'.$out.'...');
        Console::outl();

        if ($exit !== null) {
            Console::outl('%WExiting...');
            Console::outl();

            exit($exit);
        }
    }

    /**
     * @param  string      $out
     * @param  null|string $default
     * @return string
     */
    static public function prompt($out = '', $default = null)
    {
        if ($default !== null) {
            $out = '%w' . $out . ' %W['.$default.']: %w';
        } else {
            $out = '%w' . $out . '%W: %w';
        }
        
        self::out($out, 0);
        
        $input = trim(fgets(Config::getRuntime('script.stdin')));
        if (empty($input)) {
            $input = $default;
        }

        return $input;
    }

    /**
     * @param string  $out
     * @param integer $level
     */
    static public function buffer($out = '', $level = 1)
    {
        if ($level > Config::getRuntime('script.verbosity')) {
            return;
        }

        self::$buffer[] = $out;
    }

    /**
     * @param boolean $nl
     */
    static public function flush($nl = true, $align = true)
    {
        if ($align === true) {
            self::bufferAlign();
        }

        while (null !== ($out = array_shift(self::$buffer))) {
            self::out($out, Config::getRuntime('script.verbosity'), $nl);
        }
    }

    /**
     * align buffered strings
     */
    static public function bufferAlign()
    {
        $leftLen = 0;
        $indexes = [];

        foreach (self::$buffer as $i => $b) {

            $matches = [];
            $result  = preg_match('#\[([\.\s:]){3}\]#', $b, $matches, PREG_OFFSET_CAPTURE);

            if ($result !== 1) {
                continue;
            }

            $indexes[] = $i;

            if ($leftLen < $matches[0][1]) {
                $leftLen = $matches[0][1];
            }
        }

        foreach ($indexes as $i) {

            $b = self::$buffer[$i];

            $matches = [];
            $result  = preg_match('#\[([\.\s:]){3}\](.*?)#', $b, $matches, PREG_OFFSET_CAPTURE);
            $left    = substr($b, 0, $matches[0][1]);
            $type    = substr($b, $matches[0][1], 5);
            $right   = substr($b, $matches[2][1]);

            switch ($type) {
                case '[   ]':
                    $left = str_pad($left, $leftLen+3, ' ', STR_PAD_RIGHT);
                    break;
                case '[...]':
                    $left = str_pad($left, $leftLen+3, '.', STR_PAD_RIGHT);
                    break;
                case '[:  ]':
                    $left = $left . ' :';
                    $left = str_pad($left, $leftLen+3, ' ', STR_PAD_RIGHT);
                    break;
                case '[  :]':
                    $left = str_pad($left, $leftLen, ' ', STR_PAD_RIGHT);
                    $left = $left . ' : ';
                    break;
            }

            self::$buffer[$i] = $left . $right;
        }
    }
}