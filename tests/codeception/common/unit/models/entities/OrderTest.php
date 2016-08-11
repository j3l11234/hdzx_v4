<?php

namespace tests\codeception\common\unit\models\entities;

use Yii;
use Codeception\Specify;
use tests\codeception\common\unit\DbTestCase;
use Codeception\Specify;
use common\models\entities\Order;
use tests\codeception\common\fixtures\OrderFixture;

/**
 * Order test
 */
class OrderTest extends DbTestCase {

    public function testRW() {
        $modelData = [
            'date' => '2015-01-03',
            'room_id' => '99',
            'hours' => [8,9,10],
            'user_id' => '1',
            'managers' => [1,2],
            'type' => '1',
            'status' => '1',
            'submit_time' => 12312312,
            'data' => [
                'name' => '李鹏翔',
                'student_no' => '12301119',
                'phone' => '15612322',
                'title' => '学习',
                'content' => '学习',
                'number' => '1',
                'secure' => '做好了',
            ],
            'issue_time' => '1',
        ];
        $order = new Order();
        $order->load($modelData, '');
        expect('save()', $order->save())->true();

        $newOrder = Order::findOne($order->getPrimaryKey());
        expect('order->date', $newOrder->date)->equals($modelData['date']);
        expect('order->room_id', $newOrder->room_id)->equals($modelData['room_id']);
        expect('order->user_id', $newOrder->user_id)->equals($modelData['user_id']);
        expect('order->type', $newOrder->type)->equals($modelData['type']);
        expect('order->status', $newOrder->status)->equals($modelData['status']);
        expect('order->submit_time', $newOrder->submit_time)->equals($modelData['submit_time']);
        expect('order->hours', $newOrder->hours)->equals($modelData['hours']);
        expect('order->managers', $newOrder->managers)->equals($modelData['managers']);
        expect('order->data', $newOrder->data)->equals($modelData['data']);
        expect('order->issue_time', $newOrder->issue_time)->equals($modelData['issue_time']);      
    }

    public function testFields() {
        $modelData = [
            'date' => '2015-01-03',
            'room_id' => '99',
            'hours' => [8,9,10],
            'user_id' => '1',
            'managers' => [1,2],
            'type' => '1',
            'status' => '1',
            'submit_time' => 12312312,
            'data' => [
                'name' => '李鹏翔',
                'student_no' => '12301119',
                'phone' => '15612322',
                'title' => '学习',
                'content' => '学习',
                'number' => '1',
                'secure' => '做好了',
            ],
            'issue_time' => '1',
        ];
        $order = new Order();
        $order->load($modelData, '');

        $exportData = $order->toArray(['date', 'room_id', 'hours', 'user_id', 'managers', 'type', 'status', 'submit_time', 'data', 'issue_time']);
        expect('exportData', $exportData)->equals($modelData);
    }

    public function testFindByDateRoom() {
        $orderList = Order::findByDateRoom('2015-12-01', 301);
        expect('the count', count($orderList))->equals(2);
    }

    public function testCheckManager() {
        $managers = [1,2];
        expect('user_id=1', Order::checkManager(1, $managers))->true();
        expect('user_id=2', Order::checkManager(2, $managers))->true();
        expect('user_id=3', Order::checkManager(3, $managers))->false();
    }

    /**
     * @inheritdoc
     */
    public function fixtures()
    {
        return [
            'order' => [
                'class' => OrderFixture::className(),
                'dataFile' => '@tests/codeception/common/unit/fixtures/data/models/order.php'
            ],
        ];
    }
}
