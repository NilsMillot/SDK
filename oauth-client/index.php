<?php
include 'vendor/autoload.php';

use GuzzleHttp\Client;

const CLIENT_ID = "client_60a3778e70ef02.05413444";
const CLIENT_FBID = "153042750126859";
const CLIENT_GTID = "cd08eb1a4191742c3488";
const CLIENT_SECRET = "cd989e9a4b572963e23fe39dc14c22bbceda0e60";
const CLIENT_FBSECRET = "cfd0d0117ba19c789c711b1f0afaf3c4";
const CLIENT_GTSECRET = "72c05c3dd00e1a8cbba435a330228a617e008eb9";

// const CLIENT_FBID = "3648086378647793";
const CLIENT_GOOGLEID= "666723567104-k4aguknbo73rlr7b12gnnin4791ssn5t.apps.googleusercontent.com";
// const CLIENT_FBSECRET = "1b5d764e7a527c2b816259f575a59942";
const CLIENT_GOOGLESECRET = "b9BzjhKAYYmHnqA18L2RI11U";
const STATE = "fdzefzefze";

function handleLogin()
{
    // http://.../auth?response_type=code&client_id=...&scope=...&state=...
    echo "<h1>Login with OAUTH</h1>";
    echo "<a href='http://localhost:8081/auth?response_type=code"
        . "&client_id=" . CLIENT_ID
        . "&scope=basic"
        . "&state=" . STATE . "'>Se connecter avec Oauth Server</a>";
    echo "<br><br>";
    echo "<a href='https://www.facebook.com/v2.10/dialog/oauth?response_type=code"
        . "&client_id=" . CLIENT_FBID
        . "&scope=email"
        . "&state=" . STATE
        . "&redirect_uri=http://localhost:8082/fbauth-success"
        . "&sdk=php-sdk-6.0-dev'>Se connecter avec Facebook</a>";
    echo "<a href='https://github.com/login/oauth/authorize?response_type=code"
        . "&client_id=". CLIENT_GTID
        . "&scope=user"
        . "&state=". STATE
        . "&redirect_uri=http://localhost:8082/gtauth-success"
        . "'> Se connecter avec GitHub </a>";
    echo "<br><br>";
    echo "<a href='https://accounts.google.com/o/oauth2/v2/auth?response_type=code"
    . "&access_type=online"
    . "&client_id=" . CLIENT_GOOGLEID
    . "&scope=email"
    . "&state=" . STATE
    . "&redirect_uri=https://localhost:8082/googleauth-success'>Se connecter avec Google</a>";
}

function handleError()
{
    ["state" => $state] = $_GET;
    echo "{$state} : Request cancelled";
}

function handleSuccess()
{
    ["state" => $state, "code" => $code] = $_GET;
    if ($state !== STATE) {
        throw new RuntimeException("{$state} : invalid state");
    }
    getUser([
        'grant_type' => "authorization_code",
        "code" => $code,
    ]);
}

function handleFbSuccess()
{
    ["state" => $state, "code" => $code] = $_GET;
    if ($state !== STATE) throw new RuntimeException("{$state} : invalid state");
    $url = "https://graph.facebook.com/oauth/access_token?grant_type=authorization_code&code={$code}&client_id=" . CLIENT_FBID . "&client_secret=" . CLIENT_FBSECRET."&redirect_uri=http://localhost:8082/fbauth-success";
    $result = file_get_contents($url);
    $resultDecoded = json_decode($result, true);
    ["access_token"=> $token] = $resultDecoded;
    $userUrl = "https://graph.facebook.com/me?fields=id,name,email";
    $curl = curl_init($userUrl);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_2) AppleWebKit/602.3.12 (KHTML, like Gecko) Version/10.0.2 Safari/602.3.12");
    curl_setopt($curl, CURLOPT_HTTPHEADER,["Authorization: Bearer {$token}"]);
    curl_setopt($curl,CURLOPT_HEADER,0);
    $result = curl_exec($curl);
    echo $result;
//   echo file_get_contents($userUrl, false, $context);
}

