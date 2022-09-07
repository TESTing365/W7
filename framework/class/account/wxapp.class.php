<?php

/**
 * [WeEngine System] Copyright (c) 2013 WE7.CC
 * User: fanyk
 * Date: 2017/12/5
 * Time: 9:38
 * @property-read $appdomain 小程序域名
 * @property-read $link_uniacid 绑定的uniacid
 * @property-read $entry_id 模块小程序入口id
 * @property-read $module_wxapp_entry_url 模块小程序入口链接
 */
class Wxapp {


	private $wxapp_version = null;
	private $uniaccount = null;
	private $account_wxapp = null;

	private $modules = null;
	private $uniacid = null;

	public function __construct() {

	}
	/**
	 *  根据 $version_id 创建
	 * @param $version_id
	 */
	public static function createByVid($version_id) {
		$wxapp = new Wxapp();
		$wxapp_version = static::getQuery()->from('wxapp_versions')->where('id', $version_id)->get();
		if($wxapp_version && isset($wxapp_version['uniacid'])) {
			$wxapp->wxapp_version = $wxapp_version;
			$wxapp->uniacid = $wxapp_version['uniacid'];
			return $wxapp;
		}
		return null;
	}

	/**
	 *  根据 $uniacid $version 创建
	 * @param $version_id
	 */
	public static function createByVersion($uniacid, $version) {
		$wxapp = new Wxapp();
		$wxapp_version = static::getQuery()->from('wxapp_versions')
			->where('uniacid', $uniacid)
			->where('version', $version)->get();
		if($wxapp_version && isset($wxapp_version['uniacid'])) {
			$wxapp->wxapp_version = $wxapp_version;
			$wxapp->uniacid = $wxapp_version['uniacid'];
			return $wxapp;
		}
		return null;
	}

	/**
	 *  是否是打包小程序
	 */
	public function isPackageApp() {
		$version = $this->wxappVersion();
		return isset($version['type']) ? $version['type'] == WXAPP_CREATE_MODULE : 0;
	}
	/**
	 *  获取普通模块小程序的入口URL
	 */
	public function getModuleWxappUrl() {
		global $_W;
		$entry_id = $this->entry_id;
		if (! $entry_id) {
			return false;
		}
		$uniacid = $this->uniacid;
		if($this->link_uniacid) {
			$uniacid = $this->link_uniacid;
		}
		$params = array('eid'=>$entry_id, 'i'=>$uniacid);
		$domain  = $this->appdomain;
		if(! $domain) {
			$domain = $_W['siteroot'].'app/index.php?';
		}
		return $domain.http_build_query($params);
	}

	/**
	 *  获取绑定的uniacid
	 * @return bool
	 */
	public function getLinkUniacid() {
		$modules = $this->modules();
		if($modules) {
			$module = current($modules);
			if (!empty($module['uniacid']) && intval($module['uniacid']) > 0) {
				return $module['uniacid'];
			}
		}
		return false;
	}


	public function __get($name) {

		if($name == 'appdomain') {
			$account_wxapp = $this->accountWxapp();
			return isset($account_wxapp['appdomain']) ? $account_wxapp['appdomain'] : null;
		}

		if($name == 'link_uniacid') {
			return $this->getLinkUniacid();
		}

		if($name == 'entry_id') {
			$version = $this->wxappVersion();
			return isset($version['entry_id']) ? $version['entry_id'] : 0;
		}

		if ($name == 'module_wxapp_entry_url') {
			return $this->getModuleWxappUrl();
		}

		return null;
	}

	private static function getQuery() {
		return new Query();
	}



	private function accountWxapp() {
		if(is_null($this->account_wxapp)) {
			$this->account_wxapp = self::getQuery()->from('account_wxapp')
				->where('uniacid',$this->uniacid)->get();
		}
		return $this->account_wxapp;
	}

	private function wxappVersion() {
		if(is_null($this->wxapp_version)) {
			$this->wxapp_version = self::getQuery()->from('wxapp_versions')->where('uniacid', $this->uniacid)->get();
		}
		return $this->wxapp_version;
	}

	private function uniAccount() {
		if(is_null($this->uniaccount)) {
			$this->uniaccount = self::getQuery()->from('uniaccount')->where('uniacid', $this->uniacid)->get();
		}
		return $this->uniaccount;
	}

	private function modules() {
		if(is_null($this->modules)) {
			$wxapp_version =  $this->wxappVersion();
			$this->modules = isset($wxapp_version['modules']) ? unserialize($wxapp_version['modules']) : false;
		}
		return $this->modules;
	}
}