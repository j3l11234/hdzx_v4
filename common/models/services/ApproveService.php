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
use common\exception\OrderQueryException;
use common\models\entities\Department;
use common\models\entities\Order;
use common\models\entities\User;
use common\models\entities\OrderOperation;
use common\models\services\UserService;
use common\models\operations\SubmitOperation;

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
                    new OrderQueryException('没有查询权限', OrderQueryException::AUTH_FAILED);
                }
                break;
            case static::TYPE_MANAGER:
                $where[] = ['=', 'type', Order::TYPE_TWICE];
                $where[] = ['in', 'status', [Order::STATUS_MANAGER_PENDING, Order::STATUS_MANAGER_APPROVED, Order::STATUS_MANAGER_REJECTED]];
                if ($user->checkPrivilege(User::PRIV_APPROVE_MANAGER_ALL)) {
                } elseif ($user->checkPrivilege(User::PRIV_APPROVE_MANAGER_DEPT)){
                    $where[] = ['in', 'dept_id', $user->getApproveDeptList()];
                } else {
                    new OrderQueryException('没有查询权限', OrderQueryException::AUTH_FAILED);
                }
                break;
            case static::TYPE_SCHOOL:
                $where[] = ['=', 'type', Order::TYPE_TWICE];
                $where[] = ['in', 'status', [Order::STATUS_SCHOOL_PENDING, Order::STATUS_SCHOOL_APPROVED, Order::STATUS_SCHOOL_REJECTED]];
                if (!$user->checkPrivilege(User::PRIV_APPROVE_SCHOOL)) {
                    new OrderQueryException('没有查询权限', OrderQueryException::AUTH_FAILED);
                }
                break;
            default:
                new OrderQueryException('查询类型异常', OrderQueryException::AUTH_FAILED);
                break;
        }

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
     * 查询单条审批预约的详细信息
     * 数据会包含操作记录
     * 优先使用缓存
     *
     * @param int $type 审批类型
     * @return json
     */
    public static function queryOneOrder($order_id) {
        $cache = Yii::$app->cache;
        $cacheKey = Order::getCacheKey($order_id).'_approve';
        $data = $cache->get($cacheKey);
        if ($data == null) {
            Yii::trace($cacheKey.':缓存失效'); 
            $order = Order::findOne($order_id);
            $data = $order->toArray(['id', 'date', 'room_id', 'hours', 'user_id', 'dept_id', 'type', 'status', 'submit_time', 'data', 'issue_time']);
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
            $data['chksum'] = substr(md5(json_encode($data)), 0, 6);
            
            $cache->set($cacheKey, $data);
        } else {
            Yii::trace($cacheKey.':缓存命中'); 
        }
        return $data;
    }
}