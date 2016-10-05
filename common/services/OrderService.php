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
use common\helpers\HdzxException;
use common\helpers\Error;
use common\models\entities\BaseUser;
use common\models\entities\User;
use common\models\entities\StudentUser;
use common\models\entities\Department;
use common\models\entities\Order;
use common\models\entities\OrderOperation;
use common\models\entities\Room;
use common\models\entities\RoomTable;
use common\operations\SubmitOperation;
use common\operations\CancelOperation;
use common\operations\IssueOperation;

/**
 * 预约相关服务类
 * 负责预约相关操作
 * 提交取消等
 */
class OrderService extends Component {

    /**
     * 查询部门列表(带缓存)
     * 优先从缓存中查询
     *
     * @return json
     */
    public static function queryDeptList($onlyId = FALSE, $useCache = TRUE) {
        $deptList;

        //读取缓存
        $cacheMiss;
        if ($useCache) {
            $cacheKey = 'DeptList';
            $cacheData = Yii::$app->cache->get($cacheKey);
            if ($cacheData == null) {
                Yii::trace($cacheKey.':缓存失效', '数据缓存');
                $cacheMiss = TRUE;
            } else {
                Yii::trace($cacheKey.':缓存命中', '数据缓存');
                $deptList = $cacheData;
                $cacheMiss = FALSE;
            }
        } else {
            $cacheMiss = TRUE;
        }

        if($cacheMiss) {
            $deptMap = [];
            $depts = [];
            foreach (Department::find()
                ->where(['status' => Department::STATUS_ENABLE])
                ->select(['id', 'name', 'parent_id', 'choose', 'usage_limit'])
                ->orderBy('align')
                ->asArray('align')
                ->each(100) as $dept) {
                $dept['usage_limit'] = json_decode('usage_limit');
                $parent_id = $dept['parent_id'];
                if(!isset($deptMap[$parent_id])){
                    $deptMap[$parent_id] = [];
                }
                $deptMap[$parent_id][] = $dept['id'];
                $depts[$dept['id']] = $dept;
            }

            $deptList = [
                'deptMap' => $deptMap,
                'depts' => $depts,
            ];

            //写入缓存
            $cacheKey = 'DeptList';
            Yii::$app->cache->set($cacheKey, $deptList,
                Yii::$app->params['cache.duration'],
                new TagDependency(['tags' => ['Room']]));
            Yii::trace($cacheKey.':写入缓存', '数据缓存'); 
        }

        if ($onlyId) {
            return array_keys($deptList['depts']);
        }
        return $deptList;
    }


    /**
     * 提交一个申请
     *
     * @param Order $order 预约
     * @param BaseUser $user 用户
     * @return null
     * @throws Exception 如果出现异常
     */
    public static function submitOrder($order, $user) {
        $roomTable = RoomService::getRoomTable($order->date, $order->room_id);
        $connection = Yii::$app->db;
        $transaction = $connection->beginTransaction();

        try {
            $order->save();
            $submitOp = new SubmitOperation($order, $user, $roomTable);
            $submitOp->doOperation();
            $transaction->commit();

            //清除缓存
            TagDependency::invalidate(Yii::$app->cache, 'RoomTable'.'_'.$order->date.'_'.$order->room_id);
            TagDependency::invalidate(Yii::$app->cache, 'Order'.'_'.$order->id);
            TagDependency::invalidate(Yii::$app->cache, 'User_'.$order->user_id);

            Yii::info('提交申请, id='.$order->id, '申请操作');
        } catch (Exception $e) {
            $transaction->rollBack();
            throw $e;
        }
    }


    /**
     * 取消一个申请
     *
     * @param Order $order 预约
     * @param BaseUser $user 用户
     * @return null
     * @throws Exception 如果出现异常
     */
    public static function cancelOrder($order, $user) {
        $roomTable = RoomService::getRoomTable($order->date, $order->room_id);
        $connection = Yii::$app->db;
        $transaction = $connection->beginTransaction();
        
        try {
            $operation = new CancelOperation($order, $user, $roomTable);
            $operation->doOperation();
            $transaction->commit();

            //清除缓存
            TagDependency::invalidate(Yii::$app->cache, 'RoomTable'.'_'.$order->date.'_'.$order->room_id);
            TagDependency::invalidate(Yii::$app->cache, 'Order'.'_'.$order->id);
            TagDependency::invalidate(Yii::$app->cache, 'User_'.$order->user_id);

            Yii::info('取消申请, id='.$order->id, '申请操作');
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;    
        }
    }


