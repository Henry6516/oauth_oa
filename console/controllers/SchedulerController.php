<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2018-08-30 14:30
 */

namespace console\controllers;

use backend\models\OaGoodsinfo;
use backend\modules\v1\controllers\ReportController;
use backend\modules\v1\models\ApiReport;
use backend\modules\v1\models\ApiSettings;
use backend\modules\v1\models\ApiUkFic;
use backend\modules\v1\utils\Handler;
use console\models\ConScheduler;
use yii\console\Controller;

use Yii;
use yii\helpers\ArrayHelper;

class SchedulerController extends Controller
{
    /**
     * @brief sale report scheduler
     */
    public function actionSaleReport()
    {
        $clearSql = 'delete from oauth_saleReport';
        $con = \Yii::$app->py_db;
        $trans = $con->beginTransaction();
        try {
            $ret = $con->createCommand($clearSql)->execute();
            if (!$ret) {
                throw new \Exception('fail to truncate table');
            }
            $dateFlags = [0, 1];
            $dateRanges = [0, 1, 2];
            foreach ($dateFlags as $flag) {
                foreach ($dateRanges as $range) {
                    $updateSql = "exec meta_saleProfit $flag, $range";
                    $re = $con->createCommand($updateSql)->execute();
                    if (!$re) {
                        throw new \Exception('fail to update data');
                    }
                }
            }
            print date('Y-m-d H:i:s') . "INFO:success to get sale-report data\n";
            $trans->commit();
        } catch (\Exception $why) {
            print date('Y-m-d H:i:s') . "INFO:fail to get sale-report data cause of $why \n";
            $trans->rollback();
        }
    }


    /**
     * @brief display info of sku are out of stock
     */
    public function actionOutOfStockSku()
    {
        $con = \Yii::$app->py_db;
        $sql = "EXEC oauth_outOfStockSku @GoodsState='',@MoreStoreID='',@GoodsUsed='0',@SupplierName='',@WarningCats='',@MoreSKU='',
        @cg=0,@GoodsCatsCode='',@index='1',@KeyStr='',
        @PageNum='100',@PageIndex='1',@Purchaser='',@LocationName='',@Used=''";
        try {
            $con->createCommand($sql)->execute();
            print date('Y-m-d H:i:s') . "INFO:success to get sku out of stock!\n";
        } catch (\Exception $why) {
            print date('Y-m-d H:i:s') . "INFO:fail to get sku out of stock cause of $why \n";
        }
    }

    /**
     * @brief 更新主页各人员目标完成度
     */
    public function actionSite()
    {
        $beginDate = '2019-09-01';//date('Y-m-d', strtotime('-30 days'));
        $endDate = date('Y-m-d', strtotime('-1 days'));//昨天时间
        $dateRate = round(((strtotime($endDate) - strtotime($beginDate))/24/3600 + 1)*100/122, 2);
        //print_r($dateRate);exit;
        try {

            //更新开发目标完成度 TODO  备份数据的加入
            $condition = [
                'dateFlag' => 1,
                'beginDate' => $beginDate,
                'endDate' => $endDate,
                'seller' => '胡小红,廖露露,常金彩,刘珊珊,王漫漫,陈微微,杨笑天,李永恒,崔明宽,张崇,史新慈',
            ];
            $devList = ApiReport::getDevelopReport($condition);
            foreach ($devList as $value){
                $target =  Yii::$app->db->createCommand("SELECT target FROM site_targetAll WHERE username='{$value['salernameZero']} '")->queryOne();
                Yii::$app->db->createCommand()->update(
                    'site_targetAll',
                    [
                        'amt' => $value['netprofittotal'],
                        'rate' => $target['target'] ? 0 : round($value['netprofittotal']*100.0/$target['target']),
                        'dateRate' => $dateRate,
                        'updatetime' => $endDate
                    ],
                    ['role' => '开发','username' => $value['salernameZero']]
                )->execute();
            }

            //更新销售和部门目标完成度
            $exchangeRate = ApiUkFic::getRateUkOrUs('USD');//美元汇率
            $sql = "CALL oauth_siteTargetAll($exchangeRate)";
            Yii::$app->db->createCommand($sql)->execute();

            print date('Y-m-d H:i:s') . " INFO:success to get data of target completion!\n";
        } catch (\Exception $why) {
            print date('Y-m-d H:i:s') . " INFO:fail to get data of target completion cause of $why \n";
        }
    }

