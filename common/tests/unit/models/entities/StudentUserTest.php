<?php

namespace common\tests\unit\models\entities;

use Yii;
use common\models\entities\StudentUser;
use common\fixtures\StudentUser as StudentUserFixture;

/**
 * StudentUser test
 */
class StudentUserTest extends \Codeception\Test\Unit
{
    /**
     * @var \common\tests\UnitTester
     */
    protected $tester;

    public function _before()
    {
        $this->tester->haveFixtures([
            'user' => [
                'class' => StudentUserFixture::className(),
                'dataFile' => codecept_data_dir() . 'user_stu.php'
            ]
        ]);
    }

    public function testFindByUsername() {
        $user = StudentUser::findByUsername('12301120');
        expect('the two deptlist should be same', $user)->notNull();
        expect('the username same', $user->username)->equals('12301120');
    }
    
}
