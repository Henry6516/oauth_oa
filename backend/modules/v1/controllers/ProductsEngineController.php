<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2019-10-10 16:58
 */

namespace backend\modules\v1\controllers;

use backend\models\EbayAllotRule;
use backend\models\EbayCategory;
use backend\models\EbayCateRule;
use backend\models\EbayDeveloperCategory;
use backend\models\EbayHotRule;
use backend\models\EbayNewRule;
use backend\models\WishRule;
use backend\modules\v1\models\ApiUser;
use console\models\ProductEngine;
use yii\data\ArrayDataProvider;
use backend\modules\v1\models\ApiProductsEngine;
use yii\db\Exception;
use yii\db\Query;
use yii\helpers\ArrayHelper;
use Yii;

class ProductsEngineController extends AdminController
{
    public $modelClass = 'backend\modules\v1\models\ApiProductsEngine';

    public $serializer = [
        'class' => 'backend\modules\v1\utils\PowerfulSerializer',
        'collectionEnvelope' => 'items',
    ];

    /**
     * 产品引擎  每日推荐
     * Date: 2019-10-30 17:36
     * Author: henry
     * @return array|\yii\db\ActiveRecord[]|\yii\data\ActiveDataProvider[]
     */
    public function actionRecommend()
    {
        try {
            return ApiProductsEngine::recommend();

        } catch (\Exception $why) {
            return ['code' => 401, 'message' => $why->getMessage()];
        }
    }

    /**
     * 产品中心  智能推荐
     * Date: 2019-12-23 10:12
     * Author: henry
     * @return array|ArrayDataProvider
     * @throws \yii\db\Exception
     */
    public function actionMindRecommend()
    {
        //获取当前用户信息
        $username = Yii::$app->user->identity->username;
        $userList = ApiUser::getUserList($username);
        //获取当前登录用户权限下的用户是否有指定eBay产品类目

        try {
            $plat = \Yii::$app->request->get('plat');
            $type = \Yii::$app->request->get('type', '');
            $page = \Yii::$app->request->get('page', 1);
            $pageSize = \Yii::$app->request->get('pageSize', 20);
            $marketplace = \Yii::$app->request->get('marketplace');//站点
            $ret = [];
            if ($plat === 'ebay') {
                $list = (new \yii\mongodb\Query())->from('ebay_recommended_product')
                    ->andFilterWhere(['marketplace' => $marketplace])
                    ->andFilterWhere(['productType' => $type])
                    ->andFilterWhere(['dispatchDate' => ['$regex' => date('Y-m-d')]])
                    ->all();
                foreach ($list as $row) {
                    if (isset($row['accept']) && $row['accept'] ||    //过滤掉已经认领的产品
                        isset($row['refuse'][$username])       //过滤掉当前用户已经过滤的产品
                    ) {
                        continue;
                    } else {
                        $receiver = [];
                        foreach ($row['receiver'] as $v) {
                            if (in_array($v, $userList)) {  //过滤被推荐人(不在自己权限下的被推荐人筛选掉)
                                $receiver[] = $v;
                            }
                        }
                        //过滤当前用户的权限下的用户
                        $row['receiver'] = $receiver;
                        if ($receiver) {
                            $ret[] = $row;
                        }
                    }
                }
                $data = new ArrayDataProvider([
                    'allModels' => $ret,
                    'sort' => [
                        'attributes' => ['price', 'visit', 'sold', 'listedTime'],
                        'defaultOrder' => [
                            'sold' => SORT_DESC,
                        ]
                    ],
                    'pagination' => [
                        'page' => $page - 1,
                        'pageSize' => $pageSize,
                    ],
                ]);
                return $data;
            }elseif ($plat == 'wish'){
                $list = (new \yii\mongodb\Query())
                    ->from('wish_recommended_product')
                    ->andFilterWhere(['productType' => $type])
                    ->andFilterWhere(['dispatchDate' => ['$regex' => date('Y-m-d')]])
                    ->all();
                foreach ($list as $row) {
                    if (isset($row['accept']) && $row['accept'] ||    //过滤掉已经认领的产品
                        isset($row['refuse'][$username])       //过滤掉当前用户已经过滤的产品
                    ) {
                        continue;
                    } else {
                        $receiver = [];
                        foreach ($row['receiver'] as $v) {
                            if (in_array($v, $userList)) {  //过滤被推荐人(不在自己权限下的被推荐人筛选掉)
                                $receiver[] = $v;
                            }
                        }
                        //过滤当前用户的权限下的用户
                        $row['receiver'] = $receiver;
                        if ($receiver) {
                            $ret[] = $row;
                        }
                    }
                }
                $data = new ArrayDataProvider([
                    'allModels' => $ret,
                    'sort' => [
                        'attributes' => ['rating', 'totalprice', 'maxNumBought', 'genTime'],
                        'defaultOrder' => [
                            'maxNumBought' => SORT_DESC,
                        ]
                    ],
                    'pagination' => [
                        'page' => $page - 1,
                        'pageSize' => $pageSize,
                    ],
                ]);
                return $data;
            }

        } catch (\Exception $why) {
            return ['code' => 401, 'message' => $why->getMessage()];
        }
    }

