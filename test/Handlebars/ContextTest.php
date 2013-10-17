<?php

namespace Handlebars;

class ContextTest extends \PHPUnit_Framework_TestCase {
    public function setUp() {
        $arr = array(
            'username' => 'reekoheek',
            'first_name' => 'Jafar',
            'last_name' => 'Shadiq',
            'staffs' => array(
                array(
                    'first_name' => 'Abdul',
                    'last_name' => 'Rasman',
                ),
                array(
                    'first_name' => 'Farid',
                    'last_name' => 'Hidayat',
                ),
            ),
            'address' => array(
                'street' => 'Jalan Cilandak Tengah',
                'province' => 'DKI Jakarta',
            ),
        );
        $this->context = new Context($arr);
    }

    public function testGet() {
        $value = $this->context->get('first_name', true);
        $this->assertEquals($value, "Jafar");

        $value = $this->context->get('address.street', true);
        $this->assertEquals($value, "Jalan Cilandak Tengah");

        $value = $this->context->get('staffs.[1].last_name', true);
        $this->assertEquals($value, "Hidayat");
    }

}