    /**
     * 获取纸质申请表
     *
     * @param Order $order 预约
     * @param BaseUser $user 用户
     * @return null
     * @throws Exception 如果出现异常
     */
    public static function paperOrder($order, $user) {
        if (!$user->checkPrivilege(User::PRIV_ADMIN) &&
            $user->id != $order->user_id) {
            throw new HdzxException('该账号无权打印申请表', Error::AUTH_FAILED);
        }

        if ($order->status != Order::STATUS_SCHOOL_APPROVED) {
            throw new HdzxException('申请状态异常', Error::INVALID_ORDER_STATUS);
        }

        $data = static::getOrders([$order->id], $useCache = true)[$order->id];
        $hourRange = Order::hours2Range($data['hours']);
        $orderData = [
            'title'                 => $data['title'],
            'student_no'            => $data['student_no'],
            'room_name'             => $data['room_name'],
            'number'                => $data['number'],
            'activity_type'         => $data['activity_type'],
            'dept_name'             => $data['dept_name'],
            'date'                  => $data['date'],
            'start_hour'            => $hourRange['start_hour'],
            'end_hour'              => $hourRange['end_hour'],
            'prin_student'          => $data['prin_student'],
            'prin_student_phone'    => $data['prin_student_phone'],
            'prin_teacher'          => $data['prin_teacher'],
            'prin_teacher_phone'    => $data['prin_teacher_phone'],
            'need_media'            => $data['need_media'],
            'content'               => $data['content'],
            'secure'                => $data['secure'],
            'apply_time'            => time(),
        ];
        return $orderData;
    }



    /**
     * 发放开门条
     *
     * @param Order $order 预约
     * @param BaseUser $user 用户
     * @param String $comment 备注
     * @return null
     * @throws Exception 如果出现异常
     */
    public static function issueOrder($order, $user, $comment = null) {
        $roomTable = RoomService::getRoomTable($order->date, $order->room_id);
        $extra = [
            'comment' => $comment,
        ];

        $connection = Yii::$app->db;
        $transaction = $connection->beginTransaction();
        

        try {
            $operation = new IssueOperation($order, $user, $roomTable, $extra);
            $operation->doOperation();
            $transaction->commit();

            //清除缓存
            TagDependency::invalidate(Yii::$app->cache, 'Order'.'_'.$order->id);

            Yii::info('发放开门条, id='.$order->id, '申请操作');
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;    
        }
    }



