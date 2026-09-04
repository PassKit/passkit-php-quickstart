<?php

declare(strict_types=1);

namespace PassKit\Quickstart;

use Flights\AirportCode;
use Flights\BoardingPassRecord;
use Flights\BoardingPassRecordRequest;
use Flights\Carrier;
use Flights\CarrierCode;
use Flights\Flight;
use Flights\FlightDesignator;
use Flights\FlightDesignatorRequest;
use Flights\FlightRequest;
use Flights\FlightSchedule;
use Flights\FlightTimes;
use Flights\Passenger;
use Flights\Port;
use Io\Date;
use Io\DefaultTemplateRequest;
use Io\Id;
use Io\LocalDateTime;
use Io\Person;
use Io\Time;

final class FlightsQuickstart implements QuickstartWorkflow
{
    private const DESIGNATOR_REVISION = 1;
    private CleanupStack $cleanup;

    public function __construct(private readonly PassKitApi $api, private readonly Config $config)
    {
        $this->cleanup = new CleanupStack();
    }

    public function run(): QuickstartResult
    {
        $carrierCode = 'YY';
        $origin = 'YY4';
        $destination = 'ADP';
        $flightNumber = (string) random_int(100, 999);
        $departureDate = self::futureDate();

        echo "Creating flight template...\n";
        $request = new DefaultTemplateRequest();
        $request->setProtocol(3);
        $request->setRevision(1);
        $template = $this->api->templates->getDefaultTemplate($request);
        $template->setName('PHP Quickstart Flight ' . gmdate('YmdHis'));
        $template->setDescription('PassKit PHP quickstart boarding pass');
        $template->setTimezone('Europe/London');
        $templateId = $this->api->templates->createTemplate($template)->getId();
        $this->cleanup->add('flight template', fn () => $this->api->templates->deleteTemplate(self::id($templateId)));

        echo "Creating or reusing carrier and airports...\n";
        $carrier = new Carrier();
        $carrier->setIataCarrierCode($carrierCode);
        $carrier->setAirlineName('PassKit Quickstart Air');
        $carrier->setPassTypeIdentifier((string) $this->config->appleCertificate);
        $carrierCreated = $this->createOrReuse('carrier ' . $carrierCode, fn () => $this->api->flights->createCarrier($carrier));
        if ($carrierCreated) {
            $this->cleanup->add('carrier', fn () => $this->api->flights->deleteCarrier(self::carrierCode($carrierCode)));
        }

        $originCreated = $this->createPort($origin, 'YYYY', 'Quickstart Origin', 'London', 'GB', 'Europe/London');
        if ($originCreated) {
            $this->cleanup->add('origin airport', fn () => $this->api->flights->deletePort(self::airportCode($origin)));
        }
        $destinationCreated = $this->createPort($destination, 'ADPY', 'Quickstart Destination', 'Paris', 'FR', 'Europe/Paris');
        if ($destinationCreated) {
            $this->cleanup->add('destination airport', fn () => $this->api->flights->deletePort(self::airportCode($destination)));
        }

        echo "Creating flight and designator...\n";
        $flight = new Flight();
        $flight->setCarrierCode($carrierCode);
        $flight->setFlightNumber($flightNumber);
        $flight->setBoardingPoint($origin);
        $flight->setDeplaningPoint($destination);
        $flight->setDepartureDate($departureDate);
        $flight->setScheduledDepartureTime(self::localDateTime($departureDate, '13:00:00'));
        $flight->setScheduledArrivalTime(self::localDateTime($departureDate, '15:00:00'));
        $flight->setPassTemplateId($templateId);
        $this->api->flights->createFlight($flight);
        $this->cleanup->add('flight', fn () => $this->api->flights->deleteFlight(
            self::flightRequest($carrierCode, $flightNumber, $origin, $destination, $departureDate),
        ));

        $designator = new FlightDesignator();
        $designator->setCarrierCode($carrierCode);
        $designator->setFlightNumber($flightNumber);
        $designator->setRevision(self::DESIGNATOR_REVISION);
        $designator->setActive(true);
        $designator->setOrigin($origin);
        $designator->setDestination($destination);
        $designator->setPassTemplateId($templateId);
        $designator->setSchedule(self::schedule());
        $this->api->flights->createFlightDesignator($designator);
        $this->cleanup->add('flight designator', fn () => $this->api->flights->deleteFlightDesignator(
            self::designatorRequest($carrierCode, $flightNumber),
        ));

        echo "Creating boarding pass...\n";
        $person = new Person();
        $person->setForename('Flight');
        $person->setSurname('Passenger');
        $person->setDisplayName('Flight Passenger');
        $person->setEmailAddress($this->config->recipientEmail ?? 'flight.passenger@dummy.passkit.com');
        $passenger = new Passenger();
        $passenger->setPassengerDetails($person);
        $record = new BoardingPassRecord();
        $record->setOperatingCarrierPNR(strtoupper(bin2hex(random_bytes(3))));
        $record->setBoardingPoint($origin);
        $record->setDeplaningPoint($destination);
        $record->setCarrierCode($carrierCode);
        $record->setFlightNumber($flightNumber);
        $record->setDepartureDate($departureDate);
        $record->setPassenger($passenger);
        $record->setSequenceNumber(1);
        $record->setSeatNumber('12A');
        $record->setClass('Economy');
        $response = $this->api->flights->createBoardingPass($record);

        $urls = [];
        foreach ($response->getBoardingPasses() as $index => $pass) {
            $passId = $pass->getId();
            $delete = new BoardingPassRecordRequest();
            $delete->setPassId(self::id($passId));
            $this->cleanup->add("boarding pass {$passId}", fn () => $this->api->flights->deleteBoardingPass($delete));
            $urls['boardingPass' . ($index + 1) . 'Url'] = $pass->getUrl() ?: ($pass->getGooglePayURL() ?: $this->config->passUrl($passId));
        }

        return new QuickstartResult($urls);
    }

