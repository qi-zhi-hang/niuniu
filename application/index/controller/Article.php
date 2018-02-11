<?php 
namespace app\index\controller;
use think\Controller;
//用模型就不用引入
class Article extends Common
{
	public function index()
	{	//dump(intval(input('id')));
		if(intval(input('id')) > 0){
			$pd = model('article');
			$map['id'] = intval(input('id'));
			$array = $pd->where($map)->find()->toArray();
			$this->assign('article' , $array);
		}else{
			$this->error('文章不存在!');
		}
		
		
		
		return $this->fetch();
	}

}