    /**
     * 能接受推荐产品的开发
     */
    public function actionRecommendDeveloper ()
    {
        try {
            return ApiProductsEngine::recommendDeveloper();
        }
        catch (\Exception $why) {
            return ['code' => 401, 'message' => $why->getMessage()];
        }
    }
    /**
     * 人工推送产品给开发
     */
    public function actionManualRecommend()
    {
        $condition = Yii::$app->request->post('condition', null);
        try {
            ApiProductsEngine::manualRecommend($condition);
        }
        catch (\Exception $why) {
            return ['code' => 401, 'message' => $why->getMessage()];
        }

    }


    /**
     * 认领
     * @return array
     */
    public function actionAccept()
    {
        try {
            $plat = \Yii::$app->request->get('plat');
            $type = \Yii::$app->request->get('type', '');
            $condition = Yii::$app->request->post('condition');
            $id = $condition['id'];
            return ApiProductsEngine::accept($plat, $type, $id);

        } catch (\Exception $why) {
            return ['code' => 401, 'message' => $why->getMessage()];
        }
    }

    /**
     * 拒绝
     * @return array|mixed
     */
    public function actionRefuse()
    {
        try {
            $plat = \Yii::$app->request->get('plat');
            $type = \Yii::$app->request->get('type', '');
            $condition = Yii::$app->request->post('condition');
            $id = $condition['id'];
            $reason = isset($condition['reason']) && $condition['reason'] ? $condition['reason'] : '拒绝';
            $res = ApiProductsEngine::refuse($plat, $type, $id, $reason);
            return $res;

        } catch (\Exception $why) {
            return ['code' => 401, 'message' => $why->getMessage()];
        }
    }

    /**
     * 立即执行规则
     * @return array
     */
    public function actionRunRule()
    {
        try {
            $condition = Yii::$app->request->post('condition');
            $ruleType = Yii::$app->request->get('type', '');
            $ruleId = $condition['ruleId'];
            return ApiProductsEngine::run($ruleType, $ruleId);


        } catch (\Exception $why) {
            return ['code' => 401, 'message' => $why->getMessage()];
        }
    }

    /**
     * 规则列表
     * @return array|\yii\db\ActiveRecord[]
     */
    public function actionRule()
    {
        $type = Yii::$app->request->get('type', 'new');
        try {
            if ($type === 'new') {
                return EbayNewRule::find()->all();
            }
            if ($type === 'hot') {
                return EbayHotRule::find()->all();
            }

        } catch (\Exception $why) {
            return ['code' => 401, 'message' => $why->getMessage()];
        }
    }

