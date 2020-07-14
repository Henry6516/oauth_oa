<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2018-09-07 10:00
 */

namespace backend\modules\v1\controllers;


use backend\models\OaEbayKeyword;
use backend\models\ShopElf\BGoods;
use backend\modules\v1\models\ApiAu;
use backend\modules\v1\models\ApiGoodsinfo;
use backend\modules\v1\models\ApiSettings;
use backend\modules\v1\models\ApiTinyTool;
use backend\modules\v1\models\ApiUk;
use backend\modules\v1\models\ApiUkFic;
use backend\modules\v1\models\ApiUser;
use backend\modules\v1\utils\ExportTools;
use Codeception\Template\Api;
use common\models\User;
use backend\modules\v1\services\ExpressExpired;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Yii;
use yii\data\ArrayDataProvider;
use yii\data\SqlDataProvider;
use yii\db\Exception;
use yii\helpers\ArrayHelper;
use yii\mongodb\Query;

class TinyToolController extends AdminController
{
    public $modelClass = 'backend\modules\v1\models\ApiTinyTool';

    public $serializer = [
        'class' => 'backend\modules\v1\utils\PowerfulSerializer',
        'collectionEnvelope' => 'items',
    ];

    public function behaviors()
    {
        return parent::behaviors();
    }

    public function actionExpressTracking()
    {
        $request = Yii::$app->request;
        $condition = $request->post('condition');
        return ApiTinyTool::expressTracking($condition);
    }

    /**
     * @brief show express info
     * @return array
     */
    public function actionExpress()
    {
        return ApiTinyTool::express();
    }

    /**
     * @brief brand list
     * @return array
     */
    public function actionBrand()
    {
        $post = \Yii::$app->request->post();
        $condition = $post['condition'];

        return ApiTinyTool::getBrand($condition);
    }

    /**
     * @brief show goods picture
     * @return array
     */
    public function actionGoodsPicture()
    {
        $post = \Yii::$app->request->post();
        $condition = $post['condition'];
        return ApiTinyTool::getGoodsPicture($condition);
    }

    /**
     * @brief modify declared value
     * @return array
     */
    public function actionDeclaredValue()
    {
        $post = \Yii::$app->request->post();
        $condition = $post['condition'];
        return ApiTinyTool::modifyDeclaredValue($condition);
    }


    /**
     * @brief fyndiq upload csv to backend
     * @return array
     * @throws \Exception
     */
    public function actionFyndiqzUpload()
    {
        $file = $_FILES['file'];
        return ApiTinyTool::FyndiqzUpload($file);

    }

    /**
     * @brief set password
     */
    public function actionSetPassword()
    {
        $post = Yii::$app->request->post()['condition'];
        $username = Yii::$app->user->identity->username;
        $password = $post['password'];
        try {
            $one = User::findOne(['username' => $username]);
            if (!empty($one)) {
                $one->password_reset_token = $password;
                $one->setPassword($password);//设置新密码
                $one->generateApiToken();//生成新的TOKEN
                $ret = $one->save();
                if (!$ret) {
                    throw new \Exception("fail to set $username");
                }
                return 'job done!';
            } else {
                throw new \Exception("Cant't find user '{$username}''");
            }
        } catch (\Exception  $why) {
            return [$why];
        }

    }

    /**
     * @brief set password
     */
    public function actionResetPassword()
    {
        $post = Yii::$app->request->post()['condition'];
        $username = $post['username'];
        $password = $post['password'];
        try {
            $one = User::findOne(['username' => $username]);
            if (!empty($one)) {
                $one->password_reset_token = $password;
                $one->setPassword($password);//设置新密码
                $one->generateApiToken();//生成新的TOKEN
                $ret = $one->save();
                if (!$ret) {
                    throw new \Exception("fail to set $username");
                }
                return 'job done!';
            } else {
                throw new \Exception("Cant't find user '{$username}''");
            }
        } catch (\Exception  $why) {
            return [$why];
        }


    }

    //====================================================
    //海外仓定价器

    /**
     * @brief UK 虚拟仓定价器
     */
    public function actionUkFic()
    {
        $request = Yii::$app->request->post();
        $cond = $request['condition'];
        if (!$cond['sku']) {
            return [
                'code' => 400,
                'message' => 'The SKU attribute can not be empty!',
            ];
        }
        $post = [
            'sku' => $cond['sku'],
            'num' => $cond['num'] ? $cond['num'] : 1,
            'price' => $cond['price'],
            'rate' => $cond['rate'],
        ];

        $data = [
            'detail' => [],
            'rate' => [],
            'price' => [],
            'transport' => [],
        ];
        //获取SKU信息
        //$sql = "EXEC ibay365_ebay_virtual_store_online_product '{$post['sku']}'";
        $sql = "SELECT 
                    r.SKU,r.skuname,r.goodscode,r.Weight,r.CategoryName,r.CreateDate,
                    CASE WHEN r.costprice<=0 THEN r.goodsPrice ELSE r.costprice END costprice
                FROM Y_R_tStockingWaring  r
                LEFT JOIN B_Goods g ON g.goodscode = r.goodscode
                -- LEFT JOIN B_PackInfo s ON g.packName = s.packName
                WHERE r.SKU='{$post['sku']}' ";
        $res = Yii::$app->py_db->createCommand($sql)->queryOne();
        if (!$res) return $data;

        $post['num'] = $post['num'] ? $post['num'] : 1;
        $post['rate'] = $post['rate'] ? $post['rate'] : 0;

        $res['num'] = $post['num'];

        $res['costprice'] = $res['costprice'] * $post['num'];
        $res['Weight'] = $res['Weight'] * $post['num'];
        $data['detail'] = $res;

        //欧速通-英伦速邮
        $name = Yii::$app->params['transport1'];
        if ($res['Weight'] < Yii::$app->params['weight']) {
            $cost = Yii::$app->params['swBasic'] + Yii::$app->params['swPrice'] * $res['Weight'];
        } else {
            $cost = $cost = Yii::$app->params['bwBasic'] + Yii::$app->params['bwPrice'] * $res['Weight'];
        }

        //CNE-全球优先
        $name2 = Yii::$app->params['transport2'];
        $cost2 = Yii::$app->params['wBasic1'] + Yii::$app->params['price1'] * $res['Weight'];


        //欧速通-英伦速邮追踪
        $name3 = Yii::$app->params['transport3'];
        if ($res['Weight'] < Yii::$app->params['weight3']) {
            $cost3 = Yii::$app->params['wBasic2'] + Yii::$app->params['price2'] * $res['Weight'];
        } else {
            $cost3 = Yii::$app->params['wBasic2'] + Yii::$app->params['price3'] * $res['Weight'];
        }

        //英伦速邮挂号
        $name4 = Yii::$app->params['transport5'];
        if ($res['Weight'] < Yii::$app->params['weight5']) {
            $cost4 = Yii::$app->params['swBasic5'] + Yii::$app->params['swPrice5'] * $res['Weight'];
        } else {
            $cost4 = Yii::$app->params['bwBasic5'] + Yii::$app->params['bwPrice5'] * $res['Weight'];
        }

        $param1 = $param2 = $param3 = $param4 = [
            'costprice' => $res['costprice'],
            'bigPriceBasic' => Yii::$app->params['bpBasic'],
            'smallPriceBasic' => Yii::$app->params['spBasic'],
            'bigPriceRate' => Yii::$app->params['bpRate'],
            'smallPriceRate' => Yii::$app->params['spRate'],
            'ebayRate' => Yii::$app->params['eRate'],
        ];
        $param1['cost'] = $cost;
        $param2['cost'] = $cost2;
        $param3['cost'] = $cost3;
        $param4['cost'] = $cost4;
        //根据售价获取利润率
        if ($post['price']) {
            $param1['price'] = $param2['price'] = $param3['price'] = $param4['price'] = $post['price'];

            $rate = ApiUkFic::getRate($param1);
            $rate['transport'] = $name;

            $rate2 = ApiUkFic::getRate($param2);
            $rate2['transport'] = $name2;

            $rate3 = ApiUkFic::getRate($param3);
            $rate3['transport'] = $name3;

            $rate4 = ApiUkFic::getRate($param4);
            $rate4['transport'] = $name4;
            $data['rate'] = [$rate, $rate2, $rate3, $rate4];
        }
        //根据利润率获取售价
        $param1['rate'] = $param2['rate'] = $param3['rate'] = $param4['rate'] = $post['rate'];
        $price = ApiUkFic::getPrice($param1);
        $price['transport'] = $name;

        $price2 = ApiUkFic::getPrice($param2);
        $price2['transport'] = $name2;

        $price3 = ApiUkFic::getPrice($param3);
        $price3['transport'] = $name3;

        $price4 = ApiUkFic::getPrice($param4);
        $price4['transport'] = $name4;

        $data['price'] = [$price, $price2, $price3, $price4];
        //print_r($data['price']);exit;
        $data['transport'] = [
            [
                'name' => $name,
                'cost' => round($cost, 2),
            ],
            [
                'name' => $name2,
                'cost' => round($cost2, 2),
            ],
            [
                'name' => $name3,
                'cost' => round($cost3, 2),
            ],
            [
                'name' => $name4,
                'cost' => round($cost4, 2),
            ]
        ];
        //print_r($data);exit;
        return $data;
    }

