<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018-12-06
 * Time: 14:23
 */

namespace backend\modules\v1\models;


use Yii;
use backend\models\OaGoods;
use yii\data\ActiveDataProvider;

class ApiGoods
{


    /**
     * 获取产品推荐列表
     * @param $user
     * @param $post
     * @return ActiveDataProvider
     * @throws \yii\db\Exception
     */
    public static function getGoodsList($user, $post)
    {
        $pageSize = isset($post['pageSize']) ? $post['pageSize'] : 10;
        $page = isset($post['page']) ? $post['page'] : 0;
        //$createDate = isset($get['pageSize']) ? $post['pageSize'] : [];


        // 返回当前用户管辖下的用户
        $userList = ApiUser::getUserList($user->username);

        //print_r($userList);exit;

        $query = OaGoods::find();
        $query->select('nid,img,cate,subCate,vendor1,origin1,introReason,checkStatus,introducer,developer,approvalNote,createDate,updateDate');
        $query->filterWhere(["IFNULL(introducer,'')" => $userList]);
        $query->filterWhere(['like', 'checkStatus', $post['checkStatus']]);
        $query->filterWhere(['like', 'cate', $post['cate']]);
        $query->filterWhere(['like', 'subCate', $post['subCate']]);
        $query->filterWhere(['like', 'vendor1', $post['vendor1']]);
        $query->filterWhere(['like', 'origin1', $post['origin1']]);
        $query->filterWhere(['like', 'introReason', $post['introReason']]);
        $query->filterWhere(['like', 'introducer', $post['introducer']]);
        $query->filterWhere(['like', 'developer', $post['developer']]);
        $query->filterWhere(['like', 'approvalNote', $post['approvalNote']]);
        if($post['createDate'])$query->filterWhere(['between', "date_format(createDate,'%Y-%m-%d')", $post['createDate'][0], $post['createDate'][1]]);
        if($post['updateDate'])$query->filterWhere(['between', "date_format(updateDate,'%Y-%m-%d')", $post['updateDate'][0], $post['updateDate'][1]]);

        $provider = new ActiveDataProvider([
            'query' => $query,
            //'db' => Yii::$app->db,
            'pagination' => [
                'page' => $page,
                'pageSize' => $pageSize,
            ],
        ]);
        return $provider;

    }

}