<?php

namespace tests\codeception\common\unit\models\entities;

use Yii;
use tests\codeception\common\unit\DbTestCase;
use Codeception\Specify;
use common\models\entities\Room;
use tests\codeception\common\fixtures\RoomFixture;

/**
 * Room test
 */
class RoomTest extends DbTestCase {

    public function testRW() {
        $modelData = [
            'number' => 405,
            'name' => '单技琴房2',
            'type' => Room::TYPE_AUTO,
            'data' => [
                'by_week' => 0, 
                'max_before' => 15,
                'min_before' => 1,
                'max_hour' => 2,
                'secure' => 0,
            ],
            'align' => '0',
            'status' => Room::STATUS_OPEN,
        ];

        $room = new Room();
        $room->load($modelData,'');

        expect('save()', $room->save())->true();

        $newRoom = Room::findOne($room->getPrimaryKey());
        expect('room->number', $newRoom->number)->equals($modelData['number']);
        expect('room->name', $newRoom->name)->equals($modelData['name']);
        expect('room->type', $newRoom->type)->equals($modelData['type']);
        expect('room->data', $newRoom->data)->equals($modelData['data']);
        expect('room->align ', $newRoom->align)->equals($modelData['align']);
        expect('room->status', $newRoom->status)->equals($modelData['status']);
    }

    public function testGetDateRange() {
        $dateRange = Room::getDateRange(10, 5, 0, strtotime('2016-03-01'));
        expect('dateRange', $dateRange)->equals([
            'start' => strtotime('2016-03-06 00:00:00'),
            'end' => strtotime('2016-03-11 23:59:59'),
        ]);


        $dateRange = Room::getDateRange(10, 5, 1, strtotime('2016-03-01'));
        expect('dateRange', $dateRange)->equals([
            'start' => strtotime('2016-03-06 00:00:00'),
            'end' => strtotime('2016-03-13 23:59:59'),
        ]);

        $dateRange = Room::getDateRange(5, 5, 1, strtotime('2016-03-01'));
        expect('dateRange', $dateRange)->equals([
            'start' => strtotime('2016-03-06 00:00:00'),
            'end' => strtotime('2016-03-06 23:59:59'),
        ]);
    }

    /**
     * @inheritdoc
     */
    public function fixtures(){
        return [
            'room' => [
                'class' => RoomFixture::className(),
                'dataFile' => '@tests/codeception/common/unit/fixtures/data/models/room.php'
            ],
        ];
    }
}
