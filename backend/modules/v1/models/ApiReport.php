<?php
/**
 * @desc PhpStorm.
 * @author: Administrator
 * @since: 2018-06-12 14:22
 */

namespace backend\modules\v1\models;

use Yii;
use yii\db\Query;
use yii\helpers\ArrayHelper;

class ApiReport
{
    /**
     * @brief sales profit report
     * @param $condition
     * @return array
     */

    public static function getSalesReport($condition)
    {
        $sql = 'meta_saleProfit @pingtai=:plat,@DateFlag=:dateFlag,@BeginDate=:beginDate,@endDate=:endDate,'.
        '@SalerAliasName=:suffix,@Saler=:seller,@StoreName=:storeName';
        $con = Yii::$app->py_db;
        $params = [
            ':plat' => $condition['plat'],
            ':dateFlag' => $condition['dateFlag'],
            ':beginDate' => $condition['beginDate'],
            ':endDate' => $condition['endDate'],
            ':suffix' => $condition['suffix'],
            ':seller' => $condition['seller'],
            ':storeName' => $condition['storeName']
        ];
        try {
            return $con->createCommand($sql)->bindValues($params)->queryAll();
        }
        catch (\Exception $why) {
            return [$why];
        }

    }


    /**
     * @brief develop profit report
     * @params $condition
     * @return array
     */
    public static function getDevelopReport($condition)
    {
        $sql = "EXEC P_DevNetprofit_advanced @DateFlag=:dateFlag,@BeginDate=:beginDate,@endDate=:endDate,".
        "@Sku='',@SalerName=:seller,@SalerName2='',@chanel='',@SaleType='',@SalerAliasName='',@DevDate='',".
        "@DevDateEnd='',@Purchaser=0,@SupplierName=0,@possessMan1=0,@possessMan2=0";
        $con = Yii::$app->py_db;
        $params = [
            ':dateFlag' => $condition['dateFlag'],
            ':beginDate' => $condition['beginDate'],
            ':endDate' => $condition['endDate'],
            ':seller' => $condition['seller'],
        ];
        try {
            return $con->createCommand($sql)->bindValues($params)->queryAll();
        }
        catch (\Exception $why) {
            return [$why];
        }

    }

    /**
     * @brief Purchase profit report
     * @params $condition
     * @return array
     */
    public static function getPurchaseReport($condition)
    {
        $sql = "EXEC z_p_purchaserProfit @DateFlag=:dateFlag,@BeginDate=:beginDate,@endDate=:endDate,".
        "@Sku='',@SalerName='',@SalerName2='',@chanel='',@SaleType='',@SalerAliasName='',@DevDate='',".
        "@DevDateEnd='',@Purchaser=:purchase,@SupplierName=0,@possessMan1=0,@possessMan2=0";
        $con = Yii::$app->py_db;
        $params = [
            ':dateFlag' => $condition['dateFlag'],
            ':beginDate' => $condition['beginDate'],
            ':endDate' => $condition['endDate'],
            ':purchase' => $condition['purchase'],
        ];
        try {
            return $con->createCommand($sql)->bindValues($params)->queryAll();
        }
        catch (\Exception $why) {
            return [$why];
        }

    }

    /**
     * @brief Possess profit report
     * @params $condition
     * @return array
     */
    public static function getPossessReport($condition)
    {
        $sql = "EXEC Z_P_PossessNetProfit @DateFlag=:dateFlag,@BeginDate=:beginDate,@endDate=:endDate,@possessMan1=:possess";
        $con = Yii::$app->py_db;
        $params = [
            ':dateFlag' => $condition['dateFlag'],
            ':beginDate' => $condition['beginDate'],
            ':endDate' => $condition['endDate'],
            ':possess' => $condition['possess'],
        ];
        try {
            return $con->createCommand($sql)->bindValues($params)->queryAll();
        }
        catch (\Exception $why) {
            return [$why];
        }

    }



    /**
     * @brief EbaySales profit report
     * @params $condition
     * @return array
     */
    public static function getEbaySalesReport($condition)
    {
        $sql = "EXEC P_YR_PossessMan2Profit @DateFlag=:dateFlag,@BeginDate=:beginDate,@endDate=:endDate,".
        "@Sku='',@SalerName='',@SalerName2=0,@chanel='eBay',@SaleType='',@SalerAliasName='',@DevDate='',".
            "@DevDateEnd='',@Purchaser=0,@SupplierName=0,@possessMan1=0,@possessMan2=0";
        $con = Yii::$app->py_db;
        $params = [
            ':dateFlag' => $condition['dateFlag'],
            ':beginDate' => $condition['beginDate'],
            ':endDate' => $condition['endDate'],
        ];
        try {
            return $con->createCommand($sql)->bindValues($params)->queryAll();
        }
        catch (\Exception $why) {
            return [$why];
        }

    }

    /**
     * @brief SalesTrend profit report
     * @params $condition
     * @return array
     */
    public static function getSalesTrendReport($condition)
    {
        $sql = "EXEC z_p_saletrendy @DateFlag=:dateFlag,@BeginDate=:beginDate,@endDate=:endDate,".
        "@Flag=:flag,@Salesman=:salesman,@chanel=:chanel,@suffix=:suffix,@Dname=:dname";
        $con = Yii::$app->py_db;
        $params = [
            ':dateFlag' => $condition['dateFlag'],
            ':beginDate' => $condition['beginDate'],
            ':endDate' => $condition['endDate'],
            ':flag' => $condition['flag'],
            ':salesman' => $condition['salesman'],
            ':chanel' => $condition['chanel'],
            ':suffix' => $condition['suffix'],
            ':dname' => $condition['dname'],
        ];
        try {
            return $con->createCommand($sql)->bindValues($params)->queryAll();
        }
        catch (\Exception $why) {
            return [$why];
        }

    }

    /**
     * @brief  profit report
     * @params $condition
     * @return array
     */
    public static function getProfitReport($condition)
    {
        $sql = "EXEC Z_P_AccountProductProfit @chanel=:chanel,@DateFlag=:dateFlag,@BeginDate=:beginDate,@endDate=:endDate,".
        "@SalerAliasName=:suffix,@SalerName=:salesman,@StoreName=:storeName,@sku=:sku";
        $con = Yii::$app->py_db;
        $params = [
            ':chanel' => $condition['chanel'],
            ':dateFlag' => $condition['dateFlag'],
            ':beginDate' => $condition['beginDate'],
            ':endDate' => $condition['endDate'],
            ':suffix' => $condition['suffix'],
            ':salesman' => $condition['salesman'],
            ':storeName' => $condition['storeName'],
            ':sku' => $condition['sku'],
        ];
        try {
            return $con->createCommand($sql)->bindValues($params)->queryAll();
        }
        catch (\Exception $why) {
            return [$why];
        }

    }

}