<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 * $sn: pro/app/source/mc/uc.ctrl.php : v 148a6d07bc2b : 2014/07/24 02:02:34 : yanghf $
 */

defined('IN_IA') or exit('Access Denied');

$dos = array('home', 'profile', 'uc');
$do = in_array($do, $dos) ? $do : 'home';

$foo = ($_GPC['foo'] == 'bind' || $_GPC['foo'] == '') ? 'bind' : 'unbind';
$setting = uni_setting($_W['uniacid'], array('uc'));
if($foo == 'bind') {
	if($setting['uc']['status'] == '1') {
		$uc = $setting['uc'];
		$mapping = table('mc_mapping_ucenter')
			->where(array(
				'uniacid' => $_W['uniacid'],
				'uid' => $_W['member']['uid']
			))
			->get();
		//如果没有映射关系
		if(empty($mapping)) {
			$op = trim($_GPC['op']);
			//如果有UC账号
			if($op == 'yes') {
				if(checksubmit('submit')) {
					$username = trim($_GPC['username']) ? trim($_GPC['username']) : message('请填写用户名！', '', 'error');
					$password = trim($_GPC['password']) ? trim($_GPC['password']) : message('请填写密码！', '', 'error');
					mc_init_uc();
					$data = uc_user_login($username, $password);
					//如果有错误,提示用户错误信息
					if($data[0] < 0) {
						if($data[0] == -1) message('用户不存在，或者被删除！', '', 'error');
						elseif ($data[0] == -2) message('密码错误！', '', 'error');
						elseif ($data[0] == -3) message('安全提问错误！', '', 'error');
					}
					$exist = table('mc_mapping_ucenter')
						->where(array(
							'uniacid' => $_W['uniacid'],
							'centeruid' => $data[0]
						))
						->get();
					if(empty($exist)) {
						//数据库建立映射关系
						table('mc_mapping_ucenter')
							->fill(array(
								'uniacid' => $_W['uniacid'],
								'uid' => $_W['member']['uid'],
								'centeruid' => $data[0]
							))
							->save();
						message('绑定UC账号成功！', url('mc/mc/home'), 'success');
					} else {
						message('该UC账号已绑定过,请使用其他账号绑定！', '', 'error');
					}
				}
			} elseif($op == 'no') {
				if(checksubmit('submit')) {
					$username = trim($_GPC['username']) ? trim($_GPC['username']) : message('请填写用户名！', '', 'error');
					$password = trim($_GPC['password']) ? trim($_GPC['password']) : message('请填写密码！', '', 'error');
					$repassword = trim($_GPC['repassword']) ? trim($_GPC['repassword']) : message('请填写确认密码！', '', 'error');
					if($password != $repassword) {message('两次密码输入不一致！', '', 'error');}
					$email = trim($_GPC['email']) ? trim($_GPC['email']) : message('请填写邮箱！', '', 'error');
					mc_init_uc();
					$uid = uc_user_register($username, $password, $email);
					if($uid < 0) {
						if($uid == -1) message('用户名不合法！', '', 'error');
						elseif ($uid == -2) message('包含不允许注册的词语！', '', 'error');
						elseif ($uid == -3) message('用户名已经存在！', '', 'error');
						elseif ($uid == -4) message('邮箱格式错误！', '', 'error');
						elseif ($uid == -5) message('邮箱不允许注册！', '', 'error');
						elseif ($uid == -6) message('邮箱已经被注册！', '', 'error');
					} else {
						//注册成功后操作
						if($_W['member']['email'] == '') {
							mc_update($_W['member']['uid'],array('email' => $email));
						}
						table('mc_mapping_ucenter')
							->fill(array(
								'uniacid' => $_W['uniacid'],
								'uid' => $_W['member']['uid'],
								'centeruid' => $uid
							))
							->save();
						message('绑定UC账号成功！', url('mc/mc/home'), 'success');
					}
				}	
			}
			template('mc/bind');
			exit;
		} else {
			message('已绑定UC账号,您可以尝试解绑定后重新绑定UC账号！', '', 'error');
		}
	} else {
		message('系统尚未开启UC！', '', 'success');
	}
} else {
	$result = table('mc_mapping_ucenter')
		->where(array(
			'uid' => $_W['member']['uid'],
			'uniacid' => $_W['uniacid']
		))
		->delete();
	if($result === false) {
		message('解绑定UC账号失败！', referer(), 'error');
	} else {
		message('解绑定UC账号成功！', referer(), 'success');
	}
}
exit('Error: -1');

