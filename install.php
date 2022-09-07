<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 * WeEngine is NOT a free software, it under the license terms, visited http://www.w7.cc/ for more details.
 */
ini_set('display_errors', 0);
error_reporting(0);
set_time_limit(0);

ob_start();
define('IA_INSTALL_ROOT', str_replace("\\",'/', dirname(__FILE__)));
define('INSTALL_VERSION', '1.1');
define('API_HOST', 'http://openapi.w7.cc');
define('API_OAUTH_LOGIN_URL', API_HOST . '/oauth/login-url/index');
define('API_OAUTH_ACCESSTOKEN', API_HOST . '/site/register/accesstoken/with-code');
define('API_OAUTH_REGISTER_SITE', API_HOST . '/site/register/index');
define('ERROR_LOG_FILE', './data/logs/error_log.php');
set_error_handler("handleError");

$actions = array('environment', 'oauth', 'install', 'chunktotal', 'download_percent', 'download', 'register_callback', 'login', 'get_config');
$action = !empty($_GET['step']) ? $_GET['step'] : '';
$action = in_array($action, $actions) ? $action : '';

$is_https = $_SERVER['SERVER_PORT'] == 443 ||
(!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) != 'off') ||
!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) == 'https' ||
!empty($_SERVER['HTTP_X_CLIENT_SCHEME']) && strtolower($_SERVER['HTTP_X_CLIENT_SCHEME']) == 'https' ||
!empty($_SERVER['HTTP_X_CLIENT_PROTO']) && strtolower($_SERVER['HTTP_X_CLIENT_PROTO']) == 'https'
	? true : false;
$sitepath = substr($_SERVER['PHP_SELF'], 0, strrpos($_SERVER['PHP_SELF'], '/'));
$sitepath = str_replace('/install.php', '', $sitepath);
$siteroot = htmlspecialchars(($is_https ? 'https://' : 'http://') . (!empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '') . $sitepath);
$cdn_source_file = we7_getcookie('cdn_source_url');

