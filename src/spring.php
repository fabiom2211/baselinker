<?php

class SpringCourier
{
    const API_URL = "https://mtapi.net/?testMode=1";

    /**
     * Create a new shipment
     *
     * @param array $order Associative array that contains the sender and recipient data
     * @param array $params Associative array that contains additional data
     *  to create the new shipment (API key, service...)
     *
     * @return array Associative array containing the data returned
     * from the API or a connection error
     */
    public function newPackage(array $order, array $params, array $product): array
    {
        $data = [
            "Apikey" => $params["apiKey"],
            'Command' => 'OrderShipment',
            'Shipment' => [
                'LabelFormat' => $params["labelFormat"],
                'ShipperReference' => $params["shipperReference"],
                'Service' => $params["service"],
                "ConsignorAddress" => $order["sender"],
                "ConsigneeAddress" => $order["recipient"],
                'Products' => $product
            ],
        ];

        $response = $this->curlExec($data);

        if ($response["ErrorLevel"] != 0) {
            return [
                "error" => true,
                "message" => "Error creating shipment: {$response["Error"]}"
            ];
        }

        if (empty($response["Shipment"]["TrackingNumber"])) {
            return [
                "error" => true,
                "message" => "Missing tracking number from response"
            ];
        }

        return [
            "success" => true,
            "trackingNumber" => $response["Shipment"]["TrackingNumber"]
        ];
    }

    /**
     * Get a shipping label
     *
     * @param string $trackingNumber Shipping tracking number created by the newPackage function
     *
     * @return array Associative array containing the data returned
     * from the API or a connection error
     */
    public function packagePDF(string $trackingNumber, string $apiKey = "f16753b55cac6c6e"): array
    {
        $data = [
            "Apikey" => $apiKey,
            "Command" => "GetShipmentLabel",
            "Shipment" => [
                "LabelFormat" => "PDF",
                "TrackingNumber" => $trackingNumber
            ]
        ];

        $response = $this->curlExec($data);

        if ($response["ErrorLevel"] != 0) {
            return [
                "error" => true,
                "message" => "Error getting label: {$response["Error"]}"
            ];
        }

        if (empty($response["Shipment"]["LabelImage"])) {
            return [
                "error" => true,
                "message" => "Missing label from response"
            ];
        }

        return [
            "success" => true,
            "labelImage" => $response["Shipment"]["LabelImage"],
            "trackingNumber" => $response["Shipment"]["TrackingNumber"] ?? ""
        ];
    }

    /**
     * Execute a connection to the API using curl
     *
     * @param array $data Associative array containing the data that will be sent to the API
     *
     * @return array Associative array containing the API response or connection error
     */
    public function curlExec(array $data): array
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => self::API_URL,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_SSL_VERIFYPEER => $this->isHttps(),
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                "Content-Type: text/json",
            ],
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);

        curl_close($ch);

        if (!empty($error)) {
            return [
                "ErrorLevel" => 2,
                "Error" => "Connection error: {$error}"
            ];
        }

        return json_decode($response, true);
    }

    /**
     * Checks if the connection is https or https
     *
     * @return bool true for https connection or false for http connection
     */
    private function isHttps(): bool
    {
        if (
            $_SERVER["SERVER_NAME"] === ("localhost" || "127.0.0.1" || "0.0.0.0")
            || $_SERVER["SERVER_PORT"] !== "443"
        ) {
            return false;
        }

        return true;
    }
}