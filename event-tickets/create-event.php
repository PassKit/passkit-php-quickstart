<?php

use Event_tickets\Event;
use Event_tickets\Production;
use Event_tickets\Venue;
use Google\Protobuf\Timestamp;

require_once "../vendor/autoload.php";

putenv("GRPC_SSL_CIPHER_SUITES=HIGH+ECDSA");

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

    // Create events client
    $eventsclient = new Event_tickets\EventTicketsClient('grpc.pub1.passkit.io:443', [
        'credentials' => $credentials
    ]);

    $production = new Production();
    $production->setId("");

    $venue = new Venue();
    $venue->setId("");

    // Function to convert a DateTime object to a Google\Protobuf\Timestamp
    function createTimestamp($year, $month, $day, $hour, $minute, $second)
    {
        $datetime = new DateTime();
        $datetime->setDate($year, $month, $day);
        $datetime->setTime($hour, $minute, $second);
        $timestamp = new Timestamp();
        $timestamp->setSeconds($datetime->getTimestamp());
        return $timestamp;
    }

    // Create timestamps for event dates
    $startDate = createTimestamp(2025, 2, 12, 13, 0, 0);
    $endDate = createTimestamp(2025, 2, 28, 13, 0, 0);
    $doorsOpen = createTimestamp(2025, 2, 12, 14, 0, 0);

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

    // You can use the eventId displayed below for other event ticket methods
    echo "EventId: " . $id->getId() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