    /**
     * 查询预约的详细信息(带缓存)
     * 数据会包含操作记录
     * 优先使用缓存
     *
     * @param Array $order_ids order_id列表
     * @param boolean $useCache 是否使用缓存
     * @return Array order的Map
     */
    public static function getOrders($order_ids, $useCache = true) {
        $orders = [];

        //读取缓存
        $cacheMisses;
        if ($useCache) {
            $cacheMisses = [];
            Yii::beginProfile('Order读取缓存', '数据缓存');
            foreach ($order_ids as $order_id) {
                $cacheKey = 'Order'.'_'.$order_id;
                $cacheData = Yii::$app->cache->get($cacheKey);
                if ($cacheData == null) {
                    Yii::trace($cacheKey.':缓存失效', '数据缓存');
                    $cacheMisses[] = $order_id;
                } else {
                    Yii::trace($cacheKey.':缓存命中', '数据缓存');
                    $orders[$order_id] = $cacheData;
                }
            }
            Yii::endProfile('Order读取缓存', '数据缓存');
        } else {
            $cacheMisses = $order_ids;
        }

        //获取剩下数据(缓存miss的)
        if(count($cacheMisses) > 0) {
            $cacheNews = [];
            foreach (Order::find()
                ->where(['in', 'id', $cacheMisses])
                ->select(['id', 'date', 'room_id', 'hours', 'user_id', 'dept_id', 'type', 'status', 'submit_time', 'data', 'issue_time'])
                ->asArray()->each(100) as $order) {
                $order['hours'] = json_decode($order['hours'], true);
                $order = array_merge($order, json_decode($order['data'], true));
                unset($order['data']);
                $order['opList'] = [];
                $orders[$order['id']] = $order;
                $cacheNews[] = $order['id'];
            }

            foreach (OrderOperation::find()
                ->where(['in', 'order_id', $cacheMisses])
                ->select(['id', 'order_id', 'user_id', 'time', 'type', 'data'])
                ->orderBy('time')
                ->asArray()->each(100) as $orderOp) {
                $orderOp = array_merge($orderOp, json_decode($orderOp['data'], true));
                unset($orderOp['data']);
                $orders[$orderOp['order_id']]['opList'][] = $orderOp;
            }

            //写入缓存
            Yii::beginProfile('Order写入缓存', '数据缓存');
            foreach ($cacheNews as $order_id) {
                $order = $orders[$order_id];
                $cacheKey = 'Order'.'_'.$order_id;
                Yii::$app->cache->set($cacheKey, $order,
                    Yii::$app->params['cache.duration'],
                    new TagDependency(['tags' => [$cacheKey, 'Order']]));
                Yii::trace($cacheKey.':写入缓存', '数据缓存'); 
            }
            Yii::endProfile('Order写入缓存', '数据缓存');
        }

        return $orders;
    }


    /**
     * 查询单个用户的预约
     * 数据会包含操作记录
     *
     * @param User $user 用户
     * @param int $type 审批类型
     * @param String $start_date 开始时间
     * @param String $end_date 结束时间
     * @return json
     */
    public static function getMyOrders($user, $start_date, $end_date) {
        $where = ['and'];
        $where[] = ['=', 'user_id', $user->id];
        if ($start_date !== null){
            $where[] = ['>=', 'date', $start_date];
        }
        if ($end_date !== null){
            $where[] = ['<=', 'date', $end_date];
        }

        $orders = Order::find()->select(['id'])->where($where)->all();
        $order_ids = array_column($orders, 'id');
        $orders = static::getOrders($order_ids);

        $data = [
            'orderList' => $order_ids,
            'orders' => $orders,
        ];

        return $data;
    }


    /**
     * 查询单个用户的预约
     * 数据会包含操作记录
     *
     * @param User $user 用户
     * @param String $username 用户名
     * @param String $start_date 开始时间
     * @param String $end_date 结束时间
     * @return json
     */
    public static function getIssueOrders($user, $username, $start_date, $end_date) {
        if (!$user->checkPrivilege(BaseUser::PRIV_ISSUE)) {
            throw new HdzxException('该账号无开门条发放权限', Error::AUTH_FAILED);
        }
        $user_id = User::findByUsername($username, NULL, true);
        if(!is_null($user_id)){
            $user_ids[] = $user_id;
        }
        if (preg_match("/^\d{8}$/",$username)) {
            $user_ids[] = 'S'.$username;
        }

        $where = ['and'];
        if ($start_date !== null){
            $where[] = ['>=', 'date', $start_date];
        }
        if ($end_date !== null){
            $where[] = ['<=', 'date', $end_date];
        }
        $where[] = ['in', 'user_id', $user_ids];

        $orders = Order::find()
            ->select(['id'])
            ->where($where)
            ->orderBy('submit_time')
            ->all();
        $order_ids = array_column($orders, 'id');
        $orders = static::getOrders($order_ids);

        $data = [
            'orderList' => $order_ids,
            'orders' => $orders,
        ];

        return $data;
    }


    /**
     * 批量获取OrderTable
     *
     * @param Array $dateRooms [日期房间]的数组
     * @return Array Map形式的OrderTable
     */

