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

    protected $helpers;

    public function __construct() {
        $this->helpers = new Helpers();
    }

    public function compile($input, $options = array()) {
        if (!is_string($input)) {
            throw new HandlebarsException('Cannot compile template: ' . json_encode($input));
        }

        $lexer = new Lexer();
        $parser = new Parser();
        $tokens = $lexer->tokenize($input);
        $ast = $parser->parse($tokens);

        $compiler = new Compiler($this, $ast, $options);

        return function($contextObject = array(), $options = array()) use ($compiler) {
            return $compiler->compile($contextObject);
        };
    }

    public function registerHelper($name, $fn = NULL, $inverse = false) {
        $this->helpers->register($name, $fn, $inverse);
    }

    public function getHelpers() {
        return $this->helpers;
    }

    public function getEscapeArgs() {
        return $this->_escapeArgs;
    }

    public function getEscape() {
        return $this->_escape;
    }
}