<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2019-11-14 17:04
 */

namespace console\models;

use backend\models\EbayAllotRule;
use backend\models\EbayHotRule;
use backend\models\EbayNewRule;
use Yii;

use yii\mongodb\Query;

class ProductEngine
{
    private $products;
    private $developer;


    /**
     * ProductEngine constructor.
     * @param $products
     * @param $developer
     */
    public function __construct($products = [], $developer = [])
    {
        $this->products = $products;
        $this->developer = $developer;
    }


    /**
     * 给产品打标签
     * @param $productType
     */
    public static function setProductTag($productType)
    {
        $table = $productType === 'new' ? 'ebay_new_product' : 'ebay_hot_product';
        $mongo = Yii::$app->mongodb;
        $col = $mongo->getCollection($table);
        $today = date('Y-m-d');
        $catMap = static::getTagCat();
        $products = $col->find(['recommendDate' => ['$regex' => $today]]);
        foreach ($products as $pt) {
            print_r($pt['_id']."\n");
            try {
                $catName = $pt['cidName'];
            } catch (\Exception  $why) {
                $catName = $pt['categoryStructure'];
            }
            $id = $pt['_id'];
            // 匹配类目
            $catNameArr = explode(' - ', $catName);
            $tag = [];
            if(count($catNameArr) > 1){
                foreach ($catMap as $cp) {
                    similar_text($catNameArr[0], $cp['platCate'], $percent1);
                    similar_text($catNameArr[1], $cp['platSubCate'], $percent2);
                    if ($percent1 >= 80 && $percent2 >= 80) {
                        $tag[] = $cp['cateName'];
                    }
                }
                $newTag = array_values(array_unique($tag));
                $col->update(['_id' => $id], ['tag' => $newTag]);
            }
        }
    }

    /**
     * 所有开发
     */
    public static function getDevelopers()
    {
        $mongo = Yii::$app->mongodb;
        $table = 'ebay_allot_rule';
        $col = $mongo->getCollection($table);
        $cur = $col->find();
        $dev = [];
        foreach ($cur as $row) {
            $ele['tag'] = $row['category'];
            $ele['excludeTag'] = $row['excludePyCate'];
            $ele['name'] = $row['username'];
            $ele['deliveryLocation'] = $row['deliveryLocation'];
            $dev[] = $ele;
        }
        return $dev;
    }

    /**
     *获取要过滤掉的店铺
     * @return array
     */
    private static function getFilterStores()
    {
        $mongo = Yii::$app->mongodb;
        $col = $mongo->getCollection('ebay_stores');
        $stores = $col->find();
        $ret = [];
        foreach ($stores as $st) {
            $ret[] = $st['eBayUserID'];
        }
        return $ret;
    }

    /**
     * 获取产品
     * @param $type
     * @return mixed
     */
    public static function getProducts($type)
    {
        if ($type === 'new') {
            $table = 'ebay_new_product';
        } else {
            $table = 'ebay_hot_product';
        }
        $mongo = Yii::$app->mongodb;
        $col = $mongo->getCollection($table);
        $filter_stores = static::getFilterStores();
        $today = date('Y-m-d');
        $cur = $col->find([
            'recommendDate' => ['$regex' => $today],
            'seller' => ['$nin' => $filter_stores ],
        ]);
        $dep = [];
        foreach ($cur as $row) {
            $ele['name'] = $row['itemId'];
            $ele['tag'] = isset($row['tag']) ? $row['tag'] : '';
            $ele['itemLocation'] = $row['itemLocation'];
            $ele['type'] = $type;
            if(empty($row['recommendToPersons'])) {
                $dep[] = $ele;
            }
        }
        return $dep;

    }

    /**
     * 获取平台类目对应的业务类目
     * @return array
     */
    private static function getTagCat()
    {
        $mongo = Yii::$app->mongodb;
        $col = $mongo->getCollection('ebay_cate_rule');
        $cats = $col->find();
        $ret = [];
        $row = ['cateName' => '', 'plat' => '', 'marketplace' => '', 'platCate' => '', 'platSubCate' => ''];
        foreach ($cats as $ct) {

            // 类目名称
            $row['cateName'] = $ct['pyCate'];

            $detail = $ct['detail'];
            foreach ($detail as $dt) {
                $row['plat'] = $dt['plat'];
                $platValue = $dt['platValue'];
                foreach ($platValue as $pt) {
                    $row['marketplace'] = $pt['marketplace'];
                    $marketplace = $pt['marketplaceValue'];
                    foreach ($marketplace as $mk) {
                        $row['platCate'] = $mk['cate'];
                        $subCate = $mk['cateValue']['subCateChecked'];
                        foreach ($subCate as $sc) {
                            $row['platSubCate'] = $sc;
                            $ret[] = $row;
                        }
                    }
                }
            }
        }
        return $ret;
    }


