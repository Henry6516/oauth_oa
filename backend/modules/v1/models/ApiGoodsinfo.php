<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019-02-18
 * Time: 9:26
 * Author: henry
 */
/**
 * @name ApiGoodsinfo.php
 * @desc PhpStorm.
 * @author: Create by henry
 * @since: Created on 2019-02-18 9:26
 */


namespace backend\modules\v1\models;


use backend\models\OaEbayGoods;
use backend\models\OaEbayGoodsSku;
use backend\models\OaGoods;
use backend\models\OaGoodsinfo;
use backend\models\OaGoodsSku;
use backend\models\OaWishGoods;
use backend\models\OaWishGoodsSku;
use yii\data\ActiveDataProvider;
use backend\modules\v1\utils\ProductCenterTools;


class ApiGoodsinfo
{
    /**
     * @param $condition
     * @return mixed
     * @throws \Exception
     */
    const GoodsInfo = 1;
    const PictureInfo = 2;
    const PlatInfo = 3;

    /**
     * @brief 属性信息列表
     * @param $condition
     * @return ActiveDataProvider
     */
    public static function getOaGoodsInfoList($condition)
    {
        $pageSize = isset($condition['pageSize']) ? $condition['pageSize'] : 10;
        $currentPage = isset($condition['currentPage']) ? $condition['currentPage'] : 1;
        $type= $condition['type'];
        $query = OaGoodsinfo::find();
        if ($type === 'goods-info') {
            $query->where(['filterType' => self::GoodsInfo]);
        }
        elseif ($type === 'picture-info')
        {
            $query->where(['filterType' => self::PictureInfo]);
        }
        elseif ($type === 'plat-info') {
            $query->where(['filterType' => self::PlatInfo]);
        }
        else {
            return [];
        }
        if(isset($condition['goodsCode'])) $query->andFilterWhere(['like', 'goodsCode', $condition['goodsCode']]);
        if(isset($condition['achieveStatus'])) $query->andFilterWhere(['like', 'achieveStatus', $condition['achieveStatus']]);
        if(isset($condition['goodsName'])) $query->andFilterWhere(['like', 'goodsName', $condition['goodsName']]);
        if(isset($condition['developer'])) $query->andFilterWhere(['like', 'developer', $condition['developer']]);
        if(isset($condition['aliasCnName'])) $query->andFilterWhere(['like', 'aliasCnName', $condition['aliasCnName']]);
        if(isset($condition['aliasEnName'])) $query->andFilterWhere(['like', 'aliasEnName', $condition['aliasEnName']]);
        if(isset($condition['stockUp'])) $query->andFilterWhere(['stockUp' => $condition['stockUp']]);
        if(isset($condition['isLiquid'])) $query->andFilterWhere(['isLiquid' => $condition['isLiquid']]);
        if(isset($condition['isPowder'])) $query->andFilterWhere(['isPowder' => $condition['isPowder']]);
        if(isset($condition['isMagnetism'])) $query->andFilterWhere(['isMagnetism' => $condition['isMagnetism']]);
        if(isset($condition['isCharged'])) $query->andFilterWhere(['isCharged' => $condition['isCharged']]);
        if(isset($condition['isVar'])) $query->andFilterWhere(['isVar' => $condition['isVar']]);
        if($condition['devDatetime'])$query->andFilterWhere(['between', "date_format(devDatetime,'%Y-%m-%d')", $condition['devDatetime'][0], $condition['devDatetime'][1]]);
        if($condition['updateTime'])$query->andFilterWhere(['between', "date_format(updateTime,'%Y-%m-%d')", $condition['updateTime'][0], $condition['updateTime'][1]]);
        $query->orderBy('id DESC');

        $provider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => $pageSize,
                'Page' => $currentPage -1
            ],
        ]);
        return $provider;
    }

    /**
     * @brief goodsInfo条目
     * @param $condition
     * @return mixed
     */
    public static function getAttributeById($condition)
    {
        $id = isset($condition['id'])? $condition['id']: '';
        if(empty($id)){
            return [];
        }
        return OaGoodsinfo::find()->with('oaGoods')->where(['id'=>$id])->asArray()->one();
    }

    /**
     * @brief 删除属性信息条目的事务
     * @param $id
     * @return array
     */
    public static function deleteAttributeById($id)
    {
        $ret = OaGoodsinfo::deleteAll(['id'=>$id]);
        if ($ret) {
            return ['success'];
        }
        return ['failure'];
    }

    /**
     * @brief 包含oa-goods,goods-info,goods-sku 数据的条目
     * @param $condition
     * @return array
     */
    public static function getAttributeInfo($condition)
    {
        $id = isset($condition['id'])? $condition['id']: '';
        if(empty($id)) {
            return [];
        }
        $goodsInfo = OaGoodsinfo::findOne(['id'=>$id]);
        if($goodsInfo === null) {
            return [];
        }
        $oaGoods = OaGoods::find()
            ->select('nid,cate,subCate,vendor1,vendor2,vendor3,origin1,origin2,origin3')
            ->where(['nid'=>$goodsInfo->goodsId])->one();
        if ($oaGoods === null) {
            $oaGoods = [
                'nid' => $goodsInfo->goodsId,
                'cate' => '',
                'subCate' => '',
                'vendor1' => '',
                'vendor2' => '',
                'vendor3' => '',
                'origin1' => '',
                'origin2' => '',
                'origin3' => '',
            ];
        }
        $skuInfo = OaGoodsSku::findAll(['infoId'=>$id]);
        return [
            'basicInfo' => [
                'goodsInfo' => $goodsInfo,
                'oaGoods' => $oaGoods,
            ],
            'skuInfo' => $skuInfo
        ];
    }


    /**
     * @brief 属性信息标记已完善
     * @param array
     * @return array
     * @throws \Throwable
     */
    public static function finishAttribute($condition)
    {
        $id = isset($condition['id'])? $condition['id']:'';
        if(empty($id)) {
            return ['failure'];
        }
        $goodsInfo = OaGoodsinfo::findOne(['id'=>$id]);
        if($goodsInfo === null) {
            return ['failure'];
        }
        //属性信息标记完善，图片信息为待处理
        try {
            $goodsInfo->achieveStatus = '已完善';
            if(empty($goodsInfo->picStatus)) {
                $goodsInfo->picStatus = '待处理';
            }
            if ($goodsInfo->update()) {
                return ['success'];
            }
        }
        catch (\Exception  $why) {
           return ['failure'];
        }
        return ['failure'];
    }

    /**
     * @brief 保存属性信息
     * @param $condition
     * @return array
     * @throws \Exception
     */
    public static function saveAttribute($condition)
    {
        $attributeInfo = $condition['basicInfo']['goodsInfo'];
        $oaInfo = $condition['basicInfo']['oaGoods'];
        $skuInfo = $condition['skuInfo'];
        $infoId = $attributeInfo['id'];
        $goodsInfo = OaGoodsinfo::findOne(['id'=>$infoId]);
        if($goodsInfo === null) {
            return ['failure'];
        }
        foreach ($skuInfo as $skuRow) {
            $skuId = isset($skuRow['id']) ? $skuRow['id']: '';
            $skuModel = OaGoodsSku::findOne(['id'=>$skuId]);
            if($skuModel === null) {
                $skuModel = new OaGoodsSku();
                $skuRow['id'] = $skuModel->id;
                $skuRow['pid'] = $infoId;
            }
            $skuModel->setAttributes($skuRow);
            $skuModel->save();
        }

        $oaGoods = OaGoods::findOne(['nid'=>$oaInfo['nid']]);
        if ($oaGoods === null) {
            $oaGoods =  new OaGoods();
            $oaGoods->nid = $oaInfo['nid'];
        }
        $oaGoods->setAttributes($oaInfo);
        $goodsInfo->setAttributes($attributeInfo);
        if( $goodsInfo->save() && $oaGoods->save()) {
                return ['success'];
        }
        return ['failure'];
    }

    ###########################  picture info ########################################

    /**
     * @brief 图片信息明细
     * @param $condition
     * @return mixed
     */
    public static function getPictureInfo($condition)
    {
        $id = isset($condition['id'])?$condition['id']:'';
        if(empty($id)) {
            return [];
        }
        return OaGoodsSku::find()
            ->select('id,sku,linkurl,property1,property2,property3')
            ->where(['infoId'=>$id])
            ->all();
    }

    /**
     * @brief 保存图片信息明细
     * @param $condition
     * @return array
     */
    public static function savePictureInfo($condition)
    {
        $pictureInfo = $condition;
        $msg = 'success';
        foreach ($pictureInfo as $picRow) {
            $id = $picRow['id'];
            $skuEntry = OaGoodsSku::findOne(['id' => $id]);
            if($skuEntry === null) {
                $msg = 'failure';
                break;
            }
            $skuEntry->setAttributes($picRow);
            if(!$skuEntry->save()) {
                $msg = 'failure';
                break;
            }
        }
        return [$msg];
    }

    public static function finishPicture($condition)
    {
        $id = isset($condition['id'])?$condition['id']:'';
        if(empty($id)) {
            return [];
        }
        return ProductCenterTools::finishPicture($id);
    }

