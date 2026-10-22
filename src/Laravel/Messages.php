<?php

declare(strict_types=1);

namespace NovoConnect\SomaliPhone\Laravel;

use Illuminate\Contracts\Translation\Translator;

/**
 * @internal
 */
final class Messages
{
    /**
     * Falls back to English when neither the app locale nor its fallback
     * locale has a translation.
     *
     * @param  array<string, string>  $replace
     */
    public static function get(string $key, array $replace = []): string
    {
        $line = "somali-phone::validation.{$key}";

        /** @var Translator&\Illuminate\Translation\Translator $translator */
        $translator = app('translator');

        $message = $translator->has($line)
            ? $translator->get($line, $replace)
            : $translator->get($line, $replace, 'en');

        return is_string($message) ? $message : $line;
    }

    /**
     * "Hormuud, Golis or Telesom", or "... ama Telesom" in Somali.
     *
     * @param  list<string>  $items
     */
    public static function join(array $items): string
    {
        if (count($items) < 2) {
            return implode('', $items);
        }

        $last = array_pop($items);

        return implode(', ', $items) . ' ' . self::get('or') . ' ' . $last;
    }
}
