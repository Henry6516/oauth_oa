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
use backend\models\OaJoomToWish;
use backend\models\OaShippingService;
use backend\models\ShopElf\BGoods;
use yii\data\ActiveDataProvider;
use backend\modules\v1\utils\ProductCenterTools;
use yii\db\Query;
use yii\helpers\ArrayHelper;
use Yii;

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
    const UsdExchange = 6.88;
    const WishTitleLength = 110;
    const EbayTitleLength = 80;
    const JoomTitleLength = 100;

    /**
     * @brief 属性信息列表
     * @param $condition
     * @return ActiveDataProvider
     */
    public static function getOaGoodsInfoList($condition)
    {
        $pageSize = isset($condition['pageSize']) ? $condition['pageSize'] : 10;
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
        if (isset($condition['mid']) && $condition['mid'] == '是') $query->andFilterWhere(['>', "mid", 1]);
        if (isset($condition['mid']) && $condition['mid'] == '否') $query->andFilterWhere(["IFNULL(mid,'')" => '']);

        $query->orderBy('id DESC');

        $provider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => $pageSize,
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

    /** 删除属性信息条目的事务
     * @param $id
     * Date: 2019-04-08 16:20
     * Author: henry
     * @return array|bool
     */
    public static function deleteAttributeById($id)
    {
        $ret = OaGoodsinfo::deleteAll(['id' => $id]);
        if ($ret) {
            return true;
        }
        return [
            'code' => 400,
            'message' => 'failure'
        ];
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


    /** 属性信息标记已完善
     * @param $condition
     * Date: 2019-04-08 16:15
     * Author: henry
     * @return array|bool
     * @throws \Throwable
     */
    public static function finishAttribute($condition)
    {
        $id = isset($condition['id']) ? $condition['id'] : '';
        if (empty($id)) {
            return [
                'code' => 400,
                'message' => "Goods info id can't be empty！"
            ];
        }
        $goodsInfo = OaGoodsinfo::findOne(['id' => $id]);
        if ($goodsInfo === null) {
            return [
                'code' => 400,
                'message' => "Can't find goods info！"
            ];
        }
        //属性信息标记完善，图片信息为待处理
        try {
            $goodsInfo->achieveStatus = '已完善';
            $goodsInfo->filterType = static::PictureInfo;
            if (empty($goodsInfo->picStatus)) {
                $goodsInfo->picStatus = '待处理';
            }
            if ($goodsInfo->update()) {
                return true;
            }
        } catch (\Exception  $why) {
            return [
                'code' => 400,
                'message' => $why->getMessage()
            ];
        }
    }

    /**保存属性信息
     * @param $condition
     * Date: 2019-04-08 17:29
     * Author: henry
     * @return array|bool
     * @throws \yii\db\Exception
     */
    public static function saveAttribute($condition)
    {
        $attributeInfo = $condition['basicInfo']['goodsInfo'];
        $oaInfo = $condition['basicInfo']['oaGoods'];
        $skuInfo = $condition['skuInfo'];
        $infoId = $attributeInfo['id'];
        $goodsInfo = OaGoodsinfo::findOne(['id' => $infoId]);
        if ($goodsInfo === null) {
            return [
               'code' => 400,
               'message' => "Can't find goods info！"
            ];
        }
        $goodsInfo->isVar = count($skuInfo) > 1 ? '是' : '否';//判断是否多属性
        $transaction = Yii::$app->db->beginTransaction();
        try{
            foreach ($skuInfo as $skuRow) {
                $skuId = isset($skuRow['id']) ? $skuRow['id'] : '';
                $skuModel = OaGoodsSku::findOne(['id' => $skuId]);
                if ($skuModel === null) {
                    $skuModel = new OaGoodsSku();
                    $skuRow['id'] = $skuModel->id;
                    $skuRow['pid'] = $infoId;
                }
                $skuModel->setAttributes($skuRow);
                $a = $skuModel->save();
                if(!$a){
                    throw new \Exception("Goods sku is already exists！");
                }
            }

            $oaGoods = OaGoods::findOne(['nid' => $oaInfo['nid']]);
            if ($oaGoods === null) {
                $oaGoods = new OaGoods();
                $oaGoods->nid = $oaInfo['nid'];
            }
            $oaGoods->setAttributes($oaInfo);
            $goodsInfo->setAttributes($attributeInfo);
            if (!$goodsInfo->save() || !$oaGoods->save()) {
                throw new \Exception("Can't save goods info or goods！");
            }
            $transaction->commit();
            return true;
        }catch (\Exception $e){
            $transaction->rollBack();
            return [
                'code' => 400,
                'message' => $e->getMessage()
            ];
        }


    }

    /** 删除多属性信息
     * @param $ids
     * Date: 2019-04-08 16:12
     * Author: henry
     * @return bool
     */
    public static function deleteAttributeVariantById($ids)
    {
        foreach ($ids as $id) {
            OaGoodsSku::deleteAll(['id' => $id]);
        }
        return true;
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
                return [
                    'code' => 400,
                    'message' => 'failure'
                ];
            }
        }
        if (!$goods->save()) {
             return [
                'code' => 400,
                'message' => 'failure'
            ];
        }
        return true;
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
                return [
                    'code' => 400,
                    'message' => 'failure'
                ];
            }
        }
        if (!$goods->save()) {
            return [
                'code' => 400,
                'message' => 'failure'
            ];
        }
        return true;
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
            return [
                'code' => 400,
                'message' => 'failure'
            ];
        }
        return true;
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
     * @brief wish模板预处理
     * @param $id
     * @return array
     * @throws \Exception
     */
    public static function preExportWish($id)
    {
        $wishInfo = OaWishgoods::find()->where(['infoId' => $id])->asArray()->one();
        $wishSku = OaWishgoodsSku::find()->where(['infoId' => $id])->asArray()->all();
        $goodsInfo = OaGoodsinfo::find()->where(['id' => $id])->asArray()->one();
        $goods = OaGoods::find()->where(['nid' => $goodsInfo['goodsId']])->asArray()->one();
        $wishAccounts = OaWishSuffix::find()->where(['like','parentCategory',$goods['cate']])
            ->orWhere(['parentCategory' => ''])
            ->asArray()->all();
        $keyWords = static::preKeywords($wishInfo);
        $titlePool = [];
        $row = [
            'sku' => '', 'selleruserid' => '', 'name' => '', 'inventory' => '', 'price' => '', 'msrp' => '',
            'shipping' => '', 'shipping_time' => '', 'main_image' => '', 'extra_images' => '', 'variants' => '',
            'landing_page_url' => '', 'tags' => '', 'description' => '', 'brand' => '', 'upc' => '', 'local_price' => '',
            'local_shippingfee' => '', 'local_currency' => ''
        ];
        $ret = [];
        foreach ($wishAccounts as $account) {
            $title = '';
            $len = self::WishTitleLength;
            while (true) {
                $title = static::getTitleName($keyWords,$len);
                --$len;
                if (empty($title) || !in_array($title, $titlePool, false)) {
                    $titlePool[] = $title;
                    break;
                }
            }
            $variantInfo = static::getWishVariantInfo($goodsInfo['isVar'], $wishInfo, $wishSku, $account);
            $row['sku'] = $wishInfo['sku'] . $account['suffix'];
            $row['selleruserid'] = $account['ibaySuffix'];
            $row['name'] = $title;
            $row['inventory'] = $wishInfo['inventory'];
            $row['price'] = $variantInfo['price'];
            $row['msrp'] = $variantInfo['msrp'];
            $row['shipping'] = $variantInfo['shipping'];
            $row['shipping_time'] = '7-21';
            $row['main_image'] = static::getWishMainImage($goodsInfo['goodsCode'],$account['mainImg']);
            $row['extra_images'] = $wishInfo['extraImages'];
            $row['variants'] = $variantInfo['variant'];
            $row['landing_page_url'] = $wishInfo['mainImage'];
            $row['tags'] = $wishInfo['tags'];
            $row['description'] = $wishInfo['description'];
            $row['brand'] = '';
            $row['upc'] = '';
            $row['local_shippingfee'] = $variantInfo['local_shippingfee'];
            $row['local_currency'] = $variantInfo['local_currency'];
            $ret[] = $row;
        }
        return $ret;
    }


    /**
     * @brief 导出joom模板
     * @param $id
     * @param $accounts
     * @return array
     */
    public static function preExportJoom($id, $accounts)
    {
        $goodsInfo = OaGoodsinfo::findOne(['id' => $id]);
        $goods = BGoods::findOne(['GoodsCode' =>$goodsInfo['goodsCode']]);
        $joomSku = OaWishGoodsSku::find()
            ->where(['infoId' => $id])
            ->asArray()->all();
        $joomInfo = OaWishGoods::find()->where(['infoId' => $id])->asArray()->one();
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
        $keyWords = static::preKeywords($joomInfo);
        $priceInfo = static::getJoomPriceInfo($joomSku);
        foreach ($accounts as $account) {
            $joomAccounts = OaJoomSuffix::find()->where(['joomName' => $account])->asArray()->one();
            $imageInfo = static::getJoomImageInfo($joomInfo,$joomAccounts);
            foreach($joomSku as $sku) {
                $row = [];
                $row['Parent Unique ID'] = $joomInfo['sku'] . $joomAccounts['skuCode'];
                $row['*Product Name'] = static::getTitleName($keyWords, self::JoomTitleLength);
                $row['Description'] = $joomInfo['description'];
                $row['*Tags'] = $joomInfo['tags'];
                $row['*Unique ID'] = $sku['sku'] . $joomAccounts['skuCode'];
                $row['Color'] = $sku['color'];
                $row['Size'] = $sku['size'];
                $row['*Quantity'] = $sku['inventory'];
                $row['*Price'] = static::getJoomAdjust($sku['weight'], $priceInfo['price']);
                $row['*Shipping'] = $priceInfo['shipping'];
                $row['Shipping weight'] = (float)$sku['weight'];
                $row['Shipping Time(enter without " ", just the estimated days )'] = '15-45';
                $row['*Product Main Image URL'] = $imageInfo['mainImage'];
                $row['Variant Main Image URL'] = str_replace($sku['linkUrl'],'/10023/', $joomAccounts['imgCode']);
                $row['Extra Image URL'] = $imageInfo['extraImages'][0];
                $row['Extra Image URL 1'] = $imageInfo['extraImages'][1];
                $row['Extra Image URL 2'] = $imageInfo['extraImages'][2];
                $row['Extra Image URL 3'] = $imageInfo['extraImages'][3];
                $row['Extra Image URL 4'] = $imageInfo['extraImages'][4];
                $row['Extra Image URL 5'] = $imageInfo['extraImages'][5];
                $row['Extra Image URL 6'] = $imageInfo['extraImages'][6];
                $row['Extra Image URL 7'] = $imageInfo['extraImages'][7];
                $row['Extra Image URL 8'] = $imageInfo['extraImages'][8];
                $row['Extra Image URL 9'] = $imageInfo['extraImages'][9];
                $row['Extra Image URL 10'] = $imageInfo['extraImages'][10];
                $row['Dangerous Kind'] = static::getJoomDangerousKind($goodsInfo);
                $row['Declared Value'] = $goods['DeclaredValue'];
            }
            $ret[] = $row;
        }
        return $ret;
    }

    /**
     * @brief ebay模板预处理
     * @param $id
     * @param $account
     * @return array
     */
    public static function preExportEbay($id, $accounts)
    {
        $ebayInfo = OaEbayGoods::find()->joinWith('oaEbayGoodsSku')
            ->where(['oa_ebayGoods.infoId' => $id])->asArray()->one();
        $goodsInfo = OaGoodsinfo::findOne(['id' => $id]);
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
        $price = self::getEbayPrice($ebayInfo);
        foreach($accounts as $account)
        {
            $ebayAccount = OaEbaySuffix::find()->where(['ebaySuffix' => $account ])->asArray()->one();
            $payPal = self::getEbayPayPal($price, $ebayAccount);
            $row['Site'] = $ebayInfo['site'];
            $row['Selleruserid'] = $ebayAccount['ebayName'];
            $row['ListingType'] = 'FixedPriceItem';
            $row['Category1'] = $ebayInfo['listedCate'];
            $row['Category2'] = $ebayInfo['listedSubCate'];
            $row['Condition'] = '1000';
            $row['ConditionBewrite'] = '';
            $row['Quantity'] = $ebayInfo['quantity'] ?: 5;
            $row['LotSize'] = '';
            $row['Duration'] = 'GTC';
            $row['ReservePrice'] = '';
            $row['BestOffer'] = '';
            $row['BestOfferAutoAcceptPrice'] = '';
            $row['BestOfferAutoRefusedPrice'] = '';
            $row['AcceptPayment'] = 'PayPal';
            $row['PayPalEmailAddress'] = $payPal;
            $row['Location'] = $ebayInfo['location'];
            $row['LocationCountry'] = $ebayInfo['country'];
            $row['ReturnsAccepted'] = '1';
            $row['RefundOptions'] = 'MoneyBack';
            $row['ReturnsWithin'] = 'Days_30';
            $row['ReturnPolicyShippingCostPaidBy'] = 'Buyer';
            $row['ReturnPolicyDescription'] = 'We accept return or exchange item within 30 days from the day customer received the original item. If you have any problem please contact us first before leaving Neutral/Negative feedback! the negative feedback can\'\'t resolve the problem .but we can. ^_^ Hope you have a happy shopping experience in our store!';
            $row['GalleryType'] = 'Gallery';
            $row['Bold'] = '';
            $row['PrivateListing'] = '';
            $row['HitCounter'] = 'NoHitCounter';
            $row['sku'] = $ebayInfo['sku'] . $ebayAccount['nameCode'];
            $row['PictureURL'] = static::getEbayPicture($goodsInfo, $ebayInfo);
            $row['Title'] = $ebayInfo['title'];
            $row['SubTitle'] = $ebayInfo['subTitle'];
            $row['IbayCategory'] = '';
            $row['StartPrice'] = '';
            $row['BuyItNowPrice'] = $price;
            $row['UseMobile'] = '1';
            $row['ShippingService1'] = static::getShippingService($ebayInfo['inShippingMethod1']);
            $row['ShippingServiceCost1'] = $ebayInfo['inFirstCost1'];
            $row['ShippingServiceAdditionalCost1'] = $ebayInfo['inSuccessorCost1'];
            $row['ShippingService2'] = static::getShippingService($ebayInfo['inShippingMethod1']);
            $row['ShippingServiceCost2'] = $ebayInfo['inFirstCost2'];
            $row['ShippingServiceAdditionalCost2'] = $ebayInfo['inSuccessorCost2'];
            $row['ShippingService3'] = '';
            $row['ShippingServiceCost3'] = '';
            $row['ShippingServiceAdditionalCost3'] = '';
            $row['ShippingService4'] = '';
            $row['ShippingServiceCost4'] = '';
            $row['ShippingServiceAdditionalCost4'] = '';
            $row['InternationalShippingService1'] = static::getShippingService($ebayInfo['inShippingMethod1']);
            $row['InternationalShippingServiceCost1'] = $ebayInfo['OutFirstCost1'];
            $row['InternationalShippingServiceAdditionalCost1'] = $ebayInfo['OutSuccessorCost1'];
            $row['InternationalShipToLocation1'] = static::getShippingService($ebayInfo['inShippingMethod2']);
            $row['InternationalShippingService2'] = $ebayInfo['InternationalShippingService2'];
            $row['InternationalShippingServiceCost2'] = $ebayInfo['OutFirstCost2'];
            $row['InternationalShippingServiceAdditionalCost2'] = $ebayInfo['OutSuccessorCost2'];
            $row['InternationalShipToLocation2'] = static::getShippingService('@outShipping2');
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
            $row['DispatchTimeMax'] = $ebayInfo['prepareDay'];
            $row['ExcludeShipToLocation'] = static::getEbayExcludeLocation($ebayAccount);
            $row['StoreCategory1'] = '';
            $row['StoreCategory2'] = '';
            $row['IbayTemplate'] = $ebayAccount['ibayTemplate'];
            $row['IbayInformation'] = '1';
            $row['IbayComment'] = '';
            $row['Description'] = static::getEbayDescription($ebayInfo['description']);
            $row['Language'] = '';
            $row['IbayOnlineInventoryHold'] = '1';
            $row['IbayRelistSold'] = '';
            $row['IbayRelistUnsold'] = '';
            $row['IBayEffectType'] = '1';
            $row['IbayEffectImg'] = static::getEbayPicture($goodsInfo, $ebayInfo);
            $row['IbayCrossSelling'] = '';
            $row['Variation'] = '';
            $row['outofstockcontrol'] = '0';
            $row['EPID'] = 'Does not apply';
            $row['ISBN'] = 'Does not apply';
            $row['UPC'] = $ebayInfo['UPC'];
            $row['EAN'] = $ebayInfo['EAN'];
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

        }
        return $ret;
    }

    /**
     * @brief 获取wish账号主图链接
     * @param $goodsCode
     * @param $mainImage
     * @return string
     */
    private static function getWishMainImage($goodsCode, $mainImage)
    {
        $base = 'https://www.tupianku.com/view/full/10023/';
        return $base . $goodsCode . '-_' . $mainImage . '_.jpg';
    }


    /**
     * @brief 整合变体信息
     * @param $isVar
     * @param $wishInfo
     * @param $wishSku
     * @param $account
     * @return array
     */
    private static  function getWishVariantInfo($isVar, $wishInfo, $wishSku, $account)
    {
        try {
            $price = ArrayHelper::getColumn($wishSku, 'price');
            $shippingPrice = ArrayHelper::getColumn($wishSku, 'shipping');
            $msrp = ArrayHelper::getColumn($wishSku, 'msrp');
            $len = count($price);
            $totalPrice = [];
            for ($i=0; $i<$len; $i++) {
                $totalPrice[] = ceil($price[$i] + $shippingPrice[$i]);
            }

            //获取最大最小价格
            $maxPrice = max($totalPrice);
            $minPrice = min($totalPrice);
            $maxMsrp = max($msrp);

            //根据总价计算运费
            if ($minPrice <= 3) {
                $shipping = 1;
            }
            else {
                $shipping = ceil($minPrice * $account['rate']);
            }

            //打包变体
            $variation = [];
            foreach ($wishSku as $sku) {
                //价格判断
                $totalPrice = ceil($sku['price'] + $sku['shipping']);
                $value['shipping'] = $shipping;
                $value['price'] = $totalPrice - $shipping < 1 ? 1 : ceil($totalPrice - $shipping);
                $var['sku'] = $sku['sku'] . $account['suffix'];
                $var['color'] = $sku['color'];
                $var['size'] = $sku['size'];
                $var['inventory'] = $sku['inventory'];
                $var['price'] = $sku['price'];
                $var['shipping'] = $sku['shipping'];
                $var['msrp'] = $sku['msrp'];
                $var['shipping_time'] = $sku['shippingTime'];
                $var['main_image'] = $sku['linkUrl'];
                $var['localized_currency_code'] = 'CNY';
                $var['localized_price'] = (string)floor($sku['price'] * self::UsdExchange);
                $variation[] = $var;
            }
            $variant = json_encode($variation);
            $ret = [];
            if ($isVar === '是') {
                $ret['variant'] = $variant;
                $ret['shipping'] = $shipping;
                $ret['price'] = $maxPrice - $shipping > 0 ? ceil($maxPrice - $shipping) : 1;
                $ret['msrp'] = $maxMsrp;
                $ret['local_price'] = floor($wishInfo['price'] * self::UsdExchange);
                $ret['local_shippingfee'] = floor($wishInfo['shipping'] * self::UsdExchange);
                $ret['local_currency'] = 'CNY';
            }
            else {
                $ret['variant'] = '';
                $ret['price'] = $maxPrice - $shipping > 0 ? ceil($maxPrice - $shipping) : 1 ;
                $ret['shipping'] = $shipping;
                $ret['msrp'] = $maxMsrp;
                $ret['local_price'] = floor($ret['price'] * self::UsdExchange);
                $ret['local_shippingfee'] = floor($shipping * self::UsdExchange);
                $ret['local_currency'] = 'CNY';
            }
            return $ret;
        }
        catch (\Exception $why) {
            return ['variant' => '', 'price' => '', 'shipping' => '',
                'msrp' => '', 'local_price' => '', 'local_shippingfee' => '','local_currency' => ''];
    }

    }

    /**
     * @brief 生成随机顺序的标题
     * @param $keywords
     * @param $length
     * @return int|string
     */
    private static function getTitleName($keywords, $length)
    {
        $head = [$keywords['head']];
        $tail = [$keywords['tail']];
        $maxLength = $length;
        $need = array_filter($keywords['need']);
        $random = array_filter($keywords['random']);
        if (empty($random) || empty($need)) {
            return '';
        }
        //判断固定部分的长度
        $unchangedLen = \strlen(implode(' ', array_merge($head, $need, $tail)));

        //固定长度太长，随机去掉一个词
        if ($unchangedLen > $maxLength) {
            shuffle($need);
            $ret = array_merge($head,$need,$tail);
            while (\strlen(implode(' ',$ret)) > $maxLength) {
                array_pop($ret);
            }
            $real_len = implode(' ', $ret);
            return $real_len;
        }

        //可用长度
        $available_len = $maxLength - $unchangedLen - 1;
        shuffle($random); //摇匀词库
        $random_str1 = [array_shift($random)]; //从摇匀的词库里不放回抽一个
        $random_arr = \array_slice($random, 0, 4);//从剩余的词库里抽四个
        $real_len = \strlen(implode(' ', array_merge($random_str1, $random_arr)));
        for ($i = 0; $i < 4; $i++) {
            if ($real_len <= $available_len) {
                break;
            }
            array_shift($random_arr); //去掉一个随机词
            $real_len = \strlen(implode(' ', array_merge($random_str1, $random_arr)));
        }
        shuffle($need);
        return implode(' ', array_merge($head, $random_str1, $need, $random_arr, $tail));
    }

    /**
     * @brief 准备关键词
     * @param $info
     * @return mixed
     */
    private static function preKeywords($info)
    {
        $ret['head'] = $info['headKeywords'];
        $ret['tail'] = $info['tailKeywords'];
        $ret['need'] = json_decode($info['requiredKeywords']);
        $ret['random'] = json_decode($info['randomKeywords']);
        return $ret;
    }

    /**
     * @brief joom定价规则
     * @param $joomSku
     * @return array
     */
    private static function getJoomPriceInfo($joomSku)
    {
        $prices = ArrayHelper::getColumn($joomSku,'price');
        $shippingPrices = ArrayHelper::getColumn($joomSku,'shipping');
        $maxJoomPrice = max(ArrayHelper::getColumn($joomSku,'joomPrices'));
        $minJoomShipping = max(ArrayHelper::getColumn($joomSku,'joomShipping'));
        $maxMrsp = max(ArrayHelper::getColumn($joomSku, 'mrsp'));
        $len = count($joomSku);
        $i = 0;
        $totalPrice = [];
        while($i<$len) {
            $totalPrice[] = $prices[$i] + $shippingPrices[$i];
            $i++;
        }

        //定价规则
        $price = max($totalPrice) - 0.01;
        $msrp = max([$price * 5, $maxMrsp]);
        $joomPrice = $maxJoomPrice - $minJoomShipping;
        $price = $joomPrice > 0 ? $joomPrice : $price;
        $shipping = $minJoomShipping;

        return ['price' => $price, 'msrp' => $msrp, 'joomPrice' => $joomPrice, 'shipping' => $shipping];

    }

    /**
     * @brief 根据总量调整joom价格
     * @param $weight
     * @param $price
     * @return mixed
     */
    private static function getJoomAdjust($weight, $price)
    {
        $adjust = OaJoomToWish::find()->asArray()->all();
        foreach ($adjust as $ad) {
            if ($weight >= $ad['greaterEqual'] && $weight < $ad['less']) {
                $price += $ad['addedPrice'];
                break;
            }
        }
        return $price;
    }

    /**
     * @brief 设置joom图片信息
     * @param $joomInfo
     * @param $account
     * @return array
     */
    private static function getJoomImageInfo($joomInfo, $account)
    {
        $mainImage = substr($joomInfo['mainImage'],0, stripos('-', $joomInfo['mainImage']));
        $mainImage = str_replace($mainImage, '/10023/', $account['imgCode']);
        $extraImages = explode($joomInfo['extraImages'],'\n');
        array_filter($extraImages, function ($ele) {return strpos($ele,'-_00_') === false; });
        $extraImages = array_map(function ($ele) use ($account) {return str_replace($ele, '/10023/',$account['imgCode']);}, $extraImages);
        $countImages = count($extraImages);
        while($countImages <=11) {
            $extraImages[] = '';
            $countImages++;
        }
        shuffle($extraImages);
        return ['mainImage' => $mainImage, 'extraImages' => $extraImages];
    }

    /**
     * @brief 判断joom属于哪种危险品
     * @param $goodsInfo
     * @return string
     */
    private static function getJoomDangerousKind($goodsInfo)
    {
        if($goodsInfo['isLiquid']) {
            return 'liquid';
        }
        if($goodsInfo['isPowder']) {
            return 'powder';
        }
        if($goodsInfo['isMagnetism']) {
            return 'magnetizedItems';
        }
        if($goodsInfo['isCharged']) {
            return  'withBattery';
        }
        return 'noDangerous';
    }

    /**
     * @brief 获取ebay价格信息
     * @param $ebayInfo
     * @return int
     */
    private static function getEbayPrice($ebayInfo)
    {
        $currencyCodeMap = ['美国站' => 'USD', '英国站' => 'GBP', '澳洲站' => 'AUD' ];
        $skuPrice = ArrayHelper::getColumn($ebayInfo['oaEbayGoodsSku'], 'retailPrice');
        $maxPrice = max($skuPrice);
        $currencyCode = $currencyCodeMap[$ebayInfo['site']];
        $usdPrice = $maxPrice * ProductCenterTools::getExchangeRate($currencyCode) / ProductCenterTools::getExchangeRate('USD');
        return $usdPrice;
    }

    /**
     * @brief 获取payPal
     * @param $price
     * @param $ebayAccount
     * @return mixed
     */
    private static function getEbayPayPal($price, $ebayAccount)
    {
        if ($price >= 8) {
            return $ebayAccount['high'];
        }
        return $ebayAccount['low'];
    }

    /**
     * @brief 获取ebay的图片信息
     * @param $goodsInfo
     * @param $ebayInfo
     * @return string
     */
    private static function getEbayPicture($goodsInfo, $ebayInfo)
    {
        return 'https://www.tupianku.com/view/full/10023/' . $goodsInfo['goodsCode'] . '-_' .
            $ebayInfo['mainImage'] . '_.jpg' . '\n' . $ebayInfo['extraImage'];
    }
    /**
     * @brief 获取eBay描述
     * @param $description
     * @return string
     */
    private static function getEbayDescription($description)
    {
        return '<span style="font-family:Arial;font-size:14px;">' .
            str_replace($description,'\n','</br>') .'</span>';
    }

    /**
     * @brief ebay屏蔽发货国家
     * @param $ebayAccount
     * @return string
     */
    private static function getEbayExcludeLocation($ebayAccount)
    {
        $specialAccounts = ['03-aatq', '09-niceday'];
        if (in_array($ebayAccount, $specialAccounts, false)) {
            return 'US Protectorates,APO/FPO,PO Box,BO,HK,MO,TW,AS,CK,FJ,PF,GU,KI,MH,FM,NR,NC,NU,PW,PG,SB,TO,TV,VU,WF,WS,BM,GL,PM,BH,IQ,JO,KW,LB,OM,QA,SA,AE,YE,GG,IS,JE,LI,LU,ME,SM,SI,SJ,VA,AI,AG,AW,BS,BB,BZ,VG,KY,CR,DM,DO,SV,GD,GP,GT,HT,HN,JM,MQ,MS,AN,NI,PA,KN,LC,VC,TT,TC,VI,CN,AT,DE,CH,MT,PR,AL,ZM,BA,MU';
        }
        return 'US Protectorates,APO/FPO,PO Box,BO,HK,MO,TW,AS,CK,FJ,PF,GU,KI,MH,FM,NR,NC,NU,PW,PG,SB,TO,TV,VU,WF,WS,BM,GL,PM,BH,IQ,JO,KW,LB,OM,QA,SA,AE,YE,GG,IS,JE,LI,LU,ME,SM,SI,SJ,VA,AI,AG,AW,BS,BB,BZ,VG,KY,CR,DM,DO,SV,GD,GP,GT,HT,HN,JM,MQ,MS,AN,NI,PA,KN,LC,VC,TT,TC,VI,CN,MT,PR,AL,ZM,BA,MU';
    }

    /**
     * @brief 获取iBay对应的运输方式
     * @param $shippingMethod
     * @return string
     */
    private static function getShippingService($shippingMethod)
    {
        return OaShippingService::findOne(['servicesName' => $shippingMethod])->ibayShipping;

    }
}