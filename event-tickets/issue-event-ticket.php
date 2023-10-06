<?php

use Event_tickets\IssueTicketRequest;

require_once "../vendor/autoload.php";


putenv("GRPC_SSL_CIPHER_SUITES=HIGH+ECDSA");
// issue-event-ticket takes the ticketTypeId and eventId and issues an event ticket.
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

    $endDate = new DateTime();
    $endDate->setDate(2023, 12, 13);
    $endDate->setTime(13, 0, 0);
    $endDate = $endDate->getTimestamp();

    $person = new Io\Person();
    $person->setDisplayName("Loyal Larry");
    $person->setForename("Larry");
    $person->setSurname("Loyal");
    $person->setEmailAddress("");

    // Create the ticket to issue
    $ticket = new IssueTicketRequest();
    $ticket->setTicketTypeId("Your ticketTypeId");
    $ticket->setEventId(" Your eventId ");
    $ticket->setExpiryDate($endDate);
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
