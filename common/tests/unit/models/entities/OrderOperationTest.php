<?php

namespace common\tests\unit\models\entities;

use Yii;
use common\models\entities\OrderOperation;
use common\fixtures\OrderOperation as OrderOperationFixture;

/**
 * OrderOperation test
 */
class OrderOperationTest extends \Codeception\Test\Unit
{
    /**
     * @var \common\tests\UnitTester
     */
    protected $tester;


    public function _before()
    {
        $this->tester->haveFixtures([
            'user' => [
                'class' => OrderOperationFixture::className(),
                'dataFile' => codecept_data_dir() . 'order_op.php'
            ]
        ]);
    }

    public function testRW() {
        $modelData = [
            'order_id' => '1',
            'user_id' => '2',
            'time' => '1',
            'type' => OrderOperation::TYPE_CHANGE_HOUR,
            'data' => [
                'operator' => '张三',
                'studentn_no' => '12301120', 
                'commemt' => '提交预约',
            ]
        ];
        $orderOp = new OrderOperation();
        $orderOp->load($modelData, '');
        expect('save()', $orderOp->save())->true();
        expect('orderOp->data', $orderOp->data)->equals($modelData['data']);

        $newOrderOp = OrderOperation::findOne($orderOp->getPrimaryKey());
        expect('orderOp->order_id', $newOrderOp->order_id)->equals($modelData['order_id']);
        expect('orderOp->user_id', $newOrderOp->user_id)->equals($modelData['user_id']);
        expect('orderOp->type', $newOrderOp->type)->equals($modelData['type']);
        expect('orderOp->data', $newOrderOp->data)->equals($modelData['data']);
    }

    public function testFields(){
        $modelData = [
            'order_id' => '1',
            'user_id' => '2',
            'time' => '1',
            'type' => OrderOperation::TYPE_CHANGE_HOUR,
            'data' => [
                'operator' => '张三',
                'studentn_no' => '12301120', 
                'commemt' => '提交预约',
            ]
        ];
        $orderOp = new OrderOperation();
        $orderOp->load($modelData, '');

        $exportData = $orderOp->toArray(['order_id', 'user_id', 'time', 'type', 'data']);
        expect('exportData', $exportData)->equals($modelData);
    }
}
