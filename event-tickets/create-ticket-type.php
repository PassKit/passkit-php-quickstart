<?php

use Event_tickets\TicketType;

require_once "../vendor/autoload.php";


putenv("GRPC_SSL_CIPHER_SUITES=HIGH+ECDSA");
// create-ticket-type takes the templateId and productionId and creates a new ticket type for an event ticket.
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

    // Create the ticket type for the event ticket
    $ticketType = new TicketType();
    $ticketType->setName("Quickstart Ticket Type");
    $ticketType->setProductionId(" Your productionId ");
    $ticketType->setBeforeRedeemPassTemplateId("Your templateId");
    $ticketType->setUid("");

    list($id, $status) = $eventsclient->createTicketType($ticketType)->wait();
    if ($status->code !== 0) {
        throw new Exception(sprintf('Status Code: %s, Details: %s, Meta: %s', $status->code, $status->details, var_dump($status->metadata)));
    }

    //You can use the ticket type Id displayed below for other event ticket methods
    echo "TicketTypeId: " . $credentials->getId() . "\n";
} catch (Exception $e) {
    echo $e;
}