    /**
     * @brief UK 虚拟仓定价器2(所有国家)
     */
    public function actionUkFic2()
    {
        $request = Yii::$app->request->post();
        $cond = $request['condition'];
        if (!$cond['sku']) {
            return [
                'code' => 400,
                'message' => 'The SKU attribute can not be empty!',
            ];
        }
        $post = [
            'sku' => $cond['sku'],
            'num' => $cond['num'] ? $cond['num'] : 1,
            'price' => $cond['price'] ? $cond['price'] : 0,
            'rate' => $cond['rate'] ? $cond['rate'] : 0,
        ];

        $data = [
            'detail' => [],
            'data' => [],
        ];
        //获取SKU信息
        //$sql = "EXEC ibay365_ebay_virtual_store_online_product '{$post['sku']}'";
        $sql = "SELECT 
                    r.SKU,r.skuname,r.goodscode,r.Weight,r.CategoryName,r.CreateDate,
                    CASE WHEN r.costprice<=0 THEN r.goodsPrice ELSE r.costprice END costprice
                FROM Y_R_tStockingWaring  r
                LEFT JOIN B_Goods g ON g.goodscode = r.goodscode
                -- LEFT JOIN B_PackInfo s ON g.packName = s.packName
                WHERE r.SKU='{$post['sku']}' ";
        $res = Yii::$app->py_db->createCommand($sql)->queryOne();
        if (!$res) return $data;

        $post['num'] = $post['num'] ? $post['num'] : 1;
        $post['rate'] = $post['rate'] ? $post['rate'] : 0;

        $res['num'] = $post['num'];

        $res['costprice'] = $res['costprice'] * $post['num'];
        $res['Weight'] = $res['Weight'] * $post['num'];
        $data['detail'] = $res;

        //获取物流方式及其报价

        $list = Yii::$app->db->createCommand("SELECT * FROM shipping_countFee ORDER BY country")->queryAll();
        foreach ($list as $v) {
            if ($v['weight1'] && $v['wBasic'] && $v['wPrice'] && $v['startWeight'] <= $res['Weight'] && $res['Weight'] < $v['weight1']) {
                $cost = $v['wBasic'] + $v['wPrice'] * $res['Weight'];
            } elseif ($v['weight2'] && $v['wBasic1'] && $v['wPrice1'] && $res['Weight'] < $v['weight2']) {
                $cost = $v['wBasic1'] + $v['wPrice1'] * $res['Weight'];
            } elseif ($v['weight3'] && $v['wBasic2'] && $v['wPrice2'] && $res['Weight'] < $v['weight3']) {
                $cost = $v['wBasic2'] + $v['wPrice2'] * $res['Weight'];
            } else {
                $cost = 0;
            }

            $item = [
                'country' => $v['country'],
                'transport' => $v['shipping'],
                'cost' => $cost,
                'price1' => $post['price'],
                'eFee1' => 0,
                'pFee1' => 0,
                'profit1' => 0,
                'profitRmb1' => 0,
                'rate1' => 0,
                'price2' => 0,
                'eFee2' => 0,
                'pFee2' => 0,
                'profit2' => 0,
                'profitRmb2' => 0,
                'rate2' => $post['rate'],
            ];
            $param = [
                'cost' => $cost,
                'costprice' => $res['costprice'],
                'bigPriceBasic' => $v['bigPriceBasic'],
                'smallPriceBasic' => $v['smallPriceBasic'],
                'bigPriceRate' => $v['bigPriceRate'],
                'smallPriceRate' => $v['smallPriceRate'],
                'ebayRate' => $v['ebayRate'],
            ];
            //根据售价获取利润率
            if ($post['price']) {
                $param['price'] = $post['price'];
                $rate = ApiUkFic::getRate($param);
                $item['eFee1'] = $rate['eFee'];
                $item['pFee1'] = $rate['pFee'];
                $item['profit1'] = $rate['profit'];
                $item['profitRmb1'] = $rate['profitRmb'];
                $item['rate1'] = $rate['rate'];
            }
            //根据利润率获取售价
            $param['rate'] = $post['rate'];
            $price = ApiUkFic::getPrice($param);
            $item['price2'] = $price['price'];
            $item['eFee2'] = $price['eFee'];
            $item['pFee2'] = $price['pFee'];
            $item['profit2'] = $price['profit'];
            $item['profitRmb2'] = $price['profitRmb'];
            //print_r($item);exit;
            $data['data'][] = $item;
        }
        //print_r($data);exit;
        return $data;
    }

