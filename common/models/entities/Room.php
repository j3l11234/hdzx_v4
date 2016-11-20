<?php
/**
 * @link http://www.j3l11234.com/
 * @copyright Copyright (c) 2015 j3l11234
 * @author j3l11234@j3l11234.com
 */

namespace common\models\entities;

use DateTime;
use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use common\behaviors\JsonBehavior;

/**
 * 房间
 *
 * @property integer $id
 * @property integer $number 房间号
 * @property string $name 房间名
 * @property integer $type 房间类型
 * @property string $data
 * ```php
 * $data = [
 *     'secure' => 1, //是否需要填写安全信息
 *     'by_week' => 1, //按周开放
 *     'open_time' => '07:00:00', //开放时间
 *     'max_before' => 30, //最大提前
 *     'min_before' => 5, //最小提前申请
 *     'max_hour' => 2, //单次申请最长时间
 * ]
 * ```  
 * @property integer $align 排序依据
 * @property integer $status 房间状态
 * @property integer $created_at
 * @property integer $updated_at
 */
class Room extends ActiveRecord {
    /**
     * 房间类型 琴房
     */
    const TYPE_SIMPLE     = 1;
    /**
     * 房间类型 活动室
     */
    const TYPE_ACTIVITY   = 2;

    /**
     * 房间状态 关闭
     */
    const STATUS_CLOSE  = 0;
    /**
     * 房间状态 开放申请
     */
    const STATUS_OPEN   = 1;


    const WEEK_START = 1;

    /**
     * @inheritdoc
     */
    public static function tableName() {
        return '{{%room}}';
    }

    /**
     * @inheritdoc
     */
    public function behaviors() {
        return [
            [
                'class' => TimestampBehavior::className(),
            ],[
                'class' => JsonBehavior::className(),
                'attributes' => ['data'],
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['id', 'number', 'name', 'type', 'data', 'align', 'status'], 'safe'],
        ];
    }


    public static function getOpenPeriod($roomData, $date) {
        $max_before = (int)($roomData['max_before']);
        $min_before = (int)($roomData['min_before']);
        $openTime = strtotime($roomData['open_time']) - strtotime('0:0:0');
        $closeTime = strtotime($roomData['close_time']) - strtotime('0:0:0');

        if ($roomData['by_week'] == 0) {
            $periodStart = (new DateTime($date))->modify("-{$max_before} days")
                ->setTime(0, 0, 0)->modify("{$openTime} seconds");
            $periodEnd = (new DateTime($date))->modify("-{$min_before} days")
                ->setTime(0, 0, 0)->modify("{$closeTime} seconds");
        } else { //以周计算
            $periodStart = new DateTime($date);
            $periodEnd = (new DateTime($date))->modify("-{$min_before} days")
                ->setTime(0, 0, 0)->modify("{$closeTime} seconds");

            $weekDay = $periodStart->format('w');
            $offset = ((static::WEEK_START+7-$weekDay)%7-7) - ($max_before-($max_before%7));
            $periodStart->modify("{$offset} days")
                ->setTime(0, 0, 0)->modify("{$openTime} seconds");
        }

        return [
            'start' => $periodStart->getTimestamp(),
            'end' => $periodEnd->getTimestamp(),
        ];
    }

