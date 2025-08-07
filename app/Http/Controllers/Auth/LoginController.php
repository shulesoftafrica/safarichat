<?php

namespace App\Http\Controllers\Auth;
use Socialite;
use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\User as Authenticatable;
use DB;

class LoginController extends Controller {
    /*
      |--------------------------------------------------------------------------
      | Login Controller
      |--------------------------------------------------------------------------
      |
      | This controller handles authenticating users for the application and
      | redirecting them to your home screen. The controller uses a trait
      | to conveniently provide its functionality to your applications.
      |
     */

use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct() {
        $this->middleware('guest')->except('logout');
    }

    protected function authenticated(Request $request, Authenticatable $user) {
        /* Your db inserts */
        $this->saveUserLocation($user->id);
        return redirect()->intended($this->redirectTo);
    }
    /**
     * Redirect the user to the GitHub authentication page.
     *
     * @return \Illuminate\Http\Response
     */
      public function redirectToProvider($social)
      {
          return Socialite::driver($social)->redirect();
      }

      /**
       * Obtain the user information from GitHub.
       *
       * @return \Illuminate\Http\Response
       */
      public function handleProviderCallback($social)
      {
          $user = Socialite::driver($social)->user();

          // $user->token;
      }
    /**
     * Get the needed authorization credentials from the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    protected function credentials(Request $request) {

        if (filter_var($request->get('email'), FILTER_VALIDATE_EMAIL)) {
            return ['email' => $request->get('email'), 'password' => $request->get('password')];
        } else {
            $phone= validate_phone_number(request('email'), request('country_code'))[1];
            return ['phone' => $phone, 'password' => $request->get('password')];
        }
    }

    public function saveUserLocation($user_id, $action = null) {
        $this->locate();
        return DB::table('login_locations')->insert([
                    'ip' => $this->ip,
                    'city' => $this->city,
                    'region' => $this->region,
                    'country' => $this->countryName,
                    'latitude' => $this->latitude,
                    'longtude' => $this->longitude,
                    'timezone' => $this->timezone,
                    'user_id' => $user_id,
                    'action' => $action,
                    'continent' => $this->continentName,
                    'currency_code' => $this->currencyCode,
                    'currency_symbol' => $this->currencySymbol,
                    'currency_convert' => $this->currencyConverter,
                    'location_radius_accuracy' => $this->locationAccuracyRadius]);
    }

    //the geoPlugin server
    public $host = 'http://www.geoplugin.net/php.gp?ip={IP}&base_currency={CURRENCY}&lang={LANG}';
    //the default base currency
    public $currency = 'USD';
    //the default language
    public $language = 'en';
    //initiate the geoPlugin publics
    public $ip = null;
    public $city = null;
    public $region = null;
    public $regionCode = null;
    public $regionName = null;
    public $dmaCode = null;
    public $countryCode = null;
    public $countryName = null;
    public $inEU = null;
    public $euVATrate = false;
    public $continentCode = null;
    public $continentName = null;
    public $latitude = null;
    public $longitude = null;
    public $locationAccuracyRadius = null;
    public $timezone = null;
    public $currencyCode = null;
    public $currencySymbol = null;
    public $currencyConverter = null;

    /*     * *
     * in updated mode, we will use https://ipstack.com/ to check these information instead of this geoplugin
     */

    public function locate($ip = null) {
        global $_SERVER;
        if (is_null($ip)) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        $host = str_replace('{IP}', $ip, $this->host);
        $host = str_replace('{CURRENCY}', $this->currency, $host);
        $host = str_replace('{LANG}', $this->language, $host);

        $data = array();
        $response = $this->fetch($host);

        $data = empty($response) ? '' : unserialize($response);

        //set the geoPlugin publics
        $this->ip = $ip;
        $this->city = isset($data['geoplugin_city']) ? $data['geoplugin_city'] : '';
        $this->region = isset($data['geoplugin_region']) ? $data['geoplugin_region'] : '';
        $this->regionCode = isset($data['geoplugin_regionCode']) ? $data['geoplugin_regionCode'] : '';
        $this->regionName = isset($data['geoplugin_regionName']) ? $data['geoplugin_regionName'] : '';
        $this->dmaCode = isset($data['geoplugin_dmaCode']) ? $data['geoplugin_dmaCode'] : '';
        $this->countryCode = isset($data['geoplugin_countryCode']) ? $data['geoplugin_countryCode'] : '';
        $this->countryName = isset($data['geoplugin_countryName']) ? $data['geoplugin_countryName'] : '';
        $this->inEU = isset($data['geoplugin_inEU']) ? $data['geoplugin_inEU'] : '';
        $this->euVATrate = isset($data['geoplugin_euVATrate']) ? $data['geoplugin_euVATrate'] : '';
        $this->continentCode = isset($data['geoplugin_continentCode']) ? $data['geoplugin_continentCode'] : '';
        $this->continentName = isset($data['geoplugin_continentName']) ? $data['geoplugin_continentName'] : '';
        $this->latitude = isset($data['geoplugin_latitude']) ? $data['geoplugin_latitude'] : '';
        $this->longitude = isset($data['geoplugin_longitude']) ? $data['geoplugin_longitude'] : '';
        $this->locationAccuracyRadius = isset($data['geoplugin_locationAccuracyRadius']) ? $data['geoplugin_locationAccuracyRadius'] : '';
        $this->timezone = isset($data['geoplugin_timezone']) ? $data['geoplugin_timezone'] : '';
        $this->currencyCode = isset($data['geoplugin_currencyCode']) ? $data['geoplugin_currencyCode'] : '';
        $this->currencySymbol = isset($data['geoplugin_currencySymbol']) ? $data['geoplugin_currencySymbol'] : '';
        $this->currencyConverter = isset($data['geoplugin_currencyConverter']) ? $data['geoplugin_currencyConverter'] : '';
    }

    function fetch($host) {

        if (function_exists('curl_init')) {

            //use cURL to fetch data
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $host);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_USERAGENT, 'geoPlugin PHP Class v1.1');
            $response = curl_exec($ch);
            curl_close($ch);
        } else if (ini_get('allow_url_fopen')) {

            //fall back to fopen()
            $response = file_get_contents($host, 'r');
        } else {

            trigger_error('geoPlugin class Error: Cannot retrieve data. Either compile PHP with cURL support or enable allow_url_fopen in php.ini ', E_USER_ERROR);
            return;
        }

        return $response;
    }

}
