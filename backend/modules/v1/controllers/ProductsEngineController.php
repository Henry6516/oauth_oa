<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2019-10-10 16:58
 */

namespace backend\modules\v1\controllers;

use backend\models\EbayCategory;
use backend\models\EbayDeveloperCategory;
use backend\models\EbayProducts;
use backend\models\WishProducts;
use backend\models\JoomProducts;
use backend\models\RecommendEbayNewProductRule;
use backend\models\EbayHotRule;
use yii\data\ArrayDataProvider;
use yii\db\Query;
use yii\helpers\ArrayHelper;
use Yii;

class ProductsEngineController extends AdminController
{

    public $modelClass = 'backend\modules\v1\models\ApiProductsEngine';

    /**
     * @brief recommend  products
     * @return mixed
     */
    public function actionRecommend()
    {
        //获取当前用户信息
        $user = $this->authenticate(Yii::$app->user, Yii::$app->request, Yii::$app->response);
        try {
            $plat = \Yii::$app->request->get('plat');
            $type = \Yii::$app->request->get('type','');
            $page = \Yii::$app->request->get('page',1);
            $pageSize = \Yii::$app->request->get('pageSize',20);
            $marketplace = \Yii::$app->request->get('marketplace');//站点
            $ret = [];
            if ($plat === 'ebay') {
                if($type === 'new') {
                    $cur = (new \yii\mongodb\Query())->from('ebay_new_product')
                        ->andFilterWhere(['marketplace' => $marketplace])->all();
                    foreach ($cur as $row) {
                        if(isset($row['accept']) && $row['accept'] && in_array($user->username, $row['accept']) ||
                            isset($row['refuse'][$user->username])){
                            continue;
                        }else{
                            $ret[] = $row;
                        }
                    }
                    $data = new ArrayDataProvider([
                        'allModels' => $ret,
                        'sort' => [
                            'attributes' => ['price', 'visit', 'sold', 'listedTime'],
                        ],
                        'pagination' => [
                            'page' => $page,
                            'pageSize' => $pageSize,
                        ],
                    ]);
                    return $data;
                }
                if ($type === 'hot') {
                    $cur = (new \yii\mongodb\Query())->from('ebay_hot_product')
                        ->andFilterWhere(['marketplace'=>$marketplace])
                        ->all();
                    foreach ($cur as $row) {
                        if(isset($row['accept']) && $row['accept'] && in_array($user->username, $row['accept']) ||
                            isset($row['refuse'][$user->username])){
                            continue;
                        }else{
                            $ret[] = $row;
                        }
                    }
                    $data = new ArrayDataProvider([
                        'allModels' => $ret,
                        'sort' => [
                            'attributes' => ['price', 'visit', 'sold'],
                        ],
                        'pagination' => [
                            'page' => $page,
                            'pageSize' => $pageSize,
                        ],
                    ]);
                    return $data;

                }
                else {
                    $station = \Yii::$app->request->get('status','US');
                    return EbayProducts::find()->where(['station' => $station])->all();
                }
            }
            if ($plat === 'wish') {
                return WishProducts::find()->all();
            }

            if ($plat === 'joom') {
                return JoomProducts::find()->all();
            }
        }
        catch (\Exception $why) {
            return ['code' => 401, 'message' => $why->getMessage()];
        }

    }


