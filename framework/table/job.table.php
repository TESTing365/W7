<?php
/**
 *
 * [WeEngine System] Copyright (c) 2013 WE7.CC
 */

defined('IN_IA') or exit('Access Denied');


class JobTable extends We7Table {

	protected $tableName = 'core_job';
	protected $field = array('type', 'payload', 'status', 'handled', 'uniacid', 'title', 'total', 'createtime', 'endtime', 'updatetime', 'isdeleted', 'uid');

	protected $default = array('status'=>0, 'handled'=>0, 'total'=>0, 'createtime'=>'custom', 'updatetime'=>'custom', 'isdeleted'=>0, 'uid'=>0);
	const DELETE_ACCOUNT = 10;
	const SYNC_FANS = 20;

	/**
	 *  使用默认创建时机
	 * @return int
	 */
	protected function defaultCreatetime() {
		return TIMESTAMP;
	}

	/** 默认更新时间
	 * @return int
	 */
	protected function defaultUpdatetime() {
		return TIMESTAMP;
	}
	/**
	 * 获取所有任务
	 * @return mixed
	 */
	public 	function jobs() {
		return $this->where('status', 0)->getall();
	}

	/**
	 *  所有任务
	 */
	public function alljobs() {
		return $this->getall();
	}

	/**
	 *  是否有已存在的任务
	 * @param $uniacid
	 * @param $type
	 */
	public function exitsJob($uniacid, $type)
	{
		$result = table('job')->where('uniacid', $uniacid)->where('type', $type)->get();
		return !empty($result);
	}
	/**
	 *  创建一个删除公众号素材的任务
	 * @param $uniacid
	 */
	public function createDeleteAccountJob($uniacid, $accountName, $total, $uid)
	{
		// 任务已存在
		if ($this->exitsJob($uniacid, self::DELETE_ACCOUNT)) {
			return error(1, '任务已存在');
		}

		$data = array(
			'type' => self::DELETE_ACCOUNT,
			'title'=> "删除{$accountName}的素材数据",
			'uniacid'=>$uniacid,
			'total'=> $total,
			'uid'=>$uid
		);
		return $this->createJob($data);
	}

	/**
	 *  创建同步粉丝任务
	 * @param $uniacid
	 */
	public function createSyncFans($uniacid, $accountName, $total ) {
		// 任务已存在
		if ($this->exitsJob($uniacid, self::SYNC_FANS)) {
			return error(1, '同步任务已存在');
		}
		$data = array(
			'type' => self::SYNC_FANS,
			'title'=> "同步 $accountName ($uniacid) 的公众号粉丝数据",
			'uniacid'=>$uniacid,
		);
		return $this->createJob($data);
	}

	private function createJob($data)
	{
		$this->fill($data);
		$result = $this->save();
		if ($result) {
			return pdo_insertid();
		}
		return $result;
	}

	/**
	 *  清除已完成任务
	 */
	public function clear($uid, $isfounder) {
		$table = table('job')
			->where('status', 1)
			->fill('isdeleted', 1);
		if (!$isfounder) {
			$table->where('uid', $uid);
		}
		return $table->save();
	}
}