    public static function getOrderTables($dateRooms) {
        $orderTables = [];

        //生成搜索条件
        $orderWhere = [];
        foreach ($dateRooms as $dateRoom) {
            $dateRoomSplit = explode('_', $dateRoom);
            $date = $dateRoomSplit[0];
            $room_id = $dateRoomSplit[1];
            if (!isset($orderWhere[$date])){
                $orderWhere[$date] = [];
            }
            $orderWhere[$date][] = $room_id;

            $orderTables[$dateRoom] = [
                'ordered' => [],
                'used' => [],
            ];
        }


        $orderFind = Order::find()->where('1=0')->select(['id', 'date', 'room_id', 'status', 'hours']);
        foreach ($orderWhere as $date => $rooms_ids) {
            $orderFind->union(Order::find()->where(['date'=>$date,'room_id'=>$rooms_ids])
                ->select(['id', 'date', 'room_id', 'status', 'hours']));
        }
        
        foreach ($orderFind->asArray()->each(100) as $order) {
            $dateRoom = $order['date'].'_'.$order['room_id'];
            $order['hours'] = json_decode($order['hours'], true);

            $rtStatus = Order::getRoomTableStatus($order['status']);
            if ($rtStatus == Order::ROOMTABLE_ORDERED) {
                 $orderTables[$dateRoom]['ordered'] = RoomTable::addTable($orderTables[$dateRoom]['ordered'], $order['id'], $order['hours']);
            } else if ($rtStatus == Order::ROOMTABLE_USED) {
                $orderTables[$dateRoom]['used'] = RoomTable::addTable($orderTables[$dateRoom]['used'], $order['id'], $order['hours']);
            } 
        }
        return $orderTables;
    }


