<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2019-04-02 10:13
 */

namespace backend\modules\v1\models;

use backend\models\ShopElf\BPerson;
use backend\models\TaskPick;
use backend\models\TaskSort;
use yii\data\ArrayDataProvider;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use Yii;
use yii\data\ActiveDataProvider;
use backend\modules\v1\utils\Helper;


class ApiWarehouseTools
{


    /**
     * @brief 添加拣货任务
     * @param $condition
     * @return array|bool
     */
    public static function setBatchNumber($condition)
    {
        $row = [
            'batchNumber' => $condition['batchNumber'],
            'picker' => $condition['picker'],
            'scanningMan' => Yii::$app->user->identity->username,
        ];

        $task = new TaskPick();
        $task->setAttributes($row);
        if ($task->save()) {
            return true;
        }
        return [
            'code' => 400,
            'message' => 'failed'
        ];
    }

    /**
     * @brief 添加分货任务
     * @param $condition
     * @return array|bool
     */
    public static function setSortBatchNumber($condition)
    {
        $row = [
            'batchNumber' => $condition['batchNumber'],
            'picker' => $condition['picker'],
            'scanningMan' => Yii::$app->user->identity->username,
        ];

        $task = new TaskSort();
        $task->setAttributes($row);
        if ($task->save()) {
            return true;
        }
        return [
            'code' => 400,
            'message' => 'failed'
        ];
    }

    /**
     * @brief 获取拣货人
     * @return array
     */
    public static function getPickMember()
    {
        $ret = BPerson::find()
            ->andWhere(['in', 'Duty', ['拣货','拣货组长','拣货-分拣']])->all();
        return ArrayHelper::getColumn($ret, 'PersonName');
    }

    /**
     * @brief 获取分拣人
     * @return array
     */
    public static function getSortMember()
    {

        $ret = BPerson::find()
            ->andWhere(['in', 'Duty', ['拣货','拣货组长','拣货-分拣']])->all();
        return ArrayHelper::getColumn($ret, 'PersonName');
    }

