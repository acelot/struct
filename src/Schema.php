<?php declare(strict_types=1);

namespace Acelot\Struct;

use Acelot\Struct\Schema\Prop;

class Schema
{
    /**
     * @var array[string]Prop
     */
    protected $props;

    /**
     * @param Prop ...$props
     */
    public function __construct(Prop ...$props)
    {
        if (empty($props)) {
            throw new \InvalidArgumentException('At least one prop must be defined');
        }

        foreach ($props as $prop) {
            $this->props[$prop->getName()] = $prop;
        }
    }

    /**
     * @return Prop[]
     */
    public function getProps(): array
    {
        return $this->props;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasProp(string $name): bool
    {
        return array_key_exists($name, $this->props);
    }

    /**
     * @param string $name
     *
     * @return Prop
     * @throws \OutOfBoundsException
     */
    public function getProp(string $name): Prop
    {
        if (!$this->hasProp($name)) {
            throw new \OutOfBoundsException(sprintf('Property "%s" not exists in struct', $name));
        }

        return $this->props[$name];
    }

    /**
     * @param Prop ...$props
     *
     * @return Schema
     */
    public function prop(Prop ...$props): Schema
    {
        return $this->withProp(...$props);
    }

    /**
     * @param Prop ...$props
     *
     * @return Schema
     */
    public function withProp(Prop ...$props): Schema
    {
        if (empty($props)) {
            throw new \InvalidArgumentException('At least one prop must be defined');
        }

        $clone = clone $this;
        foreach ($props as $prop) {
            $clone->props[$prop->getName()] = $prop;
        }

        return $clone;
    }

    /**
     * @param string $name
     *
     * @return Schema
     */
    public function withoutProp(string $name): Schema
    {
        $clone = clone $this;
        unset($clone->props[$name]);
        return $clone;
    }
}
