<?php
/**
 * 文章/公共---公告管理
 * [WeEngine System] Copyright (c) 2014 W7.CC.
 */
defined('IN_IA') or exit('Access Denied');

load()->model('article');

$dos = array('category_post', 'category', 'category_del', 'list', 'post', 'del', 'displaysetting', 'comment_status', 'comments', 'reply_comment');
$do = in_array($do, $dos) ? $do : 'list';
permission_check_account_user('system_article_notice');

//添加公告分类
if ('category_post' == $do) {
	if ($_W['ispost']) {
		$i = 0;
		if (!empty($_GPC['title'])) {
			foreach ($_GPC['title'] as $k => $v) {
				$title = safe_gpc_string($v);
				if (empty($title)) {
					continue;
				}
				$data = array(
					'title' => $title,
					'displayorder' => intval($_GPC['displayorder'][$k]),
					'type' => 'notice',
				);
				pdo_insert('article_category', $data);
				++$i;
			}
		}
		if ($_W['isw7_request']) {
			iajax(0, '添加公告分类成功');
		}
		itoast('添加公告分类成功', url('article/notice/category'), 'success');
	}
	template('article/notice-category-post');
}

//修改公告分类
if ('category' == $do) {
	$category_table = table('article_category');
	if ($_W['ispost']) {
		$id = intval($_GPC['id']);
		if (empty($id)) {
			iajax(1, '参数有误');
		}
		if (empty($_GPC['title'])) {
			iajax(1, '分类名称不能为空');
		}
		$update = array(
			'title' => safe_gpc_string($_GPC['title']),
			'displayorder' => max(0, intval($_GPC['displayorder'])),
		);
		$category_table->fill($update)->where('id', $id)->save();
		iajax(0, '修改分类成功');
	}
	$data = $category_table->getNoticeCategoryLists();
	if ($_W['isw7_request']) {
		iajax(0, $data);
	}
	template('article/notice-category');
}

//删除公告分类
if ('category_del' == $do) {
	$id = intval($_GPC['id']);
	pdo_delete('article_category', array('id' => $id, 'type' => 'notice'));
	pdo_delete('article_notice', array('cateid' => $id));
	if ($_W['isw7_request']) {
		iajax(0, '删除公告分类成功');
	}
	itoast('删除公告分类成功', referer(), 'success');
}

//编辑/添加公告
if ('post' == $do) {
	$id = empty($_GPC['id']) ? 0 : intval($_GPC['id']);
	$notice = table('article_notice')->searchWithId($id)->get();
	if (empty($notice)) {
		$notice = array(
			'is_display' => 1,
			'is_show_home' => 1,
			'group' => array('vice_founder' => array(), 'normal' => array()),
		);
	} else {
		$notice['style'] = iunserializer($notice['style']);
		$notice['group'] = empty($notice['group']) ? array('vice_founder' => array(), 'normal' => array()) : iunserializer($notice['group']);
	}
	$user_groups = table('users_group')->getall();
	$user_vice_founder_groups = table('users_founder_group')->getall();
	if ($_W['ispost']) {
		$title = safe_gpc_string($_GPC['title']) ? safe_gpc_string($_GPC['title']) : ($_W['isw7_request'] ? iajax(-1, '公告标题不能为空') : itoast('公告标题不能为空', '', 'error'));
		$cateid = intval($_GPC['cateid']) ? intval($_GPC['cateid']) : ($_W['isw7_request'] ? iajax(-1, '公告分类不能为空') : itoast('公告分类不能为空', '', 'error'));
		$content = !empty($_GPC['content']) ? safe_gpc_html(htmlspecialchars_decode($_GPC['content'], ENT_QUOTES)) : ($_W['isw7_request'] ? iajax(-1, '公告内容不能为空') : itoast('公告内容不能为空', '', 'error'));
		$style = array('color' => safe_gpc_string($_GPC['style']['color'], '', 'color'), 'bold' => intval($_GPC['style']['bold']));
		$group = $vice_group = array();
		if (!empty($_GPC['group']) && is_array($_GPC['group'])) {
			foreach ($_GPC['group'] as $value) {
				if (!is_numeric($value)) {
					if ($_W['isw7_request']) {
						iajax(-1, '参数错误！');
					}
					itoast('参数错误！');
				}
				$group[] = intval($value);
			}
		}
		if (!empty($_GPC['vice_founder_group']) && is_array($_GPC['vice_founder_group'])) {
			foreach ($_GPC['vice_founder_group'] as $vice_founder_value) {
				if (!is_numeric($vice_founder_value)) {
					if ($_W['isw7_request']) {
						iajax(-1, '参数错误！');
					}
					itoast('参数错误！');
				}
				$vice_group[] = intval($vice_founder_value);
			}
		}
		if (empty($group) && empty($vice_group)) {
			$group = '';
		} else {
			$group = iserializer(array('normal' => $group, 'vice_founder' => $vice_group));
		}
		$data = array(
			'title' => $title,
			'cateid' => $cateid,
			'content' => $content,
			'displayorder' => intval($_GPC['displayorder']),
			'click' => intval($_GPC['click']),
			'is_display' => intval($_GPC['is_display']),
			'is_show_home' => intval($_GPC['is_show_home']),
			'style' => iserializer($style),
			'group' => $group,
		);

		if (!empty($notice['id'])) {
			pdo_update('article_notice', $data, array('id' => $id));
		} else {
			$data['createtime'] = TIMESTAMP;
			pdo_insert('article_notice', $data);
		}
		if ($_W['isw7_request']) {
			iajax(0, '操作公告成功');
		}
		itoast('编辑公告成功', url('article/notice/list'), 'success');
	}

	$categorys = table('article_category')->getNoticeCategoryLists();
	if ($_W['isw7_request']) {
		$message = array(
			'user_groups' => $user_groups,
			'user_vice_founder_groups' => $user_vice_founder_groups,
			'categorys' => $categorys,
			'notice' => $notice,
			'message' => '获取信息成功'
		);
		iajax(0, $message);
	}
	template('article/notice-post');
}

