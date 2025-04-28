@use('Filament\Support\Facades\FilamentAsset')
<div
        id="add-list-{{ $getId() }}"
        ax-load
        ax-load-src="{{ FilamentAsset::getAlpineComponentSrc(id:'asmit-filament-mention', package: 'asmit/filament-mention') }}"
        x-load-css="[@js(FilamentAsset::getStyleHref(id:'asmit-filament-mention', package: 'asmit/filament-mention'))]"
        x-ignore
        x-data="mention({
    fieldName: '{{ $getId() }}',
    mentionableItems: {{ json_encode($getMentionableItems()) }},
    triggerWith: {{ json_encode($getTriggerWith()) }},
    pluck: '{{ $getPluck() }}',
    menuShowMinLength: '{{ $getMenuShowMinLength() }}',
    menuItemLimit: '{{ $getMenuItemLimit() }}',
    lookupKey: '{{ $getLookupKey() }}',
    loadingItemString: '{{ trans('asmit-filament-mention::translations.loading') }}',
    noResultsString: '{{ trans('asmit-filament-mention::translations.no_results') }}',
    triggerConfigs: {{ json_encode($getTriggerConfigs()) }},
    prefix: '{{ $getPrefix() }}',
    suffix: '{{ $getSuffix() }}',
    titleField: '{{ $getTitleField() }}',
    hintField: {{ $getHintField() ? "'".$getHintField()."'" : 'null' }}
    })"
>
    @include('filament-forms::components.rich-editor')
</div>
