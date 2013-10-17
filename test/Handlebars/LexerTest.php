<?php

namespace Handlebars;

class LexerTest extends \PHPUnit_Framework_TestCase {
    public function setUp() {
        $this->lexer = new Lexer();
    }

    public function testStaticInput() {
        $input = "
            Hello world
        ";
        $output = $this->lexer->tokenize($input);
        $this->assertEquals($output[0]['value'], "\n");
        $this->assertEquals($output[0]['type'], "_t");

        $this->assertEquals(trim($output[1]['value']), "Hello world");
        $this->assertEquals($output[1]['type'], "_t");
    }

    public function testSimpleExpression() {
        $input = "Hello {{ name }}";
        $output = $this->lexer->tokenize($input);

        $this->assertEquals($output[1]['type'], '_v');
        $this->assertEquals($output[1]['name'], 'name');

        $input = "Hello {{{ name }}}";
        $output = $this->lexer->tokenize($input);

        $this->assertEquals($output[1]['type'], '{');
        $this->assertEquals($output[1]['name'], 'name');
    }

    public function testExpressionWithParameter() {
        $input = "Hello {{ childOf father mother }}";
        $output = $this->lexer->tokenize($input);

        $this->assertEquals($output[0]['type'], '_t');
        $this->assertEquals(trim($output[0]['value']), 'Hello');

        $this->assertEquals($output[1]['type'], '_v');
        $this->assertEquals(trim($output[1]['name']), 'childOf');
        $this->assertEquals(trim($output[1]['args']), 'father mother');
    }

    public function testComment() {
        $input = "Hello {{! This should be commented }} {{ name }}";
        $output = $this->lexer->tokenize($input);

        $this->assertEquals($output[1]['type'], '!');
        $this->assertEquals(trim($output[1]['name']), 'This should be commented');
    }

    public function testBlock() {
        $input = 'Embedded block {{#noop}}{{body}}{{/noop}} for body';
        $output = $this->lexer->tokenize($input);

        $this->assertEquals($output[1]['type'], '#');
        $this->assertEquals($output[2]['type'], '_v');
        $this->assertEquals($output[2]['name'], 'body');
        $this->assertEquals($output[3]['type'], '/');
    }

}