    /**
     * 更新产品销量变化（两个时间段对比）
     * Date: 2018-12-29 11:55
     * Author: henry
     */
    public function actionSalesChange()
    {
        $sql = "EXEC oauth_salesChangeOfTwoDateBlock_backup";
        try {
            $list = Yii::$app->py_db->createCommand($sql)->queryAll();

            Yii::$app->db->createCommand()->truncateTable('cache_sales_change')->execute();
            $step = 200;
            $num = ceil(count($list)/$step);
            for ($i = 0; $i < $num; $i++){
                Yii::$app->db->createCommand()->batchInsert(
                    'cache_sales_change',
                    ['orderId', 'suffix', 'goodsCode', 'goodsName', 'qty', 'amt', 'orderTime', 'createDate'],
                    array_slice($list, $i * $step, $step)
                )->execute();
            }


            print date('Y-m-d H:i:s') . " INFO:success to update data of sales change!\n";
        } catch (\Exception $why) {
            print date('Y-m-d H:i:s') . " INFO:fail to update data of sales change cause of $why \n";
        }
    }

    /**
     * 更新主页今日爆款
     * Date: 2019-01-11 11:11
     * Author: henry
     */
    public function actionPros()
    {
        //获取昨天时间
        $beginDate = date('Y-m-d', strtotime('-30 days'));
        $endDate = date('Y-m-d', strtotime('-1 days'));
        $sql = "EXEC oauth_siteGoods @DateFlag=:dateFlag,@BeginDate=:beginDate,@EndDate=:endDate";
        $params = [
            ':dateFlag' => 1,//发货时间
            ':beginDate' => $beginDate,
            ':endDate' => $endDate
        ];
        try {
            $list = Yii::$app->py_db->createCommand($sql)->bindValues($params)->queryAll();
            //清空数据表并插入新数据
            Yii::$app->db->createCommand("TRUNCATE TABLE site_goods")->execute();
            Yii::$app->db->createCommand()->batchInsert('site_goods',
                ['profit', 'salesNum', 'platform', 'goodsCode', 'goodsName', 'endTime', 'img', 'developer', 'linkUrl', 'cate', 'subCate'],
                $list)->execute();

            print date('Y-m-d H:i:s') . " INFO:success to update data of today pros!\n";
        } catch (\Exception $why) {
            print date('Y-m-d H:i:s') . " INFO:fail to update data of today pros cause of $why \n";
        }
    }

    /**
     * 更新主页利润增长表
     * Date: 2019-01-11 11:55
     * Author: henry
     */
    public function actionProfit()
    {
        //获取上月时间
        $lastBeginDate = date('Y-m-01', strtotime('-1 month'));
        $lastEndDate = date('Y-m-t', strtotime('-1 month'));
        $beginDate = date('Y-m-01');
        $endDate = date('Y-m-d', strtotime('-1 day'));
        try {
            //获取开发人员上月和本月毛利的初步数据.
            $devSql = "EXEC oauth_siteDeveloperProfit";
            $devData = Yii::$app->py_db->createCommand($devSql)->queryAll();
            //初步数据保存到Mysql数据库cache_developProfitTmp，进一步进行计算
            Yii::$app->db->createCommand('TRUNCATE TABLE cache_developProfitTmp')->execute();
            Yii::$app->db->createCommand()->batchInsert('cache_developProfitTmp',
                ['tableType','timegroupZero','salernameZero','salemoneyrmbusZero','salemoneyrmbznZero','costmoneyrmbZero',
                    'ppebayusZero','ppebayznZero','inpackagefeermbZero','expressfarermbZero','devofflinefeeZero','devOpeFeeZero',
                    'netprofitZero','netrateZero','timegroupSix','salemoneyrmbusSix','salemoneyrmbznSix','costmoneyrmbSix',
                    'ppebayusSix','ppebayznSix','inpackagefeermbSix','expressfarermbSix','devofflinefeeSix','devOpeFeeSix',
                    'netprofitSix','netrateSix','timegroupTwe','salemoneyrmbusTwe','salemoneyrmbznTwe','costmoneyrmbTwe',
                    'ppebayusTwe','ppebayznTwe','inpackagefeermbTwe','expressfarermbTwe','devofflinefeeTwe','devOpeFeeTwe',
                    'netprofitTwe','netrateTwe','salemoneyrmbtotal','netprofittotal','netratetotal','devRate','devRate1','devRate5','type'],
                $devData)->execute();

            //插入销售和开发毛利数据(存储过程插入)
            Yii::$app->db->createCommand("CALL oauth_site_profit(0);")->execute();

            print date('Y-m-d H:i:s') . " INFO:success to update data of profit changes!\n";
        } catch (\Exception $why) {
            print date('Y-m-d H:i:s') . " INFO:fail to update data of profit changes cause of $why \n";
        }

    }

