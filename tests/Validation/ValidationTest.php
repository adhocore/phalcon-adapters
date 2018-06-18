<?php

namespace PhalconExt\Test\Validation;

use PhalconExt\Test\TestCase;
use PhalconExt\Validation\Validation;

class ValidationTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->validation = new Validation;
    }

    public function test_str_rules()
    {
        $rules = ['apple' => 'if_exist|length:min:3;max:5'];
        $vldtr = $this->validation->run($rules, []);

        $this->assertTrue($vldtr->pass());
        $this->assertFalse($vldtr->fail());
        $this->assertSame([], $vldtr->getErrorMessages());

        $vldtr = $this->validation->run($rules, ['apple' => 'A']);

        $this->assertFalse($vldtr->pass());
        $this->assertTrue($vldtr->fail());
        $this->assertSame([
            'code'    => 0,
            'message' => 'Field apple must be at least 3 characters long',
            'field'   => 'apple',
        ], $vldtr->getErrorMessages()[0]);
    }

    public function test_array_rules()
    {
        $rules = ['ball' => ['required' => true, 'length' => ['min' => 3, 'max' => 5]]];
        $vldtr = $this->validation->run($rules, ['ball' => 'ABCDEF']);

        $this->assertSame('Field ball must not exceed 5 characters long', $vldtr->getErrorMessages()[0]['message']);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown validation rule: asdf');

        $this->validation->run(['a' => 'asdf'], []);
    }

    public function test_invalid_rules()
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('The rules should be an array or string');

        $rules = ['gender' => 'in:domain:m,f', 'cat' => 1];
        $vldtr = $this->validation->run($rules, ['cat' => null]);
    }

    public function test_custom_rules()
    {
        $this->validation->registerRules([
            'step' => function ($data) {
                return $this->getCurrentValue() % $this->getOption('of', 5) === 0;
            },
        ], [
            'step' => 'Field :field must be in step of 5',
        ]);

        $this->validation->register('step', function () {
            return true;
        }, 'This is never registerd as rule `step` is already there');

        $rules = ['batch' => 'required|step'];
        $vldtr = $this->validation->run($rules, ['batch' => 11]);

        $this->assertFalse($vldtr->pass());
        $this->assertSame('Field batch must be in step of 5', $vldtr->getErrorMessages()[0]['message']);

        $vldtr = $this->validation->run($rules, ['batch' => 10]);

        $this->assertTrue($vldtr->pass());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported validation rule: abc');

        $this->validation->register('abc', 'invalid rule');
    }
}
