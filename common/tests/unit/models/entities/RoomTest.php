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

    public function testGetDateRange() {
        $dateRange = Room::getDateRange(10, 5, 0, '0:0:0', strtotime('2016-03-01 0:0:0'));
        expect('dateRange', $dateRange)->equals([
            'start' => strtotime('2016-03-06 00:00:00'),
            'end' => strtotime('2016-03-11 23:59:59'),
        ]);


        $dateRange = Room::getDateRange(10, 5, 1, '0:0:0', strtotime('2016-03-01 0:0:0'));
        expect('dateRange', $dateRange)->equals([
            'start' => strtotime('2016-03-06 00:00:00'),
            'end' => strtotime('2016-03-13 23:59:59'),
        ]);

        $dateRange = Room::getDateRange(5, 5, 1, '0:0:0', strtotime('2016-03-01 0:0:0'));
        expect('dateRange', $dateRange)->equals([
            'start' => strtotime('2016-03-06 00:00:00'),
            'end' => strtotime('2016-03-06 23:59:59'),
        ]);


        $dateRange = Room::getDateRange(6, 5, 1, '0:0:0', strtotime('2016-03-01 0:0:0'));
        expect('dateRange', $dateRange)->equals([
            'start' => strtotime('2016-03-06 00:00:00'),
            'end' => strtotime('2016-03-13 23:59:59'),
        ]);

        $dateRange = Room::getDateRange(6, 5, 1, '8:0:0', strtotime('2016-03-01 0:0:0'));
        expect('dateRange', $dateRange)->equals([
            'start' => strtotime('2016-03-06 00:00:00'),
            'end' => strtotime('2016-03-06 23:59:59'),
        ]);

        $dateRange = Room::getDateRange(6, 5, 1, '7:0:0', strtotime('2016-03-01 8:0:0'));
        expect('dateRange', $dateRange)->equals([
            'start' => strtotime('2016-03-06 00:00:00'),
            'end' => strtotime('2016-03-13 23:59:59'),
        ]);
    }

    public function testCheckOpen() {
        expect('checkOpen', Room::checkOpen('2016-03-05', 10, 5, 0, '7:0:0', strtotime('2016-03-01 8:0:0')))->false();
        expect('checkOpen', Room::checkOpen('2016-03-06', 10, 5, 0, '7:0:0', strtotime('2016-03-01 8:0:0')))->true();
        expect('checkOpen', Room::checkOpen('2016-03-11', 10, 5, 0, '0:0:0', strtotime('2016-03-01 8:0:0')))->true();
        expect('checkOpen', Room::checkOpen('2016-03-12', 10, 5, 0, '7:0:0', strtotime('2016-03-01 8:0:0')))->false();
    }

}
