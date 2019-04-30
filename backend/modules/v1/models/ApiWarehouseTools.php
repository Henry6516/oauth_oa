<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2019-04-02 10:13
 */

namespace backend\modules\v1\models;

use backend\models\ShopElf\BPerson;
use backend\models\TaskPick;
use yii\helpers\ArrayHelper;
use Yii;
use yii\data\ActiveDataProvider;
use backend\modules\v1\utils\Helper;


class ApiWarehouseTools
{


    /**
     * @brief 添加拣货任务
     * @param $condition
     * @return array|bool
     */
    public static function setBatchNumber($condition)
    {
        $row = [
            'batchNumber' => $condition['batchNumber'],
            'picker' => $condition['picker'],
            'scanningMan' => Yii::$app->user->identity->username,
        ];

        $task = new TaskPick();
        $task->setAttributes($row);
        if ($task->save()) {
            return true;
        }
        return [
            'code' => 400,
            'message' => 'failed'
        ];
    }

    /**
     * @brief 获取拣货人
     * @return array
     */
    public static function getPickMember()
    {
        $ret = BPerson::find()->andWhere(['CategoryID' => '79'])
            ->andWhere(['in', 'Duty', ['拣货','拣货组长']])->all();
        return ArrayHelper::getColumn($ret, 'PersonName');
    }

    public static function getScanningLog($condition)
    {
        $pageSize = isset($condition['pageSize']) ? $condition['pageSize'] : 10;
        $fieldsFilter = ['batchNumber', 'picker', 'isDone', 'scanningMan'];
        $timeFilter = ['createdTime', 'updatedTime'];
        $query = TaskPick::find();
        $query = Helper::generateFilter($query,$fieldsFilter,$condition);
        $query = Helper::timeFilter($query,$timeFilter,$condition);
        $query->orderBy('id DESC');
        $provider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => $pageSize,
            ],
        ]);
        return $provider;
    }

}