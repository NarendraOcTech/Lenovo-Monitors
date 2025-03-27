<?php

namespace App\Controllers;

use App\Helper\Hash;
use App\Models\User;
use App\Models\SentSms;
use ReallySimpleJWT\Token;

class Controller
{
    protected $container;
    public function __construct($container)
    {
        $this->container = $container;
    }

    protected function getData($list = [], $key = '')
    {
        if (is_array($list) && isset($list[$key])) {
            return trim($list[$key]);
        }
        return '';
    }



    protected function isMobile()
    {
        return preg_match("/(android|avantgo|blackberry|bolt|boost|cricket|docomo|fone|hiptop|mini|mobi|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos)/i", $_SERVER["HTTP_USER_AGENT"]);
    }

}
