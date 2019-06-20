<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "proCenter.oa_ebayKeyword".
 *
 * @property int $id
 * @property string $keyword
 * @property string $goodsCode
 * @property string $goodsName
 * @property string $costPrice
 * @property int $weight
 * @property string $url
 */
class OaEbayKeyword extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'proCenter.oa_ebayKeyword';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['costPrice'], 'number'],
            [['weight'], 'integer'],
            [['keyword', 'goodsCode', 'goodsName', 'url'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'keyword' => 'Keyword',
            'goodsCode' => 'Goods Code',
            'goodsName' => 'Goods Name',
            'costPrice' => 'Cost Price',
            'weight' => 'Weight',
            'url' => 'Url',
        ];
    }
}
