<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2018-09-07 10:03
 */

namespace backend\modules\v1\models;

use backend\modules\v1\utils\Handler;
use yii\data\SqlDataProvider;
use yii\db\Exception;
use yii\helpers\ArrayHelper;
use \PhpOffice\PhpSpreadsheet\Reader\Csv;

class ApiTinyTool
{
    /**
     * @brief get express information
     * @return array
     */
    public static function express()
    {
        $con = \Yii::$app->py_db;
        $sql = "SELECT * FROM 
				(
				SELECT 
				m.NID, 
					DefaultExpress = ISNULL(
						(
							SELECT
								TOP 1 Name
							FROM
								T_Express
							WHERE
								NID = m.DefaultExpressNID
						),
						''
					),             -- 物流公司
					name,           --物流方式  --used,
					URL          --链接
					
				FROM
					B_LogisticWay m
				LEFT JOIN B_SmtOnlineSet bs ON bs.logicsWayNID = m.nid
				WHERE	
				used=0
				AND URL<>'') t
				ORDER BY t.DefaultExpress";
        try {
            return $con->createCommand($sql)->queryAll();
        } catch (\Exception $why) {
            return [$why];
        }
    }

    /**
     * @brief get brand list
     * @param $condition
     * @return array
     */
    public static function getBrand($condition)
    {
        $con = \Yii::$app->py_db;
        $brand = ArrayHelper::getValue($condition, 'brand', '');
        $country = ArrayHelper::getValue($condition, 'country', '');
        $category = ArrayHelper::getValue($condition, 'category', '');
        $start = ArrayHelper::getValue($condition, 'start', 0);
        $limit = ArrayHelper::getValue($condition, 'limit', 20);
        try {
            $totalSql = "SELECT COUNT(1) FROM Y_Brand WHERE
                brand LIKE '%$brand%' and (country like '%$country%') and (category like '%$category%')";
            $totalCount = $con->createCommand($totalSql)->queryScalar();
            if ($totalCount) {
                $sql = "SELECT * FROM (
                        SELECT
                        row_number () OVER (ORDER BY imgname) rowId,
                        brand,
                        country,
                        url,
                        category,
                        imgName,
                        'http://121.196.233.153/images/brand/'+ Y_Brand.imgName +'.jpg' as imgUrl
                    FROM
                        Y_Brand
                    WHERE
                    brand LIKE '%$brand%' 
                    and (country like '%$country%')
                    and (category like '%$category%')
                ) bra
                where rowId BETWEEN $start and ($limit+$start)";
                $res = $con->createCommand($sql)->queryAll();
            } else {
                $res = [];
            }
            return [
                'items' => $res,
                'totalCount' => $totalCount,
            ];
        } catch (\Exception $why) {
            return [$why];
        }
    }

    /**
     * @brief get goods picture
     * @param $condition
     * @return array|mixed
     */
    public static function modifyDeclaredValue($condition)
    {
        $con = \Yii::$app->py_db;
        $orderId = ArrayHelper::getValue($condition, 'order_id', '');
        $declaredValue = ArrayHelper::getValue($condition, 'declared_value', 2);
        $orderArr = explode(',', $orderId);
        try {
            if ($orderArr) {
                foreach ($orderArr as $v) {
                    $sql = "SELECT isnull(sum([L_QTY]),0) AS num FROM (
                              SELECT nid,[L_QTY] FROM P_TradeDtUn (nolock) WHERE TradeNid = {$v}
                              UNION 
                              SELECT nid,[L_QTY] FROM P_TradeDt (nolock) WHERE TradeNid = {$v}
                            ) aa";
                    $num = $con->createCommand($sql)->queryOne()['num'];

                    if ($num == 0) {
                        return [
                            'code' => 400,
                            'message' => '无效的订单编号或订单异常！'
                        ];
                    }
                    $value = floor($declaredValue * 10 / $num) / 10;

                    $res = $con->createCommand("UPDATE P_TradeDtUn SET [DeclaredValue]={$value} WHERE TradeNid = {$v}")->execute();
                    $result = $con->createCommand("UPDATE P_TradeDt SET [DeclaredValue]={$value} WHERE TradeNid = {$v}")->execute();

                    if ($res === false || $result === false) {
                        return [
                            'code' => 400,
                            'message' => "订单号为:{$v}的订单申报价修改失败！"
                        ];
                    }
                }
                return true;
            } else {
                return [
                    'code' => 400,
                    'message' => "订单号不能为空！"
                ];
            }
        } catch (\Exception $e) {
            return [$e];
        }
    }


    /**
     * @brief get goods picture
     * @param $condition
     * @return array
     */
    public static function getGoodsPicture($condition)
    {
        $con = \Yii::$app->py_db;
        $salerName = ArrayHelper::getValue($condition, 'salerName', '');
        $possessMan1 = ArrayHelper::getValue($condition, 'possessMan1', '');
        $possessMan2 = ArrayHelper::getValue($condition, 'possessMan2', '');
        $beginDate = ArrayHelper::getValue($condition, 'beginDate', '') ?: '1990-01-01';
        $endDate = ArrayHelper::getValue($condition, 'endDate', '') ?: date('Y-m-d');
        $goodsName = ArrayHelper::getValue($condition, 'goodsName', '');
        $supplierName = ArrayHelper::getValue($condition, 'supplierName', '');
        $goodsSkuStatus = ArrayHelper::getValue($condition, 'goodsSkuStatus', '');
        $categoryParentName = ArrayHelper::getValue($condition, 'categoryParentName', '');
        $categoryName = ArrayHelper::getValue($condition, 'categoryName', '');
        $start = ArrayHelper::getValue($condition, 'start', 0);
        $limit = ArrayHelper::getValue($condition, 'limit', 0);
        try {
            $totalSql = "SELECT count(1) FROM b_goods AS bg
                        LEFT JOIN B_GoodsSKU AS bgs ON bg.NID = bgs.GoodsID
                        LEFT JOIN B_GoodsCats AS bgc ON bgc.NID = bg.GoodsCategoryID
                        LEFT JOIN B_Supplier bs ON bs.NID = bg.SupplierID
                        WHERE bgs.SKU IN (SELECT MIN (bgs.SKU) FROM B_GoodsSKU AS bgs GROUP BY bgs.GoodsID) 
                        AND bg.CreateDate BETWEEN '$beginDate' AND '$endDate' ";
            if ($supplierName) $totalSql .= " AND bs.SupplierName LIKE '%$supplierName%' ";
            if ($possessMan1) $totalSql .= " AND bg.possessman1 LIKE '%$possessMan1%' ";
            if ($possessMan2) $totalSql .= " AND bg.possessman2 LIKE '%$possessMan2%'";
            if ($salerName) $totalSql .= " AND bg.SalerName LIKE '%$salerName%'";
            if ($goodsName) $totalSql .= " AND bg.GoodsName LIKE '%$goodsName%'";
            if ($goodsSkuStatus) $totalSql .= " AND bgs.GoodsSKUStatus LIKE '%$goodsSkuStatus%'";
            if ($categoryParentName) $totalSql .= " AND bgc.CategoryParentName LIKE '%$categoryParentName%'";
            if ($categoryName) $totalSql .= " AND bgc.CategoryName LIKE '%$categoryName%'";
            $totalCount = $con->createCommand($totalSql)->queryScalar();
            if ($totalCount) {
                $sql = "SELECT * FROM(
                    SELECT
                        row_number () OVER (ORDER BY bg.nid) AS rowId,
                        bg.possessman1,
                        bg.GoodsCode,
                        bg.GoodsName,
                        bg.CreateDate,
                        bgs.SKU,
                        bgs.GoodsSKUStatus,
                        bgs.BmpFileName,
                        bg.LinkUrl,
                        bg.Brand,
                        bgc.CategoryParentName,
                        bgc.CategoryName
                    FROM b_goods AS bg
                    LEFT JOIN B_GoodsSKU AS bgs ON bg.NID = bgs.GoodsID
                    LEFT JOIN B_GoodsCats AS bgc ON bgc.NID = bg.GoodsCategoryID
                    LEFT JOIN B_Supplier bs ON bs.NID = bg.SupplierID
                    WHERE bgs.SKU IN (SELECT MIN (bgs.SKU) FROM B_GoodsSKU AS bgs GROUP BY bgs.GoodsID)
                    AND bg.CreateDate BETWEEN '$beginDate' AND '$endDate' ";
                if ($supplierName) $sql .= " AND bs.SupplierName LIKE '%$supplierName%' ";
                if ($possessMan1) $sql .= " AND bg.possessman1 LIKE '%$possessMan1%' ";
                if ($possessMan2) $sql .= " AND bg.possessman2 LIKE '%$possessMan2%'";
                if ($salerName) $sql .= " AND bg.SalerName LIKE '%$salerName%'";
                if ($goodsName) $sql .= " AND bg.GoodsName LIKE '%$goodsName%'";
                if ($goodsSkuStatus) $sql .= " AND bgs.GoodsSKUStatus LIKE '%$goodsSkuStatus%'";
                if ($categoryParentName) $sql .= " AND bgc.CategoryParentName LIKE '%$categoryParentName%'";
                if ($categoryName) $sql .= " AND bgc.CategoryName LIKE '%$categoryName%'";
                $sql .= ") pic
                WHERE rowId BETWEEN $start AND ($limit+$start)";
                $res = $con->createCommand($sql)->queryAll();
            } else {
                $res = [];
            }
            return [
                'items' => $res,
                'totalCount' => $totalCount,
            ];
        } catch (\Exception $why) {
            return [$why];
        }
    }

    /**
     * @brief convert csv to json
     * @param $file
     * @return array
     * @throws \Exception
     */
    public static function FyndiqzUpload($file)
    {
        /* tmp file
        if (!$file) {
            return ['code' => 400, 'message' => 'The file can not be empty!'];
        }
        $extension = ApiUpload::get_extension($file['name']);
        if ($extension !== '.csv')  {
            return ['code' => 400, 'message' => 'Please upload csv file'];
        }
        $fileName = time().$extension;
        $basePath = '/uploads/';
        $path = \Yii::$app->basePath.$basePath;
        if (!file_exists($path)) {
            !is_dir($path) && !mkdir($path,0777) && !is_dir($path);
        }
        $fileSrc = $path.$fileName;
        move_uploaded_file($file['tmp_name'],$fileSrc);
        */
        $reader = new Csv();
        $reader->setInputEncoding('utf-8');
        $reader->setDelimiter(',');
        $reader->setEnclosure('');
        $reader->setSheetIndex(0);
        $spreadsheet = $reader->load($file['tmp_name']);
        $spreadData = $spreadsheet->getActiveSheet()->toArray();
        $len = count($spreadData);
        $ret = [];
        for ($i = 1; $i < $len; $i++) {
            $row = array_combine($spreadData[0], $spreadData[$i]);
            $ret[] = $row;
        }

        $auth = \Yii::$app->params['auth'];
        $auth = 'Basic ' . base64_encode($auth['merchant'] . ':' . $auth['token']);
        $headers = [
            "Authorization:$auth",
            'Content-Type:application/json'
        ];
        $baseUrl = 'https://merchants-api.fyndiq.com/api/v1/articles';

        $out = [];
        foreach ($ret as $row) {
            $img = [$row['Extra Image URL']];
            for ($i = 1; $i < 11; $i++) {
                if (!empty($row['Extra Image URL ' . $i])) {
                    $img[] = $row['Extra Image URL ' . $i];
                }
            }
            $obj['sku'] = $row['*Unique ID'];
            $obj['parent_sku'] = $row['Parent Unique ID'];
            $obj['status'] = 'for sale';
            $obj['quantity'] = $row['*Quantity'];
            $obj['tags'] = [$row['Main Tag']] + explode(',', $row['*Tags']);
            $obj['size'] = $row['Size'];
            $obj['color'] = $row['Color'];
            $obj['brand'] = '';
            $obj['gtin'] = '';
            $obj['main_image'] = $row['Variant Main Image URL'];
            $obj['images'] = $img;
            $obj['markets'] = ['SE'];
            $obj['title'] = [['language' => 'en-US', 'value' => $row['*Product Name']]];
            $obj['description'] = [['language' => 'en-US', 'value' => $row['Description']]];
            $obj['price'] = [['market' => 'SE', 'value' => ['amount' => $row['*Price'], 'currency' => 'USD']]];
            $obj['original_price'] = [['market' => 'SE', 'value' => ['amount' => $row['*MSRP'], 'currency' => 'USD']]];
            $obj['shipping_price'] = [['market' => 'SE', 'value' => ['amount' => $row['*Shipping'], 'currency' => 'USD']]];
            $obj['shipping_time'] = [['market' => 'SE', 'value' => $row['Shipping Time(enter without " ", just the estimated days )']]];
            $response = Handler::request($baseUrl, json_encode($obj), $headers);
            $response = json_decode($response);
            $out[] = $response;
        }
        return $out;
    }

    /**
     * @brief get exception payPal
     * @param $cond
     * @return array
     * @throws \yii\db\Exception
     */
    public static function getExceptionPayPal($cond)
    {
        $beginDate = ArrayHelper::getValue($cond, 'beginDate', '');
        $endDate = ArrayHelper::getValue($cond, 'endDate', '');
        if (empty($beginDate) && empty($endDate)) {
            $sql = 'select itemId,payPal,sellerUserId,createdTime from exceptionPaypal';
        } else {
            $sql = "select itemId,payPal,sellerUserId,createdTime from exceptionPaypal where createdTime BETWEEN
                  '$beginDate' and date_add('$endDate',INTERVAL 1 day)";
        }
        $db = \Yii::$app->db;
        return $db->createCommand($sql)->queryAll();

    }

    /**
     * @brief get orders on risk
     * @return mixed
     */
    public static function getRiskyOrder()
    {
        $sql = "select nid as tradeNid,orderTime,suffix,buyerId,shipToName,shipToStreet,
            shipToStreet2,shipToCity,shipToZip,shipToCountryCode,shipToPhoneNum
             from p_trade where memo like '%钓鱼%'";
        $db = \Yii::$app->py_db;
        return $db->createCommand($sql)->queryAll();
    }
}