<?php

namespace tests\codeception\common\unit\models\services;

use Yii;
use Codeception\Specify;
use common\models\entities\Order;
use common\models\entities\OrderOperation;
use common\models\services\ApproveService;
use common\models\services\UserService;
use common\models\services\OrderService;
use common\models\services\RoomService;
use tests\codeception\common\fixtures\RoomTableFixture;
use tests\codeception\common\fixtures\OrderFixture;
use tests\codeception\common\fixtures\OrderOperationFixture;
use tests\codeception\common\fixtures\UserFixture;
use tests\codeception\common\unit\DbTestCase;

/**
 * Approve test
 */
class ApproveServiceTest extends DbTestCase {

    use Specify;

    public function testQueryApproveOrder() {
        $user = UserService::findIdentity(2)->getUser();

        $orderList = ApproveService::queryApproveOrder($user, ApproveService::TYPE_MANAGER, '2015-12-01', '2015-12-03');
        codecept_debug($orderList);
    }
    
    public function testApproveOrder(){
        $user = UserService::findIdentity(1)->getUser();

        //负责人审批测试
        $order = Order::findOne(20);
        ApproveService::approveOrder($order, $user, ApproveService::TYPE_MANAGER);

        $newOrder = Order::findOne($order->id);
        expect('$order->status', $newOrder->status)->equals(Order::STATUS_MANAGER_APPROVED);

        //校团委审批测试
        $order = Order::findOne(30);
        ApproveService::approveOrder($order, $user, ApproveService::TYPE_SCHOOL);

        $newOrder = Order::findOne($order->id);
        expect('$order->status', $newOrder->status)->equals(Order::STATUS_SCHOOL_APPROVED);

        //自动审批测试
        $order = Order::findOne(40);
        ApproveService::approveOrder($order, $user, ApproveService::TYPE_AUTO);

        $newOrder = Order::findOne($order->id);
        expect('$order->status', $newOrder->status)->equals(Order::STATUS_AUTO_APPROVED);

    }

    public function testRejectOrder(){
        $user = UserService::findIdentity(1)->getUser();

        //负责人审批测试
        $order = Order::findOne(20);
        ApproveService::rejectOrder($order, $user, ApproveService::TYPE_MANAGER);

        $newOrder = Order::findOne($order->id);
        expect('$order->status', $newOrder->status)->equals(Order::STATUS_MANAGER_REJECTED);

        //校团委审批测试
        $order = Order::findOne(30);
        ApproveService::rejectOrder($order, $user, ApproveService::TYPE_SCHOOL);

        $newOrder = Order::findOne($order->id);
        expect('$order->status', $newOrder->status)->equals(Order::STATUS_SCHOOL_REJECTED);

        //自动审批测试
        $order = Order::findOne(40);
        ApproveService::rejectOrder($order, $user, ApproveService::TYPE_AUTO);

        $newOrder = Order::findOne($order->id);
        expect('$order->status', $newOrder->status)->equals(Order::STATUS_AUTO_REJECTED);
            
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
