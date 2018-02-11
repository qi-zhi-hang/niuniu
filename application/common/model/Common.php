<?php
namespace app\common\model;
use think\Db;
class Common{
	/*
	数据列表
	默认每页10条记录
	默认没有排序字段
	$view是从控制器传进来的视图对象，用它来渲染列表数据（也可以说是把数据填充到模板中）
	$map是查询条件，默认为空
	*/
	public function _list($map = array(), $view = NULL, $order = '', $pagesize = 7, $table = '', $items = 'list', $pages = 'pages'){
		//使用当前模型进行查询
		$data = Db::name($table) -> where($map) -> order($order) -> paginate($pagesize);
		//生成分页字符
		$p = $data -> render();
		//提取列表数据，是一个多维数组
		$list = $data -> items();
		//把得到的数据填充到模板中，以便显示在页面上
		$view -> assign($items, $list);
		$view -> assign($pages, $p);
	}
}