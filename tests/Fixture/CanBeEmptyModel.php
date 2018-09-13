<?php declare(strict_types=1);

namespace Acelot\Struct\Tests\Fixture;

use Acelot\Struct\Schema;
use Acelot\Struct\Schema\Prop;
use Acelot\Struct\Struct;

/**
 * @property-read mixed $a
 * @property-read mixed $b
 * @property-read mixed $c
 */
class CanBeEmptyModel extends Struct
{
    public static function getSchema(): Schema
    {
        return new Schema(
            Prop::create('a')
                ->notRequired(),

            Prop::create('b')
                ->notRequired(),

            Prop::create('c')
                ->notRequired()
        );
    }
}
