<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2019-04-02 9:50
 */

namespace backend\modules\v1\controllers;


use backend\modules\v1\models\ApiWarehouseTools;
use Yii;

class WarehouseToolsController extends AdminController
{
    public $modelClass = 'backend\modules\v1\models\ApiWareHouseTools';
    public $serializer = [
        'class' => 'yii\rest\Serializer',
        'collectionEnvelope' => 'items',
    ];

    /**
     * @brief 拣货
     * @return array|bool
     */
    public function actionPick()
    {
        $condition = \Yii::$app->request->post('condition');
        return ApiWarehouseTools::setBatchNumber($condition);
    }

    /**
     * @brief 拣货人
     * @return array
     */
    public function actionPickMember()
    {
        return ApiWarehouseTools::getPickMember();
    }

    public function actionScanningLog()
    {
        $condition = Yii::$app->request->post()['condition'];
        return ApiWarehouseTools::getScanningLog($condition);
    }

}