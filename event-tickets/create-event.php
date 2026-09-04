<?php

use Event_tickets\Event;
use Event_tickets\Production;
use Event_tickets\Venue;
use Google\Protobuf\Timestamp;

require_once dirname(__DIR__) . "/vendor/autoload.php";
PassKit\Quickstart\Env::load(dirname(__DIR__) . "/.env");

putenv("GRPC_SSL_CIPHER_SUITES=HIGH+ECDSA");

try {
    $ca_filename = "ca-chain.pem";
    $key_filename = "key.pem";
    $cert_filename = "certificate.pem";
    $path = dirname(__DIR__) . "/certs/";

    $credentials = Grpc\ChannelCredentials::createSsl(
        file_get_contents($path . $ca_filename),
        file_get_contents($path . $key_filename),
        file_get_contents($path . $cert_filename)
    );

    // Create events client
    $eventsclient = new Event_tickets\EventTicketsClient((getenv("PASSKIT_ADDRESS") ?: "grpc.pub1.passkit.io") . ":" . (getenv("PASSKIT_PORT") ?: "443"), [
        'credentials' => $credentials
    ]);

    $production = new Production();
    $production->setId("");

    $venue = new Venue();
    $venue->setId("");

    // Keep sample events valid whenever this example is run.
    function createTimestamp(DateTimeInterface $date)
    {
        $timestamp = new Timestamp();
        $timestamp->setSeconds($date->getTimestamp());
        return $timestamp;
    }

    $start = new DateTimeImmutable('+7 days 19:00:00');
    $startDate = createTimestamp($start);
    $endDate = createTimestamp($start->modify('+3 hours'));
    $doorsOpen = createTimestamp($start->modify('-1 hour'));

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
