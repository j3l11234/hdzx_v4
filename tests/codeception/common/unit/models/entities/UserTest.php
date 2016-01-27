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

    use Specify;

    public function testApproveDeptList() {
        $this->specify('can get Approve', function () {
            $user = User::findByUsername('user0');
            $deptList = $user->getApproveDeptList();
            expect('the two deptlist should be same', $deptList)->equals([0,1,3]);
        });

        $this->specify('can set Approve', function () {
            $user = User::findByUsername('user0');
            $user->setApproveDeptList([2,3,4]);
            expect('save() should return true', $user->save())->true();

            $user2 = User::findByUsername('user0');
            $deptList = $user->getApproveDeptList();
            expect('the two deptlist should be same', $deptList)->equals([2,3,4]);
        });

        $this->specify('can check Approve', function () {
            $user = User::findByUsername('user0');         
            expect('checkApproveDept(1) should return false', $user->checkApproveDept(1))->false();
            expect('checkApproveDept(2) should return true', $user->checkApproveDept(2))->true();
        });
    }

    public function testPrivilege() {
        $this->specify('can addPrivilege', function () {
            $user = User::findByUsername('user0');
            expect('check PRIV_ADMIN should be false', $user->checkPrivilege(User::PRIV_ADMIN))->false();
            $user->addPrivilege(User::PRIV_ADMIN);
            expect('check PRIV_ADMIN should be true', $user->checkPrivilege(User::PRIV_ADMIN))->true();

            expect('check PRIV_APPROVE_MANAGE_DEPT should be false', $user->checkPrivilege(User::PRIV_APPROVE_MANAGE_DEPT))->false();
            $user->addPrivilege(User::PRIV_APPROVE_MANAGE_DEPT);
            expect('check PRIV_APPROVE_MANAGE_DEPT should be true', $user->checkPrivilege(User::PRIV_APPROVE_MANAGE_DEPT))->true();

            expect('check PRIV_APPROVE_MANAGE_ALL should be false', $user->checkPrivilege(User::PRIV_APPROVE_MANAGE_ALL))->false();
            $user->addPrivilege(User::PRIV_APPROVE_MANAGE_ALL);
            expect('check PRIV_APPROVE_MANAGE_ALL should be true', $user->checkPrivilege(User::PRIV_APPROVE_MANAGE_ALL))->true();

            expect('check PRIV_APPROVE_SCHOOL should be false', $user->checkPrivilege(User::PRIV_APPROVE_SCHOOL))->false();
            $user->addPrivilege(User::PRIV_APPROVE_SCHOOL);
            expect('check PRIV_APPROVE_SCHOOL should be true', $user->checkPrivilege(User::PRIV_APPROVE_SCHOOL))->true();

            expect('save() should return true', $user->save())->true();
        });

        $this->specify('can removePrivilege', function () {
            $user = User::findByUsername('user0');
            expect('check PRIV_ADMIN should be true', $user->checkPrivilege(User::PRIV_ADMIN))->true();
            $user->removePrivilege(User::PRIV_ADMIN);
            expect('check PRIV_ADMIN should be false', $user->checkPrivilege(User::PRIV_ADMIN))->false();

            expect('check PRIV_APPROVE_MANAGE_DEPT should be true', $user->checkPrivilege(User::PRIV_APPROVE_MANAGE_DEPT))->true();
            $user->removePrivilege(User::PRIV_APPROVE_MANAGE_DEPT);
            expect('check PRIV_APPROVE_MANAGE_DEPT should be false', $user->checkPrivilege(User::PRIV_APPROVE_MANAGE_DEPT))->false();

            expect('check PRIV_APPROVE_MANAGE_ALL should be true', $user->checkPrivilege(User::PRIV_APPROVE_MANAGE_ALL))->true();
            $user->removePrivilege(User::PRIV_APPROVE_MANAGE_ALL);
            expect('check PRIV_APPROVE_MANAGE_ALL should be false', $user->checkPrivilege(User::PRIV_APPROVE_MANAGE_ALL))->false();

            expect('check PRIV_APPROVE_SCHOOL should be true', $user->checkPrivilege(User::PRIV_APPROVE_SCHOOL))->true();
            $user->removePrivilege(User::PRIV_APPROVE_SCHOOL);
            expect('check PRIV_APPROVE_SCHOOL should be false', $user->checkPrivilege(User::PRIV_APPROVE_SCHOOL))->false();

            expect('save() should return true', $user->save())->true();
        });

        
    }
    /**
     * @inheritdoc
     */
    public function fixtures()
    {
        return [
            'user' => [
                'class' => UserFixture::className(),
                'dataFile' => '@tests/codeception/common/unit/fixtures/data/models/entities/user.php'
            ],
        ];
    }
}
