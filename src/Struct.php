<?php declare(strict_types=1);

namespace Acelot\Struct;

use Acelot\AutoMapper\AutoMapper;
use Acelot\AutoMapper\Field;
use Acelot\Struct\Exception\ExcludePropertyException;
use Acelot\Struct\Exception\ValidationException;
use Acelot\Struct\Schema\Prop;
use Respect\Validation\Exceptions\AttributeException;
use Respect\Validation\Exceptions\NestedValidationException;
use Respect\Validation\Rules\AllOf;
use Respect\Validation\Rules\Key as ValidationKey;

abstract class Struct implements \Iterator, \Countable, \JsonSerializable
{
    private $__defined_props = [];

    /**
     * @return Schema
     */
    abstract public static function getSchema(): Schema;

    /**
     * @param array|object $data
     * @param string $sourceName
     * @param bool $allowPartial - разрешать созадвать не полную модель
     * (игнорировать валидацию отсутствующих в data св-в)
     *
     * @return static
     */
    public static function mapFrom($data, string $sourceName, bool $allowPartial = false)
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

        return new static($mapper->marshalArray($data), $allowPartial);
    }

    /**
     * @param array $data
     * @param bool $allowPartial - разрешать созадвать не полную модель
     */
    public function __construct(array $data, bool $allowPartial = false)
    {
        $this->validate($data, $allowPartial);
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
        $data = iterator_to_array($this);
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
        $data = iterator_to_array($this);
        unset($data[$key]);
        return new static($data);
    }

    /**
     * @return object
     */
    public function jsonSerialize()
    {
        $data = new \stdClass();
        foreach (static::getSchema()->getProps() as $prop) {
            if (!$this->has($prop->getName())) {
                continue;
            }

            try {
                $data->{$prop->getName()} = static::jsonSerializeValue($this->get($prop->getName()), $prop);
            } catch (ExcludePropertyException $e) {
                continue;
            }
        }

        return $data;
    }

    /**
     * @param mixed $value
     * @param Prop  $prop
     *
     * @return mixed
     * @throws ExcludePropertyException
     * @codeCoverageIgnore
     */
    protected static function jsonSerializeValue($value, Prop $prop)
    {
        return $value;
    }

    /**
     * @param array $newData
     * @param bool $allowPartial - игнорировать валидацию isRequired
     */
    protected function validate(array $newData, bool $allowPartial = false): void
    {
        $props = static::getSchema()->getProps();

        $extraKeys = array_keys(array_diff_key($newData, $props));
        if (!empty($extraKeys)) {
            throw new ValidationException(array_map(function ($extraKey) {
                return [$extraKey => "Property \"$extraKey\" not defined in schema"];
            }, $extraKeys));
        }

        $rules = array_map(function (Prop $prop) use ($allowPartial) {
            $isRequired = $allowPartial ? false : $prop->isRequired();
            return new ValidationKey($prop->getName(), $prop->getValidator(), $isRequired);
        }, $props);

        $validator = new AllOf(...array_values($rules));

        // Assign default values
        foreach ($props as $prop) {
            if (!$prop->isRequired()) {
                continue;
            }
            if (!$prop->hasDefaultValue()) {
                continue;
            }
            if (array_key_exists($prop->getName(), $newData)) {
                continue;
            }
            $newData[$prop->getName()] = $prop->getDefaultValue();
        }

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
