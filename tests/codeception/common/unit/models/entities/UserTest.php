<?php

namespace tests\codeception\common\unit\models\entities;

use Yii;
use Codeception\Specify;
use common\models\entities\User;
use tests\codeception\common\unit\DbTestCase;
use tests\codeception\common\fixtures\UserFixture;

/**
 * User test
 */
class UserTest extends DbTestCase {

    public function testManagers() {
        $user = User::findByUsername('user0');
        expect('check read', $user->managers)->equals([1]);

        $user->managers = [2,3,4];
        expect('save()', $user->save())->true();
        expect('check after save', $user->managers)->equals([2,3,4]);

        $user = User::findByUsername('user0');
        expect('check read again', $user->managers)->equals([2,3,4]);
    }

    public function testPrivilege() {
        $user = User::findByUsername('user0');
        expect('before add PRIV_ADMIN', $user->checkPrivilege(User::PRIV_ADMIN))->false();
        $user->addPrivilege(User::PRIV_ADMIN);
        expect('after add PRIV_ADMIN', $user->checkPrivilege(User::PRIV_ADMIN))->true();
        expect('save()', $user->save())->true();

        expect('before remove PRIV_ADMIN', $user->checkPrivilege(User::PRIV_ADMIN))->true();
        $user->removePrivilege(User::PRIV_ADMIN);
        expect('after remove PRIV_ADMIN', $user->checkPrivilege(User::PRIV_ADMIN))->false();

        expect('save()', $user->save())->true();
    }

    /**
     * @inheritdoc
     */
    public function fixtures()
    {
        return [
            'user' => [
                'class' => UserFixture::className(),
                'dataFile' => '@tests/codeception/common/unit/fixtures/data/models/user.php'
            ],
        ];
    }
}
