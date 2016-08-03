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
use common\models\entities\Department;
use common\models\entities\Order;
use common\models\entities\OrderOperation;
use common\models\entities\RoomTable;
use common\models\operations\SubmitOperation;

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
            Yii::trace($cacheKey.':缓存失效'); 
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
            Yii::trace($cacheKey.':缓存命中'); 
        }
        return $data;
    }

    /**
     * 查询单个用户的一周房间使用情况
     * 优先使用缓存
     *
     * @param string $user_id 用户id
     * @param data $date 查询时间
     * @return json
     */
    public static function queryWeekUsage($user_id, $now = null) {
        if (empty($now)) {
            $now = time();
        }
        $weekDay = date('w', $now);
        $start_date = date('Y-m-d', strtotime('-'.(($weekDay + 6) % 7).' days', $now));
        $end_date = date('Y-m-d', strtotime((6 - ($weekDay + 6) % 7).' days', $now));

        $cache = Yii::$app->cache;
        $cacheKey = 'WeekUsage_'.$user_id.'_'.$start_date;
        $data = $cache->get($cacheKey);
        if ($data == null) {
            Yii::trace($cacheKey.':缓存失效'); 
            $where = ['and'];
            $where[] = ['>=', 'date', $start_date];
            $where[] = ['<=', 'date', $end_date];
            $where[] = ['=', 'user_id', $user_id];
            $where[] = ['in', 'status', [
                Order::STATUS_AUTO_PENDING, Order::STATUS_AUTO_APPROVED,
                Order::STATUS_MANAGER_PENDING, Order::STATUS_MANAGER_APPROVED,
                Order::STATUS_SCHOOL_PENDING, Order::STATUS_SCHOOL_APPROVED,
            ]];
            $where[] = ['=', 'status', Order::STATUS_PASSED];
            $result = Order::find()->select(['id', 'room_id', 'hours'])->where($where)->all();

            $usage = [];
            foreach ($result as $order) {
                $hours = $order->hours;
                $room_id = (string)$order->room_id;
                if (!isset($usage[$room_id])) {
                    $usage[$room_id] = 0;
                }
                $usage[$room_id] += count($hours);
            }
            $data = $usage;
            
            $cache->set($cacheKey, $data);
        } else {
            Yii::trace($cacheKey.':缓存命中'); 
        }
        return $data;
       
    }
}