$accesstoken = we7_get_accesstoken();
if (empty($accesstoken)) {
	$action = 'oauth';
}
if ($action == 'oauth') {
	$code = isset($_GET['code']) ? trim($_GET['code']) : '';
	$install_url = $siteroot . '/install.php';
	if (empty($code)) {
		$url = $siteroot . '/install.php?step=oauth';
		$callback = urlencode($url);
		$data = we7_request_api(API_OAUTH_LOGIN_URL,array('appid' => 'wb939fa59e0a189d5f', 'redirect' => $callback));
		if (is_array($data) && isset($data['error'])) {
			$error = json_decode($data['error'], true);
			$info = '云服务出了点问题，请联系官方处理或 <a style="color:#3296fa;text-decoration:none" href=' . $install_url . '>重新登录</a> 。<br>' . ($error['error'] ? ('详情：' . $error['error']) : '');
			exit(we7_error_page($info));
		}
		$forward = $data['url'];
		header('Location: ' . $forward);
		exit();
	} else {
		$params = array('code' => $code, 'timestamp' => time(), 'url' => $siteroot);
		$data = we7_request_api(API_OAUTH_ACCESSTOKEN, $params);
		if (is_array($data) && isset($data['error'])) {
			$error = json_decode($data['error'], true);
			$info = '获取accesstoken失败，请 <a style="color:#3296fa;text-decoration:none" href=' . $install_url . '>重新登录</a> 。<br>' . ($error['error'] ? ('详情：' . $error['error']) : '');
			exit(we7_error_page($info));
		}
		we7_setcookie('accesstoken', $data);
		header('Location: ' . $siteroot . '/install.php?check=1');
		exit();
	}
}
if($action == 'environment') {
	$server['upload'] = @ini_get('file_uploads') ? ini_get('upload_max_filesize') : 'unknow';
	$server['upload'] = strtolower($server['upload']);
	if ($server['upload'] == 'unknow' || !strstr($server['upload'], 'm')) {
		$ret['upload']['failed'] = true;
		$ret['upload']['name'] = '上传限制';
		$ret['upload']['result'] = $server['upload'];
	}
	if(version_compare(PHP_VERSION, '5.6.0') == -1) {
		$ret['version']['failed'] = true;
		$ret['version']['name'] = 'PHP版本';
		$ret['version']['result'] = PHP_VERSION . '（最低要求5.6.0）';
	}
	if(version_compare(PHP_VERSION, '7.0.0') == -1 && version_compare(PHP_VERSION, '5.6.0') >= 0) {
		$ret['always_populate_raw_post_data']['failed'] = @ini_get('always_populate_raw_post_data') != '-1';
		$ret['always_populate_raw_post_data']['name'] = 'always_populate_raw_post_data配置';
		$ret['always_populate_raw_post_data']['result'] = @ini_get('always_populate_raw_post_data');
		$ret['always_populate_raw_post_data']['handle'] = 'https://market.w7.cc/IndependentEngine';
	}
	$host = strpos($_SERVER['HTTP_HOST'], ':') ? parse_url($_SERVER['HTTP_HOST'], PHP_URL_HOST) : $_SERVER['HTTP_HOST'];
	if (!we7_network_enable($host)) {
		$ret['network_enabled']['failed'] = true;
		$ret['network_enabled']['name'] = '外网可访问性';
		$ret['network_enabled']['result'] = '外网不可访问';
	}
	$ret['fopen']['ok'] = @ini_get('allow_url_fopen') && function_exists('fsockopen');
	if(!$ret['fopen']['ok']) {
		$ret['fopen']['failed'] = true;
		$ret['fopen']['name'] = 'fopen';
		$ret['fopen']['result'] = '不支持fopen';
	}
	
	$ret['dom']['ok'] = class_exists('DOMDocument');
	if(!$ret['dom']['ok']) {
		$ret['dom']['failed'] = true;
		$ret['dom']['name'] = 'DOMDocument';
		$ret['dom']['result'] = '没有启用DOMDocument';
	}
	
	$ret['session']['ok'] = ini_get('session.auto_start');
	if(!empty($ret['session']['ok']) && strtolower($ret['session']['ok']) == 'on') {
		$ret['session']['failed'] = true;
		$ret['session']['name'] = 'session.auto_start开启';
		$ret['session']['result'] = '系统session.auto_start开启';
	}
	
	$ret['asp_tags']['ok'] = ini_get('asp_tags');
	if(!empty($ret['asp_tags']['ok']) && strtolower($ret['asp_tags']['ok']) == 'on') {
		$ret['asp_tags']['failed'] = true;
		$ret['asp_tags']['name'] = 'asp_tags';
		$ret['asp_tags']['result'] = 'asp_tags开启状态';
	}
	
	$ret['root']['ok'] = local_writeable(IA_INSTALL_ROOT);
	if(!$ret['root']['ok']) {
		$ret['root']['failed'] = true;
		$ret['root']['name'] = '本地目录写入';
		$ret['root']['result'] = '本地目录无法写入';
	}
	$ret['data']['ok'] = local_writeable(IA_INSTALL_ROOT . '/data');
	if(!$ret['data']['ok']) {
		$ret['data']['failed'] = true;
		$ret['data']['name'] = 'data目录写入';
		$ret['data']['result'] = 'data目录无法写入';
	}
	
	foreach (we7_need_extension() as $extension) {
		$if_ok = extension_loaded($extension);
		if (!$if_ok) {
			$ret[$extension]['failed'] = true;
			$ret[$extension]['name'] = $extension . '扩展';
			$ret[$extension]['result'] = '不支持' . $extension;
		}
	}
	
	$result = array();
	foreach($ret as $key => $value) {
		if(version_compare(PHP_VERSION, '7.0.0') >= 0 && in_array($key, array('mcrypt', 'always_populate_raw_post_data'))) {
			continue;
		}
		if(!empty($value['failed'])) {
			$value['handle'] = !empty($value['handle']) ? $value['handle'] : 'https://market.w7.cc/IndependentEngine';
			$result[] = $value;
		}
	}
	if (empty($result)) {
		exit(we7_error(0));
	} else {
		exit(we7_error(434, $result));
	}
}
if ($action == 'chunktotal') {
	exit(we7_error(0, array('total' => 1)));
}
if ($action == 'download_percent') {
	exit(we7_error(0, 100));
}
if ($action == 'download') {
	exit(we7_error(0, 1));
}
if ($action == 'get_config') {
	exit(we7_error(0, array()));
}

if ($action == 'install') {
	if (!file_exists(IA_INSTALL_ROOT . '/data/config.php') || !empty($_POST)) {
		$server = trim($_POST['server']);
		$db_username = trim($_POST['username']);
		$db_password = trim($_POST['password']);
		$db_name = trim($_POST['name']);
		$db_prefix = trim($_POST['prefix']);
		$db_prefix = !empty($db_prefix) ? $db_prefix : 'ims_';
		if (empty($server)) {
			exit(we7_error(419, '数据库主机不可为空!'));
		}
		if (empty($db_username)) {
			exit(we7_error(419, '数据库用户不可为空!'));
		}
		if (empty($db_password)) {
			exit(we7_error(419, '数据库密码不可为空!'));
		}
		if (empty($db_name)) {
			exit(we7_error(419, '数据库名称不可为空!'));
		}
		$database_result = we7_build_config($server, $db_username, $db_password, $db_name, $db_prefix);
		if ($database_result !== true) {
			exit(we7_error(419, $database_result));
		}
	}
	if (!file_exists(IA_INSTALL_ROOT . '/data/db.lock')) {
		$database_result = we7_db();
		if ($database_result !== true) {
			exit(we7_error(420, $database_result));
		}
		touch(IA_INSTALL_ROOT . '/data/db.lock');
	}
	if (!file_exists(IA_INSTALL_ROOT . '/data/install.lock')) {
		we7_register_site();
	}
	touch(IA_INSTALL_ROOT . '/data/install.lock');
	exit(we7_error(0));
}

