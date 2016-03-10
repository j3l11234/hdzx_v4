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
