<?php

use Event_tickets\Production;

require_once "../vendor/autoload.php";


putenv("GRPC_SSL_CIPHER_SUITES=HIGH+ECDSA");
// create-production creates a production for an event ticket.
try {
    $ca_filename = "ca-chain.pem";
    $key_filename = "key.pem";
    $cert_filename = "certificate.pem";
    $path = "../certs/";

    $credentials = Grpc\ChannelCredentials::createSsl(
        file_get_contents($path . $ca_filename),
        file_get_contents($path . $key_filename),
        file_get_contents($path . $cert_filename)
    );

    //Create events client
    $eventsclient = new Event_tickets\EventTicketsClient('grpc.pub1.passkit.io:443', [
        'credentials' => $credentials
    ]);

    // Create the production for the event ticket
    $production = new Production();
    $production->setName("Quickstart Production");
    $production->setFinePrint("Quickstart Fine Print");
    $production->setAutoInvalidateTicketsUponEventEnd(1);

    list($id, $status) = $eventsclient->createProduction($production)->wait();
    if ($status->code !== 0) {
        throw new Exception(sprintf('Status Code: %s, Details: %s, Meta: %s', $status->code, $status->details, var_dump($status->metadata)));
    }

    //You can use the productionId displayed below for other event ticket methods
    echo "ProductionId: " . $id->getId() . "\n";
} catch (Exception $e) {
    echo $e;
}
