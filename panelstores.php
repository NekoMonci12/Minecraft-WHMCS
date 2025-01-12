<?php

/**
MIT License

Copyright (c) 2018-2019 Stepan Fedotov <stepan@crident.com>

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
**/

if(!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

use Illuminate\Database\Capsule\Manager as Capsule;

function panelstores_GetHostname(array $params) {
    $hostname = $params['serverhostname'];
    if ($hostname === '') throw new Exception('Could not find the panel\'s hostname - did you configure server group for the product?');

    // For whatever reason, WHMCS converts some characters of the hostname to their literal meanings (- => dash, etc) in some cases
    foreach([
        'DOT' => '.',
        'DASH' => '-',
    ] as $from => $to) {
        $hostname = str_replace($from, $to, $hostname);
    }

    if(ip2long($hostname) !== false) $hostname = 'http://' . $hostname;
    else $hostname = ($params['serversecure'] ? 'https://' : 'http://') . $hostname;

    return rtrim($hostname, '/');
}

function panelstores_API(array $params, $endpoint, array $data = [], $method = "GET", $dontLog = false) {
    $url = panelstores_GetHostname($params) . '/api/client/' . $endpoint;

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($curl, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);
    curl_setopt($curl, CURLOPT_USERAGENT, "Panel-Stores");
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($curl, CURLOPT_POSTREDIR, CURL_REDIR_POST_301);
    curl_setopt($curl, CURLOPT_TIMEOUT, 5);

    $headers = [
        "Authorization: Bearer " . $params['serverpassword'],
        "Accept: application/json",
    ];

    if($method === 'POST' || $method === 'PATCH') {
        $jsonData = json_encode($data);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $jsonData);
        array_push($headers, "Content-Type: application/json");
        array_push($headers, "Content-Length: " . strlen($jsonData));
    }

    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($curl);
    $responseData = json_decode($response, true);
    $responseData['status_code'] = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    
    if($responseData['status_code'] === 0 && !$dontLog) logModuleCall("Panel-Stores", "CURL ERROR", curl_error($curl), "");

    curl_close($curl);

    if(!$dontLog) logModuleCall("Panel-Stores", $method . " - " . $url,
        isset($data) ? json_encode($data) : "",
        print_r($responseData, true));

    return $responseData;
}

function panelstores_Error($func, $params, Exception $err) {
    logModuleCall("Panel-Stores", $func, $params, $err->getMessage(), $err->getTraceAsString());
}

function panelstores_MetaData() {
    return [
        "DisplayName" => "Panel Stores",
        "APIVersion" => "1.1",
        "RequiresServer" => true,
    ];
}

function panelstores_ConfigOptions() {
    return [
        "username" => [
            "FriendlyName" => "Clients Username",
            "Description" => "Username Of Buyers.",
            "Type" => "text",
            "Size" => 25,
        ],
        "server_id" => [
            "FriendlyName" => "Panel ServerID (Pterodactyl/Pelican)",
            "Description" => "ServerID To Commands Be Executed.",
            "Type" => "text",
            "Size" => 10,
        ],
        "commands" => [
            "FriendlyName" => "Executed Commands",
            "Description" => "Commands To Be Executed.",
            "Type" => "text",
            "Size" => 25,
        ],
        "var_1" => [
            "FriendlyName" => "Variables-1",
            "Description" => "(optional)",
            "Type" => "text",
            "Size" => 10,
        ],
        "terminate_commands" => [
            "FriendlyName" => "Terminate Commands (optional)",
            "Description" => "When The Services Is Terminated.",
            "Type" => "text",
            "Size" => 25,
        ],
        "var_2" => [
            "FriendlyName" => "Variables-2",
            "Description" => "(optional)",
            "Type" => "text",
            "Size" => 10,
        ],
        "revert_commands" => [
            "FriendlyName" => "Revert Commands",
            "Description" => "Revert Actions Of Commands.",
            "Type" => "text",
            "Size" => 25,
        ],
        "var_3" => [
            "FriendlyName" => "Variables-3",
            "Description" => "(optional)",
            "Type" => "text",
            "Size" => 10,
        ],
    ];
}

