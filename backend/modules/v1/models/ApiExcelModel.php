<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018-07-20
 * Time: 9:58
 */

namespace backend\modules\v1\models;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Yii;
use yii\helpers\ArrayHelper;

class ApiExcelModel
{

    /**
     * @param $condition
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public static $ebayModel = [
        'Site'=>'',//现在用的是美国站点和eBay汽车
        'Selleruserid'=>'',//1个商品编码就对应一个类目，1个类目可以刊登给多个账号，判断
        'ListingType'=>'FixedPriceItem',
        'Category1'=>'',
        'Category2'=>'',
        'Condition'=>1000,
        'ConditionBewrite'=>'',
        'Quantity'=>20,
        'LotSize'=>'',
        'Duration'=>'GTC',
        'ReservePrice'=>'',
        'BestOffer'=>'',
        'BestOfferAutoAcceptPrice'=>'',
        'BestOfferAutoRefusedPrice'=>'',
        'AcceptPayment'=>'PayPal',
        'PayPalEmailAddress'=>'',
        'Location'=>'Shanghai',
        'LocationCountry'=>'CN',
        'ReturnsAccepted'=>1,
        'RefundOptions'=>'MoneyBack',
        'ReturnsWithin'=>'Days_30',
        'ReturnPolicyShippingCostPaidBy'=>'Buyer',
        'ReturnPolicyDescription'=> "We accept return or exchange item within 30 days from the day customer received the original item. If you have any problem please contact us first before leaving Neutral/Negative feedback! the negative feedback can't resolve the problem .but we can. ^_^ Hope you have a happy shopping experience in our store!",

        'GalleryType'=>'Gallery',
        'Bold'=>'',
        'PrivateListing'=>'',
        'HitCounter'=>'NoHitCounter',
        'sku'=>'',
        'PictureURL'=>'',
        'Title'=>'111111',
        'SubTitle'=>'',
        'IbayCategory'=>'',
        'StartPrice'=>'',
        'BuyItNowPrice'=>'',
        'UseMobile'=>1,
        'ShippingService1'=>'',
        'ShippingServiceCost1'=>0,
        'ShippingServiceAdditionalCost1'=>'',
        'ShippingService2'=>'',
        'ShippingServiceCost2'=>'',
        'ShippingServiceAdditionalCost2'=>'',
        'ShippingService3'=>'',
        'ShippingServiceCost3'=>'',
        'ShippingServiceAdditionalCost3'=>'',
        'ShippingService4'=>'',
        'ShippingServiceCost4'=>'',
        'ShippingServiceAdditionalCost4'=>'',
        'InternationalShippingService1'=>'US_IntlEconomyShippingFromGC',
        'InternationalShippingServiceCost1'=>0,
        'InternationalShippingServiceAdditionalCost1'=>0,
        'InternationalShipToLocation1'=>'Worldwide',
        'InternationalShippingService2'=>'',
        'InternationalShippingServiceCost2'=>'',
        'InternationalShippingServiceAdditionalCost2'=>'',
        'InternationalShipToLocation2'=>'',
        'InternationalShippingService3'=>'',
        'InternationalShippingServiceCost3'=>'',
        'InternationalShippingServiceAdditionalCost3'=>'',
        'InternationalShipToLocation3'=>'',
        'InternationalShippingService4'=>'',
        'InternationalShippingServiceCost4'=>'',
        'InternationalShippingServiceAdditionalCost4'=>'',
        'InternationalShipToLocation4'=>'',
        'InternationalShippingService5'=>'',
        'InternationalShippingServiceCost5'=>'',
        'InternationalShippingServiceAdditionalCost5'=>'',
        'InternationalShipToLocation5'=>'',

        'DispatchTimeMax'=>'',
        'ExcludeShipToLocation'=> 'PO Box,Africa,BO,CO,EC,FK,GF,GY,PY,PE,SR,UY,VE,BN,KH,HK,LA,MO,PH,TW,VN,AS,CK,FJ,PF,GU,KI,MH,FM,NR,NC,NU,PW,PG,SB,TO,TV,VU,WF,WS,BM,GL,PM,BH,IQ,IL,JO,KW,LB,OM,QA,SA,AE,YE,FI,GG,HU,IS,JE,LI,LU,ME,SM,RS,SI,SJ,VA,AI,AG,AW,BS,BB,BZ,VG,KY,CR,DM,DO,SV,GD,GP,GT,HT,HN,JM,MQ,MS,AN,NI,PA,KN,LC,VC,TT,TC,VI,AF,AM,AZ,BD,BT,CN,GE,KZ,KG,MN,NP,PK,LK,TJ,TM,UZ',
        'StoreCategory1'=>'',
        'StoreCategory2'=>'',
        'IbayTemplate'=>'pr92',
        'IbayInformation'=>1,
        'IbayComment'=>'',
        'Description'=>'',
        'Language'=>'',
        'IbayOnlineInventoryHold'=>0,
        'IbayRelistSold'=>'',
        'IbayRelistUnsold'=>'',
        'IBayEffectType'=>1,
        'IbayEffectImg'=>'',
        'IbayCrossSelling'=>'',
        'Variation'=>'',
        'outofstockcontrol'=>0,
        'EPID'=>'Does not apply',
        'ISBN'=>'Does not apply',
        'UPC'=>'Does not apply',
        'EAN'=>'Does not apply',
        'SecondOffer'=>0,
        'Immediately'=>'',
        'Currency'=>'',
        'LinkedPayPalAccount'=>'',
        'MBPVCount'=>'',
        'MBPVPeriod'=>'',
        'MUISICount'=>'',
        'MUISIPeriod'=>'',
        'MaximumItemCount'=>'',
        'MinimumFeedbackScore'=>'',
        'Specifics1'=>'',
        'Specifics2'=>'',
        'Specifics3'=>'',
        'Specifics4'=>'',
        'Specifics5'=>'',
        'Specifics6'=>'',
        'Specifics7'=>'',
        'Specifics8'=>'',
        'Specifics9'=>'',
        'Specifics10'=>'',
        'Specifics11'=>'',
        'Specifics12'=>'',
        'Specifics13'=>'',
        'Specifics14'=>'',
        'Specifics15'=>'',
        'Specifics16'=>'',
        'Specifics17'=>'',
        'Specifics18'=>'',
        'Specifics19'=>'',
        'Specifics20'=>'',
        'Specifics21'=>'',
        'Specifics22'=>'',
        'Specifics23'=>'',
        'Specifics24'=>'',
        'Specifics25'=>'',
        'Specifics26'=>'',
        'Specifics27'=>'',
        'Specifics28'=>'',
        'Specifics29'=>'',
        'Specifics30'=>''
    ];

    public static $zhanghao = [
        'buy_clothing' => '01-buy',
        'east_culture_2008' => '02-east_culture_2008',
        'aatq' => '03-aatq',
        'china_cheong' => '04-china_cheong',
        '5avip' => '05-5avip',
        'happygirl366' => '06-happygirl366',
        'happysmile336' => '07-happysmile336',
        'xea' => '08-xea',
        'niceday666' => '09-niceday666',
        'girlspring88' => '10-girlspring88',
        'newfashion66' => '11-newfashion66',
        'showgirl668' => '12-showgirl668',
        'showtime688' => '13-showtime688',
        'degage88' => '14-degage88',
        'exao' => '15-exao',
        'sunshinegirl678' => '16-sunshinegirl678',
        '7_su061' => '17-su061',
        'shuai.hk' => '18-shuai.hk',
        'global_saler' => 'N01',
        'supermarket6' => 'N02',
        'chuangrong89' => 'N04',
        'landchuang77' => 'N08',
        'springyinee6' => '@#A1',
        'monstercaca' => 'N11',
        'littlemay93' => '@#A2',
        'smartmilitary5' => '@#C18',
        'dangoyoung06' => '@#N05',
        'shadowchen90' => '@#A3',
        'willyerxie08' => '@#A4',
        'middleshine' => '@#N12',
        'taenha2017' => '@#C01',
        'taenha' => '@#C02',
        'sectionry-4' => '@#C19',
        'leaveszhong96' => '@#A5',
        'vitalityang1' => '@#A6',
        'redplumly3' => '@#A7',
        'actinoliteye3' => '@#C24',
        'purityhand7' => '@#C25',
        'wooddiyy3' => '@#C26',
        'sunseeke6' => '@#C27',
    ];

    public static function getZhanghao(){
        $sql = "SELECT ebayName,nameCode FROM proCenter.oa_ebaySuffix";
        $data = Yii::$app->db->createCommand($sql)->queryAll();
        $data = ArrayHelper::map($data,'ebayName','nameCode');
        return $data;
    }
    public static function getPublicationStyle(){
        $sql = "SELECT ebayName,ibayTemplate FROM proCenter.oa_ebaySuffix";
        $data = Yii::$app->db->createCommand($sql)->queryAll();
        $data = ArrayHelper::map($data,'ebayName','ibayTemplate');
        return $data;
    }

}