function handleGtSuccess()
{
    ["state" => $state, "code" => $code] = $_GET;
    if ($state !== STATE) throw new RuntimeException("{$state} : invalid state");
    $url = "https://github.com/login/oauth/access_token?grant_type=authorization_code&code={$code}&client_id=" . CLIENT_GTID . "&client_secret=" . CLIENT_GTSECRET . "&redirect_uri=http://localhost:8082/gtauth-success";
    $result = file_get_contents($url);
    $string = explode("&", $result, 2)[0];
    $token = explode("=", $string)[1];
    $userUrl = "https://api.github.com/user";
    $curl = curl_init($userUrl);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_2) AppleWebKit/602.3.12 (KHTML, like Gecko) Version/10.0.2 Safari/602.3.12");
    curl_setopt($curl, CURLOPT_HTTPHEADER,["Authorization: Bearer {$token}"]);
    curl_setopt($curl,CURLOPT_HEADER,0);
    $result = curl_exec($curl);
    echo $result;
//    echo file_get_contents($userUrl,false ,$context);

}
function handleGoogleSuccess()
{
    $client = new Client([
        'timeout' => 2.0
    ]);
    var_dump($client);
    try {
        $response = $client->request('POST', 'https://oauth2.googleapis.com/token', [
            'form_params' => [
                'code' => $_GET['code'],
                'client_id' => CLIENT_GOOGLEID,
                'client_secret' => CLIENT_GOOGLESECRET,
                'redirect_uri' => 'https://localhost:8082/googleauth-success',
                'grant_type' => 'authorization_code'
            ]
        ]);
        echo json_decode($response->getBody());
    } catch (\GuzzleHttp\Exception\ClientException $exception) {
        var_dump($exception->getMessage());
        die();
    }

    ["state" => $state, "code" => $code] = $_GET;
    if ($state !== STATE) {
        throw new RuntimeException("{$state} : invalid state");
    }
    $url = "https://oauth2.googleapis.com/token?grant_type=authorization_code&code={$code}&client_id=" . CLIENT_GOOGLEID . "&client_secret=" . CLIENT_GOOGLESECRET . "&redirect_uri=https://localhost:8082/googleauth-success";
    $result = file_get_contents($url);
    var_dump($result);
    $resultDecoded = json_decode($result, true);
    ["access_token"=> $token] = $resultDecoded;
    $userUrl = "https://openidconnect.googleapis.com/v1/userinfo?fields=name,email";
    $context = stream_context_create([
        'http' => [
            'header' => 'Authorization: Bearer ' . $token
        ]
    ]);
    // echo file_get_contents($userUrl, false, $context);
}

function getUser($params)
{
    $url = "http://oauth-server:8081/token?client_id=" . CLIENT_ID . "&client_secret=" . CLIENT_SECRET . "&" . http_build_query($params);
    $result = file_get_contents($url);
    $result = json_decode($result, true);
    $token = $result['access_token'];

    $apiUrl = "http://oauth-server:8081/me";
    $context = stream_context_create([
        'http' => [
            'header' => 'Authorization: Bearer ' . $token
        ]
    ]);
    echo file_get_contents($apiUrl, false, $context);
}

/**
 * AUTH CODE WORKFLOW
 * => Generate link (/login)
 * => Get Code (/auth-success)
 * => Exchange Code <> Token (/auth-success)
 * => Exchange Token <> User info (/auth-success)
 */
$route = strtok($_SERVER["REQUEST_URI"], "?");
switch ($route) {
    case '/login':
        handleLogin();
        break;
    case '/auth-success':
        handleSuccess();
        break;
    case '/fbauth-success':
        handleFbSuccess();
        break;
    case '/gtauth-success':
        handleGtSuccess();
    case '/googleauth-success':
        handleGoogleSuccess();
        echo "connecté via google";
        break;
    case '/auth-cancel':
        handleError();
        break;
    case '/password':
        if ($_SERVER['REQUEST_METHOD'] === "GET") {
            echo '<form method="POST">';
            echo '<input name="username">';
            echo '<input name="password">';
            echo '<input type="submit" value="Submit">';
            echo '</form>';
        } else {
            ["username" => $username, "password" => $password] = $_POST;
            getUser([
                'grant_type' => "password",
                "username" => $username,
                "password" => $password
            ]);
        }
        break;
    default:
        http_response_code(404);
        break;
}
