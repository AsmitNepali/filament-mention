<?php

namespace Asmit\FilamentMention\Forms\Components;

use Asmit\FilamentMention\Helpers\Helper;
use Filament\Forms\Components\RichEditor;

class RichMentionEditor extends RichEditor
{
    protected string $view = 'asmit-filament-mention::forms.components.rich-mention';

    /**
     * @var array<string, mixed>|\Closure
     */
    protected array|\Closure $mentionItems = [];

    protected string $modelClass;

    protected string $triggerWith = '@';

    protected string|null|\Closure $pluck = null;

    protected ?string $avatar = null;

    private string $pattern = '/\(--([a-zA-Z0-9_\.]+)--\)/';

    protected ?int $menuShowMinLength = null;

    protected ?string $lookupKey = null;

    protected bool $displayName = true;

    protected ?int $menuItemLimit = null;

    protected function setUp(): void
    {
        $this->afterStateUpdated(
            function (string $state, callable $set, self $component) {

                $mentions = $this->extractMentions($state);
                $mentionKey = 'mentions_'.$this->getName();

                if ($this->getPluck()) {
                    $component->state([
                        'state' => $this->removeAppendedExtraTextFromState($state),
                        $mentionKey => $mentions,
                    ]);
                } else {
                    $component->state($this->removeAppendedExtraTextFromState($state));
                }
            }
        );

    }

    /**
     * @param  array<string, mixed>|\Closure  $mentionsItems
     * @return $this
     */
    public function mentionsItems(array|\Closure $mentionsItems): static
    {
        $this->mentionItems = $mentionsItems;

        return $this;
    }

    /**
     * @param  array<string, mixed>  $state
     * @param  array<int|string, int|string>  $mentions
     */
    private function updateStateWithMentions(array &$state, array $mentions): void
    {
        $mentionKey = 'mentions_'.$this->getName();
        $this->getLivewire()->data[$mentionKey] = $mentions; // @phpstan-ignore-line
        $state['data'][$mentionKey] = $mentions;
    }

    /**
     * @param  array<string, mixed>  $state
     */
    private function updateState(array &$state, ?string $cleanedText): void
    {

        $fieldName = $this->getName();
        $this->getLivewire()->data[$fieldName] = $cleanedText; // @phpstan-ignore-line
        $state['data'][$fieldName] = $cleanedText;
    }

    private function removeAppendedExtraTextFromState(?string $text): ?string
    {
        return preg_replace($this->pattern, '', $text ?? '');
    }

    /**
     * @return array<int, mixed>
     */
    private function extractMentions(?string $text): array
    {
        preg_match_all($this->pattern, $text ?? '', $matches);

        /**
         * Return array of mentions
         * $matches[1] contains all @usernames
         */
        return array_unique($matches[1]);
    }

    /**
     * @return array<string|int, mixed>
     */
    public function getMentionableItems(string $input = ''): array
    {
        if ($this->mentionItems instanceof \Closure) {
            return ($this->mentionItems)($input);
        }
        if (blank($this->mentionItems)) {
            $this->mentionItems = (resolve(config('filament-mention.mentionable.model')))
                ->query()->get()->map(function ($item) {
                    return [
                        'id' => $id = $item->{config('filament-mention.mentionable.column.id')},
                        'name' => $item->{config('filament-mention.mentionable.column.display_name')},
                        'username' => $item->{config('filament-mention.mentionable.column.username')},
                        'avatar' => $item->{config('filament-mention.mentionable.column.avatar')},
                        'url' => Helper::getResolvedUrl($id),
                    ];
                })->toArray();
        }

        return $this->mentionItems;
    }

    public function triggerWith(): string
    {
        return $this->triggerWith;
    }

    /**
     * @return $this
     *               The key must be included in the mentionsItems array
     */
    public function pluck(string|\Closure $key): static
    {
        $this->pluck = $key;

        return $this;
    }

    public function getPluck(): ?string
    {
        return $this->evaluate($this->pluck);
    }

    public function avatar(string $key): static
    {
        $this->avatar = $key;

        return $this;
    }

    /**
     * @throws \Exception
     */
    public function getAvatar(): ?string
    {
        return $this->evaluate($this->avatar) ?? config('filament-mention.default.avatar');
    }

    public function menuShowMinLength(int $length = 2): self
    {
        $this->menuShowMinLength = $length;

        return $this;
    }

    public function getMenuShowMinLength(): int
    {
        return $this->menuShowMinLength ?? config('filament-mention.default.menu_show_min_length');
    }

    public function lookupKey(string $key): self
    {
        $this->lookupKey = $key;

        return $this;
    }

    public function getLookupKey(): ?string
    {
        return $this->lookupKey ?? config('filament-mention.mentionable.lookup_key');
    }

    public function menuItemLimit(int $limit): self
    {
        $this->menuItemLimit = $limit;

        return $this;
    }

    public function getMenuItemLimit(): ?int
    {
        return $this->menuItemLimit ?? config('filament-mention.default.menu_item_limit');
    }
}
