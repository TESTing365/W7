<?php
/**
 *
 * [WeEngine System] Copyright (c) 2013 WE7.CC
 */

defined('IN_IA') or exit('Access Denied');

class ArticlenewsTable extends We7Table {
	protected $tableName = 'article_news';

	public function getArticleNewsLists($order) {
		return $this->query->from($this->tableName)->orderby($order, 'DESC')->getall();
	}

	public function searchWithCreatetimeRange($time) {
		return $this->query->where('createtime >=', strtotime("-{$time} days"));
	}

	public function searchWithTitle($title) {
		return $this->query->where('title LIKE', "%{$title}%");
	}
}