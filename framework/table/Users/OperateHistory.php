<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 */
namespace We7\Table\Users;

class OperateHistory extends \We7Table {
	protected $tableName = 'users_operate_history';
	protected $primaryKey = 'id';
	protected $field = array(
		'createtime',
		'module_name',
		'type',
		'uid',
		'uniacid',
	);
	protected $default = array(
		'createtime' => '',
		'module_name' => '',
		'type' => '',
		'uid' => '',
		'uniacid' => '',
	);

	public function getALlByUid($uid) {
		return $this->query->where('uid', $uid)->orderby('createtime', 'DESC')->getall();
	}
	public function deleteByUidTypeOperate($data) {
		return $this->query->where($data)->delete();
	}
	public function searchWithLimit($limit_num) {
		return $this->query->limit($limit_num);
	}
}