<?php declare(strict_types=1);

namespace Acelot\Struct\Tests\Unit\Schema;

use Acelot\Struct\Schema\Prop;
use PHPUnit\Framework\TestCase;

class PropTest extends TestCase
{
    public function testCreate()
    {
        $prop = Prop::create('test');
        $this->assertEquals('test', $prop->getName());
    }
}
