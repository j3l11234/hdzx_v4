<?php
/**
 * @link http://www.j3l11234.com/
 * @copyright Copyright (c) 2015 j3l11234
 * @author j3l11234@j3l11234.com
 */

namespace common\services;

use Yii;
use yii\base\Component;
use yii\caching\TagDependency;
use yii\base\Exception;
use common\helpers\Error;
use common\models\entities\Department;
use common\models\entities\Order;
use common\models\entities\User;
use common\models\entities\OrderOperation;
use common\services\UserService;
use common\services\OrderService;


/**
 * 审批预约相关服务类
 * 负责审批相关操作
 */
class ApproveService extends Component {
    /**
     * 类型_自动审批
     */
    const TYPE_SIMPLE         = 01;

    /**
     * 类型_负责人审批
     */
    const TYPE_MANAGER      = 02;

    /**
     * 类型_校级审批
     */
    const TYPE_SCHOOL       = 03;

    public static $type_string = [
        01 => '琴房',
        02 => '负责人',
        03 => '校级',
    ];
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
        $menagerFilter = false; //负责人审批筛选
        switch ($type) {
            case static::TYPE_SIMPLE:
                $where[] = ['=', 'type', Order::TYPE_SIMPLE];
                $where[] = ['in', 'status', [Order::STATUS_SIMPLE_PENDING, Order::STATUS_SIMPLE_APPROVED, Order::STATUS_SIMPLE_REJECTED]];
                if (!$user->checkPrivilege(User::PRIV_APPROVE_SIMPLE)) {
                    throw new Exception('没有查询权限', Error::AUTH_FAILED);
                }
                break;
            case static::TYPE_MANAGER:
                $where[] = ['=', 'type', Order::TYPE_TWICE];
                $where[] = ['in', 'status', [Order::STATUS_MANAGER_PENDING, Order::STATUS_MANAGER_APPROVED, Order::STATUS_MANAGER_REJECTED, Order::STATUS_SCHOOL_APPROVED, Order::STATUS_SCHOOL_REJECTED]];
                if ($user->checkPrivilege(User::PRIV_APPROVE_MANAGER_ALL)) {
                } elseif ($user->checkPrivilege(User::PRIV_APPROVE_MANAGER_DEPT)){
                    $menagerFilter = true;
                } else {
                    throw new Exception('没有查询权限', Error::AUTH_FAILED);
                }
                break;
            case static::TYPE_SCHOOL:
                $where[] = ['=', 'type', Order::TYPE_TWICE];
                $where[] = ['in', 'status', [Order::STATUS_SCHOOL_PENDING, Order::STATUS_SCHOOL_APPROVED, Order::STATUS_SCHOOL_REJECTED]];
                if (!$user->checkPrivilege(User::PRIV_APPROVE_SCHOOL)) {
                    throw new Exception('没有查询权限', Error::AUTH_FAILED);
                }
                break;
            default:
                throw new Exception('无效审批类型', Error::INVALID_APPROVE_TYPE);
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
        if ($menagerFilter) {
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
                $operationClass = 'common\operations\SimpleApproveOperation';
                break;
            case static::TYPE_MANAGER:
                $operationClass = 'common\operations\ManagerApproveOperation';
                break;
            case static::TYPE_SCHOOL:
                $operationClass = 'common\operations\SchoolApproveOperation';
                break;
            default:
                throw new Exception('无效审批类型', Error::INVALID_APPROVE_TYPE);
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
                $operationClass = 'common\operations\SimpleRejectOperation';
                break;
            case static::TYPE_MANAGER:
                $operationClass = 'common\operations\ManagerRejectOperation';
                break;
            case static::TYPE_SCHOOL:
                $operationClass = 'common\operations\SchoolRejectOperation';
                break;
            default:
                throw new Exception('无效审批类型', Error::INVALID_APPROVE_TYPE);
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
                $operationClass = 'common\operations\SimpleRevokeOperation';
                break;
            case static::TYPE_MANAGER:
                $operationClass = 'common\operations\ManagerRevokeOperation';
                break;
            case static::TYPE_SCHOOL:
                $operationClass = 'common\operations\SchoolRevokeOperation';
                break;
            default:
                throw new Exception('无效审批类型', Error::INVALID_APPROVE_TYPE);
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
        } catch (Exception $e) {
            $transaction->rollBack();
            throw $e;
        }
    }


