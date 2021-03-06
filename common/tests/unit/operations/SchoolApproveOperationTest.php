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
use common\operations\SchoolApproveOperation;
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
class SchoolApproveOperationTest extends DbTestCase {

    public function testCheckAuth() {
        //认证异常
        $order = Order::findOne(30);
        $user = UserService::findIdentity(1)->getUser();
        $user->removePrivilege(User::PRIV_APPROVE_SCHOOL);
        $roomTable = RoomService::getRoomTable($order->date, $order->room_id);

        $connection = Yii::$app->db;
        $transaction=$connection->beginTransaction();
 
        $submitOp = new SchoolApproveOperation($order, $user, $roomTable);
        try {
            $submitOp->doOperation();
            expect('should throw exception', false)->true();
            $transaction->commit();
        } catch (\Exception $e) {
            expect('exception should be ERROR_AUTH_FAILED', $e->getCode())->equals(Error::AUTH_FAILED);
            $transaction->rollBack();
        } 
    }

    public function testCheckStatus() {
        //状态异常
        $order = Order::findOne(30);
        $order->status = Order::STATUS_PASSED;
        $user = UserService::findIdentity(1)->getUser();
        $roomTable = RoomService::getRoomTable($order->date, $order->room_id);

        $connection = Yii::$app->db;
        $transaction=$connection->beginTransaction();  

        $submitOp = new SchoolApproveOperation($order, $user, $roomTable);
        try {
            $submitOp->doOperation();
            expect('should throw exception', false)->true();
            $transaction->commit();
        } catch (\Exception $e) {
            expect('exception should be ERROR_INVALID_ORDER_STATUS', $e->getCode())->equals(Error::INVALID_ORDER_STATUS);
            $transaction->rollBack();
        }

         $newOrder = Order::findOne($order->id);
         expect('order->status should be STATUS_INIT', $newOrder->status)->equals(Order::STATUS_SCHOOL_PENDING);
    }

    public function testOperation() {
        //状态正常
        $order = Order::findOne(30);
        $user = UserService::findIdentity(1)->getUser();
        $roomTable = RoomService::getRoomTable($order->date, $order->room_id);

        $connection = Yii::$app->db;
        $transaction=$connection->beginTransaction();

        $submitOp = new SchoolApproveOperation($order, $user, $roomTable);
        try {
            $submitOp->doOperation();
            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;    
        }

        $newOrder = Order::findOne($order->id);
        expect('$order->status', $newOrder->status)->equals(Order::STATUS_SCHOOL_APPROVED);

        $orderOp = OrderOperation::findOne([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'type' => OrderOperation::TYPE_SCHOOL_APPROVE
            ]);
        expect('can find $orderOp', $orderOp)->notNull();

        $newRoomTable = RoomService::getRoomTable($order->date, $order->room_id);
        $ordered = $newRoomTable->getOrdered($order->hours);
        $used = $newRoomTable->getUsed($order->hours);
        expect('roomTable->ordered have not order', in_array($order->id, $ordered))->false();
        expect('roomTable->used have order', in_array($order->id, $used))->true();
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
