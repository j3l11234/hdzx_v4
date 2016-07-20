<?php

namespace tests\codeception\common\unit\models;

use Yii;
use common\exceptions\OrderOperationException;
use common\models\entities\Order;
use common\models\entities\OrderOperation;
use common\models\entities\RoomTable;
use common\models\entities\User;
use common\models\operations\BaseOrderOperation;
use common\models\operations\SubmitOperation;
use common\models\services\RoomService;
use common\models\services\UserService;
use tests\codeception\common\fixtures\RoomTableFixture;
use tests\codeception\common\fixtures\OrderFixture;
use tests\codeception\common\fixtures\OrderOperationFixture;
use tests\codeception\common\unit\DbTestCase;

/**
 * OrderOperation test
 */
class SubmitOperationTest extends DbTestCase {

    public function testCheckStatus() {
        //状态异常
        $order = new Order();
        $order->date = '2015-12-01';
        $order->room_id = 1;
        $order->user_id = 1;
        $order->managers = [1,2];
        $order->type = Order::TYPE_AUTO;
        $order->status = Order::STATUS_PASSED;
        $order->hours = [8,9,10];
        $order->data = [
            'name' => '李鹏翔',
            'student_no' => '12301119',
            'phone' => '15612322',
            'title' => '学习',
            'content' => '学习',
            'number' => '1',
            'secure' => '做好了',
        ];

        $user = UserService::findIdentity(1)->getUser();
        $roomTable = RoomService::getRoomTable($order->date, $order->room_id);

        $connection = Yii::$app->db;
        $transaction=$connection->beginTransaction();

        $order->save();
        $submitOp = new SubmitOperation($order, $user, $roomTable);
        try {
            $submitOp->doOperation();
            expect('should throw exception', false)->true();
            $transaction->commit();
        } catch (OrderOperationException $e) {
            $transaction->rollBack();
            expect('exception should be ERROR_INVALID_ORDER_STATUS', $e->getCode())->equals(BaseOrderOperation::ERROR_INVALID_ORDER_STATUS);
        }

        $newOrder = Order::findOne($order->id);
        expect('order should be delete', $newOrder)->null();
    }

    public function testOperation() {
         //状态正常
        $order = new Order();
        $order->date = '2015-12-01';
        $order->room_id = 1;
        $order->user_id = 1;
        $order->managers = [1,2];
        $order->type = Order::TYPE_AUTO;
        $order->status = Order::STATUS_INIT;
        $order->hours = [16,17,18];
        $order->data = [
            'name' => '李鹏翔',
            'student_no' => '12301119',
            'phone' => '15612322',
            'title' => '学习',
            'content' => '学习',
            'number' => '1',
            'secure' => '做好了',
        ];
        $user = UserService::findIdentity(1)->getUser();
        $roomTable = RoomService::getRoomTable($order->date, $order->room_id);

        $connection = Yii::$app->db;
        $transaction=$connection->beginTransaction();

        $order->save();
        $submitOp = new SubmitOperation($order, $user, $roomTable);
        try {
            $submitOp->doOperation();
            $transaction->commit();
        } catch (OrderOperationException $e) {
            $transaction->rollBack();
            throw $e;    
        }
        $newOrder = Order::findOne($order->id);
        expect('$order->status', $newOrder->status)->equals(Order::STATUS_AUTO_PENDING);

        $orderOp = OrderOperation::findOne([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'type' => OrderOperation::TYPE_SUBMIT
        ]);
        expect('can find $orderOp', $orderOp)->notNull();

        $newRoomTable = RoomService::getRoomTable($order->date, $order->room_id);
        $ordered = $newRoomTable->getOrdered($order->hours);
        expect('RoomTable can find order', in_array($order->id, $ordered))->true();


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
        ];
    }
}
