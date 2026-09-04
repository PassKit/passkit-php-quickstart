<?php

use Event_tickets\RedeemTicketRequest;
use Event_tickets\RedemptionDetails;
use Event_tickets\TicketId;
use Google\Protobuf\Timestamp;

require_once dirname(__DIR__) . "/vendor/autoload.php";
PassKit\Quickstart\Env::load(dirname(__DIR__) . "/.env");


putenv("GRPC_SSL_CIPHER_SUITES=HIGH+ECDSA");
// redeem-ticket takes the ticketId and redeemption date and redeems an event ticket.
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

    $redeemDate = new DateTimeImmutable('now');

    $redeemTimestamp = new Timestamp();
    $redeemTimestamp->setSeconds($redeemDate->getTimestamp());

    $ticketId = new TicketId();
    $ticketId->setTicketId("");

    $redeemDetails = new RedemptionDetails();
    $redeemDetails->setRedemptionDate($redeemTimestamp);

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
