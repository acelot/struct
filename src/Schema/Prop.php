<?php declare(strict_types=1);

namespace Acelot\Struct\Schema;

use Acelot\AutoMapper\Definition\From;
use Acelot\AutoMapper\DefinitionInterface;
use Respect\Validation\Rules\AlwaysValid;
use Respect\Validation\Validatable;

class Prop
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var Validatable
     */
    protected $validator;

    /**
     * @var array[string]DefinitionInterface
     */
    protected $mappers;

    /**
     * @var bool
     */
    protected $required;

    /**
     * @var bool
     */
    protected $hasDefaultValue;

    /**
     * @var mixed
     */
    protected $defaultValue;

    /**
     * @var array
     */
    protected $meta;

    /**
     * @param string $name
     *
     * @return Prop
     */
    public static function create(string $name): Prop
    {
        return new self($name);
    }

    /**
     * @param string $name
     */
    protected function __construct(string $name)
    {
        $this->name = $name;
        $this->validator = new AlwaysValid();
        $this->mappers = ['default' => From::create($name)];
        $this->required = true;
        $this->hasDefaultValue = false;
        $this->defaultValue = null;
        $this->meta = [];
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return Validatable
     */
    public function getValidator(): Validatable
    {
        return $this->validator;
    }

    /**
     * @param Validatable $validator
     *
     * @return Prop
     */
    public function validator(Validatable $validator): Prop
    {
        return $this->withValidator($validator);
    }

    /**
     * @param Validatable $validator
     *
     * @return Prop
     */
    public function withValidator(Validatable $validator): Prop
    {
        $clone = clone $this;
        $clone->validator = $validator;
        return $clone;
    }

    /**
     * @param string $sourceName
     *
     * @return bool
     */
    public function hasMapper(string $sourceName): bool
    {
        return array_key_exists($sourceName, $this->mappers);
    }

    /**
     * @param string $sourceName
     *
     * @return DefinitionInterface
     */
    public function getMapper(string $sourceName): DefinitionInterface
    {
        return $this->mappers[$sourceName];
    }

    /**
     * @param DefinitionInterface $definition
     * @param string              $sourceName
     *
     * @return Prop
     */
    public function mapper(DefinitionInterface $definition, string $sourceName): Prop
    {
        return $this->withMapper($definition, $sourceName);
    }

    /**
     * @param DefinitionInterface $definition
     * @param string              $sourceName
     *
     * @return Prop
     */
    public function withMapper(DefinitionInterface $definition, string $sourceName): Prop
    {
        $clone = clone $this;
        $clone->mappers[$sourceName] = $definition;
        return $clone;
    }

    /**
     * @param string $sourceName
     *
     * @return Prop
     */
    public function withoutMapper(string $sourceName): Prop
    {
        if ($sourceName === 'default') {
            throw new \InvalidArgumentException('Default mapper cannot be removed');
        }

        $clone = clone $this;
        unset($clone->mappers[$sourceName]);
        return $clone;
    }

    /**
     * @return bool
     */
    public function isRequired(): bool
    {
        return $this->required;
    }

    /**
     * @return Prop
     */
    public function required(): Prop
    {
        $clone = clone $this;
        $clone->required = true;
        return $clone;
    }

    /**
     * @return Prop
     */
    public function notRequired(): Prop
    {
        $clone = clone $this;
        $clone->required = false;
        return $clone;
    }

    /**
     * @return bool
     */
    public function hasDefaultValue(): bool
    {
        return $this->hasDefaultValue;
    }

    /**
     * @return mixed
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    /**
     * @param mixed $value
     *
     * @return Prop
     */
    public function defaultValue($value): Prop
    {
        return $this->withDefaultValue($value);
    }

    /**
     * @param mixed $value
     *
     * @return Prop
     */
    public function withDefaultValue($value): Prop
    {
        $clone = clone $this;
        $clone->hasDefaultValue = true;
        $clone->defaultValue = $value;
        return $clone;
    }

    /**
     * @return Prop
     */
    public function withoutDefaultValue(): Prop
    {
        $clone = clone $this;
        $clone->hasDefaultValue = false;
        $clone->defaultValue = null;
        return $clone;
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function hasMeta(string $key): bool
    {
        return array_key_exists($key, $this->meta);
    }

    /**
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getMeta(string $key, $default = null)
    {
        if (!$this->hasMeta($key)) {
            return $default;
        }
        return $this->meta[$key];
    }

    /**
     * @param string $key
     * @param mixed  $value
     *
     * @return Prop
     */
    public function meta(string $key, $value): Prop
    {
        return $this->withMeta($key, $value);
    }

    /**
     * @param string $key
     * @param mixed  $value
     *
     * @return Prop
     */
    public function withMeta(string $key, $value): Prop
    {
        $clone = clone $this;
        $clone->meta[$key] = $value;
        return $clone;
    }

    /**
     * @param string $key
     *
     * @return Prop
     */
    public function withoutMeta(string $key): Prop
    {
        $clone = clone $this;
        unset($clone->meta[$key]);
        return $clone;
    }
}
