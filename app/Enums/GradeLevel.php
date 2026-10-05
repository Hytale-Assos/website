<?php

namespace App\Enums;

/**
 * School grade levels a member can be enrolled in.
 *
 * The values are the codes stored (encrypted) on the user. The list is
 * provisional: cases are added or adjusted as the official grade list
 * is confirmed.
 */
enum GradeLevel: string
{
    case FirstI = '1i';

    case SecondI = '2i';

    case FirstA = '1A';

    case SecondA = '2A';

    case FirstJ = '1J';

    case SecondJ = '2J';

    /**
     * Options usable in a select input, in school progression order.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $level) => ['value' => $level->value, 'label' => $level->value],
            self::cases(),
        );
    }
}
