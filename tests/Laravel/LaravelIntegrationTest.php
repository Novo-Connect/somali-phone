<?php

declare(strict_types=1);

namespace NovoConnect\SomaliPhone\Tests\Laravel;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use NovoConnect\SomaliPhone\InvalidPhoneNumber;
use NovoConnect\SomaliPhone\Laravel\Casts\AsSomaliPhone;
use NovoConnect\SomaliPhone\Laravel\Rules\SomaliPhoneNumber;
use NovoConnect\SomaliPhone\Laravel\SomaliPhoneServiceProvider;
use NovoConnect\SomaliPhone\Operator;
use NovoConnect\SomaliPhone\PhoneNumber;
use Orchestra\Testbench\TestCase;

final class LaravelIntegrationTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [SomaliPhoneServiceProvider::class];
    }

    private function errorFor(mixed $value, mixed $rule): ?string
    {
        $validator = Validator::make(['phone' => $value], ['phone' => [$rule]]);

        return $validator->fails() ? $validator->errors()->first('phone') : null;
    }

    public function test_rule_accepts_any_valid_number(): void
    {
        $this->assertNull($this->errorFor('0634123456', new SomaliPhoneNumber));
        $this->assertNull($this->errorFor('+252 70 200 0001', new SomaliPhoneNumber));
    }

    public function test_rule_rejects_invalid_numbers(): void
    {
        $this->assertSame(
            'The phone must be a valid Somali mobile number.',
            $this->errorFor('0644123456', new SomaliPhoneNumber),
        );
        $this->assertNotNull($this->errorFor(['0634123456'], new SomaliPhoneNumber));
    }

    public function test_rule_restricts_by_operator(): void
    {
        $rule = (new SomaliPhoneNumber)->operators(Operator::HORMUUD, 'Golis');

        $this->assertNull($this->errorFor('0681234567', $rule)); // Somnet is on Hormuud
        $this->assertNull($this->errorFor('0702000001', $rule)); // Durdur is on Golis
        $this->assertSame(
            'The phone must be a number on Hormuud or Golis.',
            $this->errorFor('0634123456', $rule),
        );
    }

    public function test_rule_restricts_by_virtual_network(): void
    {
        $rule = (new SomaliPhoneNumber)->virtualNetworks('Soltelco');

        $this->assertNull($this->errorFor('0671234567', $rule));
        $this->assertSame('The phone must be a number on Soltelco.', $this->errorFor('0634123456', $rule));
    }

    public function test_rule_restricts_by_wallet(): void
    {
        $rule = (new SomaliPhoneNumber)->wallets('EVC Plus', 'Jeeb');

        $this->assertNull($this->errorFor('0615551234', $rule));
        $this->assertNull($this->errorFor('0681234567', $rule));
        $this->assertSame('The phone must be a number on EVC Plus or Jeeb.', $this->errorFor('0634123456', $rule));
    }

    public function test_rule_matches_wallet_and_network_names_case_insensitively(): void
    {
        $rule = (new SomaliPhoneNumber)->wallets('zaad');

        $this->assertNull($this->errorFor('0634123456', $rule));
        $this->assertSame('The phone must be a number on Zaad.', $this->errorFor('0615551234', $rule));
        $this->assertNull($this->errorFor('0681234567', (new SomaliPhoneNumber)->virtualNetworks('SOMNET')));
    }

    public function test_rule_rejects_unknown_wallet_names(): void
    {
        $this->expectException(\ValueError::class);
        $this->expectExceptionMessage('Unknown Somali wallet "Zad". Use one of:');

        (new SomaliPhoneNumber)->wallets('Zad');
    }

    public function test_rule_rejects_unknown_virtual_network_names(): void
    {
        $this->expectException(\ValueError::class);
        $this->expectExceptionMessage('Unknown Somali virtual network "Hormuud". Use one of: Soltelco, Somnet, Durdur.');

        (new SomaliPhoneNumber)->virtualNetworks('Hormuud');
    }

    public function test_rule_requires_waafi_support(): void
    {
        $rule = (new SomaliPhoneNumber)->waafi();
        $error = 'The phone must be on a wallet that supports WAAFI transfers.';

        $this->assertNull($this->errorFor('0615551234', $rule)); // EVC Plus
        $this->assertSame($error, $this->errorFor('0621234567', $rule)); // eDahab
        $this->assertSame($error, $this->errorFor('0702000001', $rule)); // Kayd, unconfirmed
    }

    public function test_string_rule(): void
    {
        $this->assertNull($this->errorFor('0634123456', 'somali_phone'));
        $this->assertNull($this->errorFor('0671234567', 'somali_phone:Telesom'));
        $this->assertSame(
            'The phone must be a valid Somali mobile number.',
            $this->errorFor('12345', 'somali_phone'),
        );
        $this->assertSame(
            'The phone must be a number on Telesom.',
            $this->errorFor('0615551234', 'somali_phone:Telesom'),
        );
    }

    public function test_string_rule_keeps_app_custom_messages(): void
    {
        $validator = Validator::make(
            ['phone' => 'nope'],
            ['phone' => 'somali_phone'],
            ['phone.somali_phone' => 'Custom message.'],
        );

        $this->assertSame('Custom message.', $validator->errors()->first('phone'));
    }

    public function test_string_rule_fills_allowed_in_custom_messages(): void
    {
        $validator = Validator::make(
            ['phone' => '0615551234'],
            ['phone' => 'somali_phone:telesom,golis'],
            ['phone.somali_phone' => 'Only :allowed numbers, please.'],
        );

        $this->assertSame('Only Telesom or Golis numbers, please.', $validator->errors()->first('phone'));
    }

    public function test_string_rule_uses_custom_attribute_names(): void
    {
        $validator = Validator::make(
            ['mobile_no' => 'nope'],
            ['mobile_no' => 'somali_phone'],
            [],
            ['mobile_no' => 'phone number'],
        );

        $this->assertSame('The phone number must be a valid Somali mobile number.', $validator->errors()->first('mobile_no'));
    }

    public function test_string_rule_normalizes_operator_names_in_messages(): void
    {
        $this->assertSame(
            'The phone must be a number on Hormuud or Telesom.',
            $this->errorFor('0702000001', 'somali_phone:hormuud,telesom'),
        );
    }

    public function test_string_rule_reports_unknown_operators_even_for_invalid_input(): void
    {
        $this->expectException(\ValueError::class);
        $this->expectExceptionMessage('Unknown Somali operator "Hormud"');

        $this->errorFor('nope', 'somali_phone:Hormud');
    }

    public function test_somali_messages_join_lists_with_ama(): void
    {
        $this->app->setLocale('so');

        $this->assertSame(
            'phone waa inuu noqdaa lambar Hormuud, Golis ama Telesom ah.',
            $this->errorFor('0621234567', (new SomaliPhoneNumber)->operators('Hormuud', 'Golis', 'Telesom')),
        );
        $this->assertSame(
            'phone waa inuu noqdaa lambar Hormuud ama Telesom ah.',
            $this->errorFor('0621234567', 'somali_phone:Hormuud,Telesom'),
        );
    }

    public function test_somali_translations_are_loaded(): void
    {
        $this->app->setLocale('so');

        $this->assertSame(
            'phone waa inuu noqdaa lambar mobile Soomaaliyeed oo sax ah.',
            $this->errorFor('nope', new SomaliPhoneNumber),
        );
    }

    public function test_messages_fall_back_to_english_for_other_locales(): void
    {
        $this->app->setLocale('fr');
        $this->app['translator']->setFallback('fr');

        $this->assertSame('The phone must be a valid Somali mobile number.', $this->errorFor('nope', new SomaliPhoneNumber));
        $this->assertSame(
            'The phone must be a number on Telesom or Golis.',
            $this->errorFor('0615551234', 'somali_phone:Telesom,Golis'),
        );
    }

    public function test_eloquent_cast(): void
    {
        $model = new class extends Model
        {
            protected $guarded = [];

            protected $casts = ['phone' => AsSomaliPhone::class];
        };

        $model->phone = '+252 63 412 3456';

        $this->assertSame('252634123456', $model->getAttributes()['phone']);
        $this->assertInstanceOf(PhoneNumber::class, $model->phone);
        $this->assertSame('Zaad', $model->phone->wallet());

        $model->phone = null;
        $this->assertNull($model->phone);

        $this->expectException(InvalidPhoneNumber::class);
        $model->phone = '0644123456';
    }

    public function test_eloquent_cast_serializes_as_a_plain_number(): void
    {
        $model = new class extends Model
        {
            protected $guarded = [];

            protected $casts = ['phone' => AsSomaliPhone::class];
        };

        $model->phone = '063 412 3456';

        $this->assertSame(['phone' => '252634123456'], $model->toArray());
        $this->assertSame('{"phone":"252634123456"}', $model->toJson());

        $model->phone = 615551234;
        $this->assertSame('252615551234', $model->getAttributes()['phone']);
    }

    public function test_eloquent_cast_rejects_values_that_are_not_numbers(): void
    {
        $model = new class extends Model
        {
            protected $guarded = [];

            protected $casts = ['phone' => AsSomaliPhone::class];
        };

        $this->expectException(InvalidPhoneNumber::class);
        $model->phone = ['0634123456'];
    }

    public function test_eloquent_cast_reads_invalid_stored_values_as_null(): void
    {
        $model = new class extends Model
        {
            protected $guarded = [];

            protected $casts = ['phone' => AsSomaliPhone::class];
        };

        $model->setRawAttributes(['phone' => 'legacy junk'], sync: true);

        $this->assertNull($model->phone);
        $this->assertSame('legacy junk', $model->getRawOriginal('phone'));
    }
}
