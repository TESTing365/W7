<?php
/**
 * 语音回复处理类.
 *
 * [WeEngine System] Copyright (c) 2014 W7.CC
 */
defined('IN_IA') or exit('Access Denied');

class VoiceModuleProcessor extends WeModuleProcessor {
	public function respond() {
		global $_W;
		$rid = $this->rule;
		$mediaid = table('voice_reply')->where(array('rid' => $rid))->getcolumn('mediaid');
		if (empty($mediaid)) {
			return false;
		}

		return $this->respVoice($mediaid);
	}
}
