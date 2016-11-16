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
use common\helpers\DateRoom;
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
    const TYPE_SIMPLE       = 'simple';

    /**
     * 类型_负责人审批
     */
    const TYPE_MANAGER      = 'manager';

    /**
     * 类型_校级审批
     */
    const TYPE_SCHOOL       = 'school';

    /**
     * 抽象状态_待审批
     */
    const STATUS_ABS_PENDING    = 'pending';

     /**
     * 抽象状态_通过
     */
    const STATUS_ABS_APPROVED   = 'approved';

    /**
     * 抽象状态_驳回
     */
    const STATUS_ABS_REJECTED   = 'rejected';
    

    public static $type_string = [
        'simple' => '琴房',
        'manager' => '负责人',
        'school' => '校级',
    ];


    /**
     * 查询审批申请
     * 数据会包含操作记录
     *
     * @param User $user 用户
     * @param int $type 审批类型
     * @param mixed $term 查询条件[]$start_date 开始时间
     * @param bool $onlyId 仅返回id
     * @return array 符合条件的order_id列表
     * ```php
     * $term = [
     *     'start_date' => [开始日期],
     *     'end_date' => [结束日期],
     *     'abs_status' => [抽象状态],
     *     'room_id' => [房间id],
     *     'dept_id' => [部门id],
     * ]
     * ```  
     */
    public static function getApproveOrders($user, $type, $term) {
        $where = ['and'];

        if ($type == static::TYPE_SIMPLE) {
            if (!$user->checkPrivilege(User::PRIV_APPROVE_SIMPLE)) {
                throw new UserException('没有查询权限', Error::AUTH_FAILED);
            }
            $where[] = ['=', 'type', Order::TYPE_SIMPLE];
            $abs_status = !empty($term['abs_status']) ? $term['abs_status'] : NULL;
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
            $abs_status = !empty($term['abs_status']) ? $term['abs_status'] : NULL;
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
            $abs_status = !empty($term['abs_status']) ? $term['abs_status'] : NULL;
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

        if (!empty($term['start_date'])){
            $where[] = ['>=', 'date', $term['start_date']];
        }
        if (!empty($term['end_date'])){
            $where[] = ['<=', 'date', $term['end_date']];
        }
        if (!empty($term['room_id'])){
            $where[] = ['in', 'room_id', $term['room_id']];
        }
        if (!empty($term['dept_id'])){
            $where[] = ['in', 'dept_id', $term['dept_id']];
        }

        $result = Order::find()->select(['id'])->where($where)->asArray()->all();
        $order_ids = array_column($result, 'id');

        return $order_ids;
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
    public static function rejectConflictOrders($order, $user, $type, $comment = '冲突自动驳回') {
        $rejectList = [];

        $ordersInfo = [
            'id' => $order->id,
            'date' => $order->date,
            'room_id' => $order->room_id,
            'hours' => $order->hours,
        ];
        $conflictOrder_ids = static::getConflictOrders($ordersInfo, NULL, FALSE);

        $where = ['and'];
        $where[] = ['in', 'id', $conflictOrder_ids['ordered']];
        if ($type == ApproveService::TYPE_SIMPLE) {
            $where[] = ['in', 'status', [Order::STATUS_SIMPLE_PENDING]];
        } else if ($type == ApproveService::TYPE_MANAGER) {
            $where[] = ['in', 'status', [Order::STATUS_MANAGER_PENDING]];
        } else if ($type == ApproveService::TYPE_SCHOOL) {
            $where[] = ['in', 'status', [Order::STATUS_SCHOOL_PENDING]];
        }

        foreach (Order::find()->where($where)->each(100) as $conflictOrder) {
            try {
                Yii::info(static::$type_string[$type].'审批驳回, id='.$conflictOrder['id'].', 冲突id='.$order->id, '驳回冲突申请');
                static::rejectOrder($conflictOrder, $user, $type, $comment);
                $rejectList[] = $conflictOrder['id'];
                         
            } catch (Exception $e) {
            }  
        }

        return $rejectList;
    }


    /**
     * 批量查询冲突申请
     *
     * @param array $orderInfos orderinfo的数组
     * @param array $include_ids 限制范围，如不为空，则只会从include_ids里面查找冲突申请
     * @param boolean $useCache 是否使用缓存
     * @return array 冲突id的map的map
     * ```php
     * $orderInfos = [
     *     [
     *         'id' => 1,
     *         'date' => '2016-11-11',
     *         'room_id' => 404,
     *         'hours' => [8, 9, 10],
     *     ]
     * ];
     * ``` 
     */
    public static function getConflictOrders_batch($orderInfos, $include_ids, $useCache = TRUE) {
        $conflictOrders_map = [];
        $dateRooms = [];
        
        //批量获取所需的RoomTable
        foreach ($orderInfos as $index => $orderInfo) {
            $dateRooms[$index] = new DateRoom($orderInfo['date'], $orderInfo['room_id']);
        }
        $roomTables = RoomService::getRoomTables($dateRooms, $useCache);

        //批量解析冲突
        foreach ($orderInfos as $index => $orderInfo) {
            $roomTable = $roomTables[$dateRooms[$index]->key];
            $hous = $orderInfo['hours'];
            $order_id = $orderInfo['id'];
            $conflictOrders = [
                'ordered' => RoomTable::getTable($roomTable['ordered'], $hous, $include_ids, [$order_id]),
                'used' => RoomTable::getTable($roomTable['used'], $hous, $include_ids, [$order_id]),
                'rejected' => RoomTable::getTable($roomTable['rejected'], $hous, $include_ids, [$order_id])
            ];

            $conflictOrders_map[$index] = $conflictOrders;
        }
        return $conflictOrders_map;
    }

    /**
     * 查询冲突申请
     *
     * @param array $orderInfo orderinfo的
     * @param array $include_ids 限制范围，如不为空，则只会从include_ids里面查找冲突申请
     * @param boolean $useCache 是否使用缓存
     * @return array 冲突id的map
     * ```php
     * $orderInfo = [
     *     'id' => 1,
     *     'date' => '2016-11-11',
     *     'room_id' => 404,
     *     'hours' => [8, 9, 10],
     * ];
     * ``` 
     */
    public static function getConflictOrders($orderInfo, $include_ids, $useCache = TRUE) {
        $conflictOrders = static::getConflictOrders_batch([$orderInfo], $include_ids, $useCache);
        return isset($conflictOrders[0]) ? $conflictOrders[0] : [];
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
            $deptMap = OrderService::queryDeptList()['deptMap'];
 
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
                $rejects_ = static::rejectConflictOrders($order, $user, ApproveService::TYPE_SIMPLE);
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

        //解析order_info用于分析冲突
        $ordersInfos = [];
        foreach ($result as $order) {
            $ordersInfos[$order['id']] = [
                'id' => $order->id,
                'date' => $order->date,
                'room_id' => $order->room_id,
                'hours' => $order->hours,
            ];
        }

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
                $rejectList_1 = static::rejectConflictOrders($order, $user, ApproveService::TYPE_SCHOOL);
                $rejectList_2 = static::rejectConflictOrders($order, $user, ApproveService::TYPE_MANAGER);
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