    /**
     * 更新主页销售额增长表
     * Date: 2019-04-15 16:25
     * Author: henry
     */
    public function actionSalesAmt()
    {
        try {

            //插入销售销售额数据(存储过程插入)
            Yii::$app->db->createCommand("CALL oauth_site_amt;")->execute();

            //获取开发人员销售额
            $devSql = "EXEC oauth_siteDeveloperAmt";
            $devList = Yii::$app->py_db->createCommand($devSql)->queryAll();

            //插入开发销售数据
            Yii::$app->db->createCommand()->batchInsert('site_sales_amt',
                ['username', 'depart', 'role', 'lastAmt', 'amt', 'rate', 'dateRate', 'updateTime'],
                $devList)->execute();

            print date('Y-m-d H:i:s') . " INFO:success to update data of amt changes!\n";
        } catch (\Exception $why) {
            print date('Y-m-d H:i:s') . " INFO:fail to update data of amt changes cause of $why \n";
        }

    }

    /**
     * Date: 2019-03-12 8:56
     * Author: henry
     */
    public function actionWeightDiff()
    {
        $beginDate = '2018-10-01';
        $endDate = date('Y-m-d', strtotime('-1 day'));
        //print_r($endDate);exit;
        try {
            //获取开发人员毛利
            $sql = "EXEC oauth_weightDiff :beginDate,:endDate";
            $list = Yii::$app->py_db->createCommand($sql)->bindValues([':beginDate' => $beginDate, ':endDate' => $endDate])->queryAll();
            $step = 500;
            $count = ceil(count($list) / 500);
            //清空数据表
            Yii::$app->db->createCommand('TRUNCATE TABLE cache_weightDiff')->execute();
            //插入数据
            if ($list) {
                for ($i = 0; $i <= $count; $i++) {
                    Yii::$app->db->createCommand()->batchInsert('cache_weightDiff',
                        ['trendId', 'suffix', 'orderCloseDate', 'orderWeight', 'skuWeight', 'weightDiff', 'profit'],
                        array_slice($list, $i * $step, $step))->execute();
                }
            }
            print date('Y-m-d H:i:s') . " INFO:success to update data of weight diff!\n";
        } catch (\Exception $why) {
            print date('Y-m-d H:i:s') . " INFO:fail to update data of weight diff cause of $why \n";
        }

    }

    public function actionPriceTrend()
    {
        $beginDate = '2018-10-01';
        $endDate = date('Y-m-d', strtotime('-1 day'));
        //print_r($endDate);exit;
        try {
            //获取开发人员毛利
            $sql = "EXEC oauth_weightDiff :beginDate,:endDate";
            $list = Yii::$app->py_db->createCommand($sql)->bindValues([':beginDate' => $beginDate, ':endDate' => $endDate])->queryAll();
            $step = 500;
            $count = ceil(count($list) / 500);
            //清空数据表
            Yii::$app->db->createCommand('TRUNCATE TABLE cache_weightDiff')->execute();
            //插入数据
            if ($list) {
                for ($i = 0; $i <= $count; $i++) {
                    Yii::$app->db->createCommand()->batchInsert('cache_weightDiff',
                        ['trendId', 'suffix', 'orderCloseDate', 'orderWeight', 'skuWeight', 'weightDiff', 'profit'],
                        array_slice($list, $i * $step, $step))->execute();
                }
            }
            print date('Y-m-d H:i:s') . " INFO:success to update data of weight diff!\n";
        } catch (\Exception $why) {
            print date('Y-m-d H:i:s') . " INFO:fail to update data of weight diff cause of $why \n";
        }

    }

