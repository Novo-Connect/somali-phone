<?php

declare(strict_types=1);

namespace NovoConnect\SomaliPhone;

use InvalidArgumentException;

/**
 * The message leaves out the input to keep phone numbers out of logs.
 * Read $input if you need it.
 */
final class InvalidPhoneNumber extends InvalidArgumentException
{
    public function __construct(public readonly ?string $input = null)
    {
        parent::__construct('The given value is not a valid Somali mobile number.');
    }

    public static function for(?string $input): self
    {
        return new self($input);
    }
}
