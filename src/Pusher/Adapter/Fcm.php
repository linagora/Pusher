<?php
/**
 * Created by PhpStorm.
 * User: yuriy
 * Date: 3/17/17
 * Time: 1:55 PM
 */

namespace Pusher\Adapter;


use Pusher\Collection\DeviceCollection;
use Pusher\Exception\AdapterException;
use Pusher\Model\MessageInterface;

class Fcm implements AdapterInterface
{

    const API_URL = 'https://fcm.googleapis.com/fcm/send';

    protected $serverKey;
    protected $environment;
    protected $invalidTokens = [];

    public function __construct(string $serverKey, int $environment = AdapterInterface::ENVIRONMENT_DEVELOPMENT)
    {
        $this->serverKey = $serverKey;
        $this->environment = $environment;
    }

    public function push(DeviceCollection $devices, $message, $_data = Array())
    {
        $tokens = $devices->getTokens();

        $data = [
            'data' => Array("notification_data"=>$_data),
            'notification' => $message,
            'registration_ids' => $tokens,
        ];


        $ch = curl_init(self::API_URL);
        curl_setopt_array($ch, [
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: key=' . $this->serverKey,
            ],
            CURLOPT_POSTFIELDS => json_encode($data),
        ]);

        error_log($response);

        if (!$response = curl_exec($ch)) {
            throw new AdapterException('invalid response', AdapterException::INVALID_RESPONSE);
        }

        if (!$response = json_decode($response, true)) {
            throw new AdapterException('invalid response json', AdapterException::INVALID_RESPONSE_JSON);
        }

        foreach ($response['results'] as $k => $result) {
            if (!empty($result['error'])) {
                $this->invalidTokens[] = $tokens[$k];
            }
        }
    }

    public function getFeedback():array
    {
        $result = $this->invalidTokens;
        $this->invalidTokens = [];
        return $result;
    }
}