    /**
     *  销售排名
     * Date: 2019-05-07 16:15
     * Author: henry
     */
    public function actionSalesRanking(){
        try {
            //插入销售毛利数据(存储过程插入)
            Yii::$app->db->createCommand("CALL oauth_site_profit(1);")->execute();
            print date('Y-m-d H:i:s') . " INFO:success to update data of sales profit ranking!\n";
        } catch (\Exception $why) {
            print date('Y-m-d H:i:s') . " INFO:fail to update data of sales profit ranking cause of $why \n";
        }
    }

    /** 备货产品计算
     * 每天更新开发员在本月的可用备货数量，每月第一天（1号）更新备份数据
     * 访问方法: php yii scheduler/stock
     * Date: 2019-05-06 15:58
     * Author: henry
     * @throws \yii\db\Exception
     */
    public function actionStock()
    {
        $end = date('Y-m-d');
        //$end = '2019-06-01';
        $startDate = date('Y-m-d', strtotime('-75 days', strtotime($end)));
        $endDate = date('Y-m-d', strtotime('-15 days', strtotime($end)));
        //print_r($startDate);
        //print_r($endDate);exit;
        //获取订单数详情
        $orderList = Yii::$app->py_db->createCommand("EXEC oauth_stockGoodsNumber '" . $startDate . "','" . $endDate . "','';")->queryAll();
        //获取开发产品列表
        $goodsSql = "SELECT developer,goodsCode,stockUp FROM proCenter.oa_goodsinfo gs
                      WHERE LEFT(devDatetime,10) BETWEEN '{$startDate}' AND '{$endDate}' AND ifnull(mid,0)=0;";
        $goodsList = Yii::$app->db->createCommand($goodsSql)->queryAll();
        //获取开发员备货产品数，不备货产品数，总产品数
        $list = Yii::$app->db->createCommand("CALL proCenter.oa_stockGoodsNum('{$startDate}','{$endDate}');")->queryAll();
        //统计出单数，爆旺款数量
        $developer = [];
        foreach ($goodsList as $k => $v) {
            $orderNum = 0;
            $goodsStatus = '';
            foreach ($orderList as $value){
                if($v['goodsCode'] == $value['goodsCode']){
                    $orderNum += $value['l_qty'];//出单数
                    $goodsStatus = $value['goodsStatus'];
                    break;
                }
            }
            $v['orderNum'] = $orderNum;
            $v['goodsStatus'] = $goodsStatus;
            $developer[$k] = $v;
        }
        //print_r($developer);exit;
        $orderNumList = $nonOrderNumList = [];
       foreach($list as $k => $value){
            $stockOrderNum = $nonStockOrderNum = $hot = $exu = $nonHot = $nonExu = 0;
            foreach ($developer as $v){

                if($value['username'] === $v['developer']){
                    $nonStockOrderNum = ($v['stockUp'] === '否' && $v['orderNum'] > 0) ? $nonStockOrderNum + 1 : $nonStockOrderNum;
                    $stockOrderNum = ($v['stockUp'] == '是' && $v['orderNum'] > 0) ? $stockOrderNum + 1 : $stockOrderNum;
                    $hot = ($v['goodsStatus'] == '爆款' && $v['stockUp'] == '是' && $v['orderNum'] > 0) ? $hot + 1 : $hot;
                    $exu = ($v['goodsStatus'] == '旺款' && $v['stockUp'] == '是' && $v['orderNum'] > 0) ? $exu + 1 : $exu;
                    $nonHot = ($v['goodsStatus'] == '爆款' && $v['stockUp'] == '否' && $v['orderNum'] > 0) ? $nonHot + 1 : $nonHot;
                    $nonExu = ($v['goodsStatus'] == '旺款' && $v['stockUp'] == '否' && $v['orderNum'] > 0) ? $nonExu + 1 : $nonExu;
                }
            }


           //计算 备货和不备货的爆旺款率
           $hotAndExuRate = $value['stockNum'] == 0 ? 0 : round(($hot+$exu)*1.0/$value['stockNum'], 4)*100;
           $nonHotAndExuRate = $value['nonStockNum'] == 0 ? 0 : round(($nonHot+$nonExu)*1.0/$value['nonStockNum'], 4)*100;
           //计算 备货和不备货的出单率
           $orderRate = $value['stockNum'] == 0 ? 0 : round($stockOrderNum*1.0/$value['stockNum'], 4)*100;
           $nonOrderRate = $value['nonStockNum'] == 0 ? 0 : round($nonStockOrderNum*1.0/$value['nonStockNum'], 4)*100;
           //计算 出单率评分
           $rate1 = round(max(1-max((80-$orderRate),0)*0.025,0.5),2);
           $nonRate1 = round(max(1-max((80-$nonOrderRate),0)*0.025,0.5),2);
           //计算 爆旺款率评分
           $rate2 = round(2-max((30-$hotAndExuRate)*0.04,0),2);
           $nonRate2 = round(2-max((30-$nonHotAndExuRate)*0.04,0),2);

           $item1['developer'] = $item2['developer'] = $value['username'];
           $item1['number'] = (int)$value['stockNum'];
           $item1['orderNum'] = $stockOrderNum;
           $item1['hotStyleNum'] = $hot;
           $item1['exuStyleNum'] = $exu;
           $item1['rate1'] = $rate1;
           $item1['rate2'] = $rate2;
           $item1['createDate'] = date('Y-m-d H:i:s');
           $item1['isStock'] = 'stock';

           $item2['number'] = (int)$value['nonStockNum'];
           $item2['orderNum'] = $nonStockOrderNum;
           $item2['hotStyleNum'] = $nonHot;
           $item2['exuStyleNum'] = $nonExu;
           $item2['rate1'] = $nonRate1;
           $item2['rate2'] = $nonRate2;
           $item2['createDate'] = date('Y-m-d H:i:s');
           $item2['isStock'] = 'nonstock';

           $orderNumList[$k] = $item1;
           $nonOrderNumList[$k] = $item2;
       }
        $tran = Yii::$app->db->beginTransaction();
        try {
            //插入数据表oa_stockGoodsNum
            Yii::$app->db->createCommand()->truncateTable('proCenter.oa_stockGoodsNumReal')->execute();
            Yii::$app->db->createCommand()->batchInsert('proCenter.oa_stockGoodsNumReal',
                ['developer', 'number', 'orderNum', 'hotStyleNum', 'exuStyleNum', 'rate1', 'rate2', 'createDate', 'isStock'], $orderNumList)->execute();
            Yii::$app->db->createCommand()->batchInsert('proCenter.oa_stockGoodsNumReal',
                ['developer', 'number', 'orderNum', 'hotStyleNum', 'exuStyleNum', 'rate1', 'rate2', 'createDate', 'isStock'], $nonOrderNumList)->execute();
            //更新 可用数量  判断当前日期是本月1号，数据还要插入备份表
            if (substr($end, 8, 2) !== '01') {
                $sql = " UPDATE proCenter.oa_stockGoodsNumReal r,proCenter.oa_stockGoodsNum s 
                    SET r.stockNumThisMonth = s.stockNumThisMonth,
				        r.stockNumLastMonth = CASE when ifnull(s.number,0)=0 THEN s.stockNumThisMonth 
				                              ELSE ROUND(ifnull(s.stockNumThisMonth, 30)*r.rate1*r.rate2,0) END
                    WHERE r.developer=s.developer AND substring(r.createDate,1,7) = substring(s.createDate,1,7) AND r.isStock=s.isStock ";
                Yii::$app->db->createCommand($sql)->execute();
            } else {
                //如果当前日期是本月1号，先查询有没有备份数据，在插入备份表
                $sql = " UPDATE proCenter.oa_stockGoodsNumReal r,proCenter.oa_stockGoodsNum s 
                    SET r.stockNumThisMonth = CASE when ifnull(s.number,0)=0 THEN s.stockNumThisMonth 
				                              ELSE ROUND(ifnull(s.stockNumThisMonth, 30)*r.rate1*r.rate2,0) END,
				        r.stockNumLastMonth = CASE when ifnull(s.number,0)=0 THEN s.stockNumThisMonth 
				                              ELSE ROUND(ifnull(s.stockNumThisMonth, 30)*r.rate1*r.rate2,0) END
                    WHERE r.developer=s.developer AND r.isStock=s.isStock
                    AND substring(date_add(r.createDate, interval -1 month),1,10) = substring(s.createDate,1,10) AND r.isStock=s.isStock ";
                Yii::$app->db->createCommand($sql)->execute();
                //判断备份表是否有备份数据, 没有则插入
                $checkSql = "SELECT * FROM proCenter.oa_stockGoodsNum WHERE substring(createDate,1,10)='{$end}'";
                $check = Yii::$app->db->createCommand($checkSql)->queryAll();
                if (!$check) {
                    $sqlRes = "INSERT INTO proCenter.oa_stockGoodsNum(developer,number,orderNum,hotStyleNum,exuStyleNum,rate1,rate2,discount,stockNumThisMonth,stockNumLastMonth,createDate,isStock)
                            SELECT developer,number,orderNum,hotStyleNum,exuStyleNum,rate1,rate2,discount,stockNumThisMonth,stockNumLastMonth,createDate,isStock 
                            FROM  proCenter.oa_stockGoodsNumReal WHERE substring(createDate,1,10) = '{$end}'";
                    Yii::$app->db->createCommand($sqlRes)->execute();
                }
            }
            $tran->commit();
            echo date('Y-m-d H:i:s')." (new)The stock data update successful!\n";;
        }catch (\Exception $e){
            $tran->rollBack();
            echo date('Y-m-d H:i:s')." (new)The stock data update failed!\n";
        }

    }


