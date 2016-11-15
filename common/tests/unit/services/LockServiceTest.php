<?php

namespace tests\codeception\common\unit\services;

use Yii;
use Codeception\Specify;
use common\models\entities\Lock;
use common\services\LockService;
use tests\codeception\common\fixtures\OrderFixture;
use tests\codeception\common\fixtures\OrderOperationFixture;
use tests\codeception\common\fixtures\UserFixture;
use tests\codeception\common\fixtures\LockFixture;
use tests\codeception\common\unit\DbTestCase;

/**
 * Room table test
 */
class LockServiceTest extends DbTestCase {

    use Specify;

    public function testQueryLockDateList() {
        $dateList = LockService::queryLockDateList(2);
        codecept_debug($dateList);
    }

    public function testQueryLockTable() {
        $lockTable = LockService::queryLockTable('2016-03-08', 405);
        codecept_debug($lockTable);
    }

    public function testApplyLock() {
        LockService::applyLock('2016-03-01', '2016-03-31');
    }

    /**
     * @inheritdoc
     */
    public function fixtures() {
        return [
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
            'lock' => [
                'class' => LockFixture::className(),
                'dataFile' => '@tests/codeception/common/unit/fixtures/data/models/lock.php'
            ],
        ];
    }
}