if ($action == 'login') {
	$sitename = trim(empty($_POST['sitename']) ? '' : $_POST['sitename']);
	$username = trim(empty($_POST['username']) ? '' : $_POST['username']);
	$password = trim(empty($_POST['password']) ? '' : $_POST['password']);
	
	we7_finish();
	if ((!empty($username) && $username != 'admin') || (!empty($password) && $password != '123456')) {
		if (!safe_gpc_string($username)) {
			exit(we7_error(400, '必须输入用户名，格式为 3-15 位字符，可以包括汉字、字母（不区分大小写）、数字、下划线和句点。'));
		}
		$password = safe_check_password($password);
		if (is_array($password)) {
			exit(we7_error(400, $password['message']));
		}
		$user_result = we7_update_user($username, $password);
		if (!$user_result) {
			exit(we7_error(400, '修改用户名密码失败.'));
		}
	}
	rename('install.php', 'install.php.bak');
	@unlink(IA_INSTALL_ROOT . '/data/logs/data.json');
	exit(we7_error(0));
}

if ($action == 'register_callback') {
	exit(we7_error(0));
}

function handleError($code, $description, $file = null, $line = null) {
	list($error, $log) = map_error_code($code);
	$data = array(
		'date' => date('Y-m-d H:i:s', time()),
		'level' => $log,
		'code' => $code,
		'error' => $error,
		'description' => $description,
		'file' => $file,
		'line' => $line,
		'message' => $error . ' (' . $code . '): ' . $description . ' in [' . $file . ', line ' . $line . ']'
	);
	return file_log($data);
}

function file_log($logData, $fileName = ERROR_LOG_FILE) {
	if (!is_dir('data/logs')) {
		local_mkdirs('data/logs');
	}
	$fh = fopen($fileName, 'a+');
	if (is_array($logData)) {
		$logData = print_r($logData, 1);
	}
	$logData = '<?php exit;?>' . PHP_EOL . $logData;
	$status = fwrite($fh, $logData);
	fclose($fh);
	return (bool)$status;
}

function map_error_code($code) {
	$error = $log = null;
	switch ($code) {
		case E_PARSE:
		case E_ERROR:
		case E_CORE_ERROR:
		case E_COMPILE_ERROR:
		case E_USER_ERROR:
			$error = 'Fatal Error';
			$log = LOG_ERR;
			break;
		case E_WARNING:
		case E_USER_WARNING:
		case E_COMPILE_WARNING:
		case E_RECOVERABLE_ERROR:
			$error = 'Warning';
			$log = LOG_WARNING;
			break;
		case E_NOTICE:
		case E_USER_NOTICE:
			$error = 'Notice';
			$log = LOG_NOTICE;
			break;
		case E_STRICT:
			$error = 'Strict';
			$log = LOG_NOTICE;
			break;
		case E_DEPRECATED:
		case E_USER_DEPRECATED:
			$error = 'Deprecated';
			$log = LOG_NOTICE;
			break;
		default:
			break;
	}
	return array($error, $log);
}

function local_writeable($dir) {
	$writeable = 0;
	if(!is_dir($dir)) {
		@mkdir($dir, 0777);
	}
	if(is_dir($dir)) {
		if($fp = fopen("$dir/test.txt", 'w')) {
			fclose($fp);
			unlink("$dir/test.txt");
			$writeable = 1;
		} else {
			$writeable = 0;
		}
	}
	return $writeable;
}

function local_salt($length = 8) {
	$strs = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklnmopqrstuvwxyz0123456789';
	$result = substr(str_shuffle($strs),mt_rand(0,strlen($strs)-($length + 1)),$length);
	return $result;
}

function local_config() {
	$cfg = <<<EOF
<?php
defined('IN_IA') or exit('Access Denied');

\$config = array();

\$config['db']['master']['host'] = '{DB_HOST}';
\$config['db']['master']['username'] = '{DB_USERNAME}';
\$config['db']['master']['password'] = '{DB_PASSWORD}';
\$config['db']['master']['port'] = '{DB_PORT}';
\$config['db']['master']['database'] = '{DB_DATABASE}';
\$config['db']['master']['charset'] = 'utf8';
\$config['db']['master']['pconnect'] = 0;
\$config['db']['master']['tablepre'] = '{DB_TABLEPRE}';

\$config['db']['slave_status'] = false;
\$config['db']['slave']['1']['host'] = '';
\$config['db']['slave']['1']['username'] = '';
\$config['db']['slave']['1']['password'] = '';
\$config['db']['slave']['1']['port'] = '3307';
\$config['db']['slave']['1']['database'] = '';
\$config['db']['slave']['1']['charset'] = 'utf8';
\$config['db']['slave']['1']['pconnect'] = 0;
\$config['db']['slave']['1']['tablepre'] = 'ims_';
\$config['db']['slave']['1']['weight'] = 0;

\$config['db']['common']['slave_except_table'] = array('core_sessions');

// --------------------------  CONFIG COOKIE  --------------------------- //
\$config['cookie']['pre'] = '{COOKIEPRE}';
\$config['cookie']['domain'] = '';
\$config['cookie']['path'] = '/';

// --------------------------  CONFIG SETTING  --------------------------- //
\$config['setting']['charset'] = 'utf-8';
\$config['setting']['cache'] = 'mysql';
\$config['setting']['timezone'] = 'Asia/Shanghai';
\$config['setting']['memory_limit'] = '256M';
\$config['setting']['filemode'] = 0644;
\$config['setting']['authkey'] = '{AUTHKEY}';
\$config['setting']['founder'] = '1';
\$config['setting']['development'] = 0;
\$config['setting']['referrer'] = 0;

// --------------------------  CONFIG UPLOAD  --------------------------- //
\$config['upload']['image']['extentions'] = array('gif', 'jpg', 'jpeg', 'png');
\$config['upload']['image']['limit'] = 5000;
\$config['upload']['attachdir'] = 'attachment';
\$config['upload']['audio']['extentions'] = array('mp3');
\$config['upload']['audio']['limit'] = 5000;

// --------------------------  CONFIG MEMCACHE  --------------------------- //
\$config['setting']['memcache']['server'] = '';
\$config['setting']['memcache']['port'] = 11211;
\$config['setting']['memcache']['pconnect'] = 1;
\$config['setting']['memcache']['timeout'] = 30;
\$config['setting']['memcache']['session'] = 1;

// --------------------------  CONFIG PROXY  --------------------------- //
\$config['setting']['proxy']['host'] = '';
\$config['setting']['proxy']['auth'] = '';
EOF;
	return trim($cfg);
}

