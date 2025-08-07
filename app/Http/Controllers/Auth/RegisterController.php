<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use App\Models\User;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class RegisterController extends Controller {
    /*
      |--------------------------------------------------------------------------
      | Register Controller
      |--------------------------------------------------------------------------
      |
      | This controller handles the registration of new users as well as their
      | validation and creation. By default this controller uses a trait to
      | provide this functionality without requiring any additional code.
      |
     */

use RegistersUsers;

    /**
     * Where to redirect users after registration.
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
        $this->middleware('guest');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data) {
        if ((int) $data['user_type_id'] == 2) {
            return Validator::make($data, [
                        'business_name' => ['required', 'string', 'max:255'],
                        'name' => ['required', 'string', 'max:255'],
                        'ward_id' => ['required', 'integer', 'min:1'],
                        'phone' => ['required', 'string', 'max:30', 'min:6', 'unique:businesses'],
                        //'address' => ['required', 'string', 'max:255'],
                        //'business_phone' => ['required', 'string', 'max:30', 'unique:businesses'],
                        // 'email' => ['string', 'email', 'max:255', 'unique:users'],
                        'password' => ['required', 'string', 'min:8', 'confirmed'],
            ]);
        } else {
            return Validator::make($data, [
                        'name' => 'required|string|max:255|min:4', //['required', 'string', 'max:255'],
                        'phone' => ['required', 'string', 'max:18', 'min:6', 'unique:users'],
                        //  'email' => ['string', 'email', 'max:255', 'min:4', 'unique:users'],
                        'password' => ['required', 'string', 'min:8', 'confirmed'],
            ]);
        }
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\Models\User
     */
    protected function create(array $data) {

    
        $verify_code = rand(192, 9999) . substr(str_shuffle('abcdefghkmnpqrst'), 0, 3);
        if ((int) $data['user_type_id'] == 2) {
            return $this->registerBusiness($data, $verify_code);
        } else {
            $valid = validate_phone_number($data['phone']);

            $user = User::create([
                        'name' => $data['name'],
                        //'email' => strlen($data['email']) < 4 ? time() . '@dikodiko.co.tz' : strtolower($data['email']),
                        'phone' => $valid[1],
                        'verify_code' => $verify_code,
                        'user_type_id' => $data['user_type_id'],
                        'password' => Hash::make($data['password']),
            ]);
        }

        $chat_id = validate_phone_number($data['phone'])[1] . '@c.us';
        $message = 'Hello ' . ucfirst(strtolower($data['name'])) . ''
                . 'Your Verification code: ' . $verify_code . ''
                . ''
                . ''
                . 'Welcome to your DikoDiko Account';
        $discount = \App\Models\DiscountRequest::where('phone', validate_phone_number($data['phone'])[1])->first();
        if (!empty($discount)) {
            \App\Models\DiscountRequest::where('phone', validate_phone_number($data['phone'])[1])->update(['status' => 1]);
            $message2 = 'Hello ' . $discount->user->name . ''
                    . 'User you invite with phone number : ' . validate_phone_number($data['phone'])[1] . ''
                    . 'has joined DikoDiko with a name ' . $data['name'] . ' '
                    . ''
                    . 'Since you invite this user, we have give you a discount of Tsh 5,000'
                    . 'Welcome';
            $chat_id2 = validate_phone_number($discount->user->phone)[1] . '@c.us';
            $this->sendMessage($chat_id2, $message2, 1);
        }
        isset($data['email']) && !empty($data['email']) ? $this->send_email($data['email'], 'Email Verification', $message) : '';
        $this->sendMessage($chat_id, $message, 1);
        $this->notifyAdmin('DikoDiko: New User registration: User with a name : ' . ucfirst(strtolower($data['name'])));
        return $user;
    }

    protected function registerBusiness($data, $verify_code) {

        $check_user = User::where('phone', validate_phone_number($data['phone'])[1])->first();
        $user = empty($check_user) ? User::create([
                    'name' => $data['name'],
                    // 'email' => strlen($data['email']) < 4 ? time() . '@dikodiko.co.tz' : strtolower($data['email']),
                    'phone' => validate_phone_number($data['phone'])[1],
                    'verify_code' => $verify_code,
                    'user_type_id' => $data['user_type_id'],
                    'password' => Hash::make($data['password'])]) : $check_user;
        if ($user) {
            $check_business = \App\Models\Business::where('phone', validate_phone_number($data['business_phone'])[1])->first();
            $business = empty($check_business) ? \App\Models\Business::create([
                        'name' => $data['business_name'],
                        //'email' => isset($data['business_email']) ? $data['business_email'] : '',
                        'phone' => validate_phone_number($data['business_phone'])[1],
                        'user_id' => $user->id,
                        'address' => isset($data['address']) ? $data['address'] : '',
                        'ward_id' => $data['ward_id']
                    ]) : $check_business;
            if ($business) {
                \App\Models\BusinessService::firstOrCreate([
                    'business_id' => $business->id,
                    'service_id' => $data['service_id']]);
            }
            if ((int) $business->user_id == 0) {
                //this business was registered by event owner so complete the profile here
                \App\Models\Business::where('phone', validate_phone_number($data['business_phone'])[1])->update([
                    'name' => $data['business_name'],
                    //'email' => isset($data['business_email']) ? $data['business_email'] : '',
                    'user_id' => $user->id,
                    'address' => isset($data['address']) ? $data['address'] : '',
                    'ward_id' => $data['ward_id']
                ]);
            }
            $chat_id = validate_phone_number($data['phone'])[1] . '@c.us';
            $message = 'Hello ' . ucfirst(strtolower($data['name'])) . ''
                    . 'Your Verification code: ' . $verify_code . ''
                    . ''
                    . ''
                    . 'Welcome to your DikoDiko Account';
            $this->sendMessage($chat_id, $message, 1);
            $this->notifyAdmin('DikoDiko: New Business registration: User a name : ' . ucfirst(strtolower($data['business_name'])));
            return $user;
        }
    }

}
