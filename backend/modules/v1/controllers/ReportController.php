<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2018-06-12 14:15
 */

namespace backend\modules\v1\controllers;
use backend\modules\v1\controllers\AdminController;
use backend\modules\v1\models\ApiReport;
use yii\data\ArrayDataProvider;
use yii\data\SqlDataProvider;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use Yii;
use backend\modules\v1\utils\Handler;

class ReportController extends  AdminController
{
    public $modelClass = 'backend\modules\v1\models\ApiReport';

    public function behaviors()
    {

        $behaviors = ArrayHelper::merge([
                'verbs' => [
                    'class' => VerbFilter::className(),
                    'actions' => [
                        'sales' => ['post','options'],
                        'develop' => ['post','options'],
                        'purchase' => ['post','options'],
                        'Possess' => ['post','options'],
                        'ebay-sales' => ['post','options'],
                        'sales-trend' => ['post','options'],
                        'profit' => ['post','options'],
                    ],
                ],
       ],
            parent::behaviors()
        );
        return $behaviors;

    }

    /**
     * @brief sales profit report
     * @return array
     */

    public function actionSales ()
    {
        $request = Yii::$app->request->post();
        $cond= $request['condition'];
        $condition= [
            'dateRangeType' => $cond['dateRangeType'],
            'plat' => $cond['plat']?:'',
            'dateFlag' =>$cond['dateType']?:0,
            'beginDate' => $cond['dateRange'][0],
            'endDate' => $cond['dateRange'][1],
            'suffix' => $cond['account']?"'".implode(',',$cond['account'])."'":'',
            'seller' => $cond['member']?"'".implode(',',$cond['member'])."'":'',
            'storeName' => $cond['store']?"'".implode(',',$cond['store'])."'":'',
        ];
        $ret = ApiReport::getSalesReport($condition);
        return $ret;
    }

    /**
     * @brief develop profit report
     * @return array
     */


    public function actionDevelop ()
    {
        $request = Yii::$app->request->post();
        $cond= $request['condition'];
        $condition= [
            'dateFlag' =>$cond['dateType'],
            'beginDate' => $cond['dateRange'][0],
            'endDate' => $cond['dateRange'][1],
            'seller' => $cond['member']?implode(',',$cond['member']):'',
        ];
        $ret = ApiReport::getDevelopReport($condition);
        return $ret;
    }

    /**
     * @brief Purchase profit report
     * @return array
     */
    public function actionPurchase ()
    {
        $request = Yii::$app->request->post();
        $cond= $request['condition'];
        $condition= [
            'dateFlag' =>$cond['dateType'],
            'beginDate' => $cond['dateRange'][0],
            'endDate' => $cond['dateRange'][1],
            'purchase' => $cond['member']?implode(',',$cond['member']):'',
        ];
        $ret = ApiReport::getPurchaseReport($condition);
        return $ret;
    }


    /**
     * @brief Possess profit report
     * @return array
     */
    public function actionPossess ()
    {
        $request = Yii::$app->request->post();
        $cond= $request['condition'];
        $condition= [
            'dateFlag' =>$cond['dateType'],
            'beginDate' => $cond['dateRange'][0],
            'endDate' => $cond['dateRange'][1],
            'possess' => $cond['member']?implode(',',$cond['member']):'',
        ];
        $ret = ApiReport::getPossessReport($condition);
        return $ret;
    }

    /**
     * @brief EbaySales profit report
     * @return array
     */
    public function actionEbaySales ()
    {
        $request = Yii::$app->request->post();
        $cond= $request['condition'];
        $condition= [
            'dateFlag' =>$cond['dateType'],
            'beginDate' => $cond['dateRange'][0],
            'endDate' => $cond['dateRange'][1],
            'possess' => $cond['member'],
        ];
        $ret = ApiReport::getEbaySalesReport($condition);
        return $ret;
    }



    /**
     * @brief SalesTrend profit report
     * @return array
     * @throws  \Exception
     */
    public function actionSalesTrend ()
    {
        $request = Yii::$app->request->post();
        $cond= $request['condition'];
        $queryParams = [
            'department' => $cond['department'],
            'secDepartment' => $cond['secDepartment'],
            'platform' => $cond['plat'],
            'username' => $cond['member'],
            'store' => $cond['account']
        ];
        $params = Handler::paramsFilter($queryParams);
        $condition= [
            'store' => $params['store']?implode(',',$params['store']):'',
            'queryType' => $params['queryType'],
            'dateFlag' =>$cond['dateType'],
            'showType' => $cond['flag']?:0,
            'beginDate' => $cond['dateRange'][0],
            'endDate' => $cond['dateRange'][1]
        ];

        $ret = ApiReport::getSalesTrendReport($condition);
        return $ret;
    }
    /**
     * @brief profit report
     * @return array
     */
    public function actionAccount ()
    {
        $request = Yii::$app->request->post();
        $cond= $request['condition'];
        $condition= [
            'dateFlag' =>$cond['dateType'],
            'beginDate' => $cond['dateRange'][0],
            'endDate' => $cond['dateRange'][1],
            'sku' => $cond['sku'],
            'salesman' => $cond['member']?"'".implode(',',$cond['member'])."'":'',
            'chanel' => $cond['plat'],
            'suffix' => $cond['account']?("'".implode(',',$cond['account'])."'"):'',
            'storeName' => $cond['store']?("'".implode(',',$cond['store'])."'"):'',
            'start' => $cond['start'],
            'limit' => $cond['limit'],
        ];
        $ret = ApiReport::getProfitReport($condition);
        $num = $ret ? $ret[0]['totalNum']:0;
        return [
            'items' => $ret,
            'totalCount' => $num,
        ];
    }

    /**
     * @brief introduce performance report
     */
    public function actionIntroduce()
    {
        $request = Yii::$app->request->post();
        $cond = $request['condition'];
        $condition = [
            'dateFlag' => $cond['dateType'],
            'beginDate' => $cond['dateRange'][0],
            'endDate' => $cond['dateRange'][1],
            'member' => $cond['member']?implode(',',$cond['member']):''
        ];
        //print_r($condition);exit;
        return ApiReport::getIntroduceReport($condition);
    }

}