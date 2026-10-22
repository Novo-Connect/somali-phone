<?php

declare(strict_types=1);

namespace NovoConnect\SomaliPhone;

/**
 * Licensed operators in the NCA national numbering plan.
 */
enum Operator: string
{
    case AMTEL = 'Amtel';
    case GOLIS = 'Golis';
    case HORMUUD = 'Hormuud';
    case NATIONLINK = 'NationLink';
    case SOMTEL = 'Somtel';
    case TELESOM = 'Telesom';

    public static function fromName(string $name): self
    {
        foreach (self::cases() as $case) {
            if (strcasecmp($case->value, $name) === 0) {
                return $case;
            }
        }

        $known = implode(', ', array_map(fn (self $case) => $case->value, self::cases()));

        throw new \ValueError("Unknown Somali operator \"{$name}\". Use one of: {$known}.");
    }
}
