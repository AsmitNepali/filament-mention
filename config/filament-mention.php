<?php

return [
    'mentionable' => [
        'model' => \App\Models\User::class,
        'column' => [
            'id' => 'id',
            'display_name' => 'name',
            'username' => 'username',
            'avatar' => 'profile',
        ],
        'url' => 'admin/users/{id}',
        // this will be used to generate the url for the mention item
        'lookup_key' => 'username',
        // this will be used on static search
        'search_key' => 'username',
        // this will be used on dynamic search
    ],
    'default' => [
        'trigger_with' => [
            '@',
            '#',
            '%',
        ],
        'trigger_configs' => [
            '@' => [
                'lookupKey' => 'username',
                'prefix' => '',
                'suffix' => '',
                'titleField' => 'name',
                'hintField' => null,
            ],
            '#' => [
                'lookupKey' => 'username',
                'prefix' => '',
                'suffix' => '',
                'titleField' => 'name',
                'hintField' => null,
            ],
            '%' => [
                'lookupKey' => 'name',
                'prefix' => '%',
                'suffix' => '%',
                'titleField' => 'name',
                'hintField' => null,
            ],
        ],
        'menu_show_min_length' => 2,
        'menu_item_limit' => 10,
        'prefix' => '',
        'suffix' => '',
        'title_field' => 'name',
        'hint_field' => null,
    ],
];
