<?php

namespace tests\codeception\common\unit\models\entities;

use Yii;
use Codeception\Specify;
use common\models\entities\StudentUser;
use tests\codeception\common\unit\DbTestCase;
use tests\codeception\common\fixtures\StudentUserFixture;

/**
 * User test
 */
class StudentUserTest extends DbTestCase {

    use Specify;

    public function testFindByUsername() {
        $user = StudentUser::findByUsername('12301120');
        expect('the two deptlist should be same', $user)->notNull();
        expect('the username same', $user->username)->equals('12301120');
    }

    /**
     * @inheritdoc
     */
    public function fixtures() {
        return [
            'user_stu' => [
                'class' => StudentUserFixture::className(),
                'dataFile' => '@tests/codeception/common/unit/fixtures/data/models/user_stu.php'
            ],
        ];
    }
    
}
