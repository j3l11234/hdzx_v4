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
        $this->specify('can read roomTable', function () {
            $roomTable = RoomTable::findByDateRoom('2015-01-01', '1');
            expect('can read', $roomTable)->notNull();
            expect('can read roomTable->ordered', $roomTable->getOrdered([8]))->equals([1, 2, 3]);
            expect('can read roomTable->ordered all hours', $roomTable->getOrdered())->equals([1, 2, 3, 4]);

        });
    }

    public function testWrite() {

        $this->specify('can write roomTable', function () {
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
        });
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