    /** 查询wish平台商品状态、采购到货天数并更新oa_goodsinfo表数据
     * Date: 2019-05-14 16:54
     * Author: henry
     * @throws \yii\db\Exception
     */
    public function actionWish(){
        $res = Yii::$app->py_db->createCommand("P_oa_updateGoodsStatusToTableOaGoodsInfo")->queryAll();
        //更新 oa_goodsinfo 表的stockDays，goodsStatus
        foreach ($res as $v){
            Yii::$app->db->createCommand()->update('proCenter.oa_goodsinfo',$v,['goodsCode' => $v['goodsCode']])->execute();
        }

        // 更新 oa_goodsinfo 表的wishPublish
        $sql = "UPDATE proCenter.oa_goodsinfo SET wishPublish=
	            CASE WHEN stockDays>0 AND storeName='义乌仓' AND IFNULL(dictionaryName,'') not like '%wish%' and  (completeStatus NOT LIKE '%Wish%' OR completeStatus IS NULL) then 'Y' 
			          ELSE 'N' END ";
        $ss = Yii::$app->db->createCommand($sql)->execute();
        if($ss){
            echo date('Y-m-d H:i:s')." Update successful!\n";
        }else{
            echo date('Y-m-d H:i:s')." Update failed!\n";
        }
    }

