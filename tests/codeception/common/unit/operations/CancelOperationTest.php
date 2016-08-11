<?php

namespace tests\codeception\common\unit\operations;

use Yii;
use Codeception\Specify;
use common\helpers\Error;
use common\models\entities\Order;
use common\models\entities\OrderOperation;
use common\models\entities\RoomTable;
use common\models\entities\User;
use common\operations\BaseOrderOperation;
use common\operations\CancelOperation;
use common\services\RoomService;
use common\services\UserService;
use tests\codeception\common\fixtures\OrderFixture;
use tests\codeception\common\fixtures\OrderOperationFixture;
use tests\codeception\common\fixtures\RoomTableFixture;
use tests\codeception\common\fixtures\UserFixture;
use tests\codeception\common\unit\DbTestCase;

/**
 * OrderOperation test
 */
class CancelOperationTest extends DbTestCase {

    public function testCheckAuth() {
        //认证异常
        $order = Order::findOne(1);
        $order->user_id = 99;
        $user = UserService::findIdentity(2)->getUser();
        $roomTable = RoomService::getRoomTable($order->date, $order->room_id);

        $connection = Yii::$app->db;
        $transaction=$connection->beginTransaction();
 
        $operation = new CancelOperation($order, $user, $roomTable);
        try {
            $operation->doOperation();
            expect('should throw exception', false)->true();
            $transaction->commit();
        } catch (\Exception $e) {
            expect('exception should be AUTH_FAILED', $e->getCode())->equals(Error::AUTH_FAILED);
            $transaction->rollBack();
        }
    }

    public function testOperation() {
        //状态正常
        $order = Order::findOne(1);
        $user = UserService::findIdentity(1)->getUser();
        $roomTable = RoomService::getRoomTable($order->date, $order->room_id);

        $connection = Yii::$app->db;
        $transaction=$connection->beginTransaction();

        $order->save();
        $operation = new CancelOperation($order, $user, $roomTable);
        try {
            $operation->doOperation();
            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;    
        }
        $newOrder = Order::findOne($order->id);
        expect('order should be write', $newOrder)->notNull();
        expect('$order->status', $newOrder->status)->equals(Order::STATUS_CANCELED);

        $orderOp = OrderOperation::findOne([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'type' => OrderOperation::TYPE_CANCEL
            ]);
        expect('can find $orderOp', $orderOp)->notNull();

        $newRoomTable = RoomService::getRoomTable($order->date, $order->room_id);
        $ordered = $newRoomTable->getOrdered($order->hours);
        $used = $newRoomTable->getUsed($order->hours);
        expect('roomTable->ordered havn\'t order', in_array($order->id, $ordered))->false();
        expect('roomTable->used havn\'t order', in_array($order->id, $used))->false();
    }

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