    /**
     * 增加规则
     * @return array
     */
    public function actionSaveRule()
    {
        try {

            $type = Yii::$app->request->get('type', 'new');
            $userName = Yii::$app->user->identity->username;
            $condition = \Yii::$app->request->post('condition');
            $id = ArrayHelper::getValue($condition, 'id', '');
            if ($type === 'new') {
                $rule = EbayNewRule::findOne($id);
                if (empty($rule)) {
                    $rule = new EbayNewRule();
                    $condition['creator'] = $userName;
                }
                $rule->setAttributes($condition);
                if (!$rule->save(false)) {
                    throw new \Exception('fail to save new rule');
                }
                return [];
            }

            if ($type === 'hot') {
                $rule = EbayHotRule::findOne($id);
                if (empty($rule)) {
                    $rule = new EbayHotRule();
                    $condition['creator'] = $userName;
                }
                $rule->setAttributes($condition);
                if (!$rule->save()) {
                    throw new \Exception('fail to save hot rule');
                }
                return [];
            }

        } catch (\Exception $why) {
            return ['code' => 401, 'message' => $why->getMessage()];
        }
    }

    /**
     * 删除规则
     * @return array
     * @throws \Throwable
     */
    public function actionDeleteRule()
    {
        $type = Yii::$app->request->get('type', 'new');
        $condition = \Yii::$app->request->post('condition');
        $id = ArrayHelper::getValue($condition, 'id', '');
        try {
            if ($type === 'new') {
                EbayNewRule::findOne($id)->delete();
            }
            if ($type === 'hot') {
                EbayHotRule::findOne($id)->delete();
            }
        } catch (\Exception $why) {
            return ['code' => 401, 'message' => $why->getMessage()];
        }
    }


    //==========================================================================

    /**
     * eBay类目列表
     * @return array|\yii\db\ActiveRecord[]
     */
    public function actionEbayCat()
    {
        $condition = Yii::$app->request->post('condition', null);
        try {
            return EbayCategory::find()
                ->andFilterWhere(['parentId' => $condition['parentId']])
                ->andFilterWhere(['like', 'category', $condition['category']])
                ->andFilterWhere(['like', 'marketplace', $condition['marketplace']])
                ->orderBy('parentId,category')
                ->all();
        } catch (\Exception $why) {
            return ['code' => 401, 'message' => $why->getMessage()];
        }
    }

    /** 发开员eBay类目列表
     * Date: 2019-10-31 15:11
     * Author: henry
     * @return array|ArrayDataProvider
     */
    public function actionDevCat()
    {
        $condition = Yii::$app->request->post('condition', null);
        try {
            $query = (new Query())
                ->select(["ed.*",
                    "p.category as firstCategory",
                    "ea.category",
                    "ea.marketplace",
                ])
                ->from('proEngine.ebay_developer_category ed')
                ->leftJoin('proEngine.ebay_category ea', 'ea.id=categoryId')
                ->leftJoin('proEngine.ebay_category p', 'p.id=ea.parentId')
                ->andFilterWhere(['like', 'developer', $condition['developer']])
                ->andFilterWhere(['like', 'ea.category', $condition['category']])
                ->andFilterWhere(['like', 'ea.marketplace', $condition['marketplace']])
                ->all();
            return new ArrayDataProvider([
                'allModels' => $query,
                'pagination' => [
                    'page' => isset($condition['page']) && $condition['page'] ? $condition['page'] - 1 : 0,
                    'pageSize' => isset($condition['pageSize']) && $condition['pageSize'] ? $condition['pageSize'] : 20,
                ],
            ]);
        } catch (\Exception $why) {
            return ['code' => 401, 'message' => $why->getMessage()];
        }
    }

    /**
     * 增加发开员eBay类目规则
     * @return array
     */
    public function actionSaveDevCat()
    {
        try {

            $condition = \Yii::$app->request->post('condition');
            $id = ArrayHelper::getValue($condition, 'id', '');
            $categoryId = ArrayHelper::getValue($condition, 'categoryId', '');
            $developer = ArrayHelper::getValue($condition, 'developer', '');
            /*if(!$categoryId){
                throw new \Exception('Attribute categoryId can not be empty!');
            }*/
            $model = EbayDeveloperCategory::findOne($id);
            if (empty($model)) {
                $model = new EbayDeveloperCategory();
            }
            $model->setAttributes([
                'id' => $id,
                'developer' => $developer,
                'categoryId' => $categoryId,
            ]);
            if (!$model->save()) {
                throw new \Exception('fail to add new ebay developer category rule');
            }
            return [];
        } catch (\Exception $why) {
            return ['code' => 401, 'message' => $why->getMessage()];
        }
    }

