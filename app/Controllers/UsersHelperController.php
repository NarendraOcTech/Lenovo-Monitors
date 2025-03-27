<?php

namespace App\Controllers;

// use Illuminate\Database\Capsule\Manager as DB;

use App\Helper\Hash;
use App\Helper\Validation;
use App\Models\BaseModel;
use App\Models\Reward;
use App\Models\UniqueCode;
use App\Models\User;
use App\Models\UserProfile;


class UsersHelperController extends Controller
{
    public function getToken($min = 8, $max = 8, $possible = '')
    {
        if ($possible == '')
            $possible = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
        if ($min == $max)
            $l = $min;
        else
            $l = mt_rand($min, $max);
        $str = "";

        for ($i = 0; $i < $l; $i++) {
            $k = mt_rand(0, (strlen($possible) - 1));
            $str .= $possible[$k];
        }
        return $str;
    }

    public function addUtmParams($input, $value)
    {
        $utmList = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term'];
        foreach ($utmList as $utm) {
            $utmData = $this->getData($input, $utm);
            if (!empty($utmData)) {
                $value[$utm] = $utmData;
            }
        }
        return $value;
    }

    protected function getIpInfoDetails($details)
    {
        return json_encode([
            "ip" => $this->getData($details, 'ip'),
            "city" => $this->getData($details, 'city'),
            "region" => $this->getData($details, 'region'),
            "country" => $this->getData($details, 'countryCode'),
            "postal" => $this->getData($details, 'postal'),
            "loc" => $this->getData($details, 'latitude') . ', ' . $this->getData($details, 'longitude'),
        ]);
    }


    protected function getUserInput($req)
    {
        return $req->getParsedBody();

        // return $req->getAttribute('json');
    }

    protected function validateRegister($input)
    {

        [
            $companyName,
            $name,
            $mobile,
            $city,
            $code,
            $email,
            $RDName
        ] = BaseModel::getAllData(
                    $input,
                    "companyName",
                    "name",
                    "mobile",
                    "city",
                    "code",
                    "email",
                    "RDName"
                );

        $codeData = UniqueCode::where("code", $code)->first();


        if (empty($codeData) || User::containsFoulLanguage($companyName)) {
            $output = Validation::$invalidComName;
        } elseif (empty($name) || User::containsFoulLanguage($name)) {
            $output = Validation::$invalidName;
        } elseif (empty($mobile) || !preg_match(User::MOBILE_REGEX, $mobile)) {
            $output = Validation::$invalidMobileNumber;
        } elseif (empty($city)) {
            $output = Validation::$emptyCity;
        } elseif (empty($code) || empty($codeData)) {
            $output = Validation::$invalidUniqueCode;
        } elseif ($codeData->used) {
            $output = Validation::$codeAlreadyAdded;
        } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $output = Validation::$invalidEmail;
        } elseif (!empty($RDName) && User::containsFoulLanguage($RDName)) {
            $output = Validation::setError("invalid RD Name", "invalidRDName");
        } else {
            return [];
        }

        return $output;


    }



    protected function getProfileData($mobile)
    {
        $profileData = UserProfile::where("mobile", $mobile)->first();


        // return $profileData;
        if ($profileData != null) {
            $profileId = $profileData->id;
            $session = 0;
        }
        if (empty($profileData)) {
            $save = UserProfile::saveData(["mobile" => $mobile, "created_date" => date("Y-m-d")]);
            $session = 1;
            $profileId = $save['id'];
        }
        return [$session, $profileId];
    }

    protected function updateCode($code, $profileId, $userId, $mobile)
    {
        UniqueCode::where("code", $code)
            ->where('used', 0)
            ->update(['used' => 1, 'updated_at' => date('Y-m-d'), "profile_id" => $profileId, "user_id" => $userId, "mobile" => $mobile]);
    }

    protected function getAndSendReward($id, $code)
    {
        $userData = User::where("id", $id)->first();

        $uniqueCode = UniqueCode::where("code", $code)->first();
        if (!empty($userData) && !empty($uniqueCode)) {


            User::where("id", $id)->update(["reward_code" => $uniqueCode->code, 'reward_type' => $uniqueCode->reward_type, "complete" => 1]);

            return $uniqueCode->reward_code;
        }
        return null;
    }

}
