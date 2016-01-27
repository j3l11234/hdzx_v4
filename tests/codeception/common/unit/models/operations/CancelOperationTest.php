<?php

namespace tests\codeception\common\unit\models;

use Yii;
use Codeception\Specify;
use common\exceptions\OrderOperationException;
use common\models\entities\Order;
use common\models\entities\OrderOperation;
use common\models\entities\RoomTable;
use common\models\entities\User;
use common\models\operations\BaseOrderOperation;
use common\models\operations\CancelOperation;
use common\models\services\RoomService;
use common\models\services\UserService;
use tests\codeception\common\fixtures\OrderFixture;
use tests\codeception\common\fixtures\OrderOperationFixture;
use tests\codeception\common\fixtures\RoomTableFixture;
use tests\codeception\common\fixtures\UserFixture;
use tests\codeception\common\unit\DbTestCase;

/**
 * OrderOperation test
 */
class CancelOperationTest extends DbTestCase {

    use Specify;

    public function testCheckAuth() {
        //认证异常
        $this->specify('stop on status error', function () {
            $order = Order::findOne(1);
            $order->user_id = 99;
            $user = UserService::findIdentity(1)->getUser();
            $roomTable = RoomService::getRoomTable($order->date, $order->room_id);

            $connection = Yii::$app->db;
            $transaction=$connection->beginTransaction();
     
            $submitOp = new CancelOperation($order, $user, $roomTable);
            try {
                $submitOp->doOperation();
                expect('should throw exception', false)->true();
                $transaction->commit();
            } catch (OrderOperationException $e) {
                expect('exception should be ERROR_AUTH_FAILED', $e->getCode())->equals(BaseOrderOperation::ERROR_AUTH_FAILED);
                $transaction->rollBack();
            }
        });  
    }

    public function testOperation() {
         //状态正常
        $this->specify('should do operation ok', function () {
            $order = Order::findOne(1);
            $user = UserService::findIdentity(1)->getUser();
            $roomTable = RoomService::getRoomTable($order->date, $order->room_id);

            $connection = Yii::$app->db;
            $transaction=$connection->beginTransaction();

            $order->save();
            $submitOp = new CancelOperation($order, $user, $roomTable);
            try {
                $submitOp->doOperation();
                $transaction->commit();
            } catch (OrderOperationException $e) {
                $transaction->rollBack();
                throw $e;    
            }
            $newOrder = Order::findOne($order->id);
            expect('order should be write', $newOrder)->notNull();
            expect('$order->status', $newOrder->status)->equals(Order::STATUS_CANCELED);

            $orderOp = OrderOperation::findOne([
                'order_id' => $order->id,
                'user_id' => $user->getLogicId(),
                'type' => OrderOperation::TYPE_CANCEL
                ]);
            expect('can find $orderOp', $orderOp)->notNull();

            $newRoomTable = RoomService::getRoomTable($order->date, $order->room_id);
            $ordered = $newRoomTable->getOrdered($order->getHours());
            $used = $newRoomTable->getUsed($order->getHours());
            expect('roomTable->ordered have not order', in_array($order->id, $ordered))->false();
            expect('roomTable->used have not order', in_array($order->id, $used))->false();
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
                'dataFile' => '@tests/codeception/common/unit/fixtures/data/models/roomtable.php'
            ],
            'order_op' => [
                'class' => OrderOperationFixture::className(),
                'dataFile' => '@tests/codeception/common/unit/fixtures/data/models/order_op.php'
            ],
            'order' => [
                'class' => OrderFixture::className(),
                'dataFile' => '@tests/codeception/common/unit/fixtures/data/models/entities/order.php'
            ],
            'user' => [
                'class' => UserFixture::className(),
                'dataFile' => '@tests/codeception/common/unit/fixtures/data/models/user.php'
            ],
        ];
    }
}
