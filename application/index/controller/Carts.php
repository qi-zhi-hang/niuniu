<?php 
namespace app\index\controller;
use think\Controller;
use think\Db;
use think\Session;
class Carts extends Common
{
	protected $total;//总价
	protected $count;//商品个数
	protected $member_id;//用户id
	private $db;//数据库对象
	public function __construct()
	{
		parent::__construct();
		$this ->db = model('carts');
        //从数据库查出$total $count $member_id
		$this->member_id = Session::get('member_id');
		$this->count = $this->db->gettoall($this->member_id); 
		$this->total = $this->db->getnum($this->member_id);     
	}
	//显示购物车所有商品
	public function index()
	{
		/*页面上要显示购物地址*/
		$this->getaddress(false);
		$array = $this->db->getList($this->member_id);
		$this->assign('list', $array);
		return $this->fetch();

	}
	//创建订单
	public function createorder()
	{
		$address_id = intval(input('address_id'));
		if($address_id == 0){
			$this -> error('请选择一个收货地址，如果您还没有收货地址请点击添加地址按扭新增。');
		}
		$address = Db::name('address') -> where(array('id' => $address_id, 'member_id' => $this->member_id)) -> find();
		if(!$address){
			$this->error('地址不存在，您选择的地址有误。');
		}
		$order['total'] = $this->db -> gettoall($this->member_id);
		$order['ordernumber'] = $this->build_order_no();
		$order['member_id'] = $this->member_id;
		$order['tel'] = $address['tel'];
		$order['member_id'] = $address['tel'];
		$order['address'] = getRegionById($address['region']).$address['area'];
		$order['receivename'] = $address['name'];
		$order['status'] = 1;
		$order['create_time'] = time();
		$orderdb = model('order');
		$ret = $orderdb -> save($order);
		if($ret){
			//这里还要写商品的销售记录
			
			//跳转支付
			$this->success('成功创建订单，正在准备支付……', url('Pay/index', array('id' => $orderdb -> id)));
		}else{
			$this->error($orderdb -> getError());
		}
	}
	private function build_order_no(){
	    return date('Ymd').substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
    }
	public function getaddress($return = true){
		$addresslist = Db::name('address') -> where(array('member_id' => $this->member_id)) -> select();
		$this->assign('addresslist', $addresslist);
		if($return){
			$r['code'] = 1;
			$r['data'] = $this->fetch();
			$r['info'] = '获取成功';
			echo json_encode($r);
		}
	}
	
	public function deleteaddress(){
		$map['id'] = intval(input('id'));
		$map['member_id'] = $this->member_id;
		if($map['id'] > 0){
			$db = model('address');
			$ret = $db -> where($map) -> delete();
			if($ret){
				$this->success('删除成功');
			}else{
				$this->error($db -> getError());
			}
		}else{
			$this->error('参数错误');
		}
	}
	
	
	public function saveaddress()
	{
	    $data['region'] = ','.implode(',',$_POST['region']).',';
		$data['member_id'] = $this->member_id;
		$data['name'] = input('name');
		$data['tel'] = input('tel');
		$data['code'] = input('code');
		$data['area'] = input('area');
		if(intval($data['member_id']) == 0){
			$this->error('未登录，请登录！');
		}
		if(isset($_POST['region'])){
			$db = model('address');
			$ret = $db -> save($data);
			if($ret){
				$this->success('成功添加地址！');
			}else{
				$this->error($db -> getError());
			}
		}else{
			$this->error('没有提交地址！');
		}
	}
	
	
	
	//商品加入购物车
	public function add()
	{
		if($this->member_id > 0){
			$product_id = input('get.product_id');
			if($product_id > 0){
				//商品已存在  更新
				$result = $this->db->product_exists($this->member_id , $product_id);
				if($result){
					$data['product_id'] = $product_id;
					$data['product_num'] = $result['product_num'] + abs(input('get.product_num'));
					
					$this->update($data);
				}else{
					//商品不存在  插入
					$product = Db::name('product') -> where(array('id'=> $product_id)) -> find();

					$insertdata['product_id'] = $product_id;
					$insertdata['member_id'] = $this->member_id;
					$insertdata['product_num'] = abs(input('get.product_num'));
					$insertdata['product_price'] = $product['price'];
					$insertdata['product_name'] = $product['title'];
					$insertdata['img_url'] = $product['img_url'];
					$insertdata['total_money'] = $product['price']*$insertdata['product_num'];
					
					$ret = $this->db->save($insertdata);
					if($ret){
						$this->success('添加购物车成功');
					}else{
						$this->error('添加购物车失败！！');
					}
				}
			}else{
				$this->error('商品不存在');
			}
		}else{
			$this->error('请去登录');
		}
		
		
	}
	//商品从购物车中删除
	public function delete()
	{
		$data['product_id'] = input('get.product_id');
		$data['member_id'] = $this->member_id;
		$result = $this->db->where($data)->delete();
		if($result){
			$this->success('删除商品成功');
		}else{
			$this->error('删除失败');
		}
	}
	// 商品数量更新
	public function update($data = null)
	{
		if(empty($data)){
			//1.使用助手函数来接收前台的数据
			$data['product_id'] = input('get.product_id');
			$data['product_num'] = input('get.product_num');
		}
		if($data['product_num'] < 1){
			$data['product_num'] = 1;
		}
		$result = $this->db->product_exists($this->member_id , $data['product_id']);
		$product = Db::name('product') -> where(array('id'=> $data['product_id'])) -> find();
		if($result){
					$data['total_money'] = 
					$product['price']*$data['product_num'];
				}
		//2.准备更新的数据
		$map['member_id'] = $this->member_id;
		$map['product_id'] = $data['product_id'];		
		//3.更新
		$ret = $this->db -> where($map) -> update($data);
		//4.判断数据成功,返回数据给前台
		if($ret){
			$return['num'] = $this -> db->getnum($this->member_id);
			$return['total'] =$this -> db->gettoall($this->member_id);
			$return['code'] = 1;
			$return['msg'] = '成功更新购物车';
			echo json_encode($return);
		}else{
			$return['num'] = $this -> db->getnum($this->member_id);
			$return['total'] =$this -> db->gettoall($this->member_id);
			$return['code'] = 0;
			$return['msg'] = '更新购物车失败';
			echo json_encode($return);
		}
	}
	
	public function getCart()
	{
		$return['num'] = (int)$this -> db->getnum($this->member_id);
		$return['total'] = (float)$this -> db->gettoall($this->member_id);
		$return['code'] = 1;
		echo json_encode($return);
	}
	//去付款跳到支付宝收银台
	public function pay()
	{
	    
	}

}
