<?php
/**
 * 草稿箱管理列表页
 * [WeEngine System] Copyright (c) 2014 W7.CC.
 */
defined('IN_IA') or exit('Access Denied');
load()->model('material');
load()->model('account');
load()->model('mc');

$dos = array('display', 'post', 'sync', 'delete', 'upload_news', 'archived_draft', 'publish_list', 'publish', 'publish_delete');
$do = in_array($do, $dos) ? $do : 'display';

if ('display' == $do) {
	$page_size = 24;
	$search = empty($_GPC['title']) ? '' : addslashes($_GPC['title']);
	$type = empty($_GPC['type']) ? '' : safe_gpc_string($_GPC['type']);
	$type = in_array($type, array('draft', 'news')) ? $type : 'draft';
	$page_index = empty($_GPC['page']) ? 1 : intval($_GPC['page']);
	$server = !empty($_GPC['server']) && in_array($_GPC['server'], array(MATERIAL_LOCAL, MATERIAL_WEXIN)) ? safe_gpc_string($_GPC['server']) : '';
	$material_draft_list = material_news_list($server, $search, array('page_index' => $page_index, 'page_size' => $page_size), $type);
	$material_list = $material_draft_list['material_list'];
	$pager = $material_draft_list['page'];
	$group = mc_fans_groups(true);
	template('platform/draft');
}

if ('post' == $do) {
	$id = empty($_GPC['id']) ? 0 : intval($_GPC['id']);
	$draft_list = array();
	if (!empty($id)) {
		$attachment = material_get($id);
		if (is_error($attachment)) {
			itoast('图文素材不存在，或已删除', url('platform/draft/display'), 'warning');
		}
		$draft_list = $attachment['news'];
		$material_type = $attachment['type'];
	}
	if ($_W['ispost']) {
		$is_sendto_wechat = 'wechat' == safe_gpc_string($_GPC['target']) ? true : false;
		$attach_id = $id;
		if (!empty($id) && (!empty($attachment['article_id']) || 'news' == $material_type)) {
			$attach_id = 0;
		}
		if (empty($_GPC['news'])) {
			iajax(-1, '提交内容参数有误');
		}
		$attach_id = material_news_set($_GPC['news'], $attach_id, 'draft');
		if (is_error($attach_id)) {
			iajax(-1, $attach_id['message']);
		}
		if ($is_sendto_wechat) {
			$result = material_local_news_upload($attach_id, 'draft');
			if (is_error($result)) {
				iajax(-1, $result['message']);
			}
		}
		iajax(0, array('id' => $attach_id));
	}
	$group = mc_fans_groups(true);

	template('platform/draft-post');
}

if ('sync' == $do) {
	$type = safe_gpc_string($_GPC['type']);
	$pageindex = empty($_GPC['pageindex']) ? 1 : intval($_GPC['pageindex']);
	if (!in_array($type, array('draft', 'publish'))) {
		iajax(-1, '同步类型不存在！');
	}
	$account_api = WeAccount::createByUniacid();
	$params = array('uniacid' => $_W['uniacid'], 'type' => 'draft', 'model' => 'perm');
	if ('draft' == $type) {
		$draft_list = $account_api->batchGetDraft(($pageindex - 1) * 20);
		$params['media_id !='] = '';
	} else {
		$draft_list = $account_api->batchGetPublishDraft(($pageindex - 1) * 20);
		$params['article_id !='] = '';
	}
	$material_type = 'draft';
	$draft_list['total_count'] = empty($draft_list['total_count']) ? 0 : $draft_list['total_count'];
	$draft_list['item'] = empty($draft_list['item']) ? array() : $draft_list['item'];
	$wechat_existid = empty($_GPC['wechat_existid']) ? array() : safe_gpc_array($_GPC['wechat_existid']);
	if (1 == $pageindex) {
		$original_draftid = pdo_getall('wechat_attachment', $params, array('id'), 'id');
		$original_draftid = array_keys($original_draftid);
		$wechat_existid = material_sync($draft_list['item'], array(), $material_type);
		if ($draft_list['total_count'] > 20) {
			$total = ceil($draft_list['total_count'] / 20);
			iajax('1', array('total' => $total, 'pageindex' => $pageindex + 1, 'wechat_existid' => $wechat_existid, 'original_draftid' => $original_draftid, 'type' => $type), '');
		}
	} else {
		$wechat_existid = material_sync($draft_list['item'], $wechat_existid, $material_type);
		$total = intval($_GPC['total']);
		$original_draftid = empty($_GPC['original_draftid']) ? array() : safe_gpc_array($_GPC['original_draftid']);
		if ($total != $pageindex) {
			iajax('1', array('total' => $total, 'pageindex' => $pageindex + 1, 'wechat_existid' => $wechat_existid, 'original_draftid' => $original_draftid, 'type' => $type), '');
		}
		if (empty($original_draftid)) {
			$original_draftid = array();
		}
		$original_draftid = array_filter($original_draftid, function ($item) {
			return is_numeric($item);
		});
	}
	$delete_id = array_diff($original_draftid, $wechat_existid);
	if (!empty($delete_id) && is_array($delete_id)) {
		foreach ($delete_id as $id) {
			pdo_delete('wechat_attachment', array('uniacid' => $_W['uniacid'], 'id' => $id));
			pdo_delete('wechat_news', array('uniacid' => $_W['uniacid'], 'attach_id' => $id));
		}
	}
	iajax(0, '更新成功！');
}

