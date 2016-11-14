<?php

namespace common\tests\unit\models\entities;

use Yii;
use common\models\entities\RoomTable;
use common\fixtures\RoomTable as RoomTableFixture;

/**
 * RoomTable test
 */
class RoomTableTest extends \Codeception\Test\Unit
{
    /**
     * @var \common\tests\UnitTester
     */
    protected $tester;


    public function _before()
    {
        $this->tester->haveFixtures([
            'user' => [
                'class' => RoomTableFixture::className(),
                'dataFile' => codecept_data_dir() . 'roomtable.php'
            ]
        ]);
    }

    public function testRW() {
        $modelData = [
            'date' => '2015-12-01',
            'room_id' => '1',
            'ordered' => [
                "11" => [2],
                "12" => [2],
                "13" => [2],
            ],
            'used' => [
                "8" => [1],
                "9" => [1],
                "10" => [1],
            ],
            'locked' => [],
        ];
        $roomTable = new RoomTable();
        $roomTable->load($modelData,'');
        expect('save()', $roomTable->save())->true();

        $newRoomTable = RoomTable::findOne($roomTable->getPrimaryKey());
        expect('room->date', $newRoomTable->date)->equals($modelData['date']);
        expect('room->room_id', $newRoomTable->room_id)->equals($modelData['room_id']);
        expect('room->ordered', $newRoomTable->ordered)->equals($modelData['ordered']);
        expect('room->used', $newRoomTable->used)->equals($modelData['used']);
        expect('room->locked ', $newRoomTable->locked)->equals($modelData['locked']);
    }

    public function testFields() {
        $modelData = [
            'date' => '2015-12-01',
            'room_id' => '1',
            'ordered' => [
                "11" => [2],
                "12" => [2],
                "13" => [2],
            ],
            'used' => [
                "8" => [1],
                "9" => [1],
                "10" => [1],
            ],
            'locked' => [],
        ];
        $roomTable = new RoomTable();
        $roomTable->load($modelData,'');

        $exportData = $roomTable->toArray(['date', 'room_id', 'ordered', 'used', 'locked']);
        expect('exportData', $exportData)->equals($modelData);
    }

    public function testAddTable() {
        $modelData = [
            'date' => '2015-12-01',
            'room_id' => '1',
            'ordered' => [
                "11" => [2],
                "12" => [2],
                "13" => [2],
            ],
            'used' => [
                "8" => [1],
                "9" => [1],
                "10" => [1],
            ],
            'locked' => [],
        ];
        $roomTable = new RoomTable();
        $roomTable->load($modelData,'');

        $roomTable->addOrdered(8, [8,9,10,11]);
        expect('room->ordered', $roomTable->ordered)->equals([
            "8" => [8],
            "9" => [8],
            "10" => [8],
            "11" => [2,8],
            "12" => [2],
            "13" => [2],
        ]);

        $roomTable->addUsed(8, [8,9,10,11]);
        expect('room->used', $roomTable->used)->equals([
            "8" => [1,8],
            "9" => [1,8],
            "10" => [1,8],
            "11" => [8],
        ]);

        $roomTable->addLocked(8, [8,9,10,11]);
        expect('room->locked', $roomTable->locked)->equals([
            "8" => [8],
            "9" => [8],
            "10" => [8],
            "11" => [8],
        ]);
    }

    public function testRemoveTable() {
        $modelData = [
            'date' => '2015-12-01',
            'room_id' => '1',
            'ordered' => [
                "8" => [8,9],
                "9" => [8,9],
                "10" => [8],
                "11" => [2,8],
                "12" => [2],
                "13" => [2],
            ],
            'used' => [
                "8" => [1,8],
                "9" => [1,8,10],
                "10" => [1,8,10],
                "11" => [8],
            ],
            'locked' => [
                "8" => [8,7],
                "9" => [8,7],
                "10" => [8],
                "11" => [8],
            ],
        ];
        $roomTable = new RoomTable();
        $roomTable->load($modelData,'');

        $roomTable->removeOrdered(8);
        expect('room->ordered', $roomTable->ordered)->equals([
            "8" => [9],
            "9" => [9],
            "10" => [],
            "11" => [2],
            "12" => [2],
            "13" => [2],
        ]);

        $roomTable->removeUsed(10);
        expect('room->used', $roomTable->used)->equals([
            "8" => [1,8],
            "9" => [1,8],
            "10" => [1,8],
            "11" => [8],
        ]);

        $roomTable->removeLocked(7);
        expect('room->locked', $roomTable->locked)->equals([
            "8" => [8],
            "9" => [8],
            "10" => [8],
            "11" => [8],
        ]);
    }

    public function testGetTable() {
        $modelData = [
            'date' => '2015-12-01',
            'room_id' => '1',
            'ordered' => [
                "8" => [8,9],
                "9" => [8,9],
                "10" => [8],
                "11" => [2,8],
                "12" => [2],
                "13" => [2],
            ],
            'used' => [
                "8" => [1,8],
                "9" => [1,8,10],
                "10" => [1,8,10],
                "11" => [8],
            ],
            'locked' => [
                "8" => [8,7],
                "9" => [8,7],
                "10" => [8],
                "11" => [8],
            ],
        ];
        $roomTable = new RoomTable();
        $roomTable->load($modelData,'');

        expect('room->ordered', $roomTable->getOrdered())->equals([8,9,2]);
        expect('room->ordered', $roomTable->getOrdered([10,11]))->equals([8,2]);

        expect('room->used', $roomTable->getUsed())->equals([1,8,10]);
        expect('room->used', $roomTable->getUsed([11]))->equals([8]);

        expect('room->locked', $roomTable->getLocked())->equals([8,7]);
        expect('room->locked', $roomTable->getLocked([11]))->equals([8]);
    }

    public function testGetHourTable() {
        $roomTable = new RoomTable();
        $roomTable->date = '2016-01-03';
        $roomTable->room_id = 11;
        $roomTable->addOrdered(1, [8,9,10,11]);
        $roomTable->addLocked(2, [9,10,11]);
        $roomTable->addUsed(3, [11,12,13]);

        $hours = [];
        for ($i=8; $i <= 21; $i++) { 
            $hours[] = $i;
        }
        $hourTable = $roomTable->getHourTable($hours);
        expect('hourtable',  $hourTable)->equals([
            8 => RoomTable::STATUS_ORDERED,
            9 => RoomTable::STATUS_LOCKED,
            10 => RoomTable::STATUS_LOCKED,
            11 => RoomTable::STATUS_LOCKED,
            12 => RoomTable::STATUS_USED,
            13 => RoomTable::STATUS_USED,
            14 => RoomTable::STATUS_FREE,
            15 => RoomTable::STATUS_FREE,
            16 => RoomTable::STATUS_FREE,
            17 => RoomTable::STATUS_FREE,
            18 => RoomTable::STATUS_FREE,
            19 => RoomTable::STATUS_FREE,
            20 => RoomTable::STATUS_FREE,
            21 => RoomTable::STATUS_FREE
        ]);
    }
}
