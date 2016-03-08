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
use common\models\operations\ManagerRevokeOperation;
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
class ManagerRevokeOperationTest extends DbTestCase {

    use Specify;

    public function testCheckAuth() {
        //认证异常
        $this->specify('stop on status error', function () {
            $order = Order::findOne(21);
            $user = UserService::findIdentity(1)->getUser();
            $user->setApproveDeptList([]);
            $user->removePrivilege(User::PRIV_APPROVE_MANAGER_ALL);
            $roomTable = RoomService::getRoomTable($order->date, $order->room_id);

            $connection = Yii::$app->db;
            $transaction=$connection->beginTransaction();
     
            $submitOp = new ManagerRevokeOperation($order, $user, $roomTable);
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

    public function testCheckStatus() {
        //状态异常
        $this->specify('stop on status error', function () {
            $order = Order::findOne(21);
            $order->status = Order::STATUS_MANAGER_PENDING;
            $user = UserService::findIdentity(1)->getUser();
            $roomTable = RoomService::getRoomTable($order->date, $order->room_id);

            $connection = Yii::$app->db;
            $transaction=$connection->beginTransaction();  

            $submitOp = new ManagerRevokeOperation($order, $user, $roomTable);
            try {
                $submitOp->doOperation();
                expect('should throw exception', false)->true();
                $transaction->commit();
            } catch (OrderOperationException $e) {
                expect('exception should be ERROR_INVALID_ORDER_STATUS', $e->getCode())->equals(BaseOrderOperation::ERROR_INVALID_ORDER_STATUS);
                $transaction->rollBack();
            }

             $newOrder = Order::findOne($order->id);
             expect('order->status should be STATUS_MANAGER_APPROVED', $newOrder->status)->equals(Order::STATUS_MANAGER_APPROVED);
        });
    }

    public function testOperation() {
         //正常
        $this->specify('should do operation ok', function () {
            $order = Order::findOne(21);
            $user = UserService::findIdentity(1)->getUser();
            $roomTable = RoomService::getRoomTable($order->date, $order->room_id);

            $connection = Yii::$app->db;
            $transaction=$connection->beginTransaction();
  
            $submitOp = new ManagerRevokeOperation($order, $user, $roomTable);
            try {
                $submitOp->doOperation();
                $transaction->commit();
            } catch (OrderOperationException $e) {
                $transaction->rollBack();
                throw $e;    
            }

            $newOrder = Order::findOne($order->id);
            expect('$order->status should be STATUS_MANAGER_PENDING', $newOrder->status)->equals(Order::STATUS_MANAGER_PENDING);

            $orderOp = OrderOperation::findOne([
                'order_id' => $order->id,
                'user_id' => $user->getLogicId(),
                'type' => OrderOperation::TYPE_MANAGER_REVOKE
                ]);
            expect('can find $orderOp', $orderOp)->notNull();

            $newRoomTable = RoomService::getRoomTable($order->date, $order->room_id);
            $ordered = $newRoomTable->getOrdered($order->getHours());
            $used = $newRoomTable->getUsed($order->getHours());
            expect('roomTable->ordered have order', in_array($order->id, $ordered))->true();
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
                'dataFile' => '@tests/codeception/common/unit/fixtures/data/models/order.php'
            ],
            'user' => [
                'class' => UserFixture::className(),
                'dataFile' => '@tests/codeception/common/unit/fixtures/data/models/user.php'
            ],
        ];
    }
}
