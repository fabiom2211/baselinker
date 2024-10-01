<?php

declare(strict_types=1);

header('Content-Type: application/json');

// Include the spring.php file
require_once 'spring.php';

// Function to sanitize text inputs
function sanitizeInput(string $data): string
{
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

if (isset($_GET['trackingNumber'])) {
    $trackingNumber = sanitizeInput($_GET['trackingNumber']);

    if (empty($trackingNumber)) {
        echo json_encode([
            'success' => false,
            'errors' => ['message' => 'Tracking number is required.'],
        ]);
        exit;
    }

    // Instantiate Spring class and call packagePDF to get the label
    $springCourier = new SpringCourier();
    $labelResponse = $springCourier->packagePDF($trackingNumber);

    if (isset($packageResponse['error'])) {
        $response = [
            'success' => false,
            'errors' => ['message' => $packageResponse['message']],
        ];
    } elseif (isset($labelResponse['ErrorLevel']) && $labelResponse['ErrorLevel'] > 0) {
        // Return error if unable to generate label
        echo json_encode([
            'success' => false,
            'errors' => ['message' => $labelResponse['Error']],
        ]);
    } else {
        // Success case, return the label in base64
        echo json_encode([
            'success' => true,
            'label' => $labelResponse['labelImage'] ?? null,
        ]);
    }

    exit;
}


// Initialize an array to store errors
$errors = [];

// Validation for Sender Information
$senderName = sanitizeInput($_POST['sender_name'] ?? '');
if (empty($senderName)) {
    $errors['sender_name'] = 'Sender name is required.';
}

$senderCompany = sanitizeInput($_POST['sender_company'] ?? '');
if (empty($senderCompany)) {
    $errors['sender_company'] = 'Sender company is required.';
}

// Validation for Recipient Information
$recipientName = sanitizeInput($_POST['recipient_name'] ?? '');
if (empty($recipientName)) {
    $errors['recipient_name'] = 'Recipient name is required.';
}

$recipientCompany = sanitizeInput($_POST['recipient_company'] ?? '');

$recipientAddressLine1 = sanitizeInput($_POST['recipient_addressLine1'] ?? '');
if (empty($recipientAddressLine1)) {
    $errors['recipient_addressLine1'] = 'Recipient address line 1 is required.';
}

$recipientAddressLine2 = sanitizeInput($_POST['recipient_addressLine2'] ?? '');

$recipientCity = sanitizeInput($_POST['recipient_city'] ?? '');
if (empty($recipientCity)) {
    $errors['recipient_city'] = 'Recipient city is required.';
}

$recipientState = sanitizeInput($_POST['recipient_state'] ?? '');
$recipientCountry = sanitizeInput($_POST['recipient_country'] ?? '');

if (in_array($recipientCountry, ['US', 'CA', 'AU', 'BR'], true)) {
    if (empty($recipientState)) {
        $errors['recipient_state'] = 'Recipient state is required for USA, Canada, and Australia.';
    } elseif (!preg_match('/^[A-Z]{2,3}$/', $recipientState)) {
        $errors['recipient_state'] = 'Recipient state must be a valid 2 or 3 letter ISO code.';
    }
}

// Validation for Recipient Country
if (empty($recipientCountry)) {
    $errors['recipient_country'] = 'Recipient country is required.';
} elseif (!preg_match('/^[A-Z]{2}$/', $recipientCountry)) {
    $errors['recipient_country'] = 'Recipient country must be a valid 2 letter ISO code.';
}

// Validation for Recipient Zip (only required for certain countries)
$recipientZip = sanitizeInput($_POST['recipient_zip'] ?? '');
if (in_array($recipientCountry, ['US', 'CA', 'AU', 'BR'], true)) {
    if (empty($recipientZip) || !preg_match('/^\d{5}(-\d{4})?$/', $recipientZip)) {
        $errors['recipient_zip'] = 'A valid zip code is required for USA, Canada, Australia and Brazil(e.g. 10001 or 10001-1234).';
    }
}

// Validation for Recipient Phone
$recipientPhone = sanitizeInput($_POST['recipient_phone'] ?? '');
if (empty($recipientPhone) || !preg_match('/^\+?\d{1,3}[-.\s]?\(?\d{1,4}\)?[-.\s]?\d{1,4}[-.\s]?\d{1,9}$/', $recipientPhone)) {
    $errors['recipient_phone'] = 'A valid phone number is required for recipient.';
}

// Validation for Recipient Email
$recipientEmail = sanitizeInput($_POST['recipient_email'] ?? '');
if (empty($recipientEmail) || !filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
    $errors['recipient_email'] = 'A valid email address is required for recipient.';
}

// Validation for Recipient VAT (optional)
$recipientVat = sanitizeInput($_POST['recipient_vat'] ?? '');

// Check if there are any errors
$response = [];

if (!empty($errors)) {
    $response = [
        'success' => false,
        'errors' => $errors,
    ];
} else {
    // Prepare data to send to newPackage
    $order = [
        'recipient' => [
            'Name' => $recipientName,
            'Company' => $recipientCompany,
            'AddressLine1' => $recipientAddressLine1,
            'AddressLine2' => $recipientAddressLine2,
            'City' => $recipientCity,
            'State' => $recipientState,
            'Zip' => $recipientZip,
            'Country' => $recipientCountry,
            'Phone' => $recipientPhone,
            'Email' => $recipientEmail,
            'Vat' => $recipientVat,
        ],
        'sender' => [
            'Name' => $senderName,
            'Company' => $senderCompany,
        ],
    ];

    $params = [
        'apiKey' => 'f16753b55cac6c6e',
        'labelFormat' => 'PDF',
        'shipperReference' => 'Reference_1020'.date("Yd-m-Y-H-i-s"),
        'service' => 'SIGN',
    ];

    $product[] = [
        'Description' => 'CD: The Postal Service - Give Up',
        'Sku' => 'CD1202',
        'HsCode' => '852349',
        'OriginCountry' => 'GB',
        'ImgUrl' => 'http://url.com/cd-thepostalservicegiveup.jpg',
        'Quantity' => '2',
        'Value' => '20',
        'Weight' => '0.8',
    ];

    $springCourier = new SpringCourier();
    $packageResponse = $springCourier->newPackage($order, $params, $product);


    if (isset($packageResponse['error'])) {
        $response = [
            'success' => false,
            'errors' => ['message' => $packageResponse['message']],
        ];
    } elseif (isset($packageResponse['ErrorLevel']) && $packageResponse['ErrorLevel'] > 0) {
        // If there is an error, return it
        $response = [
            'success' => false,
            'errors' => ['package' => $packageResponse['message']],
        ];
    } else {
        // Success case, extract shipment information
        $shipment = $packageResponse['Shipment'] ?? [];
        $response = [
            'success' => true,
            'message' => 'Tracking Number: '.$packageResponse['trackingNumber'],
            'trackingNumber' => $packageResponse['trackingNumber']
        ];
    }

}

// Return the JSON response
echo json_encode($response);
