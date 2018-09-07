<?php declare(strict_types=1);

namespace Acelot\Struct\Tests\Unit;

use Acelot\Struct\Schema;
use Acelot\Struct\Schema\Prop;
use PHPUnit\Framework\TestCase;

class SchemaTest extends TestCase
{
    public function testNoProps()
    {
        $this->expectException(\InvalidArgumentException::class);
        new Schema();
    }

    public function testProps()
    {
        $schema = new Schema(
            Prop::create('login'),
            Prop::create('password')
        );

        $this->assertCount(2, $schema->getProps());
        $this->assertEquals([
            'login' => Prop::create('login'),
            'password' => Prop::create('password')
        ], $schema->getProps());
    }

    public function testHas()
    {
        $schema = new Schema(
            Prop::create('login'),
            Prop::create('password')
        );

        $this->assertTrue($schema->hasProp('login'));
        $this->assertTrue($schema->hasProp('password'));
        $this->assertFalse($schema->hasProp('name'));
    }

    public function testGet()
    {
        $schema = new Schema(
            Prop::create('login'),
            Prop::create('password')
        );

        $this->assertEquals(Prop::create('login'), $schema->getProp('login'));
        $this->assertEquals(Prop::create('password'), $schema->getProp('password'));

        $this->expectException(\OutOfBoundsException::class);
        $schema->getProp('name');
    }

    public function testWith()
    {
        $schema = new Schema(Prop::create('login'));
        $this->assertCount(1, $schema->getProps());

        $schema = $schema->withProp(Prop::create('password'));
        $this->assertCount(2, $schema->getProps());
        $this->assertEquals(Prop::create('password'), $schema->getProp('password'));

        $schema = $schema->withProp(
            Prop::create('name'),
            Prop::create('birthday')
        );

        $this->assertCount(4, $schema->getProps());
        $this->assertEquals(Prop::create('name'), $schema->getProp('name'));
        $this->assertEquals(Prop::create('birthday'), $schema->getProp('birthday'));

        $this->expectException(\InvalidArgumentException::class);
        $schema->withProp();
    }

    public function testWithout()
    {
        $schema = new Schema(
            Prop::create('login'),
            Prop::create('password'),
            Prop::create('name'),
            Prop::create('birthday')
        );
        $this->assertCount(4, $schema->getProps());

        $schema = $schema->withoutProp('name');
        $this->assertCount(3, $schema->getProps());
        $this->assertArrayNotHasKey('name', $schema->getProps());

        $schema = $schema->withoutProp('undefined');
        $this->assertCount(3, $schema->getProps());
    }
}
