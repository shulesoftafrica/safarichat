<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Http\Controllers\Message;
use DB;

class Kernel extends ConsoleKernel {

    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
            //
    ];
    public $emails;

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule) {
        $schedule->command('inspire')
                ->hourly();
        $schedule->call(function () {
            (new Message())->process();
        })->everyMinute();

        $schedule->call(function () {
            $this->checkSchedule();
             $object = (new Message());
              $chat_id =  '255714825469@c.us';
            $object->sendMessage($chat_id, 'This is a testing message at '.date('Y m d h:i'), 1);
            
        })->everyMinute();

        $schedule->call(function () {
            $this->reminders();
        })->dailyAt('08:40'); // Eq to 07:40 AM 
    }

    public function reminders() {
        $object = (new Message());
        // 5 days before the event
        $close_events = \App\Models\Event::where('date', Carbon::now()->subDays(7)->toDateTimeString())->get();
        foreach ($close_events as $user_event) {
            //send congratulations message
            $message = 'Hello *' . $user_event->name . '*' . chr(10) . chr(10)
                    . 'Do you know you can stream your event live via your DikoDiko Account and all guest lists will be able to attend online ?'
                    . '' . chr(10)
                    . '*Follow these instructions to stream your events live* '
                    . '' . chr(10)
                    . '1. Login to your youtube account now (via your computer browser)' . chr(10)
                    . '2. On the top right, click an icon named *Go Live*' . chr(10)
                    . '3. Fill the form opened with your Event Title, make it unlisted, and then click *schedule it later* to specify your event date' . chr(10)
                    . '4. On the next page, click on the button written Share, and copy the link' . chr(10)
                    . '5. Login into your dikodiko.co.tz account, navigate to settings , then click *Event Setting*, and on the form, paste that link (url)'
                    . ' you copy from youtube. Save, and then click the button'
                    . ' named *Generate Event Code* where SMS will be sent to all your guest list with a link to view your event live' . chr(10)
                    . '6. Download Streaming Application named *Streamlab* in google play (if you use android) or IOS. Login into that application with your youtube(google)'
                    . 'credentials' . chr(10)
                    . '7. On the day of event, open *streamlab* application and then click go Live, (event name will appear), then click OKAY'
                    . '' . chr(10)
                    . 'Follow those simple steps and your event will looks amazing live'
                    . chr(10)
                    . 'Thanks';
            $chat_id = $user_event->phone . '@c.us';
            $object->sendMessage($chat_id, $message, 1);
        }

        //one day after registration if user has no event
        $users = \App\Models\User::whereNotIn('id', \App\Models\UsersEvent::get(['user_id']))->whereNotIn('id', \App\Models\Business::get(['user_id']))->get();
        foreach ($users as $user_withno_event) {
            //send congratulations message
            $message = 'Hello *' . $user_withno_event->name . '*' . chr(10) . chr(10)
                    . 'You have successfully registered at DikoDiko (www.dikodiko.co.tz) but you have neither created an Event nor a business account.'
                    . '' . chr(10)
                    . 'Have you stuck anywhere? If YES, feel free to let me know so I can assist you'
                    . '' . chr(10)
                    . 'Accounts with no activities will be automatically removed after few days'
                    . chr(10)
                    . 'Thanks';
            $chat_id = $user_withno_event->phone . '@c.us';
            $object->sendMessage($chat_id, $message, 1);
        }

        //reminder if no activity within a weak
        //reminder to delete an verified account
        $unverified_users = \App\Models\User::whereNull('email_verified_at')->where('date', '>', Carbon::now()->subDays(5)->toDateTimeString())->get();
        //
        foreach ($unverified_users as $unverified_user) {
            //send  message
            $message = 'Hello *' . $unverified_user->name . '*' . chr(10) . chr(10)
                    . 'This is the reminder to activate your account at www.dikodiko.co.tz. '
                    . '' . chr(10)
                    . 'To verify your account, kindly login with your username and password, then at the top you will see a message prompt you to verify, click the message'
                    . 'and the diolog will appear to let you verify your account with a code. '
                    . '' . chr(10)
                    . 'Your verification code is *' . $unverified_user->verify_code . '* .' . chr(10)
                    . '' . chr(10)
                    . 'Accounts not verified within 8 days will be automatically removed'
                    . chr(10)
                    . 'Thanks';
            $chat_id = $unverified_user->phone . '@c.us';
            $object->sendMessage($chat_id, $message, 1);
        }
        //delete not active account for 10 days
        $unverified_users_delete = \App\Models\User::whereNull('email_verified_at')->where('date', '>', Carbon::now()->subDays(8)->toDateTimeString())->get();
        //
        foreach ($unverified_users_delete as $user_to_delete) {
            //send  message
            $message = 'Hello *' . $user_to_delete->name . '*' . chr(10) . chr(10)
                    . 'Your account at DikoDiko has been deleted due to no activity.' . chr(10)
                    . 'Thanks';
            $chat_id = $user_to_delete->phone . '@c.us';
            $object->sendMessage($chat_id, $message, 1);
            $user_to_delete->delete();
        }

        //no event without no guests
        //one day after registration if user has no event
        $users_no_budgets = \DB::select('select name, email,phone from users where id not in (select user_id from businesses where user_id is not null) and id not in (select user_id from users_events where event_id not in (select event_id from budgets))');

        foreach ($users_no_budgets as $user_) {
            //send  message
            $message = 'Hello *' . $user_->name . '*' . chr(10) . chr(10)
                    . 'Your events will looks great if you will manage all your guest lists via DikoDiko.'
                    . ' Login now into your account and register your friends, relatives etc who will attend your event'
                    . ' .' . chr(10)
                    . 'If you stuck on anything, feel free to let us know' . chr(10)
                    . 'Thanks';
            $chat_id = $user_->phone . '@c.us';
            $object->sendMessage($chat_id, $message, 1);
            $user_to_delete->delete();
        }
    }

    public function checkSchedule() {

        $schedules = DB::table('reminders')->get();
        $timestamp = time() + 60*60*3;
        $time = date('H', $timestamp);
        //$current_time = date('H', strtotime(date('H:i')) + (60 * 60 * 3 - 60 * 2)); // plus +3 GMT hours to match with Tanzania time
        //   $current_time = date('H:i');
        $current_time=$time;
        foreach ($schedules as $schedule) {


            if (in_array(date('l'), explode(',', $schedule->days)) && $current_time ==date('H', strtotime($schedule->time))) {
                //execute command;
                $this->sendReminder($schedule);
            }

            if (strlen($schedule->days) < 4) {
                $day = $schedule->date;
                if (date('dmY', strtotime($day)) == date('dmY') && $current_time == date('H', strtotime($schedule->time))) {
                    $this->sendReminder($schedule);
                }
            }
        }
    }

    public function sendReminder($schedule) {

        if (!empty(explode(',', $schedule->user_id)) > 0) {
            $users_list = empty(explode(',', $schedule->user_id)) ? [0] : explode(',', $schedule->user_id);
            //switch criteria to see how the best we can allign as follows
            if ($schedule->criteria == 6 || (int) $schedule->criteria == 0) {
                //This is custom selection, so take users in the array lists
                $users = \App\Models\EventsGuest::whereIn('id', $users_list)->get();
            } else {
                //take event guest lists, and then excluse what is in the array 
                $event_id = \App\Models\User::find($schedule->user_id)->usersEvents()->orderBy('id', 'desc')->first()->event_id;
                $users = $this->getUserByCriteria($schedule->criteria, $event_id, $users_list);
            }
            foreach ($users as $guest) {
                $datediff = time() - strtotime($guest->event->date);
                $paid_amount = isset($guest->custom) ? 0 : $guest->payments()->sum('amount');
                $replacements = array(
                    $guest->guest_name, $guest->guest_pledge, $paid_amount, ((float) $paid_amount - (float) $guest->guest_pledge), round($datediff / (60 * 60 * 24))
                );
                $sms = (new Message())->getCleanSms($replacements, $schedule->message, array(
                    '/#name/i', '/#pledge/i', '/#paid_amount/i', '/#balance/i', '/#days_remain/i'
                ));
                $chat_id = validate_phone_number($guest->guest_phone)[1] . '@c.us';
                $sources = explode(',', $schedule->channels);
                foreach ($sources as $source) {
                    (new Message())->storeMessage($sms, $chat_id, $source);
                }
            }
        }
    }

    public function getUserByCriteria($criteria, $event_id, $exclude_lists = null) {
        switch ($criteria) {
            case 1:
                //All
                $users = \App\Models\EventsGuest::where('event_id', $event_id);
                break;
            case 3:
                //Full Paid Guest
                $users = \App\Models\EventsGuest::where('event_id', $event_id)->whereIn('id', \App\Models\Payment::get(['events_guests_id']))->whereNotIn('id', $exclude_lists);
                break;
            case 4:
                //Non Paid Guest
                $users = \App\Models\EventsGuest::where('event_id', $event_id)->whereNotIn('id', \App\Models\Payment::get(['events_guests_id']))->whereNotIn('id', $exclude_lists);
                break;
            case 5:
                //Partially Paid Guest
                $users = \App\Models\EventsGuest::where('event_id', $event_id)->whereNotIn('id', \App\Models\Payment::get(['events_guests_id']))->whereNotIn('id', $exclude_lists);
                break;
            default:
                break;
        }
        return $users->get();
    }

    /**
     * 
     * @param type $fields
     */
    private function curlServer($fields, $url) {
// Open connection
        $ch = curl_init();
// Set the url, number of POST vars, POST data

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'application/x-www-form-urlencoded'
        ));

        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    public function getCleanSms($replacements, $message, $pattern = null) {
        $sms = preg_replace($pattern != null ? $pattern : array(
            '/#name/i', '/#pledge/i', '/#paid_amount/i', '/#balance/i', '/#days_remain/i'
                ), $replacements, $message);
        if (preg_match('/#/', $sms)) {
            //try to replace that character
            return preg_replace('/\#[a-zA-Z]+/i', '', $sms);
        } else {
            return $sms;
        }
    }

    public function sendAnnualAniversary() {
        $events = \App\Models\Events::where('date', '<=', Carbon::now()->subDays(360)->toDateTimeString())->whereMonth('date', date('m'))->whereDay('date', date('d'))->get();
        foreach ($events as $event) {

            $message = 'Hello ' || c . name || ', tunapenda kukutakia ' || a . name || ' HAPPY ANNIVERSARY kwa ' . $event->name . ' iliyofanyika  tarehe kama ya leo ' . date('d M Y', strtotime($event->date)) . ' '
                    . '. Ubarikiwe';
            $messages = \App\Models\Message::firstOrCreate([
                        'body' => $message, 'user_id' => $event->user_id, 'phone' => $event->user->phone
            ]);
            \App\Models\MessageSentby::create(['message_id' => $messages->id, 'channel' => 'phone-sms']);
        }
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands() {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }

}
