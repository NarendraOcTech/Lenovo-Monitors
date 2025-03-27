<?php

namespace App\Middleware;

class DecryptDataMiddleware
{

    public function __invoke($request, $response, $next)
    {
        $userKey = $request->getAttribute('userKey');
        $activationData = $request->getAttribute('activationData');
        $input = $request->getParsedBody();
        if (isset($input["data"]) && $input["data"]) {
            $data = $input["data"];
            $key = $activationData["dataKey"];
            $dataAr = explode(".", $data);
            if (count($dataAr) == 3) {
                $header = $dataAr[0];
                $pay = $dataAr[1];
                $sig = $dataAr[2];

                $hed = base64_decode($header);
                $decode_pay = json_decode(base64_decode($dataAr[1]), true);

                $r2 = $sig[0];
                $r1 = $sig[1];
                $x = substr($sig, 2);
                $a_sig = substr($x, 0, $r1) . substr($x, $r1 + $r2);
                $my_sig = base64_encode(hash_hmac('sha256', $header . "." . $pay, $key));

                if ($decode_pay["t"] == $hed && $my_sig == $a_sig) {
                    $array_str = base64_decode($pay);
                    $json = json_decode($array_str, true);
                    if ($userKey == $json["userKey"]) {
                        $request = $request->withAttribute('json', $json);
                        return $next($request, $response);
                    }
                }
            }
        }
        //message: ["statusCode" => 400, "message" => "Invalid Data"];
        $str = base64_encode(json_encode(["statusCode" => 400, "message" => "Invalid Data"]));
        return $response->withJson(['resp' => $str], 400);
    }
}
