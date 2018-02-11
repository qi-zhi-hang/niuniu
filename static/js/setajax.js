// JavaScript Document
(function($){
	//传入的$为jq对象
	$.fn.ajaxsubmit = function(options){
		var defaults = {
            tip: $('<div class="showtip" style="position:fixed; display:none; background:rgba(0,0,0,0.5); z-index:9000; top:0; bottom:0; left:0; right:0;"><div  style="position:absolute; top:40%; text-align:center; width:100%;"><span class="msgbox" style="border:1px solid #ffffff;box-shadow: 0px 2px 5px #000;padding:15px 40px; background:#FFFFFF; border-radius:4px;"></span></div></div>'),
			success: function(ret){
				
			},
			returnform:true,
			tiptimeout:500,
			processing:'正在处理……'
        }
		//合并配置
		var opts = jQuery.extend({}, defaults, options);
		return this.each(function(index, element) {
			$(element).submit(function(e) {
				
				var url = $(element).attr('action');
				var data = $(element).serialize();
				var method = $(element).attr('method');
				var callback = typeof($(element).attr('callback')) == 'undefined' ? 'jump' : $(element).attr('callback');
				//正在处理
				opts.tip.find('.msgbox').html(opts.processing);
				$('body').append(opts.tip);
				opts.tip.css('color', 'green');
				opts.tip.show();
				$.ajax({
					url : url,
					data : data,
					type : method,
					dataType:"json",
					success:function(ret){
						//得到结果之后如何处理
						//成功并且指定要跳转
						opts.tip.find('.msgbox').html(ret.msg);
						if(ret.code == 1){
							//设置正确信息
							opts.tip.css('color', 'green');
							//通过form上面的callback属性指定跳转回调
							if(callback == "jump"){
								window.location.href = ret.url;
							}else if(callback == "reload"){
								window.location.reload();
							}else{
								//回调方法
								opts.success(ret);
							}
							if(opts.returnform){
								element.reset();
							}
						}else{
							opts.tip.css('color', 'red');
						}
						setTimeout(function(){
							opts.tip.hide();
						}, opts.tiptimeout);
					}
				});
				//禁止同步跳转
				return false;
			});
		});
	}
})(jQuery);

$('form[callback="none"]').ajaxsubmit();