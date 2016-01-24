<?php
/**
 * @link http://www.j3l11234.com/
 * @copyright Copyright (c) 2015 j3l11234
 * @author j3l11234@j3l11234.com
 */

namespace common\models\service;

use Yii;
use yii\base\Component;
use common\exception\RoomTableException;
use common\models\entities\RoomTable;

/**
 * 房间相关服务类
 * 负责RoomTable数据的读取，获取
 * 预约信息注册，房间锁信息的注册
 */
class RoomService extends Component {

    /**
     * 得到一个房间表
     * 如果方法不存在将会创建一个，多并发情况下可能会创建多个，但是读取的保证是最早创建的唯一一个
     * 调用此方法时不要开事务！
     *
     * @param string $date 预约日期
     * @param integer $room_id 房间id
     * @return RoomTable
     */
    public static function getRoomTable($date, $room_id) {
        $roomTable = RoomTable::findByDateRoom($date, $room_id);
        if ($roomTable === null) {
            $roomTable = new RoomTable();
            $roomTable->date = $date;
            $roomTable->room_id = $room_id;
            $roomTable->save();

            //重新查找，保证并发唯一
            $roomTable = RoomTable::findByDateRoom($date, $room_id);
        }
        return $roomTable;
    }

    /**
     * 应用一个预约
     * 将这个预约信息写入roomTable
     * 该方法将会把对应的roomTable项的id先清空再写入，所以如果$hours为null或者空数组，等同于将该order清除掉
     *
     * @param RoomTable $roomTable roomTable
     * @param id $id 预约id
     * @param array $hours 预约小时数组
     * @param boolean $isUsed true写入used,false写入order
     * @return true 如果写入成功
     * @throws StaleObjectException 如果存在并发冲突
     */
    public static function applyOrder($roomTable, $id, $hours, $isUsed = false) {
        $roomTable->removeOrdered($id);
        $roomTable->removeUsed($id);
        if ($isUsed) {
            $roomTable->addUsed($id, $hours);
        }else{
            $roomTable->addOrdered($id, $hours);
        }
        return $roomTable->save();
    }
}