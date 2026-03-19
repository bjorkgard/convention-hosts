<?php

return [
    'index' => [
        'title' => 'Våningar — :convention',
        'heading' => 'Våningar',
        'description' => 'Organisera din plats i våningar och sektioner. Expandera en våning för att visa eller hantera dess sektioner.',
        'add_section_button' => 'Lägg till sektion',
        'add_section_short' => 'Sektion',
        'add_section_tooltip' => 'Lägg till en ny sittsektion på en våning',
        'add_floor_button' => 'Lägg till våning',
        'add_floor_short' => 'Lägg till',
        'add_floor_tooltip' => 'Lägg till en ny våning i lokalen',
        'empty' => 'Inga våningar ännu.',
        'empty_add' => 'Lägg till din första våning',
    ],
    'add_dialog' => [
        'title' => 'Lägg till våning',
        'description' => 'Lägg till en ny våning till :convention.',
        'name_label' => 'Våningsnamn',
        'name_placeholder' => 't.ex. Bottenvåning',
        'cancel' => 'Avbryt',
        'submit' => 'Lägg till våning',
        'submitting' => 'Lägger till...',
    ],
    'edit_dialog' => [
        'title' => 'Redigera våning',
        'description' => 'Uppdatera våningsnamnet.',
        'name_label' => 'Våningsnamn',
        'cancel' => 'Avbryt',
        'submit' => 'Spara',
        'submitting' => 'Sparar...',
    ],
    'delete_dialog' => [
        'title' => 'Radera våning',
        'description' => 'Är du säker på att du vill radera ":name"? Alla sektioner på denna våning kommer också att raderas. Denna åtgärd kan inte ångras.',
        'confirm' => 'Radera våning',
    ],
    'row' => [
        'section_count' => '{1} :count sektion|[2,*] :count sektioner',
        'no_sections' => 'Inga sektioner på denna våning.',
        'seats' => ':available/:total platser',
        'edit_tooltip' => 'Byt namn på denna våning',
        'delete_tooltip' => 'Radera denna våning och alla dess sektioner',
        'edit_section_tooltip' => 'Redigera sektionsdetaljer',
        'delete_section_tooltip' => 'Radera denna sektion',
    ],
];
