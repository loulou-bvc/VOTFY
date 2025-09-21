<?php

class CallApi{
    public static function callApi($endpoint, $method = 'POST', $data = null) {
        $url = "https://webdev.iut-orsay.fr/~nboulad/VOTFY/api/$endpoint";
    
        $options = [
            'http' => [
                'header' => "Content-Type: application/json\r\n",
                'method' => $method,
            ],
        ];
    
        if ($data) {
            $options['http']['content'] = json_encode($data);
        }
    
        $context = stream_context_create($options);
        $response = @file_get_contents($url, false, $context);
    
        if ($response === false) {
            $error = error_get_last();
            return ['status' => 'error', 'message' => 'Erreur API : ' . $error['message']];
        }
    
        return json_decode($response, true); // Vérifiez que $response est bien un JSON pur
    }
    
}