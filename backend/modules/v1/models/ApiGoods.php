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
        //$page = isset($post['page']) ? $post['page'] : 1;

        // 返回当前用户管辖下的用户
        $userList = ApiUser::getUserList($user->username);

        $query = OaGoods::find();
        $query->select('nid,img,cate,subCate,vendor1,origin1,introReason,checkStatus,introducer,developer,approvalNote,createDate,updateDate');
        $query->andFilterWhere(["IFNULL(introducer,'')" => $userList]);//有推荐人的产品列表
        $query->andFilterWhere(['like', 'checkStatus', $post['checkStatus']]);
        $query->andFilterWhere(['like', 'cate', $post['cate']]);
        $query->andFilterWhere(['like', 'subCate', $post['subCate']]);
        $query->andFilterWhere(['like', 'vendor1', $post['vendor1']]);
        $query->andFilterWhere(['like', 'origin1', $post['origin1']]);
        $query->andFilterWhere(['like', 'introReason', $post['introReason']]);
        $query->andFilterWhere(['like', 'introducer', $post['introducer']]);
        $query->andFilterWhere(['like', 'developer', $post['developer']]);
        $query->andFilterWhere(['like', 'approvalNote', $post['approvalNote']]);
        if($post['createDate'])$query->andFilterWhere(['between', "date_format(createDate,'%Y-%m-%d')", $post['createDate'][0], $post['createDate'][1]]);
        if($post['updateDate'])$query->andFilterWhere(['between', "date_format(updateDate,'%Y-%m-%d')", $post['updateDate'][0], $post['updateDate'][1]]);
        $query->orderBy('createDate DESC');
        $provider = new ActiveDataProvider([
            'query' => $query,
            //'db' => Yii::$app->db,
            'pagination' => [
               // 'pageParam' => $page,
                'pageSize' => $pageSize,
            ],
        ]);
        return $provider;

    }


    /**
     * 获取正向开发列表
     * @param $user
     * @param $post
     * @return ActiveDataProvider
     * @throws \yii\db\Exception
     */
    public static function getForwardList($user, $post)
    {
        $pageSize = isset($post['pageSize']) ? $post['pageSize'] : 10;
        // 返回当前用户管辖下的用户
        $userList = ApiUser::getUserList($user->username);

        $query = OaGoods::find();
        $query->select('nid,stockUp,img,cate,subCate,vendor1,origin1,introReason,checkStatus,introducer,developer,approvalNote,
                                devNum,createDate,updateDate,salePrice,hopeMonthProfit,hopeRate,hopeWeight,hopeCost,hopeSale');
        $query->andFilterWhere(["IFNULL(developer,'')" => $userList]);//查看权限
        $query->andFilterWhere(['devStatus' => '正向认领']);//正向开发
        $query->andFilterWhere(['checkStatus' => ['已认领','待提交','待审批','已审批','未通过']]);
        $query->andFilterWhere(['IFNULL(stockUp,0)' => $post['stockUp']]);
        $query->andFilterWhere(['like', 'devNum', $post['devNum']]);
        $query->andFilterWhere(['like', 'checkStatus', $post['checkStatus']]);
        $query->andFilterWhere(['like', 'cate', $post['cate']]);
        $query->andFilterWhere(['like', 'subCate', $post['subCate']]);
        $query->andFilterWhere(['like', 'vendor1', $post['vendor1']]);
        $query->andFilterWhere(['like', 'origin1', $post['origin1']]);
        $query->andFilterWhere(['like', 'introReason', $post['introReason']]);
        $query->andFilterWhere(['like', 'introducer', $post['introducer']]);
        $query->andFilterWhere(['like', 'developer', $post['developer']]);
        $query->andFilterWhere(['like', 'approvalNote', $post['approvalNote']]);
        if($post['createDate'])$query->andFilterWhere(['between', "date_format(createDate,'%Y-%m-%d')", $post['createDate'][0], $post['createDate'][1]]);
        if($post['updateDate'])$query->andFilterWhere(['between', "date_format(updateDate,'%Y-%m-%d')", $post['updateDate'][0], $post['updateDate'][1]]);
        if($post['salePrice'])  $query->andFilterWhere(['and',['>=', 'salePrice', $post['salePrice']], ['<', 'salePrice', ceil($post['salePrice'] + 1)]]);
        if($post['hopeWeight']) $query->andFilterWhere(['and',['>=', 'hopeWeight', $post['hopeWeight']], ['<', 'hopeWeight', ceil($post['hopeWeight'] + 1)]]);
        if($post['hopeRate'])   $query->andFilterWhere(['and',['>=', 'hopeRate', $post['hopeRate']], ['<', 'hopeRate', ceil($post['hopeRate'] + 1)]]);
        if($post['hopeSale'])   $query->andFilterWhere(['and',['>=', 'hopeSale', $post['hopeSale']], ['<', 'hopeSale', ceil($post['hopeSale'] + 1)]]);
        if($post['hopeCost'])   $query->andFilterWhere(['and',['>=', 'hopeCost', $post['hopeCost']], ['<', 'hopeCost', ceil($post['hopeCost'] + 1)]]);
        if($post['hopeMonthProfit'])$query->andFilterWhere(['and',['>=', 'hopeMonthProfit', $post['hopeMonthProfit']], ['<', 'hopeMonthProfit', ceil($post['hopeMonthProfit'] + 1)]]);


        $query->orderBy('createDate DESC');
        $provider = new ActiveDataProvider([
            'query' => $query,
            //'db' => Yii::$app->db,
            'pagination' => [
                //'pageParam' => $page,
                'pageSize' => $pageSize,
            ],
        ]);
        return $provider;

    }

    /**
     * 获取逆向开发列表
     * @param $user
     * @param $post
     * @return ActiveDataProvider
     * @throws \yii\db\Exception
     */
    public static function getBackwardList($user, $post)
    {
        $pageSize = isset($post['pageSize']) ? $post['pageSize'] : 10;
        // 返回当前用户管辖下的用户
        $userList = ApiUser::getUserList($user->username);

        $query = OaGoods::find();
        $query->select('nid,stockUp,img,cate,subCate,vendor1,origin1,introReason,checkStatus,introducer,developer,approvalNote,
                                devNum,createDate,updateDate,salePrice,hopeMonthProfit,hopeRate,hopeWeight,hopeCost,hopeSale');
        $query->andFilterWhere(["IFNULL(developer,'')" => $userList]);//查看权限
        $query->andFilterWhere(['devStatus' => '逆向认领']);//正向开发
        $query->andFilterWhere(['checkStatus' => ['已认领','待提交','待审批','已审批','未通过']]);
        $query->andFilterWhere(['IFNULL(stockUp,0)' => $post['stockUp']]);
        $query->andFilterWhere(['like', 'devNum', $post['devNum']]);
        $query->andFilterWhere(['like', 'checkStatus', $post['checkStatus']]);
        $query->andFilterWhere(['like', 'cate', $post['cate']]);
        $query->andFilterWhere(['like', 'subCate', $post['subCate']]);
        $query->andFilterWhere(['like', 'vendor1', $post['vendor1']]);
        $query->andFilterWhere(['like', 'origin1', $post['origin1']]);
        $query->andFilterWhere(['like', 'introReason', $post['introReason']]);
        $query->andFilterWhere(['like', 'introducer', $post['introducer']]);
        $query->andFilterWhere(['like', 'developer', $post['developer']]);
        $query->andFilterWhere(['like', 'approvalNote', $post['approvalNote']]);
        if($post['createDate']) $query->andFilterWhere(['between', "date_format(createDate,'%Y-%m-%d')", $post['createDate'][0], $post['createDate'][1]]);
        if($post['updateDate']) $query->andFilterWhere(['between', "date_format(updateDate,'%Y-%m-%d')", $post['updateDate'][0], $post['updateDate'][1]]);
        if($post['salePrice'])  $query->andFilterWhere(['and',['>=', 'salePrice', $post['salePrice']], ['<', 'salePrice', ceil($post['salePrice'] + 1)]]);
        if($post['hopeWeight']) $query->andFilterWhere(['and',['>=', 'hopeWeight', $post['hopeWeight']], ['<', 'hopeWeight', ceil($post['hopeWeight'] + 1)]]);
        if($post['hopeRate'])   $query->andFilterWhere(['and',['>=', 'hopeRate', $post['hopeRate']], ['<', 'hopeRate', ceil($post['hopeRate'] + 1)]]);
        if($post['hopeSale'])   $query->andFilterWhere(['and',['>=', 'hopeSale', $post['hopeSale']], ['<', 'hopeSale', ceil($post['hopeSale'] + 1)]]);
        if($post['hopeCost'])   $query->andFilterWhere(['and',['>=', 'hopeCost', $post['hopeCost']], ['<', 'hopeCost', ceil($post['hopeCost'] + 1)]]);
        if($post['hopeMonthProfit'])$query->andFilterWhere(['and',['>=', 'hopeMonthProfit', $post['hopeMonthProfit']], ['<', 'hopeMonthProfit', ceil($post['hopeMonthProfit'] + 1)]]);

        $query->orderBy('createDate DESC');
        $provider = new ActiveDataProvider([
            'query' => $query,
            //'db' => Yii::$app->db,
            'pagination' => [
                //'pageParam' => $page,
                'pageSize' => $pageSize,
            ],
        ]);
        return $provider;

    }


    /**
     * 获取待审核列表、已通过列表、未通过列表
     * @param $user
     * @param $post
     * @return ActiveDataProvider
     * @throws \yii\db\Exception
     */
    public static function getCheckList($user, $post, $type = 'check')
    {
        $pageSize = isset($post['pageSize']) ? $post['pageSize'] : 10;
        // 返回当前用户管辖下的用户
        $userList = ApiUser::getUserList($user->username);

        $query = OaGoods::find();
        $query->select('nid,mineId,stockUp,img,cate,subCate,vendor1,origin1,introReason,checkStatus,introducer,developer,approvalNote,
                        devNum,createDate,updateDate,salePrice,hopeMonthProfit,hopeRate,hopeWeight,hopeCost,hopeSale');
        $query->andFilterWhere(["IFNULL(developer,'')" => $userList]);//查看权限
        if($type == 'check'){
            $query->andFilterWhere(['checkStatus' => '待审批']);
        }elseif($type == 'pass'){
            $query->andFilterWhere(['checkStatus' => '已审批']);
        }else{
            $query->andFilterWhere(['checkStatus' => '未通过']);
        }
        $query->andFilterWhere(['IFNULL(stockUp,0)' => $post['stockUp']]);
        if ($post['mineId'] == 1){
            $query->andFilterWhere(['>', 'IFNULL(mineId,0)', 0]);
        }elseif($post['mineId'] == 0){
            $query->andFilterWhere(['IFNULL(mineId,0)' => 0]);
        }
        $query->andFilterWhere(['like', 'devNum', $post['devNum']]);
        $query->andFilterWhere(['like', 'checkStatus', $post['checkStatus']]);
        $query->andFilterWhere(['like', 'cate', $post['cate']]);
        $query->andFilterWhere(['like', 'subCate', $post['subCate']]);
        $query->andFilterWhere(['like', 'vendor1', $post['vendor1']]);
        $query->andFilterWhere(['like', 'origin1', $post['origin1']]);
        $query->andFilterWhere(['like', 'introReason', $post['introReason']]);
        $query->andFilterWhere(['like', 'introducer', $post['introducer']]);
        $query->andFilterWhere(['like', 'developer', $post['developer']]);
        $query->andFilterWhere(['like', 'approvalNote', $post['approvalNote']]);
        if($post['createDate'])$query->andFilterWhere(['between', "date_format(createDate,'%Y-%m-%d')", $post['createDate'][0], $post['createDate'][1]]);
        if($post['updateDate'])$query->andFilterWhere(['between', "date_format(updateDate,'%Y-%m-%d')", $post['updateDate'][0], $post['updateDate'][1]]);
        if($post['salePrice'])  $query->andFilterWhere(['and',['>=', 'salePrice', $post['salePrice']], ['<', 'salePrice', ceil($post['salePrice'] + 1)]]);
        if($post['hopeWeight']) $query->andFilterWhere(['and',['>=', 'hopeWeight', $post['hopeWeight']], ['<', 'hopeWeight', ceil($post['hopeWeight'] + 1)]]);
        if($post['hopeRate'])   $query->andFilterWhere(['and',['>=', 'hopeRate', $post['hopeRate']], ['<', 'hopeRate', ceil($post['hopeRate'] + 1)]]);
        if($post['hopeSale'])   $query->andFilterWhere(['and',['>=', 'hopeSale', $post['hopeSale']], ['<', 'hopeSale', ceil($post['hopeSale'] + 1)]]);
        if($post['hopeCost'])   $query->andFilterWhere(['and',['>=', 'hopeCost', $post['hopeCost']], ['<', 'hopeCost', ceil($post['hopeCost'] + 1)]]);
        if($post['hopeMonthProfit'])$query->andFilterWhere(['and',['>=', 'hopeMonthProfit', $post['hopeMonthProfit']], ['<', 'hopeMonthProfit', ceil($post['hopeMonthProfit'] + 1)]]);

        $query->orderBy('createDate DESC');
        $provider = new ActiveDataProvider([
            'query' => $query,
            //'db' => Yii::$app->db,
            'pagination' => [
                //'pageParam' => $page,
                'pageSize' => $pageSize,
            ],
        ]);
        return $provider;

    }

}