    public function cleanup(): void
    {
        $this->cleanup->run();
    }

    private function createPort(string $iata, string $icao, string $airport, string $city, string $country, string $timezone): bool
    {
        $port = new Port();
        $port->setIataAirportCode($iata);
        $port->setIcaoAirportCode($icao);
        $port->setAirportName($airport);
        $port->setCityName($city);
        $port->setCountryCode($country);
        $port->setTimezone($timezone);
        return $this->createOrReuse("airport {$iata}", fn () => $this->api->flights->createPort($port));
    }

    private function createOrReuse(string $label, callable $create): bool
    {
        try {
            $create();
            return true;
        } catch (\RuntimeException $error) {
            if ($error->getCode() !== 6) {
                throw $error;
            }
            echo "  {$label} already exists; reusing it.\n";
            return false;
        }
    }

    private static function schedule(): FlightSchedule
    {
        $schedule = new FlightSchedule();
        foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $day) {
            $schedule->{'set' . $day}(self::flightTimes());
        }
        return $schedule;
    }

    private static function flightTimes(): FlightTimes
    {
        $times = new FlightTimes();
        $times->setBoardingTime(self::time(12, 15));
        $times->setGateClosingTime(self::time(12, 30));
        $times->setScheduledDepartureTime(self::time(13));
        $times->setScheduledArrivalTime(self::time(15));
        return $times;
    }

    private static function time(int $hour, int $minute = 0): Time
    {
        $time = new Time();
        $time->setHour($hour);
        $time->setMinute($minute);
        return $time;
    }

    private static function futureDate(): Date
    {
        $future = new \DateTimeImmutable('+7 days', new \DateTimeZone('UTC'));
        $date = new Date();
        $date->setYear((int) $future->format('Y'));
        $date->setMonth((int) $future->format('n'));
        $date->setDay((int) $future->format('j'));
        return $date;
    }

    private static function localDateTime(Date $date, string $time): LocalDateTime
    {
        $value = new LocalDateTime();
        $value->setDateTime(sprintf('%04d-%02d-%02dT%s', $date->getYear(), $date->getMonth(), $date->getDay(), $time));
        return $value;
    }

    private static function flightRequest(string $carrier, string $number, string $origin, string $destination, Date $date): FlightRequest
    {
        $request = new FlightRequest();
        $request->setCarrierCode($carrier);
        $request->setFlightNumber($number);
        $request->setBoardingPoint($origin);
        $request->setDeplaningPoint($destination);
        $request->setDepartureDate($date);
        return $request;
    }

    private static function designatorRequest(string $carrier, string $number): FlightDesignatorRequest
    {
        $request = new FlightDesignatorRequest();
        $request->setCarrierCode($carrier);
        $request->setFlightNumber($number);
        $request->setRevision(self::DESIGNATOR_REVISION);
        return $request;
    }

    private static function carrierCode(string $value): CarrierCode
    {
        $code = new CarrierCode();
        $code->setCarrierCode($value);
        return $code;
    }

    private static function airportCode(string $value): AirportCode
    {
        $code = new AirportCode();
        $code->setAirportCode($value);
        return $code;
    }

    private static function id(string $value): Id
    {
        $id = new Id();
        $id->setId($value);
        return $id;
    }
}
