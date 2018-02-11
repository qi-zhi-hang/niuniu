var showed=0;
var poker = {
    timer: 1,

    fanpai: function (pai, imgpath, key, audio) {
        var _this = pai;

        if (_this.hasClass('open') || key == 0) {
            if (key == 0) {
                _this.find('img').attr('src', '/static/game/img/pai/0.png');
            }

            return false;
        }
        _this.addClass('open');
        _this.flip({
            direction: 'lr',
            content: '<img class="pk" src="' + imgpath + key + '.png">',
            speed: 150,
            onEnd: function () {

                audio();
            }
        });


    },
    fly: function (pai, imgpath, key, audio) {

        var _this = pai;
        if (_this.hasClass('showed')) {

            return false;
        }
        _this.addClass('showed');
        var dx = _this.offset().left;
        var dy = _this.offset().top;

        console.log([dx,dy]);
        //_this.empty();
        var img = _this.find('img');
        //img.attr('src', '/static/game/img/pai/0.png');
        var h = img.height();
        //_this.css('height','0');
        setTimeout(function () {
            img.css({
                'position': 'fixed',
                'top': '50%',
                left: '50%',
                'margin-left':'-20px',
                'margin-top':'-25px',
                'max-width':'40px',
                opacity:1,
                'z-index':5
            }).animate({'margin-top':0,'margin-left':0,'top': dy + 'px', left: dx + 'px'}, 300,'', function () {
                img.css({'position': 'static','max-width':'none'});
                //_this.html(img);
                showed ++ ;
                audio();
                setTimeout(function(){poker.fanpai(pai, imgpath, key, audio);},750);

            });
        }, 150 * poker.timer);
        poker.timer++;
    },
    ajaxshow:function(url, key,imgpath, element, audio){
        $.post(url, {key:key}, function(ret){
            if(ret.code == 1){
                poker.fanpai($(element),imgpath,ret.msg, audio);
            }
        }, 'json');
    },
    fanpaiall: function (elements, imgpath, keys, audio) {
        poker.timer = 1;
        $(elements).find('img').css('opacity', 0);
        $(elements).each(function (index, element) {
            if (typeof(keys[index]) != 'undefined') {
                if (showed >= $(elements).length) {
                    $(elements).find('img').css('opacity', 1);
                    poker.fanpai($(element), imgpath, keys[index],audio);
                } else {
                    $(elements).find('img').attr('src', '/static/game/img/pai/0.png');

                    poker.fly($(element), imgpath, keys[index],audio);
                }
            }
        });
    },
    init: function (poker) {
        $(poker).removeClass('open');
        $(poker).removeClass('showed');
        $(poker).css({'background-color':'rgba(0,0,0,0)'});
        showed= 0;
    }
}
