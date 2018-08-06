# Struct

[![Build Status](https://travis-ci.org/acelot/struct.svg?branch=master)](https://travis-ci.org/acelot/struct)
[![Code Climate](https://img.shields.io/codeclimate/coverage/acelot/struct.svg)](https://codeclimate.com/github/acelot/struct)
![](https://img.shields.io/badge/dependencies-zero-blue.svg)
![](https://img.shields.io/badge/license-MIT-green.svg)

Declarative structure builder for PHP 7.

## Usage

Create some model:
```php
<?php declare(strict_types=1);

namespace MyNamespace;

use Acelot\Struct\Struct;
use Acelot\Struct\Schema;
use Acelot\Struct\Schema\Prop;

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
    public static function getSchema() : Schema
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
```

Use model:
```php
<?php declare(strict_types=1);

namespace MyNamespace;

$json = <<<JSON
{
    "login": "superhacker",
    "password": "correcthorsebatterystaple",
    "birthday": "1988-08-08"
}
JSON;

$model = CreateUserModel::mapFrom(json_decode($json), 'json');

echo $model->login;    // "superhacker"
echo $model->password; // "correcthorsebatterystaple"
echo $model->name;     // "John Doe"

var_export($model->birthday);
// DateTime::__set_state(array(
//    'date' => '1988-08-08 00:00:00.000000',
//    'timezone_type' => 3,
//    'timezone' => 'UTC',
// ))
```
