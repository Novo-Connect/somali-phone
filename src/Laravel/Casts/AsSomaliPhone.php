<?php

declare(strict_types=1);

namespace NovoConnect\SomaliPhone\Laravel\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Database\Eloquent\SerializesCastableAttributes;
use Illuminate\Database\Eloquent\Model;
use NovoConnect\SomaliPhone\InvalidPhoneNumber;
use NovoConnect\SomaliPhone\PhoneNumber;
use NovoConnect\SomaliPhone\SomaliPhone;

/**
 * Stores numbers as "252XXXXXXXXX" and reads them back as PhoneNumber.
 * Serializes to the plain string.
 *
 * Invalid values already in the database read as null. Use
 * getRawOriginal() to get them.
 *
 * @implements CastsAttributes<PhoneNumber|null, mixed>
 */
class AsSomaliPhone implements CastsAttributes, SerializesCastableAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?PhoneNumber
    {
        return is_scalar($value) ? SomaliPhone::tryParse((string) $value) : null;
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof PhoneNumber) {
            return $value->msisdn();
        }

        if (! is_scalar($value)) {
            throw InvalidPhoneNumber::for(null);
        }

        return SomaliPhone::parse((string) $value)->msisdn();
    }

    public function serialize(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return $value instanceof PhoneNumber ? $value->msisdn() : null;
    }
}
