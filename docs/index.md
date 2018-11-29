
* [菜单列表](#%E8%8F%9C%E5%8D%95%E5%88%97%E8%A1%A8)
  * [返回信息](#%E8%BF%94%E5%9B%9E%E4%BF%A1%E6%81%AF)
* [查询条件](#%E6%9F%A5%E8%AF%A2%E6%9D%A1%E4%BB%B6)
* [毛利润报表](#%E6%AF%9B%E5%88%A9%E6%B6%A6%E6%8A%A5%E8%A1%A8)
  * [sales](#sales)
  * [develop](#develop)
  * [purchase](#purchase)
  * [possess](#possess)
  * [ebay\-sales](#ebay-sales)
  * [sales\-trend](#sales-trend)
  * [account](#account)
  * [introduce](#introduce)
* [费用导入](#%E8%B4%B9%E7%94%A8%E5%AF%BC%E5%85%A5)
  * [sales\-dead\-fee](#sales-dead-fee)
* [汇率设置](#%E6%B1%87%E7%8E%87%E8%AE%BE%E7%BD%AE)
  * [get exchange](#get-exchange)
  * [update exchange](#update-exchange)
* [eBay工具](#ebay%E5%B7%A5%E5%85%B7)
  * [site](#site)
  * [size](#size)
  * [color](#color)
  * [ebay\-template](#ebay-template)
  * [ebaysku](#ebaysku)
  * [ebaysku\-template](#ebaysku-template)
* [SMT工具](#smt%E5%B7%A5%E5%85%B7)
  * [smtsku\-template](#smtsku-template)
* [Wish工具](#wish%E5%B7%A5%E5%85%B7)
  * [wishsku\-template](#wishsku-template)
* [v1/data\-center 数据中心](#v1data-center-%E6%95%B0%E6%8D%AE%E4%B8%AD%E5%BF%83)
  * [缺货分析](#%E7%BC%BA%E8%B4%A7%E5%88%86%E6%9E%90)
  * [获取物流公司列表](#%E8%8E%B7%E5%8F%96%E7%89%A9%E6%B5%81%E5%85%AC%E5%8F%B8%E5%88%97%E8%A1%A8)
  * [平台物流费用](#%E5%B9%B3%E5%8F%B0%E7%89%A9%E6%B5%81%E8%B4%B9%E7%94%A8)
  * [销售变化](#%E9%94%80%E5%94%AE%E5%8F%98%E5%8C%96)
  * [新品开发表现](#%E6%96%B0%E5%93%81%E5%BC%80%E5%8F%91%E8%A1%A8%E7%8E%B0)
* [v1/tiny\-tool UR小工具](#v1tiny-tool-ur%E5%B0%8F%E5%B7%A5%E5%85%B7)
  * [物流网址查询](#%E7%89%A9%E6%B5%81%E7%BD%91%E5%9D%80%E6%9F%A5%E8%AF%A2)
  * [品牌列表](#%E5%93%81%E7%89%8C%E5%88%97%E8%A1%A8)
  * [产品一览表](#%E4%BA%A7%E5%93%81%E4%B8%80%E8%A7%88%E8%A1%A8)
 
# 菜单列表

* 接口名称：获取菜单列表
* 请求方法：get
* 请求示例：/v1/menu

## 返回信息
* 数据格式：json
* data参数说明：


| 元素名称 | 数据类型 | 是否非空 | 元素说明     |
| :------: | :------: | :------: | :----------: |
| id       | int      | 是       | 菜单唯一编码 |
| name     | string   | 是       | 菜单名称     |
| parent   | int      | 否       | 父菜单ID     |
| route    | string   | 是       | 路由         |
| order    | int      | 否       | 菜单顺序     |
| children | array    | 否       | 子菜单列表   |

* 成功示例：
```
{
    "code": 200,
    "message": "success",
    "data": [
        {
            "id": "19",
            "name": "毛利润报表",
            "parent": null,
            "route": "/site/index",
            "order": "3",
            "children": [
                {
                    "id": "20",
                    "name": "销售毛利润报表",
                    "parent": "19",
                    "route": "/sell",
                    "order": null,
                    "children": []
                }
            ]
```

# 首页

* 接口名称：获取本部销售人员目标完成度
* 请求示例：v1/site/index
* 请求方式：get

* 接口名称：获取郑州销售人员目标完成度
* 请求示例：v1/site/sales
* 请求方式：get

* 接口名称：获取开发人员目标完成度
* 请求示例：v1/site/develop
* 请求方式：get

* 接口名称：获取部门目标完成度（不包括郑州）
* 请求示例：v1/site/department
* 请求方式：get

# 查询条件

* 接口名称：获取查询条件
* 请求示例：v1/condition
* 条件列表：

| 接口            | 名称     | 请求方法 | 返回数据 |
| :-------------: | :------: | :------: | :------: |
| /department     | 部门     | get      | array    |
| /plat           | 平台     | get      | array    |
| /store          | 仓库     | get      | array    |
| /member         | 人员     | get      | array    |
| /account        | 账号     | get      | array    |
| /brand-country  | 品牌国家 | get      | array    |
| /brand-category | 品牌类别 | get      | array    |
| /goods-status   | 产品装态 | get      | array    |
| /goods-cats     | 产品分类 | get      | array    |

# 毛利润报表

* 接口名称：查询报表
* 请求示例：v1/report
* 报表列表：

## sales 
* 接口名称：销售毛利报表
* 请求示例：v1/report/sales
* 请求方法： post
* 请求参数：

```
{
	"condition": {
		"department": "",
		"plat": "",
		"member": "",
		"store": [],
		"dateType": 0,
		"dateRange": ["2018-07-04", "2018-07-13"],
		"account": []
	}
}
```

## develop 
* 接口名称：开发毛利报表
* 请求示例：v1/report/develop
* 请求方法： post
* 请求参数：

```
{
	"condition": {
		"member": "",
		"dateType": 0,
		"dateRange": ["2018-07-04", "2018-07-13"]
	}
}
```

## purchase 
* 接口名称：采购毛利报表
* 请求示例：v1/report/purchase
* 请求方法： post
* 请求参数：

```
{
	"condition": {
		"member": "",
		"dateType": 0,
		"dateRange": ["2018-07-04", "2018-07-13"]
	}
}
```

## possess 
* 接口名称：美工毛利报表
* 请求示例：v1/report/possess
* 请求方法： post
* 请求参数：

```
{
	"condition": {
		"member": [],
		"dateType": 0,
		"dateRange": ["2018-07-04", "2018-07-13"]
	}
}
```

## ebay-sales 
* 接口名称：eBay销售毛利报表
* 请求示例：v1/report/ebay-sales
* 请求方法： post
* 请求参数：

```
{
	"condition": {
		"member": [],
		"dateType": 0,
		"dateRange": ["2018-07-04", "2018-07-13"]
	}
}
```

## sales-trend 
* 接口名称：销售额走势
* 请求示例：v1/report/sales-trend
* 请求方法： post
* 请求参数：

```
{
	"condition": {
		"department": ["运营一部","运营二部","运营三部"],
		"secDepartment":[],
		"plat": [],
		"member": [],
		"store": [],
		"dateType": 0,
		"dateRange": ["2018-07-04", "2018-07-13"],
		"account": []
	}
}
```

## account 
* 接口名称：账号毛利润报表
* 请求示例：v1/report/account
* 请求方法： post
* 请求参数：

```
{
	"condition": {
		"department": "",
		"plat": "",
		"member": [],
		"store": [],
		"sku": "",
		"dateType": 0,
		"dateRange": ["2018-07-04", "2018-07-13"],
		"account": [],
		"start":1,
        "limit":10
	}
}
```
## introduce 
* 接口名称：推荐人毛利报表
* 请求示例：v1/report/introduce
* 请求方法： post
* 请求参数：

```
{
	"condition": {
		"member": ["朱晶晶"],
		"dateType": 0,
		"dateRange": ["2018-07-04", "2018-07-13"]
	}
}
```
# 费用导入
## sales-dead-fee
* 接口名称：销售死库费用
* 请求示例：v1/upload/sales-dead-fee
* 请求方法：post
* 请求格式：form-data
* 请求参数：file : example.xlsx

# 汇率设置
## get exchange
* 接口名称：查看美元汇率
* 请求示例：v1/settings/exchange
* 请求方法：get

## update exchange
* 接口名称：更新美元汇率
* 请求示例：v1/settings/exchange
* 请求方法：post
* 请求参数：
```
{
	"condition": {
		"devRate": "",
		"saleRate": ""
	}
}
```
# eBay工具 
* 接口名称：eBay工具获取账号
* 请求示例：v1/tool/account
* 请求方法： post
* 请求参数： （说明：eBay 获取eBay账号，Wish 获取Wish账号，SMT 获取SMT账号）

```
{
    "condition": {
        "type": "eBay"
    }
}
```

## site 
* 接口名称：eBay工具获取账号站点
* 请求示例：v1/tool/site
* 请求方法： post
* 请求参数： 无


## size 
* 接口名称：销售工具获取产品码号
* 请求示例：v1/tool/size
* 请求方法： post
* 请求参数： 无

## color 
* 接口名称：销售工具获取产品颜色
* 请求示例：v1/tool/color
* 请求方法： post
* 请求参数： 无


## ebay-template 
* 接口名称：eBay工具获取（下载）商品模板
* 请求示例：v1/tool/ebay-template
* 请求方法： post
* 请求参数： （说明：suffix 多选账号数组；goodsCode 多个商品编码，用逗号隔开）

```
{
    "condition": {
            "suffix": [],
            "goodsCode": ""
        }
}
```


## ebaysku
* 接口名称：eBay工具获取商品SKU列表
* 请求示例：v1/tool/ebaysku
* 请求方法： post
* 请求参数： 

```
{
    "condition": {
            "suffix": "showtime688",
            "goodsCode": "6C0046",
            "Site": "美国",
            "Cat1": "女人世界",
            "Cat2": "内衣",
            "price": "22",
            "shipping1": "5",
            "shipping2": "5"
        }
}
```


## ebaysku-template
* 接口名称：eBay工具获取（下载）商品SKU模板
* 请求示例：v1/tool/ebaysku-template
* 请求方法： post
* 请求参数： 

```
{
    "condition": {
    	"setting": {
            "suffix": "showtime688",
            "goodsCode": "6C0046",
            "Site": "美国",
            "Cat1": "女人世界",
            "Cat2": "内衣",
            "price": "22",
            "shipping1": "5",
            "shipping2": "5"
        },
        "contents": {
            "remark": [
                "abc",
                "edffewfaewf"
            ],
            "SKU": [
                "6C004601",
                "6C004602"
            ],
            "Quantity": [
                20,
                20
            ],
            "StartPrice": [
                0,
                0
            ],
            "PictureURL": [
                "http://121.196.233.153/images/6C004601.jpg",
                "http://121.196.233.153/images/6C004602.jpg"
            ],
            "Color": [
                "红色",
                "粉色"
            ],
            "Size": [
                "",
                ""
            ],
            "pro1": [
                "Red",
                "Pink"
            ],
            "pro2": [
                "",
                ""
            ],
            "EAN": [
                "Does not apply",
                "Does not apply"
            ],
            "UPC": [
                "Does not apply",
                "Does not apply"
            ]
        }
    }
}
```

# SMT工具
* 接口名称：SMT工具获取商品SKU列表
* 请求示例：v1/tool/smtsku
* 请求方法： post
* 请求参数： 

```
{
    "condition": {
            "suffix": "aliexpress_SMT-19",
            "goodsCode": "6A0895",
            "price": "22"
        }
}
```

## smtsku-template
* 接口名称：SMT工具获取（下载）商品SKU模板
* 请求示例：v1/tool/smtsku-template
* 请求方法： post
* 请求参数： 

```
{
    "condition": {
        "contents": {
            "SKU": [
                "6C004601",
                "6C004602"
            ],
            "quantity": [
                20,
                20
            ],
            "price": [
                0,
                0
            ],
            "pic_url": [
                "http://121.196.233.153/images/6C004601.jpg",
                "http://121.196.233.153/images/6C004602.jpg"
            ],
            "property1": [
                "红色",
                "粉色"
            ],
            "property2": [
                "",
                ""
            ],
            "varition1": [
                "Red",
                "Pink"
            ],
            "varition2": [
                "",
                ""
            ],
            "name1": [
                "Does not apply",
                "Does not apply"
            ]
        }
    }
}
```

# Wish工具
* 接口名称：Wish工具获取商品SKU列表
* 请求示例：v1/tool/wishsku
* 请求方法： post
* 请求参数： 

```
{
    "condition": {
            "suffix": "wish_01-eshop",
            "goodsCode": "S161",
            "price": "22",
            "msrp": "22",
            "shipping": "22"
        }
}
```


## wishsku-template
* 接口名称：Wish工具获取（下载）商品SKU模板
* 请求示例：v1/tool/wishsku-template
* 请求方法： post
* 请求参数：

```

{
    "code": 200,
    "message": "success",
    "data": {
        "setting": {
            "suffix": "wish_01-eshop",
            "goodsCode": "S161",
            "price": "22",
            "msrp": "22",
            "shipping": "22"
        },
        "payload": [
            {
                "SKU": "S16101",
                "pic_url": "http://121.196.233.153/images/S16101.jpg",
                "variation1": "1",
                "variation2": "黄色",
                "quantity": 1000,
                "price": "22",
                "shipping": "22",
                "$shippingTime": "7-21",
                "msrp": "22",
                "property1": "1",
                "property2": "黄色"
            },
            {
                "SKU": "S16102",
                "pic_url": "http://121.196.233.153/images/S16102.jpg",
                "variation1": "2",
                "variation2": "粉色",
                "quantity": 1000,
                "price": "22",
                "shipping": "22",
                "$shippingTime": "7-21",
                "msrp": "22",
                "property1": "2",
                "property2": "粉色"
            },
            {
                "SKU": "S16103",
                "pic_url": "http://121.196.233.153/images/S16103.jpg",
                "variation1": "3",
                "variation2": "蓝色",
                "quantity": 1000,
                "price": "22",
                "shipping": "22",
                "$shippingTime": "7-21",
                "msrp": "22",
                "property1": "3",
                "property2": "蓝色"
            },
            {
                "SKU": "S16104",
                "pic_url": "http://121.196.233.153/images/S16104.jpg",
                "variation1": "4",
                "variation2": "米色",
                "quantity": 1000,
                "price": "22",
                "shipping": "22",
                "$shippingTime": "7-21",
                "msrp": "22",
                "property1": "4",
                "property2": "米色"
            },
            {
                "SKU": "S16105",
                "pic_url": "http://121.196.233.153/images/S16105.jpg",
                "variation1": "5",
                "variation2": "墨绿",
                "quantity": 1000,
                "price": "22",
                "shipping": "22",
                "$shippingTime": "7-21",
                "msrp": "22",
                "property1": "5",
                "property2": "墨绿"
            },
            {
                "SKU": "S16106",
                "pic_url": "http://121.196.233.153/images/S16106.jpg",
                "variation1": "6",
                "variation2": "灰色",
                "quantity": 1000,
                "price": "22",
                "shipping": "22",
                "$shippingTime": "7-21",
                "msrp": "22",
                "property1": "6",
                "property2": "灰色"
            },
            {
                "SKU": "S16107",
                "pic_url": "http://121.196.233.153/images/S16107.jpg",
                "variation1": "7",
                "variation2": "黄色",
                "quantity": 1000,
                "price": "22",
                "shipping": "22",
                "$shippingTime": "7-21",
                "msrp": "22",
                "property1": "7",
                "property2": "黄色"
            },
            {
                "SKU": "S16108",
                "pic_url": "http://121.196.233.153/images/S16108.jpg",
                "variation1": "8",
                "variation2": "粉色",
                "quantity": 1000,
                "price": "22",
                "shipping": "22",
                "$shippingTime": "7-21",
                "msrp": "22",
                "property1": "8",
                "property2": "粉色"
            },
            {
                "SKU": "S16109",
                "pic_url": "http://121.196.233.153/images/S16109.jpg",
                "variation1": "9",
                "variation2": "蓝色",
                "quantity": 1000,
                "price": "22",
                "shipping": "22",
                "$shippingTime": "7-21",
                "msrp": "22",
                "property1": "9",
                "property2": "蓝色"
            },
            {
                "SKU": "S16110",
                "pic_url": "http://121.196.233.153/images/S16110.jpg",
                "variation1": "10",
                "variation2": "米色",
                "quantity": 1000,
                "price": "22",
                "shipping": "22",
                "$shippingTime": "7-21",
                "msrp": "22",
                "property1": "10",
                "property2": "米色"
            },
            {
                "SKU": "S16111",
                "pic_url": "http://121.196.233.153/images/S16111.jpg",
                "variation1": "11",
                "variation2": "墨绿",
                "quantity": 1000,
                "price": "22",
                "shipping": "22",
                "$shippingTime": "7-21",
                "msrp": "22",
                "property1": "11",
                "property2": "墨绿"
            },
            {
                "SKU": "S16112",
                "pic_url": "http://121.196.233.153/images/S16112.jpg",
                "variation1": "12",
                "variation2": "灰色",
                "quantity": 1000,
                "price": "22",
                "shipping": "22",
                "$shippingTime": "7-21",
                "msrp": "22",
                "property1": "12",
                "property2": "灰色"
            }
        ]
    }
}

```
# v1/data-center 数据中心

## 缺货分析
* 接口名称：缺货分析
* 请求方法： post
* 请求示例：v1/data-center/out-of-stock-info 
```
{
    "condition": {
        "start":1,
        "limit":10
    }
}

```


## 获取物流公司列表
* 接口名称：获取物流公司列表
* 请求方法： get
* 请求示例：v1/perform/logistics

## 平台物流费用
* 接口名称：平台物流费用
* 请求方法： post
* 请求示例：v1/perform/cost
* 请求参数： 
```
{
    "condition": {
        "beginDate": "2018-08-01",
        "endDate":"2018-08-31",
        "wlCompany":""
    }
}

```

## 销售变化
* 接口名称：销售变化
* 请求方法： post
* 请求示例：v1/perform/sales
* 说明： 业绩归属人1 使用接口/v1/condition/member 获取
* 请求参数： 
```
{
    "condition": {
        "plat": "eBay",
        "suffix":"17-su061",
        "saler":""
        "start":1,
        "limit":10
    }
}

```

## 新品开发表现
* 接口名称：新品开发表现
* 请求方法： post
* 请求示例：v1/perform/perform
* 请求参数： 
```
{
    "condition": {
        "beginDate": "2018-08-01",
        "endDate":"2018-08-31",
        "createBeginDate":"",
        "createEndDate":""
    }
}

```



# v1/tiny-tool UR小工具

## 物流网址查询
* 接口名称：物流网址查询
* 请求方法：get
* 请求示例：v1/tiny-tool/express

## 品牌列表
* 接口名称：品牌列表
* 请求方法：post
* 请求示例：v1/tiny-tool/brand
* 请求参数：
```json
{
	"condition": {
		"brand": "",
		"country": "美国",
		"category":"服装",
		"start":1,
		"limit":10
	}
}
```

## 产品一览表
* 接口名称：产品一览
* 请求方法：post
* 请求示例：v1/tiny-tool/goods-picture
* 请求参数：
```json
{
	"condition": {
		"salerName":"尚显贝",
		"possessMan1":"",
		"possessMan2":"",
		"beginDate":"",
		"endDate":"",
		"goodsName":"",
		"supplierName":"",
		"goodsSkuStatus":"",
		"categoryParentName":"",
		"categoryName":"",
		"start":1,
		"limit":10
	}
}
```

## UK虚拟仓定价器
* 接口名称：UK虚拟仓定价器
* 请求方法：post
* 请求示例：v1/tiny-tool/uk-fic
* 请求参数：
```
{
    "condition": {
        "sku": "2A019303",
        "num":1,
        "price":12,
        "rate":31
    }
}
```

## UK真仓定价器
* 接口名称：UK真仓定价器
* 请求方法：post
* 请求示例：v1/tiny-tool/uk
* 请求参数：
```
{
    "condition": {
        "sku": "2A019303",
        "num":1,
        "price":12,
        "rate":31
    }
}
```
## AU真仓定价器
* 接口名称：AU真仓定价器
* 请求方法：post
* 请求示例：v1/tiny-tool/au
* 请求参数：
```
{
    "condition": {
        "sku": "2A019303",
        "num":1,
        "price":12,
        "rate":31
    }
}
```

## 修改订单申报价
* 接口名称：修改订单申报价
* 请求方法：post
* 请求示例：v1/tiny-tool/declared-value
* 请求参数：declared_value 默认为2；order_id：订单编号，多个用逗号隔开
```
{
    "condition": {
        "order_id": "8506238",
        "declared_value":2
    }
}
```

## 异常paypal
* 接口名称：异常paypal列表
* 请求方法：post
* 请求示例：v1/tiny-tool/exception-pay-pal
* 请求参数：beginDate 和 endDate 同时为空则返回全部数据
```json
{
  "condition":{
    "beginDate":"2018-10-01",
    "endDate":"2018-11-23"
  }
}
```
## 风险订单
* 接口名称：风险订单
* 请求方法：post
* 请求示例：v1/tiny-tool/risky-order
* 请求参数： 

```json
{
  "condition":{
    "beginDate":"2018-10-01",
    "endDate":"2018-11-27"
  }
}
```
## 处理风险订单
* 接口名称：风险订单
* 请求方法：post
* 请求示例：v1/tiny-tool/handle-risky-order
* 请求参数： 

```json
{
  "data":{
    "tradeNid":"7371505",
    "processor":"admin"
  }
}
```


## 黑名单
* 接口名称：黑名单
* 请求示例：v1/tiny-tool/blacklist

| 请求方法 | 请求参数 | 返回数据 |
| :------:| :------: | :------: |
| get   | 无 |    array  |
| post | json| array| 

* post 参数
```json
{
	"data": {
		"platform": "wish",
		"buyerId":"ja",
		"shipToName":"",
		"shipToStreet":"",
		"shipToStreet2":"",
		"shipToCity":"",
		"shipToState":"",
		"shipToZip":"",
		"shipToCountryCode":"",
		"shipToPhoneNum":""
	}
}
```

## 异常改订单
* 接口名称：黑名单
* 请求示例：v1/tiny-tool/exception-edition
* 请求方法： post
* 请求参数： 

```json
{
  "condition":{
    "beginDate":"2018-10-01",
    "endDate":"2018-11-27"
  }
}
```


# v1/requirement 需求管理

## 我的需求列表
* 接口名称：我的需求列表
* 请求方法：get
* 请求示例：v1/requirements/index
* 请求参数： flag:name搜索标题，detail 搜索详情
           name 标题  
           type 类型   int  1 BUG，2 新需求，3 任务，4 改进建议
           priority 紧急程度  int 1 2 3 4 5
           schedule 状态  int 1 待审核 2 已驳回 3  处理中 4 处理完成
           
 ## 审核列表      
* 接口名称：审核列表
* 请求方法：get
* 请求示例：v1/requirements/examine-list
* 请求参数： flag:name搜索标题，creator搜索创建人,detail 搜索详情
           name 搜索的内容  
           type 类型   int  1 BUG，2 新需求，3 任务，4 改进建议
           priority 紧急程度  int 1 2 3 4 5


## 获取列表
* 接口名称：处理列表
* 请求方法：get
* 请求示例：v1/requirements/deal-list
* 请求参数： flag:name搜索标题，creator搜索创建人 ,detail 搜索详情
           name 搜索的值  
           type 类型   int  1 BUG，2 新需求，3 任务，4 改进建议
           priority 紧急程度  int 1 2 3 4 5
           status 状态  int 1 open 2 In Progress 3 Resovled 4 Reopened 5 Closed
           processingPerson 处理人 string 朱洪涛，叶先钱，周鹏许


## 添加
* 接口名称：添加
* 请求方法：post
* 请求示例：v1/requirement
* 请求参数： 

## 编辑
* 接口名称：编辑
* 请求方法：put
* 请求示例：v1/requirements/{id}
* 请求参数： 

## 审核/批量审核
* 接口名称：审核
* 请求方法：post
* 请求示例：v1/requirements/examine
* 请求参数：  
```json
{
    "condition": {
        "type":"pass",
        "ids": [1,2]
    }
}
```


删除和详情restful格式接口可用