    /**
     * 产品分配算法
     * @return array
     */
    public function dispatch()
    {
        $ret = [];

        //一直分配 直到人用完，或者产品用完
        $turn = ceil(count($this->products) / count($this->developer));
        $developerNumber = count($this->developer);
        for ($i = 0; $i <= $turn; $i++) {
            $this->developer = static::turnSort($this->developer, $i % $developerNumber);
            print_r("第" . $i . "轮选择开始");
            $res = static::pickUp();
            print_r("第" . $i . "轮选择结束");
            print_r("\n");
            $ret = array_merge($ret, $res);
        }
        return static::group($ret);
    }

    /**
     * 按照数量分配给每个开发
     * @param $type
     * @return array
     */
    public static function dispatchToPersons($type='new')
    {

        $persons = static::personNumberLimit();
        $products = static::getAllProducts($type);
        $ret = [];
        foreach ($persons as $pn) {
            $productNumber = 0;
            foreach ($products as  $pt) {
                if($productNumber <= (integer)$pn['limit'] && in_array($pn['name'],$pt['receiver'], false)) {
                    $row['product'] = $pt['itemId'];
                    $row['developer'] = $pn['name'];
                    $row['type'] = $type;
                    $productNumber++;
                    $ret[] = $row;
                }
                if($productNumber >= (integer)$pn['limit']) {
                    break;
                }
            }
        }
        return static::group($ret);
    }

    /**
     * 所有产品
     * @param $type
     * @return array
     */
    private static function getAllProducts($type='new')
    {
        $today = date('Y-m-d');
        $query = new Query();
        $cur = $query->select([])
            ->from('ebay_all_recommended_product')
            ->where(['productType' => $type,'recommendDate' => ['$regex' => $today]])
            ->orderBy(['sold' => SORT_DESC]);
        $ret = $cur->all();
        return $ret;

    }

    /**
     * 每个开发的产品数量限制
     */
    private static function personNumberLimit()
    {
        $mongo = Yii::$app->mongodb;
        $col = $mongo->getCollection('ebay_allot_rule');
        $cur = $col->find();
        $ret = [];
        foreach ($cur as $row) {
            $ele['name'] = $row['username'];
            $ele['limit'] = $row['productNum'];
            $ret[] = $ele;
        }
        return $ret;
    }


    /**
     * 按itemId汇总推荐人
     * @param $ret
     * @return array
     */
    private static function group($ret)
    {
        $res = [];
        foreach ($ret as $row) {
            $res[$row['product']]['receiver'][] = $row['developer'];
            $res[$row['product']]['type'] = $row['type'];
        }
        return $res;
    }

    /**
     * 挑一次产品
     * @return array
     */
    private  function pickUp()
    {
        $ret = [];
        $row = ['product' => '', 'developer' => ''];
        foreach ($this->developer as &$dp) {
            //var_dump($dp);exit;
            foreach ($this->products as &$pt) {
                $tag = $excludeTag = [];
                if ($pt['tag']) {
                    $tags = is_array($pt['tag']) ? $pt['tag'] : [$pt['tag']];
                    foreach ($tags as $v) {
                        if (in_array($v, $dp['excludeTag'], false)) {
                            $excludeTag[] = $v;
                        }
                        if (in_array($v, $dp['tag'], false)) {
                            $tag[] = $v;
                        }
                    }
                }
                if ($excludeTag) continue;    //属于过滤类别的产品，直接跳过
                //$condition1 =  empty($dp['tag']) || in_array($pt['tag'],$dp['tag'], false);
                $condition1 =  empty($dp['tag']) || $tag;
                $condition2 = static::matchLocation($dp['deliveryLocation'], $pt['itemLocation']);
                $limit = isset($pt['limit']) ? $pt['limit']  : 0;
                if($limit === 0) {
                    $pt['limit'] = 0;
                }
                $condition3 = $limit < 2;
                $dProducts = isset($pt['products']) ? $pt['products'] : [];
                $condition4 = !in_array($pt['name'], $dProducts, false);
                if($condition1 && $condition2 && $condition3 && $condition4) {
                    $row['product'] = $pt['name'];
                    $row['developer'] = $dp['name'];
                    $row['type'] = $pt['type'];
                    $pt['limit'] += 1;
                    $dp['products'][] = $pt['name'];
                    $ret[] = $row;
                    print_r("\n");
                    print_r($dp['name']." 选中:".$pt['name']);
                    break;
                }
            }
        }
        return $ret;
    }

