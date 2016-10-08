<?php
return [
    'adminEmail' => 'admin@example.com',
    'supportEmail' => 'support@example.com',
    'backendUrl' => '../../backend/web',
    'frontendUrl' => '../../frontend/web',
    'cronKey' => '123456',
    'user.passwordResetTokenExpire' => 3600,
    'order.startHour' => 8,
    'order.endHour' => 21,
    'order.hours' => [8,9,10,11,12,13,14,15,16,17,18,19,20,21],
    'cache.duration' => 86400,
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
