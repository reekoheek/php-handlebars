<?php

namespace Handlebars;

class ParserTest extends \PHPUnit_Framework_TestCase {
    public function parse($input) {
        $lexer = new Lexer();
        $parser = new Parser();

        $output = $lexer->tokenize($input);

        return $parser->parse($output);
    }

    public function testStaticInput() {
        $input = "
            Hello world
        ";
        $output = $this->parse($input);

        $this->assertEquals($output[0]['value'], "\n");
        $this->assertEquals($output[0]['type'], "_t");

        $this->assertEquals(trim($output[1]['value']), "Hello world");
        $this->assertEquals($output[1]['type'], "_t");
    }

    public function testBlock() {
        $input = 'Embedded block {{#noop}}{{body}}{{/noop}} for body';
        $output = $this->parse($input);

        $this->assertEquals($output[1]['nodes'][0]['name'], 'body');
    }

}