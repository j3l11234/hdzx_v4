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
use yii\base\UserException;

use common\helpers\Error;
use common\models\entities\Department;
use common\models\entities\Order;
use common\models\entities\User;
use common\models\entities\OrderOperation;
use common\models\entities\RoomTable;
use common\services\UserService;
use common\services\OrderService;


/**
 * 审批申请相关服务类
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

    /**
     * 抽象状态_待审批
     */
    const STATUS_ABS_PENDING    = 01;

     /**
     * 抽象状态_通过
     */
    const STATUS_ABS_APPROVED   = 02;

    /**
     * 抽象状态_驳回
     */
    const STATUS_ABS_REJECTED   = 03;
    

    public static $type_string = [
        01 => '琴房',
        02 => '负责人',
        03 => '校级',
    ];


    /**
     * 查询审批申请
     * 数据会包含操作记录
     *
     * @param User $user 用户
     * @param int $type 审批类型
     * @param String $start_date 开始时间
     * @param String $end_date 结束时间
     * @return json
     */
    public static function getApproveOrders($user, $type, $start_date, $end_date,
        $abs_status = NULL, $room_id = NULL, $dept_id = NULL, $onlyId = FALSE) {
        //throw new UserException('没有查询权限', Error::AUTH_FAILED);
        $where = ['and'];
        if ($start_date !== null){
            $where[] = ['>=', 'date', $start_date];
        }
        if ($end_date !== null){
            $where[] = ['<=', 'date', $end_date];
        }
        if ($room_id !== NULL) {
            $where[] = ['in', 'room_id', $room_id];
        }
        if ($dept_id !== NULL) {
            $where[] = ['in', 'dept_id', $dept_id];
        }
        if ($type == static::TYPE_SIMPLE) {
            if (!$user->checkPrivilege(User::PRIV_APPROVE_SIMPLE)) {
                throw new UserException('没有查询权限', Error::AUTH_FAILED);
            }
            $where[] = ['=', 'type', Order::TYPE_SIMPLE];
            if ($abs_status == static::STATUS_ABS_PENDING) {
                $where[] = ['in', 'status', [Order::STATUS_SIMPLE_PENDING]];
            } else if ($abs_status == static::STATUS_ABS_APPROVED) {
                $where[] = ['in', 'status', [Order::STATUS_SIMPLE_APPROVED]];
            } else if ($abs_status == static::STATUS_ABS_REJECTED) {
                $where[] = ['in', 'status', [Order::STATUS_SIMPLE_REJECTED]];
            } else {
                $where[] = ['in', 'status', [Order::STATUS_SIMPLE_PENDING, Order::STATUS_SIMPLE_APPROVED, Order::STATUS_SIMPLE_REJECTED]];
            }
        } else if ($type == static::TYPE_MANAGER) {
            if ($user->checkPrivilege(User::PRIV_APPROVE_MANAGER_ALL)) {
            } elseif ($user->checkPrivilege(User::PRIV_APPROVE_MANAGER_DEPT)){
                $where[] = ['in', 'dept_id', static::queryUserDepts($user)];
            } else {
                throw new UserException('没有查询权限', Error::AUTH_FAILED);
            }
            $where[] = ['=', 'type', Order::TYPE_TWICE];
            if ($abs_status == static::STATUS_ABS_PENDING) {
                $where[] = ['in', 'status', [Order::STATUS_MANAGER_PENDING]];
            } else if ($abs_status == static::STATUS_ABS_APPROVED) {
                $where[] = ['in', 'status', [Order::STATUS_MANAGER_APPROVED, Order::STATUS_SCHOOL_APPROVED]];
            } else if ($abs_status == static::STATUS_ABS_REJECTED) {
                $where[] = ['in', 'status', [Order::STATUS_MANAGER_REJECTED, Order::STATUS_SCHOOL_REJECTED]];
            } else {
                $where[] = ['in', 'status', [Order::STATUS_MANAGER_PENDING, Order::STATUS_MANAGER_APPROVED, Order::STATUS_MANAGER_REJECTED, Order::STATUS_SCHOOL_APPROVED, Order::STATUS_SCHOOL_REJECTED]];
            }
        } else if ($type == static::TYPE_SCHOOL) {
            if (!$user->checkPrivilege(User::PRIV_APPROVE_SCHOOL)) {
                throw new UserException('没有查询权限', Error::AUTH_FAILED);
            }
            $where[] = ['=', 'type', Order::TYPE_TWICE];
            if ($abs_status == static::STATUS_ABS_PENDING) {
                $where[] = ['in', 'status', [Order::STATUS_SCHOOL_PENDING]];
            } else if ($abs_status == static::STATUS_ABS_APPROVED) {
                $where[] = ['in', 'status', [Order::STATUS_SCHOOL_APPROVED]];
            } else if ($abs_status == static::STATUS_ABS_REJECTED) {
                $where[] = ['in', 'status', [Order::STATUS_SCHOOL_REJECTED]];
            } else {
                $where[] = ['in', 'status', [Order::STATUS_SCHOOL_PENDING, Order::STATUS_SCHOOL_APPROVED, Order::STATUS_SCHOOL_REJECTED]];
            }
        } else {
            throw new UserException('无效审批类型', Error::INVALID_APPROVE_TYPE);
        }

        $orders = Order::find()->select(['id'])->where($where)->asArray()->all();
        $order_ids = array_column($orders, 'id');
        $orders = OrderService::getOrders($order_ids);

        $data = [
            'orderList' => $order_ids,
            'orders' => $orders,
        ];

        return $data;
    }

    /**
     * 审批一个申请
     *
     * @param Order $order 申请
     * @return null
     * @throws Exception 如果出现异常
     */
    public static function approveOrder($order, $user, $type, $comment = null) {
        Yii::info(static::$type_string[$type].'审批通过, id='.$order->id.', comment='.$comment, '审批申请');
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
                throw new UserException('无效审批类型', Error::INVALID_APPROVE_TYPE);
                break;
        }

        static::operateOrder($order, $user, $operationClass, $comment);
    }

    /**
     * 驳回一个申请
     *
     * @param Order $order 申请
     * @return null
     * @throws Exception 如果出现异常
     */
    public static function rejectOrder($order, $user, $type, $comment = null) {
        Yii::info(static::$type_string[$type].'审批驳回, id='.$order->id.', comment='.$comment, '审批申请');
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
                throw new UserException('无效审批类型', Error::INVALID_APPROVE_TYPE);
                break;
        }

        static::operateOrder($order, $user, $operationClass, $comment);
    }

    /**
     * 撤回一个申请申请
     *
     * @param Order $order 申请
     * @return null
     * @throws Exception 如果出现异常
     */
    public static function revokeOrder($order, $user, $type, $comment = null) {
        Yii::info(static::$type_string[$type].'审批撤回, id='.$order->id.', comment='.$comment, '审批申请');
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
                throw new UserException('无效审批类型', Error::INVALID_APPROVE_TYPE);
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
            $operation = new $operationClass($order, $user, $roomTable, $extra);
            $operation->doOperation();

            $transaction->commit();

            //清除缓存
            TagDependency::invalidate(Yii::$app->cache, 'RoomTable'.'_'.$order->date.'_'.$order->room_id);
            TagDependency::invalidate(Yii::$app->cache, 'Order'.'_'.$order->id);
            TagDependency::invalidate(Yii::$app->cache, 'User_'.$order->user_id);
        } catch (UserException $e) {
            $transaction->rollBack();
            Yii::error('审批异常, id='.$order->id.', error='.$e->getMessage(), '审批申请');
            throw $e;
        }
    }


    /**
     * 自动驳回冲突的申请
     *
     * @param Order $order 申请
     * @return null
     * @throws Exception 如果出现异常
     */
    public static function rejectConflictOrder($order, $user, $type, $comment = '冲突自动驳回') {
        $rejectList = [];

        $conflictOrder_ids = static::getConflictOrder($order['id'], $user, $type, false);
        foreach ($conflictOrder_ids as $conflictOrder_id) {
            try {
                $conflictOrder = Order::findOne($conflictOrder_id);
                if(!empty($conflictOrder)){
                    Yii::info(static::$type_string[$type].'审批驳回, id='.$conflictOrder_id.', 冲突id='.$order->id, '驳回冲突申请');
                    static::rejectOrder($conflictOrder, $user, $type, $comment);
                    $rejectList[] = $conflictOrder_id;
                }             
            } catch (Exception $e) {
            }  
        }

        return $rejectList;
    }


    /**
     * 批量查询冲突申请
     *
     * @param Order/Array $order 申请
     * @param int $type 查询类型
     * @param bool $onlyId 仅仅返回id
     * @return Array<Order>
     */
    public static function getConflictOrders_batch($order_ids, $user, $type, $useCache = TRUE, $onlyId = TRUE) {
        $conflictOrders_batch = [];
        $orders = OrderService::getOrders($order_ids, $useCache);
        $dateRooms = [];
        foreach ($orders as &$order) {
            $dateRooms[] = $order['date'].'_'.$order['room_id'];
        }
        $dateRooms = array_unique($dateRooms);
        $roomTables = RoomService::getRoomTables($dateRooms, $useCache);

        if ($type == static::TYPE_SIMPLE) {
            $filter_func = function ($order) {
                return $order['type'] == Order::TYPE_SIMPLE && $order['status'] == Order::STATUS_SIMPLE_PENDING;
            };
        } else if ($type == static::TYPE_MANAGER) {
            $manage_depts = static::queryUserDepts($user);
            $filter_func = function ($order) use ($manage_depts) {
                return $order['type'] == Order::TYPE_TWICE &&
                    $order['status'] == Order::STATUS_MANAGER_PENDING &&
                    in_array($order['dept_id'], $manage_depts);
            };
        } else if ($type == static::TYPE_SCHOOL) {
            $filter_func = function ($order) {
                return $order['type'] == Order::TYPE_TWICE && $order['status'] == Order::STATUS_SCHOOL_PENDING;
            };
        } else {
            throw new UserException('无效审批类型', Error::INVALID_APPROVE_TYPE);
        }

        $allConflictOrder_ids = [];
        $conflictOrder_ids_map = [];
        foreach ($orders as $order_id => &$order) {
            $roomTable = $roomTables[$order['date'].'_'.$order['room_id']];
            $conflictOrder_ids = RoomTable::getTable($roomTable['ordered'], $order['hours'], [$order_id]);
            $allConflictOrder_ids = array_merge($allConflictOrder_ids, $conflictOrder_ids);
            $conflictOrder_ids_map[$order_id] = $conflictOrder_ids;
        }
        $allConflictOrders = OrderService::getOrders($allConflictOrder_ids, $useCache);

        foreach ($orders as $order_id => &$order) {
            $conflictOrder_ids = $conflictOrder_ids_map[$order_id];
            $conflictOrders = [];
            foreach ($conflictOrder_ids as $conflictOrder_id) {
                $conflictOrder = $allConflictOrders[$conflictOrder_id];
                if ($filter_func($conflictOrder)) {
                    $conflictOrders[$conflictOrder_id] = $conflictOrder;
                }
            }
            $conflictOrders_batch[$order_id] = $onlyId ? array_keys($conflictOrders) : $conflictOrders;
        }

        return $conflictOrders_batch;
    }


    /**
     * 查询冲突申请
     *
     * @param Order/Array $order 申请
     * @param int $type 查询类型
     * @param bool $onlyId 仅仅返回id
     * @return Array<Order>
     */
    public static function getConflictOrder($order_id, $user, $type, $useCache = TRUE, $onlyId = TRUE) {
        return static::getConflictOrders_batch([$order_id], $user, $type, $useCache,$onlyId)[$order_id];
    }


    /**
     * 查询一个用户所有可审批的dept
     * 优先从缓存中查询
     *
     * @param User $user 用户
     * @return json
     */
    public static function queryUserDepts($user) {
        $cacheKey = 'UserDepts'.$user->id;
        $cache = Yii::$app->cache;
        $data = $cache->get($cacheKey);
        if ($data == null) {
            Yii::trace($cacheKey.':缓存失效', '数据缓存'); 
            $data = [];
            $result = Department::find()
                ->where(['status' => Department::STATUS_ENABLE])
                ->select(['id', 'name', 'parent_id',])
                ->asArray()
                ->all();

            $deptMap = [];
            $depts = [];
            foreach ($result as $key => $dept) {
                if(!isset($deptMap[$dept['parent_id']])){
                    $deptMap[$dept['parent_id']] = [];
                }
                $deptMap[$dept['parent_id']][] = $dept['id'];
                $depts[$dept['id']] = $dept;
            }

            $cascadeDepts = array_merge($user->manage_depts);
            for ($i=0; $i < count($cascadeDepts); $i++) { 
                if (!isset($deptMap[$cascadeDepts[$i]])){
                    continue;
                }
                $childList = $deptMap[$cascadeDepts[$i]];
                foreach ($childList as $dept) {
                    if(!in_array($dept, $cascadeDepts)){
                        $cascadeDepts[] = $dept;
                    }
                }
            }

            $data = $cascadeDepts;
            $cache->set($cacheKey, $data, 86400, new TagDependency(['tags' => ['User_'.$user->id, 'Dept']]));
        }else{
            Yii::trace($cacheKey.':缓存命中', '数据缓存'); 
        }
        return $data;
    }


    /**
     * 自动审批-琴房自动通过
     *
     * @param Order $order 申请
     * @return null
     * @throws Exception 如果出现异常
     */
    public static function autoApprove1($now = null) {
        if (empty($now)) {
            $now = time();
        }
        $where = ['and'];
        $where[] = ['in', 'room_id', [404,405,406,407,408,409,410,411,412,413,414,415,416,417,418,419,420,421,]];
        $where[] = ['=', 'type', Order::TYPE_SIMPLE];
        $where[] = ['in', 'status', [Order::STATUS_SIMPLE_PENDING]];
        $where[] = ['>=', 'date', date("y-m-d", $now)];
        $result = Order::find()->where($where)->orderBy('submit_time ASC')->all();

        $user = UserService::findIdentity(1)->getUser();
        $approves = [];
        $rejects = [];
        foreach ($result as $order) {
            if (in_array($order->id, $rejects)) { //该申请因为冲突已经被驳回
                Yii::trace('跳过已被驳回的申请, id='.$order->id, '自动审批'); 
                continue;
            }
            try {
                Yii::info(static::$type_string[static::TYPE_SIMPLE].'审批通过, id='.$order->id.', reason=琴房审批自动通过', '自动审批');
                static::approveOrder($order, $user, static::TYPE_SIMPLE, '琴房自动通过');
                $approves[] = $order->id;
                $rejects_ = static::rejectConflictOrder($order, $user, ApproveService::TYPE_SIMPLE);
                $rejects = array_merge ($rejects, $rejects_);  
            } catch (UserException $e) {
                if ($e->getCode() == Error::ROOMTABLE_USED) {
                    $comment = '该申请的时段被占用，自动驳回';
                } else if($e->getCode() == Error::ROOMTABLE_LOCKED) {
                    $comment = '该申请的时段被锁定，自动驳回';
                } else {
                    continue;
                }
                try {
                    Yii::info(static::$type_string[static::TYPE_SIMPLE].'审批驳回, id='.$order->id.', reason=琴房审批,'.$comment, '自动审批');
                    static::rejectOrder($order, $user, static::TYPE_SIMPLE, $comment);
                    $rejects[] = $order->id;
                } catch (UserException $e) {
                } 
            }   
        }
        return [
            'approveList' => $approves,
            'rejectList' => $rejects,
        ];
    }

    /**
     * 自动审批-负责人自动驳回
     *
     * @param Order $order 申请
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
        $where[] = ['in', 'status', [Order::STATUS_MANAGER_PENDING]];
        $month = date("m", $now);
        $year = date("Y", $now);
        $day = date("d", $now);
        $where[] = ['>=', 'date', date("y-m-d", $now)];
        $where[] = ['<', 'submit_time', mktime(0, 0, 0, $month, $day - 1, $year)];
        $result = Order::find()->where($where)->orderBy('submit_time ASC')->all();

        $user = UserService::findIdentity(1)->getUser();
        $rejectList = [];
        foreach ($result as $order) {
            try {
                Yii::info(static::$type_string[static::TYPE_MANAGER].'审批驳回, id='.$order->id.', reason=负责人超时未审批', '自动审批');
                $rejectList[] = $order->id;
                static::rejectOrder($order, $user, static::TYPE_MANAGER, '负责人超时未审批，自动驳回');
            } catch (UserException $e) {
            }  
        }

        return [
            'rejectList' => $rejectList,
        ];
    }

    /**
     * 自动审批-3天校级审批自动通过
     *
     * @param Order $order 申请
     * @return null
     * @throws Exception 如果出现异常
     */
    public static function autoApprove3($now = null) {
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
        $approveList = [];
        $rejectList = [];
        foreach ($result as $order) {
            if (in_array($order->id, $rejectList)) { //该申请因为冲突已经被驳回
                 Yii::trace('跳过已被驳回的申请, id='.$order->id, '自动审批'); 
                continue;
            }
            try {
                Yii::info(static::$type_string[static::TYPE_SCHOOL].'审批通过, id='.$order->id.', reason=校级审批自动通过', '自动审批');
                static::approveOrder($order, $user, static::TYPE_SCHOOL, '该申请提交时间最早，自动通过');  
                $approveList[] = $order->id;
                $rejectList_1 = static::rejectConflictOrder($order, $user, ApproveService::TYPE_SCHOOL);
                $rejectList_2 = static::rejectConflictOrder($order, $user, ApproveService::TYPE_MANAGER);
                $rejectList = array_merge ($rejectList, $rejectList_1, $rejectList_2);  
            } catch (UserException $e) {
                if ($e->getCode() == Error::ROOMTABLE_USED) {
                    $comment = '该申请的时段被占用，自动驳回';
                } else if($e->getCode() == Error::ROOMTABLE_LOCKED) {
                    $comment = '该申请的时段被锁定，自动驳回';
                } else {
                    continue;
                }
                try {
                    Yii::info(static::$type_string[static::TYPE_SCHOOL].'审批驳回, id='.$order->id.', reason=校级审批,'.$comment, '自动审批');
                    static::rejectOrder($order, $user, static::TYPE_SCHOOL, $comment);
                    $rejectList[] = $order->id;
                } catch (UserException $e) {
                } 
            }   
        }

        return [
            'approveList' => $approveList,
            'rejectList' => $rejectList,
        ];
    }
}