    /**
     * @brief 拣货扫描记录
     * @param $condition
     * @return ActiveDataProvider
     */
    public static function getScanningLog($condition)
    {
        $pageSize = isset($condition['pageSize']) ? $condition['pageSize'] : 10;
        $fieldsFilter = ['like' =>['batchNumber', 'picker', 'scanningMan'], 'equal' => ['isDone']];
        $timeFilter = ['createdTime', 'updatedTime'];
        $query = TaskPick::find();
        $query = Helper::generateFilter($query,$fieldsFilter,$condition);
        $query = Helper::timeFilter($query,$timeFilter,$condition);
        $query->orderBy('id DESC');
        $provider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => $pageSize,
            ],
        ]);
        return $provider;
    }

    /**
     * @brief 分货扫描记录
     * @param $condition
     * @return ActiveDataProvider
     */
    public static function getSortLog($condition)
    {
        $pageSize = isset($condition['pageSize']) ? $condition['pageSize'] : 10;
        $fieldsFilter = ['like' =>['batchNumber', 'picker', 'scanningMan'], 'equal' => ['isDone']];
        $timeFilter = ['createdTime', 'updatedTime'];
        $query = TaskSort::find();
        $query = Helper::generateFilter($query,$fieldsFilter,$condition);
        $query = Helper::timeFilter($query,$timeFilter,$condition);
        $query->orderBy('id DESC');
        $provider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => $pageSize,
            ],
        ]);
        return $provider;
    }

    /** 获取拣货统计数据
     * @param $condition
     * Date: 2019-08-23 16:16
     * Author: henry
     * @return mixed
     */
    public static function getPickStatisticsData($condition)
    {
        $query = TaskPick::find()->select(new Expression("batchNumber,picker,date_format(MAX(createdTime),'%Y-%m-%d') AS createdTime"));
        $query = $query->andWhere(['<>', "IFNULL(batchNumber,'')", '']);
        $query = $query->andWhere(['<>', "IFNULL(picker,'')", '']);
        $query = $query->groupBy(['batchNumber','picker']);
        $query = $query->having(['between', "date_format(MAX(createdTime),'%Y-%m-%d')", $condition['createdTime'][0], $condition['createdTime'][1]]);
        $list = $query->asArray()->all();
        //清空临时表数据
        Yii::$app->py_db->createCommand()->truncateTable('guest.oauth_taskPickTmp')->execute();

        $step = 200;
        for ($i=1;$i<=ceil(count($list)/$step);$i++){
            Yii::$app->py_db->createCommand()->batchInsert('guest.oauth_taskPickTmp',['batchNumber','picker','createdTime'],array_slice($list,($i-1)*$step,$step))->execute();
        }
        //获取数据
        $sql = "EXEC guest.oauth_getPickStatisticsData '{$condition['createdTime'][0]}','{$condition['createdTime'][1]}'";

        return Yii::$app->py_db->createCommand($sql)->queryAll();
    }


    /** 获取拣货统计数据
     * @param $condition
     * Date: 2019-08-23 16:16
     * Author: henry
     * @return mixed
     */
    public static function getWareStatisticsData($condition)
    {
        $beginTime = isset($condition['orderTime'][0]) ? $condition['orderTime'][0] : '';
        $endTime = isset($condition['orderTime'][1]) ? $condition['orderTime'][1] : '';
        $pageSize = isset($condition['pageSize']) ? $condition['pageSize'] : 10;
        //获取数据
        $sql = "SELECT ptd.sku,bgsku.skuName,bg.salerName,bgsku.goodsSkuStatus,bg.purchaser,
			          bs.storeName,sl.locationName,Dateadd(HOUR, 8, MIN(m.ORDERTIME)) AS minOrderTime,
                      DATEDIFF(DAY,Dateadd(HOUR, 8, MIN(ORDERTIME)),GETDATE()) AS maxDelayDays,
                      loseSkuCount = (
	                      SUM(ptd.L_QTY) - (
	                        SELECT d.Number-d.ReservationNum
			                FROM KC_CurrentStock (nolock) d WHERE ptd.GoodsSKUID = d.GoodsSKUID
			                AND d.StoreID = ptd.StoreID
			              )
                      ),
                      unStockNum = (
                        select SUM(isnull(d.Amount,0) - isnull(d.inAmount,0))
	                    FROM CG_StockOrderD(nolock) d
	                    LEFT JOIN CG_StockOrderM(nolock) m ON d.stockOrderNid=m.nid
			            WHERE d.goodsSkuid = ptd.GoodsSKUID
			              AND (m.CheckFlag = 1)   --审核通过的订单
				          AND (m.Archive = 0)
                      )
                FROM P_TradeUn (nolock) m
                LEFT JOIN	P_TradeDtUn (nolock) ptd ON m.nid=ptd.tradenid
                LEFT JOIN B_GoodsSKULocation (nolock) bgs ON ptd.GoodsSKUID = bgs.GoodsSKUID AND ptd.StoreID = bgs.StoreID
                LEFT JOIN B_goodssku (nolock) bgsku ON bgsku.nid = ptd.goodsskuid
                LEFT JOIN b_goods (nolock) bg ON bg.nid = bgsku.goodsid
                LEFT JOIN B_StoreLocation (nolock) sl ON sl.nid = bgs.LocationID
                LEFT JOIN B_Store bs ON ISNULL(ptd.StoreID, 0) = ISNULL(bs.NID, 0)
                WHERE FilterFlag = 1 ";
        if($condition['sku']){
            $sql .= " AND ptd.sku LIKE '%{$condition['sku']}%'";
        }
        if($condition['skuName']){
            $sql .= " AND bgsku.skuName LIKE '%{$condition['skuName']}%'";
        }
        if($condition['goodsSKUStatus']){
            $sql .= " AND bgsku.GoodsSKUStatus LIKE '%{$condition['goodsSKUStatus']}%'";
        }
        if($condition['purchaser']){
            $sql .= " AND bg.Purchaser LIKE '%{$condition['purchaser']}%'";
        }

        if($beginTime && $endTime){
            $sql .= " AND CONVERT(VARCHAR(10),DATEADD(HH,8,ordertime),121) BETWEEN '{$beginTime}' AND '{$endTime}' ";
        }

        $sql .= " GROUP BY ptd.sku,bgsku.skuName,ptd.GoodsSKUID,ptd.StoreID,bg.SalerName,
			        bgsku.GoodsSKUStatus,bg.Purchaser,bs.StoreName,sl.LocationName";

        $data = Yii::$app->py_db->createCommand($sql)->queryAll();
        $provider = new ArrayDataProvider([
            'allModels' => $data,
            'pagination' => [
                'pageSize' => $pageSize,
            ],
        ]);
        return $provider;
    }

    /** 仓库仓位SKU对应表
     * @param $condition
     * Date: 2019-09-03 10:23
     * Author: henry
     * @return ArrayDataProvider
     */
    public static function getWareSkuData($condition){
        $sku = $condition['sku'] ? implode("','", $condition['sku']) : '';
        $beginTime = isset($condition['orderTime'][0]) ? $condition['orderTime'][0] : '';
        $endTime = isset($condition['orderTime'][1]) ? $condition['orderTime'][1].' 23:59:59' : '';
        $pageSize = isset($condition['pageSize']) ? $condition['pageSize'] : 20;
        $sql = "SELECT gs.SKU,s.StoreName,l.LocationName,person,changeTime
                FROM [dbo].[B_GoodsSKULocation] gsl
                LEFT JOIN B_GoodsSKU gs ON gs.NID=gsl.GoodsSKUID
                LEFT JOIN B_Store s ON s.NID=gsl.StoreID
                LEFT JOIN B_StoreLocation l ON l.NID=gsl.LocationID
                LEFT JOIN (
                SELECT * FROM B_GoodsSKULocationLog bll
                WHERE NOT EXISTS (
                        SELECT 1 FROM B_GoodsSKULocationLog AS tmp
                        WHERE bll.sku = tmp.sku
                        AND bll.nowLocation = tmp.nowLocation
                        AND bll.changeTime < tmp.changeTime
                    )
                ) ll ON ll.sku=gs.sku AND ll.nowLocation=LocationName 
                WHERE 1=1 ";
        if($sku){
            $sql .= " AND gs.SKU IN ('{$sku}') ";
        }
        if($condition['store']){
            $sql .= " AND StoreName LIKE '%{$condition['sku']}%' ";
        }
        if($condition['location']){
            $sql .= " AND LocationName LIKE '%{$condition['location']}%' ";
        }
        if($beginTime && $endTime){
            $sql .= " AND changeTime BETWEEN '{$beginTime}' AND '{$endTime}'; ";
        }
        $data = Yii::$app->py_db->createCommand($sql)->queryAll();


        $provider = new ArrayDataProvider([
            'allModels' => $data,
            'pagination' => [
                'pageSize' => $pageSize,
            ],
        ]);
        return $provider;

    }





}