<?php

namespace Handlebars;

class CompilerTest extends \PHPUnit_Framework_TestCase {
    public function setUp() {
        $this->engine = new Engine();

        $this->engine->registerHelper('fullName', function($person) {
            return $person['firstName'].' '.$person['lastName'];
        });

        $this->options = array();
    }

    public function getCompiler($input, $options = NULL) {
        if (is_null($options)) {
            $options = $this->options;
        }
        $lexer = new Lexer();
        $parser = new Parser();

        $tokens = $lexer->tokenize($input);
        $ast = $parser->parse($tokens);

        // var_dump($ast);

        $compiler = new Compiler($this->engine, $ast, $options);
        return $compiler;
    }

    // public function testStaticInput() {
    //     $input = "
    //         Hello world
    //     ";
    //     $output = $this->getCompiler($input)->compile();
    //     $this->assertEquals(trim($output), 'Hello world');
    // }

    // public function testSimpleExpression() {
    //     $input = "
    //         Hello {{ name }}
    //     ";
    //     $context = array(
    //         'name' => 'Jafar Shadiq'
    //     );
    //     $output = $this->getCompiler($input)->compile($context);
    //     $this->assertEquals(trim($output), 'Hello Jafar Shadiq');

    //     $input = "
    //         Hello {{ user.name }}
    //     ";
    //     $context = array(
    //         'user' => array(
    //             'name' => 'Jafar Shadiq'
    //         ),
    //     );
    //     $output = $this->getCompiler($input)->compile($context);
    //     $this->assertEquals(trim($output), 'Hello Jafar Shadiq');

    //     $input = "
    //         Hello {{ this.user.name }}
    //     ";
    //     $context = array(
    //         'user' => array(
    //             'name' => 'Jafar Shadiq'
    //         ),
    //     );
    //     $output = $this->getCompiler($input)->compile($context);
    //     $this->assertEquals(trim($output), 'Hello Jafar Shadiq');
    // }

    public function testExpressionWithParameter() {
        $input = "
            Hello {{ fullName user }}
        ";
        $context = array(
            'user' => array(
                'firstName' => 'Jafar',
                'lastName' => 'Shadiq',
            ),
        );
        $output = $this->getCompiler($input)->compile($context);
        $this->assertEquals(trim($output), 'Hello Jafar Shadiq');
    }

}