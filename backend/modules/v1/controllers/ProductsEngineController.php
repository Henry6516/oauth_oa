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
use backend\modules\v1\models\ApiUser;
use yii\data\ArrayDataProvider;
use backend\modules\v1\models\ApiProductsEngine;
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

    /** 产品引擎  每日推荐
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

    /** 产品中心  智能推荐
     * Date: 2019-10-30 17:36
     * Author: henry
     * @return array|\yii\db\ActiveRecord[]|\yii\data\ActiveDataProvider[]
     * @throws \yii\db\Exception
     */
    public function actionMindRecommend()
    {
        //获取当前用户信息
        $username = Yii::$app->user->identity->username;
        //$username = '刘爽';
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
                        foreach ($row['receiver'] as $v) {
                            if (in_array($v, $userList)) {  //过滤当前用户的权限下的用户
                                $ret[] = $row;
                                break;
                            }
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
            }

        } catch (\Exception $why) {
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
            //'wish',
            //'joom',
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
    public function  actionCateRuleInfo($id){
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
            /*$res = [];
            foreach ($data as $v) {
                $item = $v;
                if ($v['ruleType'] == 'new') {
                    $ebayNewRule = EbayNewRule::findOne(['_id' => $v['ruleId']]);
                    $item['ruleName'] = $ebayNewRule ? $ebayNewRule['ruleName'] : '';
                } elseif ($v['ruleType'] == 'hot') {
                    $ebayHotRule = EbayHotRule::findOne(['_id' => $v['ruleId']]);
                    $item['ruleName'] = $ebayHotRule ? $ebayHotRule['ruleName'] : '';
                } else {
                    $item['ruleName'] = '';
                }
                $res[] = $item;
            }*/

            return $data;
        } catch (\Exception $why) {
            return ['code' => 401, 'message' => $why->getMessage()];
        }
    }

    //获取分配规则详情
    public function  actionAllotRuleInfo($id){
        return ApiProductsEngine::getAllotInfo($id);
    }

    /**
     * 增加或编辑规则
     * @return array
     */
    public function actionSaveAllotRule()
    {
        try {

            $userName = Yii::$app->user->identity->username;
            $condition = \Yii::$app->request->post('condition');
            $id = ArrayHelper::getValue($condition, 'id', '');
            $rule = EbayAllotRule::findOne($id);
            if (empty($rule)) {
                $rule = new EbayAllotRule();
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


}
