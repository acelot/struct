<?php declare(strict_types=1);

namespace Acelot\Struct;

use Acelot\AutoMapper\AutoMapper;
use Acelot\AutoMapper\Field;
use Acelot\Struct\Exception\UndefinedPropertyException;
use Acelot\Struct\Exception\ValidationException;
use Acelot\Struct\Schema\Prop;
use Respect\Validation\Exceptions\AttributeException;
use Respect\Validation\Exceptions\NestedValidationException;
use Respect\Validation\Rules\AllOf;
use Respect\Validation\Rules\Key as ValidationKey;

abstract class Struct implements \Iterator, \Countable, \JsonSerializable
{
    /**
     * @var array
     */
    protected $data = [];

    /**
     * @return Schema
     */
    abstract public static function getSchema(): Schema;

    /**
     * @param array|object $data
     * @param string       $sourceName
     *
     * @return static
     * @throws ValidationException
     */
    public static function mapFrom($data, string $sourceName)
    {
        $fields = array_map(
            function (Prop $prop) use ($sourceName) {
                $mapper = $prop->hasMapper($sourceName)
                    ? $prop->getMapper($sourceName)
                    : $prop->getMapper('default');

                return Field::create($prop->getName(), $mapper);
            },
            static::getSchema()->getProps()
        );

        $mapper = AutoMapper::create(...array_values($fields))->ignoreAllMissing();

        return new static($mapper->marshalArray($data));
    }

    /**
     * @param array $data
     *
     * @throws ValidationException
     */
    public function __construct(array $data)
    {
        $this->data = $this->validate($data);
    }

    /**
     * @param $name
     *
     * @return mixed
     * @throws UndefinedPropertyException
     */
    public function __get(string $name)
    {
        if (!$this->has($name)) {
            throw new UndefinedPropertyException();
        }

        return $this->get($name);
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function __isset(string $name): bool
    {
        return $this->has($name);
    }

    /**
     * @return mixed
     */
    public function current()
    {
        return current($this->data);
    }

    /**
     * @return mixed
     */
    public function next()
    {
        return next($this->data);
    }

    /**
     * @return string|null
     */
    public function key()
    {
        return key($this->data);
    }

    /**
     * @return bool
     */
    public function valid()
    {
        return key($this->data) !== null;
    }

    public function rewind()
    {
        reset($this->data);
    }

    /**
     * @return int
     */
    public function count()
    {
        return count($this->data);
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    /**
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        if (!$this->has($key)) {
            return $default;
        }

        return $this->data[$key];
    }

    /**
     * @param string $key
     * @param mixed  $value
     *
     * @return static
     * @throws ValidationException
     */
    public function set(string $key, $value)
    {
        $temp = $this->data;
        $temp[$key] = $value;
        return new static($temp);
    }

    /**
     * @param string $key
     *
     * @return static
     * @throws ValidationException
     */
    public function delete(string $key)
    {
        $temp = $this->data;
        unset($temp[$key]);
        return new static($temp);
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        $data = [];
        foreach (static::getSchema()->getProps() as $prop) {
            if (!$this->has($prop->getName())) {
                continue;
            }

            $value = $this->get($prop->getName());
            $data[$prop->getName()] = static::jsonSerializeValue($value);
        }

        return $data;
    }

    /**
     * @param mixed $value
     *
     * @return mixed
     * @codeCoverageIgnore
     */
    protected static function jsonSerializeValue($value)
    {
        return $value;
    }

    /**
     * @param array $newData
     *
     * @return array
     * @throws ValidationException
     */
    protected function validate(array $newData): array
    {
        $props = static::getSchema()->getProps();

        $extraKeys = array_keys(array_diff_key($newData, $props));
        if (!empty($extraKeys)) {
            throw new ValidationException(array_map(function ($extraKey) {
                return [$extraKey => 'Неизвестный ключ'];
            }, $extraKeys));
        }

        $rules = array_map(function (Prop $prop) {
            return new ValidationKey($prop->getName(), $prop->getValidator(), $prop->isRequired());
        }, $props);

        $validator = new AllOf(...array_values($rules));

        try {
            $validator->assert($newData);
        } catch (NestedValidationException $e) {
            throw new ValidationException(self::getErrorsDeep($e));
        }

        return array_merge($this->data, $newData);
    }

    /**
     * @param NestedValidationException $e
     *
     * @return array|string
     */
    private static function getErrorsDeep(NestedValidationException $e)
    {
        if (count($e->getRelated()) === 0) {
            return $e->getMessage();
        }

        $errors = [];
        foreach ($e->getRelated() as $related) {
            $name = $related instanceof AttributeException
                ? $related->getName()
                : '$' . $related->getId();

            $errors[$name] = $related instanceof NestedValidationException
                ? self::getErrorsDeep($related)
                : $related->getMessage();
        }

        return $errors;
    }
}
