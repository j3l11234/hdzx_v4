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
use common\helpers\Error;
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
    const TYPE_SIMPLE         = 0x0001;

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
            case static::TYPE_SIMPLE:
                $where[] = ['=', 'type', Order::TYPE_SIMPLE];
                $where[] = ['in', 'status', [Order::STATUS_SIMPLE_PENDING, Order::STATUS_SIMPLE_APPROVED, Order::STATUS_SIMPLE_REJECTED]];
                if (!$user->checkPrivilege(User::PRIV_APPROVE_SIMPLE)) {
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
            case static::TYPE_SIMPLE:
                $operationClass = 'common\models\operations\SimpleApproveOperation';
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
            case static::TYPE_SIMPLE:
                $operationClass = 'common\models\operations\SimpleRejectOperation';
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
            case static::TYPE_SIMPLE:
                $operationClass = 'common\models\operations\SimpleRevokeOperation';
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


    /**
     * 查询冲突预约
     *
     * @param Order/Array $order 预约
     * @param int $type 查询类型
     * @return Array<Order>
     */
    public static function getConflictOrder($order, $type) {
        if(is_array($order)) {
            $roomTable = RoomService::getRoomTable($order['date'], $order['room_id']);
            $hours = $order['hours'];
        } else {
            $roomTable = RoomService::getRoomTable($order->date, $order->room_id);
            $hours = $order->hours;
        }
        $orderIds = $roomTable->getOrdered($hours);

        $where = ['and'];
        $where[] = ['in', 'id', $orderIds];
        switch ($type) {
            case static::TYPE_SIMPLE:
                $where[] = ['=', 'type', Order::TYPE_SIMPLE];
                $where[] = ['in', 'status', [Order::STATUS_SIMPLE_PENDING]];
                break;
            case static::TYPE_MANAGER:
                $where[] = ['=', 'type', Order::TYPE_TWICE];
                $where[] = ['in', 'status', [Order::STATUS_MANAGER_PENDING]];
                break;
            case static::TYPE_SCHOOL:
                $where[] = ['=', 'type', Order::TYPE_TWICE];
                $where[] = ['in', 'status', [Order::STATUS_MANAGER_PENDING, Order::STATUS_SCHOOL_PENDING]];
                break;
            default:
                throw new ApproveException('类型异常', ApproveException::TYPE_NOT_FOUND);
                break;
        }
        $result = Order::find()->where($where)->orderBy('submit_time')->all();
        return $result;
    }

    /**
     * 自动审批-负责人自动驳回
     *
     * @param Order $order 预约
     * @return null
     * @throws Exception 如果出现异常
     */
    public static function autoApprove1() {
        $where = ['and'];
        $where[] = ['in', 'room_id', [403,440,441,603,301,302]];
        $where[] = ['=', 'type', Order::TYPE_TWICE];
        $where[] = ['in', 'status', [Order::STATUS_MANAGER_PENDING]];
        $now = time();
        $month = date("m", $now);
        $year = date("Y", $now);
        $day = date("d", $now);
        $where[] = ['>=', 'date', date("y-m-d", $now)];
        $where[] = ['<', 'submit_time', mktime(0, 0, 0, $month, $day - 1, $year)];
        $result = Order::find()->where($where)->orderBy('submit_time ASC')->all();

        $user = UserService::findIdentity(1)->getUser();
        foreach ($result as $order) {
            try {
                static::rejectOrder($order, $user, static::TYPE_MANAGER, '超时自动驳回');
                Yii::info('负责人审批：驳回Order'.$order->id, '自动审批');
            } catch (\Exception $e) {
                Yii::error('负责人审批：异常'.$order->id.','.$e->getMessage(), '自动审批');
            }  
        }
    }

    /**
     * 自动审批-3天校级审批自动通过
     *
     * @param Order $order 预约
     * @return null
     * @throws Exception 如果出现异常
     */
    public static function autoApprove2() {
        $where = ['and'];
        $where[] = ['in', 'room_id', [403,440,441,603,301,302]];
        $where[] = ['=', 'type', Order::TYPE_TWICE];
        $where[] = ['in', 'status', [Order::STATUS_SCHOOL_PENDING]];
        $now = time();
        $month = date("m", $now);
        $year = date("Y", $now);
        $day = date("d", $now);
        $where[] = ['>=', 'date', date("y-m-d", $now)];
        $where[] = ['<', 'submit_time', mktime(0, 0, 0, $month, $day -2, $year)];
        $result = Order::find()->where($where)->orderBy('submit_time ASC')->all();

        $user = UserService::findIdentity(1)->getUser();
        foreach ($result as $order) {
            try {
                static::approveOrder($order, $user, static::TYPE_SCHOOL, '自动审批通过');
                Yii::info('校级审批：通过Order'.$order->id, '自动审批');
            } catch (\Exception $e) {
                if ($e->getCode() == Error::ROOMTABLE_USED) {
                    //该预约已经被占用，自动驳回
                    try {
                        static::rejectOrder($order, $user, static::TYPE_SCHOOL, '因冲突驳回');
                        Yii::info('校级审批：驳回Order'.$order->id, '自动审批');
                    } catch (\Exception $e) {
                        Yii::error('校级审批：异常'.$order->id.','.$e->getMessage(), '自动审批');
                    }
                } else {
                    Yii::error('校级审批：异常'.$order->id.','.$e->getMessage(), '自动审批');
                }
            }
            
        }
    }
}