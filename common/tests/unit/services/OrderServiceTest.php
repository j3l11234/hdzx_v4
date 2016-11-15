<?php

namespace tests\codeception\common\unit\services;

use Yii;
use Codeception\Specify;
use common\models\entities\Order;
use common\models\entities\OrderOperation;
use common\services\UserService;
use common\services\OrderService;
use common\services\RoomService;
use tests\codeception\common\fixtures\RoomTableFixture;
use tests\codeception\common\fixtures\OrderFixture;
use tests\codeception\common\fixtures\OrderOperationFixture;
use tests\codeception\common\fixtures\UserFixture;
use tests\codeception\common\unit\DbTestCase;

/**
 * Room table test
 */
class OrderServiceTest extends DbTestCase {

    use Specify;

    public function testSubmitOrder() {
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

        OrderService::submitOrder($order, $user);

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
    }
    
    public function testQueryDeptList() {
        $depts = OrderService::queryDeptList();
        codecept_debug($depts);
    }
    
    public function testqueryWeekUsage() {
        OrderService::queryWeekUsage('1',  strtotime('2015-12-01'));
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
