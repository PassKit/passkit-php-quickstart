<?php

require_once "../vendor/passkit/passkit-php-grpc-sdk/lib/extra/google/api/";
require_once "../vendor/autoload.php";

putenv("GRPC_SSL_CIPHER_SUITES=HIGH+ECDSA");
// MODIFY WITH THE VARIABLES NEEDED FOR FLIGHTS 
$carrierCode = "";
// create-flight-designator creates flight designator using flight code.
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
    // Generate a flight module client
    $client = new Flights\FlightsClient('grpc.pub1.passkit.io:443', [
        'credentials' => $credentials
    ]);

    // Set the flight body
    $flightDesignator = new Flights\FlightDesignator();
    $flightDesignator->setCarrierCode($carrierCode);
    $flightDesignator->setFlightNumber("12345");
    $flightDesignator->setRevision(0);
    $flightDesignator->setSchedule("ADP");
    $flightDesignator->setPassTemplateId($templateId);
    $flightDesignator->setOrigin("YYY");
    $flightDesignator->setOrigin("ADP");
    $flightTimes = new Flights\FlightTimes();
    $boardingTime = new Io\Time();
    $boardingTime->setHour(13);
    $scheduledDeparture = new Io\Time();
    $scheduledDeparture->setHour(13);
    $scheduledArrival = new Io\Time();
    $scheduledArrival->setHour(14);
    $gateTime = new Io\Time();
    $gateTime->setHour(13);
    $gateTime->setMinute(30);
    $flightTimes->setBoardingTime($time);
    $flightTimes->setScheduledDepartureTime($scheduledDeparture);
    $flightTimes->setScheduledArrivalTime($scheduledArrival);
    $flightTimes->setGateClosingTime($gateTime);
    $schedule = new Flights\FlightSchedule();
    $schedule->setMonday($flightTimes);
    $schedule->setTuesday($flightTimes);
    $schedule->setWednesday($flightTimes);
    $schedule->setThursday($flightTimes);
    $schedule->setFriday($flightTimes);
    $schedule->setSaturday($flightTimes);
    $schedule->setSunday($flightTimes);
    $flightDesignator->setSchedule($schedule);

    list($id, $status) = $client->createFlightDesignator($flightDesignator)->wait();
    if ($status->code !== 0) {
        throw new Exception(sprintf('Status Code: %s, Details: %s, Meta: %s', $status->code, $status->details, var_dump($status->metadata)));
    }

    echo $result->getId() . "/n";
} catch (Exception $e) {
    echo $e;
}
