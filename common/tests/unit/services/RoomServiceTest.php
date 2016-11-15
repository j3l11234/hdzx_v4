<?php

namespace tests\codeception\common\unit\services;

use Yii;
use tests\codeception\common\unit\DbTestCase;
use Codeception\Specify;
use common\models\entities\RoomTable;
use common\services\RoomService;
use tests\codeception\common\fixtures\OrderFixture;
use tests\codeception\common\fixtures\OrderOperationFixture;
use tests\codeception\common\fixtures\RoomTableFixture;
use tests\codeception\common\fixtures\UserFixture;

/**
 * Room table test
 */
class RoomServiceTest extends DbTestCase {

    use Specify;

    public function testGetRoomTable() {
        // $roomTable = RoomService::getRoomTable('2016-01-01', 404,true,true);
        // expect('can find exist roomTable', $roomTable)->notNull();


        $roomTable = RoomService::getRoomTable('2015-12-01', 404, true,true);
        expect('can find not exist roomTable', $roomTable)->notNull();
        $this->tester->seeRecord(RoomTable::className(), ['date' => '2015-12-01', 'room_id' => '404']);
    }

    // public function testApplyOrder() {
    //     $roomTable = RoomService::getRoomTable('2016-01-01', 1);
    //     expect('can find exist roomTable', $roomTable)->notNull();
    //     RoomService::applyOrder($roomTable, 1, [8,9,10], false);
    //     $this->tester->seeRecord(RoomTable::className(), ['date' => '2016-01-01', 'room_id' => '1', 'ordered' => json_encode([
    //         '8' => [1],
    //         '9' => [1],
    //         '10' => [1],
    //     ])]);

    //     RoomService::applyOrder($roomTable, 2, [10,11,12], false);
    //     $this->tester->seeRecord(RoomTable::className(), ['date' => '2016-01-01', 'room_id' => '1', 'ordered' => json_encode([
    //         '8' => [1],
    //         '9' => [1],
    //         '10' => [1,2],
    //         '11' => [2],
    //         '12' => [2],
    //     ])]);

    //     RoomService::applyOrder($roomTable, 1, [], false);
    //     $this->tester->seeRecord(RoomTable::className(), ['date' => '2016-01-01', 'room_id' => '1', 'ordered' => json_encode([
    //         '8' => [],
    //         '9' => [],
    //         '10' => [2],
    //         '11' => [2],
    //         '12' => [2],
    //     ])]);
    // }

    // public function testQueryRoomTable(){
    //     $roomTable = RoomService::queryRoomTable('2015-01-02', 2);
    //     codecept_debug($roomTable);
    // }

    // public function testQueryRoomList() {
    //     $rooms = RoomService::queryRoomList();
    //     codecept_debug($rooms);
    // }

    // public function testqueryWholeDateRange() {
    //     $range = RoomService::queryWholeDateRange();
    //     codecept_debug($range);
    // }
    

    /**
     * @inheritdoc
     */
    public function fixtures()
    {
        return [
            'room_table' => [
                'class' => RoomTableFixture::className(),
                'dataFile' => '@tests/codeception/common/unit/fixtures/data/models/roomtable.php'
            ],
            'order_op' => [
                'class' => OrderOperationFixture::className(),
                'dataFile' => '@tests/codeception/common/unit/fixtures/data/models/order_op.php'
            ],
            'order' => [
                'class' => OrderFixture::className(),
                'dataFile' => '@tests/codeception/common/unit/fixtures/data/models/order.php'
            ],
            'user' => [
                'class' => UserFixture::className(),
                'dataFile' => '@tests/codeception/common/unit/fixtures/data/models/user.php'
            ],
        ];
    }
}
