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
use common\exceptions\RoomTableException;
use common\models\entities\Department;
use common\models\entities\Order;
use common\models\entities\OrderOperation;
use common\models\entities\Room;
use common\models\entities\RoomTable;
use common\operations\SubmitOperation;
use common\operations\CancelOperation;

/**
 * 预约相关服务类
 * 负责预约相关操作
 * 提交取消等
 */
class OrderService extends Component {
    
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
        $order->managers = $user->managers;
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
     * 查询单个用户的预约
     * 数据会包含操作记录
     *
     * @param User $user 用户
     * @param int $type 审批类型
     * @param String $start_date 开始时间
     * @param String $end_date 结束时间
     * @return json
     */
    public static function queryMyOrders($user, $start_date, $end_date) {
        $where = ['and'];
        $where[] = ['=', 'user_id', $user->id];
        if ($start_date !== null){
            $where[] = ['>=', 'date', $start_date];
        }
        if ($end_date !== null){
            $where[] = ['<=', 'date', $end_date];
        }

        $result = Order::find()->select(['id'])->where($where)->all();

        $orderList = [];
        $orders = [];
        foreach ($result as $key => $order) {
            $order = static::queryOneOrder($order->id);

            $orderList[] = $order['id'];
            $orders[$order['id']] = $order;
        }

        $data = [
            'orderList' => $orderList,
            'orders' => $orders,
        ];

        return $data;
    }


    /**
     * 查询单条预约的详细信息(带缓存)
     * 数据会包含操作记录
     * 优先使用缓存
     *
     * @param int $type 审批类型
     * @return json
     */
    public static function queryOneOrder($order_id) {
        $cache = Yii::$app->cache;
        $cacheKey = 'Order'.'_'.$order_id;
        $data = $cache->get($cacheKey);
        if ($data == null) {
            Yii::trace($cacheKey.':缓存失效', '数据缓存'); 
            $order = Order::findOne($order_id);
            $data = $order->toArray(['id', 'date', 'room_id', 'hours', 'user_id', 'type', 'status', 'submit_time', 'data', 'issue_time']);
            $data = array_merge($data, $data['data']);
            unset($data['data']);

            $result = OrderOperation::find()->where(['order_id' => $order_id])->all();
            $operationList = [];
            foreach ($result as $key => $orderOp) {
                $orderOp = $orderOp->toArray(['id', 'user_id', 'time', 'type', 'data']);
                $orderOp = array_merge($orderOp, $orderOp['data']);
                unset($orderOp['data']);

                $operationList[] = $orderOp;
            }
            $data['opList'] = $operationList;
            
            $cache->set($cacheKey, $data, 0, new TagDependency(['tags' => $cacheKey]));
        } else {
            Yii::trace($cacheKey.':缓存命中', '数据缓存'); 
        }
        return $data;
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
            ];;
            
            $cache->set($cacheKey, $data, 86400, new TagDependency(['tags' => 'User_'.$user->id]));
        } else {
            Yii::trace($cacheKey.':缓存命中', '数据缓存'); 
        }
        return $data;
       
    }
}