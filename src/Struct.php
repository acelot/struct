<?php declare(strict_types=1);

namespace Acelot\Struct;

use Acelot\AutoMapper\AutoMapper;
use Acelot\AutoMapper\Definition\Value;
use Acelot\AutoMapper\Field;
use Acelot\Struct\Exception\ExcludePropertyException;
use Acelot\Struct\Exception\ValidationException;
use Acelot\Struct\Schema\Prop;
use Acelot\Struct\Value\Hydrated;
use Respect\Validation\Exceptions\AttributeException;
use Respect\Validation\Exceptions\NestedValidationException;
use Respect\Validation\Rules\AllOf;
use Respect\Validation\Rules\Instance;
use Respect\Validation\Rules\Key as ValidationKey;
use Respect\Validation\Rules\OneOf;

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
     * @param string[] $hydratedProps
     *
     * @return static
     * @throws \Acelot\AutoMapper\Exception\InvalidSourceException
     * @throws \Acelot\AutoMapper\Exception\InvalidTargetException
     * @throws \Acelot\AutoMapper\Exception\SourceFieldMissingException
     */
    public static function mapFrom($data, string $sourceName, array $hydratedProps = [])
    {
        $fields = array_map(
            function (Prop $prop) use ($sourceName, $hydratedProps) {
                return Field::create($prop->getName(), static::getMapper($prop, $sourceName, $hydratedProps));
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
            if ($this->get($prop->getName()) instanceof Hydrated) {
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
     *
     * @throws ValidationException
     */
    protected function validate(array $newData): void
    {
        $props = static::getSchema()->getProps();

        $extraKeys = array_keys(array_diff_key($newData, $props));
        if (!empty($extraKeys)) {
            throw new ValidationException(array_map(function ($extraKey) {
                return [$extraKey => "Property \"$extraKey\" not defined in schema"];
            }, $extraKeys));
        }

        $rules = array_map(function (Prop $prop)  {
            return new ValidationKey(
                $prop->getName(),
                new OneOf(
                    new Instance(Hydrated::class),
                    $prop->getValidator()
                ),
                $prop->isRequired()
            );
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

    /**
     * @param Prop $prop
     * @param string $sourceName
     * @param array $hydratedProps
     *
     * @return Value|\Acelot\AutoMapper\DefinitionInterface
     */
    private static function getMapper(Prop $prop, string $sourceName, array $hydratedProps = []) {
        if (in_array($prop->getName(), $hydratedProps, true)) {
            return new Value(new Hydrated());
        }

        if ($prop->hasMapper($sourceName)) {
            return $prop->getMapper($sourceName);
        }

        return $prop->getMapper('default');
    }
}
