<?php
/**
 * 图片回复处理类.
 *
 * [WeEngine System] Copyright (c) 2014 W7.CC
 */
defined('IN_IA') or exit('Access Denied');

class ImagesModuleProcessor extends WeModuleProcessor {
	public function respond() {
		global $_W;
		$rid = $this->rule;
		$mediaid = table('images_reply')->where(array('rid' => $rid))->getcolumn('mediaid');
		if (empty($mediaid)) {
			return false;
		}
		return $this->respImage($mediaid);
	}
}
