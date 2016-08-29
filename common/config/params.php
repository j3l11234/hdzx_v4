<?php
return [
    'adminEmail' => 'admin@example.com',
    'supportEmail' => 'support@example.com',
    'user.passwordResetTokenExpire' => 3600,
    'order.startHour' => 8,
    'order.endHour' => 21,
    'usageLimit' => [
    	[
    		'rooms' => [301, 302],
    		'type' => 'week',
    		'max' => 21,
    	],
    	[
    		'rooms' => [301, 302],
    		'type' => 'month',
    		'max' => 56,
    	],
    	[
    		'rooms' => [403, 440, 441, 603],
    		'type' => 'week',
    		'max' => 42,
    	],
    	[
    		'rooms' => [403, 440, 441, 603],
    		'type' => 'month',
    		'max' => 70,
    	]
    ],
];