    /**
     * UK 真仓定价器
     * @return array
     */
    public function actionUk()
    {
        $request = Yii::$app->request->post();
        $cond = $request['condition'];
        if (!$cond['sku']) {
            return [
                'code' => 400,
                'message' => 'The SKU attribute can not be empty!',
            ];
        }
        $post = [
            'sku' => $cond['sku'],
            'num' => $cond['num'] ? $cond['num'] : 1,
            'price' => $cond['price'] ? $cond['price'] : 0,
            'rate' => $cond['rate'] ? $cond['rate'] : 0,
        ];
        $data = [
            'detail' => [],
            'rate' => [],
            'price' => [],
            'transport' => [],
        ];
        //获取SKU信息
        $res = ApiUk::getDetail($post['sku'], $post['num']);
        //print_r($res);exit;
        if (!$res) return $data;


        $data['detail'] = $res;
        $res['num'] = $post['num'];
        $res['price'] = array_sum(ArrayHelper::getColumn($res, 'price'));
        $res['weight'] = array_sum(ArrayHelper::getColumn($res, 'weight'));
        $res['height'] = array_sum(ArrayHelper::getColumn($res, 'height'));
        $res['length'] = max(ArrayHelper::getColumn($res, 'length'));
        $res['width'] = max(ArrayHelper::getColumn($res, 'width'));
        //获取运费和出库费
        $data['transport'] = ApiUk::getTransport($res['weight'], $res['length'], $res['width'], $res['height']);

        foreach ($data['transport'] as $v) {
            //根据售价获取利润率
            if ($post['price']) {
                $rateItem = ApiUk::getRate($post['price'], $v['cost'], $v['out'], $res['price']);
                $rateItem['name'] = $v['name'];
                $data['rate'][] = $rateItem;
            }
            //根据利润率获取售价
            $priceItem = ApiUk::getPrice($post['rate'], $v['cost'], $v['out'], $res['price']);
            $priceItem['name'] = $v['name'];
            $data['price'][] = $priceItem;
        }

        //print_r($data);exit;
        return $data;
    }

    /**
     * AU 真仓定价器
     * @return array
     */
    public function actionAu()
    {
        $request = Yii::$app->request->post();
        $cond = $request['condition'];
        if (!$cond['sku']) {
            return [
                'code' => 400,
                'message' => 'The SKU attribute can not be empty!',
            ];
        }
        $post = [
            'sku' => $cond['sku'],
            'num' => $cond['num'] ? $cond['num'] : 1,
            'price' => $cond['price'],
            'rate' => $cond['rate'],
        ];
        $data = [
            'detail' => [],
            'rate' => [],
            'price' => [],
            'transport' => [],
        ];
        //获取SKU信息
        //获取SKU信息
        $res = ApiAu::getDetail($post['sku'], $post['num']);
        if (!$res) return $data;

        $post['num'] = $post['num'] ? $post['num'] : 1;
        $post['rate'] = $post['rate'] ? $post['rate'] : 0;

        $data['detail'] = $res;
        $res['num'] = $post['num'];
        $res['price'] = array_sum(ArrayHelper::getColumn($res, 'price'));
        $res['weight'] = array_sum(ArrayHelper::getColumn($res, 'weight'));
        $res['height'] = array_sum(ArrayHelper::getColumn($res, 'height'));
        $res['length'] = max(ArrayHelper::getColumn($res, 'length'));
        $res['width'] = max(ArrayHelper::getColumn($res, 'width'));

        //获取运费和出库费
        $data['transport'] = ApiAu::getTransport($res['weight'], $res['length'], $res['width'], $res['height']);

        //根据售价获取利润率
        if ($post['price']) {
            $data['rate'] = ApiAu::getRate($post['price'], $data['transport']['cost'], $data['transport']['out'], $res['price']);
        }
        //根据利润率获取售价
        $data['price'] = ApiAu::getPrice($post['rate'], $data['transport']['cost'], $data['transport']['out'], $res['price']);

        //print_r($data);exit;
        return $data;
    }


