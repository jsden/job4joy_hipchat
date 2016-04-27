<?php

require_once(dirname(__FILE__) . '/vendor/autoload.php');
require_once (dirname(__FILE__) .'/GooglShortener.php');
require_once (dirname(__FILE__) .'/Job4JoyBot.php');

$config = require 'config.php';
$googl = new GooglShortener($config['google_token']);

use PicoFeed\Reader\Reader;
use GorkaLaucirica\HipchatAPIv2Client\Auth\OAuth2;
use GorkaLaucirica\HipchatAPIv2Client\Client;
use GorkaLaucirica\HipchatAPIv2Client\API\RoomAPI;
use GorkaLaucirica\HipchatAPIv2Client\Model\Message;

$data = json_decode(file_get_contents("php://input"), true);

if (!empty($data['item']))
{

    $token = require 'data/'.$data['oauth_client_id'].'_token.php';

    // Check Token expiration
    if(time() > filemtime('data/'.$data['oauth_client_id'].'_token.php')+ $token['expires_in'])
    {
        // request new token
        $groupConfig = require 'data/'.$data['oauth_client_id'].'.php';
        $token = Job4JoyBot::requestToken($groupConfig['oauthId'], $groupConfig['oauthSecret'], $groupConfig['capabilitiesUrl']);
        file_put_contents(__DIR__. '/../data/'.$groupConfig['oauthId'].'_token.php', "<?php\nreturn " . var_export(json_decode($token, true), true) . ";\n");
    }

    $auth = new OAuth2($token['access_token']);
    $client = new Client($auth);
    $roomAPI = new RoomAPI($client);

    if (!empty($config['feeds'][$data['item']['message']['message']]))
    {
        getFeed($config['feeds'][$data['item']['message']['message']], $data['item']['room']['id'], $roomAPI);
    }
    else
    {
        sendHelpMessage($data['item']['room']['id'], $roomAPI);
    }
}

function sendHelpMessage($room_id, $bot)
{
    global $config;

    $message = "Hello! I can help you with IT projects.<br />";

    foreach ($config['feeds'] as $alias => $item) {
        $message.= '<br /> <strong>'.$alias . "</strong> - ". $item['Title'];
    }

    $msgObj = new Message([
        'from' => '',
        'message' => $message,
        'color' => 'green',
        'messageFormat' => 'html'
    ]);
    $msgObj->setNotify(true);

    return $bot->sendRoomNotification($room_id, $msgObj);
}

function getFeed($feed, $room_id, $bot)
{
    global $googl;
    try {
        $reader = new Reader;
        $resource = $reader->download($feed['Feed']);
        $parser = $reader->getParser(
            $resource->getUrl(),
            $resource->getContent(),
            $resource->getEncoding()
        );
        $feed = $parser->execute();
        $items = array_reverse($feed->getItems());
        if (count($items)) {
            foreach ($items as $itm)
            {
                $url = $googl->shorten($itm->getUrl());
                $message = substr(strip_tags($itm->getContent()), 0, 150);

                $msgObj = new Message([
                    'from' => '',
                    'message' => '<a href="'.$url->id.'"><strong>'.$itm->getTitle() . '</strong></a><br />'.$message.'<br /><a href="'.$url->id.'">'.$url->id.'</a>',
                    'color' => 'green',
                    'messageFormat' => 'html',
                ]);
                $msgObj->setNotify(true);

                $bot->sendRoomNotification($room_id, $msgObj);
            }
        } else {

            $msgObj = new Message([
                'from' => '',
                'message' => 'New projects not a found!',
                'color' => 'red',
                'messageFormat' => 'html',
            ]);
            $msgObj->setNotify(true);

            $bot->sendRoomNotification($room_id, $msgObj);
        }
    }
    catch (Exception $e) {
        writeToLog($e->getErrors(), "EXCEPTION");
    }

    return true;
}

function writeToLog($data, $title = '')
{
    $log = "\n------------------------\n";
    $log .= date("Y.m.d G:i:s") . "\n";
    $log .= (strlen($title) > 0 ? $title : 'DEBUG') . "\n";
    $log .= print_r($data, 1);
    $log .= "\n------------------------\n";
    file_put_contents(__DIR__ . '/imbot.log', $log, FILE_APPEND);
    return true;
}