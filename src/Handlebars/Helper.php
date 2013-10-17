<?php

namespace Handlebars;

class Helper {
    private $name;
    private $callback;
    private $not;
    public function __construct($name, $callback, $not = false) {
        $this->name = $name;
        $this->callback = $callback;
        $this->not = $not;
    }

    public function call($args = array()) {
        $count = count($args);
        $callback = $this->callback;
        switch($count) {
            case 0:
                return $callback();
            case 1:
                return $callback($args[0]);
            case 2:
                return $callback($args[0], $args[1]);
            case 3:
                return $callback($args[0], $args[1], $args[2]);
            case 4:
                return $callback($args[0], $args[1], $args[2], $args[3]);
            case 5:
                return $callback($args[0], $args[1], $args[2], $args[3], $args[4]);

        }
    }
}