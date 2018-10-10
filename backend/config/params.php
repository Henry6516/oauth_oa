<?php
return [
    'adminEmail' => 'admin@example.com',
    // token 有效期默认1天
    'user.apiTokenExpire' => 1*24*3600,
//    'user.apiTokenExpire' => 1*60,

//=========================================================================
    //UK虚拟仓计算参数
    'eRate' => 0.1, //eBay交易费率
    'bpRate' => 0.032, //paypal大额交易费率
    'spRate' => 0.06, //paypal小额交易费率
    'bpBasic' => 0.2, //paypal大额交易基准值（英镑）
    'spBasic' => 0.05, //paypal小额交易基准值（英镑）

    //欧速通-英伦速邮物流费参数
    'transport' => '欧速通-英伦速邮',//物流名称
    'weight' => 149,//重量分界线（g）
    'bwBasic' => 6,//超重基准值（￥）
    'swBasic' => 9.5,//未超重基准值（￥）
    'bwPrice' => 0.072,//超重单价（￥/g）
    'swPrice' => 0.045,//未超重单价（￥/g）

//====================================================================

    //UK真仓计算参数
    'eRate_uk' => 0.1, //eBay交易费率
    'bpRate_uk' => 0.032, //paypal大额交易费率
    'spRate_uk' => 0.06, //paypal小额交易费率
    'bpBasic_uk' => 0.2, //paypal大额交易基准值（英镑）
    'spBasic_uk' => 0.05, //paypal小额交易基准值（英镑）

    //出库费用参数
    'w_uk_out_1' => 500,//重量分界线（g）
    'w_uk_out_2' => 1000,//重量分界线（g）
    'w_uk_out_3' => 2000,//重量分界线（g）
    'w_uk_out_4' => 10000,//重量分界线（g）
    'w_uk_out_fee_1' => 0.04,//出库费用（<=500g，单位：英镑）
    'w_uk_out_fee_2' => 0.05,//出库费用（<=1000g，单位：英镑）
    'w_uk_out_fee_3' => 0.07,//出库费用（<=2000g，单位：英镑）
    'w_uk_out_fee_4' => 0.14,//出库费用（<=10000g，单位：英镑）
    'w_uk_out_fee_5' => 0.13,//出库费用（>10000g，每增加1kg的费用，向上取整，单位：英镑）

    //Royal Mail - Untracked 48 Large Letter物流费参数
    'transport_uk1' => 'Royal Mail - Untracked 48 Large Letter',//物流名称
    'w_uk_tran_1_1' => 100,//重量分界线（g）
    'w_uk_tran_1_2' => 250,//重量分界线（g）
    'w_uk_tran_1_3' => 500,//重量分界线（g）
    'w_uk_tran_1_4' => 750,//重量限制（g）,不能超过750g，超过则需要换物流方式
    'len_uk_tran' => 35.3,//长度限制（cm）,不能超过35.3cm，超过则需要换物流方式
    'wid_uk_tran' => 25,//宽度限制（cm）,不能超过25cm，超过则需要换物流方式
    'hei_uk_tran' => 2.5,//高度限制（cm）,不能超过2.5cm，超过则需要换物流方式
    'w_uk_tran_fee_1_1' => 0.8,//物流费用（<=100g，单位：英镑）
    'w_uk_tran_fee_1_2' => 1.08,//物流费用（<=250g，单位：英镑）
    'w_uk_tran_fee_1_3' => 1.15,//物流费用（<=500g，单位：英镑）
    'w_uk_tran_fee_1_4' => 1.18,//物流费用（<=750g，单位：英镑）


    //Yodel - Packet Home Mini物流费参数
    'transport_uk2' => 'Yodel - Packet Home Mini',//物流名称
    'w_uk_tran_2' => 3000,//重量分界线（g）
    'w_uk_tran_fee_2' => 2.2,//物流费用（<=3kg，单位：英镑）

//===================================================================================
    //AU真仓计算参数
    'eRate_au' => 0.095, //eBay交易费率
    'bpRate_au' => 0.032, //paypal大额交易费率
    'spRate_au' => 0.06, //paypal小额交易费率
    'bpBasic_au' => 0.3, //paypal大额交易基准值（AU $）
    'spBasic_au' => 0.05, //paypal小额交易基准值（AU $）

    //出库费用参数
    'w_au_out_1' => 500,//重量分界线（g）
    'w_au_out_2' => 1000,//重量分界线（g）
    'w_au_out_3' => 2000,//重量分界线（g）
    'w_au_out_4' => 10000,//重量分界线（g）
    'w_au_out_fee_1' => 0.09,//出库费用（<=500g，单位：AU $）
    'w_au_out_fee_2' => 0.11,//出库费用（<=1000g，单位：AU $）
    'w_au_out_fee_3' => 0.16,//出库费用（<=2000g，单位：AU $）
    'w_au_out_fee_4' => 0.33,//出库费用（<=10000g，单位：AU $）
    'w_au_out_fee_5' => 0.28,//出库费用（>10000g，每增加1kg的费用，向上取整，单位：AU $）

    //AU Post - Untracked Large Letter物流费参数
    'transport_au1' => 'AU Post - Untracked Large Letter',//物流名称
    'w_au_tran_1_1' => 125,//重量分界线（g）
    'w_au_tran_1_2' => 250,//重量分界线（g）
    'w_au_tran_1_3' => 500,//重量限制（g）,不能超过500g，超过则需要换物流方式
    'len_au_tran' => 36,//长度限制（cm）,不能超过35.3cm，超过则需要换物流方式
    'wid_au_tran' => 26,//宽度限制（cm）,不能超过25cm，超过则需要换物流方式
    'hei_au_tran' => 2,//高度限制（cm）,不能超过2.5cm，超过则需要换物流方式
    'w_au_tran_fee_1_1' => 2.35,//物流费用（<=125g，单位：AU $）
    'w_au_tran_fee_1_2' => 3.52,//物流费用（<=250g，单位：AU $）
    'w_au_tran_fee_1_3' => 5.85,//物流费用（<=500g，单位：AU $）


    //MCS-Economy Parcel物流费参数
    'transport_au2' => 'MCS-Economy Parcel',//物流名称
    'w_au_tran_2_1' => 500,//重量分界线（g）
    'w_au_tran_2_2' => 1000,//重量分界线（g）
    'w_au_tran_2_3' => 2000,//重量分界线（g）
    'w_au_tran_2_4' => 3000,//重量分界线（g）
    'w_au_tran_2_5' => 4000,//重量分界线（g）
    'w_au_tran_2_6' => 5000,//重量分界线（g）
    'w_au_tran_fee_2_1' => 6.5,//物流费用（<=500g，单位：AU $）
    'w_au_tran_fee_2_2' => 6.8,//物流费用（<=1kg，单位：AU $）
    'w_au_tran_fee_2_3' => 7.3,//物流费用（<=2kg，单位：AU $）
    'w_au_tran_fee_2_4' => 7.8,//物流费用（<=3kg，单位：AU $）
    'w_au_tran_fee_2_5' => 8.54,//物流费用（<=4kg，单位：AU $）
    'w_au_tran_fee_2_6' => 8.64,//物流费用（<=5kg，单位：AU $）







];