function local_mkdirs($path) {
	if(!is_dir($path)) {
		local_mkdirs(dirname($path));
		mkdir($path);
	}
	return is_dir($path);
}

function local_run($sql, $link, $db) {
	if(!isset($sql) || empty($sql)) return;
	
	$sql = str_replace("\r", "\n", str_replace(' ims_', ' '.$db['prefix'], $sql));
	$sql = str_replace("\r", "\n", str_replace(' `ims_', ' `'.$db['prefix'], $sql));
	$ret = array();
	$num = 0;
	foreach(explode(";\n", trim($sql)) as $query) {
		$ret[$num] = '';
		$queries = explode("\n", trim($query));
		foreach($queries as $query) {
			$ret[$num] .= (isset($query[0]) && $query[0] == '#') || (isset($query[1]) && isset($query[1]) && $query[0].$query[1] == '--') ? '' : $query;
		}
		$num++;
	}
	unset($sql);
	foreach($ret as $query) {
		$query = trim($query);
		if($query) {
			$link->exec($query);
			if($link->errorCode() != '00000') {
				$errorInfo = $link->errorInfo();
				trigger_error($errorInfo[0] . ": " . $errorInfo[2], E_USER_WARNING);
				exit($query);
			}
		}
	}
}

function local_create_sql($schema) {
	$pieces = explode('_', $schema['charset']);
	$charset = $pieces[0];
	$engine = 'InnoDB';
	$sql = "CREATE TABLE IF NOT EXISTS `{$schema['tablename']}` (\n";
	foreach ($schema['fields'] as $value) {
		if(!empty($value['length'])) {
			$length = "({$value['length']})";
		} else {
			$length = '';
		}
		
		$signed = empty($value['signed']) ? ' unsigned' : '';
		if(empty($value['null'])) {
			$null = ' NOT NULL';
		} else {
			$null = '';
		}
		if(isset($value['default'])) {
			$default = " DEFAULT '" . $value['default'] . "'";
		} else {
			$default = '';
		}
		if($value['increment']) {
			$increment = ' AUTO_INCREMENT';
		} else {
			$increment = '';
		}
		
		$sql .= "`{$value['name']}` {$value['type']}{$length}{$signed}{$null}{$default}{$increment},\n";
	}
	foreach ($schema['indexes'] as $value) {
		$fields = implode('`,`', $value['fields']);
		if($value['type'] == 'index') {
			$sql .= "KEY `{$value['name']}` (`{$fields}`),\n";
		}
		if($value['type'] == 'unique') {
			$sql .= "UNIQUE KEY `{$value['name']}` (`{$fields}`),\n";
		}
		if($value['type'] == 'primary') {
			$sql .= "PRIMARY KEY (`{$fields}`),\n";
		}
	}
	$sql = rtrim($sql);
	$sql = rtrim($sql, ',');
	
	$sql .= "\n) ENGINE=$engine DEFAULT CHARSET=$charset;\n\n";
	return $sql;
}

function install_authcode($string, $operation = 'DECODE', $key = '', $expiry = 0) {
	$ckey_length = 4;
	$key = md5($key != '' ? $key : $GLOBALS['_W']['config']['setting']['authkey']);
	$keya = md5(substr($key, 0, 16));
	$keyb = md5(substr($key, 16, 16));
	$keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length) : substr(md5(microtime()), -$ckey_length)) : '';
	
	$cryptkey = $keya . md5($keya . $keyc);
	$key_length = strlen($cryptkey);
	
	$string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0) . substr(md5($string . $keyb), 0, 16) . $string;
	$string_length = strlen($string);
	
	$result = '';
	$box = range(0, 255);
	
	$rndkey = array();
	for ($i = 0; $i <= 255; $i++) {
		$rndkey[$i] = ord($cryptkey[$i % $key_length]);
	}
	
	for ($j = $i = 0; $i < 256; $i++) {
		$j = ($j + $box[$i] + $rndkey[$i]) % 256;
		$tmp = $box[$i];
		$box[$i] = $box[$j];
		$box[$j] = $tmp;
	}
	
	for ($a = $j = $i = 0; $i < $string_length; $i++) {
		$a = ($a + 1) % 256;
		$j = ($j + $box[$a]) % 256;
		$tmp = $box[$a];
		$box[$a] = $box[$j];
		$box[$j] = $tmp;
		$result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
	}
	
	if ($operation == 'DECODE') {
		if ((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26) . $keyb), 0, 16)) {
			return substr($result, 26);
		} else {
			return '';
		}
	} else {
		return $keyc . str_replace('=', '', base64_encode($result));
	}
	
}

