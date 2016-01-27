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

        $this->specify('can write Order Operation', function () {
            $data = [
                'order_id' => '1',
                'user_id' => '2',
                'type' => '1',
            ];
            $opData = [
                'name' => '李鹏翔',
                'student_no' => '12301119',
                'phone' => '15612322',
                'title' => '学习',
            ];

            $orderOp = new OrderOperation();
            $orderOp->load($data,'');
            $orderOp->setOpData($opData);

            expect('save() return true', $orderOp->save())->true();

            $newOrderOp = OrderOperation::findOne($orderOp->getPrimaryKey());

            expect('orderOp->order_id equal', $newOrderOp->order_id)->equals($data['order_id']);
            expect('orderOp->user_id equal', $newOrderOp->user_id)->equals($data['user_id']);
            expect('orderOp->type equal', $newOrderOp->type)->equals($data['type']);
            expect('orderOp->operationData equal', $newOrderOp->getOpData())->equals($opData);
        });
    }

    /**
     * @inheritdoc
     */
    public function fixtures()
    {
        return [
            'user' => [
                'class' => OrderOperationFixture::className(),
                'dataFile' => '@tests/codeception/common/unit/fixtures/data/models/entities/order_opt.php'
            ],
        ];
    }
}
