<?php
/**
 * [WeEngine System] Copyright (c) 2014 W7.CC
 * $sn$
 */
defined('IN_IA') or exit('Access Denied');

function article_categorys($type = 'news') {
	$categorys = pdo_fetchall('SELECT * FROM ' . tablename('article_category') . ' WHERE type = :type ORDER BY displayorder DESC', array(':type' => $type), 'id');
	return $categorys;
}

function article_news_info($id) {
	$id = intval($id);
	$news = pdo_fetch('SELECT * FROM ' . tablename('article_news') . ' WHERE id = :id', array(':id' => $id));
	if (empty($news)) {
		return error(-1, '新闻不存在或已经删除');
	} else {
		$news['thumburl'] = tomedia($news['thumb']);
		pdo_update('article_news', array('click' => $news['click'] + 1), array('id' => $id));
	}
	return $news;
}

function article_notice_info($id) {
	$id = intval($id);
	$news = pdo_fetch('SELECT * FROM ' . tablename('article_notice') . ' WHERE id = :id', array(':id' => $id));
	if (empty($news)) {
		return error(-1, '公告不存在或已经删除');
	}
	return $news;
}

function article_news_home($limit = 5) {
	$news = pdo_fetchall('SELECT * FROM ' . tablename('article_news') . ' WHERE `is_display` = 1 AND `is_show_home` = 1 ORDER BY `displayorder` DESC,`id` DESC LIMIT ' . intval($limit), array(), 'id');
	return $news;
}

function article_notice_home($limit = 5) {
	$notice = pdo_fetchall('SELECT * FROM ' . tablename('article_notice') . ' WHERE `is_display` = 1 AND `is_show_home` = 1 ORDER BY `displayorder` DESC,`id` DESC LIMIT ' . intval($limit), array(), 'id');
	foreach ($notice as $key => $notice_val) {
		$notice[$key]['style'] = iunserializer($notice_val['style']);
	}
	return $notice;
}

function article_news_all($filter = array(), $pindex = 1, $psize = 10) {
	global $_W;
	$pindex = intval($pindex);
	$psize = intval($psize);
	$condition = ' WHERE is_display = 1';
	$params = array();
	if (!empty($filter['title'])) {
		$condition .= ' AND title LIKE :title';
		$params[':title'] = "%{$filter['title']}%";
	}
	if (!empty($filter['cateid'])) {
		$condition .= ' AND cateid = :cateid';
		$params[':cateid'] = $filter['cateid'];
	}
	$total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('article_news') . $condition, $params);
	$params[':order'] = !empty($_W['setting']['news_display']) ? $_W['setting']['news_display'] : 'displayorder';
	$news = pdo_fetchall('SELECT * FROM ' . tablename('article_news') . $condition . ' ORDER BY :order DESC LIMIT ' . ($pindex - 1) * $psize . ',' . $psize, $params, 'id');
	if (!empty($news)) {
		foreach ($news as $key => $new) {
			$news[$key]['createtime'] = date('Y-m-d H:i:s', $new['createtime']);
		}
	}
	return array('total' => $total, 'news' => $news);
}

function article_notice_all($filter = array(), $pindex = 1, $psize = 10) {
	global $_W;
	$condition = ' WHERE is_display = 1';
	$params = array();
	if (!empty($filter['title'])) {
		$condition .= ' AND title LIKE :title';
		$params[':title'] = "%{$filter['title']}%";
	}
	if (!empty($filter['cateid'])) {
		$condition .= ' AND cateid = :cateid';
		$params[':cateid'] = $filter['cateid'];
	}
	$limit = ' LIMIT ' . ($pindex - 1) * $psize . ',' . $psize;
	$order = !empty($_W['setting']['notice_display']) ? $_W['setting']['notice_display'] : 'displayorder';
	$total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('article_notice') . $condition, $params);
	$notice = pdo_fetchall("SELECT * FROM " . tablename('article_notice') . $condition . " ORDER BY " . $order . " DESC " . $limit, $params, 'id');
	foreach ($notice as $key => $notice_val) {
		$notice[$key]['createtime'] = date('Y-m-d H:i:s', $notice_val['createtime']);
		$notice[$key]['style'] = iunserializer($notice_val['style']);
		$notice[$key]['group'] = empty($notice_val['group']) ? array('vice_founder' => array(), 'normal' => array()) : iunserializer($notice_val['group']);
		if ($_W['isadmin']) {
			continue;
		}
		if (!empty($notice_val['group'])) {
			if (($_W['isfounder'] && !empty($notice[$key]['group']['vice_founder']) && !in_array($_W['user']['groupid'], $notice[$key]['group']['vice_founder'])) || (!$_W['isfounder'] && !empty($notice[$key]['group']['normal']) && !in_array($_W['user']['groupid'], $notice[$key]['group']['normal']))) {
				unset($notice[$key]);
			}
		}
	}
	return array('total' => $total, 'notice' => $notice);
}

