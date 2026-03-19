<?php

return [
    'invitation' => [
        'subject' => 'Inbjudan till :convention',
        'greeting' => 'Hej :name,',
        'body' => 'Du har blivit inbjuden att gå med i **:convention**.',
        'action_description' => 'Klicka på knappen nedan för att ange ditt lösenord och aktivera ditt konto.',
        'button' => 'Acceptera inbjudan',
        'expiry' => 'Denna inbjudningslänk går ut den **:date**.',
        'thanks' => 'Tack,',
    ],
    'confirmation' => [
        'subject' => 'Bekräfta din e-postadress',
        'greeting' => 'Hej :name,',
        'body' => 'Bekräfta din e-postadress genom att klicka på knappen nedan.',
        'button' => 'Bekräfta e-postadress',
        'expiry' => 'Denna bekräftelselänk går ut den **:date**.',
        'no_action' => 'Om du inte begärde denna ändring behöver du inte göra något.',
        'thanks' => 'Tack,',
    ],
    'guest_verification' => [
        'subject' => 'Verifiera din e-post för :convention',
        'greeting' => 'Hej :name,',
        'body' => 'Din sammankomst **:convention** har skapats framgångsrikt.',
        'action_description' => 'Klicka på knappen nedan för att verifiera din e-post och ange ditt lösenord.',
        'button' => 'Verifiera e-post & ange lösenord',
        'expiry' => 'Denna verifieringslänk går ut den **:date**.',
        'thanks' => 'Tack,',
    ],
];
