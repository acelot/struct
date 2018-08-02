<?php declare(strict_types=1);

namespace Acelot\Struct\Tests\Fixture;

use Acelot\Struct\Schema;
use Acelot\Struct\Schema\Prop;
use Acelot\Struct\Struct;

use function Acelot\AutoMapper\from;

use Respect\Validation\Rules\{
    AllOf, StringType, Alnum, NoWhitespace, Length, Instance
};

/**
 * @property-read string             $login
 * @property-read string             $password
 * @property-read string             $name
 * @property-read \DateTimeInterface $birthday
 */
class CreateUserModel extends Struct
{
    public static function getSchema(): Schema
    {
        return new Schema(
            Prop::create('login')
                ->withValidator(new AllOf(
                    new StringType(),
                    new Alnum(),
                    new NoWhitespace(),
                    new Length(0, 64)
                )),

            Prop::create('password')
                ->withValidator(new AllOf(
                    new StringType(),
                    new Length(0, 256)
                )),

            Prop::create('name')
                ->withValidator(new AllOf(
                    new StringType(),
                    new Length(0, 256)
                ))
                ->withMapper(from('name')->trim()->default('John Doe'), 'json')
                ->notRequired(),

            Prop::create('birthday')
                ->withValidator(new Instance(\DateTimeInterface::class))
                ->withMapper(from('birthday')->convert(function ($value) {
                    return new \DateTimeImmutable($value);
                }), 'json')
                ->notRequired()
        );
    }
}