    /** 海外仓备货
     * Date: 2019-06-14 16:54
     * Author: henry
     * @throws \yii\db\Exception
     */
    public function actionOverseasReplenish(){
        $step = 400;
        try{
            //清空数据表
            Yii::$app->db->createCommand("TRUNCATE TABLE cache_overseasReplenish;")->execute();

            //插入UK虚拟仓补货数据
            $ukVirtualList = Yii::$app->py_db->createCommand("EXEC oauth_ukVirtualReplenish;")->queryAll();
            $max = ceil(count($ukVirtualList)/$step);
            for ($i = 0; $i < $max; $i++){
                Yii::$app->db->createCommand()->batchInsert('cache_overseasReplenish',
                    [
                        'SKU', 'SKUName', 'goodsCode', 'salerName', 'goodsStatus', 'purchaser', 'supplierName',
                        'saleNum3days', 'saleNum7days', 'saleNum15days', 'saleNum30days', 'trend', 'saleNumDailyAve', 'hopeUseNum',
                        'amount', 'totalHopeUN', 'hopeSaleDays', 'purchaseNum', 'price', 'purCost', 'type'
                    ],
                    array_slice($ukVirtualList,$i*$step, $step))->execute();
            }

            //插入AU真仓补货数据
            $auRealList = Yii::$app->py_db->createCommand("EXEC oauth_auRealReplenish")->queryAll();
            $max = ceil(count($auRealList)/$step);
            for ($i = 0; $i < $max; $i++){
                Yii::$app->db->createCommand()->batchInsert('cache_overseasReplenish',
                    [
                        'SKU', 'SKUName', 'goodsCode', 'salerName', 'goodsStatus', 'price', 'weight', 'purchaser', 'supplierName',
                        'saleNum3days', 'saleNum7days', 'saleNum15days', 'saleNum30days', 'trend', 'saleNumDailyAve', '399HopeUseNum',
                        'uHopeUseNum', 'totalHopeUseNum', 'uHopeSaleDays', 'hopeSaleDays', 'purchaseNum', 'shipNum', 'purCost', 'shipWeight', 'type'
                    ],
                    array_slice($auRealList,$i*$step, $step))->execute();
            }

            //插入UK真仓补货数据
            $ukRealList = Yii::$app->py_db->createCommand("EXEC oauth_ukRealReplenish")->queryAll();
            $max = ceil(count($ukRealList)/$step);
            for ($i = 0; $i < $max; $i++){
                Yii::$app->db->createCommand()->batchInsert('cache_overseasReplenish',
                    [
                        'SKU', 'SKUName', 'goodsCode', 'salerName', 'goodsStatus', 'price', 'weight', 'purchaser', 'supplierName',
                        'saleNum3days', 'saleNum7days', 'saleNum15days', 'saleNum30days', 'trend', 'saleNumDailyAve', '399HopeUseNum',
                        'uHopeUseNum', 'totalHopeUseNum', 'uHopeSaleDays', 'hopeSaleDays', 'purchaseNum', 'shipNum', 'purCost', 'shipWeight', 'type'
                    ],
                    array_slice($ukRealList,$i*$step, $step))->execute();
            }

            echo date('Y-m-d H:i:s')." Get overseas replenish data successful!\n";
        }catch (\Exception $e){
            echo date('Y-m-d H:i:s')." Get overseas replenish data failed!\n";
            //echo $e->getMessage();
        }

    }

