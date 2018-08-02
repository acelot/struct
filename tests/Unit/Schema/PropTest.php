<?php declare(strict_types=1);

namespace Acelot\Struct\Tests\Unit\Schema;

use function Acelot\AutoMapper\from;
use function Acelot\AutoMapper\ignore;
use Acelot\Struct\Schema\Prop;
use PHPUnit\Framework\TestCase;
use Respect\Validation\Rules\AlwaysValid;
use Respect\Validation\Rules\IntType;

class PropTest extends TestCase
{
    public function testName()
    {
        $prop = Prop::create('test');
        $this->assertEquals('test', $prop->getName());
    }

    public function testValidator()
    {
        $prop = Prop::create('test');
        $this->assertEquals(new AlwaysValid(), $prop->getValidator());

        $prop = $prop->withValidator(new IntType());
        $this->assertEquals(new IntType(), $prop->getValidator());
    }

    public function testMapper()
    {
        $prop = Prop::create('test');
        $this->assertEquals(from('test'), $prop->getMapper('default'));

        $prop = $prop->withMapper(ignore(), 'test');
        $this->assertTrue($prop->hasMapper('test'));
        $this->assertEquals(ignore(), $prop->getMapper('test'));

        $prop = $prop->withoutMapper('test');
        $this->assertFalse($prop->hasMapper('test'));

        $this->expectException(\InvalidArgumentException::class);
        $prop = $prop->withoutMapper('default');
    }

    public function testRequired()
    {
        $prop = Prop::create('test');
        $this->assertTrue($prop->isRequired());

        $prop = $prop->notRequired();
        $this->assertFalse($prop->isRequired());

        $prop = $prop->required();
        $this->assertTrue($prop->isRequired());
    }

    public function testMeta()
    {
        $prop = Prop::create('test');
        $this->assertFalse($prop->hasMeta('some_meta'));

        $prop = $prop->withMeta('some_meta', 'Meta Value');
        $this->assertTrue($prop->hasMeta('some_meta'));
        $this->assertEquals('Meta Value', $prop->getMeta('some_meta'));

        $prop = $prop->withoutMeta('some_meta');
        $this->assertFalse($prop->hasMeta('some_meta'));

        $this->assertEquals('Default Value', $prop->getMeta('undefined_meta', 'Default Value'));
    }
}