    /**
     * 得到开放的日期范围
     *
     * @param int $max_before 最大提前日期
     * @param int $min_before 最小提前日期
     * @param int $by_week 是否按周开放，1为是
     * @param string $open_time 开放时间，默认为[00:00:00]
     * @param int $now 参考时间，默认为当前时间
     * @return array 返回的格式
     * [
     *      'start' => $limitStart,
     *      'end' => $limitEnd
     *      'expired' => 过期时间
     * ]
     */
    public static function getDateRange($roomData, $now = null) {
         

        $now = $now === null ? time() : $now;
        $max_before = (int)($roomData['max_before']);
        $min_before = (int)($roomData['min_before']);
        $openTime = strtotime($roomData['open_time'], $now) - strtotime('0:0:0', $now);
        $closeTime = strtotime($roomData['close_time'], $now) - strtotime('0:0:0', $now);

        if ($roomData['by_week'] == 0) {
            $limitStart = (new DateTime())->setTimestamp($now)->modify("-{$closeTime} seconds")
                ->modify("{$min_before} days")->modify("1 day")->setTime(0, 0, 0);
            $limitEnd = (new DateTime())->setTimestamp($now)->modify("-{$openTime} seconds")
                ->modify("{$max_before} days")->setTime(23, 59, 59);
        } else { //以周计算
            $limitStart = (new DateTime())->setTimestamp($now)->modify("-{$closeTime} seconds")
                ->modify("{$min_before} days")->modify("1 day")->setTime(0, 0, 0);
            $limitEnd = (new DateTime())->setTimestamp($now)
                ->modify("-{$openTime} seconds");

            $weekDay = (int)($limitEnd->format('w'));
            $offset = (((static::WEEK_START+7-$weekDay)%7+6)%7) + ($max_before-($max_before%7));
            $limitEnd->modify("{$offset} days")->setTime(23, 59, 59);
        }

        $nowDiff = strtotime('0:0:0', $now) - $now;
        // $expired = 0;
        // if ($closeTime >= $openTime) {
        //     if ($nowDiff <= $openTime) {
        //         $expired = $openTime - $nowDiff;
        //     } else if ($nowDiff > $openTime && $nowDiff <= $closeTime) {
        //         $expired = $closeTime - $nowDiff;
        //     } else if ($nowDiff > $closeTime) {
        //         $expired = $openTime - $nowDiff + 86400;
        //     }
        // } else if($closeTime < $openTime) {
        //     if ($nowDiff <= $closeTime) {
        //         $expired = $closeTime - $nowDiff;
        //     } else if ($nowDiff > $closeTime && $nowDiff <= $openTime) {
        //         $expired = $openTime - $nowDiff;
        //     } else if ($nowDiff > $openTime) {
        //         $expired = $closeTime - $nowDiff + 86400;
        //     }
        // }
       

        return [
            'start' => $limitStart->getTimestamp(),
            'end' => $limitEnd->getTimestamp(),
            //'expired' => $expired
        ];
    }

    /**
     * 验证日期是否在可申请范围内
     *
     * @param string $date 测试日期 形如'2015-12-15'
     * @param int $max_before 最大提前日期
     * @param int $min_before 最小提前日期
     * @param int $by_week 是否按周开放，1为是
     * @param string $open_time 开放时间，默认为[00:00:00]
     * @param int $now 参考时间，默认为当前时间
     * @return boolean 是否可以申请
     */
    public static function checkOpen($roomData, $date, $now = null) {
        $now = $now === null ? time() : $now;
        $range = static::getOpenPeriod($roomData, $date);

        return ($now >= $range['start'] && $now < $range['end']);
    }

    /**
     * 得到所有开启的房间
     *
     * @param boolean $onlyId 仅获取id
     * @return 如果onlyId未真，返回room_id的列表，否则返回room的Map
     */
    public static function getOpenRooms($onlyId = FALSE) {
        $find = static::find()
            ->where(['status' => self::STATUS_OPEN])
            ->orderBy('align');

        if ($onlyId) {
            $rooms = $find->select(['id'])->asArray()->all();
            $room_ids = array_column($rooms, 'id');
            return $room_ids;
        } else {
            $_rooms = $find->select(['id','number', 'name', 'type', 'data'])->asArray()->all();
            $rooms = [];
            foreach ($_rooms as $room) {
                $room = array_merge($room, json_decode($room['data'], TRUE));
                unset($room['data']);
                $rooms[$room['id']] = $room;
            }
            return $rooms;
        }
    }
}
