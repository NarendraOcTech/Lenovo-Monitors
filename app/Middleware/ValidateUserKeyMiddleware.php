<?php

namespace App\Middleware;

use App\Models\Activation;

class ValidateUserKeyMiddleware
{
    public function __invoke($request, $response, $next)
    {
        $isValid = false;
        $route = $request->getAttribute('route');
        $userKey = $route->getArgument('userKey');

        if ($userKey){
            $activationData = Activation::where('userKey', $userKey)->first();
            if ($activationData){
                $isValid = true;
                $request= $request->withAttribute('userkey', $userKey);
                $request= $request->withAttribute('activationData', $activationData);
            }
        }

        if ($isValid){
            return $next($request, $response);
        }

        $str = 'eyJzdGF0dXNDb2RlIjogNDAwLCAibWVzc2FnZSI6ICJJbnZhbGlkIERhdGEifQ==';
        return $response->withJson(['resp' => $str], 400);

    }
}