###########################  plat info ########################################


    public static function getPlatInfoById($condition)
    {
        $plat = $condition['plat'];
        $infoId = $condition['id'];
        if ($plat === 'wish') {
            $goods = OaWishGoods::findOne(['infoId'=>$infoId]);
            $goodsSku = OaWishGoodsSku::findAll(['infoId'=>$infoId]);

        }
        elseif($plat === 'ebay') {
            $goods = OaEbayGoods::findOne(['infoId'=>$infoId]);
            $goodsSku = OaEbayGoodsSku::findAll(['infoId'=>$infoId]);
        }
        else {
            $goods = [];
            $goodsSku = [];
        }

        return [
            'basicInfo' => $goods,
            'skuInfo' => $goodsSku
        ];
    }

    /**
     * @brief save ebay info
     * @param $condition
     * @return array
     */
    public  static function saveEbayInfo($condition)
    {
        $goodsInfo = $condition['basicInfo'];
        $skuInfo = $condition['skuInfo'];
        $goods = OaEbayGoods::findOne(['nid'=>$goodsInfo['nid']]);
        $goods->setAttributes($goodsInfo);
        foreach ($skuInfo as $row) {
            $sku = OaEbayGoodsSku::findOne(['id'=>$row['id']]);
            $sku->setAttributes($row);
            if(!$sku->save()) {
                return ['failure'];
            }
        }
        if (!$goods->save()) {
           return ['failure'];
        }
        return ['success'];
    }

    /**
     * @brief 保存wish模板
     * @param $condition
     * @return array
     */
    public  static function saveWishInfo($condition)
    {
        $goodsInfo = $condition['basicInfo'];
        $skuInfo = $condition['skuInfo'];
        $goods = OaWishGoods::findOne(['id'=>$goodsInfo['id']]);
        $goods->setAttributes($goodsInfo);
        foreach ($skuInfo as $row) {
            $sku = OaWishGoodsSku::findOne(['id'=>$row['id']]);
            $sku->setAttributes($row);
            if(!$sku->save()) {
                return ['failure'];
            }
        }
        if (!$goods->save()) {
            return ['failure'];
        }
        return ['success'];
    }

    public static function finishPlat($condition) {
        $infoId = $condition['id'];
        $plat = $condition['plat'];
        $goodsInfo = OagoodsInfo::findOne(['id'=>$infoId]);
        $oldPlat = $goodsInfo->completeStatus?:'';
        $plat = array_merge($plat,explode(',',$oldPlat));
        $plat = array_filter($plat);
        $plat = array_unique($plat);
        asort($plat);
        $goodsInfo->completeStatus = implode(',', $plat);
        if(!$goodsInfo->save()) {
            return ['failure'];
        }
        return ['success'];
    }
}