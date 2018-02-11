<?php 
namespace app\index\controller;
use think\Controller;
//用模型就不用引入
class Product extends Common
{
	public function index()
	{	//dump(intval(input('id')));
		if(intval(input('id')) > 0){
			$pd = model('product');
			$map['id'] = intval(input('id'));
			$array = $pd->where($map)->find()->toArray();
			if(strpos($array['mimg_url'] , ',') > 0){
				$array['mimg_url'] = explode(',' , $array['mimg_url']);
			}else if(empty($array['mimg_url'])){
				$array['mimg_url'] = null;
			}else{
				$array['mimg_url'][0] = $array['img_url'];
				
			}
			//评论列表
		    $db = model('common');
            $db -> _list(array('product_id' => $map['id'], 'status' => 1), $this->view, 'comment_time desc', 5, 'comment');
			$this->assign('product' , $array);
		}else{
			$this->error('产品不存在!');
		}
		
		
		
		return $this->fetch();
	}
	
	public function commentinsert()
	{	
	    $islogin = $this->view -> __get('islogin');
		if($islogin){
			$member = $this->view -> __get('memberinfo');
			$data['member_id'] = $member['id'];
			$data['photo'] = $member['photo'];
			$data['nickname'] = $member['nickname'];
			$data['photo'] = $member['photo'];
			$data['status'] = 0;
			$data['pid'] = 0;
			$data['comment_time'] = time();
			$data['comment'] = input('comment');
			$data['product_img'] = input('product_img');
			$data['product_name'] = input('product_name');
			$data['product_id'] = intval(input('product_id'));
			if($data['product_id'] == 0){
				$this->error('商品不存在或者参数错误');
			}else{
				$db = model('comment');
				$result = $db -> save($data);
				if($result){
					$this->success('提交成功');
				}else{
					$this->error($db -> getError());
				}
			}
		}else{
			$this->error('请登录后再评论');
		}
	   
		
	}
}
