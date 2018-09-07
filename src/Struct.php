<?php declare(strict_types=1);

namespace Acelot\Struct;

use Acelot\AutoMapper\AutoMapper;
use Acelot\AutoMapper\Field;
use Acelot\Struct\Exception\ValidationException;
use Acelot\Struct\Schema\Prop;
use Respect\Validation\Exceptions\AttributeException;
use Respect\Validation\Exceptions\NestedValidationException;
use Respect\Validation\Rules\Key as ValidationKey;
use Respect\Validation\Rules\KeySet;

abstract class Struct implements \Iterator, \Countable, \JsonSerializable
{
    private $__defined_props = [];

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
        $this->validate($data);
    }

    public function current()
    {
        return $this->{current($this->__defined_props)};
    }

    public function next()
    {
        return $this->{next($this->__defined_props)} ?? false;
    }

    public function key()
    {
        return current($this->__defined_props);
    }

    public function valid()
    {
        return key($this->__defined_props) !== null;
    }

    public function rewind()
    {
        reset($this->__defined_props);
    }

    public function count()
    {
        return count($this->__defined_props);
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function has(string $key): bool
    {
        return property_exists($this, $key);
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

        return $this->{$key};
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
        $data = $this->toArray();
        $data[$key] = $value;
        return new static($data);
    }

    /**
     * @param string $key
     *
     * @return static
     * @throws ValidationException
     */
    public function delete(string $key)
    {
        $data = $this->toArray();
        unset($data[$key]);
        return new static($data);
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return array_reduce($this->__defined_props, function ($carry, $prop) {
            $carry[$prop] = $this->{$prop};
            return $carry;
        }, []);
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
     * @throws ValidationException
     */
    protected function validate(array $newData): void
    {
        $rules = array_map(function (Prop $prop) {
            return new ValidationKey($prop->getName(), $prop->getValidator(), $prop->isRequired());
        }, static::getSchema()->getProps());

        $validator = new KeySet(...array_values($rules));

        try {
            $validator->assert($newData);
        } catch (NestedValidationException $e) {
            throw new ValidationException(self::getErrorsDeep($e));
        }

        foreach ($newData as $key => $value) {
            $this->{$key} = $value;
            $this->__defined_props[] = $key;
        }
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
