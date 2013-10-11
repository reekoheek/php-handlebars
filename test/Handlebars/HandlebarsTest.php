<?php

namespace Handlebars;

use Handlebars\Handlebars;
use Handlebars\Util;

class HandlebarsTest extends \PHPUnit_Framework_TestCase {

    public function setUp() {
        Handlebars::init();
    }

    public function testCompile() {
        $templateString = "
            Hello World
        ";

        $fn = Handlebars::compile($templateString);
        $this->assertTrue(is_callable($fn), 'Compiled template should be callable function');
        $this->assertEquals(trim($fn()), "Hello World");
    }

    public function testRegisterHelper() {
        Handlebars::registerHelper('sayMyName', function($text, $url) {
            return "reekoheek";
        });

        $helpers = Handlebars::getHelpers();

        $this->assertNotEmpty($helpers['sayMyName']);
        $this->assertTrue(is_callable($helpers['sayMyName']['callback']));
        $this->assertFalse($helpers['sayMyName']['not']);

    }

    public function testPreCompile() {
        // FIXME reekoheek: test precompile
    }
}