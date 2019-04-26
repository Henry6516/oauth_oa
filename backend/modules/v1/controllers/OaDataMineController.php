<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2019-04-24 16:15
 */

namespace backend\modules\v1\controllers;
use backend\modules\v1\models\ApiMine;
use Codeception\Template\Api;
use Yii;

class OaDataMineController extends AdminController
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
     * @brief 获取采集数据列表
     * @return \yii\data\ActiveDataProvider
     */
    public function actionMineList()
    {
        $condition = Yii::$app->request->post();
        return ApiMine::getMineList($condition);
    }

    /**
     * @brief 获取数据详情
     * @return array
     * @throws \Exception
     */
    public function actionMineInfo()
    {
        $condition = Yii::$app->request->post()['condition'];
        try {
            return ApiMine::getMineInfo($condition);
        }
        catch (\Exception $why) {
            $ret['code'] = $why->getCode();
            $ret['message'] = $why->getMessage();
            return $ret;
        }
    }

    /**
     * @brief 采集数据
     * @return array
     */
    public function actionMine()
    {
        $condition = Yii::$app->request->post()['condition'];
        try {
            return ApiMine::Mine($condition);
        }
        catch (\Exception $why) {
            $ret['code'] = $why->getCode();
            $ret['message'] = $why->getMessage();
            return $ret;
        }
    }

    /**
     * @brief 导出模板
     * @return array
     */
    public function actionExport()
    {
        $condition = Yii::$app->request->post()['condition'];
        try {
            return ApiMine::ExportToJoom($condition);
        }
        catch (\Exception $why) {
            $ret['code'] = $why->getCode();
            $ret['message'] = $why->getMessage();
            return $ret;
        }
    }


    /**
     * @brief 标记完善
     * @return array
     */
    public function actionFinish()
    {
        $condition = Yii::$app->request->post()['condition'];
        try {
            return ApiMine::finish($condition);
        }
        catch (\Exception $why) {
            $ret['code'] = $why->getCode();
            $ret['message'] = $why->getMessage();
            return $ret;
        }
    }

    /**
     * @brief 保存数据
     * @return array
     */
    public function actionSave()
    {
        $condition = Yii::$app->request->post()['condition'];
        try {
            return ApiMine::save($condition);
        }
        catch (\Exception $why) {
            $ret['code'] = $why->getCode();
            $ret['message'] = $why->getMessage();
            return $ret;
        }
    }


    /**
     * @brief 保存并完善
     * @return array
     */
    public function actionSaveAndFinish()
    {
        $condition = Yii::$app->request->post()['condition'];
        try {
            ApiMine::save($condition);
            return ApiMine::finish(['id' => $condition['basicInfo']['id']]);
        }
        catch (\Exception $why) {
            $ret['code'] = $why->getCode();
            $ret['message'] = $why->getMessage();
            return $ret;
        }
    }


    /**
     * @brief 删除多属性条目
     * @return array
     */
    public function actionDeleteDetail()
    {
        $condition = Yii::$app->request->post()['condition'];
        try {
            return ApiMine::deleteDetail($condition);
        }
        catch (\Exception $why) {
            $ret['code'] = $why->getCode();
            $ret['message'] = $why->getMessage();
            return $ret;
        }
    }


    /**
     * @brief 删除条目
     * @return array
     */
    public function actionDelete()
    {
        $condition = Yii::$app->request->post()['condition'];
        try {
            return ApiMine::delete($condition);
        }
        catch (\Exception $why) {
            $ret['code'] = $why->getCode();
            $ret['message'] = $why->getMessage();
            return $ret;
        }
    }


    /**
     * @brief 设置价格
     * @return array
     */
    public function actionSetPrice()
    {
        $condition = Yii::$app->request->post()['condition'];
        try {
            return ApiMine::setPrice($condition);
        }
        catch (\Exception $why) {
            $ret['code'] = $why->getCode();
            $ret['message'] = $why->getMessage();
            return $ret;
        }
    }

    /**
     * @brief 设置类目
     * @return array
     */
    public function actionSetCat()
    {
        $condition = Yii::$app->request->post()['condition'];
        try {
            return ApiMine::setCat($condition);
        }
        catch (\Exception $why) {
            $ret['code'] = $why->getCode();
            $ret['message'] = $why->getMessage();
            return $ret;
        }
    }


    /**
     * @brief 转至开发
     * @return array
     */
    public function actionSendToDevelop()
    {
        $condition = Yii::$app->request->post()['condition'];
        try {
            return ApiMine::sendToDevelop($condition);
        }
        catch (\Exception $why) {
            $ret['code'] = $why->getCode();
            $ret['message'] = $why->getMessage();
            return $ret;
        }
    }

    /**
     * @brief 关联店铺SKU
     * @return array
     */
    public function actionBindShopSku()
    {
        $condition = Yii::$app->request->post()['condition'];
        try {
            return ApiMine::bindShopSku($condition);
        }
        catch (\Exception $why) {
            $ret['code'] = $why->getCode();
            $ret['message'] = $why->getMessage();
            return $ret;
        }
    }


    /**
     * @brief 保存店铺SKU
     * @return array
     */
    public function actionSaveShopSku()
    {
        $condition = Yii::$app->request->post()['condition'];
        try {
            return ApiMine::saveShopSku($condition);
        }
        catch (\Exception $why) {
            $ret['code'] = $why->getCode();
            $ret['message'] = $why->getMessage();
            return $ret;
        }
    }










}