    /**
     * 查询单个用户本周本月的使用情况
     * 优先使用缓存
     *
     * @param BaseUSer $user 用户
     * @param data $now 查询时间
     * @param boolean $useCache 是否使用缓存
     * @return json
     */
    public static function queryUsage($user, $now = null, $useCache = true) {
        if (empty($now)) {
            $now = time();
        }

        $startHour = Yii::$app->params['order.startHour'];
        $endHour = Yii::$app->params['order.endHour'];
        $maxHour = $endHour-$startHour;

        $date = date('Y-m-d', $now);
        $cache = Yii::$app->cache;
        $cacheKey = 'Usage_'.$user->id.'_'.$date;
        $data = $cache->get($cacheKey);
        if ($data == null || !$useCache) {
            Yii::trace($cacheKey.':缓存失效', '数据缓存'); 

            $monthUsage = [];
            $weekUsage = [];
            $roomList = Room::getOpenRooms(true);
            foreach ($roomList as $room_id) {
                $monthUsage[$room_id] = [
                    'used' => 0,
                    'ordered' => 0,
                    'avl' => $maxHour*date('t', $now),
                ];
                $weekUsage[$room_id] = [
                    'used' => 0,
                    'ordered' => 0,
                    'avl' => $maxHour*7,
                ];
            }

            //计算本周本月的起止日期
            $weekDay = date('w', $now);
            $monthDay = date('j', $now);
            $month = date("m", $now);
            $year = date("Y", $now);
            $monthStart = mktime(0, 0, 0, $month, 1, $year);
            $monthEnd = mktime(23, 59, 59, $month, date('t', $now), $year);
            $weekStart = mktime(0, 0, 0, $month, $monthDay-(($weekDay + 6) % 7), $year);
            $weekEnd = mktime(23, 59, 59, $month, $monthDay+(6 - ($weekDay + 6) % 7), $year);

            //查找本月的申请
            $where = ['and'];
            $where[] = ['>=', 'date', date('Y-m-d', $monthStart)];
            $where[] = ['<=', 'date', date('Y-m-d', $monthEnd)];
            $where[] = ['=', 'user_id', $user->id];
            $where[] = ['in', 'status', [
                Order::STATUS_SIMPLE_PENDING, Order::STATUS_SIMPLE_APPROVED,
                Order::STATUS_MANAGER_PENDING, Order::STATUS_MANAGER_APPROVED,
                Order::STATUS_SCHOOL_PENDING, Order::STATUS_SCHOOL_APPROVED,
            ]];
            $result = Order::find()->select(['id', 'date', 'room_id', 'status', 'hours'])->where($where)->all();
            //计算本月的使用量
            foreach ($result as $order) {
                $hours = $order->hours;
                $room_id = (string)$order->room_id;
                if (in_array($room_id, $roomList)) {
                    if ($order->status == Order::STATUS_SIMPLE_PENDING || 
                        $order->status == Order::STATUS_MANAGER_PENDING || 
                        $order->status == Order::STATUS_SCHOOL_PENDING ){
                        $monthUsage[$room_id]['ordered'] += count($hours);
                    } else {
                        $monthUsage[$room_id]['used'] += count($hours);
                    }
                }
            }

            //查找本周的申请
            $where = ['and'];
            $where[] = ['>=', 'date', date('Y-m-d', $weekStart)];
            $where[] = ['<=', 'date', date('Y-m-d', $weekEnd)];
            $where[] = ['=', 'user_id', $user->id];
            $where[] = ['in', 'status', [
                Order::STATUS_SIMPLE_PENDING, Order::STATUS_SIMPLE_APPROVED,
                Order::STATUS_MANAGER_PENDING, Order::STATUS_MANAGER_APPROVED,
                Order::STATUS_SCHOOL_PENDING, Order::STATUS_SCHOOL_APPROVED,
            ]];
            $result = Order::find()->select(['id', 'date', 'room_id', 'status', 'hours'])->where($where)->all();
            //计算本周的使用量
            foreach ($result as $order) {
                $hours = $order->hours;
                $room_id = (string)$order->room_id;
                if (in_array($room_id, $roomList)) {
                    if ($order->status == Order::STATUS_SIMPLE_PENDING || 
                        $order->status == Order::STATUS_MANAGER_PENDING || 
                        $order->status == Order::STATUS_SCHOOL_PENDING ){
                        $weekUsage[$room_id]['ordered'] += count($hours);
                    } else {
                        $weekUsage[$room_id]['used'] += count($hours);
                    }
                }     
            }

            //获取限额信息
            $limits = $user->usage_limit != null ? $user->usage_limit : Yii::$app->params['usageLimit'];
            foreach ($limits as $limit) {
                if ($limit['type'] == 'month') { //计算月限额
                    $useSum = 0;
                    foreach ($limit['rooms'] as $room_id) {
                        if (in_array($room_id, $roomList)) {
                            $useSum += ($monthUsage[$room_id]['ordered'] + $monthUsage[$room_id]['used']);
                        }
                    }

                    $avl = $limit['max'] - $useSum;
                    if ($avl < 0) {
                        $avl = 0;
                    }

                    foreach ($limit['rooms'] as $room_id) {
                        if (in_array($room_id, $roomList)) {
                            if ($monthUsage[$room_id]['avl'] > $avl) {
                                $monthUsage[$room_id]['avl'] = $avl;
                            }
                        }
                    }
                } else if ($limit['type'] == 'week') { //计算周限额
                    $useSum = 0;
                    foreach ($limit['rooms'] as $room_id) {
                        if (in_array($room_id, $roomList)) {
                            $useSum += ($weekUsage[$room_id]['ordered'] + $weekUsage[$room_id]['used']);
                        }
                    }

                    $avl = $limit['max'] - $useSum;
                    if ($avl < 0) {
                        $avl = 0;
                    }

                    foreach ($limit['rooms'] as $room_id) {
                        if (in_array($room_id, $roomList)) {
                            if ($weekUsage[$room_id]['avl'] > $avl || $weekUsage[$room_id]['avl'] == -1) {
                                $weekUsage[$room_id]['avl'] = $avl;
                            }
                        }
                    }
                }
            }

            Yii::trace('$monthUsage:'."\n".var_export($monthUsage, true), __METHOD__);
            Yii::trace('$weekUsage:'."\n".var_export($weekUsage, true), __METHOD__);
            Yii::trace('$limits:'."\n".var_export($limits, true), __METHOD__);
            
            $data = [
                'month' => $monthUsage,
                'week' => $weekUsage,
            ];
            
            $cache->set($cacheKey, $data,
                Yii::$app->params['cache.duration'],
                new TagDependency(['tags' => 'User_'.$user->id]));
        } else {
            Yii::trace($cacheKey.':缓存命中', '数据缓存'); 
        }
        return $data;
       
    }
}