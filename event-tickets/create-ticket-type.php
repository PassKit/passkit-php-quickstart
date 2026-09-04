<?php

use Event_tickets\TicketType;

require_once dirname(__DIR__) . "/vendor/autoload.php";
PassKit\Quickstart\Env::load(dirname(__DIR__) . "/.env");


putenv("GRPC_SSL_CIPHER_SUITES=HIGH+ECDSA");
// create-ticket-type takes the templateId and productionId and creates a new ticket type for an event ticket.
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

    // Create the ticket type for the event ticket
    $ticketType = new TicketType();
    $ticketType->setName("Quickstart Ticket Type");
    $ticketType->setProductionId("");
    $ticketType->setBeforeRedeemPassTemplateId("");
    $ticketType->setUid("");

    list($id, $status) = $eventsclient->createTicketType($ticketType)->wait();
    if ($status->code !== 0) {
        throw new Exception(sprintf('Status Code: %s, Details: %s, Meta: %s', $status->code, $status->details, var_dump($status->metadata)));
    }

    //You can use the ticket type Id displayed below for other event ticket methods
    echo "TicketTypeId: " . $id->getId() . "\n";
} catch (Exception $e) {
    echo $e;
}
