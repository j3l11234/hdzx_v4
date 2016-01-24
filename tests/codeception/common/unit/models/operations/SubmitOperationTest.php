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
use common\models\operations\SubmitOperation;
use common\models\service\RoomService;
use common\models\service\UserService;
use tests\codeception\common\fixtures\RoomTableFixture;
use tests\codeception\common\fixtures\OrderFixture;
use tests\codeception\common\fixtures\OrderOperationFixture;
use tests\codeception\common\unit\DbTestCase;

/**
 * OrderOperation test
 */
class SubmitOperationTest extends DbTestCase {

    use Specify;

    public function testCheckStatus() {
        //状态异常
        $this->specify('stop on status error', function () {
            $order = new Order();
            $order->date = '2015-12-01';
            $order->room_id = 1;
            $order->user_id = 1;
            $order->dept_id = 1;
            $order->type = Order::TYPE_AUTO;
            $order->status = Order::STATUS_PASSED;
            $order->setHours([16,17,18]);
            $order->setOrderData([
                'name' => '李鹏翔',
                'student_no' => '12301119',
                'phone' => '15612322',
                'title' => '学习',
                'content' => '学习',
                'number' => '1',
                'secure' => '做好了',
            ]);
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
        });
    }

    public function testOperation() {
         //状态正常
        $this->specify('should do operation ok', function () {
            $order = new Order();
            $order->date = '2015-12-01';
            $order->room_id = 1;
            $order->user_id = 1;
            $order->dept_id = 1;
            $order->type = Order::TYPE_AUTO;
            $order->status = Order::STATUS_INIT;
            $order->setHours([16,17,18]);
            $order->setOrderData([
                'name' => '李鹏翔',
                'student_no' => '12301119',
                'phone' => '15612322',
                'title' => '学习',
                'content' => '学习',
                'number' => '1',
                'secure' => '做好了',
            ]);
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
            expect('order should be write', $newOrder)->notNull();
            expect('$order->status', $newOrder->status)->equals(Order::STATUS_AUTO_PENDING);

            $orderOp = OrderOperation::findOne([
                'order_id' => $order->id,
                'user_id' => $user->getLogicId(),
                'type' => OrderOperation::TYPE_SUBMIT
                ]);
            expect('can find $orderOp', $orderOp)->notNull();

            $newRoomTable = RoomService::getRoomTable($order->date, $order->room_id);
            $ordered = $newRoomTable->getOrdered($order->getHours());
            expect('RoomTable can find order', in_array($order->id, $ordered))->true();
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
                'dataFile' => '@tests/codeception/common/unit/fixtures/data/models/operations/roomtable.php'
            ],
            'order_op' => [
                'class' => OrderOperationFixture::className(),
                'dataFile' => '@tests/codeception/common/unit/fixtures/data/models/operations/order_op.php'
            ],
            'order' => [
                'class' => OrderFixture::className(),
                'dataFile' => '@tests/codeception/common/unit/fixtures/data/models/entities/order.php'
            ],
        ];
    }
}