    /**
     * 规则列表
     * @return array|\yii\db\ActiveRecord[]
     */
    public function actionRule()
    {
        $type = Yii::$app->request->get('type','new');
        try {
            if ($type === 'new') {
                return RecommendEbayNewProductRule::find()->all();
            }
            if ($type === 'hot') {
                return EbayHotRule::find()->all();
            }

        }
        catch (\Exception $why) {
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
            $condition = \Yii::$app->request->post('condition');
            $id = ArrayHelper::getValue($condition, 'id', '');
            if ($type === 'new') {
//                $attrs = [
//                    'soldStart' => ArrayHelper::getValue($condition, 'soldStart', ''),
//                    'soldEnd' => ArrayHelper::getValue($condition, 'soldEnd', ''),
//                    'visitStart' => ArrayHelper::getValue($condition, 'visitStart', ''),
//                    'visitEnd' => ArrayHelper::getValue($condition, 'visitEnd', ''),
//                    'priceEnd' => ArrayHelper::getValue($condition, 'priceEnd', ''),
//                    'priceStart' => ArrayHelper::getValue($condition, 'priceStart', ''),
//                    'country' => ArrayHelper::getValue($condition, 'country', ''),
//                    'popularStatus' => ArrayHelper::getValue($condition, 'popularStatus', ''),
//                    'sellerOrStore' => ArrayHelper::getValue($condition, 'sellerOrStore', ''),
//                    'storeLocation' => ArrayHelper::getValue($condition, 'storeLocation', ''),
//                    'salesThreeDayFlag' => ArrayHelper::getValue($condition, 'salesThreeDayFlag', ''),
//                    'listedTime' => ArrayHelper::getValue($condition, 'listedTime', ''),
//                    'itemLocation' => ArrayHelper::getValue($condition, 'itemLocation', ''),
//                    'creator' => ArrayHelper::getValue($condition, 'creator', ''),
//                    'createdDate' => date('Y-m-d H:i:s'),
//                    'updatedDate' => date('Y-m-d H:i:s'),
//                ];
                $rule = RecommendEbayNewProductRule::findOne($id);
                if(empty($rule)) {
                    $rule = new RecommendEbayNewProductRule();
                }
                $rule->setAttributes($condition);
                if (!$rule->save()) {
                    throw new \Exception('fail to add new rule');
                }
                return [];
            }

            if ($type === 'hot') {
                $rule = EbayHotRule::findOne($id);
                if(empty($rule)) {
                    $rule = new EbayHotRule();
                }
                $rule->setAttributes($condition);
                if (!$rule->save()) {
                    throw new \Exception('fail to add new rule');
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
        $type = Yii::$app->request->get('type','new');
        $condition = \Yii::$app->request->post('condition');
        $id = ArrayHelper::getValue($condition, 'id','');
        try {
            if($type === 'new') {
                RecommendEbayNewProductRule::findOne($id)->delete();
            }
            if($type === 'hot') {
                EbayHotRule::findOne($id)->delete();
            }
        }
        catch (\Exception $why) {
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
        $condition = Yii::$app->request->post('condition',null);
        try {
            return EbayCategory::find()
                ->filterWhere(['parentId' => $condition['parentId']])
                ->filterWhere(['like', 'category', $condition['category']])
                ->all();
        } catch (\Exception $why) {
            return ['code' => 401, 'message' => $why->getMessage()];
        }
    }

    /**
     * 发开员eBay类目列表
     * @return array|\yii\db\ActiveRecord[]
     */
    public function actionDevCat()
    {
        $condition = Yii::$app->request->post('condition',null);
        try {
            $query = (new Query())
                ->select('ed.*,ea.category')
                ->from('proEngine.ebay_developer_category ed')
                ->leftJoin('proEngine.ebay_category ea','ea.id=categoryId')
                ->andFilterWhere(['like', 'developer', $condition['developer']])
                ->andFilterWhere(['like', 'category', $condition['category']])
                ->all();
            //$query = EbayDeveloperCategory::find()
            //->joinWith('category')
            //->asArray()->all();
            return $query;
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
            $categoryId= ArrayHelper::getValue($condition, 'categoryId', '');
            $developer= ArrayHelper::getValue($condition, 'developer', '');
            /*if(!$categoryId){
                throw new \Exception('Attribute categoryId can not be empty!');
            }*/
            $model = EbayDeveloperCategory::findOne($id);
            if(empty($model)) {
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
        $id = ArrayHelper::getValue($condition, 'id','');
        try {
            return EbayDeveloperCategory::findOne($id)->delete();
        } catch (\Exception $why) {
            return ['code' => 401, 'message' => $why->getMessage()];
        }
    }

}
