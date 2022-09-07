<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 * $sn: pro/framework/class/loader.class.php : v 5a1adce731a4 : 2015/02/05 07:16:41 : Gorden $.
 */
defined('IN_IA') or exit('Access Denied');

/**
 * @return Loader
 */
function load() {
	static $loader;
	if (empty($loader)) {
		$loader = new Loader();
	}

	return $loader;
}

/**
 * 加载一个表抽象对象
 *
 * @param string $name 服务名称
 *
 * @return We7Table 表模型
 */
function table($name) {
	$table_classname = '\\We7\\Table\\';
	$subsection_name = explode('_', $name);
	if (1 == count($subsection_name)) {
		$table_classname .= ucfirst($subsection_name[0]) . '\\' . ucfirst($subsection_name[0]);
	} else {
		foreach ($subsection_name as $key => $val) {
			if (0 == $key) {
				$table_classname .= ucfirst($val) . '\\';
			} else {
				$table_classname .= ucfirst($val);
			}
		}
	}

	if (in_array($name, array(
		'account',
		'account_aliapp',
		'account_baiduapp',
		'account_toutiaoapp',
		'account_wxapp',
		'account_phoneapp',
		'account_webapp',
		'account_wechats',
		'article_category',
		'activity_clerks',
		'article_news',
		'article_notice',
		'article_comment',
		'basic_reply',
		'core_profile_fields',
		'core_sendsms_log',
		'core_attachment',
		'core_attachment_group',
		'core_paylog',
		'core_performance',
		'core_refundlog',
		'core_settings',
		'core_cover_reply',
		'core_message_notice_log',
		'core_menu_shortcut',
		'core_job',
		'core_menu',
		'cover_reply',
		'custom_reply',
		'images_reply',
		'mc_card',
		'mc_card_members',
		'mc_card_notices',
		'mc_card_notices_unread',
		'mc_card_record',
		'mc_card_sign_record',
		'mc_credits_recharge',
		'mc_credits_record',
		'mc_cash_record',
		'mc_chats_record',
		'mc_groups',
		'mc_handsel',
		'mc_mass_record',
		'mc_mapping_fans',
		'mc_fans_tag_mapping',
		'mc_mapping_ucenter',
		'mc_members',
		'mc_member_fields',
		'mc_member_address',
		'mc_fans_groups',
		'mc_oauth_fans',
		'mc_fans_tag',
		'modules_rank',
		'modules_bindings',
		'modules_plugin',
		'modules_plugin_rank',
		'modules_cloud',
		'modules_recycle',
		'modules',
		'modules_ignore',
		'music_reply',
		'news_reply',
		'paycenter_order',
		'phoneapp_versions',
		'qrcode',
		'qrcode_stat',
		'rule',
		'rule_keyword',
		'site_article',
		'site_article_comment',
		'site_category',
		'site_templates',
		'site_multi',
		'site_nav',
		'site_page',
		'site_slide',
		'site_styles',
		'site_styles_vars',
		'stat_visit',
		'stat_visit_ip',
		'system_welcome_binddomain',
		'uni_account',
		'uni_account_extra_modules',
		'uni_account_menus',
		'uni_account_modules',
		'uni_account_users',
		'uni_account_modules_shortcut',
		'uni_verifycode',
		'uni_group',
		'uni_modules',
		'uni_settings',
		'userapi_reply',
		'userapi_cache',
		'users',
		'users_group',
		'users_profile',
		'users_bind',
		'users_create_group',
		'users_extra_group',
		'users_extra_limit',
		'users_extra_modules',
		'users_extra_templates',
		'users_lastuse',
		'users_founder_group',
		'users_founder_own_users',
		'users_founder_own_users_groups',
		'users_founder_own_uni_groups',
		'users_founder_own_create_groups',
		'users_permission',
		'users_login_logs',
		'users_operate_history',
		'users_operate_star',
		'voice_reply',
		'video_reply',
		'wechat_news',
		'wechat_attachment',
		'wxapp_versions',
		'wxcard_reply',
		'uni_link_uniacid',
		'wxapp_general_analysis',
		'wxapp_register_version',
		'wxapp_reply',
	))) {
		return new $table_classname();
	}

	load()->classs('table');
	load()->table($name);
	$service = false;

	$class_name = "{$name}Table";
	if (class_exists($class_name)) {
		$service = new $class_name();
	}

	return $service;
}

/**
 * php文件加载器.
 *
 * @method bool func($name)
 * @method bool model($name)
 * @method bool classs($name)
 * @method bool web($name)
 * @method bool app($name)
 * @method bool library($name)
 */