    /** 库存情况
     * Date: 2019-06-14 16:54
     * Author: henry
     * @throws \yii\db\Exception
     */
    public function actionStockStatus(){
        $beginTime = time();
        $step = 100;
        try{
            //插入库存预警数据
            Yii::$app->db->createCommand("TRUNCATE TABLE cache_stockWaringTmpData;")->execute();

            $stockList = Yii::$app->py_db->createCommand("EXEC oauth_stockStatus 1;")->queryAll();
            $max = ceil(count($stockList)/$step);
            for ($i = 0; $i < $max; $i++){
                Yii::$app->db->createCommand()->batchInsert('cache_stockWaringTmpData',
                    [
                        'sku', 'storeName', 'goodsStatus', 'salerName', 'costPrice', 'useNum', 'costmoney',
                        'notInStore', 'notInCostmoney', 'hopeUseNum', 'totalCostmoney', 'updateTime'
                    ],
                    array_slice($stockList,$i*$step, $step))->execute();
            }

            //插入30天销售数据
            Yii::$app->db->createCommand("TRUNCATE TABLE cache_30DayOrderTmpData;")->execute();

            $saleList = Yii::$app->py_db->createCommand("EXEC oauth_stockStatus")->queryAll();
            $max = ceil(count($saleList)/$step);
            for ($i = 0; $i < $max; $i++){
                Yii::$app->db->createCommand()->batchInsert('cache_30DayOrderTmpData',
                    [
                        'sku','salerName', 'storeName', 'goodsStatus', 'costMoney', 'updateTime'
                    ],
                    array_slice($saleList,$i*$step, $step))->execute();
            }
            //计算耗时
            $endTime = time();
            $diff = $endTime - $beginTime;
            if($diff >= 3600){
                $hour = floor($diff/3600);
                $diff = $diff%3600;
                $minute = floor($diff/60);
                $second = $diff%60;
                $message = "It takes {$hour} hours,{$minute} minutes and {$second} seconds!";
            }elseif ($diff >= 60){
                $minute = floor($diff/60);
                $second = $diff%60;
                $message = "It takes {$minute} minutes and {$second} seconds!";
            }else{
                $message = "It takes {$diff} seconds!";
            }
            echo date('Y-m-d H:i:s')." Get stock status data successful! $message\n";
        }catch (\Exception $e){
            echo date('Y-m-d H:i:s')." Get stock status data failed! \n";
            //echo $e->getMessage();
        }

    }


    /**
     * @brief 更新主页各人员目标完成度
     */
    public function actionZzTarget()
    {
        $startDate = '2019-05-31';
        $endDate =  date('Y-m-d',strtotime('-1 day'));
        $endDate =  '2019-08-31';
        //计算时间进度
        $dateRate = round((strtotime($endDate) - strtotime($startDate))/86400/92,4);
        //计算销售数据
        $startDate = date('Y-m-01');
        //$startDate = date('2019-06-01');
        //$endDate = date('2019-07-31');
        try {
            ConScheduler::getZzTargetData($startDate, $endDate, $dateRate);
            print date('Y-m-d H:i:s') . " INFO:success to update data of target completion!\n";
        } catch (\Exception $why) {
            print date('Y-m-d H:i:s') . " INFO:fail to update data of target completion cause of $why \n";
        }
    }