function we7_network_enable($host) {
	if (empty($host)) {
		trigger_error('function name we7_network_enable \'s param : $host is empty');
		return false;
	}
	$httphost_is_ip = preg_match('/^(1\d{2}|2[0-4]\d|25[0-5]|[1-9]\d|[1-9])\.(1\d{2}|2[0-4]\d|25[0-5]|[1-9]\d|\d)\.(1\d{2}|2[0-4]\d|25[0-5]|[1-9]\d|\d)\.(1\d{2}|2[0-4]\d|25[0-5]|[1-9]\d|\d)$/', $host);
	if ($httphost_is_ip) {
		$if_local_network10 = preg_match('/^10\.(1\d{2}|2[0-4]\d|25[0-5]|[1-9]\d|\d)\.(1\d{2}|2[0-4]\d|25[0-5]|[1-9]\d|\d)\.(1\d{2}|2[0-4]\d|25[0-5]|[1-9]\d|\d)$/', $host);
		if ($if_local_network10) {
			return false;
		}
		$if_local_network172 = preg_match('/^172\.(1[6-9]|2[0-9]|3[0-1])\.(1\d{2}|2[0-4]\d|25[0-5]|[1-9]\d|\d)\.(1\d{2}|2[0-4]\d|25[0-5]|[1-9]\d|\d)$/', $host);
		if ($if_local_network172) {
			return false;
		}
		$if_local_network192 = preg_match('/^192\.168\.(1\d{2}|2[0-4]\d|25[0-5]|[1-9]\d|\d)\.(1\d{2}|2[0-4]\d|25[0-5]|[1-9]\d|\d)$/', $host);
		if ($if_local_network192) {
			return false;
		}
	} else {
		$dns_record = dns_get_record($host, DNS_A);
		if (empty($dns_record) ||
			empty($dns_record[0]['ip']) ||
			$dns_record[0]['ip'] == '127.0.0.1' ||
			strpos($dns_record[0]['ip'], '10.') === 0) {
			return false;
		}
		$if_local_network172 = preg_match('/^172\.(1[6-9]|2[0-9]|3[0-1])\.(1\d{2}|2[0-4]\d|25[0-5]|[1-9]\d|\d)\.(1\d{2}|2[0-4]\d|25[0-5]|[1-9]\d|\d)$/', $dns_record[0]['ip']);
		if ($if_local_network172) {
			return false;
		}
		$if_local_network192 = preg_match('/^192\.168\.(1\d{2}|2[0-4]\d|25[0-5]|[1-9]\d|\d)\.(1\d{2}|2[0-4]\d|25[0-5]|[1-9]\d|\d)$/', $dns_record[0]['ip']);
		if ($if_local_network192) {
			return false;
		}
	}
	return true;
}

function we7_need_extension() {
	return array('zip', 'pdo', 'pdo_mysql', 'openssl', 'gd', 'mbstring', 'mcrypt', 'curl');
}

function we7_get_accesstoken() {
	$accesstoken = we7_getcookie('accesstoken');
	if(!empty($accesstoken) && !empty($accesstoken['access_token']) && $accesstoken['expire_time'] > time()) {
		return $accesstoken['access_token'];
	}
	return '';
}

/**
 * @param $link PDO
 * @param $method
 * @param $sql
 * @return false|mixed
 */
function we7_pdo($link, $method, $sql) {
	if (empty($link) || empty($method) || empty($sql)) {
		return false;
	}
	if (!($link instanceof PDO)) {
		trigger_error('$link不是有效的数据库连接:' . (string)$link);
		return false;
	}
	$statement = $link->$method($sql);
	if ($link->errorCode() != '00000') {
		$errorInfo = $link->errorInfo();
		trigger_error($errorInfo[0] . ": " . $errorInfo[2], E_USER_WARNING);
		return false;
	}
	if ($statement instanceof PDOStatement) {
		$result = $statement->fetch();
		if ($statement->errorCode() != '00000') {
			$errorInfo = $statement->errorInfo();
			trigger_error($errorInfo[0] . ": " . $errorInfo[2], E_USER_WARNING);
			return false;
		}
	} else {
		$result = $statement;
	}
	return $result;
}
/**
 * 生成config.php文件
 * @param $server
 * @param $db_username
 * @param $db_password
 * @param $db_name
 * @param $db_prefix
 * @return bool|false|string
 */
