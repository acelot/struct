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

        $this->assertTrue($schema->has('login'));
        $this->assertTrue($schema->has('password'));
        $this->assertFalse($schema->has('name'));
    }

    public function testGet()
    {
        $schema = new Schema(
            Prop::create('login'),
            Prop::create('password')
        );

        $this->assertEquals(Prop::create('login'), $schema->get('login'));
        $this->assertEquals(Prop::create('password'), $schema->get('password'));

        $this->expectException(\OutOfBoundsException::class);
        $schema->get('name');
    }

    public function testWith()
    {
        $schema = new Schema(Prop::create('login'));
        $this->assertCount(1, $schema->getProps());

        $schema = $schema->with(Prop::create('password'));
        $this->assertCount(2, $schema->getProps());
        $this->assertEquals(Prop::create('password'), $schema->get('password'));

        $schema = $schema->with(
            Prop::create('name'),
            Prop::create('birthday')
        );

        $this->assertCount(4, $schema->getProps());
        $this->assertEquals(Prop::create('name'), $schema->get('name'));
        $this->assertEquals(Prop::create('birthday'), $schema->get('birthday'));

        $this->expectException(\InvalidArgumentException::class);
        $schema->with();
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

        $schema = $schema->without('name');
        $this->assertCount(3, $schema->getProps());
        $this->assertArrayNotHasKey('name', $schema->getProps());

        $schema = $schema->without('undefined');
        $this->assertCount(3, $schema->getProps());
    }
}
