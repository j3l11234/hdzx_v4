<?php

namespace tests\codeception\common\unit\models;

use Yii;
use tests\codeception\common\unit\DbTestCase;
use Codeception\Specify;
use common\models\entities\Order;
use tests\codeception\common\fixtures\OrderFixture;

/**
 * Order test
 */
class OrderTest extends DbTestCase {

    use Specify;

    //throw new \yii\base\Exception(var_export($var,true));

    public function testRW() {
        $this->specify('can write Order', function () {
            $data = [
                'date' => '2015-01-03',
                'room_id' => '99',
                'user_id' => '1',
                'dept_id' => '1',
                'type' => '1',
                'status' => '1',
                'submit_time' => 12312312,
            ];
            $orderData = [
                'name' => '李鹏翔',
                'student_no' => '12301119',
                'phone' => '15612322',
                'title' => '学习',
                'content' => '学习',
                'number' => '1',
                'secure' => '做好了',
            ];
            $hours = [8,9,10];

            $order = new Order();
            $order->load($data,'');
            $order->setHours($hours);
            $order->setOrderData($orderData);

            expect('save() return true', $order->save())->true();

            $newOrder = Order::findOne($order->getPrimaryKey());

            expect('order->date equal', $newOrder->date)->equals($data['date']);
            expect('order->room_id equal', $newOrder->room_id)->equals($data['room_id']);
            expect('order->user_id equal', $newOrder->user_id)->equals($data['user_id']);
            expect('order->dept_id equal', $newOrder->dept_id)->equals($data['dept_id']);
            expect('order->type equal', $newOrder->type)->equals($data['type']);
            expect('order->status equal', $newOrder->status)->equals($data['status']);
            expect('order->date equal', $newOrder->submit_time)->equals($data['submit_time']);
            expect('order->hours equal', $newOrder->getHours())->equals($hours);
            expect('order->orderData equal', $newOrder->getOrderData())->equals($orderData);
        });
    }

    public function testFindByDateRoom() {
        $this->specify('test FindByDateRoom()', function () {
            $orderList = Order::findByDateRoom('2015-01-01', 1);
            expect('the count should be 2', count($orderList))->equals(2);
        });
    }

    /**
     * @inheritdoc
     */
    public function fixtures()
    {
        return [
            'order' => [
                'class' => OrderFixture::className(),
                'dataFile' => '@tests/codeception/common/unit/fixtures/data/models/entities/order.php'
            ],
        ];
    }
}