    /** 删除发开员eBay类目规则
     * Date: 2019-10-28 15:07
     * Author: henry
     * @return array|false|int
     * @throws \Throwable
     */
    public function actionDeleteDevCat()
    {
        $condition = \Yii::$app->request->post('condition');
        $id = ArrayHelper::getValue($condition, 'id', '');
        try {
            return EbayDeveloperCategory::findOne($id)->delete();
        } catch (\Exception $why) {
            return ['code' => 401, 'message' => $why->getMessage()];
        }
    }

    //======================================================================================
    //类目规则
    public function actionPlat()
    {
        return [
            'ebay',
            'wish',
            'shopee',
        ];
    }

    public function actionMarketplace()
    {
        $plat = Yii::$app->request->get('plat', null);
        try {
            return EbayCategory::find()->andFilterWhere(['plat' => $plat])->distinct('marketplace');
        } catch (\Exception $why) {
            return ['code' => 401, 'message' => $why->getMessage()];
        }
    }

    public function actionPyCate()
    {
        $cateList = Yii::$app->runAction('/v1/oa-goodsinfo/attribute-info-cat')['data'];
        try {
            $cate = EbayCateRule::find()->distinct('pyCate');
            $excludeCate = EbayCateRule::find()->distinct('excludePyCate');
            return array_values(array_unique(array_merge($cateList, $cate, $excludeCate)));
        } catch (\Exception $why) {
            return ['code' => 401, 'message' => $why->getMessage()];
        }
    }

    /**
     * 类目列表
     * @return array|\yii\db\ActiveRecord[]
     */
    public function actionCategory()
    {
        $condition = Yii::$app->request->post('condition', null);
        try {
            return EbayCategory::find()
                ->andFilterWhere(['plat' => $condition['plat']])
                ->andFilterWhere(['marketplace' => $condition['marketplace']])
                ->andFilterWhere(['like', 'cate', $condition['cate']])
                ->orderBy('cate')
                ->all();
        } catch (\Exception $why) {
            return ['code' => 401, 'message' => $why->getMessage()];
        }
    }


    /**
     * 启用规则
     * @return array
     */
    public static function actionStartRule()
    {
        try {
            $condition = Yii::$app->request->post('condition', null);
            ApiProductsEngine::startRule($condition);
        }
        catch (\Exception $why) {
            return ['code' => 401, 'message' => $why->getMessage()];
        }
    }

    /**
     * 停用规则
     * @return array
     */
    public static function actionStopRule()
    {
        try {
            $condition = Yii::$app->request->post('condition', null);
            ApiProductsEngine::stopRule($condition);
        }
        catch (\Exception $why) {
            return ['code' => 401, 'message' => $why->getMessage()];
        }
    }



    public function actionSaveCategory()
    {
        try {

            $condition = \Yii::$app->request->post('condition');
            $id = ArrayHelper::getValue($condition, 'id', '');
            $rule = EbayCategory::findOne($id);
            if (empty($rule)) {
                $rule = new EbayCategory();
            }
            $rule->setAttributes($condition);
            if (!$rule->save(false)) {
                throw new \Exception('fail to save new rule');
            }
            return [];

        } catch (\Exception $why) {
            return ['code' => 401, 'message' => $why->getMessage()];
        }
    }

    //获取类目规则详情
    public function actionCateRuleInfo($id)
    {
        return ApiProductsEngine::getCateInfo($id);
    }

    /** 类目规则列表
     * Date: 2019-11-05 14:26
     * Author: henry
     * @return array|ArrayDataProvider
     */
    public function actionCateRule()
    {
        $page = Yii::$app->request->get('page', 1);
        $pageSize = Yii::$app->request->get('pageSize', 20);
        try {
            $data = EbayCateRule::find()->all();
            return $data = new ArrayDataProvider([
                'allModels' => $data,
                'pagination' => [
                    'page' => $page - 1,
                    'pageSize' => $pageSize,
                ],
            ]);
        } catch (\Exception $why) {
            return ['code' => 401, 'message' => $why->getMessage()];
        }
    }


