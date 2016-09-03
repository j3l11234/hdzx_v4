<?php
namespace common\filter;

use Yii;
use yii\filters\AccessRule;

/**
 * PrivilegeRule
 * 检查权限
 */
class PrivilegeRule extends AccessRule {

    /**
     * 权限规则
     */
    public $privileges;

    /**
     * @inheritdoc
     */
    public function allows($action, $user, $request)
    {
        if ($this->matchAction($action)
            && $this->matchRole($user)
            && $this->matchPrivilege($user)
            && $this->matchIP($request->getUserIP())
            && $this->matchVerb($request->getMethod())
            && $this->matchController($action->controller)
            && $this->matchCustom($action)
        ) {
            return $this->allow ? true : false;
        } else {
            return null;
        }
    }

    /**
     * @param User $user the user object
     * @return boolean 权限验证是否通过
     */
    protected function matchPrivilege($user)
    {
        if (empty($this->privileges)) {
            return true;
        }

        $user = $user->getIdentity()->getUser();
        foreach ($this->privileges as $privilege) {
            if(!$user->checkPrivilege($privilege)){
                return false;
            }
        }
        return true;
    }

}