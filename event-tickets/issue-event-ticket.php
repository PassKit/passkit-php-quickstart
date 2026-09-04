<?php

use Event_tickets\IssueTicketRequest;
use Google\Protobuf\Timestamp;

require_once dirname(__DIR__) . "/vendor/autoload.php";
PassKit\Quickstart\Env::load(dirname(__DIR__) . "/.env");


putenv("GRPC_SSL_CIPHER_SUITES=HIGH+ECDSA");
// issue-event-ticket takes the ticketTypeId and eventId and issues an event ticket.
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

    //Create events client
    $eventsclient = new Event_tickets\EventTicketsClient((getenv("PASSKIT_ADDRESS") ?: "grpc.pub1.passkit.io") . ":" . (getenv("PASSKIT_PORT") ?: "443"), [
        'credentials' => $credentials
    ]);

    $endDate = new DateTimeImmutable('+8 days');

    $expiryTimestamp = new Timestamp();
    $expiryTimestamp->setSeconds($endDate->getTimestamp());


    $person = new Io\Person();
    $person->setDisplayName("Loyal Larry");
    $person->setForename("Larry");
    $person->setSurname("Loyal");
    $person->setEmailAddress("");

    // Create the ticket to issue
    $ticket = new IssueTicketRequest();
    $ticket->setTicketTypeId("");
    $ticket->setEventId("");
    $ticket->setExpiryDate($expiryTimestamp);
    $ticket->setOrderNumber("1");
    $ticket->setTicketNumber("1");
    $ticket->setPerson($person);

    list($id, $status) = $eventsclient->issueTicket($ticket)->wait();
    if ($status->code !== 0) {
        throw new Exception(sprintf('Status Code: %s, Details: %s, Meta: %s', $status->code, $status->details, var_dump($status->metadata)));
    }

    //You can view the ticket using the url displayed when the program is ran
    echo "Pass URL: " . "https://pub1.pskt.io/" . $id->getId() . "\n";
} catch (Exception $e) {
    echo $e;
}
