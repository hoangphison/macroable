<?php

namespace Spatie\Macroable\Test;

use BadMethodCallException;
use PHPUnit\Framework\TestCase;
use Spatie\Macroable\Macroable;

class AnonymousClass1
{
    private $privateVariable = 'privateValue';

    use Macroable;

    private static function getPrivateStatic()
    {
        return 'privateStaticValue';
    }
}

class AnonymousClass2
{
    public function __invoke()
    {
        return 'newValue';
    }
}

class AnonymousClass3
{
    private function secretMixinMethod()
    {
        return 'secret';
    }

    public function mixinMethodA()
    {
        return function ($value) {
            return $this->mixinMethodB($value);
        };
    }

    public function mixinMethodB()
    {
        return function ($value) {
            return $this->privateVariable.'-'.$value;
        };
    }
}

class MacroableTest extends TestCase
{
    protected $macroableClass;

    public function setUp()
    {
        parent::setUp();
    }

    /** @test */
    public function a_new_macro_can_be_registered_and_called()
    {
        AnonymousClass1::macro('newMethod', function () {
            return 'newValue';
        });

        $this->assertEquals('newValue', (new AnonymousClass1)->newMethod());
    }

    /** @test */
    public function a_new_macro_can_be_registered_and_called_statically()
    {
        AnonymousClass1::macro('newMethod', function () {
            return 'newValue';
        });

        $this->assertEquals('newValue', AnonymousClass1::newMethod());
    }

    /** @test */
    public function a_class_can_be_registered_as_a_new_macro_and_be_invoked()
    {
        AnonymousClass1::macro('newMethod', new AnonymousClass2);

        $this->assertEquals('newValue', (new AnonymousClass1)->newMethod());
        $this->assertEquals('newValue', AnonymousClass1::newMethod());
    }

    /** @test */
    public function it_passes_parameters_correctly()
    {
        AnonymousClass1::macro('concatenate', function (...$strings) {
            return implode('-', $strings);
        });

        $this->assertEquals('one-two-three', (new AnonymousClass1)->concatenate('one', 'two', 'three'));
    }

    /** @test */
    public function registered_methods_are_bound_to_the_class()
    {
        AnonymousClass1::macro('newMethod', function () {
            return $this->privateVariable;
        });

        $this->assertEquals('privateValue', (new AnonymousClass1)->newMethod());
    }

    /** @test */
    public function it_can_work_on_static_methods()
    {
        AnonymousClass1::macro('testStatic', function () {
            return $this::getPrivateStatic();
        });

        $this->assertEquals('privateStaticValue', (new AnonymousClass1)->testStatic());
    }

    /** @test */
    public function it_can_mixin_all_public_methods_from_another_class()
    {
        AnonymousClass1::mixin(new AnonymousClass3);

        $this->assertEquals('privateValue-test', (new AnonymousClass1)->mixinMethodA('test'));
    }

    /** @test */
    public function it_will_throw_an_exception_if_a_method_does_not_exist()
    {
        $this->expectException(BadMethodCallException::class);

        (new AnonymousClass1)->nonExistingMethod();
    }

    /** @test */
    public function it_will_throw_an_exception_if_a_static_method_does_not_exist()
    {
        $this->expectException(BadMethodCallException::class);

        AnonymousClass1::nonExistingMethod();
    }
}
