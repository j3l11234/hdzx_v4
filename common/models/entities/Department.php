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
use common\behaviors\JsonBehavior;

/**
 * This is the model class for table "{{%dept}}".
 *
 * @property integer $id
 * @property string $name
 * @property integer $parent_id
 * @property integer $status
 * @property integer $choose
 * @property json $usage_limit
 * @property integer $align
 * @property integer $created_at
 * @property integer $updated_at
 */
class Department extends ActiveRecord
{
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
    public static function tableName()
    {
        return '{{%dept}}';
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
                'attributes' => ['usage_limit'],
            ],
        ];
    }


    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name', 'align', 'created_at', 'updated_at'], 'required'],
            [['align' ], 'integer'],
            ['status', 'in', 'range' => [static::STATUS_DISABLED, static::STATUS_ENABLE]],
            [['name'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => '名称',
            'parent_id' => '父社团id',
            'status' => '状态',
            'choose' => '是否可选择',
            'usage_limit' => '使用限额(不可用)',
            'align' => '排序',
            'created_at' => '创建时间',
            'updated_at' => '修改时间',
        ];
    }
}