function we7_build_config($server, $db_username, $db_password, $db_name, $db_prefix) {
	if (empty($server) || empty($db_username) || empty($db_password) || empty($db_name)) {
		return false;
	}
	$pieces = explode(':', $server);
	$db = array(
		'server' => $pieces[0] == '127.0.0.1' ? 'localhost' : $pieces[0],
		'port' => !empty($pieces[1]) ? $pieces[1] : '3306',
		'username' => $db_username,
		'password' => $db_password,
		'prefix' => $db_prefix,
		'name' => $db_name,
	);
	$error = '';
	try {
		$link = new PDO("mysql:host={$db['server']};port={$db['port']}", $db['username'], $db['password']); 	// dns可以没有dbname
		we7_pdo($link, 'exec', "SET character_set_connection=utf8, character_set_results=utf8, character_set_client=binary");
		we7_pdo($link, 'exec', "SET sql_mode=''");
		$databases = we7_pdo($link, 'query', "SHOW DATABASES LIKE '{$db['name']}';");
		if (empty($databases)) {
			if (substr($link->getAttribute(PDO::ATTR_SERVER_VERSION), 0, 3) > '4.1') {
				we7_pdo($link, 'query', "CREATE DATABASE IF NOT EXISTS `{$db['name']}` DEFAULT CHARACTER SET utf8;");
			} else {
				we7_pdo($link, 'query', "CREATE DATABASE IF NOT EXISTS `{$db['name']}`;");
			}
		}
		$databases_if_exists = we7_pdo($link, 'query', "SHOW DATABASES LIKE '{$db['name']}';");
		if (empty($databases_if_exists)) {
			$error = "数据库不存在且创建数据库失败.";
		}
		we7_pdo($link, 'exec', "USE `{$db_name}`;");
		$tables = we7_pdo($link, 'query', "SHOW TABLES LIKE '{$db_prefix}%';");
		if (!empty($tables)) {
			return '您的数据库不为空，请重新建立数据库或是清空该数据库或更改表前缀！';
		}
	} catch (PDOException $e) {
		trigger_error($e->getCode() . ':' . $e->getMessage());
		$error = $e->getMessage();
		if (strpos($error, 'Access denied for user') !== false) {
			$error = '您的数据库访问用户名或是密码错误.';
		} elseif (strpos($error, 'No such file or directory') !== false) {
			$error = '无法连接数据库,请检查数据库是否正常.详情:' . $error;
		} else {
			$error = iconv('gbk', 'utf8', $error);
		}
	}
	if (!empty($error)) {
		return $error;
	}
	
	$config = local_config();
	$cookiepre = local_salt(4) . '_';
	$authkey = local_salt(8);
	$config = str_replace(array(
		'{DB_HOST}', '{DB_USERNAME}', '{DB_PASSWORD}', '{DB_PORT}', '{DB_DATABASE}', '{DB_TABLEPRE}', '{COOKIEPRE}', '{AUTHKEY}'
	), array(
		$db['server'], $db['username'], $db['password'], $db['port'], $db['name'], $db['prefix'], $cookiepre, $authkey
	), $config);
	local_mkdirs(IA_INSTALL_ROOT . '/data');
	$result = file_put_contents(IA_INSTALL_ROOT . '/data/config.php', $config);
	return $result !== false ? true : false;
}

/**
 * 创建数据库
 * @return bool|string
 */