    /**
     * 发货地址匹配
     * @param $developerLocation
     * @param $productLocation
     * @return bool
     */
    private static function matchLocation($developerLocation, $productLocation)
    {
        $locationMap =  [
            '中国' => 'CN',
            '香港' => 'HK',
            '美国' => 'US',
            '英国' => 'GB',
            '法国' => 'FR',
            '德国' => 'DE',
            '荷兰' => 'NL',
            '爱尔兰' => 'IE',
            '加拿大' => 'CA',
            '意大利' => 'IT',
            '澳大利亚' => 'AU'
        ];
        if(empty($developerLocation)) {
            return true;
        }
        foreach ($developerLocation as $dl) {
            if(strpos($productLocation,$locationMap[$dl]) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * 轮流排序
     * @param $list
     * @param $index
     * @return mixed
     */
    public static function turnSort($list, $index)
    {
        $first =  [];
        $left = [];
        $right = [];
        $length = count($list);
        for($cur=0; $cur<$length; ++$cur) {
           if($cur < $index) {
               $left[] = $list[$cur];
           }
            elseif($cur > $index) {
                $right[] = $list[$cur];
            }
            else{
               $first[] = $list[$cur];
            }
        }
        return array_merge($first, $right, $left);
    }

    /**
     * 获取每日推荐统计数据
     * Date: 2019-11-21 8:43
     * Author: henry
     * @return array
     */
    public static function getDailyReportData()
    {
        $db = Yii::$app->mongodb;
        //获取新品统计数
        $totalNewNum = $db->getCollection('ebay_new_product')
            ->count(['recommendDate' => ['$regex' => date('Y-m-d')]]);
        //分配新品总数
        $dispatchNewNum = $db->getCollection('ebay_recommended_product')
            ->count(['productType' => 'new', 'dispatchDate' => ['$regex' => date('Y-m-d')]]);
        //认领新品总数量
        $claimNewNum = $db->getCollection('ebay_recommended_product')
            ->count(['productType' => 'new', 'accept' => ['$size' => 1], 'dispatchDate' => ['$regex' => date('Y-m-d')]]);
        //过滤新品总数量
        $filterNewNum = $db->getCollection('ebay_recommended_product')
            ->count(['productType' => 'new', "refuse" => ['$ne' => null], 'dispatchDate' => ['$regex' => date('Y-m-d')]]);
        //未处理新品总数量
        $unhandledNewNum = $db->getCollection('ebay_recommended_product')
            ->count(['productType' => 'new', "refuse" => null, 'accept' => null, 'dispatchDate' => ['$regex' => date('Y-m-d')]]);

        //=========================================================================================
        //获取热销产品推送规则列表并统计产品数
        $totalHotNum = $db->getCollection('ebay_hot_product')
            ->count(['recommendDate' => ['$regex' => date('Y-m-d')]]);
        //分配热销品总数
        $dispatchHotNum = $db->getCollection('ebay_recommended_product')
            ->count(['productType' => 'hot', 'dispatchDate' => ['$regex' => date('Y-m-d')]]);
        //认领热销品总数量
        $claimHotNum = $db->getCollection('ebay_recommended_product')
            ->count(['productType' => 'hot', 'accept' => ['$size' => 1], 'dispatchDate' => ['$regex' => date('Y-m-d')]]);
        //过滤热销品总数量
        $filterHotNum = $db->getCollection('ebay_recommended_product')
            ->count(['productType' => 'hot', "refuse" => ['$ne' => null], 'dispatchDate' => ['$regex' => date('Y-m-d')]]);
        //未处理热销品总数量
        $unhandledHotNum = $db->getCollection('ebay_recommended_product')
            ->count(['productType' => 'hot', "refuse" => null, 'accept' => null, 'dispatchDate' => ['$regex' => date('Y-m-d')]]);

        //获取开发数据统计
        $devList = EbayAllotRule::find()->all();
        $devData = [];
        foreach ($devList as $v){
            $dispatchNum = $db->getCollection('ebay_recommended_product')
                ->count(['receiver' => $v['username'], 'dispatchDate' => ['$regex' => date('Y-m-d')]]);
            $claimNum = $db->getCollection('ebay_recommended_product')
                ->count(['accept' => $v['username'], 'dispatchDate' => ['$regex' => date('Y-m-d')]]);
            $filterNum = $db->getCollection('ebay_recommended_product')
                ->count([
                    '$or' => [
                        [
                            "refuse.".$v['username'] => null,
                            'accept' => ['$nin' => [null, $v['username']]],
                        ],
                        [
                            "refuse.".$v['username'] => ['$ne' => null]
                        ]
                    ],
                    "receiver" => $v['username'] ,
                    'dispatchDate' => ['$regex' => date('Y-m-d')]
                ]);
            $unhandledNum = $db->getCollection('ebay_recommended_product')
                ->count([
                    "refuse.".$v['username'] => null,
                    'accept' => null,
                    "receiver" => $v['username'] ,
                    'dispatchDate' => ['$regex' => date('Y-m-d')]
                ]);
            $devItem['username'] = $v['username'];
            $devItem['depart'] = $v['depart'];
            $devItem['dispatchNum'] = $dispatchNum;//当天分配数量
            $devItem['claimNum'] = $claimNum;      //认领数量
            $devItem['claimRate'] = $dispatchNum ? round($claimNum * 1.0/$dispatchNum * 100, 2) : 0;
            $devItem['filterNum'] = $filterNum;    //过滤数量
            $devItem['filterRate'] = $dispatchNum ? round($filterNum * 1.0 / $dispatchNum * 100, 2) : 0;
            $devItem['unhandledNum'] = $unhandledNum;    //过滤数量
            $devItem['unhandledRate'] = $dispatchNum ? round($unhandledNum * 1.0 / $dispatchNum * 100, 2) : 0;
            
            $devData[] = $devItem;
        }
        $claimData = [];
        for ($i=7;$i>0;$i--){
            $date = date('Y-m-d', strtotime("-$i day"));
            $claimData[] = [
                'name' => $date,
                'value' => $db->getCollection('ebay_recommended_product')
                    ->count(['accept' => ['$size' => 1], 'dispatchDate' => ['$regex' => $date]])
            ];
        }

        return [
            'totalNewNum' => $totalNewNum,        //获取新品总数
            'dispatchNewNum' => $dispatchNewNum,  //分配新品总数
            'claimNewNum' => $claimNewNum,        //认领新品总数
            'filterNewNum' => $filterNewNum,      //过滤新品总数
            'unhandledNewNum' => $unhandledNewNum,//未处理新品总数

            'totalHotNum' => $totalHotNum,        //获取热销品总数
            'dispatchHotNum' => $dispatchHotNum,  //分配热销品总数
            'claimHotNum' => $claimHotNum,        //认领热销品总数
            'filterHotNum' => $filterHotNum,      //过滤热销品总数
            'unhandledHotNum' => $unhandledHotNum,//未处理热销品总数

            'devData' => $devData,                    //开发数据
            'claimData' => $claimData,                //开发数据
        ];
    }
    /**
     * 根据匹配结果，按照ItemID查找数据
     * @param $itemId
     * @param $pickupResult
     * @return array
     */
    public static function pullData($itemId,$pickupResult)
    {
        $mongo = Yii::$app->mongodb;
        $type = $pickupResult['type'];
        $table = $type === 'new' ? 'ebay_new_product' : 'ebay_hot_product';
        $col = $mongo->getCollection($table);
        $ret = $col->findOne(['itemId' => (string)$itemId]);

        $ret['receiver'] = $pickupResult['receiver'];
        $ret['dispatchDate'] = date('Y-m-d H:i:s');
        $ret['productType'] = $type;
        unset($ret['_id']);
        return $ret;

    }


    /**
     * 入库处理
     * @param $row
     * @param string $type
     */
    public static function pushData($row, $type='all')
    {
        if($type === 'all') {
            $table = 'ebay_all_recommended_product';
        }
        else {
            $table = 'ebay_recommended_product';
        }
        $mongo = Yii::$app->mongodb;
        $col = $mongo->getCollection($table);
        try {
            $col->insert($row);
        }
        catch (\Exception  $why) {
            print 'fail to save '. $row['itemId'] . ' cause of ' . $why->getMessage();
    }
        print_r('pushing ' . $row['itemId'] . ' into ' . $table . "\n");
    }

}