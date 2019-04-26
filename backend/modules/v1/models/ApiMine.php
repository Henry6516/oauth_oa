<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2019-04-24 16:18
 */

namespace backend\modules\v1\models;

use yii\data\ActiveDataProvider;
use backend\models\OaDataMine;
use backend\models\OaDataMineDetail;
use backend\models\OaGoods;
use backend\models\ShopElf\BGoodsSKULinkShop;
use Exception;
use Yii;


class ApiMine
{

    /**
     * @brief 获取采集数据列表
     * @param $condition
     * @return ActiveDataProvider
     */
    public static function getMineList($condition)
    {

        $pageSize = isset($condition['pageSize']) ? $condition['pageSize'] : 10;
        $query = OaDataMine::find();
        $provider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => $pageSize,
            ],
        ]);
        return $provider;
    }

    /**
     * @brief 获取条目的详细信息
     * @param $condition
     * @return array
     * @throws \Exception
     */
    public static function getMineInfo($condition)
    {
        $id = isset($condition['id']) ? $condition['id'] : '';
        if (empty($id)) {
            throw new Exception('id 不能为空', '400001');
        }
        $mine = OaDataMine::find()->where(['id' => $id])->asArray()->one();
        $mineDetail = OaDataMineDetail::find()->select('id,mid,parentId,proName,description,tags,childId,color,
        proSize,quantity,price,msrPrice,shipping,shippingWeight,shippingTime,varMainImage')
            ->where(['mid' => $id])->asArray()->all();
        $images = OaDataMineDetail::find()->select('extraImage0,extraImage1,extraImage2,extraImage3,extraImage4,
        extraImage5,extraImage6,extraImage7,extraImage8,extraImage9,extraImage10,mainImage')->
        where(['mid' => $id])->asArray()->one();
        return['basicInfo' => $mine, 'images' => $images, 'detailsInfo' => $mineDetail];
    }


    /**
     * @brief 标记玩善
     * @param $condition
     * @throws Exception
     * @return array
     */
    public static function finish($condition)
    {
        $id = isset($condition['id']) ? $condition['id'] : '';
        if (empty($id)) {
            throw new Exception('id 不能为空', '400001');
        }
        if (!is_array($id)) {
            $id = [$id];
        }
        $trans = Yii::$app->db->beginTransaction();
        try{
            foreach ($id as $mid) {
                $mine = OaDataMine::findOne(['id' => $mid]);
                if ($mine === null) {
                    throw new Exception('无效的ID', '400002');
                }
                $mine->setAttribute('detailStatus','已完善');
                if(!$mine->save()){
                    throw  new \Exception('保存失败！', '400003');
                }
            }
            $trans->commit();
        }
        catch (\Exception $why){
            $trans->rollBack();
            throw  new \Exception('保存失败！', '400003');
        }
        return [];
    }

    /**
     * @brief 设置价格
     * @param $condition
     * @return array
     * @throws Exception
     * @throws \yii\db\Exception
     */
    public static function setPrice($condition)
    {
        $id = isset($condition['id']) ? $condition['id'] : '';
        $price = isset($condition['price']) ? (int)$condition['price'] : 0;
        $operator = isset($condition['operator']) ? $condition['operator'] : '=';

        if (empty($id)) {
            throw new Exception('id 不能为空', '400001');
        }
        if (!is_array($id)) {
            throw new Exception('id 应为数组！', '400004');
        }
        $trans = Yii::$app->db->beginTransaction();
        try {
            foreach ($id as $mid) {
                $mine = OaDataMineDetail::findAll(['mid' => $mid]);
                foreach ($mine as $row) {
                    $oldPrice = $row->price;
                    $newPrice = round(static::_calculatePrice($oldPrice, $price, $operator),2);
                    $row->setAttribute('price', $newPrice);
                    if(!$row->save()){
                        throw  new \Exception('保存失败！', '400003');
                    }
                }
            }
            $trans->commit();
        }
        catch (\Exception $why){
            $trans->rollBack();
            throw  new \Exception('保存失败！', '400003');
        }
        return [];

    }

    /**
     * @brief 设置类目
     * @param $condition
     * @return array
     * @throws Exception
     * @throws \yii\db\Exception
     */
    public static function setCat($condition)
    {
        $id = isset($condition['id']) ? $condition['id'] : '';
        $cat = isset($condition['cat']) ? (int)$condition['cat'] : '';
        $subCat = isset($condition['subCat']) ? $condition['subCat'] : '';

        if (empty($id)) {
            throw new Exception('id 不能为空', '400001');
        }
        if (!is_array($id)) {
            throw new Exception('id 应为数组！', '400004');
        }
        $trans = Yii::$app->db->beginTransaction();
        try {
            foreach ($id as $mid) {
                $mine = OaDataMineDetail::findAll(['mid' => $mid]);
                foreach ($mine as $row) {
                    $row->setAttribute('cat', $cat);
                    $row->setAttribute('subCat', $subCat);
                    if(!$row->save()){
                        throw  new \Exception('保存失败！', '400003');
                    }
                }
            }
            $trans->commit();
        }
        catch (\Exception $why){
            $trans->rollBack();
            throw  new \Exception('保存失败！', '400003');
        }
        return [];

    }

    /**
     * @brief 删除条目
     * @param $condition
     * @return array
     * @throws Exception
     */
    public static function delete($condition)
    {
        $id = isset($condition['id']) ? $condition['id'] : '';
        if (empty($id)) {
            throw new Exception('id 不能为空', '400001');
        }
        $trans = Yii::$app->db->beginTransaction();
        try {
            OaDataMine::deleteAll(['id' =>$id]);
            OaDataMineDetail::deleteAll(['mid' => $id]);
            $trans->commit();
        }
        catch (\Exception $why) {
            $trans->rollBack();
            throw  new Exception('删除失败！', '400005');

        }
        return [];
    }


    /**
     * @brief 删除多属性条目
     * @param $condition
     * @return array
     * @throws Exception
     * @throws \yii\db\Exception
     */
    public static function deleteDetail($condition)
    {
        $id = isset($condition['id']) ? $condition['id'] : '';
        if (empty($id)) {
            throw new Exception('id 不能为空', '400001');
        }
        if (!\is_array($id)) {
            $id = [$id];
        }
        $trans = Yii::$app->db->beginTransaction();
        try {
            foreach ($id as $varId) {
                OaDataMineDetail::deleteAll(['id' => $varId]);
            }
            $trans->commit();
        }
        catch (\Exception $why) {
            $trans->rollBack();
            throw  new \Exception('删除失败！', '400005');

        }
        return [];
    }

    /**
     * @brief 转至开发
     * @param $condition
     * @throws Exception
     * @throws \yii\db\Exception
     */
    public static function sendToDevelop($condition)
    {
        $id = isset($condition['id']) ? $condition['id'] : '';
        $stockUp = isset($condition['stockUp']) ? $condition['stockUp'] : 0;
        $user = Yii::$app->user->identity->username;
        $mine = OaDataMine::findOne($id);
        if($mine === null) {
           throw new Exception('无效的ID', '400002');
        }
        if($mine->devStatus !=='未开发') {
            throw new Exception('该状态下产品不能转至开发','400006');
        }
        static::_sendToDevelop($mine,$user, $stockUp);
    }

    /**
     * @brief 关联店铺SKu
     * @param $condition
     * @return array
     * @throws Exception
     */
    public static function bindShopSku($condition)
    {
        $id = isset($condition['id']) ? $condition['id'] : '';
        if (empty($id)) {
            throw new Exception('id 不能为空', '400001');
        }
        $mine = OaDataMine::findOne($id);
        if($mine === null) {
            throw  new Exception('无效的ID', '400002');
        }
        $goodsCode = $mine->goodsCode;
        $variations = OaDataMineDetail::find()->select('id,childId,color,proSize,pySku,mainImage')
            ->where(['mid' => $id])->asArray()->all();
        return ['goodsCode' => $goodsCode, 'pyGoodsCode' => '', 'variations' => $variations];
    }

    /**
     * @brief 保存店铺SKU
     * @param $condition
     * @return array
     * @throws Exception
     */
    public static function saveShopSku($condition)
    {
        $variations = $condition['variations'];
        $pyGoodsCode = $condition['pyGoodsCode'];
        $goodsCode = $condition['goodsCode'];

        //bind trans
        $trans = Yii::$app->py_db->beginTransaction();
        try{
            foreach ($variations as $var) {
                $shopSku = BGoodsSKULinkShop::findOne(['SKU' => $var['pySku'], 'ShopSKU' => $var['childId']]);
                if($shopSku === null) {
                    $shopSku = new BGoodsSKULinkShop();
                }
                $shopSku->setAttributes(['SKU' => $var['pySku'], 'ShopSKU' => $var['childId']]);
                if(!$shopSku->save()){
                    throw new Exception('关联失败！', '400008');
                }
                $detail = OaDataMineDetail::findOne($var['id']);
                $detail->setAttributes(['pySku' => $var['pySku']]);
                if(!$detail->save()) {
                    throw new Exception('关联失败！', '400008');
                }
            }
            $mine = OaDataMine::findOne(['goodsCode' => $goodsCode]);
            $mine->setAttributes(['pyGoodsCode' => $pyGoodsCode, 'devStatus' => '已关联']);
            if(!$mine->save()) {
                throw new Exception('关联失败！', '400008');
            }
            $trans->commit();
        }
        catch (Exception $why) {
            $trans->rollBack();
        }
        return [];

    }

    public static function save($condition)
    {
        $basicInfo = $condition['basicInfo'];
        $variations = $condition['variations'];
    }
    /**
     * @brief 计算价格
     * @param $oldPrice
     * @param $price
     * @param $operator
     * @return float|int
     */
    private static function _calculatePrice($oldPrice, $price, $operator)
    {
        if ($operator === '=') {
            return $price;
        }
        if ($operator === '+') {
            return $oldPrice + $price;
        }
        if ($operator === '-') {
            return $oldPrice - $price;
        }

        if ($operator === '*') {
            return $oldPrice * $price;
        }
        if ($operator === '/') {
            return $oldPrice / $price;
        }

    }

    /**
     * @brief 转至开发事务
     * @param $mine
     * @param $developer
     * @param $stockUp
     * @throws Exception
     * @throws \yii\db\Exception
     */
    private static function _sendToDevelop($mine, $developer, $stockUp)
    {
        $row = [
            'cate,'  => $mine['cat'],
            'devNum,'  => $mine['goodsCode'],
            'devStatus,'  => '正向认领',
            'checkStatus,'  => '待审批',
//            'createDate,'  => date('Y-m-d H:i:s'),
//            'updateDate,'  => date('Y-m-d H:i:s'),
            'img,'  => $mine['mainImage'],
            'subCate,'  => $mine['subCat'],
            'origin1,'  => 'https://www.joom.com/en/products/'. $mine['proId'],
            'developer,'  => $developer,
            'stockUp,'  => $stockUp,
            'mineId'  => $mine['id'],
        ];

        $goods = new OaGoods();
        $trans =  Yii::$app->db->getTransaction();
        try {
            $goods->setAttributes($row);
            $mine->setAttribute(['devStatus' => '开发中']);
            if(!$goods->save() || !$mine->save()) {
                throw new Exception('转至失败！', '400007');
            }
            $trans->commit();
        }
        catch (Exception $why) {
            $trans->rollBack();
            throw new Exception('转至失败！', '400007');
        }
    }

}