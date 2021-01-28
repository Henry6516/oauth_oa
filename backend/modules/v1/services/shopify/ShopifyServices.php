<?php
/**
 * @name ShopifyServices.php
 * @desc PhpStorm.
 * @author: Create by henry
 * @since: Created on 2021-01-19 10:36
 */

namespace backend\modules\v1\services\shopify;


use backend\modules\v1\services\Logger;
use backend\modules\v1\utils\Helper;
use yii\db\Exception;

class ShopifyServices extends AbstractService
{

    /**
     * getPassword
     * Date: 2021-01-19 14:15
     * Author: henry
     * @return mixed
     */
    public function getPassword()
    {
        $pwdSql = "SELECT apikey,password FROM [dbo].[S_ShopifySyncInfo] WHERE hostname='{$this->myshopify_domain}'";
        return \Yii::$app->py_db->createCommand($pwdSql)->queryOne();
    }

    /**
     * getUrl
     * @param $account
     * @param $endpoint
     * Date: 2021-01-19 14:15
     * Author: henry
     * @return string
     */
    public function init()
    {
        $token = $this->getPassword();
        $this->base_uri = sprintf(
            "https://%s:%s@%s.myshopify.com/admin/api/%s/",
            $token['apikey'],
            $token['password'],
            $this->myshopify_domain,
            $this->getApiVersion()
        );
        return $this;
    }

    /**
     * Create a new Product
     * @param $product
     * Date: 2021-01-19 16:13
     * Author: henry
     * @return array | mixed
     */
    public function createProduct($product)
    {
        try {
            $endpoint = 'products.json';
            $url = $this->base_uri . $endpoint;
//            var_dump($url);exit;
            $res = Helper::post($url, json_encode(['product' => $product]));
            //$res = [201, ['product'=>['id' => 4914039488589]]];
            //var_dump($res);
//            return $res;
            if ($res[0] > 400) {
                $product_id = '';
                $content = json_encode($res[1]['errors']);
            } else {
                $content = 'success';
                $product_id = $res[1]['product']['id'];
            }
            return  [
                'suffix' => $this->myshopify_domain,
                'sku' => $product['sku'],
                'product_id' => (string) $product_id,
                'creator' => \Yii::$app->user->identity->username,
                'type' => 'product',
                'content' => $content
            ];
        } catch (Exception $e) {
            return false;
        }

    }

    /**
     * Get a Product
     * @param $product
     * Date: 2021-01-19 16:13
     * Author: henry
     * @return array
     */
    public function getProduct($product_id)
    {
        $endpoint = 'products/' . $product_id . '.json';
        $url = $this->base_uri . $endpoint;
        $res = Helper::post($url, '', [], 'GET');
        if($res[0] == 200){
            return $res[1]['product'];
        }else{
            return [];
        }
    }

    /**
     * Get Images List a Product
     * @param $product
     * Date: 2021-01-19 16:13
     * Author: henry
     * @return array
     */
    public function getImages($product_id)
    {
        $endpoint = 'products/' . $product_id . '/images.json';
        $url = $this->base_uri . $endpoint;
        $res = Helper::post($url, '', [], 'GET');
        if($res[0] == 200){
            return $res[1]['images'];
        }else{
            return [];
        }
    }

    /**
     * Get Images List a Product
     * @param $product
     * Date: 2021-01-19 16:13
     * Author: henry
     * @return string
     */
    public function updateImages($product_id, $image_id, $variant_ids, $sku)
    {
        $endpoint = 'products/' . $product_id . '/images/' . $image_id . '.json';
        $url = $this->base_uri . $endpoint;
        $data = [
            'image' => [
                'id' => $image_id,
                'variant_ids' => $variant_ids
            ]
        ];
        $res = Helper::post($url, json_encode($data), [], 'PUT');
        if($res[0] >= 400){
            return json_encode($res[1]);
        }else{
            return '';
        }
    }


}
