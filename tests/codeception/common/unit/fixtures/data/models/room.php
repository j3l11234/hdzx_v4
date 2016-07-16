<?php

use common\models\entities\Room;

return [
    [
        'id' => 1,
        'number' => 301,
        'name' => '多功能厅',
        'type' => Room::TYPE_SCHOOL_AUTO,
        'data' => json_encode([
            'by_week' => 1, 
            'max_before' => 14,
            'min_before' => 5,
            'max_hour' => 14,
            'secure' => 1,
        ]),
        'align' => '1',
        'status' => Room::STATUS_OPEN,
    ],[
        'id' => 2,
        'number' => 302,
        'name' => '小剧场',
        'type' => Room::TYPE_SCHOOL_AUTO,
        'data' => json_encode([
            'by_week' => 1, 
            'max_before' => 14,
            'min_before' => 5,
            'max_hour' => 14,
            'secure' => 1,
        ]),
        'align' => '1',
        'status' => Room::STATUS_OPEN,
    ],[
        'id' => 3,
        'number' => 404,
        'name' => '单技琴房1',
        'type' => Room::TYPE_AUTO,
        'data' => json_encode([
            'by_week' => 0, 
            'max_before' => 15,
            'min_before' => 1,
            'max_hour' => 2,
            'secure' => 0,
        ]),
        'align' => '0',
        'status' => Room::STATUS_OPEN,
    ],
];

