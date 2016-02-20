<?php
/**
 * @link http://www.j3l11234.com/
 * @copyright Copyright (c) 2015 j3l11234
 * @author j3l11234@j3l11234.com
 */

namespace common\models\services;

use Yii;
use yii\base\Component;
use common\exception\RoomTableException;
use common\models\entities\Department;
use common\models\entities\Order;
use common\models\entities\RoomTable;
use common\models\operations\SubmitOperation;

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
    public static function queryDeptList() {
        $cacheKey = 'deptList';
        $cache = Yii::$app->cache;
        $data = $cache->get($cacheKey);
        if ($data == null) {
            Yii::trace($cacheKey.':缓存失效'); 
            $data = [];
            $result = Department::find()->orderBy('align')->all();
            $deptList = [];
            $depts = [];
            foreach ($result as $key => $dept) {
                $dept = $dept->toArray(['id', 'name']);
                $deptList[] = $dept['id'];
                $depts[$dept['id']] = $dept;
            }
            $data = [
                'deptList' => $deptList,
                'depts' => $depts,
            ];
            $cache->set($cacheKey, $data);
        }else{
            Yii::trace($cacheKey.':缓存命中'); 
        }
        return $data;
    }

    /**
     * 提交一个申请
     *
     * @param Order $order 预约
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
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    /**
     * 查询一个申请(带缓存)
     *
     * @param Order $order 预约
     * @return null
     * @throws Exception 如果出现异常
     */
    public static function queryOneOrder($order_id) {
        $cache = Yii::$app->cache;
        $cacheKey = Order::getCacheKey($order_id);
        $data = $cache->get($cacheKey);
        if ($data == null) {
            Yii::trace($cacheKey.':缓存失效'); 
            $order = Order::findOne($order_id);
            $data = $order->toArray(['id', 'date', 'room_id', 'hours', 'user_id', 'dept_id', 'type', 'status', 'submit_time', 'data', 'issue_time']);
            $data = array_merge($data, $data['data']);
            unset($data['data']);
            $cache->set($cacheKey, $data);
        } else {
            Yii::trace($cacheKey.':缓存命中'); 
        }
        return $data;
    }

}