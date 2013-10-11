<?php

namespace Handlebars;

class Handlebars {
    public static $engine;

    public static function init() {
        self::$engine = null;
        return self::getEngine(true);
    }

    public static function getEngine($singleton = false) {
        if (!$singleton) {
            return new Engine();
        }

        if (is_null(self::$engine)) {
            self::$engine = new Engine();
        }

        return self::$engine;
    }

    public static function __callStatic($method, $arguments) {
        return call_user_func_array(array(self::getEngine(true), $method), $arguments);
    }
}