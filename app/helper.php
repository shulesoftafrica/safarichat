<?php
$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
if ($host == 'localhost') {
    define('ROOT', '/resources/');
} else {
    define('ROOT', '/resources/');
}
function mailConfig() {}

function getDevice()
{
    return 1;
}

function custom_date($datatime)
{
    $newTZ = new DateTimeZone('America/New_York');
    date_default_timezone_set('America/New_York');

    $GMT = new DateTimeZone(Config::get('app.timezone'));
    $date = new DateTime($datatime, $newTZ);
    $date->setTimezone($GMT);
    return $date->format('Y-m-d H:i:s');
}

function validateDate($date, $format = 'Y-m-d')
{
    $d = DateTime::createFromFormat($format, $date);
    // The Y ( 4 digits year ) returns TRUE for any integer with any number of digits so changing the comparison from == to === fixes the issue.
    return $d && $d->format($format) === $date;
}

function json_call($array = null)
{
    if (isset($_GET['callback']) === TRUE) {
        header('Content-Type: text/javascript;');
        header('Access-Control-Allow-Origin: http://client');
        header('Access-Control-Max-Age: 3628800');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');

        return request('callback') . '(' . (json_encode($array)) . ')';
    }
}

function money($amount, $decimal = 0)
{
    return number_format($amount, $decimal);
}

/*
 * *  Function:   Convert number to string
 * *  Arguments:  int
 * *  Returns:    string
 * *  Description:
 * *      Converts a given integer (in range [0..1T-1], inclusive) into
 * *      alphabetical format ("one", "two", etc.).
 */

function number_to_words($number)
{
    if (($number < 0) || ($number > 999999999)) {
        return "$number";
    }

    $Gn = floor($number / 1000000);  /* Millions (giga) */
    $number -= $Gn * 1000000;
    $kn = floor($number / 1000);     /* Thousands (kilo) */
    $number -= $kn * 1000;
    $Hn = floor($number / 100);      /* Hundreds (hecto) */
    $number -= $Hn * 100;
    $Dn = floor($number / 10);       /* Tens (deca) */
    $n = $number % 10; /* Ones */

    $res = "";

    if ($Gn) {
        $res .= number_to_words($Gn) . " Million";
    }

    if ($kn) {
        $res .= (empty($res) ? "" : " ") .
            number_to_words($kn) . " Thousand";
    }

    if ($Hn) {
        $res .= (empty($res) ? "" : " ") .
            number_to_words($Hn) . " Hundred";
    }

    $ones = array(
        "",
        "One",
        "Two",
        "Three",
        "Four",
        "Five",
        "Six",
        "Seven",
        "Eight",
        "Nine",
        "Ten",
        "Eleven",
        "Twelve",
        "Thirteen",
        "Fourteen",
        "Fifteen",
        "Sixteen",
        "Seventeen",
        "Eightteen",
        "Nineteen"
    );
    $tens = array(
        "",
        "",
        "Twenty",
        "Thirty",
        "Fourty",
        "Fifty",
        "Sixty",
        "Seventy",
        "Eighty",
        "Ninety"
    );

    if ($Dn || $n) {
        if (!empty($res)) {
            $res .= " and ";
        }

        if ($Dn < 2) {
            $res .= $ones[$Dn * 10 + $n];
        } else {
            $res .= $tens[$Dn];

            if ($n) {
                $res .= "-" . $ones[$n];
            }
        }
    }

    if (empty($res)) {
        $res = "zero";
    }

    return $res;
}

function getPackage()
{
    $user_id = \Auth::user()->id;
    
    // Check for active subscription
    $activePayment = DB::table('admin_payments')
        ->where('user_id', $user_id)
        ->where('subscription_end', '>=', now())
        ->orderBy('subscription_end', 'desc')
        ->first();
    
    return $activePayment;
}

function is_trial()
{
    $user = \Auth::user();
    $try_period = $user->created_at;
    $now = time();
    $your_date = strtotime($try_period);
    $datediff = $now - $your_date;
    $days = round($datediff / (60 * 60 * 24));

    // Check if user has active subscription
    $hasActiveSubscription = getPackage();
    if ($hasActiveSubscription) {
        return 0; // Not in trial if has active subscription
    }

    // Default trial period is 3 days for new users
    $trialDays = config('app.TRIAL_DAYS', 3);
    
    $trial = 1;
    if ((int) $days > (int) $trialDays) {
        $trial = 0;
    }

    return $trial;
}

