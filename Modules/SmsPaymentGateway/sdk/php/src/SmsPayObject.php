<?php

namespace SmsPay;

/**
 * Base object that hydrates API JSON responses into accessible object properties.
 *
 * Provides dynamic property access via `__get()` and array conversion via `toArray()`.
 *
 * @property-read string|null $id
 * @property-read string|null $object
 */
class SmsPayObject
{
    /** @var array<string, mixed> Internal data store. */
    protected array $attributes = [];

    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->attributes = $attributes;
    }

    /**
     * Create an SmsPayObject (or subclass) from an associative array.
     *
     * Nested arrays that contain an "object" key are recursively converted.
     *
     * @param array<string, mixed> $data
     * @return static
     */
    public static function fromArray(array $data): self
    {
        $obj = new static();

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                // Recursively hydrate nested objects that look like API resources.
                if (isset($value['object'])) {
                    $obj->attributes[$key] = new self($value);
                } else {
                    $obj->attributes[$key] = $value;
                }
            } else {
                $obj->attributes[$key] = $value;
            }
        }

        return $obj;
    }

    /**
     * Magic getter — access any response field as a property.
     *
     * @param string $name
     * @return mixed|null
     */
    public function __get(string $name)
    {
        return $this->attributes[$name] ?? null;
    }

    /**
     * Magic isset — allows `isset($obj->property)` checks.
     *
     * @param string $name
     * @return bool
     */
    public function __isset(string $name): bool
    {
        return array_key_exists($name, $this->attributes);
    }

    /**
     * Convert the object (and any nested SmsPayObjects) back to an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $result = [];

        foreach ($this->attributes as $key => $value) {
            if ($value instanceof self) {
                $result[$key] = $value->toArray();
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * JSON representation.
     *
     * @return string
     */
    public function __toString(): string
    {
        return (string) json_encode($this->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}
