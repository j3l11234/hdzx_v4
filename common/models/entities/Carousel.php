<?php
/**
 * @link http://www.j3l11234.com/
 * @copyright Copyright (c) 2015 j3l11234
 * @author j3l11234@j3l11234.com
 */

namespace common\models\entities;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "hdzx_carousel".
 *
 * @property integer $id
 * @property string $title
 * @property string $content
 * @property string $picture
 * @property integer $status
 * @property integer $align
 * @property integer $created_at
 * @property integer $updated_at
 */
class Carousel extends ActiveRecord {
    /**
     * 状态 禁用
     */
    const STATUS_DISABLED = 0;
    /**
     * 状态 正常显示
     */
    const STATUS_ENABLE = 1;

    /**
     * @inheritdoc
     */
    public static function tableName() {
        return '{{%carousel}}';
    }

    /**
     * @inheritdoc
     */
    public function behaviors() {
        return [
            TimestampBehavior::className(),
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['title', 'content', 'picture', ], 'safe'],
            [['status', ], 'required'],
            [['align', ], 'integer'],
            ['status', 'in', 'range' => [static::STATUS_DISABLED, static::STATUS_ENABLE]],
        ];
    }

     /**
     * 得到启用的轮播
     *
     * @param Lock $lock_id 房间锁
     * @return json
     */
    public static function getCarousels()
    {
        return static::find()
            ->where(['status' => self::STATUS_ENABLE])
            ->orderBy('align')
            ->all();
    }


    /**
     * 获取状态文本
     * 
     * @return array 状态文本
     */
    public static function getStatusTexts() {
        return [
            static::STATUS_DISABLED => '禁用',
            static::STATUS_ENABLE   => '启用',
        ];
    }


    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => '标题',
            'content' => '内容',
            'picture' => '图片',
            'status' => '状态',
            'align' => '排序',
            'created_at' => '创建时间',
            'updated_at' => '更新时间',
        ];
    }
}
