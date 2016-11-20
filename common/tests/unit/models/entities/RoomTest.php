<?php

namespace common\tests\unit\models\entities;

use Yii;
use common\models\entities\Room;
use common\fixtures\Room as RoomFixture;

/**
 * Room test
 */
class RoomTest extends \Codeception\Test\Unit
{
    /**
     * @var \common\tests\UnitTester
     */
    protected $tester;

    public function _before()
    {
        $this->tester->haveFixtures([
            'room' => [
                'class' => RoomFixture::className(),
                'dataFile' => codecept_data_dir() . 'room.php'
            ]
        ]);
    }

    public function testRW() {
        $modelData = [
            'number' => 405,
            'name' => '单技琴房2',
            'type' => Room::TYPE_SIMPLE,
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

    public function testFields() {
        $modelData = [
            'number' => 405,
            'name' => '单技琴房2',
            'type' => Room::TYPE_SIMPLE,
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

        $exportData = $room->toArray(['number', 'name', 'type', 'data', 'align', 'status']);
        expect('exportData', $exportData)->equals($modelData);
    }


    public function testGetOpenPeriod() {
        $dateRange = Room::getOpenPeriod([
            'max_before' => 10,
            'min_before' => 5,
            'by_week' => 0,
            'open_time' => '07:12:34',
            'close_time' => '23:12:34',
        ], '2016-11-17');
        expect('dateRange', $dateRange)->equals([
            'start' => strtotime('2016-11-07 07:12:34'),
            'end' => strtotime('2016-11-12 23:12:34'),
        ]);

        $dateRange = Room::getOpenPeriod([
            'max_before' => 13,
            'min_before' => 5,
            'by_week' => 1,
            'open_time' => '07:00:00',
            'close_time' => '23:59:59',
        ], '2016-11-20');
        expect('dateRange', $dateRange)->equals([
            'start' => strtotime('2016-11-07 07:00:00'),
            'end' => strtotime('2016-11-15 23:59:59'),
        ]);

        $dateRange = Room::getOpenPeriod([
            'max_before' => 14,
            'min_before' => 5,
            'by_week' => 1,
            'open_time' => '07:00:00',
            'close_time' => '23:59:59',
        ], '2016-11-20');
        expect('dateRange', $dateRange)->equals([
            'start' => strtotime('2016-10-31 07:00:00'),
            'end' => strtotime('2016-11-15 23:59:59'),
        ]);

        $dateRange = Room::getOpenPeriod([
            'max_before' => 14,
            'min_before' => 5,
            'by_week' => 1,
            'open_time' => '07:00:00',
            'close_time' => '23:59:59',
        ], '2016-11-22');
        expect('dateRange', $dateRange)->equals([
            'start' => strtotime('2016-11-07 07:00:00'),
            'end' => strtotime('2016-11-17 23:59:59'),
        ]);
    }

    public function testGetDateRange() {
        $dateRange = Room::getDateRange([
            'max_before' => 10,
            'min_before' => 5,
            'by_week' => 0,
            'open_time' => '00:00:00',
            'close_time' => '23:59:59',
        ], strtotime('2016-03-01 0:0:0'));
        expect('dateRange', $dateRange)->equals([
            'start' => strtotime('2016-03-06 00:00:00'),
            'end' => strtotime('2016-03-11 23:59:59'),
        ]);

        $dateRange = Room::getDateRange([
            'max_before' => 10,
            'min_before' => 5,
            'by_week' => 1,
            'open_time' => '00:00:00',
            'close_time' => '23:59:59',
        ], strtotime('2016-03-01 0:0:0'));
        expect('dateRange', $dateRange)->equals([
            'start' => strtotime('2016-03-06 00:00:00'),
            'end' => strtotime('2016-03-13 23:59:59'),
        ]);

        $dateRange = Room::getDateRange([
            'max_before' => 14,
            'min_before' => 5,
            'by_week' => 1,
            'open_time' => '00:00:00',
            'close_time' => '23:59:59',
        ], strtotime('2016-03-01 0:0:0'));
        expect('dateRange', $dateRange)->equals([
            'start' => strtotime('2016-03-06 00:00:00'),
            'end' => strtotime('2016-03-20 23:59:59'),
        ]);

        $dateRange = Room::getDateRange([
            'max_before' => 7,
            'min_before' => 2,
            'by_week' => 1,
            'open_time' => '07:00:00',
            'close_time' => '22:00:00',
        ], strtotime('2016-03-07 06:59:59'));
        expect('dateRange', $dateRange)->equals([
            'start' => strtotime('2016-03-09 00:00:00'),
            'end' => strtotime('2016-03-13 23:59:59'),
        ]);

        $dateRange = Room::getDateRange([
            'max_before' => 7,
            'min_before' => 2,
            'by_week' => 1,
            'open_time' => '07:00:00',
            'close_time' => '22:00:00',
        ], strtotime('2016-03-07 22:00:00'));
        expect('dateRange', $dateRange)->equals([
            'start' => strtotime('2016-03-10 00:00:00'),
            'end' => strtotime('2016-03-20 23:59:59'),
        ]);

        codecept_debug(strtotime('0:0:0',strtotime('2016-03-20 23:59:59')));
    }

    // public function testCheckOpen() {
    //     expect('checkOpen', Room::checkOpen('2016-03-05', 10, 5, 0, '7:0:0', strtotime('2016-03-01 8:0:0')))->false();
    //     expect('checkOpen', Room::checkOpen('2016-03-06', 10, 5, 0, '7:0:0', strtotime('2016-03-01 8:0:0')))->true();
    //     expect('checkOpen', Room::checkOpen('2016-03-11', 10, 5, 0, '0:0:0', strtotime('2016-03-01 8:0:0')))->true();
    //     expect('checkOpen', Room::checkOpen('2016-03-12', 10, 5, 0, '7:0:0', strtotime('2016-03-01 8:0:0')))->false();
    // }

}
