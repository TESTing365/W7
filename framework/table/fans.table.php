<?php
/**
 *
 * [WeEngine System] Copyright (c) 2013 WE7.CC
 */

defined('IN_IA') or exit('Access Denied');

class FansTable extends We7Table {
	protected $field = array('uid');
	protected $tableName = 'mc_mapping_fans';
	public function fansAll($openids) {
		global $_W;
		return $this->query->from('mc_mapping_fans')
			->where('openid', $openids)
			->where('uniacid', $_W['uniacid'])
			->where('acid', $_W['acid'])
			->getall('openid');
	}

	public function fansInfo($openid) {
		return $this->query->from('mc_mapping_fans')->where('openid', $openid)->get();
	}

	public function oauthFans($oauth_openid) {
		return $this->query->from('mc_oauth_fans')->where('oauth_openid', $oauth_openid)->get();
	}

	public function tagGroup($uniacid) {
		$groups = $this->query->from('mc_fans_groups')->where('uniacid', $uniacid)->getcolumn('groups');
		return !empty($groups) ? iunserializer($groups) : array();
	}
}