    /**
     * 增加或编辑规则
     * @return array
     */
    public function actionSaveCateRule()
    {
        try {

            $condition = \Yii::$app->request->post('condition');
            $id = ArrayHelper::getValue($condition, 'id', '');
            $rule = EbayCateRule::findOne($id);
            if (empty($rule)) {
                $rule = new EbayCateRule();
            }
            $rule->setAttributes($condition);
            if (!$rule->save(false)) {
                throw new \Exception('fail to save new rule');
            }
            return [];

        } catch (\Exception $why) {
            return ['code' => 401, 'message' => $why->getMessage()];
        }
    }

    /**
     * 删除规则
     * @return array
     * @throws \Throwable
     */
    public function actionDeleteCateRule()
    {
        $condition = \Yii::$app->request->post('condition');
        $id = ArrayHelper::getValue($condition, 'id', '');
        try {
            EbayCateRule::findOne($id)->delete();
        } catch (\Exception $why) {
            return ['code' => 401, 'message' => $why->getMessage()];
        }
    }


//======================================================================================
    //分配规则
    public function actionAllotRule()
    {
        try {
            $data = EbayAllotRule::find()->asArray()->all();
            return $data;
        } catch (\Exception $why) {
            return ['code' => 401, 'message' => $why->getMessage()];
        }
    }

    //获取分配规则详情
    public function actionAllotRuleInfo($id)
    {
        return ApiProductsEngine::getAllotInfo($id);
    }

    /**
     * 增加或编辑规则
     * @return array
     */
    public function actionSaveAllotRule()
    {
        try {
            $condition = \Yii::$app->request->post('condition');
            $id = ArrayHelper::getValue($condition, 'id', '');

            if (empty($id)) {
                $rule = new EbayAllotRule();
            }else{
                $rule = EbayAllotRule::findOne($id);
            }
            $rule->setAttributes($condition);
            if (!$rule->save(false)) {
                throw new \Exception('fail to save new rule');
            }
            return [];

        } catch (\Exception $why) {
            return ['code' => 401, 'message' => $why->getMessage()];
        }
    }

    /**
     * 删除规则
     * @return array
     * @throws \Throwable
     */
    public function actionDeleteAllotRule()
    {
        $condition = \Yii::$app->request->post('condition');
        $id = ArrayHelper::getValue($condition, 'id', '');
        try {
            EbayAllotRule::findOne($id)->delete();
        } catch (\Exception $why) {
            return ['code' => 401, 'message' => $why->getMessage()];
        }
    }

//===========================================================================================
    //统计报表

    /**
     * 统计报表首页，每日统计
     * Date: 2019-11-20 9:39
     * Author: henry
     * @return array
     */
    public function actionDailyReport()
    {
        return ProductEngine::getDailyReportData();
    }

    /**
     * 认领产品报表
     * Date: 2019-11-19 8:54
     * Author: henry
     * Date: 2020-01-09 11:29
     * Author: henry
     * @return array
     */
    public function actionProductReport()
    {
        $condition = Yii::$app->request->post('condition');
        $developer = isset($condition['developer']) && $condition['developer'] ? $condition['developer'] : [];
        $beginDate = isset($condition['dateRange']) && $condition['dateRange'] ? $condition['dateRange'][0] : '';
        $endDate = isset($condition['dateRange']) && $condition['dateRange'] ? ($condition['dateRange'][1] . " 23:59:59") : '';
        //计算开发分配产品总数
        try{
            return ProductEngine::getProductReportData($developer, $beginDate, $endDate);
        }catch (Exception $e){
            return [
                'code' => 400,
                'message' => 'failed get product report data cause of '.$e->getMessage(),
            ];
        }
    }

