<?php
/**
 * [WeEngine System] Copyright (c) 2013 WE7.CC
 */
namespace We7\Table\Store;

class Order extends \We7Table {
	protected $tableName = 'site_store_order';
	protected $primaryKey = 'id';
	protected $field = array(
		'orderid',
		'goodsid',
		'duration',
		'buyer',
		'buyerid',
		'amount',
		'type',
		'changeprice',
		'createtime',
		'uniacid',
		'endtime',
		'wxapp',
		'is_wish',
	);
	protected $default = array(
		'orderid' => '',
		'goodsid' => 0,
		'duration' => 0,
		'buyer' => '',
		'buyerid' => 0,
		'amount' => 0,
		'type' => 0,
		'changeprice' => 0,
		'createtime' => 0,
		'uniacid' => '',
		'endtime' => '',
		'wxapp' => '',
		'is_wish' => 0,
	);

	public function getStatisticsInfoByDate($starttime, $endtime) {
		return $this->query
			->select('COUNT(id) AS total_orders, SUM(amount) AS total_amounts ')
			->where(array(
				'createtime >=' => $starttime,
				'createtime <=' => $endtime
			))->get();
	}

	public function getQueryJoinGoodsTable($order_type = 0, $goods_type = 0) {
		$query = $this->query
			->from($this->tableName, 'a')
			->leftjoin('site_store_goods', 'b')
			->on('a.goodsid', 'b.id');
		if ($order_type > 0) {
			$query->where('a.type', $order_type);
		}
		if ($goods_type > 0) {
			$query->where('b.type', $goods_type);
		}
		return $query;
	}

	public function getUserBuyAccountNum($uid) {
		$count = $this->getQueryJoinGoodsTable(STORE_ORDER_FINISH, STORE_TYPE_ACCOUNT)
			->select('b.account_num')
			->where('a.buyerid', intval($uid))
			->getall();
		if (empty($count)) {
			return 0;
		} else {
			$count = array_sum(array_column($count, 'account_num'));
			$deleted_account = table('store_create_account')->getUserDeleteNum($uid, ACCOUNT_TYPE_OFFCIAL_NORMAL);
			return max(0, $count - $deleted_account);
		}
	}

	public function getUserBuyWxappNum($uid) {
		$count = $this->getQueryJoinGoodsTable(STORE_ORDER_FINISH, STORE_TYPE_WXAPP)
			->select('b.wxapp_num')
			->where('a.buyerid', intval($uid))
			->getall();
		if (empty($count)) {
			return 0;
		} else {
			$count = array_sum(array_column($count, 'wxapp_num'));
			$deleted_account = table('store_create_account')->getUserDeleteNum($uid, ACCOUNT_TYPE_APP_NORMAL);
			return max(0, $count - $deleted_account);
		}
	}

	public function getApiOrderByUniacid($uniacid) {
		return $this->getQueryJoinGoodsTable(STORE_ORDER_FINISH, STORE_TYPE_API)
			->select('a.duration, b.api_num, b.price')
			->where('a.uniacid', intval($uniacid))
			->getall();
	}

	public function getUserBuyPackage($uniacid) {
		return $this->getQueryJoinGoodsTable(STORE_ORDER_FINISH, STORE_TYPE_PACKAGE)
			->where(function ($query) use ($uniacid) {
				$query->where('a.uniacid', $uniacid)->whereor('a.wxapp', $uniacid);
			})->getall('module_group');
	}

	/**获取公众号在站内商城购买的物品
	 * @param $uniacid string 1.指定的公众号
	 * @param $type string 1.商品类型
	 */
	public function searchAccountBuyGoods($uniacid, $type) {
		$this->query->from('site_store_goods', 'g')
			->leftjoin('site_store_order', 'r')
			->on(array('g.id' => 'r.goodsid'))
			->where('g.type', $type)
			->where('r.type', STORE_ORDER_FINISH);

		if ($type == STORE_TYPE_API) {
			$number_list = $this->query->where('r.uniacid', $uniacid)->select('(g.api_num * r.duration) as number')->getall('number');
			return array_sum(array_keys($number_list));

		} else{
			$this->query->where(function ($query) use ($uniacid) {
				$query->where('r.uniacid', $uniacid)->whereor('r.wxapp', $uniacid);
			});

			load()->model('store');
			$all_type = store_goods_type_info();
			if ($all_type[$type]['group'] == 'module') {
				$keyfield = 'module';
			} else {
				$type_name = array(
					STORE_TYPE_PACKAGE => 'module_group',
				);
				$keyfield = empty($type_name[$type]) ? '' : $type_name[$type];
			}
			return $this->query->getall($keyfield);
		}
	}

	public function searchWithEndtime() {
		$this->query->where('r.endtime >=', time());
		return $this;
	}
}