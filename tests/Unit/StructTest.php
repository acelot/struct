<?php declare(strict_types=1);

namespace Acelot\Struct\Tests\Unit;

use Acelot\Struct\Exception\UndefinedPropertyException;
use Acelot\Struct\Exception\ValidationException;
use Acelot\Struct\Schema;
use Acelot\Struct\Tests\Fixture\CreateUserModel;
use PHPUnit\Framework\Error\Notice;
use PHPUnit\Framework\TestCase;

class StructTest extends TestCase
{
    public function testConstructWithoutData()
    {
        $this->expectException(ValidationException::class);
        $model = new CreateUserModel([]);
    }

    public function testConstructAndOtherMethods()
    {
        $model = new CreateUserModel([
            'login' => 'superhacker',
            'password' => 'correcthorsebatterystaple',
            'birthday' => new \DateTimeImmutable('1988-08-08')
        ]);

        $this->assertEquals(3, count($model));

        $this->assertEquals([
            'login' => 'superhacker',
            'password' => 'correcthorsebatterystaple',
            'birthday' => new \DateTimeImmutable('1988-08-08')
        ], $model->toArray());

        $this->assertTrue(property_exists($model, 'login'));
        $this->assertTrue($model->has('login'));
        $this->assertEquals('superhacker', $model->login);
        $this->assertEquals('superhacker', $model->get('login'));

        $this->assertTrue(property_exists($model, 'password'));
        $this->assertTrue($model->has('password'));
        $this->assertEquals('correcthorsebatterystaple', $model->password);
        $this->assertEquals('correcthorsebatterystaple', $model->get('password'));

        $this->assertTrue(property_exists($model, 'birthday'));
        $this->assertTrue($model->has('birthday'));
        $this->assertEquals(new \DateTimeImmutable('1988-08-08'), $model->birthday);
        $this->assertEquals(new \DateTimeImmutable('1988-08-08'), $model->get('birthday'));

        $this->assertFalse(property_exists($model, 'name'));
        $this->assertFalse($model->has('name'));
        $this->assertEquals('John Doe', $model->get('name', 'John Doe'));

        $this->expectException(Notice::class);
        $name = $model->name;
    }

    public function testGetSchema()
    {
        $model = new CreateUserModel([
            'login' => 'superhacker',
            'password' => 'correcthorsebatterystaple'
        ]);

        $schema = $model::getSchema();

        $this->assertInstanceOf(Schema::class, $schema);

        $this->assertTrue($schema->has('login'));
        $this->assertTrue($schema->has('password'));
        $this->assertTrue($schema->has('birthday'));
        $this->assertTrue($schema->has('name'));
    }

    public function testMapFrom()
    {
        $model = CreateUserModel::mapFrom([
            'login' => 'superhacker',
            'password' => 'correcthorsebatterystaple',
            'birthday' => '1988-08-08'
        ], 'json');

        $this->assertEquals('superhacker', $model->login);
        $this->assertEquals('correcthorsebatterystaple', $model->password);
        $this->assertEquals(new \DateTimeImmutable('1988-08-08'), $model->birthday);
        $this->assertEquals('John Doe', $model->name);
    }

    public function testIterator()
    {
        $model = new CreateUserModel([
            'login' => 'superhacker',
            'password' => 'correcthorsebatterystaple',
            'birthday' => new \DateTimeImmutable('1988-08-08')
        ]);

        $this->assertTrue(is_iterable($model));

        foreach ($model as $key => $value) {
            $this->assertEquals($model->get($key), $value);
        }
    }

    public function testSetAndDelete()
    {
        $model = new CreateUserModel([
            'login' => 'superhacker',
            'password' => 'correcthorsebatterystaple',
            'birthday' => new \DateTimeImmutable('1988-08-08')
        ]);

        $this->assertCount(3, $model);

        $model = $model->set('name', 'Judy Doe');
        $this->assertCount(4, $model);
        $this->assertTrue($model->has('name'));
        $this->assertEquals('Judy Doe', $model->name);

        $model = $model->delete('birthday');
        $this->assertCount(3, $model);
        $this->assertFalse($model->has('birthday'));

        $this->expectException(ValidationException::class);
        $model = $model->set('gender', 'male');
    }

    public function testJsonSerialize()
    {
        $model = new CreateUserModel([
            'login' => 'superhacker',
            'password' => 'correcthorsebatterystaple',
            'birthday' => new \DateTimeImmutable('1988-08-08', new \DateTimeZone('UTC'))
        ]);

        $json = json_encode($model);
        $this->assertEquals('{"login":"superhacker","password":"correcthorsebatterystaple","birthday":"1988-08-08T00:00:00.000+00:00"}',
            $json);
    }
}
