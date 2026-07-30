<?php

namespace MakiDizajnerica\Dto;

use ArrayAccess;
use ReflectionClass;
use JsonSerializable;
use AllowDynamicProperties;
use Illuminate\Contracts\Support\Arrayable;
use MakiDizajnerica\Dto\Attributes\DoNotSerializeProperty;

#[AllowDynamicProperties]
abstract class DataTransferObject implements Arrayable, ArrayAccess, JsonSerializable
{
    /**
     * @param  array $data
     * @return void
     */
    public function __construct(array $data = [])
    {
        $this->populate($data);
    }

    /**
     * @param  array $data
     * @return void
     */
    protected function populate(array $data): void
    {
        $reflection = new ReflectionClass($this);
        $properties = $reflection->getProperties();

        foreach ($properties as $property) {
            $name = $property->getName();

            if (array_key_exists($name, $data)) {
                $setter = sprintf('set%s', ucfirst($name));

                if (method_exists($this, $setter)) {
                    $this->$setter($data[$name]);
                } else {
                    $this->set($name, $data[$name]);
                }
            }
        }
    }

    /**
     * @param  array $data
     * @return static
     */
    public static function make(array $data = []): static
    {
        return new static($data);
    }

    /**
     * @param  array $overrides
     * @return static
     */
    public function with(array $overrides): static
    {
        return static::make(
            array_merge($this->toArray(), $overrides)
        );
    }

    /**
     * @param  string|array $keys
     * @return static
     */
    public function except(string|array $keys): static
    {
        if (! is_array($keys)) {
            $keys = [$keys];
        }

        $data = $this->toArray();

        foreach ($keys as $key) {
            unset($data[$key]);
        }

        return static::make($data);
    }

    /**
     * @param  string|array $keys
     * @return static
     */
    public function only(string|array $keys): static
    {
        if (! is_array($keys)) {
            $keys = [$keys];
        }

        $data = $this->toArray();

        $filtered = array_filter(
            $data,
            fn ($key) => in_array($key, $keys, true),
            ARRAY_FILTER_USE_KEY
        );

        return static::make($filtered);
    }

    /**
     * @param  mixed $offset
     * @return bool
     */
    public function exists(mixed $offset): bool
    {
        return property_exists($this, $offset);
    }

    /**
     * @param  mixed $offset
     * @return bool
     */
    public function isset(mixed $offset): bool
    {
        return isset($this->$offset);
    }

    /**
     * @param  mixed $offset
     * @param  mixed $value
     * @return $this
     */
    public function set(mixed $offset, mixed $value): static
    {
        $this->$offset = $value;

        return $this;
    }

    /**
     * @param  mixed $offset
     * @param  mixed $default
     * @return mixed
     */
    public function get(mixed $offset, mixed $default = null): mixed
    {
        if ($this->exists($offset) && $this->isset($offset)) {
            return $this->$offset;
        }

        return $default;
    }

    /**
     * @param  mixed $offset
     * @return $this
     */
    public function unset(mixed $offset): static
    {
        unset($this->$offset);

        return $this;
    }

    /**
     * @param  mixed $offset
     * @return bool
     */
    public function offsetExists(mixed $offset): bool
    {
        return $this->exists($offset);
    }

    /**
     * @param  mixed $offset
     * @param  mixed $value
     * @return void
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->set($offset, $value);
    }

    /**
     * @param  mixed $offset
     * @return mixed
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->get($offset);
    }

    /**
     * @param  mixed $offset
     * @return void
     */
    public function offsetUnset(mixed $offset): void
    {
        $this->unset($offset);
    }

    /**
     * Serialize the properties of the DTO into an array.
     */
    protected function serializeProperties(): array
    {
        $reflection = new ReflectionClass($this);
        $properties = $reflection->getProperties();

        $array = [];

        foreach ($properties as $property) {
            $key = $property->getName();

            if (! $property->isInitialized($this)) {
                continue;
            }

            $value = $property->getValue($this);
            $attributes = $property->getAttributes();

            foreach ($attributes as $attribute) {
                switch ($attribute->getName()) {
                    case DoNotSerializeProperty::class:
                        continue 3;
                }
            }

            $array[$key] = $value;
        }

        return $array;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return $this->serializeProperties();
    }

    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
