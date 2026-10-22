<?php

declare(strict_types=1);

namespace NovoConnect\SomaliPhone\Laravel\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use NovoConnect\SomaliPhone\Laravel\Messages;
use NovoConnect\SomaliPhone\Operator;
use NovoConnect\SomaliPhone\Prefixes;
use NovoConnect\SomaliPhone\SomaliPhone;

/**
 * Validates a Somali mobile number, optionally limited by operator,
 * virtual network, wallet or WAAFI support.
 *
 *     new SomaliPhoneNumber
 *     (new SomaliPhoneNumber)->operators(Operator::HORMUUD)
 *     (new SomaliPhoneNumber)->virtualNetworks('Somnet')
 *     (new SomaliPhoneNumber)->wallets('Zaad', 'Sahal')
 *     (new SomaliPhoneNumber)->waafi()
 */
class SomaliPhoneNumber implements ValidationRule
{
    /** @var list<Operator> */
    protected array $operators = [];

    /** @var list<string> */
    protected array $virtualNetworks = [];

    /** @var list<string> */
    protected array $wallets = [];

    protected bool $waafi = false;

    public function operators(Operator|string ...$operators): static
    {
        $this->operators = array_values(array_map(
            fn (Operator|string $op) => $op instanceof Operator ? $op : Operator::fromName($op),
            $operators,
        ));

        return $this;
    }

    /**
     * @throws \ValueError for an unknown name
     */
    public function virtualNetworks(string ...$names): static
    {
        $this->virtualNetworks = self::known($names, Prefixes::virtualNetworks(), 'virtual network');

        return $this;
    }

    /**
     * @throws \ValueError for an unknown name
     */
    public function wallets(string ...$wallets): static
    {
        $this->wallets = self::known($wallets, Prefixes::wallets(), 'wallet');

        return $this;
    }

    public function waafi(bool $required = true): static
    {
        $this->waafi = $required;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $number = is_scalar($value) ? SomaliPhone::tryParse((string) $value) : null;

        if ($number === null) {
            $fail(Messages::get('invalid'));

            return;
        }

        if ($this->operators && ! in_array($number->operator(), $this->operators, true)) {
            $fail(Messages::get('operator', [
                'allowed' => Messages::join(array_map(fn (Operator $op) => $op->value, $this->operators)),
            ]));

            return;
        }

        if ($this->virtualNetworks && ! in_array($number->virtualNetwork(), $this->virtualNetworks, true)) {
            $fail(Messages::get('virtual_network', ['allowed' => Messages::join($this->virtualNetworks)]));

            return;
        }

        if ($this->wallets && ! in_array($number->wallet(), $this->wallets, true)) {
            $fail(Messages::get('wallet', ['allowed' => Messages::join($this->wallets)]));

            return;
        }

        if ($this->waafi && ! $number->supportsWaafi()) {
            $fail(Messages::get('waafi'));
        }
    }

    /**
     * Maps names to their spelling in the data ("zaad" to "Zaad") and
     * throws on unknown ones.
     *
     * @param  array<string>  $names
     * @param  list<string>  $known
     * @return list<string>
     */
    private static function known(array $names, array $known, string $kind): array
    {
        $result = [];

        foreach ($names as $name) {
            foreach ($known as $candidate) {
                if (strcasecmp($candidate, $name) === 0) {
                    $result[] = $candidate;

                    continue 2;
                }
            }

            throw new \ValueError(sprintf('Unknown Somali %s "%s". Use one of: %s.', $kind, $name, implode(', ', $known)));
        }

        return $result;
    }
}