    /**
     * 推送规则统计
     * Date: 2019-11-19 15:52
     * Author: henry
     * @return array
     */
    public function actionRuleReport()
    {
        $condition = Yii::$app->request->post('condition');
        $plat = isset($condition['plat']) && $condition['plat'] ? $condition['plat'] : '';
        $ruleType = isset($condition['ruleType']) && $condition['ruleType'] ? $condition['ruleType'] : '';
        $ruleName = isset($condition['ruleName']) && $condition['ruleName'] ? $condition['ruleName'] : '';
        $beginDate = isset($condition['dateRange']) && $condition['dateRange'] ? $condition['dateRange'][0] : '';
        $endDate = isset($condition['dateRange']) && $condition['dateRange'] ? ($condition['dateRange'][1] . ' 23:59:59') : '';

        //获取eBay推送规则列表并统计产品数
        if ($ruleType) {
            $ebayData = ProductEngine::getEbayRuleData('ebay', $ruleType, $ruleName, $beginDate, $endDate);
        }else{
            $newData = ProductEngine::getEbayRuleData('ebay', 'new', $ruleName, $beginDate, $endDate);
            $hotData = ProductEngine::getEbayRuleData('ebay', 'hot', $ruleName, $beginDate, $endDate);
            $ebayData = array_merge($newData, $hotData);
        }
        $wishData = ProductEngine::getWishRuleData('wish', 'new', $ruleName, $beginDate, $endDate);

        if(!$plat) {
            return array_merge($ebayData, $wishData);
        }if($plat == 'ebay'){
            return $ebayData;
        }elseif($plat == 'wish'){
            return $wishData;
        }
    }


    /**
     * 过滤理由统计
     * Date: 2019-11-22 15:52
     * Author: henry
     * @return array
     */
    public function actionRefuseReport()
    {
        $condition = Yii::$app->request->post('condition');
        $beginDate = isset($condition['dateRange']) && $condition['dateRange'] ? $condition['dateRange'][0] : '';
        $endDate = isset($condition['dateRange']) && $condition['dateRange'] ? ($condition['dateRange'][1] . ' 23:59:59') : '';

        list($ebayRefuseData, $ebayOtherRefuseData) = ProductEngine::getRefuseData('ebay', $beginDate, $endDate);
        list($wishRefuseData, $wishOtherRefuseData) = ProductEngine::getRefuseData('wish', $beginDate, $endDate);

        $refuseData = $otherDetailData = [];
        foreach ($ebayRefuseData as $k => $val){
            foreach ($wishRefuseData as $j => $v){
                if($k == $j){
                    $item['refuse'] = $k;
                    $item['num'] = $val + $v;
                    $refuseData[] = $item;
                }
            }
        }
        $allKeys = array_unique(array_merge(array_keys($ebayOtherRefuseData),array_keys($wishOtherRefuseData)));
        if($allKeys){
            foreach ($allKeys as $i => $val){
                $otherDetailData[$i]['name'] = $val;
                $otherDetailData[$i]['num'] = 0;
                foreach ($ebayOtherRefuseData as $k => $v){
                    if($val == $k){
                        $otherDetailData[$i]['num'] += $v;
                    }
                }
                foreach ($wishOtherRefuseData as $k => $v){
                    if($val == $k){
                        $otherDetailData[$i]['num'] += $v;
                    }
                }
            }
        }
        return [
            'refuse' => $refuseData,
            'detail' => $otherDetailData,
        ];
    }


    //=========================================================================================
    //产品过滤
    public static function actionImageSearch()
    {
        try {
            $condition = Yii::$app->request->post('condition');
            $imageUrl = $condition['imageUrl'];
            return ApiProductsEngine::imageSearch($imageUrl);
        }
        catch (\Exception $why) {
            return ['code' => 401, 'message' => $why->getMessage()];
        }

    }

    /**
     * 根据商品编码获取SKU信息
     * Date: 2019-12-06 9:03
     * Author: henry
     * @return array
     */
    public static function actionSkuInfo(){
        try {
            $condition = Yii::$app->request->post('condition');
            $goodsCode = $condition['goodsCode'];
            $sql = "EXEC Y_R_KC_StockingWaringAll '',0,0,'','0','{$goodsCode}','','',50000,1,'','1','','',''";
            return Yii::$app->py_db->createCommand($sql)->queryAll();
        }
        catch (\Exception $why) {
            return ['code' => 401, 'message' => $why->getMessage()];
        }
    }



}
