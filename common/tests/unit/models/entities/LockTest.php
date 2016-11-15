<?php

namespace common\tests\unit\models\entities;

use Yii;
use common\models\entities\Lock;
use common\fixtures\Lock as LockFixture;

/**
 * Lock test
 */
class LockTest extends \Codeception\Test\Unit
{
    /**
     * @var \common\tests\UnitTester
     */
    protected $tester;


    public function _before()
    {
        $this->tester->haveFixtures([
            'lock' => [
                'class' => LockFixture::className(),
                'dataFile' => codecept_data_dir() . 'lock.php'
            ]
        ]);
    }

    public function testRW() {
        $modelData = [
            'rooms' => [301,302,603,403,404,405,406,407,408,409,414,415,440,441],
            'hours' => [12,13],
            'start_date' => '2016-01-01',
            'end_date' => '2016-06-30',
            'status' => Lock::STATUS_ENABLE,
            'data' => [
                'loop_type' => Lock::LOOP_DAY, 
                'loop_day' => 1,
                'title' => '中午休息',
                'comment' => '规定！',
            ],
        ];

        $lock = new Lock();
        $lock->load($modelData, '');

        expect('save()', $lock->save())->true();

        $newLock = Lock::findOne($lock->getPrimaryKey());

        expect('order->rooms', $newLock->rooms)->equals($modelData['rooms']);
        expect('order->hours', $newLock->hours)->equals($modelData['hours']);
        expect('order->start_date', $newLock->start_date)->equals($modelData['start_date']);
        expect('order->end_date', $newLock->end_date)->equals($modelData['end_date']);
        expect('order->status', $newLock->status)->equals($modelData['status']);
        expect('order->data', $newLock->data)->equals($modelData['data']);     
    }

    public function testFields() {
        $modelData = [
            'rooms' => [301,302,603,403,404,405,406,407,408,409,414,415,440,441],
            'hours' => [12,13],
            'start_date' => '2016-01-01',
            'end_date' => '2016-06-30',
            'status' => Lock::STATUS_ENABLE,
            'data' => [
                'loop_type' => Lock::LOOP_DAY, 
                'loop_day' => 1,
                'title' => '中午休息',
                'comment' => '规定！',
            ],
        ];

        $lock = new Lock();
        $lock->load($modelData, '');
        $exportData = $lock->toArray(['rooms', 'hours', 'start_date', 'end_date', 'status', 'data']);
        expect('exportData', $exportData)->equals($modelData);
    }

    public function testGetDateList() {
        $dateList = Lock::getDateList(Lock::LOOP_DAY, 1, '2016-01-01', '2016-01-05');
        expect('$dateList', $dateList)->equals(['2016-01-01', '2016-01-02', '2016-01-03', '2016-01-04', '2016-01-05']);

        $dateList = Lock::getDateList(Lock::LOOP_WEEK, 2, '2016-01-04', '2016-01-31');
        expect('$dateList', $dateList)->equals(['2016-01-05', '2016-01-12', '2016-01-19', '2016-01-26']);
        $dateList = Lock::getDateList(Lock::LOOP_WEEK, 2, '2016-01-05', '2016-01-31');
        expect('$dateList', $dateList)->equals(['2016-01-05', '2016-01-12', '2016-01-19', '2016-01-26']);
        $dateList = Lock::getDateList(Lock::LOOP_WEEK, 2, '2016-01-06', '2016-01-31');
        expect('$dateList', $dateList)->equals(['2016-01-12', '2016-01-19', '2016-01-26']);

        $dateList = Lock::getDateList(Lock::LOOP_MONTH, 15, '2016-01-01', '2016-05-31');
        expect('$dateList', $dateList)->equals(['2016-01-15', '2016-02-15', '2016-03-15', '2016-04-15','2016-05-15']);
        $dateList = Lock::getDateList(Lock::LOOP_MONTH, 31, '2016-01-01', '2016-05-31');
        expect('$dateList', $dateList)->equals(['2016-01-31', '2016-03-31', '2016-05-31']);
    }
}
