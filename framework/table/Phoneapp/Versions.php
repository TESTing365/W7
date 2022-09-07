<?php
/**
 * [WeEngine System] Copyright (c) 2013 WE7.CC
 */
namespace We7\Table\Phoneapp;

class Versions extends \We7Table {
	protected $tableName = 'phoneapp_versions';
	protected $primaryKey = 'id';
	protected $field = array(
		'uniacid',
		'version',
		'description',
		'modules',
		'createtime',
	);
	protected $default = array(
		'uniacid' => '',
		'version' => '',
		'description' => '',
		'modules' => '',
		'createtime' => '',
	);

	public function getById($id) {
		$data = $this->where('id', $id)->get();
		if (empty($data)) {
			return array();
		}
		$data['modules'] = iunserializer($data['modules']);
		return $data;
	}

	public function getLatestByUniacid($uniacid) {
		return $this->where('uniacid', $uniacid)->orderby('id', 'desc')->limit(4)->getall('id');
	}

	public function getLastByUniacid($uniacid) {
		$data = $this->where('uniacid', $uniacid)->orderby('id', 'desc')->get();
		if (empty($data)) {
			return array();
		}
		$data['modules'] = iunserializer($data['modules']);
		return $data;
	}

	public function getByUniacid($uniacid) {
		return $this->where('uniacid', $uniacid)->orderby('id', 'desc')->getall();
	}
}