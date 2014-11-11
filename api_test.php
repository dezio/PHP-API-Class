<?php

function buildApiUrl($command, $args = array()) {
    $param = "";
    foreach($args as $k => $v) {
        $param = "&".$k."=".$v;
    } // foreach end
    return "http://localhost/ticketing_api/api_prototype/api_prototype.php?command=" . $command . $param;
}

function getApiResponse($command, $args = array()) {
    $url = buildApiUrl($command, $args);
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_URL => $url
    ));
    $result = curl_exec($curl);
    echo $result;
    return json_decode($result);
}

function checkIfApiIs($command, $args, $expectedMessage, $verbose = true) {
    echo '<fieldset><legend>' . buildApiUrl($command, $args) . '</legend>';
    $result = getApiResponse($command, $args);
    echo '<div><pre>' . print_r($expectedMessage, 1) . '</pre></div>';
    echo '<div><pre>' . print_r($result->Message, 1) . '</pre></div>';
    echo '<div>' . ($expectedMessage == $result->Message ? "Ok" : "Error")  . '</div>';
    echo '</div>';
}

// Ping should return pong! as message
checkIfApiIs("ping", array(), "pong!", true);

?>