<?php

namespace tests\codeception\common\unit\operations;

use Yii;
use Codeception\Specify;
use common\helpers\Error;
use common\models\entities\Order;
use common\models\entities\OrderOperation;
use common\models\entities\RoomTable;
use common\models\entities\User;
use common\services\RoomService;
use common\services\UserService;
use common\operations\BaseOrderOperation;
use tests\codeception\common\fixtures\OrderFixture;
use tests\codeception\common\fixtures\OrderOperationFixture;
use tests\codeception\common\fixtures\RoomTableFixture;
use tests\codeception\common\fixtures\UserFixture;
use tests\codeception\common\unit\DbTestCase;

/**
 * OrderOperation test
 */
class BaseOrderOperationTest extends DbTestCase {

    public function testApplyRoomTable() {
        //房间表异常
        $order = Order::findOne(2);
        $user = UserService::findIdentity($order->user_id)->getUser();
        $roomTable = RoomService::getRoomTable($order->date, $order->room_id);
        $order->hours = [9,10];

        $connection = Yii::$app->db;
        $transaction=$connection->beginTransaction();

        $baseOp = new BaseOrderOperation($order, $user, $roomTable);
        try {
            $baseOp->doOperation();
            expect('should throw exception', false)->true();
            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();
            expect('ROOMTABLE_USED', $e->getCode())->equals(Error::ROOMTABLE_USED);
        }
    }

    public function testCompet() {
        //房间表异常
        $order = Order::findOne(2);
        $user = UserService::findIdentity($order->user_id)->getUser();
        $roomTable = RoomService::getRoomTable($order->date, $order->room_id);

        $connection = Yii::$app->db;
        $transaction=$connection->beginTransaction();

        $baseOp = new BaseOrderOperation($order, $user, $roomTable);
        try {
            $roomTable->ver = $roomTable->ver+1;
            $baseOp->doOperation();
            expect('should throw exception', false)->true();
            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();
            expect('COMPET', $e->getCode())->equals(Error::COMPET);
        }
    }

    public function testOperation() {
        //状态正常
        $order = Order::findOne(2);
        $user = UserService::findIdentity($order->user_id)->getUser();
        $roomTable = RoomService::getRoomTable($order->date, $order->room_id);

        $connection = Yii::$app->db;
        $transaction=$connection->beginTransaction();

        $baseOp = new BaseOrderOperation($order, $user, $roomTable);
        try {
            $baseOp->doOperation();
            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }

        $orderOp = OrderOperation::findOne([
            'order_id' => $order->id,
            'user_id' => $user->id,
        ]);
        expect('orderOp', $orderOp)->notNull();
    }

    /**
     * @inheritdoc
     */
    public function fixtures() {
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
