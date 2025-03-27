<?php

namespace App\Models;

class User extends BaseModel
{
    const MOBILE_REGEX = '/^[6789][0-9]{9}$/';


    public static function getDataIv()
    {
        return hex2bin("b3310999b80f430c889d8661");
    }

    public static function containsFoulLanguage($input)
    {
        $lowerInput = explode(" ", strtolower($input));
        $foulList = [
            'fuck you',
            'fcuk you',
            'fuck',
            'fcuk',
            'f**k',
            'motherfucker',
            'mother fucker',
            'ass',
            'asshole',
            'shit',
            'cock',
            'cunt',
            'behenchod',
            'bhenchod',
            'madarchod',
            'nude',
            'boobs',
            'vagina',
            'dick',
            'hutiya',
            'chutiya',
            'sucker',
            'fucker',
            'porn',
            'chut'
        ];
        foreach ($lowerInput as $foul) {
            if (in_array($foul, $foulList)) {
                return true;
            }
        }
        return false;
    }
}
