<?php

use Event_tickets\Event;
use Event_tickets\Production;
use Event_tickets\Venue;

require_once "../vendor/autoload.php";


putenv("GRPC_SSL_CIPHER_SUITES=HIGH+ECDSA");
// create-event takes the venueId and creates an event for an event ticket.
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

    $production = new Production();
    $production->setId("Your Production Id");
    $venue = new Venue();
    $venue->setId("Your Venue Id");

    $startDate = new DateTime();
    $startDate->setDate(2023, 12, 12);
    $startDate->setTime(13, 0, 0);
    $startDate = $startDate->getTimestamp();
    $endDate = new DateTime();
    $endDate->setDate(2023, 12, 13);
    $endDate->setTime(13, 0, 0);
    $endDate = $endDate->getTimestamp();
    $doorsOpen = new DateTime();
    $doorsOpen->setDate(2023, 12, 13);
    $doorsOpen->setTime(14, 0, 0);
    $doorsOpen = $doorsOpen->getTimestamp();

    // Create the event for the event ticket
    $event = new Event();
    $event->setProduction($production);
    $event->setVenue($venue);
    $event->setScheduledStartDate($startDate);
    $event->setDoorsOpen($doorsOpen);
    $event->setEndDate($endDate);
    $event->setRelevantDate($startDate);

    list($id, $status) = $eventsclient->createEvent($event)->wait();
    if ($status->code !== 0) {
        throw new Exception(sprintf('Status Code: %s, Details: %s, Meta: %s', $status->code, $status->details, var_dump($status->metadata)));
    }

    //You can use the eventId displayed below for other event ticket methods
    echo "EventId: " . $event->getId() . "\n";
} catch (Exception $e) {
    echo $e;
}
