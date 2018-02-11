<?php
/**
 * Created by PhpStorm.
 * User: 10510
 * Date: 2017/6/25
 * Time: 21:05
 */

namespace app\Nav;
class Setting
{
    public function __construct($modules_id)
    {
        //echo __DIR__;
        dump(view(__DIR__.'/../Tpl/index.html', array('d'=>'78787878787')) -> getContent());
    }

    public function show()
    {

    }

    public function save()
    {

    }

}