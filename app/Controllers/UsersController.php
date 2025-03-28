<?php

namespace App\Controllers;

use App\Models\Activation;
use App\Helper\Hash;
use App\Helper\Validation;
use App\Models\BaseModel;
use App\Models\Reward;
use App\Models\UniqueCode;
use App\Models\User;
use Illuminate\Database\QueryException;

class UsersController extends UsersHelperController
{

    public function createUser($req, $res)
    {
        $input = $req->getParsedBody();
        $device = $this->isMobile() ? "mobile" : "web";
        $key = Activation::getToken(22, 25);
        $values = ['dataKey' => substr($key, 4, 14)];
        $masterKey = $this->getData($input, 'masterKey');
        if (!empty($masterKey) && is_numeric($masterKey)) {
            $values['masterKey'] = Activation::htmlEncode($masterKey);
            $values['isNewUser'] = 0;
        } else {
            $values['isNewUser'] = 1;
        }
        $values = $this->addUtmParams($input, $values);
        $values['created_date'] = date("Y-m-d");
        $details = isset($input['ipInfo']) ? $input['ipInfo'] : [];
        $ipInfo = $this->getIpInfoDetails($details);
        $values['user_ip'] = $ipInfo;
        $userKey = Activation::createUser($device, $values);

        $output = [
            "statusCode" => 200,
            "userKey" => $userKey,
            "dataKey" => $key,
        ];
        return Hash::encodeOutput($res, $output);
    }

    public function register($req, $res)
    {
        $input = $this->getUserInput($req);
        $output = $this->validateRegister($input);




        if (empty($output)) {
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
            $mobileEnc = Hash::encryptData($mobile);
            $savedData = [
                "company_name" => $companyName,
                "mobile" => Hash::encryptData($mobile),
                "code" => $code,
                "city" => $city,
                "name" => Hash::encryptData($name),
                "created_date" => date("Y-m-d"),
                "email" => Hash::encryptData($email),
                "rd_name" => $RDName
            ];
            [$savedData["session"], $savedData["profile_id"]] = $this->getProfileData($mobileEnc);
            // $output = $savedData;

            try {
                $save = User::saveData($savedData);
                $this->updateCode($code, $savedData["profile_id"], $save["id"], $mobileEnc);
                $output = Validation::$successOutput;
                $reward = UniqueCode::where("code", $code)->where("user_id", $save["id"])->first()->reward_type;
                $rewardType = $this->getAndSendReward($save['id'], $code);
                $output["reward"] = $rewardType;
                // $output["reward"] = $reward;

            } catch (QueryException $e) {
                $output = Validation::$codeAlreadyAdded;
                $output["message"] = $e;
            }

        }
        return Hash::encodeOutput($res, $output);
    }
}