/**
 * 删除文章分类
 * @param $id
 * @return bool
 */
function article_category_delete($id) {
	$id = intval($id);
	if (empty($id)) {
		return false;
	}
	load()->func('file');
	$category = pdo_fetch("SELECT id, parentid, nid FROM " . tablename('site_category') . " WHERE id = :id", array(':id' => $id));
	if (empty($category)) {
		return false;
	}
	if ($category['parentid'] == 0) {
		$children_cates = pdo_getall('site_category', array('parentid' => $id));
		pdo_update('site_article', array('pcate' => 0), array('pcate' => $id));
		if (!empty($children_cates)) {
			$children_cates_id = array_column($children_cates, 'id');
			pdo_update('site_article', array('ccate' => 0), array('ccate' => $children_cates_id), 'OR');
		}
	} else {
		pdo_update('site_article', array('ccate' => 0), array('ccate' => $id));
	}
	$navs = pdo_fetchall("SELECT icon, id FROM " . tablename('site_nav') . " WHERE id IN (SELECT nid FROM " . tablename('site_category') . " WHERE id = :id OR parentid = :id)", array(':id' => $id), 'id');
	if (!empty($navs)) {
		foreach ($navs as $row) {
			file_delete($row['icon']);
		}
		pdo_delete('site_nav', array('id' => array_keys($navs)));
	}
	pdo_delete('site_category', array('id' => $id, 'parentid' => $id), 'OR');
	return true;
}

/**
 * 评论回复
 * @param $data
 */
function article_comment_add($comment) {
	if (empty($comment['content'])) {
		return error(-1, '回复内容不能为空');
	}
	if (empty($comment['uid']) && empty($comment['openid'])) {
		return error(-1, '用户信息不能为空');
	}

	$article_comment_table = table('site_article_comment');
	$article_comment_table->addComment($comment);
	return true;
}

function article_comment_detail($article_lists) {
	global $_W;
	load()->model('mc');
	if (empty($article_lists)) {
		return array();
	}

	foreach ($article_lists as $list) {
		$parent_article_comment_ids[] = $list['id'];
	}
	
	table('site_article_comment')->fill('is_read', ARTICLE_COMMENT_READ)->whereId($parent_article_comment_ids)->save();
	$son_comment_lists = pdo_getall('site_article_comment', array('uniacid' => $_W['uniacid'], 'parentid in' => $parent_article_comment_ids));

	if (!empty($son_comment_lists)) {
		foreach ($son_comment_lists as $list) {
			$uids[$list['uid']] = $list['uid'];
		}
	}

	$user_table = table('users');
	$users = $user_table->searchWithUid($uids)->getUsersList();

	foreach ($article_lists as &$list) {
		$list['createtime'] = date('Y-m-d H:i:s', $list['createtime']);
		$fans_info = mc_fansinfo($list['openid']);
		$list['username'] = $fans_info['nickname'];
		$list['avatar'] = $fans_info['avatar'];
		if (empty($son_comment_lists)) {
			continue;
		}

		foreach ($son_comment_lists as $son_comment) {
			if ($son_comment['parentid'] == $list['id']) {
				$son_comment['username'] = $users[$son_comment['uid']]['username'];
				$list['son_comment'][] = $son_comment;
			}
		}
	}
	return $article_lists;
}
