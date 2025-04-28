<?php

namespace Asmit\FilamentMention\Traits;

use Asmit\FilamentMention\Helpers\Helper;

/**
 * @phpstan-ignore-next-line
 */
trait HasMentionable
{
    public function getMentionableItems(?string $searchKey): array
    {
        return resolve(config('filament-mention.mentionable.model'))
            ->query()
            ->whereLike(config('filament-mention.mentionable.search_key'), "%$searchKey%")
            ->get()
            ->map(function ($mentionable) {
                return [
                    'id' => $id = $mentionable->{config('filament-mention.mentionable.column.id')},
                    'name' => $mentionable->{config('filament-mention.mentionable.column.display_name')},
                    'username' => $mentionable->{config('filament-mention.mentionable.column.username')},
                    'avatar' => $mentionable->{config('filament-mention.mentionable.column.avatar')},
                    'url' => Helper::getResolvedUrl($id),
                ];
            })
            ->toArray();
    }
}
