<?php

require_once "../vendor/autoload.php";

use Event_tickets\Venue;

putenv("GRPC_SSL_CIPHER_SUITES=HIGH+ECDSA");
// create-venue creates a venue for an event ticket.
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

    // Create the venue for the event ticket
    $venue = new Venue();
    $venue->setName("Quickstart Venue");
    $venue->setAddress("123 Abc Street");
    $venue->setTimezone("Europe/London");

    list($id, $status) = $eventsclient->createVenue($venue)->wait();
    if ($status->code !== 0) {
        throw new Exception(sprintf('Status Code: %s, Details: %s, Meta: %s', $status->code, $status->details, var_dump($status->metadata)));
    }

    //You can use the venueId displayed below for other event ticket methods
    echo "VenueId: " . $venue->getId() . "\n";
} catch (Exception $e) {
    echo $e;
}