if ('delete' == $do) {
	if (empty($_W['isfounder']) && ACCOUNT_MANAGE_NAME_MANAGER != $_W['role'] && ACCOUNT_MANAGE_NAME_OWNER != $_W['role'] && ACCOUNT_MANAGE_NAME_OPERATOR != $_W['role']) {
		iajax(1, '您没有权限删除文件');
	}
	$material_id = is_array($_GPC['material_id']) ? safe_gpc_array($_GPC['material_id']) : array(safe_gpc_int($_GPC['material_id']));
	$server = 'local' == safe_gpc_string($_GPC['server']) ? 'local' : 'wechat';
	if (empty($material_id)) {
		iajax(1, '提交内容参数有误');
	}
	foreach ($material_id as $id) {
		$result = material_news_delete($id, 'draft');
	}
	if (is_error($result)) {
		iajax('-1', $result['message']);
	}

	iajax('0', '删除草稿成功');
}

if ('upload_news' == $do) {
	$material_id = intval($_GPC['material_id']);
	$result = material_local_news_upload($material_id, 'draft');
	if (is_error($result)) {
		iajax(-1, $result['message']);
	} else {
		iajax(0, '转换成功');
	}
}

if ('archived_draft' == $do) {
	$id = intval($_GPC['id']);
	if (empty($id)) {
		iajax(-1, '素材id参数不能为空');
	}
	$material = table('wechat_attachment')->getById($id);
	$post_news = table('wechat_news')->getAllByAttachId($material['id']);
	if (empty($material) || empty($post_news)) {
		iajax(-1, '素材不存在');
	}
	$wechat_attachment = array(
		'uniacid' => $_W['uniacid'],
		'acid' => $_W['acid'],
		'media_id' => '',
		'type' => 'draft',
		'model' => 'local',
		'createtime' => TIMESTAMP,
		'article_id' => '',
		'publish_status' => -1
	);
	pdo_insert('wechat_attachment', $wechat_attachment);
	$attach_id = pdo_insertid();
	foreach ($post_news as $news) {
		unset($news['id']);
		$news['url'] = '';
		$news['thumb_media_id'] = '';
		$news['attach_id'] = $attach_id;
		$local_attachment = material_network_image_to_local($news['thumb_url'], $_W['uniacid'], $_W['uid']);
		$news['thumb_url'] = $local_attachment['url'];
		pdo_insert('wechat_news', $news);
	}
	$result = material_local_news_upload($attach_id, 'draft');
	if (is_error($result)) {
		iajax(-1, $result['message']);
	}
	iajax(0, array('id' => $attach_id));
}

if ('publish_list' == $do) {
	$page_size = 24;
	$search = empty($_GPC['title']) ? '' : addslashes($_GPC['title']);
	$page_index = empty($_GPC['page']) ? 1 : intval($_GPC['page']);
	$server = !empty($_GPC['server']) && in_array($_GPC['server'], array(MATERIAL_LOCAL, MATERIAL_WEXIN)) ? safe_gpc_string($_GPC['server']) : '';
	$material_draft_list = material_news_list($server, $search, array('page_index' => $page_index, 'page_size' => $page_size), 'publish');
	$material_list = $material_draft_list['material_list'];
	$pager = $material_draft_list['page'];
	template('platform/draft-publish');
}

if ('publish' == $do) {
	$media_id = safe_gpc_string($_GPC['media_id']);
	$attachment = material_get($media_id);
	if (is_error($attachment) || empty($attachment['media_id'])) {
		iajax(-1, '草稿不存在！');
	}
	$account_api = WeAccount::createByUniacid();
	$result = $account_api->publishDraft($attachment['media_id']);
	if (is_error($result)) {
		iajax(-1, $result['message']);
	}
	pdo_update('wechat_attachment', array('publish_id' => $result['publish_id'], 'publish_status' => 1), array('id' => $media_id));

	iajax(0, '草稿发布任务提交成功！');
}

if ('publish_delete' == $do) {
	$media_id = safe_gpc_string($_GPC['media_id']);
	$index = empty($_GPC['index']) ? 1 : safe_gpc_int($_GPC['index']) + 1;
	$attachment = material_get($media_id);
	if (is_error($attachment) || empty($attachment['article_id'])) {
		iajax(-1, '草稿不存在或未发布！');
	}
	$account_api = WeAccount::createByUniacid();
	$result = $account_api->deletePublishDraft($attachment['article_id'], $index);
	if (is_error($result)) {
		iajax(-1, $result['message']);
	}
	$params = array(
		'uniacid' => $_W['uniacid'],
		'attach_id' => $attachment['id'],
		'displayorder' => $index - 1
	);
	pdo_update('wechat_news', array('is_deleted' => 1), $params);

	iajax(0, '删除发布成功！');
}