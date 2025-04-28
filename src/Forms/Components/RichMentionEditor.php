<?php

namespace Asmit\FilamentMention\Forms\Components;

use Asmit\FilamentMention\Helpers\Helper;
use Closure;
use Exception;
use Filament\Forms\Components\RichEditor;

/**
 * Class RichMentionEditor
 *
 * A custom RichEditor component with mention functionality.
 */
class RichMentionEditor extends RichEditor
{
    protected string $view = 'asmit-filament-mention::forms.components.rich-mention';

    /**
     * @var array<string, mixed>|Closure The mentionable items, either as an array or a closure.
     */
    protected array|Closure $mentionItems = [];

    /**
     * @var string|array|null The trigger character(s) for mentions.
     */
    protected string|array|null $triggerWith = null;

    /**
     * @var string|null|Closure The key to pluck from mentionable items.
     */
    protected string|null|Closure $pluck = null;

    /**
     * @var string|null The avatar key for mentionable items.
     */
    protected ?string $avatar = null;

    /**
     * @var string The regex pattern for extracting mentions.
     */
    private const MENTION_PATTERN = '/\(--([a-zA-Z0-9_\.]+)--\)/';

    /**
     * @var int|null The minimum input length to show the mention menu.
     */
    protected ?int $menuShowMinLength = null;

    /**
     * @var string|null The lookup key for mentionable items.
     */
    protected ?string $lookupKey = null;

    /**
     * @var bool Whether to display the name of the mentionable item.
     */
    protected bool $displayName = true;

    /**
     * @var int|null The maximum number of items to show in the mention menu.
     */
    protected ?int $menuItemLimit = null;

    protected string $stateKey = 'data';

    /**
     * @var array|null The configuration for specific triggers.
     */
    protected ?array $triggerConfigs = null;

    /**
     * @var string|null The prefix to add before the mention.
     */
    protected ?string $prefix = null;

    /**
     * @var string|null The suffix to add after the mention.
     */
    protected ?string $suffix = null;

    /**
     * @var string|null The field to use for the title in the mention dropdown.
     */
    protected ?string $titleField = null;

    /**
     * @var string|null The field to use for the hint in the mention dropdown.
     */
    protected ?string $hintField = null;

    /**
     * Set the mentionable items.
     *
     * @param array<string, mixed>|Closure $mentionsItems The mentionable items as an array or a closure.
     *
     * @return $this
     */
    public function mentionsItems(array|Closure $mentionsItems): static
    {
        $this->mentionItems = $mentionsItems;

        return $this;
    }

    public function stateKey(string $stateKey): static
    {
        $this->stateKey = $stateKey;

        return $this;
    }

    /**
     * Dehydrate the state of the component.
     *
     * @param array<string, mixed> $state        The current state of the component.
     * @param bool                 $isDehydrated Whether the state is dehydrated.
     *
     * @return void
     */
    public function dehydrateState(array &$state, bool $isDehydrated = true): void
    {
        $key = array_key_first($state);

        if ($key === $this->stateKey) {
            $this->dehydrateData($state);

            return;
        }

        $this->dehydrateMountedActionsData($state, $key);
    }

    /**
     * Dehydrate the `data` key in the state.
     *
     * @param array<string, mixed> $state The current state of the component.
     *
     * @return void
     */
    public function dehydrateData(array &$state): void
    {
        $fieldName = $this->getName();
        $rawState = $state[$this->stateKey][$fieldName];

        if (! blank($this->getPluck())) {
            $mentions = $this->extractMentions($rawState);
            $this->updateStateWithMentions($state, $mentions);
        }

        $cleanedText = $this->removeAppendedExtraTextFromState($rawState);
        $this->updateState($state, $cleanedText);
        $this->processState($state[$this->stateKey], $this->getName());
    }

    /**
     * Update the state with extracted mentions.
     *
     * @param array<string, mixed>          $state    The current state of the component.
     * @param array<int|string, int|string> $mentions The extracted mentions.
     *
     * @return void
     */
    private function updateStateWithMentions(array &$state, array $mentions): void
    {
        $mentionKey = 'mentions_' . $this->getName();
        $this->getLivewire()->{$this->stateKey}[$mentionKey] = $mentions; // @phpstan-ignore-line
        $state[$this->stateKey][$mentionKey] = $mentions;
    }

