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
 * Config class
 */
class Config
{
    /**
     * @var array
     */
    static public $runtime = [];

    /**
     * @var array
     */
    static public $config = [];

    /**
     * @param  string $key
     * @param  mixed  $value
     * @return mixed
     */
    static public function setRuntime($key, $value)
    {
        self::$runtime[(string)$key] = $value;

        return $value;
    }

    /**
     * @param  string $key
     * @return mixed
     */
    static public function getRuntime($key)
    {
        if (!array_key_exists($key, self::$runtime)) {
            throw new Exception('Invalid runtime config key provided: ' . $key);
        }

        return self::$runtime[$key];
    }

    /**
     * @param  string $key
     * @param  mixed  $value
     * @return mixed
     */
    static public function set($key, $value)
    {
        self::$config[(string)$key] = $value;

        return $value;
    }

    /**
     * @param  string $key
     * @return mixed
     */
    static public function get($key)
    {
        if (!array_key_exists($key, self::$config)) {
            throw new Exception('Invalid runtime config key provided');
        }

        return self::$config[$key];
    }
}

