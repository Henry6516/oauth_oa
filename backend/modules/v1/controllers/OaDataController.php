<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019-03-06
 * Time: 10:29
 * Author: henry
 */
/**
 * @name OaDataController.php
 * @desc PhpStorm.
 * @author: Create by henry
 * @since: Created on 2019-03-06 10:29
 */


namespace backend\modules\v1\controllers;

use backend\models\OaGoodsinfo;
use backend\models\OaGoodsinfoExtendsStatus;
use backend\modules\v1\models\ApiOaData;
use Yii;
class OaDataController extends AdminController
{
    public $modelClass = 'backend\modules\v1\models\ApiOaData';

    public $serializer = [
        'class' => 'yii\rest\Serializer',
        'collectionEnvelope' => 'items',
    ];

    public function behaviors()
    {
        return parent::behaviors();
    }

    /**
     * 产品中心
     * Date: 2019-03-07 16:51
     * Author: henry
     * @return \yii\data\ActiveDataProvider
     */
    public function actionProduct(){
        $condition = Yii::$app->request->post()['condition'];
        return ApiOaData::getOaData($condition, 'product');
    }

    /**
     * 销售产品列表
     * Date: 2019-03-08 9:11
     * Author: henry
     * @return \yii\data\ActiveDataProvider
     */
    public function actionSales(){
        $condition = Yii::$app->request->post()['condition'];
        return ApiOaData::getOaData($condition, 'sales');
    }

    /** 标记推广已完成
     * Date: 2019-05-17 11:52
     * Author: henry
     * @return array|bool
     * @throws \yii\db\Exception
     */
    public function actionExtend()
    {
        $username = yii::$app->user->identity->username;
        $cond = yii::$app->request->post()["condition"];
        $ids = isset($cond['id']) && $cond['id'] ? $cond['id'] : [];
        if(!$ids){
            return [
                'code' => 400,
                'message' => 'ID can not be empty!'
            ];
        }
        $connection = yii::$app->db;
        $trans = $connection->beginTransaction();
        try {
            foreach ($ids as $id) {
                $model = OaGoodsinfo::findOne($id);
                //判断当前用户是否可以推广
                $salerList = explode(',',$model->mapPersons);
                if(!in_array($username,$salerList)){
                    throw new \Exception('You can not promote the product!');
                }
                //保存个人推广信息
                $statusModel = OaGoodsinfoExtendsStatus::findOne(['infoId' => $id, 'saler' => $username]);
                if($statusModel){
                    $statusModel->status = '已推广';
                }else{
                    $statusModel = new OaGoodsinfoExtendsStatus();
                    $statusModel->infoId = $id;
                    $statusModel->saler = $username;
                    $statusModel->status = '已推广';
                    $statusModel->createTime = date('Y-m-d H:i:s',time());
                }
                if(!$statusModel->save(false)){
                    throw new \Exception('fail to update extendStatus!');
                }

                //查看销售推广情况
                $salerNum = count($salerList);
                $number = OaGoodsinfoExtendsStatus::find()
                    ->andWhere(['infoId' => $id,'status' => '已推广'])
                    ->count();
                //保存商品所有推广信息（所有人推广完成，商品推广完成）
                if($salerNum == $number){
                    $model->extendStatus = '已推广';
                    $model->save(false);
                }
            }
            $trans->commit();
            return true;
        } catch (\Exception $e) {
            $trans->rollBack();
            $msg = "批量标记推广完成失败！";
            $msg = $e->getMessage();
            return [
                'code' => 400,
                'message' => $msg
            ];
        }
    }

    /**
     * Wish待刊登
     * Date: 2019-03-08 9:11
     * Author: henry
     * @return \yii\data\ActiveDataProvider
     */
    public function actionWish(){
        $condition = Yii::$app->request->post()['condition'];
        return ApiOaData::getOaData($condition,'wish');
    }

    /** 备货表现  不备货表现
     * Date: 2019-05-15 11:53
     * Author: henry
     * @return array
     */
    public function actionStock(){
        return ApiOaData::getStockData('stock');
    }

    /**  不备货表现
     * Date: 2019-05-15 11:53
     * Author: henry
     * @return array
     */
    public function actionNonstock(){
        return ApiOaData::getStockData('nonstock');
    }


    /**
     * 最近30天类目表现
     * Date: 2019-03-11 14:19
     * Author: henry
     * @return \yii\data\ActiveDataProvider
     */
    public function actionCatPerform(){
        return ApiOaData::getCatPerformData();
    }

    public function actionCat(){
        $condition = Yii::$app->request->post()['condition'];
        return ApiOaData::getCatDetailData($condition);
    }

}