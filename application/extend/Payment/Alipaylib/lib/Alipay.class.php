<?php

require_once("Alipaylib/lib/alipay_submit.class.php");
class Alipay{
	public $site_id = 0;
	//构造函数
	public function Alipay($site_id = 0, $callback = ''){
		if($site_id == 0){
			$site_id = defined('__ADMINSITEID__')?__ADMINSITEID__:__SITEID__;
		}
		
		$this->site_id = $site_id;
		
		$db = M('Payment');
		
		$ret = $db -> where(array('site_id' => $site_id,'classname' => 'Alipay')) -> find();
		$ret = unserialize($ret['value']);
		
		
		
		
				//↓↓↓↓↓↓↓↓↓↓请在这里配置您的基本信息↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓
		//合作身份者ID，签约账号，以2088开头由16位纯数字组成的字符串，查看地址：https://b.alipay.com/order/pidAndKey.htm
		$this->alipay_config['partner']		= $ret[0];
		
		//收款支付宝账号，以2088开头由16位纯数字组成的字符串，一般情况下收款账号就是签约账号
		$this->alipay_config['seller_id']	= $this->alipay_config['partner'];
		
		// MD5密钥，安全检验码，由数字和字母组成的32位字符串，查看地址：https://b.alipay.com/order/pidAndKey.htm
		$this->alipay_config['key']			= $ret[1];
		//dump($this->alipay_config);exit;
		// 服务器异步通知页面路径  需http://格式的完整路径，不能加?id=123这类自定义参数，必须外网可以正常访问
		$this->alipay_config['notify_url'] = $callback == ''?"http://".$_SERVER['HTTP_HOST']. __APP__ ."/Payment/notify/code/Alipay.html":$callback;
		
		// 页面跳转同步通知页面路径 需http://格式的完整路径，不能加?id=123这类自定义参数，必须外网可以正常访问
		$this->alipay_config['return_url'] = "http://".$_SERVER['HTTP_HOST']. __APP__ ."/";
		
		//签名方式
		$this->alipay_config['sign_type']    = strtoupper('MD5'); 
		
		//字符编码格式 目前支持 gbk 或 utf-8
		$this->alipay_config['input_charset']= strtolower('utf-8');
		
		//ca证书路径地址，用于curl中ssl校验
		//请保证cacert.pem文件在当前文件夹目录中
		$this->alipay_config['cacert']    = getcwd().'\\cacert.pem';
		
		//访问模式,根据自己的服务器是否支持ssl访问，若支持请选择https；若不支持请选择http
		$this->alipay_config['transport']    = 'http';
		
		// 支付类型 ，无需修改
		$this->alipay_config['payment_type'] = "1";
				
		// 产品类型，无需修改
		
		
		
		if(!isMobile()){//PC接口
		    $this->alipay_config['service'] = "create_direct_pay_by_user";
		}else{
			//手机接口
			$this->alipay_config['service'] = "alipay.wap.create.direct.pay.by.user";
		}
		
		
		
		 
		//↑↑↑↑↑↑↑↑↑↑请在这里配置您的基本信息↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑
		
		
		//↓↓↓↓↓↓↓↓↓↓ 请在这里配置防钓鱼信息，如果没开通防钓鱼功能，为空即可 ↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓
			
		// 防钓鱼时间戳  若要使用请调用类文件submit中的query_timestamp函数
		$this->alipay_config['anti_phishing_key'] = ""; 
			
		// 客户端的IP地址 非局域网的外网IP地址，如：221.0.0.1
		$this->alipay_config['exter_invoke_ip'] = ""; 
	
	}
	//创建设置项表单
	public function createform(){
		
		$db = M('Payment');
		$ret = array('','','','');
		$ret = $db -> where(array('site_id' => $this->site_id,'classname' => 'Alipay')) -> find();
		$ret = unserialize($ret['value']);
		$html = '<style>#Alipay td{padding:5px;}</style><table id="Alipay" border="1" cellspacing="0" cellpadding="5" width="100%">
 
  <tr>
    <td align="center" valign="middle"><input type="hidden" name="classname" value="Alipay">合作ID</td>
    <td align="center"><input type="text" name="level[]" value="'.$ret[0].'" /></td>
  </tr>
  <tr>
    <td align="center" valign="middle">支付密钥</td>
    <td align="center"><input type="text" name="level[]" value="'.$ret[1].'" /></td>
  </tr>
  <tr>
    <td align="center" valign="middle">收款账号</td>
    <td align="center"><input type="text" name="level[]" value="'.$ret[2].'" /></td>
  </tr>
 
</table>';

        return $html;
	}
	
