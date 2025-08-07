<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class Stock extends Database {

    public function __construct() {
        parent::__construct(get_class());
    }

    public function sendProfitReport() {

        $this->reset('3737687_inventory', '3737687_inventory', 'cE6Bvk9l7^tV-IQB');
        $clients = $this->select('SELECT * FROM `clients`');
        foreach ($clients as $client_) {
            $client = (object) $client_;
            $this->reset('3737687_' . $client->system_dbname, '3737687_' . $client->system_dbname, $client->system_password);
            $expenses_info = $this->select('SELECT sum(amount) as amount FROM `sma_expenses` WHERE date(date)=CURRENT_DATE');
            $expenses = (object) array_shift($expenses_info);

            //$payment = \collect(DB::connection('stock')->select('SELECT SUM(amount) as amount FROM `sma_payments` WHERE date(date)=CURRENT_DATE'))->first();
            $sales_info = $this->select('SELECT SUM(total) as amount FROM `sma_sales` WHERE date(date)=CURRENT_DATE');
            $sales = (object) array_shift($sales_info);




            //$message_en = 'Hello ' . chr(10) . 'Todays report : ' . date('d M Y h:i') . chr(10) . 'Total Sales : ' . number_format($sales->amount) . ''
            // . chr(10) .
            //'Total Expenses : ' . number_format($expenses->amount) . chr(10) . '. For more information, kindly open your online account';
            $users = $this->select('select * from sma_users where group_id=1');
            foreach ($users as $user_) {
                $user = (object) $user_;

                $message_kw = 'Habari ' . $user->first_name . chr(10) . chr(10) . 'Repoti ya leo : *' . date('d M Y h:i') . chr(10) . '* katika *' . strtoupper($client->username) . ' PHARMACY* ' . chr(10) . chr(10) . ''
                        . 'Jumla ya Mauzo : ' . number_format($sales->amount) . ''  . chr(10)
                        . 'Jumla ya matumizi : ' . number_format($expenses->amount) . chr(10) . 'Kwa taarifa kamili, ingia kwenye account yako online' . chr(10) . '_Hii repoti '
                        . ' imetengenezwa automatically kutoka kwenye system ya stock management, na hutumwa kila siku_';

                $chat_id = '255' . ltrim(trim($user->phone), 0) . '@c.us';
                // $this->sendMessage($chat_id, $message_en);
                $this->sendMessage($chat_id, $message_kw);
            }
        }
    }

}
