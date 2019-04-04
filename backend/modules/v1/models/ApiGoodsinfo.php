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
use backend\models\OaEbaySuffix;
use backend\models\OaWishSuffix;
use backend\models\OaJoomSuffix;
use yii\data\ActiveDataProvider;
use backend\modules\v1\utils\ProductCenterTools;
use yii\db\Query;
use yii\helpers\ArrayHelper;


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
        $type = $condition['type'];
        $query = OaGoodsinfo::find();
        if ($type === 'goods-info') {
            $query->where(['filterType' => self::GoodsInfo]);
            if (isset($condition['stockUp'])) $query->andFilterWhere(['stockUp' => $condition['stockUp']]);
            if (isset($condition['developer'])) $query->andFilterWhere(['like', 'developer', $condition['developer']]);
        } elseif ($type === 'picture-info') {
            $query = (new Query())->select("gi.*,g.vendor1,g.vendor2,g.vendor3,
             g.origin2,g.origin3,g.origin1,g.cate,g.subCate,g.introducer")
                ->from('proCenter.oa_goodsinfo gi')
                ->join('LEFT JOIN', 'proCenter.oa_goods g', 'g.nid=gi.goodsId');
            //$query->joinWith('oaGoods')->asArray();
            $query->where(['filterType' => self::PictureInfo]);
            if (isset($condition['stockUp'])) $query->andFilterWhere(['gi.stockUp' => $condition['stockUp']]);
            if (isset($condition['developer'])) $query->andFilterWhere(['like', 'gi.developer', $condition['developer']]);
        } elseif ($type === 'plat-info') {
            $query = (new Query())->select("gi.*,g.vendor1,g.vendor2,g.vendor3,
             g.origin2,g.origin3,g.origin1,g.cate,g.subCate,g.introducer")
                ->from('proCenter.oa_goodsinfo gi')
                ->join('LEFT JOIN', 'proCenter.oa_goods g', 'g.nid=gi.goodsId');
            //$query->joinWith('oaGoods')->asArray();
            $query->where(['filterType' => self::PlatInfo]);
            if (isset($condition['stockUp'])) $query->andFilterWhere(['gi.stockUp' => $condition['stockUp']]);
            if (isset($condition['developer'])) $query->andFilterWhere(['like', 'gi.developer', $condition['developer']]);
        } else {
            return [];
        }
        if (isset($condition['goodsCode'])) $query->andFilterWhere(['like', 'goodsCode', $condition['goodsCode']]);
        if (isset($condition['achieveStatus'])) $query->andFilterWhere(['like', 'achieveStatus', $condition['achieveStatus']]);
        if (isset($condition['goodsName'])) $query->andFilterWhere(['like', 'goodsName', $condition['goodsName']]);
        if (isset($condition['aliasCnName'])) $query->andFilterWhere(['like', 'aliasCnName', $condition['aliasCnName']]);
        if (isset($condition['aliasEnName'])) $query->andFilterWhere(['like', 'aliasEnName', $condition['aliasEnName']]);
        if (isset($condition['picStatus'])) $query->andFilterWhere(['like', 'picStatus', $condition['picStatus']]);
        if (isset($condition['possessman1'])) $query->andFilterWhere(['like', 'possessman1', $condition['possessman1']]);
        if (isset($condition['purchaser'])) $query->andFilterWhere(['like', 'purchaser', $condition['purchaser']]);
        if (isset($condition['introducer'])) $query->andFilterWhere(['like', 'introducer', $condition['introducer']]);
        if (isset($condition['mapPersons'])) $query->andFilterWhere(['like', 'mapPersons', $condition['mapPersons']]);
        if (isset($condition['dictionaryName'])) $query->andFilterWhere(['like', 'dictionaryName', $condition['dictionaryName']]);
        if (isset($condition['supplierName'])) $query->andFilterWhere(['like', 'supplierName', $condition['supplierName']]);
        if (isset($condition['cate'])) $query->andFilterWhere(['like', 'cate', $condition['cate']]);
        if (isset($condition['subCate'])) $query->andFilterWhere(['like', 'subCate', $condition['subCate']]);
        if (isset($condition['storeName'])) $query->andFilterWhere(['like', 'storeName', $condition['storeName']]);
        if (isset($condition['isLiquid'])) $query->andFilterWhere(['isLiquid' => $condition['isLiquid']]);
        if (isset($condition['isPowder'])) $query->andFilterWhere(['isPowder' => $condition['isPowder']]);
        if (isset($condition['isMagnetism'])) $query->andFilterWhere(['isMagnetism' => $condition['isMagnetism']]);
        if (isset($condition['isCharged'])) $query->andFilterWhere(['isCharged' => $condition['isCharged']]);
        if (isset($condition['isVar'])) $query->andFilterWhere(['isVar' => $condition['isVar']]);
        if (isset($condition['devDatetime']) && !empty($condition['devDatetime'])) $query->andFilterWhere(['between', "date_format(devDatetime,'%Y-%m-%d')", $condition['devDatetime'][0], $condition['devDatetime'][1]]);
        if (isset($condition['updateTime']) && !empty($condition['updateTime'])) $query->andFilterWhere(['between', "date_format(updateTime,'%Y-%m-%d')", $condition['updateTime'][0], $condition['updateTime'][1]]);
        $query->orderBy('id DESC');

        $provider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => $pageSize,
                'Page' => $currentPage - 1
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
        $id = isset($condition['id']) ? $condition['id'] : '';
        if (empty($id)) {
            return [];
        }
        return OaGoodsinfo::find()->with('oaGoods')->where(['id' => $id])->asArray()->one();
    }

    /**
     * @brief 删除属性信息条目的事务
     * @param $id
     * @return array
     */
    public static function deleteAttributeById($id)
    {
        $ret = OaGoodsinfo::deleteAll(['id' => $id]);
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
        $id = isset($condition['id']) ? $condition['id'] : '';
        if (empty($id)) {
            return [];
        }
        $goodsInfo = OaGoodsinfo::findOne(['id' => $id]);
        if ($goodsInfo === null) {
            return [];
        }
        $oaGoods = OaGoods::find()
            ->select('nid,cate,subCate,vendor1,vendor2,vendor3,origin1,origin2,origin3')
            ->where(['nid' => $goodsInfo->goodsId])->one();
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
        $skuInfo = OaGoodsSku::findAll(['infoId' => $id]);
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
        $id = isset($condition['id']) ? $condition['id'] : '';
        if (empty($id)) {
            return ['failure'];
        }
        $goodsInfo = OaGoodsinfo::findOne(['id' => $id]);
        if ($goodsInfo === null) {
            return ['failure'];
        }
        //属性信息标记完善，图片信息为待处理
        try {
            $goodsInfo->achieveStatus = '已完善';
            $goodsInfo->filterType = static::PictureInfo;
            if (empty($goodsInfo->picStatus)) {
                $goodsInfo->picStatus = '待处理';
            }
            if ($goodsInfo->update()) {
                return ['success'];
            }
        } catch (\Exception  $why) {
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
        $goodsInfo = OaGoodsinfo::findOne(['id' => $infoId]);
        if ($goodsInfo === null) {
            return ['failure'];
        }
        foreach ($skuInfo as $skuRow) {
            $skuId = isset($skuRow['id']) ? $skuRow['id'] : '';
            $skuModel = OaGoodsSku::findOne(['id' => $skuId]);
            if ($skuModel === null) {
                $skuModel = new OaGoodsSku();
                $skuRow['id'] = $skuModel->id;
                $skuRow['pid'] = $infoId;
            }
            $skuModel->setAttributes($skuRow);
            $skuModel->save();
        }

        $oaGoods = OaGoods::findOne(['nid' => $oaInfo['nid']]);
        if ($oaGoods === null) {
            $oaGoods = new OaGoods();
            $oaGoods->nid = $oaInfo['nid'];
        }
        $oaGoods->setAttributes($oaInfo);
        $goodsInfo->setAttributes($attributeInfo);
        if ($goodsInfo->save() && $oaGoods->save()) {
            return ['success'];
        }
        return ['failure'];
    }

    /**
     * @brief 删除多属性信息
     * @param $ids
     * @return array
     */
    public static function deleteAttributeVariantById($ids)
    {
        foreach ($ids as $id) {
            OaGoodsSku::deleteAll(['id' => $id]);
        }
        return ['success'];
    }

    ###########################  picture info ########################################

    /**
     * @brief 图片信息明细
     * @param $condition
     * @return mixed
     */
    public static function getPictureInfo($condition)
    {
        $id = isset($condition['id']) ? $condition['id'] : '';
        if (empty($id)) {
            return [];
        }
        return OaGoodsSku::find()
            ->select('id,sku,linkUrl,property1,property2,property3')
            ->where(['infoId' => $id])
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
            if ($skuEntry === null) {
                $msg = 'failure';
                break;
            }
            $skuEntry->setAttributes($picRow);
            if (!$skuEntry->save()) {
                $msg = 'failure';
                break;
            }
        }
        return [$msg];
    }

    public static function finishPicture($condition)
    {
        $id = isset($condition['id']) ? $condition['id'] : '';
        if (empty($id)) {
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
            $goods = OaWishGoods::findOne(['infoId' => $infoId]);
            $goodsSku = OaWishGoodsSku::findAll(['infoId' => $infoId]);

        } elseif ($plat === 'ebay') {
            $goods = OaEbayGoods::findOne(['infoId' => $infoId]);
            $goodsSku = OaEbayGoodsSku::findAll(['infoId' => $infoId]);
        } else {
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
    public static function saveEbayInfo($condition)
    {
        $goodsInfo = $condition['basicInfo'];
        $skuInfo = $condition['skuInfo'];
        $goods = OaEbayGoods::findOne(['nid' => $goodsInfo['nid']]);
        $goods->setAttributes($goodsInfo);
        foreach ($skuInfo as $row) {
            $sku = OaEbayGoodsSku::findOne(['id' => $row['id']]);
            $sku->setAttributes($row);
            if (!$sku->save()) {
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
    public static function saveWishInfo($condition)
    {
        $goodsInfo = $condition['basicInfo'];
        $skuInfo = $condition['skuInfo'];
        $goods = OaWishGoods::findOne(['id' => $goodsInfo['id']]);
        $goods->setAttributes($goodsInfo);
        foreach ($skuInfo as $row) {
            $sku = OaWishGoodsSku::findOne(['id' => $row['id']]);
            $sku->setAttributes($row);
            if (!$sku->save()) {
                return ['failure'];
            }
        }
        if (!$goods->save()) {
            return ['failure'];
        }
        return ['success'];
    }

    public static function finishPlat($condition)
    {
        $infoId = $condition['id'];
        $plat = $condition['plat'];
        $goodsInfo = OagoodsInfo::findOne(['id' => $infoId]);
        $oldPlat = $goodsInfo->completeStatus ?: '';
        $plat = array_merge($plat, explode(',', $oldPlat));
        $plat = array_filter($plat);
        $plat = array_unique($plat);
        asort($plat);
        $goodsInfo->completeStatus = implode(',', $plat);
        if (!$goodsInfo->save()) {
            return ['failure'];
        }
        return ['success'];
    }

    /**
     * @brief get all ebay accounts
     * @return array
     */
    public static function getEbayAccount()
    {
        $ret = OaEbaySuffix::find()->select('ebaySuffix,ebayName')->all();
        return ArrayHelper::map($ret, 'ebayName', 'ebaySuffix');
    }

    /**
     * @brief get all ebay stores
     * @return array
     */
    public static function getEbayStore()
    {
        $ret = OaEbaySuffix::find()->select('storeCountry')
            ->distinct()->all();
        return ArrayHelper::getColumn($ret, 'storeCountry');
    }

    /**
     * @brief prepare wish data to export
     * @param $id
     * @return array
     * @throws \Exception
     */
    public static function preExportWish($id)
    {
        $wishInfo = OaWishgoods::find()->where(['infoId' => $id])->asArray()->one();
        $wishAccounts = OaWishSuffix::find()->asArray()->all();
        $row = [
            'sku' => '', 'selleruserid' => '', 'name' => '', 'inventory' => '', 'price' => '', 'msrp' => '',
            'shipping' => '', 'shipping_time' => '', 'main_image' => '', 'extra_images' => '', 'variants' => '',
            'landing_page_url' => '', 'tags' => '', 'description' => '', 'brand' => '', 'upc' => '', 'local_price' => '',
            'local_shippingfee' => '', 'local_currency' => ''
        ];
        $ret = [];
        foreach ($wishAccounts as $account) {
            $row['sku'] = $wishInfo['sku'];
            $row['selleruserid'] = $account['shortName'];
            $row['name'] = $wishInfo['title'];
            $row['inventory'] = $wishInfo['inventory'];
            $row['price'] = $wishInfo['price'];
            $row['msrp'] = $wishInfo['msrp'];
            $row['shipping'] = $wishInfo['shipping'];
            $row['shipping_time'] = $wishInfo['shippingTime'];
            $row['main_image'] = $wishInfo['mainImage'];
            $row['extra_images'] = $wishInfo['extraImages'];
            $row['variants'] = $wishInfo['sku'];
            $row['landing_page_url'] = $wishInfo['mainImage'];
            $row['tags'] = $wishInfo['tags'];
            $row['description'] = $wishInfo['description'];
            $row['brand'] = '';
            $row['upc'] = '';
            $row['local_shippingfee'] = $wishInfo['shipping'] * 6.88;
            $row['local_currency'] = $wishInfo['price'] * 6.88;
            $ret[] = $row;
        }
        return $ret;
    }


    public static function preExportJoom($id, $account)
    {
        $joomSkuInfo = OaWishGoodsSku::find()->joinWith('oaWishGoods')->where(['oa_wishGoods.infoId' => $id])->asArray()->one();
        $joomInfo = $joomSkuInfo['oaWishGoods'];
        $joomAccounts = OaJoomSuffix::find()->where(['joomName' => $account])->asArray()->one();
        $row = [
            'Parent Unique ID' => '', '*Product Name' => '', 'Description' => '', '*Tags' => '', '*Unique ID' => '', 'Color' => '',
            'Size' => '', '*Quantity' => '', '*Price' => '', '*MSRP' => '', '*Shipping' => '', 'Shipping weight' => '',
            'Shipping Time(enter without " ", just the estimated days )' => '', '*Product Main Image URL' => '',
            'Variant Main Image URL' => '', 'Extra Image URL' => '', 'Extra Image URL 1' => '', 'Extra Image URL 2' => '',
            'Extra Image URL 3' => '', 'Extra Image URL 4' => '', 'Extra Image URL 5' => '', 'Extra Image URL 6' => '',
            'Extra Image URL 7' => '', 'Extra Image URL 8' => '', 'Extra Image URL 9' => '', 'Dangerous Kind' => '',
            'Declared Value' => '',
        ];
        $ret = [];
        $row['Parent Unique ID'] = $joomInfo['sku'] . $joomAccounts['skuCode'];
        $row['*Product Name'] = $joomInfo['title'];
        $row['Description'] = $joomInfo['description'];
        $row['*Tags'] = $joomInfo['tags'];
        $row['*Unique ID'] = $joomInfo['sku'];
        $row['Color'] = 'color';
        $row['Size'] = $joomInfo['title'];
        $row['*Quantity'] = $joomInfo['title'];
        $row['*Price'] = $joomInfo['title'];
        $row['*Shipping'] = $joomInfo['title'];
        $row['Shipping weight'] = $joomInfo['title'];
        $row['Shipping Time(enter without " ", just the estimated days )'] = $joomInfo['title'];
        $row['*Product Main Image URL'] = $joomInfo['title'];
        $row['Variant Main Image URL'] = $joomInfo['title'];
        $row['Extra Image URL'] = $joomInfo['title'];
        $row['Extra Image URL 1'] = $joomInfo['title'];
        $row['Extra Image URL 2'] = $joomInfo['title'];
        $row['Extra Image URL 3'] = $joomInfo['title'];
        $row['Extra Image URL 4'] = $joomInfo['title'];
        $row['Extra Image URL 5'] = $joomInfo['title'];
        $row['Extra Image URL 6'] = $joomInfo['title'];
        $row['Extra Image URL 7'] = $joomInfo['title'];
        $row['Extra Image URL 8'] = $joomInfo['title'];
        $row['Extra Image URL 9'] = $joomInfo['title'];
        $row['Extra Image URL 10'] = $joomInfo['title'];
        $row['Dangerous Kind'] = $joomInfo['title'];
        $row['Declared Value'] = $joomInfo['title'];
        $ret[] = $row;
        return $ret;
    }

    /**
     * @param $id
     * @param $account
     */
    public static function preExportEbay($id, $account)
    {
        $ebayInfo = OaEbayGoods::find()->joinWith('oaEbayGoodsSku')->where(['oa_ebayGoods.infoId' => $id])->asArray()->one();
        $ret = [];
        $row = [
            'Site' => '', 'Selleruserid' => '', 'ListingType' => '', 'Category1' => '', 'Category2' => '',
            'Condition' => '', 'ConditionBewrite' => '', 'Quantity' => '', 'LotSize' => '', 'Duration' => '',
            'ReservePrice' => '', 'BestOffer' => '', 'BestOfferAutoAcceptPrice' => '', 'BestOfferAutoRefusedPrice' => '',
            'AcceptPayment' => '', 'PayPalEmailAddress' => '', 'Location' => '', 'LocationCountry' => '',
            'ReturnsAccepted' => '', 'RefundOptions' => '', 'ReturnsWithin' => '', 'ReturnPolicyShippingCostPaidBy' => '',
            'ReturnPolicyDescription' => '', 'GalleryType' => '', 'Bold' => '', 'PrivateListing' => '',
            'HitCounter' => '', 'sku' => '', 'PictureURL' => '', 'Title' => '', 'SubTitle' => '', 'IbayCategory' => '',
            'StartPrice' => '', 'BuyItNowPrice' => '', 'UseMobile' => '', 'ShippingService1' => '',
            'ShippingServiceCost1' => '', 'ShippingServiceAdditionalCost1' => '', 'ShippingService2' => '',
            'ShippingServiceCost2' => '', 'ShippingServiceAdditionalCost2' => '', 'ShippingService3' => '',
            'ShippingServiceCost3' => '', 'ShippingServiceAdditionalCost3' => '', 'ShippingService4' => '',
            'ShippingServiceCost4' => '', 'ShippingServiceAdditionalCost4' => '', 'InternationalShippingService1' => '',
            'InternationalShippingServiceCost1' => '', 'InternationalShippingServiceAdditionalCost1' => '',
            'InternationalShipToLocation1' => '', 'InternationalShippingService2' => '', 'InternationalShippingServiceCost2' => '',
            'InternationalShippingServiceAdditionalCost2' => '', 'InternationalShipToLocation2' => '',
            'InternationalShippingService3' => '', 'InternationalShippingServiceCost3' => '',
            'InternationalShippingServiceAdditionalCost3' => '', 'InternationalShipToLocation3' => '',
            'InternationalShippingService4' => '', 'InternationalShippingServiceCost4' => '',
            'InternationalShippingServiceAdditionalCost4' => '', 'InternationalShipToLocation4' => '',
            'InternationalShippingService5' => '', 'InternationalShippingServiceCost5' => '',
            'InternationalShippingServiceAdditionalCost5' => '', 'InternationalShipToLocation5' => '',
            'DispatchTimeMax' => '', 'ExcludeShipToLocation' => '', 'StoreCategory1' => '',
            'StoreCategory2' => '', 'IbayTemplate' => '', 'IbayInformation' => '',
            'IbayComment' => '', 'Description' => '', 'Language' => '', 'IbayOnlineInventoryHold' => '',
            'IbayRelistSold' => '', 'IbayRelistUnsold' => '', 'IBayEffectType' => '', 'IbayEffectImg' => '',
            'IbayCrossSelling' => '', 'Variation' => '', 'outofstockcontrol' => '', 'EPID' => '',
            'ISBN' => '', 'UPC' => '', 'EAN' => '', 'SecondOffer' => '', 'Immediately' => '', 'Currency' => '',
            'LinkedPayPalAccount' => '', 'MBPVCount' => '', 'MBPVPeriod' => '', 'MUISICount' => '',
            'MUISIPeriod' => '', 'MaximumItemCount' => '', 'MinimumFeedbackScore' => '', 'Specifics1' => '',
            'Specifics2' => '', 'Specifics3' => '', 'Specifics4' => '', 'Specifics5' => '', 'Specifics6' => '',
            'Specifics7' => '', 'Specifics8' => '', 'Specifics9' => '', 'Specifics10' => '', 'Specifics11' => '',
            'Specifics12' => '', 'Specifics13' => '', 'Specifics14' => '', 'Specifics15' => '',
            'Specifics16' => '', 'Specifics17' => '', 'Specifics18' => '', 'Specifics19' => '',
            'Specifics20' => '', 'Specifics21' => '', 'Specifics22' => '', 'Specifics23' => '',
            'Specifics24' => '', 'Specifics25' => '', 'Specifics26' => '', 'Specifics27' => '',
            'Specifics28' => '', 'Specifics29' => '', 'Specifics30' => '',
        ];
        $row['Site'] = '';
        $row['Selleruserid'] = '';
        $row['ListingType'] = '';
        $row['Category1'] = '';
        $row['Category2'] = '';
        $row['Condition'] = '';
        $row['ConditionBewrite'] = '';
        $row['Quantity'] = '';
        $row['LotSize'] = '';
        $row['Duration'] = '';
        $row['ReservePrice'] = '';
        $row['BestOffer'] = '';
        $row['BestOfferAutoAcceptPrice'] = '';
        $row['BestOfferAutoRefusedPrice'] = '';
        $row['AcceptPayment'] = '';
        $row['PayPalEmailAddress'] = '';
        $row['Location'] = '';
        $row['LocationCountry'] = '';
        $row['ReturnsAccepted'] = '';
        $row['RefundOptions'] = '';
        $row['ReturnsWithin'] = '';
        $row['ReturnPolicyShippingCostPaidBy'] = '';
        $row['ReturnPolicyDescription'] = '';
        $row['GalleryType'] = '';
        $row['Bold'] = '';
        $row['PrivateListing'] = '';
        $row['HitCounter'] = '';
        $row['sku'] = '';
        $row['PictureURL'] = '';
        $row['Title'] = '';
        $row['SubTitle'] = '';
        $row['IbayCategory'] = '';
        $row['StartPrice'] = '';
        $row['BuyItNowPrice'] = '';
        $row['UseMobile'] = '';
        $row['ShippingService1'] = '';
        $row['ShippingServiceCost1'] = '';
        $row['ShippingServiceAdditionalCost1'] = '';
        $row['ShippingService2'] = '';
        $row['ShippingServiceCost2'] = '';
        $row['ShippingServiceAdditionalCost2'] = '';
        $row['ShippingService3'] = '';
        $row['ShippingServiceCost3'] = '';
        $row['ShippingServiceAdditionalCost3'] = '';
        $row['ShippingService4'] = '';
        $row['ShippingServiceCost4'] = '';
        $row['ShippingServiceAdditionalCost4'] = '';
        $row['InternationalShippingService1'] = '';
        $row['InternationalShippingServiceCost1'] = '';
        $row['InternationalShippingServiceAdditionalCost1'] = '';
        $row['InternationalShipToLocation1'] = '';
        $row['InternationalShippingService2'] = '';
        $row['InternationalShippingServiceCost2'] = '';
        $row['InternationalShippingServiceAdditionalCost2'] = '';
        $row['InternationalShipToLocation2'] = '';
        $row['InternationalShippingService3'] = '';
        $row['InternationalShippingServiceCost3'] = '';
        $row['InternationalShippingServiceAdditionalCost3'] = '';
        $row['InternationalShipToLocation3'] = '';
        $row['InternationalShippingService4'] = '';
        $row['InternationalShippingServiceCost4'] = '';
        $row['InternationalShippingServiceAdditionalCost4'] = '';
        $row['InternationalShipToLocation4'] = '';
        $row['InternationalShippingService5'] = '';
        $row['InternationalShippingServiceCost5'] = '';
        $row['InternationalShippingServiceAdditionalCost5'] = '';
        $row['InternationalShipToLocation5'] = '';
        $row['DispatchTimeMax'] = '';
        $row['ExcludeShipToLocation'] = '';
        $row['StoreCategory1'] = '';
        $row['StoreCategory2'] = '';
        $row['IbayTemplate'] = '';
        $row['IbayInformation'] = '';
        $row['IbayComment'] = '';
        $row['Description'] = '';
        $row['Language'] = '';
        $row['IbayOnlineInventoryHold'] = '';
        $row['IbayRelistSold'] = '';
        $row['IbayRelistUnsold'] = '';
        $row['IBayEffectType'] = '';
        $row['IbayEffectImg'] = '';
        $row['IbayCrossSelling'] = '';
        $row['Variation'] = '';
        $row['outofstockcontrol'] = '';
        $row['EPID'] = '';
        $row['ISBN'] = '';
        $row['UPC'] = '';
        $row['EAN'] = '';
        $row['SecondOffer'] = '';
        $row['Immediately'] = '';
        $row['Currency'] = '';
        $row['LinkedPayPalAccount'] = '';
        $row['MBPVCount'] = '';
        $row['MBPVPeriod'] = '';
        $row['MUISICount'] = '';
        $row['MUISIPeriod'] = '';
        $row['MaximumItemCount'] = '';
        $row['MinimumFeedbackScore'] = '';
        $row['Specifics1'] = '';
        $row['Specifics2'] = '';
        $row['Specifics3'] = '';
        $row['Specifics4'] = '';
        $row['Specifics5'] = '';
        $row['Specifics6'] = '';
        $row['Specifics7'] = '';
        $row['Specifics8'] = '';
        $row['Specifics9'] = '';
        $row['Specifics10'] = '';
        $row['Specifics11'] = '';
        $row['Specifics12'] = '';
        $row['Specifics13'] = '';
        $row['Specifics14'] = '';
        $row['Specifics15'] = '';
        $row['Specifics16'] = '';
        $row['Specifics17'] = '';
        $row['Specifics18'] = '';
        $row['Specifics19'] = '';
        $row['Specifics20'] = '';
        $row['Specifics21'] = '';
        $row['Specifics22'] = '';
        $row['Specifics23'] = '';
        $row['Specifics24'] = '';
        $row['Specifics25'] = '';
        $row['Specifics26'] = '';
        $row['Specifics27'] = '';
        $row['Specifics28'] = '';
        $row['Specifics29'] = '';
        $row['Specifics30'] = '';

        $ret[] = $row;
        return $ret;
    }
}