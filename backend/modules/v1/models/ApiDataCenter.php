<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2018-09-07 10:03
 */

namespace backend\modules\v1\models;

use Yii;
use yii\data\ArrayDataProvider;
use yii\data\Sort;

class ApiDataCenter
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
        }
        catch (\Exception $why) {
            return [$why];
        }
    }

    /**
     * 获取销售变化表（两个时间段对比）
     * @param $condition
     * Date: 2018-12-29 15:46
     * Author: henry
     * @return ArrayDataProvider
     * @throws \yii\db\Exception
     */
    public static function getSalesChangeData($condition)
    {
        $updateSql = "oauth_salesChangeOfTwoDateBlock @lastBeginDate=:lastBeginDate,@lastEndDate=:lastEndDate,@beginDate=:beginDate,@endDate=:endDate ";
        $items = [
            ':lastBeginDate' => $condition['lastBeginDate'],
            ':lastEndDate' => $condition['lastEndDate'],
            ':beginDate' => $condition['beginDate'],
            ':endDate' => $condition['endDate']
        ];
        $data = Yii::$app->py_db->createCommand($updateSql)->bindValues($items)->queryAll();

        //清空数据表并插入新数据
        Yii::$app->db->createCommand("TRUNCATE TABLE cache_sales_change")->execute();
        //更新cache_sales_change 表数据
        Yii::$app->db->createCommand()->batchInsert(
            'cache_sales_change',
            ['suffix','goodsCode','goodsName','lastNum','lastAmt','num','amt','numDiff','amtDiff','createDate'],
            $data
        )->execute();

        $sql = "SELECT username,sc.* FROM cache_sales_change sc
                LEFT JOIN auth_store s ON s.store=sc.suffix
                LEFT JOIN auth_store_child scc ON scc.store_id=s.id
                LEFT JOIN `user` u ON u.id=scc.user_id 
                WHERE 1=1 ";
        if ($condition['suffix']) $sql .= " AND sc.suffix IN(" . $condition['suffix'] . ') ';
        if ($condition['salesman']) $sql .= " AND u.username IN(" . $condition['salesman'] . ') ';
        if ($condition['goodsName']) $sql .= " AND sc.goodsName LIKE '%" . $condition['goodsName'] . "%'";
        if ($condition['goodsCode']) $sql .= " AND sc.goodsCode LIKE '%" . $condition['goodsCode'] . "%'";
        //$sql .= " ORDER BY numDiff DESC";
        $list = Yii::$app->db->createCommand($sql)->queryAll();
        $data = new ArrayDataProvider([
            'allModels' => $list,
            'sort' => [
                'defaultOrder' => ['numDiff' => SORT_DESC],
                'attributes' => ['suffix', 'salesman', 'goodsName','goodsCode','lastNum','lastAmt','num','amt','numDiff','amtDiff','createDate'],
            ],
            'pagination' => [
                'pageSize' => $condition['pageSize'],
            ],
        ]);
        return $data;
    }


    /**
     * @param $condition
     * Date: 2019-02-19 14:55
     * Author: henry
     * @return array
     */
    public static function getPriceChangeData($condition)
    {
        $sql = 'exec oauth_priceChange :suffix,:beginDate,:endDate,:showType,:dateFlag';
        $con = Yii::$app->py_db;
        $params = [
            ':suffix' => $condition['store'],
            ':beginDate' => $condition['beginDate'],
            ':endDate' => $condition['endDate'],
            ':showType' => $condition['showType'],
            ':dateFlag' => $condition['dateFlag'],
        ];
        try {
            return $con->createCommand($sql)->bindValues($params)->queryAll();
        }
        catch (\Exception $why) {
            return [$why];
        }
    }


    /**
     * @param $condition
     * Date: 2019-02-21 14:18
     * Author: henry
     * @return array
     */
    public static function getWeightDiffData($condition)
    {
        $sql = "SELECT CASE WHEN IFNULL(pd.department,'')<>'' THEN pd.department ELSE d.department END AS department,
                CASE WHEN IFNULL(pd.department,'')<>'' THEN d.department ELSE '' END AS secDepartment,
                u.username,s.platform,cw.* 
                FROM cache_weightDiff cw
                LEFT JOIN auth_store s ON s.store=cw.suffix
                LEFT JOIN auth_store_child sc ON s.id=sc.store_id
                LEFT JOIN `user` u ON u.id=sc.user_id
                LEFT JOIN auth_department_child dc ON u.id=dc.user_id
                LEFT JOIN auth_department d ON d.id=dc.department_id
                LEFT JOIN auth_department pd ON pd.id=d.parent
                WHERE 1=1
                ";
        if($condition['store']) {
            $store = str_replace(',', "','",$condition['store']);
            $sql .= " AND cw.suffix IN ('{$store}')";
        }
        if($condition['beginDate'] && $condition['endDate']) $sql .= " AND cw.orderCloseDate BETWEEN '{$condition['beginDate']}' AND '{$condition['endDate']}'";
        $con = Yii::$app->db;
        try {
            return $con->createCommand($sql)->queryAll();
        }
        catch (\Exception $why) {
            return [$why];
        }
    }


}