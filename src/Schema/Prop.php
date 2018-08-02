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
    public function withMapper(DefinitionInterface $definition, string $sourceName)
    {
        $clone = clone $this;
        $clone->mappers[$sourceName] = $definition;
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
     * @param string $key
     *
     * @return bool
     */
    public function hasMeta(string $key): bool
    {
        return array_key_exists($key, $this->meta);
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
    public function withMeta(string $key, $value)
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
    public function withoutMeta(string $key)
    {
        $clone = clone $this;
        unset($clone->meta[$key]);
        return $clone;
    }
}
