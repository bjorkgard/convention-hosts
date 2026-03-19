<?php

return [
    'start' => [
        'forbidden' => 'Endast sammankomstansvariga kan starta närvarorapporter.',
        'success' => 'Närvarorapport startad för :period-perioden.',
    ],
    'stop' => [
        'forbidden' => 'Endast sammankomstansvariga kan stoppa närvarorapporter.',
        'success' => 'Närvarorapporten har låsts.',
    ],
    'report' => [
        'success' => 'Närvaro på :attendance rapporterad för :period-perioden.',
    ],
    'banner' => [
        'sections_reported' => ':reported av :total sektioner rapporterade',
        'total_attendance' => 'Total närvaro: :count',
        'stop_button' => 'Stoppa närvarorapport',
        'stop_tooltip' => 'Lås denna period och sluta samla in närvaro',
    ],
    'incomplete_dialog' => [
        'title' => 'Ofullständig rapport',
        'description' => 'Endast :reported av :total sektioner har rapporterat närvaro. Om du stoppar nu låses denna period och inga fler uppdateringar kan göras.',
        'cancel' => 'Avbryt',
        'confirm' => 'Stoppa ändå',
    ],
    'card' => [
        'title' => 'Närvarorapport',
        'reported' => 'Rapporterad: :count',
        'not_reported' => 'Ännu inte rapporterad',
        'input_label' => 'Närvaro (:period)',
        'input_placeholder' => 'Ange antal närvarande',
        'update' => 'Uppdatera',
        'send' => 'Skicka',
    ],
];
