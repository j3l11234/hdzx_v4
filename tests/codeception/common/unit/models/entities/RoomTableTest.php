<?php

namespace tests\codeception\common\unit\models;

use Yii;
use tests\codeception\common\unit\DbTestCase;
use Codeception\Specify;
use common\models\entities\RoomTable;
use tests\codeception\common\fixtures\RoomTableFixture;

/**
 * Room table test
 */
class RoomTableTest extends DbTestCase {

    use Specify;

    //throw new \yii\base\Exception(var_export($var,true));
    public function testRead() {
        $roomTable = RoomTable::findByDateRoom('2015-01-01', '1');
        expect('can read', $roomTable)->notNull();
        expect('can read roomTable->ordered', $roomTable->getOrdered([8]))->equals([1, 2, 3]);
        expect('can read roomTable->ordered all hours', $roomTable->getOrdered())->equals([1, 2, 3, 4]);
    }

    public function testWrite() {
        $roomTable = new RoomTable();
        $roomTable->date = '2016-01-03';
        $roomTable->room_id = 11;
        $roomTable->addOrdered(1, [8,9,10,11]);
        $roomTable->addOrdered(2, [9,10,11]);
        $roomTable->addOrdered(3, [11,12,13]);
        $roomTable->removeOrdered(2);

        expect('save() return true', $roomTable->save())->true();  
        $this->tester->seeRecord(RoomTable::className(), ['date' => '2016-01-03', 'room_id' => '11']);
        expect('can write roomTable->ordered', $roomTable->getOrdered())->equals([1,3]);
    }

    public function testGetHourTable() {
        $roomTable = new RoomTable();
        $roomTable->date = '2016-01-03';
        $roomTable->room_id = 11;
        $roomTable->addOrdered(1, [8,9,10,11]);
        $roomTable->addLocked(2, [9,10,11]);
        $roomTable->addUsed(3, [11,12,13]);

        //codecept_debug($roomTable->toArray(['ordered', 'used', 'locked']));
        $hours = [];
        for ($i=8; $i <= 21; $i++) { 
            $hours[] = $i;
        }
        $hourTable = $roomTable->getHourTable($hours);
        expect('can read roomTable->ordered all hours',  $hourTable)->equals([
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
        //codecept_debug($hourTable);
    }




    /**
     * @inheritdoc
     */
    public function fixtures()
    {
        return [
            'room_table' => [
                'class' => RoomTableFixture::className(),
                'dataFile' => '@tests/codeception/common/unit/fixtures/data/models/entities/roomtable.php'
            ],
        ];
    }
}
