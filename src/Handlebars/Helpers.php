<?php

namespace Handlebars;

use Handlebars\Exception as HandlebarsException;

class Helpers {
    private $registries = array();

    public function register($name, $fn = NULL, $inverse = false) {
        if (is_string($name)) {
            if ($fn instanceof Helper) {
                $this->registries[$name] = $fn;
            } else {
                $this->registries[$name] = new Helper($name, $fn, $inverse);
            }
        } elseif (is_array($name)) {
            $helpers = $name;
            foreach($helpers as $name => $helper) {
                if ($helper instanceof Helper) {
                    $this->register($name, $helper);
                } elseif (is_array($helper) && isset($helper['callback']) && is_callable($helper['callback'])) {
                    $this->register($name, $helper['callback'], $helper['not']);
                } else {
                    $this->register($name, $helper, false);
                }
            }
        } else {
            throw new HandlebarsException('Cannot register helper name: '.json_encode($name));
        }
    }

    public function has($name) {
        if (isset($this->registries[$name])) {
            return true;
        } else {
            return false;
        }
    }

    public function get($name) {
        if ($this->has($name)) {
            return $this->registries[$name];
        }
        return NULL;
    }
}