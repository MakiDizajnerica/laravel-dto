<?php

namespace MakiDizajnerica\Dto\Casts;

use Illuminate\Database\Eloquent\Model;
use MakiDizajnerica\Dto\DataTransferObject;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

abstract class DtoCast implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if (blank($value)) {
            return $this->toDto($model);
        }

        return $this->toDto($model, json_decode($value, true));
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if (blank($value)) {
            return null;
        }

        if ($value instanceof DataTransferObject) {
            $value = $value->toArray();
        }

        return json_encode($value);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  array  $data
     * @return \MakiDizajnerica\Dto\DataTransferObject
     */
    abstract protected function toDto(Model $model, array $data = []): DataTransferObject;
}
