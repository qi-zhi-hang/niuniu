// JavaScript Document
(function($){
	//传入的$为jq对象
	var T;
	$.fn.countdown = function(options){
		var defaults = {
            initcount:0,
			end:function(){},
			start:function(){},
			down:function(count){}
        }
		//合并配置
		var opts = jQuery.extend({}, defaults, options);

		return this.each(function(index, element) {
			var obj = $(this);
			var count = opts.initcount;
			clearTimeout(T);
			var go = function (count){
				obj.html(count);
				if(count == 0){

					opts.end();
					return false;
				}

				T = setTimeout(function(){
					opts.down(count);
					count--;
					go(count);
				},1000);
			}
			go(opts.initcount);
		});
	}
})(jQuery);
