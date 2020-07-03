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
use backend\models\OaGoods1688;
use backend\models\OaGoodsinfo;
use backend\models\OaGoodsSku;
use backend\models\OaGoodsSku1688;
use backend\models\OaMyMallSuffix;
use backend\models\OaPaypal;
use backend\models\OaSmtGoods;
use backend\models\OaSmtGoodsSku;
use backend\models\OaWishGoods;
use backend\models\OaWishGoodsSku;
use backend\models\OaEbaySuffix;
use backend\models\OaWishSuffix;
use backend\models\OaJoomSuffix;
use backend\models\OaShopify;
use backend\models\OaVovaSuffix;
use backend\models\OaJoomToWish;
use backend\models\OaSiteCountry;
use backend\models\OaShippingService;
use backend\modules\v1\services\Logger;
use backend\modules\v1\utils\Helper;
use mdm\admin\models\Store;
use yii\data\ActiveDataProvider;
use backend\modules\v1\utils\ProductCenterTools;
use yii\db\Exception;
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
    private static $goodsInfo = ['待处理', '已完善'];
    private static $pictureInfo = ['待处理'];
    const PlatInfo = '已完善';
//    const UsdExchange = 1;
    const UsdExchange = 6.88;
    const WishTitleLength = 110;
    const myMallTitleLength = 110;
    const EbayTitleLength = 80;
    const JoomTitleLength = 100;
    const smtTitleLength = 128;

    /**
     * @brief 属性信息列表
     * @param $condition
     * @return ActiveDataProvider
     * @throws \Exception
     */
    public static function getOaGoodsInfoList($condition)
    {
        //todo 权限需要重写
        $pageSize = isset($condition['pageSize']) ? $condition['pageSize'] : 10;
        $type = $condition['type'];
        $user = Yii::$app->user->identity->username;
        $userList = ApiUser::getUserList($user);
        $userRole = implode('', ApiUser::getUserRole($user));
        if ($type === 'goods-info') {
            $query = (new Query())->select('gi.*,g.vendor1,g.vendor2,g.vendor3,
             g.origin2,g.origin3,g.origin1,g.cate,g.subCate,g.introducer')
                ->from('proCenter.oa_goodsinfo gi')
                ->join('LEFT JOIN', 'proCenter.oa_goods g', 'g.nid=gi.goodsId');
            if (isset($condition['achieveStatus']) && $condition['achieveStatus'] ||
                isset($condition['goodsCode']) && $condition['goodsCode']
            ) {
                $query->andFilterWhere(['like', 'achieveStatus', $condition['achieveStatus']]);
            } else {
                $query->where(['in', 'achieveStatus', ['待处理']]);
            }
            //print_r($userRole);exit;

            if (strpos($userRole, '开发') !== false) {
                $query->andWhere(['or', ['in', 'gi.developer', $userList], ['in', 'introducer', $userList]]);
            } else if (strpos($userRole, '美工') !== false) {
                $query->andWhere(['or', ['in', 'possessMan1', $userList], ['in', 'introducer', $userList]]);
            } else if (strpos($userRole, '销售') !== false) {
                $query->andFilterWhere(['in', 'introducer', $userList]);
            }


            if (isset($condition['stockUp'])) $query->andFilterWhere(['gi.stockUp' => $condition['stockUp']]);
            if (isset($condition['developer'])) $query->andFilterWhere(['like', 'gi.developer', $condition['developer']]);


        } elseif ($type === 'picture-info') {
            $query = (new Query())->select('gi.*,g.vendor1,g.vendor2,g.vendor3,
             g.origin2,g.origin3,g.origin1,g.cate,g.subCate,g.introducer')
                ->from('proCenter.oa_goodsinfo gi')
                ->join('LEFT JOIN', 'proCenter.oa_goods g', 'g.nid=gi.goodsId');

            if (strpos($userRole, '开发') !== false) {
                $query->andWhere(['or', ['in', 'gi.developer', $userList], ['in', 'introducer', $userList]]);
            } else if (strpos($userRole, '美工') !== false) {
                $userList = array_merge($userList, array_map(function ($user) {
                    return $user . '-2';
                }, $userList));
                $query->andWhere(['or', ['in', 'possessMan1', $userList], ['in', 'introducer', $userList]]);
            } else if (strpos($userRole, '销售') !== false) {
                $query->andFilterWhere(['in', 'introducer', $userList]);
            }


            if (isset($condition['picStatus']) && $condition['picStatus'] ||
                isset($condition['goodsCode']) && $condition['goodsCode']
            ) {
                $query->andFilterWhere(['like', 'picStatus', $condition['picStatus']]);
            } else {
                $query->andFilterWhere(['in', "IFNULL(picStatus,'')", static::$pictureInfo]);
            }
            if (isset($condition['stockUp'])) $query->andFilterWhere(['gi.stockUp' => $condition['stockUp']]);
            if (isset($condition['developer'])) $query->andFilterWhere(['like', 'gi.developer', $condition['developer']]);


        } elseif ($type === 'plat-info') {  //平台信息

            $query = (new Query())->select('gi.*,g.vendor1,g.vendor2,g.vendor3,
             g.origin2,g.origin3,g.origin1,g.cate,g.subCate,g.introducer')
                ->from('proCenter.oa_goodsinfo gi')
                ->join('LEFT JOIN', 'proCenter.oa_goods g', 'g.nid=gi.goodsId');
            $query->where(['picStatus' => self::PlatInfo]);

            //美工,开发，采购看自己,
            if (strpos($userRole, '销售') === false) {
                if (strpos($userRole, '采购') !== false) {
                    $query->andWhere(['or', ['in', 'g.introducer', $userList], ['in', 'gi.purchaser', $userList]]);
                } else {
                    $query->andWhere(['or', ['in', 'gi.developer', $userList], ['in', 'possessMan1', $userList]]);
                }
            }
            //print_r($userRole);exit;
            if (isset($condition['codeStr']) && $condition['codeStr']) {
                $codeArr = explode(',', $condition['codeStr']);
                $map = ['or'];
                foreach ($codeArr as $v) {
                    $map[] = ['like', 'goodsCode', $v];
                }
                $query->andFilterWhere($map);
            }

            if (isset($condition['stockUp'])) $query->andFilterWhere(['gi.stockUp' => $condition['stockUp']]);
            if (isset($condition['developer'])) $query->andFilterWhere(['like', 'gi.developer', $condition['developer']]);
        } else {
            return [];
        }
        if (isset($condition['goodsCode'])) $query->andFilterWhere(['like', 'goodsCode', $condition['goodsCode']]);
        if (isset($condition['goodsName'])) $query->andFilterWhere(['like', 'goodsName', $condition['goodsName']]);
        if (isset($condition['aliasCnName'])) $query->andFilterWhere(['like', 'aliasCnName', $condition['aliasCnName']]);
        if (isset($condition['aliasEnName'])) $query->andFilterWhere(['like', 'aliasEnName', $condition['aliasEnName']]);
        if (isset($condition['picStatus'])) $query->andFilterWhere(['like', 'picStatus', $condition['picStatus']]);
        $query = static::completedStatusFilter($query, $condition);
        $query = static::forbidPlatFilter($query, $condition);
        if (isset($condition['goodsStatus'])) $query->andFilterWhere(['like', 'goodsStatus', $condition['goodsStatus']]);
        if (isset($condition['possessMan1'])) $query->andFilterWhere(['like', 'possessMan1', $condition['possessMan1']]);
        if (isset($condition['purchaser'])) $query->andFilterWhere(['like', 'purchaser', $condition['purchaser']]);
        if (isset($condition['introducer'])) $query->andFilterWhere(['like', 'introducer', $condition['introducer']]);
        if (isset($condition['mapPersons'])) $query->andFilterWhere(['like', 'mapPersons', $condition['mapPersons']]);
        if (isset($condition['supplierName'])) $query->andFilterWhere(['like', 'supplierName', $condition['supplierName']]);
        if (isset($condition['cate'])) $query->andFilterWhere(['like', 'cate', $condition['cate']]);
        if (isset($condition['subCate'])) $query->andFilterWhere(['like', 'subCate', $condition['subCate']]);
        if (isset($condition['storeName'])) $query->andFilterWhere(['like', 'storeName', $condition['storeName']]);
        if (isset($condition['vendor1'])) $query->andFilterWhere(['like', 'vendor1', $condition['vendor1']]);
        if (isset($condition['vendor2'])) $query->andFilterWhere(['like', 'vendor2', $condition['vendor2']]);
        if (isset($condition['vendor3'])) $query->andFilterWhere(['like', 'vendor3', $condition['vendor3']]);
        if (isset($condition['origin1'])) $query->andFilterWhere(['like', 'origin1', $condition['origin1']]);
        if (isset($condition['origin2'])) $query->andFilterWhere(['like', 'origin2', $condition['origin2']]);
        if (isset($condition['origin3'])) $query->andFilterWhere(['like', 'origin3', $condition['origin3']]);
        if (isset($condition['wishPublish'])) $query->andFilterWhere(['wishPublish' => $condition['wishPublish']]);
        if (isset($condition['isLiquid'])) $query->andFilterWhere(['isLiquid' => $condition['isLiquid']]);
        if (isset($condition['isPowder'])) $query->andFilterWhere(['isPowder' => $condition['isPowder']]);
        if (isset($condition['isMagnetism'])) $query->andFilterWhere(['isMagnetism' => $condition['isMagnetism']]);
        if (isset($condition['isCharged'])) $query->andFilterWhere(['isCharged' => $condition['isCharged']]);
        if (isset($condition['isVar'])) $query->andFilterWhere(['isVar' => $condition['isVar']]);
        if (isset($condition['stockDays'])) $query->andFilterWhere(['stockDays' => $condition['stockDays']]);
        if (isset($condition['devDatetime']) && !empty($condition['devDatetime'])) $query->andFilterWhere(['between', "date_format(devDatetime,'%Y-%m-%d')", $condition['devDatetime'][0], $condition['devDatetime'][1]]);
        if (isset($condition['updateTime']) && !empty($condition['updateTime'])) $query->andFilterWhere(['between', "date_format(updateTime,'%Y-%m-%d')", $condition['updateTime'][0], $condition['updateTime'][1]]);
        if (isset($condition['mid']) && $condition['mid'] === '是') $query->andFilterWhere(['>', "ifnull(mid,1)", 1]);
        if (isset($condition['mid']) && $condition['mid'] === '否') $query->andFilterWhere(["IFNULL(mid,0)" => 0]);
        $query->orderBy('devDateTime DESC,id DESC');
        //print_r($query->createCommand()->getRawSql());exit;
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
            ->select('nid,cate,subCate,salePrice,vendor1,vendor2,vendor3,origin1,origin2,origin3')
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
        $skuInfo = (new Query())->select("gs.*, ss.offerId, ss.specId")
            ->from('proCenter.oa_goodssku gs')
            ->leftJoin('proCenter.oa_goodsSku1688 ss', 'ss.goodsSkuId=gs.id')->where(['infoId' => $id])->all();
        foreach ($skuInfo as &$v){
            $goods =  OaGoods1688::find()->select('offerId,specId,style')
                ->where(['infoId' => $id, 'offerId' => $v['offerId']])->distinct()->asArray()->all();
            $v['selectData'] = array_merge([["offerId" => '无', "specId" => '无', 'style' => '无']], $goods);
        }
        return [
            'basicInfo' => [
                'goodsInfo' => $goodsInfo,
                'oaGoods' => $oaGoods,
            ],
            'skuInfo' => $skuInfo,
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
        $ids = isset($condition['id']) ? $condition['id'] : '';
        if (empty($ids)) {
            return [
                'code' => 400,
                'message' => "Goods info id can't be empty！"
            ];
        }
        //属性信息标记完善，图片信息为待处理
        $transaction = Yii::$app->db->beginTransaction();
        try {
            foreach ($ids as $id) {
                $goodsInfo = OaGoodsinfo::findOne(['id' => $id]);
                if ($goodsInfo === null) {
                    throw new \Exception("Can't find goods info！");
                }
                // 同步信息 到wishGoods，wishGoodsSku，ebayGoods，ebayGoodsSku
                ProductCenterTools::saveAttributeToPlat($id);
                //判断是否需要美工做图
                $skuList = OaGoodsSku::findAll(['infoId' => $id]);
                $skuArrNew = ArrayHelper::getColumn($skuList, 'linkUrl');
                $flag = 0;//不需要重新做图
                foreach ($skuArrNew as $v) {
                    if (!$v) {
                        $flag = 1;//需要重新做图
                        break;
                    }
                }
                $goodsInfo->achieveStatus = '已完善';
                if (empty($goodsInfo->picStatus) || $flag == 1) {
                    $goodsInfo->picStatus = '待处理';
                }
                if (!$goodsInfo->save()) {
                    throw new \Exception('Save sku failed');
                }
            }
            $transaction->commit();
            return true;
        } catch (\Exception  $why) {
            $transaction->rollBack();
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
        // 处理特殊商品信息
        $map = ['带磁商品' => 'isMagnetism', '带电商品' => 'isCharged', '液体商品' => 'isLiquid', '粉末商品' => 'isPowder'];
        if (array_key_exists($attributeInfo['attributeName'], $map)) {
            $attributeInfo[$map[$attributeInfo['attributeName']]] = '是';
        }
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
        $transaction = Yii::$app->db->beginTransaction();
        try {
            foreach ($skuInfo as $skuRow) {
                //保存SKU信息
                $skuId = isset($skuRow['id']) ? $skuRow['id'] : '';
                $skuModel = OaGoodsSku::findOne(['id' => $skuId]);
                if ($skuModel === null) {
                    $skuModel = new OaGoodsSku();
                    $skuRow['id'] = $skuModel->id;
                    $skuRow['pid'] = $infoId;
                }
                $skuRow['sku'] = trim($skuRow['sku']);//移除SKU中空格
                $skuModel->setAttributes($skuRow);
                $a = $skuModel->save();
                if (!$a) {
                    throw new \Exception("Goods sku is already exists！");
                }
                //保存SKU关联1688信息
                $specId = isset($skuRow['specId']) ? $skuRow['specId'] : '';
                $offerId = isset($skuRow['offerId']) ? $skuRow['offerId'] : '';
                if($offerId){
                    $count = OaGoods1688::find()->andFilterWhere(['infoId' => $infoId, 'offerId' => $offerId])->count();
                    if($specId || $count == 1){
                        $goodsSku1688 = OaGoodsSku1688::findOne(['goodsSkuId' => $skuModel->id]);
                        if(!$goodsSku1688){
                            $goodsSku1688 = new OaGoodsSku1688();
                            $goodsSku1688->goodsSkuId = $skuModel->id;
                        }
                        if($specId != '无'){
                            $goods1688 = OaGoods1688::find()->andFilterWhere(['infoId' => $infoId, 'offerId' => $offerId, 'specId' => $specId])->one();
                            $goodsSku1688->supplierLoginId = $goods1688->supplierLoginId;
                            $goodsSku1688->companyName = $goods1688->companyName;
                        }else{
                            $goodsSku1688->supplierLoginId = '';
                            $goodsSku1688->companyName = '';
                        }
                        $goodsSku1688->offerId = $offerId;
                        $goodsSku1688->specId = $specId;
                        $goodsSku1688->isDefault = 1;
                        $ss = $goodsSku1688->save();
                        if (!$ss) {
                            throw new \Exception("failed save 1688 goods sku info！");
                        }
                    }
                }
            }
            $oaGoods = OaGoods::findOne(['nid' => $oaInfo['nid']]);
            if ($oaGoods === null) {
                $oaGoods = new OaGoods();
                $oaGoods->nid = $oaInfo['nid'];
            }
            $oaGoods->setAttributes($oaInfo);
            $attributeInfo['goodsCode'] = trim($attributeInfo['goodsCode']);//移除goodsCode中空格
            $goodsInfo->setAttributes($attributeInfo);
            $goodsInfo->isVar = count($skuInfo) > 1 ? '是' : '否';//判断是否多属性
            if (!$goodsInfo->save() || !$oaGoods->save()) {
                throw new \Exception("Can't save goods info or goods！");
            }
            $transaction->commit();
            return true;
        } catch (\Exception $e) {
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

    /**
     * @brief 生成采购单
     * @param $condition
     * @return array
     * @throws \Exception
     */
    public static function makePurchasingOrder($condition)
    {

        $id = $condition['id'];
        $goodsInfo = OaGoodsinfo::findOne(['id' => $id]);
        $goodsCode = $goodsInfo->goodsCode;
        return ProductCenterTools::purchasingOrder($goodsCode);
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

    /** 保存图片信息明细
     * @param $condition
     * Date: 2019-04-28 10:02
     * Author: henry
     * @return bool
     * @throws \Exception
     */
    public static function savePictureInfo($condition)
    {
        $pictureInfo = isset($condition['pictureInfo']) ? $condition['pictureInfo'] : [];
        foreach ($pictureInfo as $picRow) {
            $id = $picRow['id'];
            $skuEntry = OaGoodsSku::findOne(['id' => $id]);
            if ($skuEntry === null) {
                throw new \Exception("Can't get goods sku info");
            }
            $skuEntry->setAttributes($picRow);
            if (!$skuEntry->save()) {
                throw new \Exception("Save goods sku info failed");
            }
        }
        return true;
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
            if ($goods === null & $goodsSku === null) {
                $ret = [
                    'basicInfo' => [
                        'id' => '', 'sku' => '', 'title' => '', 'description' => '', 'inventory' => '', 'price' => '', 'msrp' => '',
                        'shipping' => '', 'shippingTime' => '', 'tags' => '', 'mainImage' => '', 'goodsId' => '', 'infoId' => '',
                        'extraImages' => '', 'headKeywords' => '', 'requiredKeywords' => '', 'randomKeywords' => '', 'tailKeywords' => '',
                        'wishTags' => '', 'stockUp' => ''
                    ],
                    'skuInfo' => [[
                        'id' => '', 'infoId' => '', 'sid' => '', 'sku' => '', 'color' => '', 'size' => '', 'inventory' => '',
                        'price' => '', 'shipping' => '', 'msrp' => '', 'shippingTime' => '', 'linkUrl' => '', 'goodsSkuId' => '',
                        'weight' => '', 'joomPrice' => '', 'joomShipping' => ''
                    ]]];
                return $ret;
            }

        } elseif ($plat === 'ebay') {
            $goods = OaEbayGoods::findOne(['infoId' => $infoId]);
            $goodsSku = OaEbayGoodsSku::findAll(['infoId' => $infoId]);
            if ($goods === null && $goodsSku === null) {
                $ret = [
                    'basicInfo' => [
                        'nid' => '', 'goodsId' => '', 'location' => '', 'country' => '', 'postCode' => '', 'prepareDay' => '',
                        'site' => '', 'listedCate' => '', 'listedSubcate' => '', 'title' => '', 'subTitle' => '', 'description' => '',
                        'quantity' => '', 'nowPrice' => '', 'UPC' => '', 'EAN' => '', 'brand' => '', 'MPN' => '', 'color' => '', 'type' => '',
                        'material' => '', 'intendedUse' => '', 'unit' => '', 'bundleListing' => '', 'shape' => '', 'features' => '',
                        'regionManufacture' => '', 'reserveField' => '', 'inShippingMethod1' => '', 'inFirstCost1' => '', 'inSuccessorCost1' => '',
                        'inShippingMethod2' => '', 'inFirstCost2' => '', 'inSuccessorCost2' => '', 'outShippingMethod1' => '',
                        'outFirstCost1' => '', 'outSuccessorCost1' => '', 'outShipToCountry1' => '', 'outShippingMethod2' => '',
                        'outFirstCost2' => '', 'outSuccessorCost2' => '', 'outShipToCountry2' => '', 'mainPage' => '', 'extraPage' => '',
                        'sku' => '', 'infoId' => '', 'specifics' => '{"specifics":[{"Brand":"Unbranded"}]}', 'iBayTemplate' => '', 'headKeywords' => '',
                        'requiredKeywords' => '["","","","","",""]',
                        'randomKeywords' => '["","","","","","","","","",""]',
                        'tailKeywords' => '', 'stockUp' => '否'
                    ],
                    'skuInfo' => [[
                        'id' => '', 'itemId' => '', 'sid' => '', 'infoId' => '', 'sku' => '', 'quantity' => '', 'retailPrice' => '',
                        'imageUrl' => '',
                        'property' => [
                            'columns' => [[
                                'Color' => ''
                            ], [
                                'Size' => ''
                            ], [
                                '款式3' => ''
                            ], [
                                'UPC' => 'Does not apply'
                            ]],
                            'pictureKey' => 'color'
                        ]
                    ]
                    ]];
                return $ret;
            }
            foreach ($goodsSku as $sku) {
                $sku['property'] = json_decode($sku['property']);
            }
        } elseif ($plat === 'aliexpress') {
            $goods = OaSmtGoods::findOne(['infoId' => $infoId]);
            $goodsSku = OaSmtGoodsSku::findAll(['infoId' => $infoId]);
            if (!$goods && !$goodsSku) {
                $ret = [
                    'basicInfo' => [
                        'infoId' => '', 'category1' => '', 'isPackSell' => '', 'baseUnit' => '', 'addUnit' => '', 'quantity' => 100,
                        'lotNum' => 1, 'wsvalidnum' => '', 'packageType' => '', 'bulkOrder' => '', 'bulkDiscount' => '', 'deliverytime' => '',
                        'autoDelay' => '', 'description' => '', 'descriptionmobile' => '', 'packageLength' => '', 'packageWidth' => '',
                        'packageHeight' => '', 'grossWeight' => 0, 'addWeight' => '', 'productPrice' => 0, 'sku' => '', 'itemtitle' => '',
                        'freighttemplate' => '', 'promisetemplate' => '', 'imageUrl' => '', 'productunit' => '', 'groupid' => '',
                        'remarks' => '', 'publicmubanedit' => '', 'headKeywords' => '',
                        'requiredKeywords' => '["","","","","",""]',
                        'randomKeywords' => '["","","","","","","","","",""]',
                        'tailKeywords' => ''
                    ],
                    'skuInfo' => [[
                        'id' => '', 'infoId' => '', 'sid' => '', 'sku' => '', 'color' => '', 'size' => '',
                        'quantity' => '', 'price' => '', 'shipping' => '', 'msrp' => '',
                        'shippingTime' => '', 'pic_url' => '', 'goodsSkuId' => '', 'weight' => '',
                    ]]
                ];
                return $ret;
            }
            if (!$goods['requiredKeywords']) $goods['requiredKeywords'] = '["","","","","",""]';
            if (!$goods['randomKeywords']) $goods['randomKeywords'] = '["","","","","",""]';
            if (!$goods['grossWeight']) $goods['grossWeight'] = 0;
            if (!$goods['lotNum']) $goods['lotNum'] = 1;
            if (!$goods['quantity']) $goods['quantity'] = 1000;
            if (!$goods['productPrice']) $goods['productPrice'] = 0;
            if (isset($goods['category1']) && $goods['category1']) {
                foreach ($goodsSku as &$sku) {
                    $sku = self::filterAliexpressSkuColorAndSize($goods['category1'], $sku);
                }
            }
        } else {
            $goods = [];
            $goodsSku = [];
        }

        return [
            'basicInfo' => $goods,
            'skuInfo' => $goodsSku
        ];
    }

    /** save ebay info
     * @param $condition
     * Date: 2019-04-22 16:12
     * Author: henry
     * @return array|bool
     * @throws \Exception
     */
    public static function saveEbayInfo($condition)
    {
        $goodsInfo = $condition['basicInfo'];
        $skuInfo = $condition['skuInfo'];
        $goods = OaEbayGoods::findOne(['nid' => $goodsInfo['nid']]);
        $goods->setAttributes($goodsInfo);
        $tran = Yii::$app->db->beginTransaction();
        try {
            foreach ($skuInfo as $row) {
                $sku = OaEbayGoodsSku::findOne(['id' => $row['id']]);
                if ($sku === null) {
                    $sku = new OaEbayGoodsSku();
                }
                $row['property'] = json_encode($row['property']);
                $sku->setAttributes($row);
                if (!$sku->save()) {
                    throw new \Exception('save sku failed');
                }
            }
            if (!$goods->save()) {
                throw new \Exception('save goods failed');
            }
            $tran->commit();
            return ["success to save {$goodsInfo['sku']}"];
        } catch (\Exception $e) {
            $tran->rollBack();
            return [
                'code' => 400,
                'message' => $e->getMessage(),
            ];
        }

    }

    /** 保存wish模板
     * @param $condition
     * Date: 2019-04-23 10:32
     * Author: henry
     * @return array|bool
     * @throws \yii\db\Exception
     */
    public static function saveWishInfo($condition)
    {
        $goodsInfo = $condition['basicInfo'];
        $skuInfo = $condition['skuInfo'];
        $goods = OaWishGoods::findOne(['id' => $goodsInfo['id']]);
        $goods->setAttributes($goodsInfo);
        $tran = Yii::$app->db->beginTransaction();
        try {
            foreach ($skuInfo as $row) {
                $sku = OaWishGoodsSku::findOne(['id' => $row['id']]);
                if ($sku === null) {
                    $sku = new OaWishGoodsSku();
                }
                $sku->setAttributes($row);
                if (!$sku->save()) {
                    throw new \Exception('save sku failed');
                }
            }
            if (!$goods->save()) {
                throw new \Exception('save goods failed');
            }
            $tran->commit();
            return true;
        } catch (\Exception $e) {
            $tran->rollBack();
            return [
                'code' => 400,
                'message' => $e->getMessage(),
            ];
        }
    }

    /** 保存smt模板
     * @param $condition
     * Date: 2019-04-23 10:32
     * Author: henry
     * @return array|bool
     * @throws \yii\db\Exception
     */
    public static function saveSmtInfo($condition)
    {
        $goodsInfo = $condition['basicInfo'];
        $skuInfo = $condition['skuInfo'];
        $goods = OaSmtGoods::findOne(['id' => $goodsInfo['id']]);
        $imgArr = [];
        for ($i = 0; $i < 6; $i++) {
            if ($condition['basicInfo']['imageUrl' . $i]) {
                $imgArr[] = $condition['basicInfo']['imageUrl' . $i];
            }
        }
        $goodsInfo['imageUrl'] = implode(';', $imgArr);
        $keyWords = static::preKeywords($goodsInfo);
        $titlePool = [];
        $title = '';
        $len = self::smtTitleLength;
        while (true) {
            $title = static::getTitleName($keyWords, $len);
            --$len;
            if (empty($title) || !in_array($title, $titlePool, false)) {
                $titlePool[] = $title;
                break;
            }
        }
        $goodsInfo['itemtitle'] = $title;
        $goods->setAttributes($goodsInfo);
        $tran = Yii::$app->db->beginTransaction();
        try {
            foreach ($skuInfo as $row) {
                $sku = OaSmtGoodsSku::findOne(['id' => $row['id']]);
                if ($sku === null) {
                    $sku = new OaSmtGoodsSku();
                }
                $sku->setAttributes($row);
                if (isset($goods['category1']) && $goods['category1']) {
                    $newRow = self::filterAliexpressSkuColorAndSize($goods['category1'], $row);
                    $sku->color = $newRow['color'];
                    $sku->size = $newRow['size'];
                }
                if (!$sku->save()) {
                    throw new \Exception('save sku failed');
                }
            }
            if (!$goods->save()) {
                throw new \Exception('save goods failed');
            }
            $tran->commit();
            return true;
        } catch (\Exception $e) {
            $tran->rollBack();
            return [
                'code' => 400,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * @param $categoryId
     * @param $row
     * Date: 2020-05-28 16:41
     * Author: henry
     * @return mixed
     */
    public static function filterAliexpressSkuColorAndSize($categoryId, $row)
    {
        $sql = "select name,value from aliexpress_specifics where categoryid={$categoryId} and isskuattribute=1 order by customizedpic desc";
        $ibayArr = Yii::$app->ibay->createCommand($sql)->queryAll();
        foreach ($ibayArr as $k => $var) {
            #第一行是关联图片属性 ，匹配颜色
            if ($k == 0 && $row['color']) {
                $row['color'] = self::getAliexpressSkuAttributes($row['color'], $var);
            }
            #匹配 Size
            if ($row['size'] && strpos($var['name'], 'Size') !== false) {
                $row['size'] = self::getAliexpressSkuAttributes($row['size'], $var, true);
            }
        }
        return $row;
    }

    /** SKU 和ibay数据匹配
     * @param $attribute
     * @param $var
     * Date: 2020-05-28 15:55
     * Author: henry
     * @return string
     */
    public static function getAliexpressSkuAttributes($attribute, $var, $flag = false)
    {
        $attributeArr = unserialize($var['value']);
        //var_dump($attributeArr);exit;
        $newAttrArr = $zhAttr = $enAttr = [];
        foreach ($attributeArr as $attrVal) {
            if ($flag == true) {
                if ($attrVal['names']['zh'] == $attribute || $attrVal['names']['en'] == $attribute
                    || $attrVal['names']['zh'] == 'XL' && $attribute == '1XL'
                    || $attrVal['names']['zh'] == 'XXL' && $attribute == '2XL'
                    || $attrVal['names']['zh'] == 'XXXL' && $attribute == '3XL'
                ) {
                    $newAttrArr[] = ['zh' => $attrVal['names']['zh'], 'en' => $attrVal['names']['en']];
                }
                if ($attrVal['names']['zh'] == $attribute
                    || $attrVal['names']['zh'] == 'XL' && $attribute == '1XL'
                    || $attrVal['names']['zh'] == 'XXL' && $attribute == '2XL'
                    || $attrVal['names']['zh'] == 'XXXL' && $attribute == '3XL'
                ) {
                    $zhAttr[] = $attrVal['names']['zh']; //中文颜色
                }
                if ($attrVal['names']['en'] == $attribute
                    || $attrVal['names']['zh'] == 'XL' && $attribute == '1XL'
                    || $attrVal['names']['zh'] == 'XXL' && $attribute == '2XL'
                    || $attrVal['names']['zh'] == 'XXXL' && $attribute == '3XL'
                ) {
                    $enAttr[] = $attrVal['names']['en']; //英文颜色
                }
            } else {
                if (strpos($attrVal['names']['zh'], $attribute) !== false ||
                    strpos($attrVal['names']['en'], $attribute) !== false
                ) {
                    $newAttrArr[] = ['zh' => $attrVal['names']['zh'], 'en' => $attrVal['names']['en']];
                }
                if (strpos($attrVal['names']['zh'], $attribute) !== false) {
                    $zhAttr[] = $attrVal['names']['zh']; //中文颜色
                }
                if (strpos($attrVal['names']['en'], $attribute) !== false) {
                    $enAttr[] = $attrVal['names']['en']; //英文颜色
                }
            }
        }
        //获取最终属性值，先匹配中文，没有则匹配英文
        $minZhAttr = $minEnAttr = '';

        if ($zhAttr) {
            $minZhAttr = min($zhAttr);//取最小中文颜色
            foreach ($newAttrArr as $v) {
                if ($v['zh'] == $minZhAttr) {
                    $minEnAttr = $v['en'];
                }
            }
            if ($minZhAttr && $minEnAttr) {
                $attribute = $minEnAttr . '(' . $minZhAttr . ')';
            }
        } elseif ($enAttr) {
            $minEnAttr = min($enAttr);//取最小英文颜色
            foreach ($newAttrArr as $v) {
                if ($v['en'] == $minEnAttr) {
                    $minZhAttr = $v['zh'];
                }
            }
            if ($minZhAttr && $minEnAttr) {
                $attribute = $minEnAttr . '(' . $minZhAttr . ')';
            }
        }
        return $attribute;
    }


    /** 平台信息标记完善
     * @param $condition
     * Date: 2019-05-17 13:16
     * Author: henry
     * @return array|bool
     * @throws \yii\db\Exception
     */
    public static function finishPlat($condition)
    {
        $ids = is_array($condition['id']) ? $condition['id'] : [$condition['id']];
        $plat = $condition['plat'];
        $tran = Yii::$app->db->beginTransaction();
        try {
            foreach ($ids as $infoId) {
                $goodsInfo = OagoodsInfo::findOne(['id' => $infoId]);
                $oldPlat = $goodsInfo->completeStatus ?: '';
                $newPlat = array_merge($plat, explode(',', $oldPlat));
                $newPlat = array_filter($newPlat);
                $newPlat = array_unique($newPlat);
                asort($newPlat);
                $goodsInfo->completeStatus = implode(',', $newPlat);
                if (!$goodsInfo->save()) {
                    throw new \Exception('标记完善失败!');
                }
            }
            $tran->commit();
            return true;
        } catch (\Exception $e) {
            $tran->rollBack();
            return [
                'code' => 400,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * @brief wish保存并完善
     * @param $condition
     * @return array
     * @throws \Exception
     */
    public static function saveFinishPlat($condition)
    {
        $plat = $condition['plat'];
        if ($plat === 'wish') {
            static::saveWishInfo($condition);
        }
        if ($plat === 'ebay') {
            static::saveEbayInfo($condition);
        }
        if ($plat === 'joom') {
            static::saveWishInfo($condition);
        }
        if ($plat === 'aliexpress') {
            static::saveSmtInfo($condition);
        }
        $platCondition = ['id' => $condition['id'], 'plat' => [$plat]];
        static::finishPlat($platCondition);
        return [];
    }


    /**
     * @brief get all ebay accounts
     * @return array
     */
    public static function getEbayAccount()
    {
        $ret = OaEbaySuffix::find()->select('ebaySuffix,ebayName,storeCountry')->orderBy('ebaySuffix')->all();
        //return ArrayHelper::map($ret, 'ebayName', 'ebaySuffix');
        return $ret;
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
        $wishAccounts = OaWishSuffix::find()->where(['like', 'parentCategory', $goods['cate']])
            ->orWhere(["IFNULL(parentCategory,'')" => ''])
            ->andWhere(['isIbay' => 1])
            ->asArray()->all();
        $keyWords = static::preKeywords($wishInfo);

        $row = [
            'sku' => '', 'selleruserid' => '', 'name' => '', 'inventory' => '', 'price' => '', 'msrp' => '',
            'shipping' => '', 'shipping_time' => '', 'main_image' => '', 'extra_images' => '', 'variants' => '',
            'landing_page_url' => '', 'tags' => '', 'description' => '', 'brand' => '', 'upc' => '', 'local_price' => '',
            'local_shippingfee' => '', 'local_currency' => ''
        ];
        $ret = ['name' => 'wish-' . $goodsInfo['goodsCode']];
        $out = [];
        foreach ($wishAccounts as $account) {
            $titlePool = [];
            $title = '';
            $len = self::WishTitleLength;
            while (true) {
                $title = static::getTitleName($keyWords, $len);
                --$len;
                if (empty($title) || !in_array($title, $titlePool, false)) {
                    $titlePool[] = $title;
                    break;
                }
            }
            if(count($wishSku) > 1) $goodsInfo['isVar'] = '是'; // 2020-06-02 添加（单平台添加多属性）
//            var_dump($goodsInfo['isVar']);exit;
            $variantInfo = static::getWishVariantInfo($goodsInfo['isVar'], $wishInfo, $wishSku, $account);
            $row['sku'] = $wishInfo['sku'] . $account['suffix'];
            $row['selleruserid'] = $account['ibaySuffix'];
            $row['name'] = $title;
            $row['inventory'] = $wishInfo['inventory'];
            $row['price'] = $variantInfo['price'];
            $row['msrp'] = $variantInfo['msrp'];
            $row['shipping'] = $variantInfo['shipping'];
            $row['shipping_time'] = '7-21';
            $row['main_image'] = static::getWishMainImage($goodsInfo['goodsCode'], $account['mainImg']);
            $row['extra_images'] = $wishInfo['extraImages'];
            $row['variants'] = $variantInfo['variant'];
            $row['landing_page_url'] = $wishInfo['mainImage'];
            $row['tags'] = $wishInfo['wishTags'];
            $row['description'] = $wishInfo['description'];
            $row['brand'] = '';
            $row['upc'] = '';
            $row['local_price'] = $variantInfo['local_price'];
            $row['local_shippingfee'] = $variantInfo['local_shippingfee'];
            $row['local_currency'] = $variantInfo['local_currency'];
            $out[] = $row;
        }
        $ret['data'] = $out;
        return $ret;
    }



    /**
     * @brief 导出myMall模板
     * @param $ids
     * @param $accounts
     * @return array
     */
    public static function preExportMyMall($id)
    {
        $goodsInfo = OaGoodsinfo::find()->where(['id' => $id])->one();
        $ret = ['name' =>$goodsInfo['goodsCode']];
        $myMallAccounts = OaMyMallSuffix::find()
            ->asArray()->all();
        $aRow = [
            'SKU' => '', 'group_id' => '', 'enable' => 'TRUE', 'stock' => '9000', 'name' => '', 'price' => '',
            'old_price' => '', 'color' => '', 'size' => '', 'weight' => '', 'packaging_size' => '',
            'brand' => '', 'tags' => '', 'upc' => '', 'description' => '', 'main_image_url' => '', 'image_url_1' => '',
            'image_url_2' => '', 'image_url_3' => '', 'image_url_4' => '', 'image_url_5' => '', 'image_url_6' => '',
            'image_url_7' => '', 'image_url_8' => '', 'image_url_9' => '', 'image_url_10' => '',
            'shipping_template_id' => '', 'shipping_time' => '3', 'lp_url' => ''

        ];

        $out = [];
        $id = $goodsInfo['id'];
        $myMallSku = OaWishGoodsSku::find()
            ->where(['infoId' => $id])
            ->asArray()->all();
        $myMallInfo = OaWishGoods::find()->where(['infoId' => $id])->asArray()->one();
        $keyWords = static::preKeywords($myMallInfo);
        $title = static::getTitleName($keyWords, self::myMallTitleLength);
        foreach ($myMallAccounts as $account) {
            $imageInfo = static::getJoomImageInfo($myMallInfo, $account);
            foreach ($myMallSku as $sku) {
                $row = $aRow;
                $row['SKU'] = $sku['sku'] . $account['skuCode'];
                $row['group_id'] = $myMallInfo['sku'] . $account['skuCode'];
                $row['name'] = $title;
                $row['price'] = $sku['price'];
                $row['old_price'] = ceil($sku['price'] * 3);
                $row['color'] = $sku['color'];
                $row['size'] = $sku['size'];
                $row['weight'] = $sku['weight'];
                $row['tags'] = $myMallInfo['wishTags'];
                $row['description'] = $myMallInfo['description'];
                $row['main_image_url'] = $sku['linkUrl'];
                $row['image_url_1'] = $imageInfo['mainImage'];
                $row['image_url_2'] = $imageInfo['extraImages'][0];
                $row['image_url_3'] = $imageInfo['extraImages'][1];
                $row['image_url_4'] = $imageInfo['extraImages'][2];
                $row['image_url_5'] = $imageInfo['extraImages'][3];
                $row['image_url_6'] = $imageInfo['extraImages'][4];
                $row['image_url_7'] = $imageInfo['extraImages'][5];
                $row['image_url_8'] = $imageInfo['extraImages'][6];
                $row['image_url_9'] = $imageInfo['extraImages'][7];
                $row['image_url_10'] = $imageInfo['extraImages'][8];
                $out[] = $row;
            }
        }
        $ret['data'] = $out;
        return $ret;
    }


    /**
     * @brief wish模板预处理
     * @param $id
     * @return array
     * @throws \Exception
     */
    public static function preExportWishData($id)
    {
        $wishInfo = OaWishgoods::find()->where(['infoId' => $id])->asArray()->one();
        $wishSku = OaWishgoodsSku::find()->where(['infoId' => $id])->asArray()->all();
        $goodsInfo = OaGoodsinfo::find()->where(['id' => $id])->asArray()->one();
        $goods = OaGoods::find()->where(['nid' => $goodsInfo['goodsId']])->asArray()->one();
        $wishAccounts = OaWishSuffix::find()->where(['like', 'parentCategory', $goods['cate']])
            ->orWhere(["IFNULL(parentCategory,'')" => ''])
            ->andWhere(['isIbay' => 0])
            ->asArray()->all();
        $keyWords = static::preKeywords($wishInfo);

        $row = [
            'sku' => '', 'selleruserid' => '', 'name' => '', 'inventory' => '', 'price' => '', 'msrp' => '',
            'shipping' => '', 'shipping_time' => '', 'main_image' => '', 'extra_images' => '', 'variants' => '',
            'landing_page_url' => '', 'tags' => '', 'description' => '', 'brand' => '', 'upc' => '', 'local_price' => '',
            'local_shippingfee' => '', 'local_currency' => ''
        ];
        $ret = ['name' => 'wish-' . $goodsInfo['goodsCode']];
        $out = [];
        foreach ($wishAccounts as $account) {
            $titlePool = [];
            $title = '';
            $len = self::WishTitleLength;
            while (true) {
                $title = static::getTitleName($keyWords, $len);
                --$len;
                if (empty($title) || !in_array($title, $titlePool, false)) {
                    $titlePool[] = $title;
                    break;
                }
            }
            if(count($wishSku) > 1) $goodsInfo['isVar'] = '是'; // 2020-06-02 添加（单平台添加多属性）
//            var_dump($goodsInfo['isVar']);exit;
            $variantInfo = static::getWishVariantInfo($goodsInfo['isVar'], $wishInfo, $wishSku, $account);
            $row['sku'] = $wishInfo['sku'] . $account['suffix'];
            $row['selleruserid'] = $account['shortName'];
            $row['name'] = $title;
            $row['inventory'] = $wishInfo['inventory'];
            $row['price'] = $variantInfo['price'];
            $row['msrp'] = $variantInfo['msrp'];
            $row['shipping'] = $variantInfo['shipping'];
            $row['shipping_time'] = '7-21';
            $row['main_image'] = static::getWishMainImage($goodsInfo['goodsCode'], $account['mainImg']);
            $row['extra_images'] = $wishInfo['extraImages'];
            $row['variants'] = $variantInfo['variant'];
            $row['landing_page_url'] = $wishInfo['mainImage'];
            $row['tags'] = $wishInfo['wishTags'];
            $row['description'] = $wishInfo['description'];
            $row['brand'] = '';
            $row['upc'] = '';
            $row['local_price'] = $variantInfo['local_price'];
            $row['local_shippingfee'] = $variantInfo['local_shippingfee'];
            $row['local_currency'] = $variantInfo['local_currency'];
            $out[] = $row;
        }
        $ret['data'] = $out;
        return $ret;
    }


    /**
     * @brief 导出joom模板
     * @param $ids
     * @param $accounts
     * @return array
     */
    public static function preExportJoom($ids, $accounts)
    {
        if (!is_array($accounts)) {
            $accounts = [$accounts];
        }
        $name = $accounts[0];
        if (!is_array($ids)) {
            $goodsInfo = OaGoodsinfo::find()->where(['OR', ['goodsCode' => $ids], ['id' => $ids]])->one();
            $ret = ['name' => $name . '-' . $goodsInfo['goodsCode']];
            $ids = [$ids];
        } else {
            if (count($ids) == 1) {
                $goodsInfo = OaGoodsinfo::find()->where(['OR', ['goodsCode' => $ids], ['id' => $ids]])->one();
                $ret = ['name' => $name . '-' . $goodsInfo['goodsCode']];
            } else {
                $ret = ['name' => $name . '-batch-'];
            }
        }

        $row = [
            'Parent Unique ID' => '', '*Product Name' => '', 'Description' => '', '*Tags' => '', '*Unique ID' => '', 'Color' => '',
            'Size' => '', '*Quantity' => '', '*Price' => '', '*MSRP' => '', '*Shipping' => '', 'Shipping weight' => '',
            'Shipping Time(enter without " ", just the estimated days )' => '', '*Product Main Image URL' => '',
            'Variant Main Image URL' => '', 'Extra Image URL' => '', 'Extra Image URL 1' => '', 'Extra Image URL 2' => '',
            'Extra Image URL 3' => '', 'Extra Image URL 4' => '', 'Extra Image URL 5' => '', 'Extra Image URL 6' => '',
            'Extra Image URL 7' => '', 'Extra Image URL 8' => '', 'Extra Image URL 9' => '', 'Dangerous Kind' => '',
            'Declared Value' => '', 'Store id' => ''
        ];
        $out = [];
        foreach ($ids as $id) {
            if (is_numeric($id)) {
                $goodsInfo = OaGoodsinfo::findOne(['id' => $id]);
            } else {
                $goodsInfo = OaGoodsinfo::findOne(['goodsCode' => $id]);
                $id = $goodsInfo['id'];
            }
            $joomSku = OaWishGoodsSku::find()
                ->where(['infoId' => $id])
                ->asArray()->all();
            $joomInfo = OaWishGoods::find()->where(['infoId' => $id])->asArray()->one();
            $keyWords = static::preKeywords($joomInfo);
            $title = static::getTitleName($keyWords, self::JoomTitleLength);
            foreach ($accounts as $account) {
                $joomAccounts = OaJoomSuffix::find()->where(['joomName' => $account])->asArray()->one();
                $imageInfo = static::getJoomImageInfo($joomInfo, $joomAccounts);
                foreach ($joomSku as $sku) {
//                    $price = static::getJoomAdjust($sku['weight'], $priceInfo['price']);
                    $row['Parent Unique ID'] = $joomInfo['sku'] . $joomAccounts['skuCode'];
                    $row['*Product Name'] = $title;
                    $row['Description'] = $joomInfo['description'];
                    $row['*Tags'] = $joomInfo['wishTags'];
                    $row['*Unique ID'] = $sku['sku'] . $joomAccounts['skuCode'];
                    $row['Color'] = $sku['color'];
                    $row['Size'] = $sku['size'];
                    $row['*Quantity'] = $sku['inventory'];
                    $row['*Price'] = $sku['joomPrice'];
                    $row['*MSRP'] = ($sku['joomPrice'] + $sku['joomShipping']) * 5;
                    $row['*Shipping'] = $sku['joomShipping'];
                    $row['Shipping weight'] = (float)$sku['weight'] * 1.0 / 1000;
                    $row['Shipping Time(enter without " ", just the estimated days )'] = '15-45';
                    $row['*Product Main Image URL'] = $imageInfo['mainImage'];
                    $row['Variant Main Image URL'] = str_replace('/10023/', '/' . $joomAccounts['imgCode'] . '/', $sku['linkUrl']);
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
                    $row['Dangerous Kind'] = static::getJoomDangerousKind($goodsInfo);
                    $row['Declared Value'] = static::getJoomDeclaredValue($sku['joomPrice']);
                    $out[] = $row;
                }
            }
        }
        $ret['data'] = $out;
        return $ret;
    }

    /** joom 上架产品
     * @param $id
     * @param $accounts
     * Date: 2020-05-26 17:07
     * Author: henry
     * @return array|bool
     * @throws Exception
     */
    public static function uploadToJoomBackstage($id, $accounts)
    {
        if (!is_array($accounts)) {
            $accounts = [$accounts];
        }
        $row = [
            'parent_sku' => '', 'brand' => '', 'description' => '',
            'tags' => '', 'upc' => '', 'color' => '', 'sku' => '', 'name' => '', 'hs_code' => '',
            'size' => '', 'inventory' => '', 'price' => '', 'msrp' => '', 'shipping' => '',
            'shipping_weight' => '', 'shipping_height' => '', 'shipping_length' => '', 'shipping_width' => '',
            'main_image' => '', 'product_main_image' => '', 'variation_main_image' => '', 'extra_images' => '',
            'landing_page_url' => '', 'dangerous_kind' => 'notDangerous', 'declaredValue' => ''
        ];
        $logData = [
            'infoId' => $id,
            'ibayTemplateId' => '',
            'result' => 'failed',
            'platForm' => 'joom',
        ];
        if (is_numeric($id)) {
            $goodsInfo = OaGoodsinfo::findOne(['id' => $id]);
        } else {
            $goodsInfo = OaGoodsinfo::findOne(['goodsCode' => $id]);
            $id = $goodsInfo['id'];
        }
        $joomSku = OaWishGoodsSku::find()->where(['infoId' => $id])->asArray()->all();

        $joomInfo = OaWishGoods::find()->where(['infoId' => $id])->asArray()->one();
        $keyWords = static::preKeywords($joomInfo);
        $title = static::getTitleName($keyWords, self::JoomTitleLength);
        foreach ($accounts as $account) {
            $joomAccounts = OaJoomSuffix::find()->where(['joomName' => $account])->asArray()->one();
            $imageInfo = static::getJoomImageInfo($joomInfo, $joomAccounts);
            $row['parent_sku'] = $joomInfo['sku'] . $joomAccounts['skuCode'];
            #获取账号TOKEN
            if($account == 'Joom') $account .= '0';
//            var_dump($account);exit;
            $sql = "SELECT AliasName AS suffix,d.memo,AccessToken AS token 
                        FROM [dbo].[S_JoomSyncInfo] s
                        INNER JOIN B_Dictionary d ON d.DictionaryName=s.AliasName AND d.CategoryID=12 
                        WHERE  AliasName LIKE '{$account}%'";
            $tokens = Yii::$app->py_db->createCommand($sql)->queryAll();
            #判断账号是否存在该产品
            $check = self::selectAndCheckJoomProducts($row['parent_sku'], $tokens);
//            var_dump($check);exit;
            if ($check) {
                $joomSku = OaWishGoodsSku::find()->andFilterWhere(['infoId' => $id])->andFilterWhere(['not in', 'sku', $check[1]])->asArray()->all();
//                var_dump($joomSku);exit;
                # 上架新增加变体（已排除已有变体）
                $varRes = self::uploadProductVariationToJoomBackstage($row['parent_sku'], $joomSku, $check[0], $joomAccounts);
            } else {
                $row['name'] = $title;
                $row['description'] = $joomInfo['description'];
                $row['tags'] = $joomInfo['wishTags'];
                $row['sku'] = $joomSku[0]['sku'] . $joomAccounts['skuCode'];
                $row['color'] = $joomSku[0]['color'];
                $row['size'] = $joomSku[0]['size'];
                $row['inventory'] = $joomSku[0]['inventory'];
                $row['price'] = $joomSku[0]['joomPrice'];
                $row['msrp'] = ($joomSku[0]['joomPrice'] + $joomSku[0]['joomShipping']) * 5;
                $row['shipping'] = $joomSku[0]['joomShipping'];
                $row['shipping_weight'] = (float)$joomSku[0]['weight'] * 1.0 / 1000;
                //$row['product_main_image'] = $imageInfo['mainImage'];
                $row['main_image'] = $imageInfo['mainImage'];
                $row['variation_main_image'] = str_replace('/10023/', '/' . $joomAccounts['imgCode'] . '/', $joomSku[0]['linkUrl']);

                foreach ($imageInfo['extraImages'] as $k => $v) {
                    if ($k <= 9 && $v) {
                        if ($k == 0) {
                            $row['extra_images'] .= $imageInfo['extraImages'][$k];
                        } else {
                            $row['extra_images'] .= '|' . $imageInfo['extraImages'][$k];
                        }
                    }
                }
                $row['dangerous_kind'] = static::getJoomDangerousKind($goodsInfo);
                $row['declaredValue'] = static::getJoomDeclaredValue($joomSku[0]['joomPrice']);
                #随机获取符合条件的token
                $token = self::filterJoomAccount($goodsInfo['goodsId'], $tokens);
                $row['access_token'] = $token ? $token['token'] : '';
                # 上架产品
                $url = 'https://api-merchant.joom.com/api/v2/product/add';
                $ret = Helper::curlRequest($url, $row);
                if ($ret['code'] == 0 && $ret['data']) {
                    $logData['ibayTemplateId'] = $ret['data']['Product']['id'];
                    $logData['result'] = 'Success';
                } else {
                    $logData['result'] = $ret['message'];
                }
                Logger::ibayLog($logData);
                if ($ret['code'] != 0) {
                    throw new Exception($ret['message']);
                }
                # 上架新增加变体（已排除已有变体）
                unset($joomSku[0]);
//                var_dump($joomSku);exit;
                $varRes = self::uploadProductVariationToJoomBackstage($row['parent_sku'], $joomSku, $token, $joomAccounts);
            }
            if ($varRes) {
                return [
                    'code' => 400,
                    'msg' => 'Failed upload variants',
                    'data' => $varRes
                ];
            }
        }
        return true;
    }

    /** joom 上架产品变体
     * @param $parentSku
     * @param $joomSku
     * @param $account
     * @param $joomAccounts
     * Date: 2020-05-27 14:44
     * Author: henry
     * @return array | boolean
     */
    public static function uploadProductVariationToJoomBackstage($parentSku, $joomSku, $token, $joomAccounts)
    {
        $variationRow = [
            'main_image' => '', 'sku' => '', 'parent_sku' => '',
            'enabled' => true, 'color' => '', 'hs_code' => '', 'declaredValue' => '',
            'size' => '', 'inventory' => '', 'price' => '', 'msrp' => '', 'shipping' => '',
            'shipping_weight' => '', 'shipping_height' => '', 'shipping_length' => '', 'shipping_width' => ''
        ];
        $message = [];
        foreach ($joomSku as $sku) {
            $variationRow['parent_sku'] = $parentSku;
            $variationRow['sku'] = $sku['sku'] . $joomAccounts['skuCode'];
            $variationRow['color'] = $sku['color'];
            $variationRow['size'] = $sku['size'];
            $variationRow['inventory'] = $sku['inventory'];
            $variationRow['price'] = $sku['joomPrice'];
            $variationRow['msrp'] = ($sku['joomPrice'] + $sku['joomShipping']) * 5;
            $variationRow['shipping'] = $sku['joomShipping'];
            $variationRow['shipping_weight'] = (float)$sku['weight'] * 1.0 / 1000;
            $variationRow['main_image'] = str_replace('/10023/', '/' . $joomAccounts['imgCode'] . '/', $sku['linkUrl']);
            $variationRow['declaredValue'] = static::getJoomDeclaredValue($sku['joomPrice']);
            $variationRow['access_token'] = $token ? $token['token'] : '';

            $variationUrl = 'https://api-merchant.joom.com/api/v2/variant/add';
            $res = Helper::curlRequest($variationUrl, $variationRow);
//            var_dump($res);exit;
            if ($res['code']) {
                $message[] = 'SKU ' . $sku['sku'] . ' upload failed cause of ' . $res['message'];
            }
        }
        return $message;
    }

    /**
     * @param $sku
     * @param $account
     * Date: 2020-05-27 14:24
     * Author: henry
     * @return bool|mixed
     */
    public static function selectAndCheckJoomProducts($parentSku, $account)
    {
        $url = "https://api-merchant.joom.com/api/v2/product";
        foreach ($account as $v) {
            $params = [
                'access_token' => $v['token'],
                'parent_sku' => $parentSku,
            ];
            $res = Helper::curlRequest($url, $params, 'GET');
            if ($res['code'] == 0) {
//                var_dump($res);exit;
                $sku = [];
                $skuArr = $res['data']['Product']['variants'];
                foreach ($skuArr as $var) {
                    $item = explode('@#', $var['Variant']['sku']);
                    $sku[] = $item[0];
                }
                return [$v, $sku];
            }
        }
        return false;
    }


    /** 查询账号内是否存在该产品
     * @param $sku
     * @param $account
     * Date: 2020-05-26 16:38
     * Author: henry
     * @return mixed
     */
    public static function checkJoomProducts($sku, $account)
    {
        if ($account == 'joom') {
            $new = $account . '0';
            $sql = "SELECT COUNT(1) FROM ibay365_joom_lists WHERE (suffix LIKE '{$new}%' OR suffix='{$account}') AND (code LIKE '{$sku}%' OR sku LIKE '{$sku}%')";
        } else {
            $sql = "SELECT COUNT(1) FROM ibay365_joom_lists WHERE suffix LIKE '{$account}%' AND (code LIKE '{$sku}%' OR sku LIKE '{$sku}%')";
        }
        return Yii::$app->py_db->createCommand($sql)->queryScalar();
    }


    /** 过滤joom账号后随机选择一个账号
     * @param $goodsId
     * @param $accounts
     * Date: 2020-05-23 11:12
     * Author: henry
     * @return array|mixed
     * @throws \yii\db\Exception
     */
    public static function filterJoomAccount($goodsId, $accounts)
    {
        $ret = $arr = [];
        $goods = OaGoods::findOne($goodsId);

        foreach ($accounts as $item) {
            $sql = "SELECT * FROM proCenter.oa_joomSuffixFilter WHERE joomSuffix='{$item['suffix']}'";
            $res = Yii::$app->db->createCommand($sql)->queryScalar();
            if (!$res) {
                $arr[] = $item;
                if ($item['memo'] == $goods['subCate']) {
                    $ret[] = $item;
                } else if ($item['memo'] == $goods['cate']) {
                    $ret[] = $item;
                }
            }
        }

        //随机选择一个账号
        if ($ret) {
            shuffle($ret);
            return $ret[0];
        }
        if ($arr) {
            shuffle($arr);
            return $arr[0];
        }

        return [];
    }


    /**
     * @brief ebay模板预处理
     * @param $id
     * @param $accounts
     * @return array
     */
    public static function preExportEbay($id, $accounts)
    {
        $ebayInfo = OaEbayGoods::find()->joinWith('oaEbayGoodsSku')
            ->where(['oa_ebayGoods.infoId' => $id])->asArray()->one();
        $goodsInfo = OaGoodsinfo::findOne($id);
        if ($ebayInfo === null || $goodsInfo === null) {
            return ['code' => '400001', 'message' => '无效的ID'];
        }
        $ret = ['name' => 'ebay-' . $goodsInfo['goodsCode']];
        $out = [];
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
        $keyWords = static::preKeywords($ebayInfo);
        foreach ($accounts as $account) {
            $ebayAccount = OaEbaySuffix::find()->where(['ebaySuffix' => $account])->asArray()->one();
            $payPal = self::getEbayPayPal($price, $ebayAccount);
            $titlePool = [];
            $title = '';
            $len = self::EbayTitleLength;
            while (true) {
                $title = static::getTitleName($keyWords, $len);
                --$len;
                if (empty($title) || !in_array($title, $titlePool, false)) {
                    $titlePool[] = $title;
                    break;
                }
            }

            $row['Site'] = $ebayInfo['site'];
            $row['Selleruserid'] = $ebayAccount['ebayName'];
            $row['ListingType'] = 'FixedPriceItem';
            $row['Category1'] = $ebayInfo['listedCate'];
            $row['Category2'] = $ebayInfo['listedSubcate'];
            $row['Condition'] = '1000';
            $row['ConditionBewrite'] = '';
            $row['Quantity'] = !empty($ebayInfo['quantity']) ? $ebayInfo['quantity'] : 5;
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
            $row['PictureURL'] = static::getEbayPicture($goodsInfo, $ebayInfo, $account);
            $row['Title'] = $title;
            $row['SubTitle'] = $ebayInfo['subTitle'];
            $row['IbayCategory'] = '';
            $row['StartPrice'] = '';
            $row['BuyItNowPrice'] = $price;
            $row['UseMobile'] = '1';
            $row['ShippingService1'] = static::getShippingService($ebayInfo['inShippingMethod1']);
            $row['ShippingServiceCost1'] = $ebayInfo['inFirstCost1'];
            $row['ShippingServiceAdditionalCost1'] = $ebayInfo['inSuccessorCost1'];
            $row['ShippingService2'] = static::getShippingService($ebayInfo['inShippingMethod2']);
            $row['ShippingServiceCost2'] = $ebayInfo['inFirstCost2'];
            $row['ShippingServiceAdditionalCost2'] = $ebayInfo['inSuccessorCost2'];
            $row['ShippingService3'] = '';
            $row['ShippingServiceCost3'] = '';
            $row['ShippingServiceAdditionalCost3'] = '';
            $row['ShippingService4'] = '';
            $row['ShippingServiceCost4'] = '';
            $row['ShippingServiceAdditionalCost4'] = '';
            $row['InternationalShippingService1'] = static::getShippingService($ebayInfo['outShippingMethod1']);
            $row['InternationalShippingServiceCost1'] = $ebayInfo['outFirstCost1'];
            $row['InternationalShippingServiceAdditionalCost1'] = $ebayInfo['outSuccessorCost1'];
            $row['InternationalShipToLocation1'] = static::getShippingService($ebayInfo['outShippingMethod1']) ? 'Worldwide' : '';
            $row['InternationalShippingService2'] = static::getShippingService($ebayInfo['outShippingMethod2']);
            $row['InternationalShippingServiceCost2'] = $ebayInfo['outFirstCost2'];
            $row['InternationalShippingServiceAdditionalCost2'] = $ebayInfo['outSuccessorCost2'];
            $row['InternationalShipToLocation2'] = static::getShippingService($ebayInfo['outShippingMethod2']) ? 'Worldwide' : '';
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
            $row['IbayEffectImg'] = static::getEbayPicture($goodsInfo, $ebayInfo, $account);
            $row['IbayCrossSelling'] = '';
            //var_dump($ebayInfo);exit;
            if(count($ebayInfo['oaEbayGoodsSku']) > 1) $goodsInfo['isVar'] = '是'; // 2020-06-02 添加（单平台添加多属性）
            $row['Variation'] = static::getEbayVariation($goodsInfo['isVar'], $ebayInfo, $ebayAccount['nameCode']);
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
            $out[] = $row;
        }
        $ret['data'] = $out;
        return $ret;
    }


    /**
     * @brief shopfiy模板预处理
     * @param $id
     * @param $accounts
     * @return array
     * @throws \Exception
     */
    public static function preExportShopify($id, $accounts)
    {
        $wishInfo = OaWishgoods::find()->where(['infoId' => $id])->asArray()->one();
        $wishSku = OaWishgoodsSku::find()->where(['infoId' => $id])->asArray()->all();
        $goodsInfo = OaGoodsinfo::find()->where(['id' => $id])->asArray()->one();
//        $goods = OaGoods::find()->where(['nid' => $goodsInfo['goodsId']])->asArray()->one();
        $keyWords = static::preKeywords($wishInfo);
        $rowTemplate = [
            'Handle' => '', 'Title' => '', 'Body (HTML)' => '', 'Vendor' => '', 'Type' => '', 'Tags' => '',
            'Published' => 'TRUE', 'Option1 Name' => '', 'Option1 Value' => '', 'Option2 Name' => '',
            'Option2 Value' => '', 'Option3 Name' => '', 'Option3 Value' => '', 'Variant SKU' => '',
            'Variant Grams' => '', 'Variant Inventory Tracker' => 'shopify', 'Variant Inventory Qty' => '',
            'Variant Inventory Policy' => 'continue', 'Variant Fulfillment Service' => 'manual', 'Variant Price' => '',
            'Variant Compare At Price' => '', 'Variant Requires Shipping' => 'FALSE', 'Variant Taxable' => 'FALSE',
            'Variant Barcode' => '', 'Image Src' => '', 'Image Position' => '', 'Image Alt Text' => '',
            'Gift Card' => 'FALSE', 'SEO Title' => '', 'SEO Description' => '',
            'Google Shopping / Google Product Category' => '', 'Google Shopping / Gender' => '',
            'Google Shopping / Age Group' => '', 'Google Shopping / MPN' => '',
            'Google Shopping / AdWords Grouping' => '', 'Google Shopping / AdWords Labels' => '',
            'Google Shopping / Condition' => '', 'Google Shopping / Custom Product' => '',
            'Google Shopping / Custom Label 0' => '', 'Google Shopping / Custom Label 1' => '',
            'Google Shopping / Custom Label 2' => '', 'Google Shopping / Custom Label 3' => '',
            'Google Shopping / Custom Label 4' => '', 'Variant Image' => '',
            'Variant Weight Unit' => 'g', 'Variant Tax Code' => '', 'Cost per item' => '',
        ];
        $ret = ['name' => 'shopify-' . $goodsInfo['goodsCode']];
        $out = [];

        foreach ($accounts as $act) {
            $account = OaShopify::find()->where(['account' => $act])->asArray()->one();
            $titlePool = [];
            $title = '';
            $len = self::WishTitleLength;
            while (true) {
                $title = static::getTitleName($keyWords, $len);
                --$len;
                if (empty($title) || !in_array($title, $titlePool, false)) {
                    $titlePool[] = $title;
                    break;
                }
            }
            $imageSrc = explode("\n", $wishInfo['extraImages']);
            $sizeImage = array_shift($imageSrc);
            if (strpos($sizeImage, '00_.jpg') !== false) {
                array_splice($imageSrc, 1, 0, $sizeImage);
            }
            if (strpos($sizeImage, '00_.jpg') === false) {
                array_splice($imageSrc, 0, 0, $sizeImage);
            }
            $imagesCount = count($imageSrc);
            $position = 1;
            foreach ($wishSku as $sku) {
                $option1Name = static::getShopifyOptionName($position, $sku, 'Color');
                $option2Name = static::getShopifyOptionName($position, $sku, 'Size');
                $row = $rowTemplate;
                $row['Handle'] = str_replace(' ', '-', $title);
                $row['Title'] = $position > 1 ? '' : $title;
                $row['Body (HTML)'] = $position > 1 ? '' : str_replace("\n", '<br>', $wishInfo['description']);
                $row['Vendor'] = $position > 1 ? '' : $account['account'];
                $row['Tags'] = $position > 1 ? '' : static::getShopifyTag($account['tags'], $title);
                $row['Published'] = $position > 1 ? '' : 'True';
                $row['Option1 Name'] = !empty($option1Name) ? $option1Name : $option2Name;
                $row['Option2 Name'] = $option2Name;
                $row['Option1 Value'] = !empty($sku['color']) ? $sku['color'] : $sku['size'];
                $row['Option2 Value'] = empty($sku['color']) && !empty($sku['size']) ? '' : $sku['size'];
                $row['Variant SKU'] = $sku['sku'];
                $row['Variant Grams'] = $sku['weight'];
                $row['Variant Inventory Qty'] = $sku['inventory'];
                $row['Variant Price'] = $sku['price'] + 3;
                $row['Variant Compare At Price'] = ceil(($sku['price'] + 3) * 3);
                $row['Variant Image'] = $sku['linkUrl'];
                $row['Image Src'] = $position <= $imagesCount ? $imageSrc[$position - 1] : '';
                $row['Image Position'] = $position <= $imagesCount ? $position : '';
                $out[] = $row;
                $position++;
            }

            //追加图片
            if ($imagesCount > $position) {
                $row = $rowTemplate;
                foreach ($row as $key => $value) {
                    $row[$key] = '';
                }
                while ($position <= $imagesCount) {
                    $row['Image Src'] = $imageSrc[$position - 1];
                    $out[] = $row;
                    $position++;
                }
            }

        }
        $ret['data'] = $out;
        return $ret;
    }

    public static function getShopifyAccounts()
    {
        $ret = OaShopify::find()->select('account')->asArray()->all();
        $ret = ArrayHelper::getColumn($ret, 'account');
        return $ret;
    }


    /**
     * @brief vova模板预处理
     * @param $ids
     * @param $accounts
     * @return array
     * @throws \Exception
     */
    public static function preExportVova($ids, $accounts)
    {
        $rowTemplate = [
            'Vova Category ID' => '', 'Parent SKU' => '', 'SKU' => '', 'Goods Name' => '', 'Quantity' => '',
            'Goods Description' => '', 'Tags' => '', 'Goods Brand' => '', 'Market Price' => '', 'Shop Price' => '',
            'Shipping Fee' => '', 'Shipping Weight' => '', 'Shipping Time' => '', 'From Platform' => '',
            'Size' => '', 'Color' => '', 'Style Quantity' => '', 'Main Image URL' => '', 'Extra Image URL' => '',
            'Extra Image URL 1' => '', 'Extra Image URL 2' => '', 'Extra Image URL 3' => '', 'Extra Image URL 4' => '',
            'Extra Image URL 5' => '', 'Extra Image URL 6' => '', 'Extra Image URL 7' => '', 'Extra Image URL 8' => '',
            'Extra Image URL 9' => '', 'Extra Image URL 10' => ''
        ];
        $out = [];
        $fileName = count($ids) > 1 ? 'multiple-goods' : OaGoodsinfo::find()
                ->select('goodsCode')->where(['id' => $ids[0]])->scalar() or
            OaGoodsinfo::find()
                ->select('goodsCode')->where(['goodsCode' => $ids[0]])->scalar();
        if (!is_array($accounts)) {
            $accounts = [$accounts];
        }
        $ret = ['name' => $accounts[0] . '-' . $fileName];
        foreach ($ids as $id) {
            if (is_numeric($id)) {
                $goodsInfo = OaGoodsinfo::findOne(['id' => $id]);
            } else {
                $goodsInfo = OaGoodsinfo::findOne(['goodsCode' => $id]);
                $id = $goodsInfo['id'];
            }
            $wishInfo = OaWishgoods::find()->where(['infoId' => $id])->asArray()->one();
            $wishSku = OaWishgoodsSku::find()->where(['infoId' => $id])->asArray()->all();
            $keyWords = static::preKeywords($wishInfo);

            foreach ($accounts as $act) {
                $account = OaVovaSuffix::findOne(['account' => $act]);
                $fixArr = explode('-', $act);
                if (count($fixArr) == 2) {
                    $postfix = '@#' . substr($fixArr[1], 0, 2);
                } else {
                    $postfix = '@#' . $fixArr[1];
                }
                $titlePool = [];
                $title = '';
                $len = self::WishTitleLength;
                while (true) {
                    $title = static::getTitleName($keyWords, $len);
                    --$len;
                    if (empty($title) || !in_array($title, $titlePool, false)) {
                        $titlePool[] = $title;
                        break;
                    }
                }

                foreach ($wishSku as $sku) {
                    $row = $rowTemplate;
                    $row['Parent SKU'] = $wishInfo['sku'] . $postfix;
                    $row['SKU'] = $sku['sku'] . $postfix;
                    $row['Goods Name'] = $title;
                    $row['Quantity'] = 100000;
                    $row['Goods Description'] = $wishInfo['description'];
                    $row['Tags'] = $wishInfo['wishTags'];
                    $row['Market Price'] = ceil($sku['price'] * 5);
                    $row['Shop Price'] = $sku['price'];
                    $row['Shipping Fee'] = 0;
                    $row['Shipping Weight'] = $sku['weight'];
                    $row['Shipping Time'] = '15-45';
                    $row['Size'] = $sku['size'];
                    $row['Color'] = $sku['color'];
                    $row['Main Image URL'] = static::getWishMainImage($goodsInfo['goodsCode'], !empty($account) ? $account['mainImage'] : '0');
                    $row['Extra Image URL'] = $sku['linkUrl'];
                    $extraImages = explode("\n", $wishInfo['extraImages']);
                    $count = 1;
                    while ($count < 21) {
                        $row['Extra Image URL ' . $count] = isset($extraImages[$count - 1]) ? $extraImages[$count - 1] : '';
                        $count++;
                    }
                    $out[] = $row;
                }

            }
        }

        $ret['data'] = $out;
        return $ret;
    }

    public static function getVovaAccounts()
    {
        $ret = Store::find()->select('store')->where(['platForm' => 'VOVA'])->asArray()->all();
        $ret = ArrayHelper::getColumn($ret, 'store');
        return $ret;
    }

    public static function getSmtAccounts()
    {
        $sql = "SELECT selleruserid FROM public.aliexpress_user WHERE platform='aliexpress' ORDER BY selleruserid;";

        $res = Yii::$app->ibay->createCommand($sql)->queryAll();
        $ret = ArrayHelper::getColumn($res, 'selleruserid');
        return $ret;
    }

    public static function getSmtCategory()
    {
        $sql = "SELECT categoryid as id,pid,concat_ws('(',name,namecn) || ')' AS name FROM public.aliexpress_category;";

        $ret = Yii::$app->ibay->createCommand($sql)->queryAll();
        $data = Helper::tree($ret);
        return $data;
    }

    /**
     * Date: 2020-04-27 12:01
     * Author: henry
     * @return mixed
     * @throws \yii\db\Exception
     */
    public static function addSmtExportModel()
    {
        $username = Yii::$app->user->identity->username;
        $condition = Yii::$app->request->post()['condition'];
        $ids = isset($condition['ids']) && $condition['ids'] ? $condition['ids'] : [];
        $suffixList = isset($condition['suffix']) && $condition['suffix'] ? $condition['suffix'] : [];
        //先验证产品是否都完善
        foreach ($ids as $id) {
            $goodsInfo = OaGoodsinfo::findOne($id);
            if (strpos($goodsInfo['completeStatus'], 'aliexpress') === false) {
                return [
                    'code' => 400,
                    'msg' => '商品 ' . $goodsInfo['goodsCode'] . " 没有完善Aliexpress模板，加入导出队列失败! \n"
                ];
            }
        }

        foreach ($ids as $id) {
            $model = OaSmtGoods::findOne(['infoId' => $id]);
            foreach ($suffixList as $suffix) {
                $sql = "select * from proCenter.oa_smtImportToIbayLog where ibaySuffix=:suffix and sku=:sku and status1=0 and status2=0";
                $logQ = Yii::$app->db->createCommand($sql)->bindValues([':suffix' => $suffix, ':sku' => $model['sku']])->queryOne();
                $list = [
                    'ibaySuffix' => $suffix,
                    'sku' => $model['sku'],
                    'creator' => $username,
                    'createDate' => date('Y-m-d H:i:s')
                ];
                if (!$logQ) {
                    Yii::$app->db->createCommand()->insert('proCenter.oa_smtImportToIbayLog', $list)->execute();
                } else {
                    Yii::$app->db->createCommand()->update('proCenter.oa_smtImportToIbayLog',
                        ['creator' => $username, 'createDate' => date('Y-m-d H:i:s')],
                        ['ibaySuffix' => $suffix, 'sku' => $model['sku'], 'status1' => 0, 'status2' => 0])->execute();
                }
            }
        }
        return true;
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
    private static function getWishVariantInfo($isVar, $wishInfo, $wishSku, $account)
    {
        try {
            $price = ArrayHelper::getColumn($wishSku, 'price');
            $shippingPrice = ArrayHelper::getColumn($wishSku, 'shipping');
            $msrp = ArrayHelper::getColumn($wishSku, 'msrp');
            $len = count($price);
            $totalPrice = [];
            for ($i = 0; $i < $len; $i++) {
                $totalPrice[] = ceil($price[$i] + $shippingPrice[$i]);
            }

            //获取最大最小价格
            $maxPrice = max($totalPrice);
            $minPrice = min($totalPrice);
            $maxMsrp = ceil(max($msrp));

            //根据总价计算运费
            if ($minPrice <= 3) {
                $shipping = 1;
            } else {
                $shipping = ceil($minPrice * $account['rate']);
            }


            //打包变体
            $variation = [];
            foreach ($wishSku as $sku) {
                //价格判断
                $totalPrice = ceil($sku['price'] + $sku['shipping']);
                $sku['shipping'] = $shipping;

                // price - 0.01
                $sku['price'] = $totalPrice - $shipping < 1 ? 1 : ceil($totalPrice - $shipping);
                $sku['price'] -= 0.01;

                $var['sku'] = $sku['sku'] . $account['suffix'];
                $var['color'] = $sku['color'];
                $var['size'] = $sku['size'];
                $var['inventory'] = $sku['inventory'];
                $var['price'] = $sku['price'];
                $var['shipping'] = $sku['shipping'];
                $var['msrp'] = ceil($sku['msrp']);
                $var['shipping_time'] = $sku['shippingTime'];
                $var['main_image'] = $sku['linkUrl'];

                //美元账号
                if ($account['localCurrency'] === 'USD') {
                    $var['localized_currency_code'] = 'USD';
                    $var['localized_price'] = (string)$sku['price'];
                } // 人民币账号
                else {
                    $var['localized_currency_code'] = 'CNY';
                    $var['localized_price'] = floor((string)$sku['price'] * self::UsdExchange * 100) / 100;
                }
                $variation[] = $var;
            }
            $variant = json_encode($variation);
            $ret = [];
//            var_dump($variant);exit;
            if ($isVar === '是') {
                $ret['variant'] = $variant;

                # price -0.01
                $ret['price'] = $maxPrice - $shipping > 0 ? ceil($maxPrice - $shipping) : 1;
                $ret['price'] -= 0.01;
                $ret['shipping'] = $shipping;

                $ret['msrp'] = $maxMsrp;

                //美元账号
                if ($account['localCurrency'] === 'USD') {
                    $ret['local_price'] = $ret['price'];
                    $ret['local_shippingfee'] = $shipping;
                    $ret['local_currency'] = 'USD';
                } //人民币账号
                else {
                    $ret['local_price'] = floor($ret['price'] * self::UsdExchange * 100) / 100;
                    $ret['local_shippingfee'] = floor($shipping * self::UsdExchange * 100) / 100;
                    $ret['local_currency'] = 'CNY';
                }
            } else {
                $ret['variant'] = '';

                #price -0.01
                $ret['price'] = $maxPrice - $shipping > 0 ? ceil($maxPrice - $shipping) : 1;
                $ret['price'] -= 0.01;
                $ret['shipping'] = $shipping;

                $ret['msrp'] = $maxMsrp;

                //美元账号
                if ($account['localCurrency'] === 'USD') {
                    $ret['local_price'] = $ret['price'];
                    $ret['local_shippingfee'] = $shipping;
                    $ret['local_currency'] = 'USD';
                } // 人民币账号
                else {
                    $ret['local_price'] = floor($ret['price'] * self::UsdExchange * 100) / 100;
                    $ret['local_shippingfee'] = floor($shipping * self::UsdExchange * 100) / 100;
                    $ret['local_currency'] = 'CNY';
                }

            }
            return $ret;
        } catch (\Exception $why) {
            return ['variant' => '', 'price' => '', 'shipping' => '',
                'msrp' => '', 'local_price' => '', 'local_shippingfee' => '', 'local_currency' => ''];
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
            $ret = array_merge($head, $need, $tail);
            while (\strlen(implode(' ', $ret)) > $maxLength) {
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
        $ret = array_merge($head, $random_str1, $need, $random_arr, $tail);
        $ret = array_map(function ($ele) {
            return trim($ele);
        }, $ret);
        return implode(' ', $ret);
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
        $requireKeywords = !empty($info['requiredKeywords']) ? array_slice(json_decode($info['requiredKeywords']), 0, 6) : [];
        $randomKeywords = !empty($info['randomKeywords']) ? array_slice(json_decode($info['randomKeywords']), 0, 10) : [];
        $ret['need'] = $requireKeywords;
        $ret['random'] = $randomKeywords;
        return $ret;
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
        $mainImage = str_replace('/10023/', '/' . $account['imgCode'] . '/', $joomInfo['mainImage']);
        $extraImages = explode("\n", $joomInfo['extraImages']);
        $extraImages = array_filter($extraImages, function ($ele) {
            return strpos($ele, '-_00_') === false;
        });
        $extraImages = array_map(function ($ele) use ($account) {
            return str_replace('/10023/', '/' . $account['imgCode'] . '/', $ele);
        }, $extraImages);
        shuffle($extraImages);
        $countImages = count($extraImages);
        while ($countImages < 11) {
            $extraImages[] = '';
            $countImages++;
        }
        return ['mainImage' => $mainImage, 'extraImages' => $extraImages];
    }

    /**
     * @brief 判断joom属于哪种危险品
     * @param $goodsInfo
     * @return string
     */
    private static function getJoomDangerousKind($goodsInfo)
    {
        if ($goodsInfo['isLiquid'] == '是') {
            return 'liquid';
        }
        if ($goodsInfo['isPowder'] == '是') {
            return 'powder';
        }
        if ($goodsInfo['isMagnetism'] == '是') {
            return 'magnetizedItems';
        }
        if ($goodsInfo['isCharged'] == '是') {
            return 'withBattery';
        }
        return 'notDangerous';
    }

    /**
     * @brief 获取ebay价格信息
     * @param $ebayInfo
     * @return int
     */
    private static function getEbayPrice($ebayInfo)
    {
        $countrySite = OaSiteCountry::findOne(['name' => $ebayInfo['site']]);
        $skuPrice = ArrayHelper::getColumn($ebayInfo['oaEbayGoodsSku'], 'retailPrice');
        $maxPrice = max($skuPrice);
        $currencyCode = ($countrySite === null) ? 'USD' : $countrySite->code;
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
        $paypal = OaPaypal::findOne($ebayAccount['low']);
        if ($price >= 12) {
            $paypal = OaPaypal::findOne($ebayAccount['high']);
        }
        return $paypal ? $paypal['paypal'] : '';
    }

    /**
     * @brief 获取ebay的图片信息
     * @param $goodsInfo
     * @param $ebayInfo
     * @return string
     */
    private static function getEbayPicture($goodsInfo, $ebayInfo, $account)
    {
        $ebaySuffixCode = OaEbaySuffix::findOne(['ebaySuffix' => $account]);
        //print_r($account);exit;
        return 'https://www.tupianku.com/view/full/10023/' . $goodsInfo['goodsCode'] . '-_' .
            $ebaySuffixCode['mainImg'] . "_.jpg\n" . $ebayInfo['extraPage'];
    }

    /**
     * @brief 获取eBay描述
     * @param $description
     * @return string
     */
    private static function getEbayDescription($description)
    {
        return '<span style="font-family:Arial;font-size:14px;">' .
            str_replace("\n", '</br>', $description) . '</span>';
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
        if (!empty($shippingMethod)) {
            $shippingService = OaShippingService::findOne(['servicesName' => $shippingMethod]);
            if ($shippingService !== null) {
                return $shippingService->ibayShipping;
            }
        }
        return '';


    }

    /**
     * @brief 封装ebay多属性信息
     * @param $isVar
     * @param $skuInfo
     * @param $account
     * @return string
     *
     */
    private static function getEbayVariation($isVar, $ebayInfo, $account)
    {
        $skuInfo = $ebayInfo['oaEbayGoodsSku'];
        if ($isVar === '否') {
            return '';
        }
        // 判断属性是否全为空
        $propertyFlag = static::isEmpetyProperty($skuInfo);

        $pictures = [];
        $variation = [];
        $variationSpecificsSet = ['NameValueList' => []];
        foreach ($skuInfo as $sku) {
            $columns = json_decode($sku['property'], true)['columns'];
            $picKey = json_decode($sku['property'], true)['pictureKey'] ?: 'Color';
            $value = ['value' => ''];
            foreach ($columns as $col) {
                if (array_keys($col)[0] === ucfirst($picKey)) {
                    $value['value'] = $col[ucfirst($picKey)];
                    break;
                }
            }
            $item = [];
            foreach ($columns as $col) {

                //不全为空的属性才加入NameValueList
                if ($propertyFlag[array_keys($col)[0]] > 0) {
                    $map = ['Name' => array_keys($col)[0], 'Value' => array_values($col)[0]];
                    $item[] = $map;
                }
            }
            $variationSpecificsSet['NameValueList'] = $item;
            $pic = ['VariationSpecificPictureSet' => ['PictureURL' => [$sku['imageUrl']]], 'Value' => $value['value']];
            $pictures[] = $pic;
            $var = [
                'SKU' => $sku['sku'] . $account,
                'Quantity' => $sku['quantity'],
                'StartPrice' => $sku['retailPrice'],
                'VariationSpecifics' => $variationSpecificsSet,
            ];
            $variation[] = $var;
        }
        $extraImages = explode("\n", $ebayInfo['extraPage']);
        $row = [
            'assoc_pic_key' => $picKey, 'assoc_pic_count' => count($extraImages), 'Variation' => $variation,
            'Pictures' => $pictures, 'VariationSpecificsSet' => $variationSpecificsSet
        ];
        return json_encode($row);
    }

    /**
     *
     */
    private static function isEmpetyProperty($ebaySku)
    {
        // 取出所有的属性名称
        $keys = json_decode($ebaySku[0]['property'], true)['columns'];
        $propertyFlag = [];
        foreach ($keys as $rows) {
            $propertyFlag[array_keys($rows)[0]] = 0;
        }

        //逐个判断每个属性是否全为空
        foreach ($propertyFlag as $pty => $flag) {
            foreach ($ebaySku as $sku) {
                $property = json_decode($sku['property'], true)['columns'];
                $property = static::flatArray($property);
                if (!empty($property[$pty])) {
                    $propertyFlag[$pty] = 1;
                    break;
                }
            }
        }
        return $propertyFlag;

    }

    /**
     * @brief 压平数组
     * @param $property
     * @return array
     */
    private static function flatArray($property)
    {
        $ret = [];
        foreach ($property as $pty) {
            foreach ($pty as $key => $value) {
                $ret[$key] = $value;
            }
        }
        return $ret;
    }

    /**
     * @brief 平台信息完善状态过滤
     * @param $query
     * @param $condition
     * @return mixed
     */
    public static function completedStatusFilter($query, $condition)
    {
        if (isset($condition['completeStatus']) && !empty($condition['completeStatus'])) {
            $status = $condition['completeStatus'];
            if (in_array('未设置', $status)) {
                $status = array_filter($status, function ($ele) {
                    return $ele !== '未设置';
                });
                asort($status);
                if (empty($status)) {
                    $query->andWhere(['is', 'completeStatus', null]);
                    return $query;
                } else {
                    $map = ['or', ['is', 'completeStatus', null]];
                    foreach ($status as $v) {
                        $map[] = ['like', 'completeStatus', $v];
                    }
                    $query->andWhere($map);
                    return $query;
                }

            } else {
                asort($status);
                $map = ['or'];
                foreach ($status as $v) {
                    $map[] = ['like', 'completeStatus', $v];
                }
                $query->andWhere($map);
                return $query;
            }
        }
        return $query;
    }

    public static function forbidPlatFilter($query, $condition)
    {
        //todo 禁售平台过滤
        if (isset($condition['dictionaryName']) && !empty($condition['dictionaryName'])) {
            $status = $condition['dictionaryName'];
            if (in_array('未设置', $status)) {
                $status = array_filter($status, function ($ele) {
                    return $ele !== '未设置';
                });
                asort($status);
                if (empty($status)) {
                    $query->andWhere(['=', "ifnull(dictionaryName,'')", '']);
                    return $query;
                } else {
                    $status = implode(',', $status);
                    $query->andWhere(['or', ['=', "ifnull(dictionaryName,'')", ''], ['like', 'dictionaryName', $status]]);
                    return $query;
                }

            } else {
                asort($status);
                $status = implode(',', $status);
                $query->andWhere(['=', 'dictionaryName', $status]);
                return $query;
            }
        }
        return $query;
    }

    /**
     * @计算joom申报价
     * @param $price
     * @return float|int
     */
    private static function getJoomDeclaredValue($price)
    {
        if ($price > 0 && $price <= 1) {
            return 0.1;
        }
        if ($price > 1 && $price <= 2) {
            return 0.5;
        }
        if ($price > 2 && $price <= 5) {
            return 1.2;
        }
        if ($price > 5 && $price <= 20) {
            return 2;
        }
        if ($price > 20) {
            return 3;
        }
    }

    /**
     * @brief shopify Tags
     * @param $tags
     * @param $title
     * @return string
     */
    private static function getShopifyTag($tags, $title)
    {
        $out = [];
        $tags = explode(',', $tags);
        foreach ($tags as $tg) {
            if (stripos($title, $tg) !== false) {
                $out[] = $tg;
            }
        }
        return implode(', ', $out);
    }

    /**
     * @brief 判断option name
     * @param $position
     * @param $sku
     * @param $name
     * @return string
     */
    private static function getShopifyOptionName($position, $sku, $name)
    {
        if ($position > 1) {
            return '';
        } else {
            if (empty($sku[strtolower($name)])) {
                return '';
            }
            return $name;
        }

    }
}
