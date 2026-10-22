<?php

declare(strict_types=1);

namespace NovoConnect\SomaliPhone\Tests;

use NovoConnect\SomaliPhone\InvalidPhoneNumber;
use NovoConnect\SomaliPhone\Operator;
use NovoConnect\SomaliPhone\PhoneNumber;
use NovoConnect\SomaliPhone\Prefixes;
use NovoConnect\SomaliPhone\SomaliPhone;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SomaliPhoneTest extends TestCase
{
    public static function validFormats(): array
    {
        return [
            'local'                  => ['0634123456'],
            'national'               => ['634123456'],
            'msisdn'                 => ['252634123456'],
            'e164'                   => ['+252634123456'],
            'double zero'            => ['00252634123456'],
            'spaced'                 => ['+252 63 412 3456'],
            'dashed'                 => ['252-63-412-3456'],
            'parentheses'            => ['(063) 412 3456'],
            'country code with 0'    => ['+252 063 412 3456'],
            'surrounding whitespace' => ["  0634123456\n"],
            'dotted'                 => ['063.412.3456'],
            'non-breaking spaces'    => ["063\u{00A0}412\u{00A0}3456"],
            'arabic-indic digits'    => ['٠٦٣٤١٢٣٤٥٦'],
            'eastern arabic-indic'   => ['+۲۵۲ ۶۳ ۴۱۲ ۳۴۵۶'],
        ];
    }

    #[DataProvider('validFormats')]
    public function test_it_normalizes_common_formats(string $input): void
    {
        $this->assertSame('252634123456', SomaliPhone::normalize($input));
    }

    public static function invalidNumbers(): array
    {
        return [
            'empty'                    => [''],
            'null'                     => [null],
            'too short'                => ['063412345'],
            'too long'                 => ['06341234567'],
            'unused prefix 64'         => ['0644123456'],
            'unused prefix 74'         => ['0744123456'],
            'unused prefix 79'         => ['0794123456'],
            'prefix 73 not yet in use' => ['0734123456'],
            'landline range'           => ['0114123456'],
            'not a mobile'             => ['0814123456'],
            'other country'            => ['+254712345678'],
            'letters'                  => ['phone number'],
            'letters between digits'   => ['abc63def4123456'],
            'number in a sentence'     => ['call me on 063 412 3456'],
            'two numbers'              => ['0634123456 / 0615551234'],
            'plus in the middle'       => ['063+4123456'],
            'fullwidth digits'         => ['０６３４１２３４５６'],
            'plus without 252'         => ['+634123456'],
            'plus with only a 0'       => ['+0634123456'],
            'other country with 00'    => ['0063412345'],
        ];
    }

    #[DataProvider('invalidNumbers')]
    public function test_it_rejects_invalid_numbers(?string $input): void
    {
        $this->assertNull(SomaliPhone::normalize($input));
        $this->assertFalse(SomaliPhone::isValid($input));
    }

    public function test_parse_throws_for_invalid_numbers(): void
    {
        try {
            SomaliPhone::parse('0644123456');
            $this->fail('Expected an InvalidPhoneNumber exception.');
        } catch (InvalidPhoneNumber $e) {
            $this->assertStringNotContainsString('0644123456', $e->getMessage());
            $this->assertSame('0644123456', $e->input);
        }
    }

    public function test_from_national_rejects_invalid_values(): void
    {
        $this->assertSame('634123456', PhoneNumber::fromNational('634123456')->national());

        $this->expectException(InvalidPhoneNumber::class);
        PhoneNumber::fromNational('123');
    }

    public static function lookups(): array
    {
        // input, operator, virtual network, wallet, waafi (null = unconfirmed)
        return [
            'Hormuud 61'  => ['0615551234', Operator::HORMUUD, null, 'EVC Plus', true],
            'Hormuud 77'  => ['0771234567', Operator::HORMUUD, null, 'EVC Plus', true],
            'Somnet 68'   => ['0681234567', Operator::HORMUUD, 'Somnet', 'Jeeb', null],
            'Telesom 63'  => ['0634123456', Operator::TELESOM, null, 'Zaad', true],
            'Soltelco 67' => ['0671234567', Operator::TELESOM, 'Soltelco', 'Kaash Plus', null],
            'Golis 90'    => ['0907123456', Operator::GOLIS, null, 'Sahal', true],
            'Durdur 70'   => ['0702000001', Operator::GOLIS, 'Durdur', 'Kayd', null],
            'Somtel 62'   => ['0621234567', Operator::SOMTEL, null, 'eDahab', false],
            'NationLink'  => ['0691234567', Operator::NATIONLINK, null, 'e-Maal', false],
            'Amtel 71'    => ['0711234567', Operator::AMTEL, null, 'MyCash', false],
        ];
    }

    #[DataProvider('lookups')]
    public function test_it_identifies_operator_virtual_network_and_wallet(
        string $input,
        Operator $operator,
        ?string $virtualNetwork,
        ?string $wallet,
        ?bool $waafi,
    ): void {
        $number = SomaliPhone::parse($input);

        $this->assertSame($operator, $number->operator());
        $this->assertSame($virtualNetwork, $number->virtualNetwork());
        $this->assertSame($virtualNetwork !== null, $number->isVirtualNetwork());
        $this->assertSame($virtualNetwork ?? $operator->value, $number->networkName());
        $this->assertSame($wallet, $number->wallet());
        $this->assertSame($waafi === true, $number->supportsWaafi());
        $this->assertSame($waafi === null, $number->isWaafiUnconfirmed());
        $this->assertSame($waafi, $number->jsonSerialize()['waafi']);
    }

    public function test_it_formats_numbers(): void
    {
        $number = SomaliPhone::parse('252634123456');

        $this->assertSame('634123456', $number->national());
        $this->assertSame('252634123456', $number->msisdn());
        $this->assertSame('+252634123456', $number->e164());
        $this->assertSame('0634123456', $number->local());
        $this->assertSame('63', $number->prefix());
        $this->assertSame('+252 63 412 3456', $number->format());
        $this->assertSame('063 412 3456', $number->format(international: false));
        $this->assertSame('252634123456', (string) $number);
    }

    public function test_equality_ignores_input_format(): void
    {
        $number = SomaliPhone::parse('0634123456');

        $this->assertTrue($number->equals('+252 63 412 3456'));
        $this->assertTrue($number->equals(SomaliPhone::parse('634123456')));
        $this->assertFalse($number->equals('0634123457'));
        $this->assertFalse($number->equals('not a number'));
        $this->assertFalse($number->equals(null));
    }

    public function test_it_serializes_to_json(): void
    {
        $this->assertSame(
            '{"number":"252702000001","operator":"Golis","virtual_network":"Durdur","wallet":"Kayd","waafi":null}',
            json_encode(SomaliPhone::parse('0702000001')),
        );
    }

    public function test_prefix_lookups(): void
    {
        $this->assertSame(['61', '68', '77', '78'], Prefixes::forOperator(Operator::HORMUUD));
        $this->assertSame(['70'], Prefixes::forVirtualNetwork('durdur'));
        $this->assertSame([], Prefixes::forVirtualNetwork('Hormuud'));
    }

    public function test_it_lists_wallets_and_virtual_networks(): void
    {
        $this->assertSame(['Soltelco', 'Somnet', 'Durdur'], Prefixes::virtualNetworks());
        $this->assertContains('Zaad', Prefixes::wallets());
        $this->assertSame(array_values(array_unique(Prefixes::wallets())), Prefixes::wallets());
    }

    public function test_every_wallet_marked_as_waafi_exists_in_the_prefix_map(): void
    {
        foreach ([...Prefixes::WAAFI_WALLETS, ...Prefixes::UNCONFIRMED_WAAFI_WALLETS] as $wallet) {
            $this->assertContains($wallet, Prefixes::wallets());
        }

        $this->assertSame([], array_intersect(Prefixes::WAAFI_WALLETS, Prefixes::UNCONFIRMED_WAAFI_WALLETS));
    }

    public function test_operator_from_name_is_case_insensitive(): void
    {
        $this->assertSame(Operator::NATIONLINK, Operator::fromName('nationlink'));

        $this->expectException(\ValueError::class);
        $this->expectExceptionMessage('Unknown Somali operator "Safaricom". Use one of: Amtel, Golis, Hormuud, NationLink, Somtel, Telesom.');
        Operator::fromName('Safaricom');
    }
}
