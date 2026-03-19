<?php

return [
    'start' => [
        'forbidden' => 'Only convention managers can start attendance reports.',
        'success' => 'Attendance report started for :period period.',
    ],
    'stop' => [
        'forbidden' => 'Only convention managers can stop attendance reports.',
        'success' => 'Attendance report has been locked.',
    ],
    'report' => [
        'success' => 'Attendance of :attendance reported for the :period period.',
    ],
    'banner' => [
        'sections_reported' => ':reported of :total sections reported',
        'total_attendance' => 'Total attendance: :count',
        'stop_button' => 'Stop attendance report',
        'stop_tooltip' => 'Lock this period and stop collecting attendance',
    ],
    'incomplete_dialog' => [
        'title' => 'Incomplete Report',
        'description' => 'Only :reported of :total sections have reported attendance. Stopping now will lock this period and no further updates can be made.',
        'cancel' => 'Cancel',
        'confirm' => 'Stop anyway',
    ],
    'card' => [
        'title' => 'Attendance Report',
        'reported' => 'Reported: :count',
        'not_reported' => 'Not yet reported',
        'input_label' => 'Attendance (:period)',
        'input_placeholder' => 'Enter attendance count',
        'update' => 'Update',
        'send' => 'Send',
    ],
];
