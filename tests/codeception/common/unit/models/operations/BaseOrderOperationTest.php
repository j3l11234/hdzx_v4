<?php

namespace tests\codeception\common\unit\models;

use Yii;
use Codeception\Specify;
use common\exceptions\OrderOperationException;
use common\models\entities\Order;
use common\models\entities\OrderOperation;
use common\models\entities\RoomTable;
use common\models\entities\User;
use common\models\services\RoomService;
use common\models\services\UserService;
use common\models\operations\BaseOrderOperation;
use tests\codeception\common\fixtures\OrderFixture;
use tests\codeception\common\fixtures\OrderOperationFixture;
use tests\codeception\common\fixtures\RoomTableFixture;
use tests\codeception\common\fixtures\UserFixture;
use tests\codeception\common\unit\DbTestCase;

/**
 * OrderOperation test
 */
class BaseOrderOperationTest extends DbTestCase {

    use Specify;

    public function testApplyRoomTable() {
        //房间表异常
        $this->specify('stop on status roomtable', function () {
            $order = Order::findOne(2);
            $user = UserService::findIdentity(1)->getUser();
            $roomTable = RoomService::getRoomTable($order->date, $order->room_id);
            $order->setHours([9,10]);

            $connection = Yii::$app->db;
            $transaction=$connection->beginTransaction();

            $baseOp = new BaseOrderOperation($order, $user, $roomTable);
            try {
                $baseOp->doOperation();
                expect('should throw exception', false)->true();
                $transaction->commit();
            } catch (OrderOperationException $e) {
                $transaction->rollBack();
                expect('exception should be ERROR_ROOMTABLE_USED', $e->getCode())->equals(BaseOrderOperation::ERROR_ROOMTABLE_USED);
            }
        });
    }

    public function testOperation() {
         //状态正常
        $this->specify('should do operation ok', function () {
            $order = Order::findOne(2);
            $user = UserService::findIdentity(1)->getUser();
            $roomTable = RoomService::getRoomTable($order->date, $order->room_id);

            $connection = Yii::$app->db;
            $transaction=$connection->beginTransaction();

            $baseOp = new BaseOrderOperation($order, $user, $roomTable);
            try {
                $baseOp->doOperation();
                $transaction->commit();
            } catch (OrderOperationException $e) {
                $transaction->rollBack();
                throw $e;
            }

            $orderOp = OrderOperation::findOne([
                'order_id' => $order->id,
                'user_id' => $user->getLogicId(),
                ]);
            expect('can find orderOp', $orderOp)->notNull();
        });
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