    /**
     * @brief display exception payPal
     * @return array
     * @throws \yii\db\Exception
     */
    public function actionExceptionPayPal()
    {
        $request = Yii::$app->request->post();
        $cond = $request['condition'];
        return ApiTinyTool::getExceptionPayPal($cond);
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    public function actionRiskyOrder()
    {
        $request = Yii::$app->request;
        $cond = $request->post()['condition'];
        return ApiTinyTool::getRiskyOrder($cond);
    }

    /**
     * @return array
     * @throws \yii\db\Exception
     */
    public function actionHandleRiskyOrder()
    {
        $request = Yii::$app->request;
        $data = $request->post()['data'];
        return ApiTinyTool::handleRiskyOrder($data);
    }

    /**
     * @brief display and edit blacklist
     * @return array|mixed
     */
    public function actionBlacklist()
    {
        $request = Yii::$app->request;
        if ($request->isGet) {
            $cond = $request->get();
            return ApiTinyTool::getBlacklist($cond);
        }
        if ($request->isPost) {
            $data = $request->post()['data'];
            return ApiTinyTool::saveBlacklist($data);
        }
        if ($request->isDelete) {
            $id = $request->get()['id'];
            return ApiTinyTool::deleteBlacklist($id);
        }
    }

    public function actionExceptionEdition()
    {
        $request = Yii::$app->request->post();
        $cond = $request['condition'];
        return ApiTinyTool::getExceptionEdition($cond);
    }

    public function actionEbayVirtualStore()
    {
        $request = Yii::$app->request->post();
        $cond = $request['condition'];
        return ApiTinyTool::getEbayVirtualStore($cond);
    }

    /**
     * @brief 超时物流
     * @return array|mixed
     */
    public function actionExpressExpired()
    {
        try {
            return ExpressExpired::run();
        } catch (\Exception $why) {
            return ['message' => $why->getMessage(), 'code' => $why->getCode()];
        }
    }

//========================================================
    //海外仓补货

    /** UK虚拟仓补货
     * Date: 2019-05-28 16:31
     * Author: henry
     * @return array|ArrayDataProvider
     */
    public function actionUkReplenish()
    {
        $request = Yii::$app->request->post();
        $cond = $request['condition'];
        try {
            /*$sql = "SELECT SKU, SKUName, goodsCode, salerName, goodsStatus, purchaser, supplierName,
                        saleNum3days, saleNum7days, saleNum15days, saleNum30days, trend, saleNumDailyAve, hopeUseNum,
                        amount, totalHopeUN, hopeSaleDays, purchaseNum, price, purCost
                    FROM cache_overseasReplenish WHERE type='UK虚拟仓'";
            if (isset($cond['sku']) && $cond['sku']) $sql .= " AND SKU LIKE '%{$cond['sku']}%'";
            if (isset($cond['salerName']) && $cond['salerName']) $sql .= " AND salerName LIKE '%{$cond['salerName']}%'";
            if (isset($cond['purchaser']) && $cond['purchaser']) $sql .= " AND purchaser LIKE '%{$cond['purchaser']}%'";
            if (isset($cond['trend']) && $cond['trend']) $sql .= " AND trend LIKE '%{$cond['trend']}%'";
            if (isset($cond['isPurchaser']) && $cond['isPurchaser'] == '是') $sql .= " AND purchaseNum>0 ";
            if (isset($cond['isPurchaser']) && $cond['isPurchaser'] == '否') $sql .= " AND purchaseNum=0 ";
            $data = Yii::$app->db->createCommand($sql)->queryAll();*/
            $sql = "EXEC  [dbo].[LY_eBayUKVirtualWarehouse_Replenishment_20191113] '{$cond['salerName']}','{$cond['purchaser']}'";
            $data = Yii::$app->py_db->createCommand($sql)->queryAll();
            $totalPurCost = array_sum(ArrayHelper::getColumn($data, 'purCost'));

            $provider = new ArrayDataProvider([
                'allModels' => $data,
                'pagination' => [
                    'pageSize' => isset($cond['pageSize']) && $cond['pageSize'] ? $cond['pageSize'] : 20,
                ],
            ]);
            return ['provider' => $provider, 'extra' => ['totalPurCost' => $totalPurCost]];
        } catch (\Exception $why) {
            return ['message' => $why->getMessage(), 'code' => $why->getCode()];
        }

    }

    /** AU真仓补货
     * Date: 2019-05-29 8:40
     * Author: henry
     * @return array|ArrayDataProvider
     */
    public function actionAuRealReplenish()
    {
        $request = Yii::$app->request->post();
        $cond = $request['condition'];
        try {
//            $sql = "SELECT SKU, SKUName, goodsCode, salerName, goodsStatus, price, weight, purchaser, supplierName,
//                        saleNum3days, saleNum7days, saleNum15days, saleNum30days, trend, saleNumDailyAve, 399HopeUseNum,
//                        uHopeUseNum, totalHopeUseNum, uHopeSaleDays, hopeSaleDays, purchaseNum, shipNum, purCost, shipWeight
//                    FROM cache_overseasReplenish WHERE type='AU真仓'";
//            if (isset($cond['sku']) && $cond['sku']) $sql .= " AND SKU LIKE '%{$cond['sku']}%'";
//            if (isset($cond['salerName']) && $cond['salerName']) $sql .= " AND salerName LIKE '%{$cond['salerName']}%'";
//            if (isset($cond['purchaser']) && $cond['purchaser']) $sql .= " AND purchaser LIKE '%{$cond['purchaser']}%'";
//            if (isset($cond['trend']) && $cond['trend']) $sql .= " AND trend LIKE '%{$cond['trend']}%'";
//            if (isset($cond['isPurchaser']) && $cond['isPurchaser'] == '是') $sql .= " AND purchaseNum>0 ";
//            if (isset($cond['isPurchaser']) && $cond['isPurchaser'] == '否') $sql .= " AND purchaseNum=0 ";
//            if (isset($cond['isShipping']) && $cond['isShipping'] == '是') $sql .= " AND shipNum>0 ";
//            if (isset($cond['isShipping']) && $cond['isShipping'] == '否') $sql .= " AND shipNum=0 ";
//            $data = Yii::$app->db->createCommand($sql)->queryAll();
            $data = Yii::$app->py_db->createCommand("EXEC LY_eBayAURealWarehouse_Replenishment_20191105 '{$cond['salerName']}','{$cond['purchaser']}';")->queryAll();
            $totalPurCost = array_sum(ArrayHelper::getColumn($data, 'purCost'));
            $totalShipWeight = array_sum(ArrayHelper::getColumn($data, 'shipWeight'));

            $provider = new ArrayDataProvider([
                'allModels' => $data,
                'pagination' => [
                    'pageSize' => isset($cond['pageSize']) && $cond['pageSize'] ? $cond['pageSize'] : 20,
                ],
            ]);
            return ['provider' => $provider, 'extra' => ['totalPurCost' => $totalPurCost, 'totalShipWeight' => $totalShipWeight]];
        } catch (\Exception $why) {
            return ['message' => $why->getMessage(), 'code' => $why->getCode()];
        }
    }

    /** AU真仓补货
     * Date: 2019-05-29 8:40
     * Author: henry
     * @return array|ArrayDataProvider
     */
    public function actionUkRealReplenish()
    {
        $request = Yii::$app->request->post();
        $cond = $request['condition'];
        try {
            /*$sql = "SELECT SKU, SKUName, goodsCode, salerName, goodsStatus, price, weight, purchaser, supplierName,
                        saleNum3days, saleNum7days, saleNum15days, saleNum30days, trend, saleNumDailyAve, 399HopeUseNum,
                        uHopeUseNum, totalHopeUseNum, uHopeSaleDays, hopeSaleDays, purchaseNum, shipNum, purCost, shipWeight
                    FROM cache_overseasReplenish WHERE type='UK真仓'";
            if (isset($cond['sku']) && $cond['sku']) $sql .= " AND SKU LIKE '%{$cond['sku']}%'";
            if (isset($cond['salerName']) && $cond['salerName']) $sql .= " AND salerName LIKE '%{$cond['salerName']}%'";
            if (isset($cond['purchaser']) && $cond['purchaser']) $sql .= " AND purchaser LIKE '%{$cond['purchaser']}%'";
            if (isset($cond['trend']) && $cond['trend']) $sql .= " AND trend LIKE '%{$cond['trend']}%'";
            if (isset($cond['isPurchaser']) && $cond['isPurchaser'] == '是') $sql .= " AND purchaseNum>0 ";
            if (isset($cond['isPurchaser']) && $cond['isPurchaser'] == '否') $sql .= " AND purchaseNum=0 ";
            if (isset($cond['isShipping']) && $cond['isShipping'] == '是') $sql .= " AND shipNum>0 ";
            if (isset($cond['isShipping']) && $cond['isShipping'] == '否') $sql .= " AND shipNum=0 ";
            $data = Yii::$app->db->createCommand($sql)->queryAll();*/
            $data = Yii::$app->py_db->createCommand("EXEC LY_eBayUKRealWarehouse_Replenishment_20191105 '{$cond['salerName']}','{$cond['purchaser']}';")->queryAll();
            $totalPurCost = array_sum(ArrayHelper::getColumn($data, 'purCost'));
            $totalShipWeight = array_sum(ArrayHelper::getColumn($data, 'shipWeight'));

            $provider = new ArrayDataProvider([
                'allModels' => $data,
                'pagination' => [
                    'pageSize' => isset($cond['pageSize']) && $cond['pageSize'] ? $cond['pageSize'] : 20,
                ],
            ]);
            return ['provider' => $provider, 'extra' => ['totalPurCost' => $totalPurCost, 'totalShipWeight' => $totalShipWeight]];
        } catch (\Exception $why) {
            return ['message' => $why->getMessage(), 'code' => $why->getCode()];
        }
    }


    /** eBayUK虚拟海外仓补货
     * Date: 2019-08-01 16:40
     * Author: henry
     * @return array|ArrayDataProvider
     */
    public function actionUkVirtualReplenish()
    {
        $request = Yii::$app->request->post();
        $cond = $request['condition'];
        try {
            $sql = "EXEC  [guest].[LY_eBayUK_Replenishment] '{$cond['salerName']}','{$cond['purchaser']}'";
            $data = Yii::$app->py_db->createCommand($sql)->queryAll();
            //$totalPurCost = array_sum(ArrayHelper::getColumn($data, 'purCost'));
            //$totalShipWeight = array_sum(ArrayHelper::getColumn($data, 'shipWeight'));

            $provider = new ArrayDataProvider([
                'allModels' => $data,
                'pagination' => [
                    'pageSize' => isset($cond['pageSize']) && $cond['pageSize'] ? $cond['pageSize'] : 20,
                ],
            ]);
            //return ['provider' => $provider, 'extra' => ['totalPurCost' => $totalPurCost, 'totalShipWeight' => $totalShipWeight]];
            return $provider;
        } catch (\Exception $why) {
            return ['message' => $why->getMessage(), 'code' => $why->getCode()];
        }
    }


//=========================================================
//海外仓补货表格下载

    /** 下载表格
     * Date: 2019-08-05 10:18
     * Author: henry
     * @return array
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     * @throws \yii\db\Exception
     */
    public function actionExportReplenish()
    {
        $request = Yii::$app->request;
        if (!$request->isPost) {
            return [];
        }
        $cond = $request->post()['condition'];

        switch ($cond['type']) {
            case 'uk':
                $name = 'ukVirtualReplenish';
                /*$sql = "SELECT SKU, SKUName, goodsCode, salerName, goodsStatus, purchaser, supplierName,
                        saleNum3days, saleNum7days, saleNum15days, saleNum30days, trend, saleNumDailyAve, hopeUseNum,
                        amount, totalHopeUN, hopeSaleDays, purchaseNum, price, purCost
                    FROM cache_overseasReplenish WHERE type='UK虚拟仓'";
                if (isset($cond['sku']) && $cond['sku']) $sql .= " AND SKU LIKE '%{$cond['sku']}%'";
                if (isset($cond['salerName']) && $cond['salerName']) $sql .= " AND salerName LIKE '%{$cond['salerName']}%'";
                if (isset($cond['purchaser']) && $cond['purchaser']) $sql .= " AND purchaser LIKE '%{$cond['purchaser']}%'";
                if (isset($cond['trend']) && $cond['trend']) $sql .= " AND trend LIKE '%{$cond['trend']}%'";
                if (isset($cond['isPurchaser']) && $cond['isPurchaser'] == '是') $sql .= " AND purchaseNum>0 ";
                if (isset($cond['isPurchaser']) && $cond['isPurchaser'] == '否') $sql .= " AND purchaseNum=0 ";
                $data = Yii::$app->db->createCommand($sql)->queryAll();*/
                $sql = "EXEC  [dbo].[LY_eBayUKVirtualWarehouse_Replenishment_20191113] '{$cond['salerName']}','{$cond['purchaser']}'";
                $data = Yii::$app->py_db->createCommand($sql)->queryAll();
                $title = ['SKU', 'SKU名称', '商品编码', '开发员', '状态', '采购', '供应商', '3天销量', '7天销量', '15天销量', '30天销量',
                    '走势', '日均销量', '预计可用库存', '义乌仓库存', '义乌仓采购未审核', '预计可卖天数', '采购数量', '单价', '采购金额'];
                break;
            case 'auReal':
                $name = 'auRealReplenish';
                /*$sql = "SELECT SKU, SKUName, goodsCode, salerName, goodsStatus, price, weight, purchaser, supplierName,
                        saleNum3days, saleNum7days, saleNum15days, saleNum30days, trend, saleNumDailyAve, 399HopeUseNum,
                        uHopeUseNum, totalHopeUseNum, uHopeSaleDays, hopeSaleDays, purchaseNum, shipNum, purCost, shipWeight
                    FROM cache_overseasReplenish WHERE type='AU真仓'";
                if (isset($cond['sku']) && $cond['sku']) $sql .= " AND SKU LIKE '%{$cond['sku']}%'";
                if (isset($cond['salerName']) && $cond['salerName']) $sql .= " AND salerName LIKE '%{$cond['salerName']}%'";
                if (isset($cond['purchaser']) && $cond['purchaser']) $sql .= " AND purchaser LIKE '%{$cond['purchaser']}%'";
                if (isset($cond['trend']) && $cond['trend']) $sql .= " AND trend LIKE '%{$cond['trend']}%'";
                if (isset($cond['isPurchaser']) && $cond['isPurchaser'] == '是') $sql .= " AND purchaseNum>0 ";
                if (isset($cond['isPurchaser']) && $cond['isPurchaser'] == '否') $sql .= " AND purchaseNum=0 ";
                if (isset($cond['isShipping']) && $cond['isShipping'] == '是') $sql .= " AND shipNum>0 ";
                if (isset($cond['isShipping']) && $cond['isShipping'] == '否') $sql .= " AND shipNum=0 ";
                $data = Yii::$app->db->createCommand($sql)->queryAll();*/
                $data = Yii::$app->py_db->createCommand("EXEC LY_eBayAURealWarehouse_Replenishment_20191105 '{$cond['salerName']}','{$cond['purchaser']}';")->queryAll();
                $title = ['SKU', 'SKU名称', '商品编码', '季节', '类别', '开发员', '状态', '价格(￥)', '重量(g)', '采购', '供应商', '3天销量', '7天销量', '15天销量', '30天销量',
                    '走势', '日均销量', '金皖399预计可用库存', '万邑通AU预计可用库存', '预计可用库存', '万邑通AU预计可用天数', '预计可卖天数', '采购数量', '发货数量', '采购金额', '发货重量(g)'];
                break;
            case 'ukReal':
                $name = 'ukRealReplenish';
                /*$sql = "SELECT SKU, SKUName, goodsCode, salerName, goodsStatus, price, weight, purchaser, supplierName,
                        saleNum3days, saleNum7days, saleNum15days, saleNum30days, trend, saleNumDailyAve, 399HopeUseNum,
                        uHopeUseNum, totalHopeUseNum, uHopeSaleDays, hopeSaleDays, purchaseNum, shipNum, purCost, shipWeight
                    FROM cache_overseasReplenish WHERE type='UK真仓'";
                if (isset($cond['sku']) && $cond['sku']) $sql .= " AND SKU LIKE '%{$cond['sku']}%'";
                if (isset($cond['salerName']) && $cond['salerName']) $sql .= " AND salerName LIKE '%{$cond['salerName']}%'";
                if (isset($cond['purchaser']) && $cond['purchaser']) $sql .= " AND purchaser LIKE '%{$cond['purchaser']}%'";
                if (isset($cond['trend']) && $cond['trend']) $sql .= " AND trend LIKE '%{$cond['trend']}%'";
                if (isset($cond['isPurchaser']) && $cond['isPurchaser'] == '是') $sql .= " AND purchaseNum>0 ";
                if (isset($cond['isPurchaser']) && $cond['isPurchaser'] == '否') $sql .= " AND purchaseNum=0 ";
                if (isset($cond['isShipping']) && $cond['isShipping'] == '是') $sql .= " AND shipNum>0 ";
                if (isset($cond['isShipping']) && $cond['isShipping'] == '否') $sql .= " AND shipNum=0 ";
                $data = Yii::$app->db->createCommand($sql)->queryAll();*/
                $data = Yii::$app->py_db->createCommand("EXEC LY_eBayUKRealWarehouse_Replenishment_20191105 '{$cond['salerName']}','{$cond['purchaser']}';")->queryAll();
                $title = ['SKU', 'SKU名称', '商品编码', '季节', '类别', '开发员', '状态', '价格(￥)', '重量(g)', '采购', '供应商', '3天销量', '7天销量', '15天销量', '30天销量',
                    '走势', '日均销量', '金皖399预计可用库存', '万邑通UK预计可用库存', '预计可用库存', '万邑通UK预计可用天数', '预计可卖天数', '采购数量', '发货数量', '采购金额', '发货重量(g)'];
                break;
            case 'uk2':
                $name = 'ukRealReplenish2';
                $sql = "EXEC  [guest].[LY_eBayUK_Replenishment] @salerName=:salerName,@purchaser=:purchaser";
                $params = [
                    ':salerName' => $cond['salerName'],
                    ':purchaser' => $cond['purchaser'],
                ];
                $data = Yii::$app->py_db->createCommand($sql)->bindValues($params)->queryAll();
                $title = ['商品编码', 'SKU', '商品名称', '状态', '预计可用库存', '仓库', '开发员', '采购', '供应商', '成本价(￥)', '平均单价(￥)', '重量(g)', '3天销量', '7天销量', '15天销量', '30天销量',
                    '3天平均销量', '7天平均销量', '15天平均销量', '30天平均销量', '走势', '日均销量', '总预计可用库存', '采购到货天数', '预警销售天数', '预计可卖天数', '是否特殊备货', '是否采购', '采购数量', '采购单价', '采购金额'];
                break;
            default :
                $name = 'ukVirtualReplenish';
                $sql = "SELECT SKU, SKUName, goodsCode, salerName, goodsStatus, purchaser, supplierName,
                        saleNum3days, saleNum7days, saleNum15days, saleNum30days, trend, saleNumDailyAve, hopeUseNum,
                        amount, totalHopeUN, hopeSaleDays, purchaseNum, price, purCost 
                    FROM cache_overseasReplenish WHERE type='UK虚拟仓'";
                if (isset($cond['sku']) && $cond['sku']) $sql .= " AND SKU LIKE '%{$cond['sku']}%'";
                if (isset($cond['salerName']) && $cond['salerName']) $sql .= " AND salerName LIKE '%{$cond['salerName']}%'";
                if (isset($cond['purchaser']) && $cond['purchaser']) $sql .= " AND purchaser LIKE '%{$cond['purchaser']}%'";
                if (isset($cond['trend']) && $cond['trend']) $sql .= " AND trend LIKE '%{$cond['trend']}%'";
                if (isset($cond['isPurchaser']) && $cond['isPurchaser'] == '是') $sql .= " AND purchaseNum>0 ";
                if (isset($cond['isPurchaser']) && $cond['isPurchaser'] == '否') $sql .= " AND purchaseNum=0 ";
                $data = Yii::$app->db->createCommand($sql)->queryAll();
                $title = ['SKU', 'SKU名称', '商品编码', '开发员', '状态', '采购', '供应商', '3天销量', '7天销量', '15天销量', '30天销量',
                    '走势', '日均销量', '预计可用库存', '义乌仓库存', '义乌仓采购未审核', '预计可卖天数', '采购数量', '单价', '采购金额'];
                break;
        }
        ExportTools::toExcelOrCsv($name, $data, 'Xls', $title);
    }

    /**
     * @brief 上传joom单号
     * @return array
     * @throws \Exception
     */
    public function actionUploadJoomTracking()
    {
        try {
            $file = $_FILES['file'];
            if (!$file) {
                return ['code' => 400, 'message' => 'The file can not be empty!'];
            }
            return ApiTinyTool::uploadJoomTracking($file);
        } catch (\Exception $why) {
            return ['code' => $why->getCode(), 'message' => $why->getMessage()];
        }
    }

    /**
     * @brief 下载joom单号模板
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function actionDownJoomTrackingTemplate()
    {
        ApiTinyTool::downLoadJoomTrackingTemplate();
    }

    public function actionJoomTrackingLog()
    {
        try {
            $condition = Yii::$app->request->post()['condition'];
            return ApiTinyTool::getTaskJoomTracking($condition);
        } catch (\Exception  $why) {
            return ['message' => $why->getMessage(), 'code' => $why->getCode()];
        }
    }

    //================竞品分析===================

    /** 竞品分析
     * Date: 2019-06-20 15:57
     * Author: henry
     * @return array|ArrayDataProvider
     */
    public function actionKeywordAnalysis()
    {
        $cond = Yii::$app->request->post()['condition'];
        return ApiTinyTool::getKeywordGoodsList($cond);
    }

    /** 从普源获取要搜索的产品
     * Date: 2019-06-20 16:23
     * Author: henry
     * @return array
     */
    public function actionKeywordList()
    {
        $cond = Yii::$app->request->post()['condition'];
        return ApiTinyTool::getKeywordGoodsListFromShopElf($cond);
    }

    /** 编辑信息
     * Date: 2019-06-20 16:24
     * Author: henry
     * @return array|bool
     */
    public function actionUpdateKeywordInfo()
    {
        try {
            $cond = Yii::$app->request->post()['condition'];
            if (!isset($cond['id']) || !$cond['id']) {
                $q = OaEbayKeyword::findOne(['goodsCode' => $cond['goodsCode']]);
                if ($q) {
                    throw new \Exception('SKU already exists and cannot be added repeatedly!');
                }
                $model = new OaEbayKeyword();
            } else {
                $model = OaEbayKeyword::findOne($cond['id']);
            }
            $model->setAttributes($cond);
            list($url1, $url2) = ApiTinyTool::handelKeyword($cond['keyword']);
            $model->ukUrl = $url1;
            $model->auUrl = $url2;
            list($url3, $url4) = ApiTinyTool::handelKeyword($cond['keyword2']);
            $model->ukUrl2 = $url3;
            $model->auUrl2 = $url4;

            if (!$model->save()) {
                //print_r($model->getErrors());exit;
                throw new \Exception('save keyword info failed!');
            }
            return true;
        } catch (\Exception $e) {
            return [
                'code' => 400,
                'message' => $e->getMessage(),
            ];
        }


    }


    /** 导出关键词
     * Date: 2019-06-28 16:20
     * Author: henry
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     * @throws \yii\db\Exception
     */
    public static function actionKeywordExport()
    {
        $cond = Yii::$app->request->post()['condition'];
        ApiTinyTool::exportKeyword($cond);
    }

    /** 导入关键词
     * Date: 2019-06-28 17:39
     * Author: henry
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */

    public static function actionKeywordImport()
    {
        ApiTinyTool::importKeyword();
    }


    //================================

    /**
     * @brief 查询joom空运费订单
     * @return array|\yii\data\ActiveDataProvider
     */
    public function actionJoomNullExpressFare()
    {
        try {
            $condition = Yii::$app->request->post()['condition'];
            return ApiTinyTool::getJoomNullExpressFare($condition);
        } catch (\Exception $why) {
            return ['code' => $why->getCode(), 'message' => $why->getMessage()];
        }
    }

    /**
     * @brief 更新joom空运费订单
     * @return array
     */
    public function actionJoomUpdateNullExpressFare()
    {
        try {
            $condition = Yii::$app->request->post()['condition'];
            return ApiTinyTool::updateJoomNullExpressFare($condition);
        } catch (\Exception $why) {
            return ['code' => $why->getCode(), 'message' => $why->getMessage()];
        }
    }


    /***
     * @brief 获取eBay账号余额
     * @return array|\yii\data\ActiveDataProvider
     */
    public function actionEbayBalance()
    {
        try {
            $condition = Yii::$app->request->post()['condition'];
            return ApiTinyTool::getEbayBalance($condition);
        } catch (\Exception $why) {
            return ['code' => $why->getCode(), 'message' => $why->getMessage()];

        }
    }

    /**
     * @brief 下载eBay账单
     * @return array
     */
    public function actionExportEbayBalance()
    {
        try {
            $request = Yii::$app->request->post();
            $condition = $request['condition'];
            $condition['pageSize'] = 100000;
            $data = ApiTinyTool::getEbayBalance($condition)->models;
            $title = ['ID', '账号名称', '销售', '部门', '出账单时间', '余额', '货币', '更新时间'];
            ExportTools::toExcelOrCsv('ebay-balance', $data, 'Xls', $title);
        } catch (\Exception $why) {
            return ['code' => $why->getCode(), 'message' => $why->getMessage()];
        }
    }


    /**
     * ebay账单查询条件
     * @return array
     */
    public function actionEbayBalanceCondition()
    {
        try {
            return ApiTinyTool::getEbayBalanceCondition();
        } catch (\Exception $why) {
            return ['code' => $why->getCode(), 'message' => $why->getMessage()];
        }
    }

    #################ebay账单时间设置#########################

    /**
     * 查询
     */
    public function actionEbayBalanceTimeGet()
    {

        try {
            $condition = Yii::$app->request->post('condition', []);
            return ApiTinyTool::ebayBalanceTimeGet($condition);
        } catch (\Exception $why) {
            return ['code' => $why->getCode(), 'message' => $why->getMessage()];
        }
    }

    /**
     * 修改
     */
    public function actionEbayBalanceTimeUpdate()
    {
        try {
            $condition = Yii::$app->request->post('condition', []);
            return ApiTinyTool::ebayBalanceTimeUpdate($condition);

        } catch (\Exception $why) {
            return ['code' => $why->getCode(), 'message' => $why->getMessage()];
        }

    }

    /**
     * 创建
     */
    public function actionEbayBalanceTimeCreate()
    {

        try {
            $condition = Yii::$app->request->post('condition', []);
            return ApiTinyTool::ebayBalanceTimeCreate($condition);
        } catch (\Exception $why) {
            return ['code' => $why->getCode(), 'message' => $why->getMessage()];
        }
    }

    /**
     * 删除
     */
    public function actionEbayBalanceTimeDelete()
    {
        try {
            $condition = Yii::$app->request->post('condition', []);
            return ApiTinyTool::ebayBalanceTimeDelete($condition);
        } catch (\Exception $why) {
            return ['code' => $why->getCode(), 'message' => $why->getMessage()];
        }

    }


    /**
     * 详情
     */
    public function actionEbayBalanceTimeDetail()
    {
        try {
            $condition = Yii::$app->request->post('condition', []);
            return ApiTinyTool::ebayBalanceTimeDetail($condition);
        } catch (\Exception $why) {
            return ['code' => $why->getCode(), 'message' => $why->getMessage()];
        }

    }


    //====================海外仓库存周转===================

    /**
     * 销售员产品库存
     * Date: 2019-12-06 11:55
     * Author: henry
     * @return SqlDataProvider
     * @throws \yii\db\Exception
     */
    public function actionSku()
    {
        $cond = Yii::$app->request->post('condition');
        $goodsCode = ArrayHelper::getValue($cond, 'goodsCode');
        $seller = ArrayHelper::getValue($cond, 'seller');
        $pageSize = ArrayHelper::getValue($cond, 'pageSize');
        $username = Yii::$app->user->identity->username;
        $userArr = ApiUser::getUserList($username);
        $userList = implode("','", $userArr);
        $countSql = "SELECT COUNT(*) FROM `cache_skuSeller` ss 
                INNER JOIN `cache_stockWaringTmpData` c ON ss.goodsCode=c.goodsCode WHERE seller1 IN ('{$userList}')";
        $sql = "SELECT c.*,ss.seller1,ss.seller2,CASE WHEN IFNULL(p.department,'')<>'' THEN p.department ELSE d.department END as depart ,
                IFNULL(ca.threeSellCount,0) AS threeSellCount, IFNULL(ca.sevenSellCount,0) AS sevenSellCount, 
                IFNULL(ca.fourteenSellCount,0) AS fourteenSellCount, IFNULL(ca.thirtySellCount,0) AS thirtySellCount,
         CASE WHEN IFNULL(threeSellCount,0)/3*0.4 + IFNULL(sevenSellCount,0)/7*0.4 + IFNULL(fourteenSellCount,0)/14*0.4 + IFNULL(thirtySellCount,0)/30*0.1 = 0 THEN 10000
         ELSE  round(ifnull(hopeUseNum,0)/(IFNULL(threeSellCount,0)/3*0.4 + IFNULL(sevenSellCount,0)/7*0.1 + IFNULL(fourteenSellCount,0)/14*0.4 + IFNULL(thirtySellCount,0)/30*0.1),0)
         END  AS turnoverDays
                FROM `cache_skuSeller` ss
                INNER JOIN cache_stockWaringTmpData c ON ss.goodsCode=c.goodsCode
                LEFT JOIN cache_30DayOrderTmpData ca ON ca.sku=c.sku AND ca.storeName=c.storeName
                LEFT JOIN `user` u ON u.username=ss.seller1
				LEFT JOIN auth_department_child dc ON dc.user_id=u.id
				LEFT JOIN auth_department d ON d.id=dc.department_id
				LEFT JOIN auth_department p ON p.id=d.parent
				WHERE seller1 IN ('{$userList}') AND c.storeName='万邑通UK'";
        if ($goodsCode) {
            $sql .= " AND c.goodsCode LIKE '%{$goodsCode}%'";
            $countSql .= " AND c.goodsCode LIKE '%{$goodsCode}%'";
        }
        if ($seller) {
            $sql .= " AND ss.seller1 LIKE '%{$seller}%'";
            $countSql .= " AND ss.seller1 LIKE '%{$seller}%'";
        }
        $count = Yii::$app->db->createCommand($countSql)->queryScalar();
        $res = new SqlDataProvider([
            'sql' => $sql,
            'totalCount' => (int)$count,
            'pagination' => [
                'pageSize' => $pageSize ? $pageSize : 20
            ]
        ]);
        return $res;
    }

    /**
     * Date: 2020-01-17 11:49
     * Author: henry
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     * @throws \yii\db\Exception
     */
    public function actionSkuExport()
    {
        $cond = Yii::$app->request->post('condition');
        $goodsCode = ArrayHelper::getValue($cond, 'goodsCode');
        $seller = ArrayHelper::getValue($cond, 'seller');
        $username = Yii::$app->user->identity->username;
        $userArr = ApiUser::getUserList($username);
        $userList = implode("','", $userArr);
        $sql = "SELECT c.goodsCode,c.sku,c.skuName,c.storeName,c.goodsStatus,c.salerName,createDate,costPrice,c.costmoney,hopeUseNum,weight,
                  ss.seller1,ss.seller2,CASE WHEN IFNULL(p.department,'')<>'' THEN p.department ELSE d.department END as depart ,
               IFNULL(ca.threeSellCount,0) AS threeSellCount, IFNULL(ca.sevenSellCount,0) AS sevenSellCount, 
                IFNULL(ca.fourteenSellCount,0) AS fourteenSellCount, IFNULL(ca.thirtySellCount,0) AS thirtySellCount,
         CASE WHEN IFNULL(threeSellCount,0)/3*0.4 + IFNULL(sevenSellCount,0)/7*0.4 + IFNULL(fourteenSellCount,0)/14*0.4 + IFNULL(thirtySellCount,0)/30*0.1 = 0 THEN 10000
         ELSE  round(ifnull(hopeUseNum,0)/(IFNULL(threeSellCount,0)/3*0.4 + IFNULL(sevenSellCount,0)/7*0.1 + IFNULL(fourteenSellCount,0)/14*0.4 + IFNULL(thirtySellCount,0)/30*0.1),0)
         END  AS turnoverDays
                FROM `cache_skuSeller` ss
                INNER JOIN cache_stockWaringTmpData c ON ss.goodsCode=c.goodsCode
                LEFT JOIN cache_30DayOrderTmpData ca ON ca.sku=c.sku AND ca.storeName=c.storeName
                LEFT JOIN `user` u ON u.username=ss.seller1
				LEFT JOIN auth_department_child dc ON dc.user_id=u.id
				LEFT JOIN auth_department d ON d.id=dc.department_id
				LEFT JOIN auth_department p ON p.id=d.parent
				WHERE seller1 IN ('{$userList}') AND c.storeName='万邑通UK'";
        if ($goodsCode) $sql .= " AND c.goodsCode LIKE '%{$goodsCode}%'";
        if ($seller) $sql .= " AND ss.seller1 LIKE '%{$seller}%'";


        $res = Yii::$app->db->createCommand($sql)->queryAll();
        $name = 'ProductInventoryTurnoverDetails';
        $title = ['商品编码', 'SKU', '商品名称', '仓库', '商品状态', '开发员', '普源创建时间', '平均单价', '成本', '预计可用库存', '重量', '销售1', '销售2', '部门',
            '3天销量', '7天销量', '14天销量', '30天销量', '周转天数'
        ];
        ExportTools::toExcelOrCsv($name, $res, 'Xls', $title);
    }

    /** 销售员总库存周转
     * Date: 2020-07-14 15:02
     * Author: henry
     * @return array
     */
    public function actionStockSeller(){
        $sql = "SELECT seller1,SUM(useNum) AS useNum,sum(30DaySellCount) AS 30DaySellCount,ROUND(sum(30DaySellCount)/30,1) AS ave,
			    CASE WHEN sum(30DaySellCount) = 0 AND SUM(useNum) > 0 THEN 10000 ELSE ROUND(SUM(useNum)*30/sum(30DaySellCount),1) END AS sellDays
                FROM (
                        SELECT IFNULL(u.seller1,'无人') seller1,c.sku,useNum,IFNULL(thirtySellCount,0) 30DaySellCount
                        FROM `cache_stockWaringTmpData` c
                        LEFT JOIN `cache_30DayOrderTmpData` co ON co.sku=c.sku
                        INNER JOIN `cache_skuSeller` u ON u.goodsCode=c.goodsCode WHERE c.storeName='万邑通UK'
                    UNION
                        SELECT IFNULL(u.seller1,'无人') seller1,IFNULL(co.sku,'') sku,IFNULL(useNum,0) useNum,IFNULL(thirtySellCount,0) 30DaySellCount
                        FROM `cache_30DayOrderTmpData` co
                        INNER JOIN `cache_skuSeller` u ON u.goodsCode=SUBSTR(co.sku,1,LENGTH(u.goodsCode))
						LEFT JOIN `cache_stockWaringTmpData` c ON co.sku=c.sku WHERE c.sku IS NULL
                ) aa GROUP BY seller1;";
        try{
            return Yii::$app->db->createCommand($sql)->queryAll();
        }catch (\Exception $e){
            return [
                'code' => 400,
                'message' => $e->getMessage(),
            ];
        }
    }



    /**
     * Date: 2020-03-13 11:49
     * Author: henry
     * @throws \yii\db\Exception
     */
    public function actionSkuUpdate()
    {
        $cond = Yii::$app->request->post('condition');
        $goodsCode = ArrayHelper::getValue($cond, 'goodsCode');
        $seller = ArrayHelper::getValue($cond, 'seller');
        if (!$goodsCode || !$seller) {
            return ['code' => 400, 'message' => '商品编码或销售员不能为空!'];
        }
        $sqlOne = "SELECT count(1) from cache_skuSeller WHERE goodsCode='{$goodsCode}';";
        $count = Yii::$app->db->createCommand($sqlOne)->queryScalar();
        $date = date('Y-m-d H:i:s');
        if ($count) {
            $sql = "UPDATE cache_skuSeller SET seller1='{$seller}',updateDate='{$date}' WHERE goodsCode='{$goodsCode}';";
        } else {
            $sql = "INSERT INTO cache_skuSeller(goodsCode,seller1,updateDate) values('{$goodsCode}','{$seller}','{$date}');";
        }
        try {
            Yii::$app->db->createCommand($sql)->execute();
            return true;
        } catch (\Exception $why) {
            return ['code' => $why->getCode(), 'message' => $why->getMessage()];
        }
    }

    /** 万邑通库存
     * Date: 2020-07-13 16:29
     * Author: henry
     * @return ArrayDataProvider
     * @throws \yii\db\Exception
     */
    public function actionWytSkuStorage()
    {
        $sql = "SELECT * FROM cache_wyt_sku_storage WHERE 1=1";
        $cond = Yii::$app->request->post('condition');
        $sku = ArrayHelper::getValue($cond, 'sku');
        $skuName = ArrayHelper::getValue($cond, 'skuName');
        $pageSize = ArrayHelper::getValue($cond, 'pageSize');
        if ($sku) $sql .= " AND sku like '%{$sku}%' ";
        if ($skuName) $sql .= " AND skuName like '%{$skuName}%' ";
        $data = Yii::$app->db->createCommand($sql)->queryAll();
        $dataProvider = new ArrayDataProvider([
            'allModels' => $data,
            'pagination' => [
                'pageSize' => isset($pageSize) && $pageSize ? $pageSize : 20,
            ],
        ]);
        return $dataProvider;
    }

    /** 海外仓，订单修改物流方式
     * Date: 2020-07-14 9:20
     * Author: henry
     * @return array
     * @throws Exception
     */
    public function actionModifyOrderLogisticsWay()
    {
        return ApiTinyTool::modifyOrderLogisticsWay();
    }




}
