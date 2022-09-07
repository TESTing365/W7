<?php
/**
 *
 * [WeEngine System] Copyright (c) 2013 WE7.CC
 */

defined('IN_IA') or exit('Access Denied');

class ArticlenoticeTable extends We7Table {
	protected $tableName = 'article_notice';

	public function getArticleNoticeLists($order) {
		return $this->query->from($this->tableName)->orderby($order, 'DESC')->getall();
	}

	public function searchWithCreatetimeRange($time) {
		return $this->query->where('createtime >=', strtotime("-{$time} days"));
	}

	public function searchWithTitle($title) {
		return $this->query->where('title LIKE', "%{$title}%");
	}
}