    /**
     * Update the state with cleaned text.
     *
     * @param array<string, mixed> $state       The current state of the component.
     * @param string|null          $cleanedText The cleaned text without appended extra data.
     *
     * @return void
     */
    private function updateState(array &$state, string|null $cleanedText): void
    {

        $fieldName = $this->getName();
        $this->getLivewire()->{$this->stateKey}[$fieldName] = $cleanedText; // @phpstan-ignore-line
        $state[$this->stateKey][$fieldName] = $cleanedText;
    }

    /**
     * Dehydrate mounted actions data in the state.
     *
     * @param array<string, mixed> $state                The current state of the component.
     * @param string|null          $keyOfMountActionData The key of the mounted action data.
     *
     * @return void
     */
    public function dehydrateMountedActionsData(array &$state, ?string $keyOfMountActionData): void
    {
        if ($keyOfMountActionData) {
            $this->processState($state[$keyOfMountActionData][0], $this->getName());
        }
    }

    /**
     * Process the state by extracting mentions and cleaning text.
     *
     * @param array<string, mixed> $state The current state of the component.
     * @param string               $key   The key to process in the state.
     *
     * @return void
     */
    private function processState(array &$state, string $key): void
    {
        $rawState = $state[$key];

        if (!blank($this->getPluck())) {
            $mentions = $this->extractMentions($rawState);
            $state['mentions_' . $this->getName()] = $mentions;
        }

        $cleanedText = $this->removeAppendedExtraTextFromState($rawState);
        $state[$key] = $cleanedText;
    }

    /**
     * Remove appended extra text (e.g., IDs) from the state.
     *
     * @param string|null $text The text to clean.
     *
     * @return string|null The cleaned text.
     */
    private function removeAppendedExtraTextFromState(?string $text): ?string
    {
        return preg_replace(self::MENTION_PATTERN, '', $text ?? '');
    }

    /**
     * Extract mentions from the text using a regex pattern.
     *
     * @param string|null $text The text to extract mentions from.
     *
     * @return array<int, mixed> An array of extracted mentions.
     */
    private function extractMentions(?string $text): array
    {
        preg_match_all(self::MENTION_PATTERN, $text ?? '', $matches);

        /**
         * Return array of mentions
         * $matches[1] contains all @usernames
         */
        return array_unique($matches[1]);
    }

