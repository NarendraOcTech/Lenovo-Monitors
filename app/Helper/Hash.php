<?php

namespace App\Helper;

use App\Models\User;
use Slim\Http\Response;

class Hash
{
    private static $dataKey = "dd22727b1a56e933223d37184a6721d5f31a0bd97c333e1dd004d66227d12e07";
    private static $algo = 'aes-256-gcm';

    public static function encodeOutput(Response $res, $output)
    {
        $encOut = base64_encode(json_encode($output));
        return $res->withJson(['resp' => $encOut], $output["statusCode"]);
    }

    public static function encryptData($data)
    {
        $dataBin = openssl_encrypt(
            $data,
            self::$algo,
            hex2bin(self::$dataKey),
            OPENSSL_RAW_DATA,
            User::getDataIv(),
            $dataTag
        );
        return bin2hex($dataBin) . '.' . bin2hex($dataTag);
    }
}
