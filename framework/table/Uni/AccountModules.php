<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 */
namespace We7\Table\Uni;

class AccountModules extends \We7Table {
	protected $tableName = 'uni_account_modules';
	protected $primaryKey = 'id';
	protected $field = array(
		'uniacid',
		'module',
		'enabled',
		'shortcut',
		'displayorder',
		'settings',
	);
	protected $default = array(
		'uniacid' => '',
		'module' => '',
		'enabled' => 0,
		'shortcut' => 0,
		'displayorder' => 0,
		'settings' => '',
	);
	
	public function getByUniacidAndModule($module_name, $uniacid) {
		$result = $this->query->where('module', $module_name)->where('uniacid', $uniacid)->get();
		if (!empty($result)) {
			$result['settings'] = iunserializer($result['settings']);
		}
		return $result;
	}
}