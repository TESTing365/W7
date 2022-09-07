<?php
/**
 *
 * [WeEngine System] Copyright (c) 2013 WE7.CC
 */

defined('IN_IA') or exit('Access Denied');

class ArticlecategoryTable extends We7Table {
	protected $tableName = 'article_category';

	public function getNewsCategoryLists() {
		return $this->query->from($this->tableName)->where('type', 'news')->orderby('displayorder', 'DESC')->getall('id');
	}

	public function getNoticeCategoryLists() {
		return $this->query->from($this->tableName)->where('type', 'notice')->orderby('displayorder', 'DESC')->getall('id');
	}
}