//公告列表
if ('list' == $do) {
	$pindex = empty($_GPC['page']) ? 1 : intval($_GPC['page']);
	$psize = 20;

	$article_table = table('article_notice');
	$cateid = empty($_GPC['cateid']) ? 0 : intval($_GPC['cateid']);
	$createtime = empty($_GPC['createtime']) ? 0 : intval($_GPC['createtime']);
	$title = empty($_GPC['title']) ? '' : safe_gpc_string($_GPC['title']);

	if (!empty($cateid)) {
		$article_table->searchWithCateid($cateid);
	}

	if (!empty($createtime)) {
		$article_table->searchWithCreatetimeRange($createtime);
	}

	if (!empty($title)) {
		$article_table->searchWithTitle($title);
	}

	$order = !empty($_W['setting']['notice_display']) ? $_W['setting']['notice_display'] : 'displayorder';

	$article_table->searchWithPage($pindex, $psize);
	$article_table->orderby($order, 'DESC');
	$notices = $article_table->getList();
	$total = $article_table->getLastQueryTotal();
	$pager = pagination($total, $pindex, $psize);

	$categorys = table('article_category')->getNoticeCategoryLists();
	foreach ($notices as $n=>$notice) {
		if (!empty($categorys[$notice['cateid']])) {
			$notices[$n]['catename'] = $categorys[$notice['cateid']]['title'];
		} else {
			$notices[$n]['catename'] = '';
		}

		$notices[$n]['createtime'] = date('Y-m-d H:i', $notice['createtime']);
	}
	$comment_status = setting_load('notice_comment_status');
	$comment_status = empty($comment_status['notice_comment_status']) ? 0 : 1;
	$notice_display = setting_load('notice_display');
	$notice_display = !empty($notice_display) ? $notice_display['notice_display'] : 'displayorder';
	if ($_W['isw7_request']) {
		$message = array(
			'total' => $total,
			'page' => $pindex,
			'page_size' => $psize,
			'list' => $notices,
			'comment_status' => $comment_status,
			'notice_display' => $notice_display
		);
		iajax(0, $message);
	}
	template('article/notice');
}

//删除公告
if ('del' == $do) {
	$id = intval($_GPC['id']);
	pdo_delete('article_notice', array('id' => $id));
	if ($_W['isw7_request']) {
		iajax(0, '删除公告成功');
	}
	itoast('删除公告成功', referer(), 'success');
}

//显示排序设置
if ('displaysetting' == $do) {
	$setting = safe_gpc_string($_GPC['setting']);
	$data = 'createtime' == $setting ? 'createtime' : 'displayorder';
	setting_save($data, 'notice_display');
	if ($_W['isw7_request']) {
		iajax(0, '更改成功');
	}
	itoast('更改成功！', referer(), 'success');
}

//开关公告留言功能
if ('comment_status' == $do) {
	$status = setting_load('notice_comment_status');
	setting_save(empty($status['notice_comment_status']) ? 1 : 0, 'notice_comment_status');
	if ($_W['isw7_request']) {
		iajax(0, '更改成功');
	}
	itoast('更改成功！', referer(), 'success');
}

//留言列表
if ('comments' == $do) {
	$id = intval($_GPC['id']);
	$order = empty($_GPC['order']) || 'id' == safe_gpc_string($_GPC['order']) ? 'id' : 'like_num';
	template('article/comment-list');
}

//回复留言
if ('reply_comment' == $do) {
	$id = intval($_GPC['id']);
	$comment_table = table('article_comment');
	$comment = $comment_table->where('id', $id)->get();
	if (empty($comment)) {
		iajax(1, '评论不存在');
	}
	$data = array(
		'parentid' => $comment['id'],
		'articleid' => $comment['articleid'],
		'uid' => $_W['uid'],
		'content' => safe_gpc_string($_GPC['replycontent']),
	);
	$comment_table->addComment($data);
	$data = array_merge($data, $_W['user']);
	iajax(0, $data);
}