function userAccessRole()
{
    $user_id = \Auth::user()->id;
    $objet = array();
    if ((int) $user_id > 0) {
        //get package subscribed
        $package = getPackage();
        if (!empty($package)) {
            //dynamically check features subscribed then show to users
            $features = \App\Models\AdminFeaturePackage::whereAdminPackageId($package->id)->get();
            foreach ($features as $feature) {
                array_push($objet, [$feature->adminFeature->code_name => $feature->value]);
            }
        } else {
            $objet = array();
        }
    }
    return $objet;
}

function form_error($errors, $tag)
{
    if ($errors != null && $errors->has($tag)) {
        return $errors->first($tag);
    }
}

function can_access($permission)
{
    $user_id = \Auth::user()->id;
    $return = 0;
    if ((int) $user_id > 0) {
        $global = userAccessRole();
        foreach ($global as $key => $value) {
            if ($permission == $key) {
                $return = $value;
            }
        }
    }
    return $return;
}

function createRoute()
{
    $url = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
    $url_param = explode('/', $url);


    $controller = isset($url_param[2]) && !empty($url_param[2]) ? $url_param[2] . '' : 'home';
    $method = isset($url_param[3]) && !empty($url_param[3]) ? $url_param[3] : 'index';
    $view = $method == 'view' ? 'show' : $method;

    return in_array($controller, array('public', 'storage')) ? NULL : ucfirst($controller) . '@' . $view;
}

function timeAgo($datetime, $full = false)
{
    return \Carbon\Carbon::createFromTimeStamp(strtotime($datetime))->diffForHumans();
}

/**
 * Drop-down Menu
 *
 * @access  public
 * @param   string
 * @param   array
 * @param   string
 * @param   string
 * @return  string
 */
if (!function_exists('form_dropdown')) {

    function form_dropdown($name = '', $options = array(), $selected = array(), $extra = '')
    {
        if (!is_array($selected)) {
            $selected = array($selected);
        }

        // If no selected state was submitted we will attempt to set it automatically
        if (count($selected) === 0) {
            // If the form name appears in the $_POST array we have a winner!
            if (isset($_POST[$name])) {
                $selected = array($_POST[$name]);
            }
        }

        if ($extra != '')
            $extra = ' ' . $extra;

        $multiple = (count($selected) > 1 && strpos($extra, 'multiple') === FALSE) ? ' multiple="multiple"' : '';

        $form = '<select name="' . $name . '"' . $extra . $multiple . ">\n";

        foreach ($options as $key => $val) {
            $key = (string) $key;

            if (is_array($val) && !empty($val)) {
                $form .= '<optgroup label="' . $key . '">' . "\n";

                foreach ($val as $optgroup_key => $optgroup_val) {
                    $sel = (in_array($optgroup_key, $selected)) ? ' selected="selected"' : '';

                    $form .= '<option value="' . $optgroup_key . '"' . $sel . '>' . (string) $optgroup_val . "</option>\n";
                }

                $form .= '</optgroup>' . "\n";
            } else {
                $sel = (in_array($key, $selected)) ? ' selected="selected"' : '';

                $form .= '<option value="' . $key . '"' . $sel . '>' . (string) $val . "</option>\n";
            }
        }

        $form .= '</select>';

        return $form;
    }
}

/**
 * 
 * @param type $phone_number
 * @return array($country_name, $valid_number) or not array if wrong number
 */
