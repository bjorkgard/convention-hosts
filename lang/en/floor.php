<?php

return [
    'index' => [
        'title' => 'Floors — :convention',
        'heading' => 'Floors',
        'description' => 'Organize your venue into floors and sections. Expand a floor to view or manage its sections.',
        'add_section_button' => 'Add Section',
        'add_section_short' => 'Section',
        'add_section_tooltip' => 'Add a new seating section to a floor',
        'add_floor_button' => 'Add Floor',
        'add_floor_short' => 'Add',
        'add_floor_tooltip' => 'Add a new floor level to the venue',
        'empty' => 'No floors yet.',
        'empty_add' => 'Add your first floor',
    ],
    'add_dialog' => [
        'title' => 'Add Floor',
        'description' => 'Add a new floor to :convention.',
        'name_label' => 'Floor Name',
        'name_placeholder' => 'e.g. Ground Floor',
        'cancel' => 'Cancel',
        'submit' => 'Add Floor',
        'submitting' => 'Adding...',
    ],
    'edit_dialog' => [
        'title' => 'Edit Floor',
        'description' => 'Update the floor name.',
        'name_label' => 'Floor Name',
        'cancel' => 'Cancel',
        'submit' => 'Save',
        'submitting' => 'Saving...',
    ],
    'delete_dialog' => [
        'title' => 'Delete Floor',
        'description' => 'Are you sure you want to delete ":name"? All sections on this floor will also be deleted. This action cannot be undone.',
        'confirm' => 'Delete floor',
    ],
    'row' => [
        'section_count' => '{1} :count section|[2,*] :count sections',
        'no_sections' => 'No sections on this floor.',
        'seats' => ':available/:total seats',
        'edit_tooltip' => 'Rename this floor',
        'delete_tooltip' => 'Delete this floor and all its sections',
        'edit_section_tooltip' => 'Edit section details',
        'delete_section_tooltip' => 'Delete this section',
    ],
];