function we7_db() {
	global $is_https;
	define('IN_IA', true);
	require IA_INSTALL_ROOT . '/data/config.php';
	$db = array(
		'server' => $config['db']['master']['host'],
		'port' => $config['db']['master']['port'],
		'username' => $config['db']['master']['username'],
		'password' => $config['db']['master']['password'],
		'prefix' => $config['db']['master']['tablepre'],
		'name' => $config['db']['master']['database'],
	);
	$cookiepre = $config['cookie']['pre'];
	$authkey = $config['setting']['authkey'];
	
	$link = new PDO("mysql:dbname={$db['name']};host={$db['server']};port={$db['port']}", $db['username'], $db['password']);
	we7_pdo($link, 'exec', "SET character_set_connection=utf8, character_set_results=utf8, character_set_client=binary");
	we7_pdo($link, 'exec', "SET sql_mode=''");
	
	$dbfile = IA_INSTALL_ROOT . '/data/db-1.x.php';
	if(file_exists(IA_INSTALL_ROOT . '/index.php') &&
		is_dir(IA_INSTALL_ROOT . '/web') &&
		file_exists($dbfile)) {
		$dat = require $dbfile;
		if(empty($dat) || !is_array($dat)) {
			return '安装包不正确, 数据安装脚本缺失.';
		}
		
		foreach($dat['schemas'] as $schema) {
			$sql = local_create_sql($schema);
			local_run($sql, $link, $db);
		}
		foreach($dat['datas'] as $data) {
			local_run($data, $link, $db);
		}
	} else {
		return '安装包不正确.';
	}
	
	//默认用户名密码
	$user = array('username' => 'admin', 'password' => '123456');
	$salt = local_salt(8);
	$password = sha1("{$user['password']}-{$salt}-{$authkey}");
	$sql = "INSERT INTO `{$db['prefix']}users` (`username`, `password`, `salt`, `joindate`, `groupid`, `status`, `founder_groupid`, `is_bind`) VALUES('{$user['username']}', '{$password}', '{$salt}', '" . time() . "', 1, 2, 1, 0)";
	$result = we7_pdo($link, 'exec', $sql);
	if (!$result) {
		return '初始用户创建失败,请联系官方处理!';
	}
	$cookie = array('lastvisit' => '', 'lastip' => '');
	$cookie['uid'] = $link->lastInsertId();
	$cookie['hash'] = md5($password . $salt);
	
	$session = install_authcode(json_encode($cookie), 'encode', $authkey);
	$secure = $is_https ? 1 : 0;
	setcookie("{$cookiepre}__session", $session, 0, '/', '', $secure, true);
	
	return true;
}
function we7_register_site() {
	global $siteroot, $accesstoken;
	
	defined('IN_IA') or define('IN_IA', true);
	require IA_INSTALL_ROOT . '/framework/version.inc.php';
	$version = IMS_VERSION;
	$callback = urlencode($siteroot . '/install.php?step=register_callback');
	$post = array(
		'access_token' => $accesstoken,
		'name' => $siteroot . '的站点',
		'url' => $siteroot,
		'family' => 'c',
		'version' => $version,
		'release' => '',
		'callback' => $callback,
		'install_type' => 10,
	);
	$data = we7_request_api(API_OAUTH_REGISTER_SITE, $post);
	if (!is_dir('data/logs')) {
		local_mkdirs('data/logs');
	}
	file_put_contents('./data/logs/install-' . date('Ymd') . '.php', '<?php exit;?>');
	file_put_contents('./data/logs/install-' . date('Ymd') . '.php', var_export(array($data, $post), true), FILE_APPEND);
	if (is_array($data) && isset($data['error'])) {
		return $data['error'];
	} else {
		return true;
	}
}

function we7_update_user($username, $password) {
	global $_W, $is_https;
	load()->model('user');
	$userinfo = pdo_get('users', array('username' => 'admin'));
	$password = user_hash($password, $userinfo['salt']);
	$result = pdo_update('users', array('username' => $username, 'password' => $password), array('uid' => $userinfo['uid']));
	//重写session
	$cookie = array('lastvisit' => '', 'lastip' => '');
	$cookie['uid'] = $userinfo['uid'];
	$cookie['hash'] = md5($password . $userinfo['salt']);
	
	$session = install_authcode(json_encode($cookie), 'encode', $_W['config']['setting']['authkey']);
	$secure = $is_https ? 1 : 0;
	setcookie($_W['config']['cookie']['pre'] . "__session", $session, 0, '/', '', $secure, true);
	return $result ? true : false;
}

function we7_finish() {
	global $_W;
	@unlink(IA_INSTALL_ROOT . '/data/db.lock');
	@unlink(IA_INSTALL_ROOT . '/data/logs/error_log.php');
	@unlink(IA_INSTALL_ROOT . '/data/logs/install-' . date('Ymd') . '.php');
	define('IN_SYS', true);
	require IA_INSTALL_ROOT . '/framework/bootstrap.inc.php';
	require IA_INSTALL_ROOT . '/web/common/bootstrap.sys.inc.php';
	$_W['uid'] = $_W['isfounder'] = 1;
	load()->web('common');
	load()->web('template');
	load()->model('setting');
	load()->model('cache');
	load()->model('cloud');
	
	we7_setcookie('ims_family', '');
	cache_build_frame_menu();
	cache_build_setting();
	cache_build_users_struct();
	cache_build_module_subscribe_type();
	return true;
}

function we7_http_request($url, $post = array()) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	@curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_HEADER, 1);
	if ($post) {
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('X-User-Ip:' . get_client_ip()));
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('X-We7-Cache:' . time()));
	}
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
	curl_setopt($ch, CURLOPT_TIMEOUT, 60);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
	
	$data = curl_exec($ch);
	$errno = curl_errno($ch);
	$error = curl_error($ch);
	curl_close($ch);
	if ($errno || empty($data)) {
		return array('errno' => $errno, 'error' => $error);
	} else {
		return we7_http_response_parse($data);
	}
}

function we7_http_response_parse($data) {
	$rlt = array();
	
	$pos = strpos($data, "\r\n\r\n");
	$split1[0] = substr($data, 0, $pos);
	$split1[1] = substr($data, $pos + 4, strlen($data));
	
	$split2 = explode("\r\n", $split1[0], 2);
	preg_match('/^(\S+) (\S+) (.*)$/', $split2[0], $matches);
	$rlt['code'] = !empty($matches[2]) ? $matches[2] : 200;
	$rlt['status'] = !empty($matches[3]) ? $matches[3] : 'OK';
	$rlt['responseline'] = !empty($split2[0]) ? $split2[0] : '';
	$header = explode("\r\n", $split2[1]);
	$isgzip = false;
	foreach ($header as $v) {
		$pos = strpos($v, ':');
		$key = substr($v, 0, $pos);
		$value = trim(substr($v, $pos + 1));
		if (isset($rlt['headers'][$key]) && is_array($rlt['headers'][$key])) {
			$rlt['headers'][$key][] = $value;
		} elseif (!empty($rlt['headers'][$key])) {
			$temp = $rlt['headers'][$key];
			unset($rlt['headers'][$key]);
			$rlt['headers'][$key][] = $temp;
			$rlt['headers'][$key][] = $value;
		} else {
			$rlt['headers'][$key] = $value;
		}
		if(!$isgzip && strtolower($key) == 'content-encoding' && strtolower($value) == 'gzip') {
			$isgzip = true;
		}
	}
	$rlt['content'] = $split1[1];
	if($isgzip && function_exists('gzdecode')) {
		$rlt['content'] = gzdecode($rlt['content']);
	}
	
	$rlt['meta'] = $data;
	if($rlt['code'] == '100') {
		return we7_http_response_parse($rlt['content']);
	}
	return $rlt;
}