function validate_phone_number($number, $code = null)
{
    $country_code = $code == null ? request('country_code') : $code;
    $phone_number = preg_replace("/[^0-9]/", '', $number);
    $phone = preg_replace('/' . $country_code . '/', '', $phone_number, 1);
    $valid_number = $country_code . $phone;
    $valid = array(request('country_name'), $valid_number);
    return $valid;
}
$countries=  array(
            93 => " Afghanistan",
            355 => " Albania",
            213 => " Algeria",
            1 => " American Samoa",
            376 => "Andorra ",
            244 => " Angola",
            1 => " Anguilla",
            1 => " Antigua and Barbuda",
            54 => " Argentine Republic",
            374 => " Armenia",
            297 => " Aruba",
            247 => " Ascension",
            61 => " Australia",
            672 => " Australian External Territories",
            43 => " Austria ",
            994 => " Azerbaijani Republic",
            1 => " Bahamas ",
            973 => " Bahrain",
            880 => " Bangladesh ",
            1 => " Barbados ",
            375 => " Belarus ",
            32 => " Belgium ",
            501 => " Belize",
            229 => " Benin ",
            1 => " Bermuda ",
            975 => " Bhutan",
            591 => " Bolivia",
            387 => " Bosnia and Herzegovina ",
            267 => " Botswana",
            55 => " Brazil (Federative Republic of)",
            1 => " British Virgin Islands",
            673 => " Brunei Darussalam ",
            359 => " Bulgaria (Republic of)",
            226 => " Burkina Faso",
            257 => " Burundi (Republic of)",
            855 => " Cambodia (Kingdom of)",
            237 => " Cameroon (Republic of)",
            1 => " Canada",
            238 => " Cape Verde (Republic of)",
            1 => " Cayman Islands ",
            236 => " Central African Republic ",
            235 => " Chad (Republic of)",
            56 => " Chile ",
            86 => " China ( Republic of)",
            57 => " Colombia (Republic of)",
            269 => " Comoros (Union of the)",
            242 => " Congo (Republic of the)",
            682 => " Cook Islands",
            506 => " Costa Rica",
            225 => " Côte d \"Ivoire (Republic of)",
            385 => " Croatia (Republic of)",
            53 => " Cuba",
            357 => " Cyprus (Republic of)",
            420 => " Czech Republic ",
            850 => " Democratic People\"s Republic of Korea ",
            243 => " Democratic Republic of the Congo",
            670 => " Democratic Republic of Timor-Leste",
            45 => " Denmark",
            246 => " Diego Garcia ",
            253 => " Djibouti (Republic of) ",
            1 => " Dominica (Commonwealth of)",
            1 => " Dominican Republic",
            593 => " Ecuador",
            20 => " Egypt (Arab Republic of)",
            503 => " El Salvador (Republic of)",
            240 => " Equatorial Guinea (Republic of)",
            291 => " Eritrea",
            372 => " Estonia (Republic of)",
            251 => " Ethiopia (Federal Democratic Republic of) ",
            500 => " Falkland Islands (Malvinas) ",
            298 => " Faroe Islands",
            679 => " Fiji (Republic of)",
            358 => " Finland ",
            33 => " France",
            262 => " French Departments and Territories in the Indian Ocean ",
            594 => " French Guiana (French Department of)",
            689 => " French Polynesia (Territoire français \"outre-mer)",
            241 => " Gabonese Republic",
            220 => " Gambia (Republic of the)",
            995 => " Georgia",
            49 => " Germany (Federal Republic of)",
            233 => " Ghana",
            350 => " Gibraltar",
            881 => " Global Mobile Satellite System (GMSS) shared code",
            30 => " Greece ",
            299 => " Greenland (Denmark)",
            1 => " Grenada",
            388 => " Group of countries shared code",
            590 => " Guadeloupe (French Department of)",
            1 => " Guam ",
            502 => " Guatemala (Republic of)",
            224 => " Guinea (Republic of)",
            245 => " Guinea-Bissau (Republic of)",
            592 => " Guyana",
            509 => " Haiti (Republic of)",
            504 => " Honduras (Republic of)",
            852 => " Hong Kong China",
            36 => " Hungary (Republic of)",
            354 => " Iceland",
            91 => " India (Republic of)",
            62 => " Indonesia (Republic of)",
            870 => " Inmarsat SNAC ",
            98 => " Iran (Islamic Republic of)",
            964 => " Iraq (Republic of)",
            353 => " Ireland",
            972 => " Israel (State of)",
            39 => " Italy",
            1 => " Jamaica",
            81 => " Japan",
            962 => " Jordan (Hashemite Kingdom of)",
            7 => " Kazakhstan (Republic of)",
            254 => " Kenya (Republic of)",
            686 => " Kiribati (Republic of)",
            82 => " Korea (Republic of)",
            965 => " Kuwait (State of)",
            996 => " Kyrgyz Republic ",
            856 => " Lao People\"s Democratic Republic",
            371 => " Latvia (Republic of)",
            961 => " Lebanon ",
            266 => " Lesotho (Kingdom of)",
            231 => " Liberia (Republic of)",
            218 => " Libya (Socialist People\"s Libyan Arab Jamahiriya)",
            423 => " Liechtenstein (Principality of)",
            370 => " Lithuania (Republic of) ",
            352 => " Luxembourg",
            853 => " Macao China",
            261 => " Madagascar (Republic of)",
            265 => " Malawi",
            60 => " Malaysia",
            960 => " Maldives (Republic of)",
            223 => " Mali (Republic of)",
            356 => " Malta",
            692 => " Marshall Islands (Republic of the)",
            596 => " Martinique (French Department of)",
            222 => " Mauritania (Islamic Republic of)",
            230 => " Mauritius (Republic of)",
            269 => " Mayotte",
            52 => " Mexico",
            691 => " Micronesia (Federated States of)",
            373 => " Moldova (Republic of) ",
            377 => " Monaco (Principality of)",
            976 => " Mongolia ",
            382 => " Montenegro (Republic of)",
            1 => " Montserrat",
            212 => " Morocco (Kingdom of)",
            258 => " Mozambique (Republic of) ",
            95 => " Myanmar (Union of)",
            264 => " Namibia (Republic of)",
            674 => " Nauru (Republic of)",
            977 => " Nepal (Federal Democratic Republic of)",
            31 => " Netherlands (Kingdom of the)",
            599 => " Netherlands Antilles",
            687 => " New Caledonia (Territoire français d\"outre-mer)",
            64 => " New Zealand",
            505 => " Nicaragua",
            227 => "Niger (Republic of the)",
            234 => " Nigeria (Federal Republic of)",
            683 => " Niue ",
            1 => " Northern Mariana Islands (Commonwealth of the)",
            47 => " Norway",
            968 => " Oman (Sultanate of)",
            92 => " Pakistan (Islamic Republic of)",
            680 => " Palau (Republic of)",
            507 => " Panama (Republic of)",
            675 => " Papua New Guinea",
            595 => " Paraguay (Republic of)",
            51 => "Peru",
            63 => "Philippines (Republic of the)",
            48 => " Poland (Republic of)",
            351 => " Portugal",
            1 => " Puerto Rico",
            974 => " Qatar (State of)",
            40 => " Romania ",
            7 => " Russian Federation",
            250 => " Rwanda (Republic of)",
            290 => " Saint Helena",
            1 => " Saint Kitts and Nevis",
            1 => " Saint Lucia",
            508 => " Saint Pierre and Miquelon (Collectivité territoriale de la République française)",
            1 => " Saint Vincent and the Grenadines",
            685 => " Samoa (Independent State of)",
            378 => " San Marino (Republic of) ",
            239 => " Sao Tome and Principe (Democratic Republic of)",
            966 => " Saudi Arabia (Kingdom of)",
            221 => " Senegal (Republic of)",
            381 => " Serbia (Republic of)",
            248 => " Seychelles (Republic of)",
            232 => " Sierra Leone",
            65 => " Singapore (Republic of)",
            421 => " Slovak Republic",
            386 => " Slovenia (Republic of)",
            677 => " Solomon Islands",
            252 => " Somali Democratic Republic",
            27 => " South Africa (Republic of)",
            34 => " Spain",
            94 => " Sri Lanka (Democratic Socialist Republic of)",
            249 => " Sudan (Republic of the)",
            597 => " Suriname (Republic of)",
            268 => " Swaziland (Kingdom of)",
            46 => " Sweden",
            41 => " Switzerland (Confederation of)",
            963 => " Syrian Arab Republic",
            886 => " Taiwan China",
            992 => " Tajikistan (Republic of)",
            255 => " Tanzania (United Republic of)",
            66 => " Thailand",
            389 => " The Former Yugoslav Republic of Macedonia",
            228 => " Togolese Republic",
            690 => " Tokelau",
            676 => " Tonga (Kingdom of)",
            1 => " Trinidad and Tobago",
            290 => " Tristan da Cunha",
            216 => " Tunisia",
            90 => " Turkey",
            993 => " Turkmenistan",
            1 => " Turks and Caicos Islands",
            688 => " Tuvalu",
            256 => " Uganda (Republic of)",
            380 => " Ukraine",
            971 => " United Arab Emirates",
            44 => " United Kingdom of Great Britain and Northern Ireland ",
            1 => " United States of America",
            1 => " United States Virgin Islands",
            598 => " Uruguay (Eastern Republic of)",
            998 => " Uzbekistan (Republic of)",
            678 => " Vanuatu (Republic of)",
            379 => " Vatican City State",
            39 => " Vatican City State",
            58 => " Venezuela (Bolivarian Republic of)",
            84 => " Viet Nam (Socialist Republic of)",
            681 => " Wallis and Futuna (Territoire français d\"outre-mer)",
            967 => " Yemen (Republic of)",
            260 => "Zambia (Republic of)",
            263 => " Zimbabwe"
        );
define('COUNTRY_CODES',$countries);

function validate_phone_number_old($number)
{
    $phone_number = preg_replace("/[^0-9]/", '', $number);;
    if (strlen(preg_replace('#[^0-9]#i', '', $phone_number)) < 7 || strlen(preg_replace('#[^0-9]#i', '', $phone_number)) > 14) {
        return FALSE;
    } else {

        $y = substr($phone_number, -9);
        $z = str_ireplace($y, '', $phone_number);
        $p = str_ireplace('+', '', $z);

        $x = COUNTRY_CODES;


        foreach ($x as $key => $value) {
            if ($p == $key) {
                $country_name = $value;
                $code = $key;
            } else {
                $country_name = ' Tanzania (United Republic of)';
                $code = '255';
            }
        }

        $valid_number = $code . $y;

        $valid = array($country_name, $valid_number);
        return $valid;
    }
}
