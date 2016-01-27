<?php

namespace tests\codeception\common\unit\models;

use Yii;
use tests\codeception\common\unit\DbTestCase;
use Codeception\Specify;
use common\models\LoginForm;
use tests\codeception\common\fixtures\UserFixture;
use tests\codeception\common\fixtures\StudentUserFixture;

/**
 * Login form test
 */
class LoginFormTest extends DbTestCase {

    use Specify;

    public function setUp() {
        parent::setUp();
        Yii::configure(Yii::$app, [
            'components' => [
                'user' => [
                    'class' => 'yii\web\User',
                    'identityClass' => 'common\models\service\UserService'
                ],
            ],
        ]);
    }

    protected function tearDown() {
        Yii::$app->user->logout();
        parent::tearDown();
    }

    public function testLoginNoUser() {
        $model = new LoginForm([
            'username' => 'not_existing_username',
            'password' => 'not_existing_password',
        ]);

        expect('model should not login user', $model->login())->false();
        expect('error message should be set', $model->errors)->hasKey('username');
        expect('user should not be logged in', Yii::$app->user->isGuest)->true();
    }

    public function testLoginWrongPassword() {
        $model = new LoginForm([
            'username' => 'admin',
            'password' => 'wrong_password',
        ]);

        expect('model should not login user', $model->login())->false();
        expect('error message should be set', $model->errors)->hasKey('password');
        expect('user should not be logged in', Yii::$app->user->isGuest)->true();
    }

    public function testLoginWrongPasswordStudent() {
        $model = new LoginForm([
            'username' => '12301120',
            'password' => 'wrong_password',
        ]);

        expect('model should not login user', $model->login())->false();
        expect('error message should be set', $model->errors)->hasKey('password');
        expect('user should not be logged in', Yii::$app->user->isGuest)->true();
    }

    public function testLoginCorrect() {
        $model = new LoginForm([
            'username' => 'admin',
            'password' => 'nimda',
        ]);

        expect('model should login user', $model->login())->true();
        expect('error message should not be set', $model->errors)->hasntKey('password');
        expect('user should be logged in', Yii::$app->user->isGuest)->false();
    }

    public function testLoginCorrectStudent() {
        $model = new LoginForm([
            'username' => '12301120',
            'password' => 'nimda',
        ]);

        expect('model should login user', $model->login())->true();
        expect('error message should not be set', $model->errors)->hasntKey('password');
        expect('user should be logged in', Yii::$app->user->isGuest)->false();
    }

    /**
     * @inheritdoc
     */
    public function fixtures() {
        return [
            'user' => [
                'class' => UserFixture::className(),
                'dataFile' => '@tests/codeception/common/unit/fixtures/data/models/user.php'
            ],
            'user_stu' => [
                'class' => StudentUserFixture::className(),
                'dataFile' => '@tests/codeception/common/unit/fixtures/data/models/user_stu.php'
            ],
        ];
    }
}