    /**
     * Get mentionable items based on the input.
     *
     * @param string $input The input text to filter mentionable items.
     *
     * @return array<string|int, mixed> An array of mentionable items.
     */
    public function getMentionableItems(string $input = ''): array
    {
        if ($this->mentionItems instanceof Closure) {
            return ($this->mentionItems)($input);
        }

        if (blank($this->mentionItems)) {
            $this->mentionItems = (resolve(config('filament-mention.mentionable.model')))->query()
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $id = $item->{config('filament-mention.mentionable.column.id')},
                        'name' => $item->{config('filament-mention.mentionable.column.display_name')},
                        'username' => $item->{config('filament-mention.mentionable.column.username')},
                        'avatar' => $item->{config('filament-mention.mentionable.column.avatar')},
                        'url' => Helper::getResolvedUrl($id),
                    ];
                })
                ->toArray();
        }

        return $this->mentionItems;
    }

    /**
     * Set the trigger character(s) for mentions.
     *
     * @param string|array $key The trigger character(s).
     *
     * @return $this
     */
    public function triggerWith(string|array $key): static
    {
        $this->triggerWith = $key;

        return $this;
    }

    /**
     * Set the key to pluck from mentionable items.
     *
     * @param string|Closure $key The key to pluck.
     *
     * @return $this
     */
    public function pluck(string|Closure $key): static
    {
        $this->pluck = $key;

        return $this;
    }

    /**
     * Get the pluck key.
     *
     * @return string|null The pluck key.
     */
    public function getPluck(): ?string
    {
        return $this->evaluate($this->pluck);
    }

    /**
     * Set the avatar key for mentionable items.
     *
     * @param string $key The avatar key.
     *
     * @return $this
     */
    public function avatar(string $key): static
    {
        $this->avatar = $key;

        return $this;
    }

    /**
     * Get the avatar key or default avatar.
     *
     * @return string|null The avatar key or default avatar.
     * @throws Exception
     */
    public function getAvatar(): ?string
    {
        return $this->evaluate($this->avatar) ?? config('filament-mention.default.avatar');
    }

    /**
     * Set the minimum length of input to show the mention menu.
     *
     * @param int $length The minimum input length.
     *
     * @return $this
     */
    public function menuShowMinLength(int $length = 2): self
    {
        $this->menuShowMinLength = $length;

        return $this;
    }

    /**
     * Get the minimum length of input to show the mention menu.
     *
     * @return int The minimum input length.
     */
    public function getMenuShowMinLength(): int
    {
        return $this->menuShowMinLength ?? config('filament-mention.default.menu_show_min_length');
    }

    /**
     * Set the lookup key for mentionable items.
     *
     * @param string $key The lookup key.
     *
     * @return $this
     */
    public function lookupKey(string $key): self
    {
        $this->lookupKey = $key;

        return $this;
    }

    /**
     * Get the lookup key for mentionable items.
     *
     * @return string|null The lookup key.
     */
    public function getLookupKey(): ?string
    {
        return $this->lookupKey ?? config('filament-mention.mentionable.lookup_key');
    }

    /**
     * Set the maximum number of items to show in the mention menu.
     *
     * @param int $limit The maximum number of items.
     *
     * @return $this
     */
    public function menuItemLimit(int $limit): self
    {
        $this->menuItemLimit = $limit;

        return $this;
    }

    /**
     * Get the maximum number of items to show in the mention menu.
     *
     * @return int|null The maximum number of items.
     */
    public function getMenuItemLimit(): ?int
    {
        return $this->menuItemLimit ?? config('filament-mention.default.menu_item_limit');
    }

    /**
     * Get the trigger character(s) for mentions.
     *
     * @return string|array|null The trigger character(s).
     */
    public function getTriggerWith(): array|string|null
    {
        return $this->triggerWith ?? config('filament-mention.default.trigger_with');
    }

    /**
     * Set the configuration for specific triggers.
     *
     * @param array $configs The configuration for specific triggers.
     *
     * @return $this
     */
    public function triggerConfigs(array $configs): static
    {
        $this->triggerConfigs = $configs;

        return $this;
    }

    /**
     * Get the configuration for specific triggers.
     *
     * @return array|null The configuration for specific triggers.
     */
    public function getTriggerConfigs(): ?array
    {
        return $this->triggerConfigs ?? config('filament-mention.default.trigger_configs', []);
    }

    /**
     * Set the prefix to add before the mention.
     *
     * @param string $prefix The prefix to add.
     *
     * @return $this
     */
    public function prefix(string $prefix): static
    {
        $this->prefix = $prefix;

        return $this;
    }

    /**
     * Get the prefix to add before the mention.
     *
     * @return string|null The prefix to add.
     */
    public function getPrefix(): ?string
    {
        return $this->prefix ?? config('filament-mention.default.prefix', '');
    }

    /**
     * Set the suffix to add after the mention.
     *
     * @param string $suffix The suffix to add.
     *
     * @return $this
     */
    public function suffix(string $suffix): static
    {
        $this->suffix = $suffix;

        return $this;
    }

    /**
     * Get the suffix to add after the mention.
     *
     * @return string|null The suffix to add.
     */
    public function getSuffix(): ?string
    {
        return $this->suffix ?? config('filament-mention.default.suffix', '');
    }

    /**
     * Set the field to use for the title in the mention dropdown.
     *
     * @param string $field The field to use for the title.
     *
     * @return $this
     */
    public function titleField(string $field): static
    {
        $this->titleField = $field;

        return $this;
    }

    /**
     * Get the field to use for the title in the mention dropdown.
     *
     * @return string|null The field to use for the title.
     */
    public function getTitleField(): ?string
    {
        return $this->titleField ?? config('filament-mention.default.title_field', 'name');
    }

    /**
     * Set the field to use for the hint in the mention dropdown.
     *
     * @param string $field The field to use for the hint.
     *
     * @return $this
     */
    public function hintField(string $field): static
    {
        $this->hintField = $field;

        return $this;
    }

    /**
     * Get the field to use for the hint in the mention dropdown.
     *
     * @return string|null The field to use for the hint.
     */
    public function getHintField(): ?string
    {
        return $this->hintField ?? config('filament-mention.default.hint_field');
    }
}