class Loader {
	private $cache = array();
	private $singletonObject = array();
	private $libraryMap = array(
		'agent' => 'agent/agent.class',
		'captcha' => 'captcha/captcha.class',
		'pdo' => 'pdo/PDO.class',
		'qrcode' => 'qrcode/phpqrcode',
		'ftp' => 'ftp/ftp',
		'pinyin' => 'pinyin/pinyin',
		'pkcs7' => 'pkcs7/pkcs7Encoder',
		'json' => 'json/JSON',
		'phpmailer' => 'phpmailer/PHPMailerAutoload',
		'oss' => 'alioss/autoload',
		'qiniu' => 'qiniu/autoload',
		'cosv5' => 'cosv5/index',
	);
	private $loadTypeMap = array(
		'func' => '/framework/function/%s.func.php',
		'model' => '/framework/model/%s.mod.php',
		'classs' => '/framework/class/%s.class.php',
		'library' => '/framework/library/%s.php',
		'table' => '/framework/table/%s.table.php',
		'web' => '/web/common/%s.func.php',
		'app' => '/app/common/%s.func.php',
	);
	private $accountMap = array(
		'pay' => 'pay/pay',
		'account' => 'account/account',
		'weixin.account' => 'account/weixin.account',
		'weixin.platform' => 'account/weixin.platform',
		'aliapp.account' => 'account/aliapp.account',
		'baiduapp.account' => 'account/baiduapp.account',
		'toutiaoapp.account' => 'account/toutiaoapp.account',
		'phoneapp.account' => 'account/phoneapp.account',
		'webapp.account' => 'account/webapp.account',
		'wxapp.account' => 'account/wxapp.account',
		'wxapp.platform' => 'account/wxapp.platform',
		'wxapp.work' => 'account/wxapp.work',
	);

	public function __construct() {
		$this->registerAutoload();
	}

	public function registerAutoload() {
		spl_autoload_register(array($this, 'autoload'));
		//spl_autoload_register(array($this, 'autoloadBiz'));
	}

	public function autoload($class) {
		$section = array(
			'Table' => '/framework/table/',
		);
		//兼容旧版load()方式加载类
		$classmap = array(
			'We7Table' => 'table',
		);
		if (isset($classmap[$class])) {
			load()->classs($classmap[$class]);
		} elseif (preg_match('/^[0-9a-zA-Z\-\\\\_]+$/', $class)
			&& (0 === stripos($class, 'We7') || 0 === stripos($class, '\We7'))
			&& false !== stripos($class, '\\')) {
			$group = explode('\\', $class);
			$path = IA_ROOT . $section[$group[1]];
			unset($group[0]);
			unset($group[1]);
			$file_path = $path . implode('/', $group) . '.php';
			if (is_file($file_path)) {
				include $file_path;
			}
			//如果没有找到表，默认路由到Core命名空间，兼容之前命名不标准
			$file_path = $path . 'Core/' . implode('', $group) . '.php';
			if (is_file($file_path)) {
				include $file_path;
			}
		}
	}

	public function __call($type, $params) {
		global $_W;
		$name = $cachekey = array_shift($params);

		$accountMapKey = array_search($name, $this->accountMap);
		if (!empty($accountMapKey)) {
			$name = $cachekey = $accountMapKey;
		}

		if (!empty($this->cache[$type]) && isset($this->cache[$type][$cachekey])) {
			return true;
		}
		if (empty($this->loadTypeMap[$type])) {
			return true;
		}
		//第三方库文件因为命名差异，支持定义别名
		if ('library' == $type && !empty($this->libraryMap[$name])) {
			$name = $this->libraryMap[$name];
		}
		if ('classs' == $type && !empty($this->accountMap[$name])) {
			//兼容升级写法，后续直接去掉if判断
			$filename = sprintf($this->loadTypeMap[$type], $this->accountMap[$name]);
			if (file_exists(IA_ROOT . $filename)) {
				$name = $this->accountMap[$name];
			}
		}
		$file = sprintf($this->loadTypeMap[$type], $name);
		if (file_exists(IA_ROOT . $file)) {
			include IA_ROOT . $file;
			$this->cache[$type][$cachekey] = true;
		}

		return true;
	}

	/**
	 * 获取一个服务单例，目录是在framework/class目录下.
	 *
	 * @param unknown $name
	 */
	public function singleton($name) {
		if (isset($this->singletonObject[$name])) {
			return $this->singletonObject[$name];
		}
		$this->singletonObject[$name] = $this->object($name);

		return $this->singletonObject[$name];
	}

	/**
	 * 获取一个服务对象，目录是在framework/class目录下.
	 *
	 * @param unknown $name
	 */
	public function object($name) {
		$this->classs(strtolower($name));
		if (class_exists($name)) {
			return new $name();
		} else {
			return false;
		}
	}
}