    /**
     * @brief 根据最近两周销售SKU重量更新普源SKU重量
     */
    public function actionUpdateWeight()
    {
        $endDate =  date('Y-m-d',strtotime('-1 day'));
        $startDate = date('Y-m-d',strtotime('-9 day', strtotime('-1 day')));
        //计算时间进度
        try {
            Yii::$app->py_db->createCommand("EXEC B_py_ModifyProductWeight '{$startDate}','{$endDate}'")->execute();
            print date('Y-m-d H:i:s') . " INFO:success to update weight of b_goods!\n";
        } catch (\Exception $why) {
            print date('Y-m-d H:i:s') . " INFO:fail to update weight of b_goods cause of $why \n";
        }
    }

    /**
     * 销量变化
     * Date: 2018-12-29 11:55
     * Author: henry
     */
    public function actionSalesChangeInTenDays()
    {
        try {
            $stmt = "EXEC z_demo_zongchange @suffix='',@SalerName='',@pingtai='' ";
            $list = Yii::$app->py_db->createCommand($stmt)->queryAll();
            //print_r($data);exit;
            Yii::$app->db->createCommand("TRUNCATE TABLE cache_salesChangeInTenDays")->execute();
            Yii::$app->db->createCommand()->batchInsert('cache_salesChangeInTenDays',
                ['pingtai', 'suffix', 'goodsCode', 'goodsName', 'goodsSkuStatus', 'categoryName', 'salerName', 'salerName2', 'createDate',
                    'jinyitian', 'shangyitian', 'changeOneDay', 'jinwutian', 'shangwutian', 'changeFiveDay', 'jinshitian', 'shangshitian', 'changeTenDay', 'updateDate'],
                $list)->execute();

            print date('Y-m-d H:i:s') . " INFO:success to update data of sales change in ten days!\n";
        } catch (\Exception $why) {
            print date('Y-m-d H:i:s') . " INFO:fail to update data of sales change in ten days cause of $why \n";
        }
    }

    /**
     * 修改普源图片地址
     * Date: 2018-12-29 11:55
     * Author: henry
     */
    public function actionUpdateUrl()
    {
        try {
            $sql1 = "UPDATE B_GoodsSKU SET BmpFileName='http://121.196.233.153/images/'+ case when CHARINDEX('_',sku, 0) = 0 then sku else SUBSTRING(sku,0, CHARINDEX('_',sku, 0)) end +'.jpg'  
                    WHERE BmpFileName LIKE '%Shop Elf%' OR BmpFileName LIKE '%普源%' OR BmpFileName='' OR BmpFileName NOT LIKE '%121.196.233.153%' ";
            $sql2 = "UPDATE B_Goods SET BmpFileName='http://121.196.233.153/images/'+SKU+'.jpg' 
                    WHERE BmpFileName LIKE '%Shop Elf%' OR BmpFileName LIKE '%普源%' OR BmpFileName='' OR BmpFileName NOT LIKE '%121.196.233.153%'";
            Yii::$app->py_db->createCommand($sql1)->execute();
            Yii::$app->py_db->createCommand($sql2)->execute();

            print date('Y-m-d H:i:s') . " INFO:success to update picture url of shopElf!\n";
        } catch (\Exception $why) {
            print date('Y-m-d H:i:s') . " INFO:fail to update picture url of shopElf of $why \n";
        }
    }


    /** 获取最近一个月产品销量
     * Date: 2019-08-08 15:21
     * Author: henry
     */
    public function actionAmtLatestMonth()
    {
        try {
            $sql = "EXEC guest.oauth_getSalesAmtOfLatestMonth";
            $list = Yii::$app->py_db->createCommand($sql)->queryAll();
            Yii::$app->db->createCommand()->truncateTable('data_salesAmtOfLatestMonth')->execute();
            Yii::$app->db->createCommand()->batchInsert('data_salesAmtOfLatestMonth',['goodsCode','createDate','developer','possessMan1','amt','updateTime'],$list)->execute();

            print date('Y-m-d H:i:s') . " INFO:success to get sales amt of latest month!\n";
        } catch (\Exception $why) {
            print date('Y-m-d H:i:s') . " INFO:fail to get sales amt of latest month because $why \n";
        }
    }


}