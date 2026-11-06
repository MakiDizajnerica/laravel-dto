<?php

namespace MakiDizajnerica\Dto;

use ArrayAccess;
use ReflectionClass;
use JsonSerializable;
use AllowDynamicProperties;
use MakiDizajnerica\Dto\Attributes\Hidden;
use Illuminate\Contracts\Support\Arrayable;

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
            array_merge($this->toRawArray(), $overrides)
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

        $data = $this->toRawArray();

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

        $data = $this->toRawArray();

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
     * @param  bool $applyAttributes
     * @return array
     */
    protected function buildArray(bool $applyAttributes): array
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

            if ($applyAttributes) {
                $attributes = $property->getAttributes();

                foreach ($attributes as $attribute) {
                    switch ($attribute->getName()) {
                        case Hidden::class:
                            continue 3;
                    }
                }
            }

            $array[$key] = $value;
        }

        return $array;
    }

    /**
     * Get all properties of the DTO as an array.
     */
    final public function toRawArray(): array
    {
        return $this->buildArray(false);
    }

    /**
     * Get all properties of the DTO as an array, excluding hidden properties.
     */
    final public function toArray(): array
    {
        return $this->buildArray(true);
    }

    /**
     * @return array
     */
    final public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
