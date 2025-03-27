<?php



namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class BaseModel extends Model
{

    public static function createUser($device = 'web', $values = [])
    {
        $device = ($device == "mobile") ? "mobile" : "web";
        $child_name = get_called_class();

        $userKey = 0;
        $max = mt_getrandmax();
        if ($max > 4294967290) {
            $max = 4294967290;
        }

        while ($userKey == 0) {
            $userKey = rand(1, $max);
            $flag = $child_name::isUnique('userKey', $userKey);
            if (!$flag) {
                $userKey = 0;
            }
        }
        $values["userKey"] = $userKey;
        if (!(isset($values["masterKey"]) && $values["masterKey"] != "")) {
            $values['masterKey'] = $userKey;
        }
        $values["device"] = $device;
        $values["user_ip "] = $child_name::getClientIp();

        $browser = get_browser(null, true);
        $values["browser"] = $browser['browser'];
        $values["os"] = $browser['platform'];

        $values["created_day"] = date("l");
        $values["created_hour"] = date("G");
        $values["created_day_hour"] = date("D-G");

        $resp = $child_name::saveData($values);

        if ($resp["code"] == 200)
            return $userKey;
        else
            return 0;

    }


    public static function saveData($data = [])
    {
        if (is_array($data) && count($data) > 0) {
            $child_name = get_called_class();
            $obj = new $child_name();

            foreach ($data as $key => $val) {
                $encoded_key = $child_name::htmlEncode($key);
                $obj->$encoded_key = $child_name::htmlEncode($val);
            }
            if ($obj->save()) {
                return array("code" => 200, "message" => "Data saved", "id" => $obj->id);
            } else {
                return array("code" => 401, "message" => "Invalid data format");
            }
        }
        return array("code" => 400, "message" => "Invalid data format");
    }



    public static function isUnique($col_name = "", $value = "")
    {
        $child_name = get_called_class();

        $col_name = $child_name::htmlEncode($col_name);
        $value = $child_name::htmlEncode($value);

        if ($col_name == '' || $value == '')
            return false;

        $count = $child_name::where($col_name, $value)->count();
        if ($count == 0)
            return true;
        return false;
    }

    public static function htmlEncode($data)
    {
        if (is_array($data)) {
            foreach ($data as $key => $val) {
                $data[htmlspecialchars(trim($key), ENT_QUOTES, 'UTF-8')] = htmlspecialchars($val, ENT_QUOTES, 'UTF-8');
            }
            return $data;
        } else {
            return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
        }
    }




    public static function getClientIp()
    {
        $ipaddress = '';
        if (getenv('HTTP_CLIENT_IP'))
            $ipaddress = getenv('HTTP_CLIENT_IP');
        else if (getenv('HTTP_X_FORWARDED_FOR'))
            $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
        else if (getenv('HTTP_X_FORWARDED'))
            $ipaddress = getenv('HTTP_X_FORWARDED');
        else if (getenv('HTTP_FORWARDED_FOR'))
            $ipaddress = getenv('HTTP_FORWARDED_FOR');
        else if (getenv('HTTP_FORWARDED'))
            $ipaddress = getenv('HTTP_FORWARDED');
        else if (getenv('REMOTE_ADDR'))
            $ipaddress = getenv('REMOTE_ADDR');
        else
            $ipaddress = 'UNKNOWN';
        return $ipaddress;
    }


    public static function getToken($min = 8, $max = 8, $possible = '')
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


    public static function getAllData($list = [], ...$keys)
    {
        $dataArray = [];
        $notFoundKeys = [];
        if (is_array($list)) {
            foreach ($keys as $val) {
                if (isset($list[$val])) {
                    $dataArray[] = $list[$val];
                } else {
                    $dataArray[] = null;
                    $notFoundKeys[] = $val;
                }

            }
            $dataArray[] = $notFoundKeys;
            return $dataArray;
        }
        return '';

    }


}