function panelstores_GetOption(array $params, $id, $default = NULL) {
    $options = panelstores_ConfigOptions();

    $friendlyName = $options[$id]['FriendlyName'];
    if(isset($params['configoptions'][$friendlyName]) && $params['configoptions'][$friendlyName] !== '') {
        return $params['configoptions'][$friendlyName];
    } else if(isset($params['configoptions'][$id]) && $params['configoptions'][$id] !== '') {
        return $params['configoptions'][$id];
    } else if(isset($params['customfields'][$friendlyName]) && $params['customfields'][$friendlyName] !== '') {
        return $params['customfields'][$friendlyName];
    } else if(isset($params['customfields'][$id]) && $params['customfields'][$id] !== '') {
        return $params['customfields'][$id];
    }

    $found = false;
    $i = 0;
    foreach(panelstores_ConfigOptions() as $key => $value) {
        $i++;
        if($key === $id) {
            $found = true;
            break;
        }
    }

    if($found && isset($params['configoption' . $i]) && $params['configoption' . $i] !== '') {
        return $params['configoption' . $i];
    }

    return $default;
}

function panelstores_TestConnection(array $params) {
    $solutions = [
        0 => "Check module debug log for more detailed error.",
        401 => "Authorization header either missing or not provided.",
        403 => "Double check the password (which should be the Application Key).",
        404 => "Result not found.",
        422 => "Validation error.",
        500 => "Panel errored, check panel logs.",
    ];

    $err = "";
    try {
        $response = panelstores_API($params, 'account');

        if($response['status_code'] !== 200) {
            $status_code = $response['status_code'];
            $err = "Invalid status_code received: " . $status_code . ". Possible solutions: "
                . (isset($solutions[$status_code]) ? $solutions[$status_code] : "None.");
        } else {
            if($response['meta']['pagination']['count'] === 0) {
                $err = "Authentication successful, but no nodes are available.";
            }
        }
    } catch(Exception $e) {
        panelstores_Error(__FUNCTION__, $params, $e);
        $err = $e->getMessage();
    }

    return [
        "success" => $err === "",
        "error" => $err,
    ];
}

function panelstores_CreateAccount(array $params)
{
    try {
        $serverId = panelstores_GetOption($params, 'server_id');
        $command = panelstores_GetOption($params, 'commands');
        $endpoint = "servers/" . $serverId . "/command";
        $var_1 = panelstores_GetOption($params, 'var_1', '');
        $var_2 = panelstores_GetOption($params, 'var_2', '');
        $var_3 = panelstores_GetOption($params, 'var_3', '');
        $username = panelstores_GetOption($params, 'username', '');
        $command = str_replace(
            ['[var-1]', '[var-2]', '[var-3]', '[username]'],
            [$var_1, $var_2, $var_3, $username],
            $command
        );

        $data = [
            "command" => $command,
        ];
        $response = panelstores_API($params, $endpoint, $data, "POST");

        if ($response['status_code'] !== 204) {
            throw new Exception("Failed to execute command. Status code: {$response['status_code']}");
        }
    } catch (Exception $e) {
        return $e->getMessage();
    }

    return 'success';
}

function panelstores_SuspendAccount(array $params)
{
    try {
        $serverId = panelstores_GetOption($params, 'server_id');
        $command = panelstores_GetOption($params, 'revert_commands');
        $endpoint = "servers/{$serverId}/command";
        $var_1 = panelstores_GetOption($params, 'var_1', '');
        $var_2 = panelstores_GetOption($params, 'var_2', '');
        $var_3 = panelstores_GetOption($params, 'var_3', '');
        $username = panelstores_GetOption($params, 'username', '');

        $command = str_replace(
            ['[var-1]', '[var-2]', '[var-3]', '[username]'],
            [$var_1, $var_2, $var_3, $username],
            $command
        );

        $data = [
            "command" => $command,
        ];
        $response = panelstores_API($params, $endpoint, $data, "POST");
        
        if ($response['status_code'] !== 204) {
            throw new Exception("Failed to execute command. Status code: {$response['status_code']}");
        }
    } catch (Exception $e) {
        return $e->getMessage();
    }

    return 'success';
}

