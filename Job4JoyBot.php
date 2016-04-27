<?php

class Job4JoyBot
{
    public static function requestToken($oauthId, $oauthSecret, $capabilitiesUrl)
    {
        $cap = json_decode(file_get_contents($capabilitiesUrl), true);

        return self::curl_request($oauthId, $oauthSecret, $cap['capabilities']['oauth2Provider']['tokenUrl'], [
            'grant_type' => 'client_credentials',
            'scope' => 'send_notification'
        ]);
    }

    public static function curl_request($client_id, $client_secret, $url, $post_data = null) {

        $headers = [
            'Content-Type: application/x-www-form-urlencoded',
            'Authorization: Basic '. base64_encode($client_id.":".$client_secret)
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);


        if (is_array($post_data)) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
        }
        $response = curl_exec($ch);

        curl_close($ch);

        return $response;
    }
}