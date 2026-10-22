<?php

declare(strict_types=1);

namespace NovoConnect\SomaliPhone\Laravel;

use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Factory as ValidationFactory;
use Illuminate\Validation\Validator;
use NovoConnect\SomaliPhone\Operator;
use NovoConnect\SomaliPhone\SomaliPhone;

class SomaliPhoneServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/../../lang', 'somali-phone');

        $this->publishes([
            __DIR__ . '/../../lang' => $this->app->langPath('vendor/somali-phone'),
        ], 'somali-phone-lang');

        $this->callAfterResolving('validator', fn (ValidationFactory $validator) => $this->registerStringRule($validator));
    }

    /**
     * 'somali_phone' or 'somali_phone:Hormuud,Telesom' to restrict by operator.
     */
    private function registerStringRule(ValidationFactory $validator): void
    {
        $validator->extend('somali_phone', function (string $attribute, mixed $value, array $parameters): bool {
            // Check the operator names first so a typo fails on any input.
            $allowed = self::operatorsFrom($parameters);

            $number = is_scalar($value) ? SomaliPhone::tryParse((string) $value) : null;

            if ($number === null) {
                return false;
            }

            return $allowed === [] || in_array($number->operator(), $allowed, true);
        });

        $validator->replacer('somali_phone', function (string $message, string $attribute, string $rule, array $parameters, Validator $validator): string {
            $allowed = Messages::join(array_map(fn (Operator $operator) => $operator->value, self::operatorsFrom($parameters)));

            // The app has its own message for this rule.
            if ($message !== 'validation.somali_phone') {
                return str_replace(':allowed', $allowed, $message);
            }

            return Messages::get($parameters === [] ? 'invalid' : 'operator', [
                'attribute' => $validator->getDisplayableAttribute($attribute),
                'allowed'   => $allowed,
            ]);
        });
    }

    /**
     * @param  array<mixed>  $parameters  The rule's parameters, e.g. ["Hormuud", "Telesom"].
     * @return list<Operator>
     */
    private static function operatorsFrom(array $parameters): array
    {
        $operators = [];

        foreach ($parameters as $name) {
            if (! is_string($name)) {
                throw new \ValueError('The somali_phone rule expects operator names, e.g. somali_phone:Hormuud,Telesom.');
            }

            $operators[] = Operator::fromName($name);
        }

        return $operators;
    }
}