function panelstores_UnsuspendAccount(array $params)
{
    try {
        $serverId = panelstores_GetOption($params, 'server_id');
        $command = panelstores_GetOption($params, 'commands');
        $endpoint = "servers/{$serverId}/command";
        $var_1 = panelstores_GetOption($params, 'var_1', '');
        $var_2 = panelstores_GetOption($params, 'var_2', '');
        $var_3 = panelstores_GetOption($params, 'var_3', '');
        $username = panelstores_GetOption($params, 'username', '');

        $command = str_replace(
            ['[var-1]', '[var-2]', '[var-3]', '[username]'],
            [$var_1, $var_2, $var_3, $username],
            $command
        );

        $data = [
            "command" => $command,
        ];
        $response = panelstores_API($params, $endpoint, $data, "POST");
        
        if ($response['status_code'] !== 204) {
            throw new Exception("Failed to execute command. Status code: {$response['status_code']}");
        }
    } catch (Exception $e) {
        return $e->getMessage();
    }

    return 'success';
}

function panelstores_TerminateAccount(array $params)
{
    try {
        $serverId = panelstores_GetOption($params, 'server_id');
        $command = panelstores_GetOption($params, 'terminate_commands');
        $endpoint = "servers/{$serverId}/command";
        $var_1 = panelstores_GetOption($params, 'var_1', '');
        $var_2 = panelstores_GetOption($params, 'var_2', '');
        $var_3 = panelstores_GetOption($params, 'var_3', '');
        $username = panelstores_GetOption($params, 'username', '');

        $command = str_replace(
            ['[var-1]', '[var-2]', '[var-3]', '[username]'],
            [$var_1, $var_2, $var_3, $username],
            $command
        );

        $data = [
            "command" => $command,
        ];
        $response = panelstores_API($params, $endpoint, $data, "POST");
        
        if ($response['status_code'] !== 204) {
            throw new Exception("Failed to execute command. Status code: {$response['status_code']}");
        }
    } catch (Exception $e) {
        return $e->getMessage();
    }

    return 'success';
}

function panelstores_ChangePassword(array $params)
{
    try {
        if($params['password'] === '') throw new Exception('The password cannot be empty.');
    } catch (Exception $e) {
        return $e->getMessage();
    }

    return 'success';
}

function panelstores_ChangePackage(array $params)
{
    try {
        $serverId = panelstores_GetOption($params, 'server_id');
        $command = panelstores_GetOption($params, 'commands');
        $revert = panelstores_GetOption($params, 'revert_commands');
        $endpoint = "servers/{$serverId}/command";
        $var_1 = panelstores_GetOption($params, 'var_1', '');
        $var_2 = panelstores_GetOption($params, 'var_2', '');
        $var_3 = panelstores_GetOption($params, 'var_3', '');
        $username = panelstores_GetOption($params, 'username', '');

        $revert = str_replace(
            ['[var-1]', '[var-2]', '[var-3]', '[username]'],
            [$var_1, $var_2, $var_3, $username],
            $revert
        );

        $command = str_replace(
            ['[var-1]', '[var-2]', '[var-3]', '[username]'],
            [$var_1, $var_2, $var_3, $username],
            $command
        );

        $revert_data = [
            "command" => $revert,
        ];
        $data = [
            "command" => $command,
        ];

        $revert_response = panelstores_API($params, $endpoint, $revert_data, "POST");
        if ($revert_response['status_code'] !== 204) {
            throw new Exception("Failed to execute command. Status code: {$revert_response['status_code']}");
        }

        $response = panelstores_API($params, $endpoint, $data, "POST");
        if ($response['status_code'] !== 204) {
            throw new Exception("Failed to execute command. Status code: {$response['status_code']}");
        }
    } catch (Exception $e) {
        return $e->getMessage();
    }

    return 'success';
}

function panelstores_Renew(array $params)
{
    try {
        $serverId = panelstores_GetOption($params, 'server_id');
        $command = panelstores_GetOption($params, 'commands');
        $endpoint = "servers/{$serverId}/command";
        $var_1 = panelstores_GetOption($params, 'var_1', '');
        $var_2 = panelstores_GetOption($params, 'var_2', '');
        $var_3 = panelstores_GetOption($params, 'var_3', '');
        $username = panelstores_GetOption($params, 'username', '');

        $command = str_replace(
            ['[var-1]', '[var-2]', '[var-3]', '[username]'],
            [$var_1, $var_2, $var_3, $username],
            $command
        );

        $data = [
            "command" => $command,
        ];
        $response = panelstores_API($params, $endpoint, $data, "POST");

        if ($response['status_code'] !== 204) {
            throw new Exception("Failed to execute command. Status code: {$response['status_code']}");
        }
    } catch (Exception $e) {
        return $e->getMessage();
    }

    return 'success';
}