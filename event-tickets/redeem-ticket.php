<?php

use Event_tickets\RedeemTicketRequest;
use Event_tickets\RedemptionDetails;
use Event_tickets\TicketId;
use Event_tickets\ValidateDetails;
use Event_tickets\ValidateTicketRequest;

require_once "../vendor/autoload.php";


putenv("GRPC_SSL_CIPHER_SUITES=HIGH+ECDSA");
// redeem-ticket takes the ticketId and redeemption date and redeems an event ticket.
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

    $redeemDate = new DateTime("now");
    $redeemDate = $endDate->getTimestamp();

    $ticketId = new TicketId();
    $ticketId->setTicketId("Your ticket Id");

    $redeemDetails = new RedemptionDetails();
    $redeemDetails->setRedemptionDate($redeemDate);

    // Set up ticket to redeem
    $ticket = new RedeemTicketRequest();
    $ticket->setTicket($ticketId);
    $ticket->setRedemptionDetails($redeemDetails);

    list($id, $status) = $eventsclient->redeemTicket($ticket)->wait();
    if ($status->code !== 0) {
        throw new Exception(sprintf('Status Code: %s, Details: %s, Meta: %s', $status->code, $status->details, var_dump($status->metadata)));
    }

    echo "The ticket has been redeemed \n";
} catch (Exception $e) {
    echo $e;
}
