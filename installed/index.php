<?php

require_once (dirname(__FILE__) .'/../Job4JoyBot.php');

$data = json_decode(file_get_contents("php://input"), true);

if (!empty($data)) {
    file_put_contents(__DIR__. '/../data/'.$data['oauthId'].'.php', "<?php\nreturn " . var_export($data, true) . ";\n");
    $token = Job4JoyBot::requestToken($data['oauthId'], $data['oauthSecret'], $data['capabilitiesUrl']);
    file_put_contents(__DIR__. '/../data/'.$data['oauthId'].'_token.php', "<?php\nreturn " . var_export($token, true) . ";\n");
}