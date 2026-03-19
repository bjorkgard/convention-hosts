<?php

return [
    'index' => [
        'title' => 'Users — :convention',
        'heading' => 'Users',
        'description' => 'Manage who has access to this convention. Invite users and assign roles to control what they can see and do.',
        'add_button' => 'Add User',
        'add_short' => 'Add',
        'add_tooltip' => 'Invite a new user by email and assign their role',
        'empty' => 'No users yet.',
        'empty_invite' => 'Invite your first user',
    ],
    'add_dialog' => [
        'title' => 'Add User',
        'description' => 'Invite a new user to :convention.',
        'submit' => 'Invite User',
        'submitting' => 'Inviting...',
    ],
    'edit_dialog' => [
        'title' => 'Edit User',
        'description' => 'Update user details and roles.',
        'submit' => 'Save',
        'submitting' => 'Saving...',
    ],
    'form' => [
        'first_name_label' => 'First Name',
        'last_name_label' => 'Last Name',
        'email_label' => 'Email',
        'mobile_label' => 'Mobile',
        'roles_label' => 'Roles',
        'cancel' => 'Cancel',
    ],
    'row' => [
        'email_confirmed' => 'Email confirmed',
        'email_not_confirmed' => 'Email not yet confirmed',
        'email_already_confirmed' => 'Email already confirmed',
        'resend_invitation' => 'Resend invitation email',
        'resend_label' => 'Resend invitation',
        'edit_tooltip' => 'Edit user details and roles',
        'delete_tooltip' => 'Remove user from this convention',
        'edit_label' => 'Edit :name',
        'delete_label' => 'Delete :name',
    ],
    'delete_dialog' => [
        'title' => 'Remove User',
        'description' => 'Are you sure you want to remove :name from this convention? This action cannot be undone.',
        'confirm' => 'Remove',
    ],
    'roles' => [
        'owner' => 'Owner',
        'administrator' => 'Administrator',
        'owner_description' => 'Full admin access — can delete convention, export data, and manage everything',
        'administrator_description' => 'Convention-wide access — can manage all floors, sections, and users',
    ],
    'validation' => [
        'jwpub_domain' => 'The email address cannot contain jwpub.org domain.',
        'password_regex' => 'The password must contain at least one lowercase letter, one uppercase letter, one number, and one symbol (@$!%*#?&).',
    ],
];
