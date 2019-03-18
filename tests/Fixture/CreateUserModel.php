<?php declare(strict_types=1);

namespace Acelot\Struct\Tests\Fixture;

use Acelot\Struct\Exception\ExcludePropertyException;
use Acelot\Struct\Schema;
use Acelot\Struct\Schema\Prop;
use Acelot\Struct\Struct;

use function Acelot\AutoMapper\from;

use Respect\Validation\Rules\AllOf;
use Respect\Validation\Rules\BoolType;
use Respect\Validation\Rules\StringType;
use Respect\Validation\Rules\Alnum;
use Respect\Validation\Rules\NoWhitespace;
use Respect\Validation\Rules\Length;
use Respect\Validation\Rules\Instance;

/**
 * @property-read string             $login
 * @property-read string             $password
 * @property-read string             $name
 * @property-read \DateTimeInterface $birthday
 * @property-read bool               $isActive
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
                ->notRequired(),

            Prop::create('isActive')
                ->withValidator(new BoolType())
                ->defaultValue(true)
        );
    }

    protected static function jsonSerializeValue($value, Prop $prop)
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format(\DateTime::RFC3339_EXTENDED);
        }

        if ($prop->getName() === 'password') {
            throw new ExcludePropertyException();
        }

        return $value;
    }
}
