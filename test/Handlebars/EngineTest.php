<?php

namespace Handlebars;

class EngineTest extends \PHPUnit_Framework_TestCase {
    public function setUp() {
        $this->engine = Handlebars::getEngine();
    }

    public function testCompileNull() {
        $exception = NULL;
        try {
            $this->engine->compile(NULL);
        } catch(\Exception $e) {
            $exception = $e;
        }

        $this->assertNotNull($exception, "Should catch exception on compiling NULL");
    }

    public function testCompileStaticString() {
        $fn = $this->engine->compile("Hello World");

        $this->assertTrue(is_callable($fn));

        $result = $fn();

        $this->assertEquals($result, "Hello World");
    }

}