<?php

declare(strict_types=1);

namespace NovoConnect\SomaliPhone;

use JsonSerializable;
use Stringable;

/**
 * A validated Somali mobile number. Create one with SomaliPhone::parse().
 */
final class PhoneNumber implements JsonSerializable, Stringable
{
    /**
     * @param  string  $national  The 9-digit national number, e.g. "634123456".
     */
    private function __construct(private readonly string $national) {}

    /**
     * @internal  Use SomaliPhone::parse() instead.
     *
     * @throws InvalidPhoneNumber  If $national isn't 9 digits on a prefix in use.
     */
    public static function fromNational(string $national): self
    {
        if (! SomaliPhone::isValidNational($national)) {
            throw InvalidPhoneNumber::for($national);
        }

        return new self($national);
    }

    /** "634123456" */
    public function national(): string
    {
        return $this->national;
    }

    /** "252634123456", the format most SMS gateways and wallet APIs expect. */
    public function msisdn(): string
    {
        return '252' . $this->national;
    }

    /** "+252634123456" */
    public function e164(): string
    {
        return '+252' . $this->national;
    }

    /** "0634123456" */
    public function local(): string
    {
        return '0' . $this->national;
    }

    /** "63" */
    public function prefix(): string
    {
        return substr($this->national, 0, 2);
    }

    /** The licensed operator, e.g. Operator::GOLIS for a Durdur number. */
    public function operator(): Operator
    {
        return Prefixes::get($this->prefix())['operator'];
    }

    /** The virtual network on this prefix, e.g. "Durdur", or null for the operator's own network. */
    public function virtualNetwork(): ?string
    {
        return Prefixes::get($this->prefix())['virtual_network'];
    }

    public function isVirtualNetwork(): bool
    {
        return $this->virtualNetwork() !== null;
    }

    /** The name to show the user: the virtual network if there is one, otherwise the operator. */
    public function networkName(): string
    {
        return $this->virtualNetwork() ?? $this->operator()->value;
    }

    /** The mobile money wallet on this network, e.g. "Kayd", or null if unknown. */
    public function wallet(): ?string
    {
        return Prefixes::get($this->prefix())['wallet'];
    }

    /** Whether the wallet is confirmed to work with WAAFI. */
    public function supportsWaafi(): bool
    {
        return in_array($this->wallet(), Prefixes::WAAFI_WALLETS, true);
    }

    /** Whether the wallet may work with WAAFI but isn't confirmed yet. */
    public function isWaafiUnconfirmed(): bool
    {
        return in_array($this->wallet(), Prefixes::UNCONFIRMED_WAAFI_WALLETS, true);
    }

    /**
     * "+252 63 412 3456" (international) or "063 412 3456" (local).
     */
    public function format(bool $international = true): string
    {
        $rest = substr($this->national, 2, 3) . ' ' . substr($this->national, 5);

        return $international
            ? '+252 ' . $this->prefix() . ' ' . $rest
            : '0' . $this->prefix() . ' ' . $rest;
    }

    public function equals(self|string|null $other): bool
    {
        if (is_string($other)) {
            $other = SomaliPhone::tryParse($other);
        }

        return $other !== null && $other->national === $this->national;
    }

    public function __toString(): string
    {
        return $this->msisdn();
    }

    /**
     * "waafi" is null when support is unconfirmed.
     *
     * @return array{number: string, operator: string, virtual_network: ?string, wallet: ?string, waafi: ?bool}
     */
    public function jsonSerialize(): array
    {
        return [
            'number'          => $this->msisdn(),
            'operator'        => $this->operator()->value,
            'virtual_network' => $this->virtualNetwork(),
            'wallet'          => $this->wallet(),
            'waafi'           => $this->isWaafiUnconfirmed() ? null : $this->supportsWaafi(),
        ];
    }
}
