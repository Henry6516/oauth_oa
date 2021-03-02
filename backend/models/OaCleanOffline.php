<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "oa_cleanOffline".
 *
 * @property int $id
 * @property string $sku
 * @property string $checkStatus 初始化,已找到,未找到
 * @property string $creator 创建人
 * @property string $createdTime
 * @property string $updatedTime
 */
class OaCleanOffline extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'oa_cleanOffline';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['createdTime', 'updatedTime'], 'safe'],
            [['sku'], 'string', 'max' => 500],
            [['checkStatus'], 'string', 'max' => 10],
            [['creator'], 'string', 'max' => 20],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'sku' => 'Sku',
            'checkStatus' => 'Check Status',
            'creator' => 'Creator',
            'createdTime' => 'Created Time',
            'updatedTime' => 'Updated Time',
        ];
    }
}
