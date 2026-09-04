<?php

declare(strict_types=1);

namespace PassKit\Quickstart;

use Event_tickets\Event;
use Event_tickets\IssueTicketRequest;
use Event_tickets\OrderNumberRequest;
use Event_tickets\Production;
use Event_tickets\RedeemTicketRequest;
use Event_tickets\Ticket;
use Event_tickets\TicketId;
use Event_tickets\TicketListRequest;
use Event_tickets\TicketNumberRequest;
use Event_tickets\TicketType;
use Event_tickets\ValidateTicketRequest;
use Event_tickets\Venue;
use Google\Protobuf\Timestamp;
use Io\DefaultTemplateRequest;
use Io\Filters;
use Io\Id;
use Io\Person;

final class EventTicketsQuickstart implements QuickstartWorkflow
{
    private CleanupStack $cleanup;

    public function __construct(private readonly PassKitApi $api, private readonly Config $config)
    {
        $this->cleanup = new CleanupStack();
    }

    public function run(): QuickstartResult
    {
        $suffix = gmdate('YmdHis') . '-' . bin2hex(random_bytes(2));

        echo "Creating event-ticket template...\n";
        $templateRequest = new DefaultTemplateRequest();
        $templateRequest->setProtocol(102);
        $templateRequest->setRevision(1);
        $template = $this->api->templates->getDefaultTemplate($templateRequest);
        $template->setName("PHP Quickstart Event Ticket {$suffix}");
        $template->setDescription('PassKit PHP quickstart event ticket');
        $template->setTimezone('Europe/London');
        $templateId = $this->api->templates->createTemplate($template)->getId();
        $this->cleanup->add('event-ticket template', fn () => $this->api->templates->deleteTemplate(self::id($templateId)));

        echo "Creating production and venue...\n";
        $production = new Production();
        $production->setName("PHP Quickstart Production {$suffix}");
        $production->setFinePrint('PassKit PHP quickstart fine print');
        $production->setAutoInvalidateTicketsUponEventEnd(1);
        $production->setStatus([1, 4]);
        $productionId = $this->api->eventTickets->createProduction($production)->getId();
        $this->cleanup->add('production', function () use ($productionId): void {
            $request = new Production();
            $request->setId($productionId);
            $this->api->eventTickets->deleteProduction($request);
        });

        $venue = new Venue();
        $venue->setName("PHP Quickstart Venue {$suffix}");
        $venue->setAddress('123 Quickstart Street, London');
        $venue->setTimezone('Europe/London');
        $venueId = $this->api->eventTickets->createVenue($venue)->getId();

        echo "Creating event and ticket type...\n";
        $start = time() + 7 * 86400;
        $productionRef = new Production();
        $productionRef->setId($productionId);
        $venueRef = new Venue();
        $venueRef->setId($venueId);
        $event = new Event();
        $event->setProduction($productionRef);
        $event->setVenue($venueRef);
        $event->setDoorsOpen(self::timestamp($start - 3600));
        $event->setScheduledStartDate(self::timestamp($start));
        $event->setRelevantDate(self::timestamp($start));
        $event->setEndDate(self::timestamp($start + 3 * 3600));
        $eventId = $this->api->eventTickets->createEvent($event)->getId();

        $ticketType = new TicketType();
        $ticketType->setName('PHP Quickstart General Admission');
        $ticketType->setUid("php-{$suffix}");
        $ticketType->setProductionId($productionId);
        $ticketType->setBeforeRedeemPassTemplateId($templateId);
        $ticketTypeId = $this->api->eventTickets->createTicketType($ticketType)->getId();

        echo "Issuing ticket...\n";
        $ticketNumber = "T-{$suffix}";
        $orderNumber = "O-{$suffix}";
        $person = new Person();
        $person->setDisplayName('PHP Quickstart Guest');
        if ($this->config->recipientEmail !== null) {
            $person->setEmailAddress($this->config->recipientEmail);
        }
        $issue = new IssueTicketRequest();
        $issue->setEventId($eventId);
        $issue->setTicketTypeId($ticketTypeId);
        $issue->setTicketNumber($ticketNumber);
        $issue->setOrderNumber($orderNumber);
        $issue->setPerson($person);
        $ticketId = $this->api->eventTickets->issueTicket($issue)->getId();
        $this->cleanup->add('event ticket', fn () => $this->api->eventTickets->deleteTicket(self::ticketId($ticketId)));

        echo "Validating, updating, finding, listing, and redeeming ticket...\n";
        $validate = new ValidateTicketRequest();
        $validate->setTicket(self::ticketId($ticketId));
        $validate->setMaxNumberOfValidations(3);
        $this->api->eventTickets->validateTicket($validate);

        $update = new Ticket();
        $update->setId($ticketId);
        $updatedPerson = new Person();
        $updatedPerson->setDisplayName('Updated PHP Quickstart Guest');
        $update->setPerson($updatedPerson);
        $this->api->eventTickets->updateTicket($update);
        $this->api->eventTickets->getTicketById(self::id($ticketId));

        $byOrder = new OrderNumberRequest();
        $byOrder->setProductionId($productionId);
        $byOrder->setOrderNumber($orderNumber);
        $this->api->eventTickets->getTicketsByOrderNumber($byOrder);
        $byNumber = new TicketNumberRequest();
        $byNumber->setProductionId($productionId);
        $byNumber->setTicketNumber($ticketNumber);
        $this->api->eventTickets->getTicketByTicketNumber($byNumber);
        $list = new TicketListRequest();
        $list->setProductionId($productionId);
        $list->setFilters(new Filters());
        $this->api->eventTickets->listTicketsToArray($list);

        $redeem = new RedeemTicketRequest();
        $redeem->setTicket(self::ticketId($ticketId));
        $this->api->eventTickets->redeemTicket($redeem);

        return new QuickstartResult(['eventTicketUrl' => $this->config->passUrl($ticketId)]);
    }

    public function cleanup(): void
    {
        $this->cleanup->run();
    }

    private static function id(string $value): Id
    {
        $id = new Id();
        $id->setId($value);
        return $id;
    }

    private static function ticketId(string $value): TicketId
    {
        $id = new TicketId();
        $id->setTicketId($value);
        return $id;
    }

    private static function timestamp(int $seconds): Timestamp
    {
        $timestamp = new Timestamp();
        $timestamp->setSeconds($seconds);
        return $timestamp;
    }
}
