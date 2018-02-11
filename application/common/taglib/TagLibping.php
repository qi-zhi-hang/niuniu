<?php
namespace app\common\taglib;
use think\template\TagLib;
use think\Db;
class TagLibping extends TagLib{
    /**
     * 定义标签列表
     */
    protected $tags   =  [
        // 标签定义： attr 属性列表 close 是否闭合（0 或者1 默认1） alias 标签别名 level 嵌套层次
        'ueditor'      => ['attr' => 'name,id,style,value,class', 'close' => 0],
		'upload'      => ['attr' => 'name,id,style,value,class,type', 'close' => 0],
    ];
    /**
     * 获得一个编辑器
     */
    public function tagUeditor($attr, $content)
    {
        $id = $attr['id'];
        $name = $attr['name'];
		$value = $attr['value'];
		$style   	    =	!empty($attr['style'])?$attr['style']:'';
        $width		=	!empty($attr['width'])?$attr['width']: '100%';
        $height     =	!empty($attr['height'])?$attr['height'] :'320px';
		if(empty($value)){
			$html = '';
		}else{
			$html = '<?php echo '.$value.'; ?>';
		}
		$parseStr = '
	<script id="'.$id.'" class="ueditorelement" name="'.$name.'" type="text/plain" style="'.$style.'">'.$html.'</script>
	
	
	
	<script>
	
	function '.$id.'addLoadEventtagUeditor(func) { 
			var oldonload = window.onload; 
				if (typeof window.onload != \'function\') { 
				    window.onload = func;
				} else { 
				window.onload = function() { 
				    oldonload();
				func(); 
				} 
			}  
		}
	'.$id.'addLoadEventtagUeditor(function(){
		UE.getEditor("'.$id.'",{serverUrl:"'.url('Ueditor/upload').'"}); 
	});
	</script>
	';
        return $parseStr;
    } 
	/**
     * 获得一个上传组件
     */
    public function tagUpload($attr, $content)
    {
        $id = $attr['id'];
        $name = $attr['name'];
		$value = $attr['value'];
		$num = !empty($attr['num']) ? $attr['num'] : 1;
		$name1 = md5($name);
		if(strpos($name, '$') === 0){
			$name = '{'.$name.'}';
		}
		if(!empty($value)){
			$listhtml = '<?php if(strpos('.$value.',",")){
			$list'.$name1.' = explode(",", '.$value.');
		}else{
			$list'.$name1.'[0] = '.$value.';
		}

		if($list'.$name1.'[0] == "" && count($list'.$name1.') == 1){
			$list'.$name1.' = array();
		}

		foreach($list'.$name1.' as $v){
			?>
			<div style="position:relative;" class="preview processing image-preview success">
  <div class="details">
   <div class="filename"><span>图片</span></div>
  <div class="size"></div><img alt="图片" src="<?php echo $v;?>"></div>
  <div class="progress"><span class="upload" style="width: 100%;"></span></div>
  <div class="success-mark"><span>✔</span></div>
  <div class="error-mark"><span>✘</span></div>
  <div class="error-message"><span></span></div>
<a href="##" class="close">x</a><input type="hidden" name="'.$name.'" value="<?php echo $v;?>"></div>
		<?php }?>';
		}else{
			$listhtml = null;
		}
		
		

		$parseStr = '<a href="##" class="btn btn-success btn-sm" id="dropz'.$id.'">选择文件上传</a>
<div class="dropzonebox dropzonebox'.$id.'">
'.$listhtml.'

</div>
         
		 <script>
		 
		 function '.$id.'addLoadEventtagUpload(func) { 
			var oldonload = window.onload; 
				if (typeof window.onload != \'function\') { 
				    window.onload = func;
				} else { 
				window.onload = function() { 
				    oldonload();
				func(); 
				} 
			} 
		}
		 '.$id.'addLoadEventtagUpload(function(){
			 
			 
			  $("#dropz'.$id.'").dropzone({
        url: "{:url(\'Index/upload\')}", //必须填写
        method:"post",  //也可用put
        paramName:"upfile", //默认为file
        maxFiles:'.$num.',//一次性上传的文件数量上限
        maxFilesize: 20, //MB
        acceptedFiles: ".jpg,.gif,.png", //上传的类型
        previewsContainer:".dropzonebox'.$id.'", //显示的容器
        parallelUploads: 3,
        dictMaxFilesExceeded: "您最多只能上传'.$num.'个文件！",
        dictResponseError: \'文件上传失败!\',
        dictInvalidFileType: "你不能上传该类型文件,文件类型只能是*.jpg,*.gif,*.png。",
        dictFallbackMessage:"浏览器不受支持",
        dictFileTooBig:"文件过大上传文件最大支持.",
        previewTemplate: \'<div style="position:relative;" class=\"preview file-preview\">\n  <div class=\"details\">\n   <div class=\"filename\"><span></span></div>\n  </div>\n  <div class=\"progress\"><span class=\"upload\"></span></div>\n  <div class=\"success-mark\"><span>✔</span></div>\n  <div class=\"error-mark\"><span>✘</span></div>\n  <div class=\"error-message\"><span></span></div>\n<a href="##" class="close">x</a><input type="hidden" name="'.$name.'"></div>\',//设置显示模板
		success(file,dataUrl){
			file.previewTemplate.addClass("success");
			file.previewTemplate.find(\'input\').val(dataUrl);
			file.previewTemplate.find(\'.close\').click(function(){
				if(!confirm("确定要移除吗？")){
					return false;
				}
				$(this).parent().remove();
			});
		},
        init:function(){
            
            }});
			 
			 
			 $(".dropzonebox'.$id.'").find(\'.close\').click(function(){
				if(!confirm("确定要移除吗？")){
					return false;
				}
				$(this).parent().remove();
			});
		});
		 
   

</script>
		 
		 
		 

';
        
        
		
        return $parseStr;
    }
}
