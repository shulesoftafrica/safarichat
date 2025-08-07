<?php

namespace App\Http\Controllers\Traits;

/**
 * Description of Notify
 *
 * @author swill
 */
trait Notify {

    //put your code here

    static function dailyReport() {
        //user registered
        $users = \App\Models\User::whereToday()->count();

        //business registered
        $business = \App\Models\Business::whereToday()->count();

        //guest registered
        $guests = \App\Models\EventGuest::whereToday()->count();

        //messages sent
        $messages = \App\Models\MessageSentBy::whereToday()->count();

        //revenue received
        $revenue = \App\Models\AdminPayment::whereToday()->sum('amount');
        //services registered
        $service = \App\Models\User::whereToday()->count();

        //promotions required
        \App\Models\User::whereToday()->count();
        $message = '*Today Report*'
                . 'New User Registered:  *' . $users . '*,' . chr(10) . chr(10)
                . 'Businesses Registered :  ' . $business . ', ' . chr(10)
                . 'Total Guests Registered: ' . $guests . '' . chr(10)
                . 'Total Messages sent :  ' . $messages . ', ' . chr(10)
                . 'Total Service Registered: ' . $service . '' . chr(10)
                . 'Total Revenue Received  =Tsh ' . $revenue . '/=' . chr(10) . chr(10)
                . ''
                . 'Thanks';
        $chat_id = validate_phone_number('255734952586')[1] . '@c.us';
        self::sendMessage($chat_id, $message, 1);
    }

    static function userVideoTutorial($type) {
        switch ($type) {
            case 'promo':
                //we will put this outside before user login ot understand what is all about dikodiko
                break;
            case 'introduction':
                //once user login first time, we will prompt an option to view basic training about dikodiko
                break;
            case 'sms':
                //we will share a video on how to send and manage sms
                break;
            case 'stream':
                //we will send a video to explain how to stream his/her vidoe
                break;

            case 'promotion':
                //promotions
                break;

            default:
                break;
        }
    }

    static function businessVideoTutotial($type) {
        switch ($type) {
            case 'promo':
                //we will put this outside before user login ot understand what is all about dikodiko
                break;
            case 'introduction':
                //once user login first time, we will prompt an option to view basic training about dikodiko
                break;
            case 'sms':
                //we will share a video on how to send and manage sms
                break;
            case 'stream':
                //we will send a video to explain how to stream his/her vidoe
                break;

            case 'promotion':
                //promotions
                break;

            default:
                break;
        }
    }

    static function getCustomerFeedback() {
        
    }

    static function personalizeMarketing() {
        switch ($type) {
            case 'color':
                //we will give user an option to choose event colors
                break;

            default:
                break;
        }
    }

    function activityUsage() {
        
    }

    static function emailMarketing() {
        
    }

    static function loyality() {
        
    }

    static function socialMediaPostMessage() {
        
    }

}
