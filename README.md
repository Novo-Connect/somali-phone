# Somali Phone

[![Tests](https://github.com/Novo-Connect/somali-phone/actions/workflows/tests.yml/badge.svg)](https://github.com/Novo-Connect/somali-phone/actions/workflows/tests.yml)
[![Latest Version](https://img.shields.io/packagist/v/novo-connect/somali-phone.svg)](https://packagist.org/packages/novo-connect/somali-phone)
[![License](https://img.shields.io/packagist/l/novo-connect/somali-phone.svg)](LICENSE)

Validate and normalize Somali mobile numbers, and find out which operator and mobile money wallet they belong to.

Works with PHP 8.1+, and comes with a validation rule and an Eloquent cast for Laravel 10 to 13.

## Installation

```bash
composer require novo-connect/somali-phone
```

## Usage

```php
use NovoConnect\SomaliPhone\SomaliPhone;

$number = SomaliPhone::parse('063 412 3456');

$number->msisdn();          // "252634123456"
$number->e164();            // "+252634123456"
$number->local();           // "0634123456"
$number->format();          // "+252 63 412 3456"

$number->operator();        // Operator::TELESOM
$number->networkName();     // "Telesom"
$number->wallet();          // "Zaad"
$number->supportsWaafi();   // true
```

Numbers can be written with or without the country code, with spaces, dashes or brackets, and in Arabic-Indic digits. `parse()` throws an `InvalidPhoneNumber` exception for anything else, while `tryParse()` returns `null`.

```php
SomaliPhone::isValid('0644123456');      // false, 64 is not in use
SomaliPhone::normalize('+252634123456'); // "252634123456"

SomaliPhone::parse('0634123456')->equals('+252 63 412 3456'); // true
```

Some prefixes run a virtual network on top of an operator. For those, `virtualNetwork()` returns the network and `networkName()` returns it instead of the operator:

```php
$number = SomaliPhone::parse('0702000001');

$number->operator();        // Operator::GOLIS
$number->virtualNetwork();  // "Durdur"
$number->wallet();          // "Kayd"
```

### WAAFI

`supportsWaafi()` is only true for wallets listed in the [WaafiPay docs](https://docs.waafipay.com): EVC Plus, Zaad and Sahal. Jeeb, Kaash Plus and Kayd may also work with WAAFI, but we couldn't confirm it, so they return `false` and `isWaafiUnconfirmed()` returns `true`. If you know of an official source, please open an issue.

## Laravel

The service provider is registered automatically.

### Validation

```php
use NovoConnect\SomaliPhone\Laravel\Rules\SomaliPhoneNumber;
use NovoConnect\SomaliPhone\Operator;

$request->validate([
    'phone'     => ['required', new SomaliPhoneNumber],
    'evc_phone' => ['required', (new SomaliPhoneNumber)->operators(Operator::HORMUUD)],
    'wallet'    => ['required', (new SomaliPhoneNumber)->wallets('Zaad', 'Sahal')],
    'payout'    => ['required', (new SomaliPhoneNumber)->waafi()],
]);
```

You can also filter by `virtualNetworks('Somnet')`. Or use the string rule:

```php
'phone' => 'required|somali_phone',
'phone' => 'required|somali_phone:Hormuud,Telesom',
```

Error messages are in English and Somali. Run `php artisan vendor:publish --tag=somali-phone-lang` to change them.

### Eloquent cast

```php
use NovoConnect\SomaliPhone\Laravel\Casts\AsSomaliPhone;

protected $casts = [
    'phone' => AsSomaliPhone::class,
];
```

The number is saved as `252634123456` and you get a `PhoneNumber` back when you read it. Assigning an invalid number throws an exception, so validate first. Since the column is normalized, normalize input before querying it too:

```php
Customer::where('phone', SomaliPhone::normalize($request->phone))->first();
```

## Prefixes

| Prefix         | Operator   | Virtual network | Wallet     |
|----------------|------------|-----------------|------------|
| 61, 77, 78     | Hormuud    |                 | EVC Plus   |
| 68             | Hormuud    | Somnet          | Jeeb       |
| 63             | Telesom    |                 | Zaad       |
| 67             | Telesom    | Soltelco        | Kaash Plus |
| 60, 72, 90     | Golis      |                 | Sahal      |
| 70             | Golis      | Durdur          | Kayd       |
| 62, 65, 66, 76 | Somtel     |                 | eDahab     |
| 69             | NationLink |                 | e-Maal     |
| 71, 75         | Amtel      |                 | MyCash     |

Based on the NCA numbering plan published through the ITU in December 2024. Prefix 90 isn't in that plan but is widely used by Golis. If something here is wrong or out of date, a pull request with a source is very welcome.

## Testing

```bash
composer test
```

## Credits

Created by [Zakaria Ibrahim](https://github.com/zakaria-ibr), maintained by [Novo Connect](https://novo.co.com). Questions go to [hello@novo.co.com](mailto:hello@novo.co.com).

## License

MIT. See [LICENSE](LICENSE).
