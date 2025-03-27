<?php

namespace App\Helper;

use App\Models\BlockedUser;

class Validation
{


    public static function setError($message, $messageId)
    {
        return ['statusCode' => 400, 'message' => $message, "messageId" => $messageId];
    }



    public static $invalidComName = [
        'statusCode' => 400,
        'message' => 'Invalid Company Name',
        'messageId' => 'invalidComName'
    ];

    public static $invalidName = [
        'statusCode' => 400,
        'message' => 'Invalid Name',
        'messageId' => 'invalidName'
    ];


    public static $invalidMobileNumber = [
        'statusCode' => 400,
        'message' => 'Please enter a valid mobile number',
        'messageId' => 'invalidMobileNumber'
    ];


    public static $emptyCity = [
        'statusCode' => 400,
        'message' => 'City can not be empty',
        'messageId' => 'emptyCity'
    ];


    public static $invalidUniqueCode = [
        'statusCode' => 400,
        'message' => 'Invalid unique code',
        'messageId' => 'invalidUniqueCode'
    ];

    public static $codeAlreadyAdded = [
        'statusCode' => 400,
        'message' => 'The Unique Code has already been used',
        'messageId' => 'codeAlreadyUsed'
    ];

    public static $invalidEmail = [
        'statusCode' => 400,
        'message' => 'Invalid email',
        'messageId' => 'invalidEmail'
    ];

    public static $successOutput = [
        'statusCode' => 200,
        'message' => 'Success'
    ];

   
}