    /**
     * 自动驳回冲突的预约
     *
     * @param Order $order 预约
     * @return null
     * @throws Exception 如果出现异常
     */
    public static function rejectConflictOrder($order, $user, $type, $comment = '冲突自动驳回') {
        $rejectList = [];

        //如果是负责人审批则不驳回任何冲突审批
        if ($type == static::TYPE_MANAGER) {
            return $rejectList;
        }

        $orderList = static::getConflictOrder($order, $type, false);
        foreach ($orderList as $conflictOrder) {
            //跳过自身
            if ($conflictOrder->id == $order->id) {
                continue;
            }

            try {
                Yii::info(static::$type_string[$type].'审批：驳回Order_'.$conflictOrder->id.'(因为与Order_'.$order->id.')冲突', '驳回冲突申请');
                static::rejectOrder($conflictOrder, $user, $type, $comment);
                $rejectOrder[] = $conflictOrder->id;
            } catch (Exception $e) {
                Yii::error(static::$type_string[$type].'审批：Order_'.$conflictOrder->id.'异常,'.$e->getMessage(), '驳回冲突申请');
            }  
        }
        
        //如果是校级审批，还需要把负责人审批也一起驳回
        if ($type == static::TYPE_SCHOOL) {
            $orderList = static::getConflictOrder($order, static::TYPE_MANAGER, false);
            foreach ($orderList as $conflictOrder) {
                //跳过自身
                if ($conflictOrder->id == $order->id) {
                    continue;
                }
                
                try {
                    Yii::info(static::$type_string[static::TYPE_MANAGER].'审批：驳回Order_'.$conflictOrder->id.'(因为与Order_'.$order->id.')冲突', '驳回冲突申请');
                    static::rejectOrder($conflictOrder, $user, $type, $comment);
                    $rejectOrder[] = $conflictOrder->id;
                } catch (Exception $e) {
                    Yii::error(static::$type_string[static::TYPE_MANAGER].'审批：Order_'.$conflictOrder->id.'异常,'.$e->getMessage(), '驳回冲突申请');
                }   
            }
        }

        return $rejectList;
    }

    /**
     * 查询冲突预约
     *
     * @param Order/Array $order 预约
     * @param int $type 查询类型
     * @param bool $onlyId 仅仅返回id
     * @return Array<Order>
     */
    public static function getConflictOrder($order, $type, $onlyId = true) {
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
                throw new Exception('无效审批类型', Error::INVALID_APPROVE_TYPE);
                break;
        }
        $find = Order::find()->where($where)->orderBy('submit_time ASC');
        if ($onlyId) {
            $result = $find->select(['id'])->asArray()->all();
        } else {
            $result = $find->all();
        }

        return $result;
    }

    /**
     * 自动审批-负责人自动驳回
     *
     * @param Order $order 预约
     * @return null
     * @throws Exception 如果出现异常
     */
    public static function autoApprove1($now = null) {
        if (empty($now)) {
            $now = time();
        }
        $where = ['and'];
        $where[] = ['in', 'room_id', [403,440,441,603,301,302]];
        $where[] = ['=', 'type', Order::TYPE_TWICE];
        $where[] = ['in', 'status', [Order::STATUS_MANAGER_PENDING]];
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
            } catch (Exception $e) {
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
    public static function autoApprove2($now = null) {
        if (empty($now)) {
            $now = time();
        }
        $where = ['and'];
        $where[] = ['in', 'room_id', [403,440,441,603,301,302]];
        $where[] = ['=', 'type', Order::TYPE_TWICE];
        $where[] = ['in', 'status', [Order::STATUS_SCHOOL_PENDING]];
        $month = date("m", $now);
        $year = date("Y", $now);
        $day = date("d", $now);
        $where[] = ['>=', 'date', date("y-m-d", $now)];
        $where[] = [
            'or',
            ['<', 'submit_time', mktime(0, 0, 0, $month, $day - 2, $year)], //提交审批3日之后
            ['<=', 'date', date("y-m-d", mktime(0, 0, 0, $month, $day + 4, $year))] //距离申请日还有3日
        ];
        $result = Order::find()->where($where)->orderBy('submit_time ASC')->all();

        $user = UserService::findIdentity(1)->getUser();
        foreach ($result as $order) {
            try {
                static::approveOrder($order, $user, static::TYPE_SCHOOL, '自动审批通过');
                Yii::info('校级审批：通过Order'.$order->id, '自动审批');
            } catch (Exception $e) {
                //TBD 冲突
                Yii::error('校级审批：异常'.$order->id.','.$e->getMessage(), '自动审批');
            }
            
        }
    }
}