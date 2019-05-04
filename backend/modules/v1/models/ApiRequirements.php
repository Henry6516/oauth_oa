<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2019-05-04 10:38
 */

namespace backend\modules\v1\models;
use backend\models\Requirements;
use yii\data\ActiveDataProvider;
use Yii;

class ApiRequirements
{

    /**
     * @brief 审核
     * @param $condition
     * @return array
     * @throws \Exception
     */
    public static function examine($condition)
    {
        $ids = isset($condition['ids']) ? $condition['ids'] : [];
        if (!$ids) {
            throw new Exception('无效的ID','无效的ID');
        }
        $transaction = Yii::$app->db->beginTransaction();
        try {
            foreach ($ids as $id) {
                $username = Yii::$app->user->identity->username;
                $require = Requirements::findOne($id);
                if ($require->schedule != Requirements::SCHEDULE_TO_BE_AUDITED) {
                    throw new \Exception('审核失败!', 400);
                }
                if (!$require->processingPerson) {
                    throw new \Exception('审核失败，请选择处理人！', '400');
                }
                $require->auditor = $username;
                $require->auditDate = date('Y-m-d H:i:s');
                $require->schedule = $condition['type'] == 'pass' ? Requirements::SCHEDULE_DEALING : Requirements::SCHEDULE_FAILED;
                $require->status = $condition['type'] == 'pass' ? 1 : 0;
                if (!$require->save()) {
                    throw new \Exception('审核失败!', 400);
                }
            }
            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw new \Exception($e->getMessage(), $e->getCode());
        }
        return [];
    }

    /**
     * @brief 获取处理中列表
     * @return ActiveDataProvider
     */
    public static function getDealList()
    {
        return static::_getExaminedList(Requirements::SCHEDULE_DEALING);
    }

    /**
     * @brief 获取已完成列表
     * @return ActiveDataProvider
     */
    public static function getCompletedList()
    {
        return static::_getExaminedList(Requirements::SCHEDULE_DEALING);
    }
    /**
     * @param $scheduleType
     * @return ActiveDataProvider
     */
    private static function _getExaminedList($scheduleType)
    {
        $get = Yii::$app->request->get();
        $sortProperty = !empty($get['sortProperty']) ? $get['sortProperty'] : 'id';
        $sortOrder = !empty($get['sortOrder']) ? $get['sortOrder'] : 'desc';
        $pageSize = isset($get['pageSize']) ? $get['pageSize'] : 10;
       $type = isset($get['type']) && $get['type'] ? $get['type'] : null;
//        $type = $get['type'];//isset($get['type']) && $get['type'] ? $get['type'] : null;
        $priority = isset($get['priority']) && $get['priority'] ? $get['priority'] : null;
        $status = isset($get['status']) && $get['status'] ? $get['status'] : null;

        $query = Requirements::find();
        $query->andFilterWhere(["type" => $type, "priority" => $priority, 'status' => $status]);
        if ($scheduleType === Requirements::SCHEDULE_DEALING)
        {
            $query->andFilterWhere(["schedule" => [Requirements::SCHEDULE_DEALING]]);
        }

        if ($scheduleType === Requirements::SCHEDULE_DEALT)
        {
            $query->andFilterWhere(["schedule" => [Requirements::SCHEDULE_DEALT]]);
        }

        $query->andFilterWhere(['like', "processingPerson", isset($get['processingPerson'])? $get['processingPerson']:'']);
        $query->andFilterWhere(['like', "name", isset($get['name']) ? $get['name']:'']);
        $query->andFilterWhere(['like', "detail", isset($get['detail']) ? $get['detail'] : '']);
        $query->andFilterWhere(['like', "creator", isset($get['creator']) ? $get['creator'] : '']);
        $query->orderBy($sortProperty.' '.$sortOrder);

        $provider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => $pageSize
            ],
        ]);
        return $provider;
    }
}