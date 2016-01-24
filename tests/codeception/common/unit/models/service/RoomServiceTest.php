<?php

namespace tests\codeception\common\unit\models\service;

use Yii;
use tests\codeception\common\unit\DbTestCase;
use Codeception\Specify;
use common\models\entities\RoomTable;
use common\models\service\RoomService;
use tests\codeception\common\fixtures\RoomTableFixture;

/**
 * Room table test
 */
class RoomServiceTest extends DbTestCase {

    use Specify;

    public function testGetRoomTable() {

        $this->specify('can add order', function () {
            $roomTable = RoomService::getRoomTable('2016-01-01', 1);
            expect('can find exist roomTable', $roomTable)->notNull();


            $roomTable = RoomService::getRoomTable('2016-01-01', 99);
            expect('can find not exist roomTable', $roomTable)->notNull();
            $this->tester->seeRecord(RoomTable::className(), ['date' => '2016-01-01', 'room_id' => '99']);
        });
    }

    public function testApplyOrder() {

        $this->specify('can add order', function () {
            $roomTable = RoomService::getRoomTable('2016-01-01', 1);
            expect('can find exist roomTable', $roomTable)->notNull();
            RoomService::applyOrder($roomTable, 1, [8,9,10], false);
            $this->tester->seeRecord(RoomTable::className(), ['date' => '2016-01-01', 'room_id' => '1', 'ordered' => json_encode([
                '8' => [1],
                '9' => [1],
                '10' => [1],
            ])]);

            RoomService::applyOrder($roomTable, 2, [10,11,12], false);
            $this->tester->seeRecord(RoomTable::className(), ['date' => '2016-01-01', 'room_id' => '1', 'ordered' => json_encode([
                '8' => [1],
                '9' => [1],
                '10' => [1,2],
                '11' => [2],
                '12' => [2],
            ])]);

            RoomService::applyOrder($roomTable, 1, [], false);
            $this->tester->seeRecord(RoomTable::className(), ['date' => '2016-01-01', 'room_id' => '1', 'ordered' => json_encode([
                '8' => [],
                '9' => [],
                '10' => [2],
                '11' => [2],
                '12' => [2],
            ])]);
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
