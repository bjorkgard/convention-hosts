<?php

return [
    'index' => [
        'title' => 'Användare — :convention',
        'heading' => 'Användare',
        'description' => 'Hantera vem som har åtkomst till denna sammankomst. Bjud in användare och tilldela roller för att styra vad de kan se och göra.',
        'add_button' => 'Lägg till användare',
        'add_short' => 'Lägg till',
        'add_tooltip' => 'Bjud in en ny användare via e-post och tilldela deras roll',
        'empty' => 'Inga användare ännu.',
        'empty_invite' => 'Bjud in din första användare',
    ],
    'add_dialog' => [
        'title' => 'Lägg till användare',
        'description' => 'Bjud in en ny användare till :convention.',
        'submit' => 'Bjud in användare',
        'submitting' => 'Bjuder in...',
    ],
    'edit_dialog' => [
        'title' => 'Redigera användare',
        'description' => 'Uppdatera användaruppgifter och roller.',
        'submit' => 'Spara',
        'submitting' => 'Sparar...',
    ],
    'form' => [
        'first_name_label' => 'Förnamn',
        'last_name_label' => 'Efternamn',
        'email_label' => 'E-post',
        'mobile_label' => 'Mobil',
        'roles_label' => 'Roller',
        'cancel' => 'Avbryt',
    ],
    'row' => [
        'email_confirmed' => 'E-post bekräftad',
        'email_not_confirmed' => 'E-post ännu inte bekräftad',
        'email_already_confirmed' => 'E-post redan bekräftad',
        'resend_invitation' => 'Skicka inbjudningsmail igen',
        'resend_label' => 'Skicka inbjudan igen',
        'edit_tooltip' => 'Redigera användaruppgifter och roller',
        'delete_tooltip' => 'Ta bort användare från denna sammankomst',
        'edit_label' => 'Redigera :name',
        'delete_label' => 'Radera :name',
    ],
    'delete_dialog' => [
        'title' => 'Ta bort användare',
        'description' => 'Är du säker på att du vill ta bort :name från denna sammankomst? Denna åtgärd kan inte ångras.',
        'confirm' => 'Ta bort',
    ],
    'roles' => [
        'owner' => 'Ägare',
        'administrator' => 'Administratör',
        'owner_description' => 'Full administratörsåtkomst — kan radera sammankomst, exportera data och hantera allt',
        'administrator_description' => 'sammankomstomfattande åtkomst — kan hantera alla våningar, sektioner och användare',
    ],
    'validation' => [
        'jwpub_domain' => 'E-postadressen kan inte innehålla domänen jwpub.org.',
        'password_regex' => 'Lösenordet måste innehålla minst en liten bokstav, en stor bokstav, en siffra och en symbol (@$!%*#?&).',
    ],
];
