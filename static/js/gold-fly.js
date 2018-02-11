(function ($) {
	var count = 0;
	var jl = 8;
	var speed = 1000;
	var innertime = 80;
	$.extend({
		jinbifly: function (img, m, data, k) {
			count++;
			console.log(count);
			var img = $(img);
			for (var i = 0; i < data[k].length; i++) {
				img.clone().addClass("jinbix").appendTo("body").css({
					"z-index": 3,
					'position': 'fixed',
					'top': ($(m + data[k][i][0]).offset().top +20) + 'px',
					'left': ($(m + data[k][i][0]).offset().left+20) + 'px'
				}).animate({
					"left": ($(m + data[k][i][1]).offset().left+20),
					"top": ($(m + data[k][i][1]).offset().top+20)
				}, speed, '', function () {
				});
			}
		},
		jinbiflyall: function (img, m, data, callback, audio) {

			var nexttime = 0;
			if (data[0].length > 0) {
				for (var j = 1; j < jl; j++) {
					setTimeout(function () {
						$.jinbifly(img, m, data, 0);
					}, innertime * j);
					nexttime = (innertime * j + speed);
				}
				audio();
			}
			setTimeout(function () {

				var nexttime1 = 0;
				if (data[1].length > 0) {
					for (var j = 1; j < jl; j++) {
						setTimeout(function () {
							$.jinbifly(img, m, data, 1);
						}, innertime * j);
						nexttime1 = (innertime * j + speed);
					}
					audio();
				}
				setTimeout(function () {
					setTimeout(function(){
						$('.jinbix').remove();
					},1000);
					$.each(data[2],function(index,val){
						if(val>0){
							val = '+'+val;
						}
						$(m + index).find('.gameret').html(val).show();
					});
					setTimeout(function() {
						$('.gameret').hide();
					},3000);
					callback();
				}, nexttime1);
			}, nexttime);

		}
	});
})(jQuery);
