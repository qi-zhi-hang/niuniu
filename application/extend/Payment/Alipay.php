<?php
use think\Db;
class Alipay{
	public $site_id = 0;
	//构造函数
	public function __construct($callback = ''){
		require_once("Alipaylib/lib/alipay_submit.class.php");
		$db = Db::name('pay');
		$ret = $db -> where(array('classname' => 'Alipay')) -> find();
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
		$this->alipay_config['notify_url'] = $callback == ''?url('Pay/notify', array('code' => 'Alipay'), true, true):$callback;
		
		// 页面跳转同步通知页面路径 需http://格式的完整路径，不能加?id=123这类自定义参数，必须外网可以正常访问
		$this->alipay_config['return_url'] = "http://".$_SERVER['HTTP_HOST']."/";
		
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
		
		
		
		if(!$this->isMobile()){//PC接口
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
	private function isMobile(){ 
    // 如果有HTTP_X_WAP_PROFILE则一定是移动设备
    if (isset ($_SERVER['HTTP_X_WAP_PROFILE']))
    {
        return true;
    } 
    // 如果via信息含有wap则一定是移动设备,部分服务商会屏蔽该信息
    if (isset ($_SERVER['HTTP_VIA']))
    { 
        // 找不到为flase,否则为true
        return stristr($_SERVER['HTTP_VIA'], "wap") ? true : false;
    } 
    // 脑残法，判断手机发送的客户端标志,兼容性有待提高
    if (isset ($_SERVER['HTTP_USER_AGENT']))
    {
        $clientkeywords = array ('nokia',
            'sony',
            'ericsson',
            'mot',
            'samsung',
            'htc',
            'sgh',
            'lg',
            'sharp',
            'sie-',
            'philips',
            'panasonic',
            'alcatel',
            'lenovo',
            'iphone',
            'ipod',
            'blackberry',
            'meizu',
            'android',
            'netfront',
            'symbian',
            'ucweb',
            'windowsce',
            'palm',
            'operamini',
            'operamobi',
            'openwave',
            'nexusone',
            'cldc',
            'midp',
            'wap',
            'mobile'
            ); 
        // 从HTTP_USER_AGENT中查找手机浏览器的关键字
        if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT'])))
        {
            return true;
        } 
    } 
    // 协议法，因为有可能不准确，放到最后判断
    if (isset ($_SERVER['HTTP_ACCEPT']))
    { 
        // 如果只支持wml并且不支持html那一定是移动设备
        // 如果支持wml和html但是wml在html之前则是移动设备
        if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) && (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false || (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html'))))
        {
            return true;
        } 
    } 
    return false;
}
	
	//创建设置项表单
	public function createform(){
		
		$db = Db::name('pay');
		$ret = array('','','','');
		$ret = $db -> where(array('classname' => 'Alipay')) -> find();
		$ret = unserialize($ret['value']);
		$html = '<div class="form-group">
                                    <label class="control-label col-md-3">合作ID</label>
                                    <div class="col-md-4 col-xs-11">
                                        <input class="form-control" name="level[]" value="'.$ret[0].'" type="text" placeholder="">
                                       
                                    </div>
                                </div>
								<div class="form-group">
                                    <label class="control-label col-md-3">支付密钥</label>
                                    <div class="col-md-4 col-xs-11">
                                        <input class="form-control" name="level[]" value="'.$ret[1].'" type="text" placeholder="">
                                       
                                    </div>
                                </div>
								<div class="form-group">
                                    <label class="control-label col-md-3">收款账号</label>
                                    <div class="col-md-4 col-xs-11">
                                        <input class="form-control" name="level[]" value="'.$ret[2].'" type="text" placeholder="">
                                       
                                    </div>
                                </div>
								';

        return $html;
	}
	
	//保存设置项
	public function saveform(){
		if(isset($_POST['level'])){
			$data['classname'] = 'Alipay';
			$data['value'] = serialize($_POST['level']);
			$db = Db::name('pay');
			//dump($data);
			$ret = $db -> where(array('classname' => $data['classname'])) -> find();
			if($ret){
				$saveret = $db -> where(array('classname' => $data['classname'])) -> data(array('value' => $data['value'])) -> update();
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
		
		
		
		$ordermes = Db::name('order')->where(array('id'=>$orderid))->find();
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
		  
		  //建立请求
            $alipaySubmit = new AlipaySubmit($this->alipay_config);
            $html_text = $alipaySubmit->buildRequestForm($parameter,"get", "确认");
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
		
		
		
		
		
		 $Order = model('Order');
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

	

