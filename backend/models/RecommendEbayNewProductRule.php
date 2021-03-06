<?php

namespace backend\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "proEngine.recommend_ebayNewProductRule".
 *
 * @property int $id
 * @property int $soldStart
 * @property int $soldEnd
 * @property int $visitStart
 * @property int $visitEnd
 * @property double $priceEnd
 * @property double $priceStart
 * @property string $country
 * @property int $popularStatus
 * @property int $sellerOrStore
 * @property string $storeLocation
 * @property int $salesThreeDayFlag
 * @property string $listedTime
 * @property string $marketplace
 * @property string $itemLocation
 * @property string $creator
 * @property int $isUsed
 * @property string $createdDate
 * @property string $updatedDate
 */
class RecommendEbayNewProductRule extends \yii\db\ActiveRecord
{

    public function behaviors()
    {
        return [[
            /**
             * TimestampBehavior：
             */
            'class' => TimestampBehavior::className(),
            'createdAtAttribute' => 'createdDate',
            'updatedAtAttribute' => 'updatedDate',
            'value' => new Expression('NOW()'),
        ],];
    }
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'proEngine.recommend_ebayNewProductRule';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['soldStart', 'soldEnd', 'visitStart', 'visitEnd', 'popularStatus', 'sellerOrStore', 'salesThreeDayFlag', 'isUsed'], 'integer'],
            [['priceEnd', 'priceStart'], 'number'],
            [['createdDate', 'updatedDate'], 'safe'],
            [['country',  'creator'], 'string', 'max' => 20],
            [['listedTime', 'itemLocation'], 'string', 'max' => 50],
            [['marketplace','storeLocation'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'soldStart' => 'Sold Start',
            'soldEnd' => 'Sold End',
            'visitStart' => 'Visit Start',
            'visitEnd' => 'Visit End',
            'priceEnd' => 'Price End',
            'priceStart' => 'Price Start',
            'country' => 'Country',
            'popularStatus' => 'Popular Status',
            'sellerOrStore' => 'Seller Or Store',
            'storeLocation' => 'Store Location',
            'salesThreeDayFlag' => 'Sales Three Day Flag',
            'listedTime' => 'Listed Time',
            'marketplace' => 'Marketplace',
            'itemLocation' => 'Item Location',
            'creator' => 'Creator',
            'isUsed' => 'Is Used',
            'createdDate' => 'Created Date',
            'updatedDate' => 'Updated Date',
        ];
    }
}
