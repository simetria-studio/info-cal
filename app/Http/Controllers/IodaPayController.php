<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use App\Models\Transaction;
use Illuminate\Http\Request;

class IodaPayController extends Controller
{
    public function createPayment($secret, $public, $email, $fiscalId, $fullName, $fiscalType, $value, $expiration, $referenceId)
    {
        $client = new Client();

        $payload = [
            'payer' => [
                'email' => $email,
                'fiscalId' => $fiscalId,
                'fullName' => $fullName,
                'fiscalType' => $fiscalType
            ],
            'value' => $value,
            'expiration' => $expiration,
            'referenceId' => $referenceId
        ];


        $response = $client->post('https://api-h.iodapay.com.br/payments/v0/pix/simplified', [
            'headers' => [
                'x-public-key' => $public,
                'x-secret-key' => $secret,
                'Content-Type' => 'application/json',
            ],
            'json' => $payload
        ]);

        if ($response->getStatusCode() == 201) {

            return $response->getBody();
        } else {
            \Log::error('API Response Error:', [
                'status' => $response->getStatusCode(),
                'reason' => $response->getReasonPhrase(),
                'body' => $response->getBody()->getContents()
            ]);

            return response()->json([
                'error' => 'Unexpected HTTP status: ' . $response->getStatusCode() . ' ' . $response->getReasonPhrase()
            ], $response->getStatusCode());
        }
    }
    public function callbackNotify(Request $request)
    {
        $data = $request->all();

        \Log::info('Callback Notify:', $data);

        // if ($data['status'] == 'PAID') {
        //     $transaction = Transaction::where('transaction_id', $data['transactionId'])->first();

        //     if ($transaction) {
        //         $transaction->status_pay = 'PAID';
        //         $transaction->save();
        //     }
        // }

        return response()->json(['status' => 'OK']);
    }
}
