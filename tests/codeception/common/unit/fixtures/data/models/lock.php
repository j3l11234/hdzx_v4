<?php

use common\models\entities\Lock;

return [
    [
        'id' => 1,
        'rooms' => json_encode([301,302,603,403,404,405,406,407,408,409,414,415,440,441]),
        'hours' => json_encode([12,13]),
        'start_date' => '2016-01-01',
        'end_date' => '2016-06-30',
        'status' => Lock::STATUS_ENABLE,
        'data' => json_encode([
            'loop_type' => Lock::LOOP_DAY, 
            'loop_day' => 1,
            'title' => '中午休息',
            'comment' => '规定！',
        ]),
    ],
    [
        'id' => 2,
        'rooms' => json_encode([301,302,403]),
        'hours' => json_encode([14,16]),
        'start_date' => '2016-01-01',
        'end_date' => '2016-06-30',
        'status' => Lock::STATUS_ENABLE,
        'data' => json_encode([
            'loop_type' => Lock::LOOP_WEEK, 
            'loop_day' => 2,
            'title' => '周二占用',
            'comment' => '规定！',
        ]),
    ],
];

