<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2018-09-07 10:00
 */

namespace backend\modules\v1\controllers;


use backend\modules\v1\models\ApiAu;
use backend\modules\v1\models\ApiTinyTool;
use backend\modules\v1\models\ApiUk;
use backend\modules\v1\models\ApiUkFic;
use Codeception\Template\Api;
use common\models\User;
use PhpOffice\PhpSpreadsheet\Calculation\Exception;
use Yii;
class TinyToolController extends AdminController
{
    public $modelClass = 'backend\modules\v1\models\ApiTinyTool';

    public function behaviors()
    {
        return parent::behaviors();
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
        $post = Yii::$app->request->post();
        $userInfo = $post['user'];
        try {
            foreach ($userInfo as $user) {
                $username = $user['username'];
                $one = User::findOne(['username' => $username]);
                if (!empty($one)) {
                    $one->password = $user['password'];
                    $one->password_hash = Yii::$app->security->generatePasswordHash($user['password']);
                    $ret = $one->save();
                    if (!$ret) {
                        throw new \Exception("fail to set $username");
                    }

                }
            }
            return 'job done!';
        }
        catch(\Exception  $why) {
            return [$why];
        }



    }

    /**
     * @brief UK 虚拟仓定价器
     */
    public function actionUkFic()
    {
        $request = Yii::$app->request->post();
        $cond= $request['condition'];
        if(!$cond['sku']){
            return [
                'code' => 400,
                'message' => 'The SKU attribute can not be empty!',
            ];
        }
        $post= [
            'sku' => $cond['sku'],
            'num' => $cond['num'] ? $cond['num'] : 1,
            'price' => $cond['price'],
            'rate' => $cond['rate'],
        ];

        $data = [
            'num' => 0,
            'detail' => [],
            'rate' => [],
            'price' => [],
            'transport' => [],
        ];
        //获取SKU信息
        $sql = "EXEC ibay365_ebay_virtual_store_online_product '{$post['sku']}'";
        $res = Yii::$app->py_db->createCommand($sql)->queryOne();
        if(!$res) return $data;

        $post['num'] = $post['num'] ? $post['num'] : 1;
        $post['rate'] = $post['rate'] ? $post['rate'] : 0;

        $data['num'] = $post['num'];

        $res['costprice'] = $res['costprice'] * $post['num'];
        $res['Weight'] = $res['Weight'] * $post['num'];
        $data['detail'] = $res;

        if($res['Weight'] > Yii::$app->params['weight']){
            $cost = Yii::$app->params['bwBasic'] + Yii::$app->params['bwPrice'] * $res['Weight'];
        }else{
            $cost = Yii::$app->params['swBasic'] + Yii::$app->params['swPrice'] * $res['Weight'];
        }

        //根据售价获取利润率
        if($post['price']){
            $data['rate'] = ApiUkFic::getRate($post['price'], $cost ,$res['costprice']);
        }
        //根据利润率获取售价
        $data['price'] = ApiUkFic::getPrice($post['rate'], $cost ,$res['costprice']);
        $data['transport'] = [
            'name' => Yii::$app->params['transport'],
            'cost' => round($cost,2),
        ];
        //print_r($data);exit;
        return $data;
    }

    /**
     * UK 真仓定价器
     * @return array
     */
    public function actionUk(){
        $request = Yii::$app->request->post();
        $cond= $request['condition'];
        if(!$cond['sku']){
            return [
                'code' => 400,
                'message' => 'The SKU attribute can not be empty!',
            ];
        }
        $post= [
            'sku' => $cond['sku'],
            'num' => $cond['num'] ? $cond['num'] : 1,
            'price' => $cond['price'],
            'rate' => $cond['rate'],
        ];
        $data = [
            'num' => 0,
            'detail' => [],
            'rate' => [],
            'price' => [],
            'transport' => [],
        ];
        //获取SKU信息
        $res = ApiUk::getDetail($post['sku']);
        if(!$res) return $data;

        $post['num'] = $post['num'] ? $post['num'] : 1;
        $post['rate'] = $post['rate'] ? $post['rate'] : 0;

        $data['num'] = $post['num'];

        $res['price'] = $res['price'] * $post['num'];
        $res['weight'] = $res['weight'] * $post['num'];
        $res['height'] = $res['height'] * $post['num'];
        //print_r($res);exit;
        $data['detail'] = $res;

        //获取运费和出库费
        $data['transport'] = ApiUk::getTransport($res['weight'],$res['length'],$res['width'],$res['height']);

        //根据售价获取利润率
        if($post['price']){
            $data['rate'] = ApiUk::getRate($post['price'], $data['transport']['cost'] , $data['transport']['out'], $res['price']);
        }

        //根据利润率获取售价
        $data['price'] = ApiUk::getPrice($post['rate'], $data['transport']['cost'], $data['transport']['out'], $res['price']);

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
        $cond= $request['condition'];
        if(!$cond['sku']){
            return [
                'code' => 400,
                'message' => 'The SKU attribute can not be empty!',
            ];
        }
        $post= [
            'sku' => $cond['sku'],
            'num' => $cond['num'] ? $cond['num'] : 1,
            'price' => $cond['price'],
            'rate' => $cond['rate'],
        ];
        $data = [
            'num' => 0,
            'detail' => [],
            'rate' => [],
            'price' => [],
            'transport' => [],
        ];
        //获取SKU信息
        //获取SKU信息
        $res = ApiAu::getDetail($post['sku']);
        if(!$res) return $data;

        $post['num'] = $post['num'] ? $post['num'] : 1;
        $post['rate'] = $post['rate'] ? $post['rate'] : 0;

        $data['num'] = $post['num'];

        $res['price'] = $res['price'] * $post['num'];
        $res['weight'] = $res['weight'] * $post['num'];
        $res['height'] = $res['height'] * $post['num'];
        $data['detail'] = $res;

        //获取运费和出库费
        $data['transport'] = ApiAu::getTransport($res['weight'],$res['length'],$res['width'],$res['height']);

        //根据售价获取利润率
        if($post['price']){
            $data['rate'] = ApiAu::getRate($post['price'], $data['transport']['cost'], $data['transport']['out'], $res['price']);
        }
        //根据利润率获取售价
        $data['price'] = ApiAu::getPrice($post['rate'], $data['transport']['cost'], $data['transport']['out'], $res['price']);

        //print_r($data);exit;
        return $data;
    }



}