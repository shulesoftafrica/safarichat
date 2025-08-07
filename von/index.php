<?php

include_once 'database.php';
include_once 'stock.php';
include_once 'dikodiko.php';
include_once 'ibada.php';

class Sync {

    protected $dikodiko;
    protected $stock;
    protected $ibada;

    public function __construct() {


        $this->stock = new Stock();
        $this->dikodiko = new Dikodiko();
        $this->ibada = new Ibada();
        if (date('H') == 8) {

            //*Run Morning sessions reminders
            $this->dikodiko->reminders();
            $this->dikodiko->sendAnnualAniversary();
        }

        if (date('H') == 18) {
            /**
             * Run Evening Sessions
             */
        }

        if (date('H') == 20) {
            /**
             * sub Evening Sessions
             */
            $this->dikodiko->dailyCollections();
        }
        if (date('H') == 22) {
            /**
             * Run end of day, session reports
             */
            $this->stock->sendProfitReport();
        }
  

        //return $this->stock->sendMessage('255714825469@c.us', 'Testing message with cron job with remote job '.date('H:i'));
    }

    public function curlPrivate($fields) {
        // Open connection
        $ch = curl_init();
// Set the url, number of POST vars, POST data

        curl_setopt($ch, CURLOPT_URL, $this->url . '?schema_name=' . $this->school);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'application/x-www-form-urlencoded'
        ));

        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
        // curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

}

new Sync();
