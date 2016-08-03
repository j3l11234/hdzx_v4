<?php
/**
 * @link http://www.j3l11234.com/
 * @copyright Copyright (c) 2015 j3l11234
 * @author j3l11234@j3l11234.com
 */

namespace common\models\services;

use Yii;
use yii\base\Component;
use yii\caching\TagDependency;
use common\exceptions\RoomTableException;
use common\exceptions\ApproveException;
use common\models\entities\Department;
use common\models\entities\Order;
use common\models\entities\User;
use common\models\entities\OrderOperation;
use common\models\services\UserService;
use common\models\services\OrderService;


/**
 * 审批预约相关服务类
 * 负责审批相关操作
 */
class ApproveService extends Component {
    /**
     * 类型_自动审批
     */
    const TYPE_AUTO         = 0x0001;

    /**
     * 类型_负责人审批
     */
    const TYPE_MANAGER      = 0x0002;

    /**
     * 类型_校级审批
     */
    const TYPE_SCHOOL       = 0x0003;

    /**
     * 查询审批预约
     * 数据会包含操作记录
     *
     * @param User $user 用户
     * @param int $type 审批类型
     * @param String $start_date 开始时间
     * @param String $end_date 结束时间
     * @return json
     */
    public static function queryApproveOrder($user, $type, $start_date, $end_date) {
        $where = ['and'];
        switch ($type) {
            case static::TYPE_AUTO:
                $where[] = ['=', 'type', Order::TYPE_AUTO];
                $where[] = ['in', 'status', [Order::STATUS_AUTO_PENDING, Order::STATUS_AUTO_APPROVED, Order::STATUS_AUTO_REJECTED]];
                if (!$user->checkPrivilege(User::PRIV_APPROVE_AUTO)) {
                    throw new ApproveException('没有查询权限', ApproveException::AUTH_FAILED);
                }
                break;
            case static::TYPE_MANAGER:
                $where[] = ['=', 'type', Order::TYPE_TWICE];
                $where[] = ['in', 'status', [Order::STATUS_MANAGER_PENDING, Order::STATUS_MANAGER_APPROVED, Order::STATUS_MANAGER_REJECTED, Order::STATUS_SCHOOL_APPROVED, Order::STATUS_SCHOOL_REJECTED]];
                if ($user->checkPrivilege(User::PRIV_APPROVE_MANAGER_ALL)) {
                } elseif ($user->checkPrivilege(User::PRIV_APPROVE_MANAGER_DEPT)){
                    //$where[] = ['in', 'dept_id', $user->getApproveDeptList()];
                } else {
                    throw new ApproveException('没有查询权限', ApproveException::AUTH_FAILED);
                }
                break;
            case static::TYPE_SCHOOL:
                $where[] = ['=', 'type', Order::TYPE_TWICE];
                $where[] = ['in', 'status', [Order::STATUS_SCHOOL_PENDING, Order::STATUS_SCHOOL_APPROVED, Order::STATUS_SCHOOL_REJECTED]];
                if (!$user->checkPrivilege(User::PRIV_APPROVE_SCHOOL)) {
                    throw new ApproveException('没有查询权限', ApproveException::AUTH_FAILED);
                }
                break;
            default:
                throw new ApproveException('查询类型异常', ApproveException::TYPE_NOT_FOUND);
                break;
        }

        if ($start_date !== null){
            $where[] = ['>=', 'date', $start_date];
        }
        if ($end_date !== null){
            $where[] = ['<=', 'date', $end_date];
        }

        $result = Order::find()->select(['id', 'managers'])->where($where)->asArray()->all();

        $orderList = [];
        $orders = [];
        if ($type == static::TYPE_MANAGER && 
            !$user->checkPrivilege(User::PRIV_APPROVE_MANAGER_ALL)) {
            foreach ($result as $key => $order) {
                if(!Order::checkManager($user->id, $order['managers'])) {
                    continue;
                }
                $order = OrderService::queryOneOrder($order['id']);
                $orderList[] = $order['id'];
                $orders[$order['id']] = $order;
            }
        } else {
            foreach ($result as $key => $order) {
                $order = OrderService::queryOneOrder($order['id']);
                $orderList[] = $order['id'];
                $orders[$order['id']] = $order;
            }
        }
        $data = [
            'orderList' => $orderList,
            'orders' => $orders,
        ];

        return $data;
    }

    /**
     * 审批一个申请
     *
     * @param Order $order 预约
     * @return null
     * @throws Exception 如果出现异常
     */
    public static function approveOrder($order, $user, $type, $comment = null) {
        switch ($type) {
            case static::TYPE_AUTO:
                $operationClass = 'common\models\operations\AutoApproveOperation';
                break;
            case static::TYPE_MANAGER:
                $operationClass = 'common\models\operations\ManagerApproveOperation';
                break;
            case static::TYPE_SCHOOL:
                $operationClass = 'common\models\operations\SchoolApproveOperation';
                break;
            default:
                throw new ApproveException('类型异常', ApproveException::TYPE_NOT_FOUND);
                break;
        }

        static::operateOrder($order, $user, $operationClass, $comment);
    }

    /**
     * 驳回一个申请
     *
     * @param Order $order 预约
     * @return null
     * @throws Exception 如果出现异常
     */
    public static function rejectOrder($order, $user, $type, $comment = null) {
        switch ($type) {
            case static::TYPE_AUTO:
                $operationClass = 'common\models\operations\AutoRejectOperation';
                break;
            case static::TYPE_MANAGER:
                $operationClass = 'common\models\operations\ManagerRejectOperation';
                break;
            case static::TYPE_SCHOOL:
                $operationClass = 'common\models\operations\SchoolRejectOperation';
                break;
            default:
                throw new ApproveException('类型异常', ApproveException::TYPE_NOT_FOUND);
                break;
        }

        static::operateOrder($order, $user, $operationClass, $comment);
    }

    /**
     * 撤回一个申请预约
     *
     * @param Order $order 预约
     * @return null
     * @throws Exception 如果出现异常
     */
    public static function revokeOrder($order, $user, $type, $comment = null) {
        switch ($type) {
            case static::TYPE_AUTO:
                $operationClass = 'common\models\operations\AutoRevokeOperation';
                break;
            case static::TYPE_MANAGER:
                $operationClass = 'common\models\operations\ManagerRevokeOperation';
                break;
            case static::TYPE_SCHOOL:
                $operationClass = 'common\models\operations\SchoolRevokeOperation';
                break;
            default:
                throw new ApproveException('类型异常', ApproveException::TYPE_NOT_FOUND);
                break;
        }

        static::operateOrder($order, $user, $operationClass, $comment);
    }

    protected static function operateOrder($order, $user, $operationClass, $comment) {

        $roomTable = RoomService::getRoomTable($order->date, $order->room_id);
        $extra = [
            'comment' => $comment
        ];

        $connection = Yii::$app->db;
        $transaction = $connection->beginTransaction();

        try {
            $operation = new $operationClass($order, $user, $roomTable,$extra);
            $operation->doOperation();

            $transaction->commit();

            //清除缓存
            TagDependency::invalidate(Yii::$app->cache, 'RoomTable'.'_'.$order->date.'_'.$order->room_id);
            TagDependency::invalidate(Yii::$app->cache, 'Order'.'_'.$order->id);
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }
    }
}