<?php

namespace tests\codeception\common\unit\models\entities;

use Yii;
use tests\codeception\common\unit\DbTestCase;
use Codeception\Specify;
use common\models\entities\OrderOperation;
use tests\codeception\common\fixtures\OrderOperationFixture;

/**
 * OrderOperation test
 */
class OrderOperationTest extends DbTestCase {

    use Specify;

    public function testRW() {
        $modelData = [
            'order_id' => '1',
            'user_id' => '2',
            'type' => '1',
            'data' => [
                'name' => '李鹏翔',
                'student_no' => '12301119',
                'phone' => '15612322',
                'title' => '学习',
            ]
        ];
        $orderOp = new OrderOperation();
        $orderOp->load($modelData ,'');

        expect('save()', $orderOp->save())->true();
        expect('orderOp->data', $orderOp->data)->equals($modelData['data']);

        $newOrderOp = OrderOperation::findOne($orderOp->getPrimaryKey());

        expect('orderOp->order_id', $newOrderOp->order_id)->equals($modelData['order_id']);
        expect('orderOp->user_id', $newOrderOp->user_id)->equals($modelData['user_id']);
        expect('orderOp->type', $newOrderOp->type)->equals($modelData['type']);
        expect('orderOp->data', $newOrderOp->data)->equals($modelData['data']);
    }

    public function testFields(){
        $orderOp = OrderOperation::findOne(1);
        $exportData = $orderOp->toArray();
        expect('exportData', $exportData)->equals([
            'id' => '1',
            'order_id' => '1',
            'user_id' => '2',
            'time' => '1',
            'type' => '1',
            'data' => [
                'name' => '李鹏翔',
                'student_no' => '12301119',
                'phone' => '15612322',
                'title' => '学习',
            ]
        ]);
    }

    /**
     * @inheritdoc
     */
    public function fixtures()
    {
        return [
            'user' => [
                'class' => OrderOperationFixture::className(),
                'dataFile' => '@tests/codeception/common/unit/fixtures/data/models/order_op.php'
            ],
        ];
    }
}
