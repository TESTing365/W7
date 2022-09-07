<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 */
namespace We7\Table\Core;

class Job extends \We7Table {
	protected $tableName = 'core_job';
	protected $primaryKey = 'id';
	protected $field = array(
		'type',
		'uniacid',
		'payload',
		'status',
		'title',
		'handled',
		'total',
		'createtime',
		'updatetime',
		'endtime',
		'uid',
		'isdeleted',
	);
	protected $default = array(
		'type' => '',
		'uniacid' => '0',
		'payload' => '',
		'status' => '0',
		'title' => '',
		'handled' => '0',
		'total' => '0',
		'createtime' => TIMESTAMP,
		'updatetime' => TIMESTAMP,
		'endtime' => '0',
		'uid' => '0',
		'isdeleted' => '0',
	);

	const DELETE_ACCOUNT = 10; //删除公众号素材任务
	const SYNC_FANS = 20; //同步粉丝任务

	/**
	 *  创建一个删除公众号素材的任务
	 * @param $uniacid
	 */
	public function createDeleteAccountJob($uniacid, $accountName, $total, $uid) {
		$exits_job = $this->exitsJob($uniacid, self::DELETE_ACCOUNT);
		if ($exits_job) {
			return $exits_job['id'];
		}
		$this->fill(array(
			'type' => self::DELETE_ACCOUNT,
			'title'=> "删除{$accountName}的素材数据",
			'uniacid'=>$uniacid,
			'total'=> $total,
			'uid'=>$uid
		));
		if ($this->save()) {
			return pdo_insertid();
		} else {
			return false;
		}
	}

	public function exitsJob($uniacid, $type) {
		$result = self::getQuery()
			->where('uniacid', $uniacid)
			->where('type', $type)
			->get();
		return !empty($result);
	}

	/**
	 *  清除已完成任务
	 */
	public function clear($uid, $isfounder) {
		$this->where('status', 1)->fill('isdeleted', 1);
		if (!$isfounder) {
			$this->where('uid', $uid);
		}
		return $this->save();
	}
}