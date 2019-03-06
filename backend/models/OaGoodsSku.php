<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "proCenter.oa_goodssku".
 *
 * @property int $id
 * @property int $infoId
 * @property string $sku
 * @property string $property1
 * @property string $property2
 * @property string $property3
 * @property string $Weight
 * @property string $memo1
 * @property string $memo2
 * @property string $memo3
 * @property string $memo4
 * @property string $linkurl
 * @property int $goodsskuid
 * @property string $RetailPrice
 * @property string $CostPrice
 * @property int $stockNum
 * @property int $did
 * @property string $joomPrice
 * @property string $joomShipping
 */
class OaGoodsSku extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'proCenter.oa_goodssku';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['infoId', 'goodsskuid', 'stockNum', 'did'], 'integer'],
            [['sku'], 'required'],
            [['Weight', 'RetailPrice', 'CostPrice', 'joomPrice', 'joomShipping'], 'number'],
            [['sku', 'property1', 'property2', 'property3', 'memo1', 'memo2', 'memo3', 'memo4', 'linkurl'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'infoId' => 'Info ID',
            'sku' => 'Sku',
            'property1' => 'Property1',
            'property2' => 'Property2',
            'property3' => 'Property3',
            'Weight' => 'Weight',
            'memo1' => 'Memo1',
            'memo2' => 'Memo2',
            'memo3' => 'Memo3',
            'memo4' => 'Memo4',
            'linkurl' => 'Linkurl',
            'goodsskuid' => 'Goodsskuid',
            'RetailPrice' => 'Retail Price',
            'CostPrice' => 'Cost Price',
            'stockNum' => 'Stock Num',
            'did' => 'Did',
            'joomPrice' => 'Joom Price',
            'joomShipping' => 'Joom Shipping',
        ];
    }
}