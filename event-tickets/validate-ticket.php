<?php

use Event_tickets\TicketId;
use Event_tickets\ValidateDetails;
use Event_tickets\ValidateTicketRequest;

require_once "../vendor/autoload.php";


putenv("GRPC_SSL_CIPHER_SUITES=HIGH+ECDSA");
// validate-ticket takes the ticketId and validation date and validates an event ticket.
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

    $validateDate = new DateTime("now");
    $validateDate = $endDate->getTimestamp();

    $ticketId = new TicketId();
    $ticketId->setTicketId("Your ticket Id");

    $validateDetails = new ValidateDetails();
    $validateDetails->setValidateDate($validateDate);

    // Set up ticket to validate
    $ticket = new ValidateTicketRequest();
    $ticket->setMaxNumberOfValidations(1);
    $ticket->setTicket($ticketId);
    $ticket->setValidateDetails($validateDetails);

    list($id, $status) = $eventsclient->validateTicket($ticket)->wait();
    if ($status->code !== 0) {
        throw new Exception(sprintf('Status Code: %s, Details: %s, Meta: %s', $status->code, $status->details, var_dump($status->metadata)));
    }

    echo "The ticket has been validated \n";
} catch (Exception $e) {
    echo $e;
}
