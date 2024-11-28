<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class SMS
{
    protected $client;
    protected $baseUrl;
    protected $apiKey;

    public function __construct()
    {
        $this->client = new Client();
        $this->baseUrl = env('INFOBIP_BASE_URL');   
        $this->apiKey = env('INFOBIP_API_KEY');
    }

    public function send($phone1, $message)
    {
        try {
            $response = $this->client->post("{$this->baseUrl}/sms/2/text/advanced", [
                'json' => [
                    'messages' => [
                        [
                            'to' => $phone1,
                            'from' => env('INFOBIP_FROM'),
                            'text' => $message
                        ]
                    ]
                ],
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'App ' . $this->apiKey,
                    'Accept' => 'application/json'
                    
                ]
            ]);

            $body = json_decode($response->getBody(), true);
            Log::info('Infobip Response Body: ' . json_encode($body));

            if ($response->getStatusCode() !== 200 || empty($body['messages'][0]['status']['groupName']) || $body['messages'][0]['status']['groupName'] != 'SUCCESS') {
                Log::error("SMS sending failed to {$phone1}: " . json_encode($body));
                return [
                    'success' => false,
                    'message' => 'SMS sending failed: ' . json_encode($body)
                ];
            }

            return [
                'success' => true,
                'message' => 'SMS sent successfully'
            ];

        } catch (\Exception $e) {
            Log::error("Failed to send SMS to {$phone1}: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to send SMS: ' . $e->getMessage()
            ];
        }
    }
}
