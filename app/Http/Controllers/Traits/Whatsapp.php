<?php

namespace App\Http\Controllers\Traits;

use App\Model\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait Whatsapp
{
    public $token = 'GouFIbHRU0ikw7j297ZxAqNIR1iPwN3swy8dOqmH1abcaf36';
    public $baseUrl = 'https://waapi.app/api/v1/instances/';

    function __construct()
    {
        parent::__construct();
    }
    public function createInstance()
    {
        $pendingInstance = DB::table('shulesoft.whatsapp_instances')
        ->where('status', 0)
        ->where('schema_name', SCHEMA_NAME)->first();
        if (empty($pendingInstance)) {
            return redirect()->back()->with('error', 'You have a pending application. Please enable whatsapp integration.');
        }
        $ch = curl_init($this->baseUrl);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'accept: application/json',
            'authorization: Bearer GouFIbHRU0ikw7j297ZxAqNIR1iPwN3swy8dOqmH1abcaf36'
        ]);
        try {
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            curl_close($ch);

            if ($httpCode == 201) { //created
                $responseData = json_decode($response, true);

                // Insert into whatsapp_instance table
                $data = [
                    'instance_id' => $responseData['instance']['id'],
                    'owner' => $responseData['instance']['owner'],
                    'name' => $responseData['instance']['name'],
                    'updated_at' => now()
                ];
                DB::table('shulesoft.whatsapp_instances')->where('id', $pendingInstance->id)->update($data);
                $phone_number = str_replace('+', '', $pendingInstance->phone_number);
                $this->data['response'] = $data;
                $this->data["subview"] = "communication/smssettings/paircode";
                $this->load->view('_layout_main', $this->data);
                return $this->pairWhatsAppCode($responseData['instance']['id'], $phone_number);
            }
        } catch (\Exception $e) {
            Log::error('Error creating instance: ' . $e->getMessage());
            curl_close($ch);
            return redirect()->back()->with('error', 'Failed to request whatsapp instance');
        }
    }
    public function pairWhatsAppCode($instanceId, $phone_number)
    {
        $url = $this->baseUrl . $instanceId . '/client/action/request-pairing-code';

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'accept: application/json',
            'authorization: Bearer ' . $this->token,
            'content-type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'phoneNumber' => $phone_number
        ]));

        try {
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            curl_close($ch);

            if ($httpCode == 200) {


                $responseData = json_decode($response, true);
                // Update whatsapp_instance table
                DB::table('shulesoft.whatsapp_instances')
                    ->where('instance_id', $instanceId)
                    ->update([
                        'pairing_code' => $responseData['data']['pairingCode'],
                        'phone_number' => $phone_number,
                        'updated_at' => now()
                    ]);

                // return response()->json([
                //     'status' => 'success',
                //     'pairing_code' => $responseData['data']['data']['pairingCode']
                // ]);
                $this->data['pairing_code'] = $responseData['data']['pairingCode'];
                $this->data['instance_id'] = $instanceId;
                $this->data['phone_number'] = $phone_number;
                $this->data["subview"] = "communication/smssettings/paircode";
                $this->load->view('_layout_main', $this->data);
            }

            return response()->json(['status' => 'error'], 400);
        } catch (\Exception $e) {
            Log::error('Error getting pairing code: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

 

}
