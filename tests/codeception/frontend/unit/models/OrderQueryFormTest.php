<?php

namespace tests\codeception\common\unit\models;

use Yii;
use tests\codeception\common\unit\DbTestCase;
use Codeception\Specify;
use frontend\models\OrderQueryForm;

/**
 * Query form test
 */
class OrderQueryFormTest extends DbTestCase {

    use Specify;

    public function setUp() {
        parent::setUp();
    }

    protected function tearDown() {
        //Yii::$app->user->logout();
        parent::tearDown();
    }

    public function testGetRoomTables() {
        $data = [
            'start_date' => '2016-01-30',
            'end_date' => '2016-02-28',
            'rooms' => '[404]'
        ];
        $model = new OrderQueryForm($data);
        $roomtable = $model->getRoomTables();
        //codecept_debug($roomtable);
    }

    /**
     * @inheritdoc
     */
    public function fixtures() {
        return [
        ];
    }
}