function we7_request_api($url, $post = array()) {
	$response = we7_http_request($url, $post);
	
	if ($response['code'] == 401) {
		return array('error' => 401);
	}
	
	if ($response['code'] != 200 || isset($response['errno'])) {
		return array('error' =>$response['content']);
	}
	$result = json_decode($response['content'], true);
	if (is_array($result)) {
		return $result;
	} else {
		return $response['content'];
	}
}

function we7_error($num, $message = 'success') {
	$num = intval($num);
	return json_encode(array('errno' => $num, 'data' => $message));
}

function we7_setcookie($key, $value) {
	if (!is_dir('data/logs')) {
		local_mkdirs('data/logs');
	}
	$data = null;
	if (file_exists('./data/logs/data.json')) {
		$data = file_get_contents('./data/logs/data.json');
	}
	if (empty($data)) {
		$data = array($key => $value);
	} else {
		$data = json_decode($data, true);
		$data[$key] = $value;
	}
	file_put_contents('./data/logs/data.json', json_encode($data));
	return true;
}

function we7_getcookie($key) {
	if (empty($key)) {
		return '';
	}
	if (file_exists('./data/logs/data.json')) {
		$data = file_get_contents('./data/logs/data.json');
	}
	if (empty($data)) {
		return '';
	}
	$result = json_decode($data, true);
	if (isset($result[$key])) {
		return $result[$key];
	}
	return '';
}

function get_client_ip() {
	static $ip = '';
	if (isset($_SERVER['REMOTE_ADDR'])) {
		$ip = $_SERVER['REMOTE_ADDR'];
	}
	if (isset($_SERVER['HTTP_CDN_SRC_IP'])) {
		$ip = $_SERVER['HTTP_CDN_SRC_IP'];
	} elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
		$ip = $_SERVER['HTTP_CLIENT_IP'];
	} elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && preg_match_all('#\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}#s', $_SERVER['HTTP_X_FORWARDED_FOR'], $matches)) {
		foreach ($matches[0] as $xip) {
			if (!preg_match('#^(10|172\.16|192\.168)\.#', $xip)) {
				$ip = $xip;
				break;
			}
		}
	}
	if (preg_match('/^([0-9]{1,3}\.){3}[0-9]{1,3}$/', $ip)) {
		return $ip;
	} else {
		return '127.0.0.1';
	}
}
function we7_error_page($message) {
	return '<!DOCTYPE html>
			<html lang="zh-cn">
				<head>
					<meta charset="utf-8">
					<meta http-equiv="X-UA-Compatible" content="IE=edge">
					<meta name="viewport" content="width=device-width, initial-scale=1.0">
					<title>微擎安装</title>
				</head>
				<style>
					html,body,.jump{height:100vh;width:100vw;overflow:hidden;background-color:#fff}.jump{position:relative;text-align:center}.center-box{margin:280px auto 0;height:230px;width:440px;display:inline-block;text-align:center}.jump-content{font-size:18px;line-height:30px;color:#666;font-weight:300}.jump-tips{font-size:14px;line-height:30px;color:#999}
				</style>
				<body>
					<div class="jump">
						<div class="center-box">
							<img src="https://cdn.w7.cc/ued/jump/image/jump-logo.png" alt="" style="margin-bottom:10px">
							<div class="jump-content">' . $message . '</div>
						</div>
					</div>
				</body>
			</html>';
}

header('content-type:text/html;charset=utf-8');
echo '<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>微擎安装</title>
  <base href="' . $sitepath . '/install.php">

  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="icon" type="image/x-icon" href="https://cdn.w7.cc/favicon.ico">
<link rel="stylesheet" href="//cdn.w7.cc/ued/we7-install/' . INSTALL_VERSION . '/styles.css?v=' . time() . '"></head>
<body>
  <app-root></app-root>
<script type="text/javascript" src="//cdn.w7.cc/ued/we7-install/' . INSTALL_VERSION . '/runtime.js?v=' . time() . '"></script><script type="text/javascript" src="//cdn.w7.cc/ued/we7-install/' . INSTALL_VERSION . '/polyfills.js?v=' . time() . '"></script><script type="text/javascript" src="//cdn.w7.cc/ued/we7-install/' . INSTALL_VERSION . '/main.js?v=' . time() . '"></script></body>
</html>';