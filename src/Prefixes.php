<?php

declare(strict_types=1);

namespace NovoConnect\SomaliPhone;

/**
 * Somali mobile prefixes and what sits behind each one.
 *
 * "operator" is the licensee in the NCA national numbering plan (ITU
 * communication of 8 December 2024). "virtual_network" is set where an
 * operator runs a separate virtual network on one of its ranges: Somnet on
 * Hormuud's 68, Soltelco on Telesom's 67 and Durdur on Golis's 70.
 * "wallet" is the mobile money service for that prefix.
 *
 * 64, 74 and 79 are allocated in the plan but not in use, so they are left out.
 * 73 is assigned to Telesom but not in use yet; Telesom numbers are on 63.
 * 90 is not in the 2024 communication but is in wide use by Golis subscribers.
 */
final class Prefixes
{
    public const MAP = [
        '60' => ['operator' => Operator::GOLIS,      'virtual_network' => null,       'wallet' => 'Sahal'],
        '61' => ['operator' => Operator::HORMUUD,    'virtual_network' => null,       'wallet' => 'EVC Plus'],
        '62' => ['operator' => Operator::SOMTEL,     'virtual_network' => null,       'wallet' => 'eDahab'],
        '63' => ['operator' => Operator::TELESOM,    'virtual_network' => null,       'wallet' => 'Zaad'],
        '65' => ['operator' => Operator::SOMTEL,     'virtual_network' => null,       'wallet' => 'eDahab'],
        '66' => ['operator' => Operator::SOMTEL,     'virtual_network' => null,       'wallet' => 'eDahab'],
        '67' => ['operator' => Operator::TELESOM,    'virtual_network' => 'Soltelco', 'wallet' => 'Kaash Plus'],
        '68' => ['operator' => Operator::HORMUUD,    'virtual_network' => 'Somnet',   'wallet' => 'Jeeb'],
        '69' => ['operator' => Operator::NATIONLINK, 'virtual_network' => null,       'wallet' => 'e-Maal'],
        '70' => ['operator' => Operator::GOLIS,      'virtual_network' => 'Durdur',   'wallet' => 'Kayd'],
        '71' => ['operator' => Operator::AMTEL,      'virtual_network' => null,       'wallet' => 'MyCash'],
        '72' => ['operator' => Operator::GOLIS,      'virtual_network' => null,       'wallet' => 'Sahal'],
        '75' => ['operator' => Operator::AMTEL,      'virtual_network' => null,       'wallet' => 'MyCash'],
        '76' => ['operator' => Operator::SOMTEL,     'virtual_network' => null,       'wallet' => 'eDahab'],
        '77' => ['operator' => Operator::HORMUUD,    'virtual_network' => null,       'wallet' => 'EVC Plus'],
        '78' => ['operator' => Operator::HORMUUD,    'virtual_network' => null,       'wallet' => 'EVC Plus'],
        '90' => ['operator' => Operator::GOLIS,      'virtual_network' => null,       'wallet' => 'Sahal'],
    ];

    /**
     * Wallets that work with WAAFI, as listed in the WaafiPay developer docs
     * (https://docs.waafipay.com, October 2026).
     */
    public const WAAFI_WALLETS = ['EVC Plus', 'Zaad', 'Sahal'];

    /**
     * Reported to work with WAAFI, but no official source confirms it yet.
     * supportsWaafi() returns false for these.
     */
    public const UNCONFIRMED_WAAFI_WALLETS = ['Jeeb', 'Kaash Plus', 'Kayd'];

    /**
     * @return list<string>
     */
    public static function wallets(): array
    {
        return array_values(array_unique(array_column(self::MAP, 'wallet')));
    }

    /**
     * @return list<string>
     */
    public static function virtualNetworks(): array
    {
        return array_values(array_unique(array_filter(array_column(self::MAP, 'virtual_network'))));
    }

    public static function has(string $prefix): bool
    {
        return isset(self::MAP[$prefix]);
    }

    /**
     * @return array{operator: Operator, virtual_network: ?string, wallet: ?string}
     */
    public static function get(string $prefix): array
    {
        return self::MAP[$prefix];
    }

    /**
     * @return list<string>
     */
    public static function forOperator(Operator $operator): array
    {
        // PHP turns numeric keys like '61' into integers.
        return array_map('strval', array_keys(array_filter(
            self::MAP,
            fn (array $row) => $row['operator'] === $operator,
        )));
    }

    /**
     * @return list<string>
     */
    public static function forVirtualNetwork(string $name): array
    {
        return array_map('strval', array_keys(array_filter(
            self::MAP,
            fn (array $row) => $row['virtual_network'] !== null
                && strcasecmp($row['virtual_network'], $name) === 0,
        )));
    }
}
