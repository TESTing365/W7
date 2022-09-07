<?php
/**
 * 视频回复处理类.
 *
 * [WeEngine System] Copyright (c) 2014 W7.CC
 */
defined('IN_IA') or exit('Access Denied');

class VideoModuleProcessor extends WeModuleProcessor {
	public function respond() {
		global $_W;
		$rid = $this->rule;
		$item = table('video_reply')->where(array('rid' => $rid))->get();
		if (empty($item)) {
			return false;
		}

		return $this->respVideo(array(
			'MediaId' => $item['mediaid'],
			'Title' => $item['title'],
			'Description' => $item['description'],
		));
	}
}
