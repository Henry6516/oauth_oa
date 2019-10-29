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
     * 认领
     * @return array
     */
    public function actionAccept()
    {
        try {
            $plat = \Yii::$app->request->get('plat');
            $type = \Yii::$app->request->get('type','');
            $condition = Yii::$app->request->post('condition');
            $id = $condition['id'];
            return ApiProductsEngine::accept($plat,$type, $id);

        }
        catch (\Exception $why) {
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
            $type = \Yii::$app->request->get('type','');
            $condition = Yii::$app->request->post('condition');
            $id = $condition['id'];
            return ApiProductsEngine::refuse($plat,$type, $id);

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
            $userName = Yii::$app->user->identity->username;
            $condition = \Yii::$app->request->post('condition');
            $id = ArrayHelper::getValue($condition, 'id', '');
            if ($type === 'new') {
                $rule = RecommendEbayNewProductRule::findOne($id);
                if(empty($rule)) {
                    $rule = new RecommendEbayNewProductRule();
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
                if(empty($rule)) {
                    $rule = new EbayHotRule();
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
