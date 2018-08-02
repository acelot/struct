<?php declare(strict_types=1);

namespace Acelot\Struct\Tests\Fixture;

use Acelot\Struct\Schema;
use Acelot\Struct\Schema\Prop;
use Acelot\Struct\Struct;

class Model extends Struct
{
    public static function getSchema(): Schema
    {
        return new Schema(
            Prop::create('id')
        );
    }
}
