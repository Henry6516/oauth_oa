<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2018-09-07 10:03
 */

namespace backend\modules\v1\models;

use backend\modules\v1\utils\Handler;
use yii\data\SqlDataProvider;
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
                where rowId BETWEEN $start and $limit";
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
                        AND bs.SupplierName LIKE '%$supplierName%'
                        AND bg.possessman1 LIKE '%$possessMan1%'
                        AND bg.possessman2 LIKE '%$possessMan2%'
                        AND bg.SalerName LIKE '%$salerName%'
                        AND bg.CreateDate BETWEEN '$beginDate'
                        AND '$endDate'
                        AND bg.GoodsName LIKE '%$goodsName%'
                        AND bgs.GoodsSKUStatus LIKE '%$goodsSkuStatus%'
                        AND bgc.CategoryParentName LIKE '%$categoryParentName%'
                        AND bgc.CategoryName LIKE '%$categoryName%'";
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
                    AND bs.SupplierName LIKE '%$supplierName%'
                    AND bg.possessman1 LIKE '%$possessMan1%'
                    AND bg.possessman2 LIKE '%$possessMan2%'
                    AND bg.SalerName LIKE '%$salerName%'
                    AND bg.CreateDate BETWEEN '$beginDate'
                    AND '$endDate'
                    AND bg.GoodsName LIKE '%$goodsName%'
                    AND bgs.GoodsSKUStatus LIKE '%$goodsSkuStatus%'
                    AND bgc.CategoryParentName LIKE '%$categoryParentName%'
                    AND bgc.CategoryName LIKE '%$categoryName%'
                ) pic
                WHERE rowId BETWEEN $start AND $limit";
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
}