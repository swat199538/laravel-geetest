<?php
/**
 * Created by PhpStorm.
 * User: wangl
 * Date: 2018/5/4
 * Time: 15:10
 */

namespace Swat\GeeTest;


use Illuminate\Support\Facades\Facade;

class GeeTestFacade extends Facade
{
    /**
     *获取绑定到IOC容器的名字
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'geetest';
    }
}