	//保存设置项
	public function saveform(){
		if(isset($_POST['level'])){
			$data['classname'] = 'Alipay';
			$data['value'] = serialize($_POST['level']);
			$data['site_id'] = $this->site_id;
			$db = M('Payment');
			//dump($data);
			$ret = $db -> where(array('site_id' => $data['site_id'],'classname' => $data['classname'])) -> find();
			if($ret){
				$saveret = $db -> where(array('site_id' => $data['site_id'],'classname' => $data['classname'])) -> data(array('value' => $data['value'])) -> save();
			}else{
				$saveret = $db -> data($data) -> add();
			}
			if($saveret){
				return true; 
			}else{
				return false;
			}
			
		}
	}
	
	//发起支付
	public function pay($orderid){
		
		
		
		$ordermes = M('order')->where(array('id'=>$orderid))->find();
		$Setbody = $ordermes['ordernumber'].$ordermes['id'];
		$SetTotal_fee = $ordermes['total'];
		

/**************************请求参数**************************/
        //商户订单号，商户网站订单系统中唯一订单号，必填
        $out_trade_no = $ordermes['ordernumber'];

        //订单名称，必填
        $subject = $ordermes['ordernumber'];

        //付款金额，必填
        $total_fee = $SetTotal_fee;

        //商品描述，可空
        $body = $Setbody;





/************************************************************/

//构造要请求的参数数组，无需改动
		  $parameter = array(
				  "service"       => $this->alipay_config['service'],
				  "partner"       => $this->alipay_config['partner'],
				  "seller_id"  => $this->alipay_config['seller_id'],
				  "payment_type"	=> $this->alipay_config['payment_type'],
				  "notify_url"	=> $this->alipay_config['notify_url'],
				  "return_url"	=> $this->alipay_config['return_url'],
				  
				  "anti_phishing_key"=>$this->alipay_config['anti_phishing_key'],
				  "exter_invoke_ip"=>$this->alipay_config['exter_invoke_ip'],
				  "out_trade_no"	=> $out_trade_no,
				  "subject"	=> $subject,
				  "total_fee"	=> $total_fee,
				  "body"	=> $body,
				  "_input_charset"	=> trim(strtolower($this->alipay_config['input_charset']))
				  //其他业务参数根据在线开发文档，添加参数.文档地址:https://doc.open.alipay.com/doc2/detail.htm?spm=a219a.7629140.0.0.kiX33I&treeId=62&articleId=103740&docType=1
				  //如"参数名"=>"参数值"
				  
		  );
		  //getHtml($parameter)
		  //建立请求
		  $alipaySubmit = new AlipaySubmit($this->alipay_config);
		  $html_text = $alipaySubmit->buildRequestForm($parameter,"get", "确认");
        /*
        echo '<iframe src="'.$html_text.'" frameborder="0" scrolling="no" style="border:1px solid red;padding-top:0px;margin-top:0px;margin:0;padding:0;top:0;">
</iframe>';
        */
		  return $html_text;
				  
		
		
		
		
	}
	
	
	
	public function callback(){
	    require_once("Alipaylib/lib/alipay_notify.class.php");

//计算得出通知验证结果
$alipayNotify = new AlipayNotify($this->alipay_config);
$verify_result = $alipayNotify->verifyNotify();

if($verify_result) {//验证成功
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	//请在这里加上商户的业务逻辑程序代

	
	//——请根据您的业务逻辑来编写程序（以下代码仅作参考）——
	
    //获取支付宝的通知返回参数，可参考技术文档中服务器异步通知参数列表
	
	//商户订单号

	$out_trade_no = $_POST['out_trade_no'];

	//支付宝交易号

	$trade_no = $_POST['trade_no'];

	//交易状态
	$trade_status = $_POST['trade_status'];


    if($_POST['trade_status'] == 'TRADE_FINISHED') {
		//判断该笔订单是否在商户网站中已经做过处理
			//如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
			//请务必判断请求时的total_fee、seller_id与通知时获取的total_fee、seller_id为一致的
			//如果有做过处理，不执行商户的业务程序
				
		//注意：
		//退款日期超过可退款期限后（如三个月可退款），支付宝系统发送该交易状态通知

        //调试用，写文本函数记录程序运行情况是否正常
        //logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
    }
    else if ($_POST['trade_status'] == 'TRADE_SUCCESS') {
		
		
		
		
		
		 $Order = D('Order');
			 $Order->upstate($out_trade_no);
		//判断该笔订单是否在商户网站中已经做过处理
			//如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
			//请务必判断请求时的total_fee、seller_id与通知时获取的total_fee、seller_id为一致的
			//如果有做过处理，不执行商户的业务程序
				
		//注意：
		//付款完成后，支付宝系统发送该交易状态通知

        //调试用，写文本函数记录程序运行情况是否正常
        //logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
    }

	//——请根据您的业务逻辑来编写程序（以上代码仅作参考）——
        
	echo "success";		//请不要修改或删除
	 
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
}
else {
    //验证失败
    echo "fail";

    //调试用，写文本函数记录程序运行情况是否正常
    //logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
}
	}
	
	
	
	
}

	

