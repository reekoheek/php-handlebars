<?php

namespace Handlebars;

/*
 * This file is part of php-handlebars.
 *
 * (c) 2013 Jafar Shadiq
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Handlebars\Exception as HandlebarsException;

class Engine {

    private $_escapeArgs = array ( ENT_COMPAT, 'UTF-8' );
    private $_escape = 'htmlspecialchars';

    protected $helpers = array();
    protected $lexer;
    protected $parser;

    private function getLexer() {
        if (!$this->lexer) {
            $this->lexer = new Lexer();
        }
        return $this->lexer;
    }

    private function getParser() {
        if (!$this->parser) {
            $this->parser = new Parser();
        }
        return $this->parser;
    }

    public function compile($input, $options = array()) {
        if (!is_string($input)) {
            throw new HandlebarsException('Cannot compile template: ' . json_encode($input));
        }

        $tokens = $this->getLexer()->tokenize($input);
        $ast = $this->getParser()->parse($tokens);

        $compiler = new Compiler($this, $ast, $options);

        return function($contextObject = array(), $options = array()) use ($compiler) {
            return $compiler->compile($contextObject);
        };
    }

    public function registerHelper($name, $fn = NULL, $inverse = false) {
        if (is_string($name)) {
            $this->helpers[$name] = array(
                'not' => $inverse,
                'callback' => $fn,
            );
        } elseif (is_array($name)) {
            $helpers = $name;
            foreach($helpers as $name => $helper) {
                if (is_array($helper) && isset($helper['callback']) && is_callable($helper['callback'])) {
                    $this->helpers[$name] = $helper;
                } else {
                    $this->helpers[$name] = array(
                        'not' => false,
                        'callback' => $helper,
                    );
                }
            }
        } else {
            throw new HandlebarsException('Cannot register helper name: '.json_encode($name));
        }
    }

    public function getHelpers()
    {
        return $this->helpers;
    }

    public function getEscapeArgs()
    {
        return $this->_escapeArgs;
    }

    public function getEscape()
    {
        return $this->_escape;
    }
}