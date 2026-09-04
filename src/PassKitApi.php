<?php

declare(strict_types=1);

namespace PassKit\Quickstart;

final class PassKitApi
{
    /** @var array<string, array{unary: list<string>, stream: list<string>}> */
    public const METHODS = [
        'loyalty' => [
            'unary' => ['createProgram', 'getProgram', 'updateProgram', 'deleteProgram', 'createTier', 'getTier', 'updateTier', 'deleteTier', 'enrolMember', 'getMemberRecordById', 'getMemberRecordByExternalId', 'updateMember', 'patchPerson', 'deleteMember', 'countMembers', 'changeMemberTier', 'earnPoints', 'burnPoints', 'setPoints', 'updateMemberExpiry', 'renewMembersExpiry', 'countMemberEvents', 'checkInMember', 'checkOutMember', 'deleteMemberEvent', 'deleteEventsForMember', 'getProgramEnrolment'],
            'stream' => ['listPrograms', 'listTiers', 'listMembers', 'listMemberEvents', 'getMessageHistoryForMember', 'getMetaKeysForProgram', 'getMemberEventMetaKeysForProgram'],
        ],
        'coupons' => [
            'unary' => ['createCouponCampaign', 'getCouponCampaign', 'updateCouponCampaign', 'deleteCouponCampaign', 'createCouponOffer', 'getCouponOffer', 'updateCouponOffer', 'deleteCouponOffer', 'createCoupon', 'getCouponById', 'getCouponByExternalId', 'updateCoupon', 'updateCouponExternalId', 'patchPerson', 'redeemCoupon', 'voidCoupon', 'countCouponsByCouponCampaign', 'getAnalytics'],
            'stream' => ['listCouponCampaigns', 'listCouponOffers', 'listCouponsByCouponCampaign', 'streamCouponUpdates', 'streamCouponRedemptions', 'getMetaKeysForCampaign'],
        ],
        'eventTickets' => [
            'unary' => ['createProduction', 'getProduction', 'updateProduction', 'patchProduction', 'deleteProduction', 'createVenue', 'getVenueById', 'updateVenue', 'patchVenue', 'deleteVenue', 'createEvent', 'getEventById', 'getEventByStartDateAndVenue', 'updateEvent', 'patchEvent', 'deleteEvent', 'createTicketType', 'getTicketTypeById', 'getTicketTypeByUserDefinedId', 'updateTicketType', 'patchTicketType', 'deleteTicketType', 'issueTicket', 'issueTicketById', 'getTicketById', 'getTicketByTicketNumber', 'getTicketsByOrderNumber', 'getEventTicketPass', 'updateTicket', 'patchPerson', 'validateTicket', 'redeemTicket', 'redeemTicketsByOrderNumber', 'deleteTicket', 'deleteTicketsByOrderNumber', 'countTickets', 'getAnalytics'],
            'stream' => ['listProductions', 'listVenues', 'listEvents', 'listTicketTypes', 'listTickets'],
        ],
        'flights' => [
            'unary' => ['createCarrier', 'getCarrier', 'updateCarrier', 'deleteCarrier', 'createPort', 'getPort', 'updatePort', 'deletePort', 'createFlightDesignator', 'getFlightDesignator', 'updateFlightDesignator', 'deleteFlightDesignator', 'createFlight', 'getFlight', 'updateFlight', 'deleteFlight', 'createBoardingPass', 'getBoardingPass', 'getBoardingPassRecord', 'updateBoardingPass', 'deleteBoardingPass'],
            'stream' => [],
        ],
        'templates' => [
            'unary' => ['createTemplate', 'getTemplate', 'getDefaultTemplate', 'updateTemplate', 'copyTemplate', 'deleteTemplate', 'countTemplates', 'createLocation', 'getLocation', 'updateLocation', 'copyLocation', 'deleteLocation', 'countLocations', 'createBeacon', 'getBeacon', 'updateBeacon', 'copyBeacon', 'deleteBeacon', 'countBeacons', 'createLink', 'getLink', 'updateLink', 'copyLink', 'deleteLink', 'countLinks'],
            'stream' => ['listTemplates', 'listLocations', 'listBeacons', 'listLinks'],
        ],
        'images' => [
            'unary' => ['createImages', 'getImageBundle', 'getImageData', 'getImageURL', 'getLocalizedImageURL', 'updateImage', 'deleteImage', 'deleteLocalizedImage', 'countImages', 'getProfileImage', 'getProfileImageById', 'setProfileImage', 'getStampImageConfig', 'updateStampImageConfig', 'getStampImagePreview', 'getStampImageURL'],
            'stream' => ['listImages'],
        ],
        'analytics' => ['unary' => ['getAnalytics'], 'stream' => []],
        'distribution' => [
            'unary' => ['getSmartPassLink', 'getDataCollectionPageFields', 'validateBarcode', 'sendWelcomeEmail', 'addMessage', 'getMessage', 'updateMessage', 'cancelMessage'],
            'stream' => ['getMessages'],
        ],
        'messages' => [
            'unary' => ['createMessage', 'getMessage', 'updateMessage', 'deleteMessage', 'sendMessage'],
            'stream' => [],
        ],
        'integrations' => [
            'unary' => ['createSinkSubscription', 'getSinkSubscription', 'updateSinkSubscription', 'deleteSinkSubscription', 'getSampleSubscriptionEvent'],
            'stream' => ['listSinkSubscriptions'],
        ],
        'scanners' => ['unary' => ['getScannerConfig', 'createScannerConfig', 'updateScannerConfig'], 'stream' => []],
        'certificates' => ['unary' => ['getAppleCertificateData', 'countAppleCertificates'], 'stream' => ['listAppleCertificates']],
        'raw' => [
            'unary' => ['createPassProject', 'getPassProject', 'updatePassProject', 'copyPassProject', 'deletePassProject', 'createPass', 'getPassById', 'getPassByExternalId', 'updatePass', 'deletePass'],
            'stream' => ['streamPassUpdates', 'listPassesByPassProject', 'listPassesByPassTemplate'],
        ],
    ];

    /** @var array<string, list<string>> */
    public const ADVANCED = [
        'loyalty' => ['batchUpdate', 'bulkDeleteMembers', 'updateMembersBySegment', 'deleteMembersBySegment', 'copyProgram'],
        'coupons' => ['bulkVoidCoupons', 'copyCouponCampaign'],
        'eventTickets' => ['bulkDeleteTickets', 'copyProduction'],
    ];

    /** @var array<string, ApiGroup> */
    private array $groups = [];
    /** @var array<string, ApiGroup> */
    private array $advanced = [];
    /** @var array<string, object> */
    private array $clients;

    /** @param array<string, object> $clients */
    public function __construct(array $clients, bool $allowDestructive = false)
    {
        $this->clients = $clients;
        foreach (self::METHODS as $domain => $definition) {
            $this->groups[$domain] = new ApiGroup($clients[$domain], $definition['unary'], $definition['stream']);
        }
        foreach (self::ADVANCED as $domain => $methods) {
            $this->advanced[$domain] = new ApiGroup($clients[$domain], $methods, [], $allowDestructive);
        }
    }

    public function __get(string $domain): ApiGroup
    {
        if ($domain === 'advanced') {
            throw new \LogicException('Use advanced("loyalty"), advanced("coupons"), or advanced("eventTickets").');
        }
        return $this->groups[$domain] ?? throw new \OutOfBoundsException("Unknown API domain: {$domain}");
    }

    public function advanced(string $domain): ApiGroup
    {
        return $this->advanced[$domain] ?? throw new \OutOfBoundsException("Unknown advanced API domain: {$domain}");
    }

    public function client(string $domain): object
    {
        return $this->clients[$domain] ?? throw new \OutOfBoundsException("Unknown SDK client: {$domain}");
    }

    public function batchUpdateMembers(\Io\BatchUpdateRequest $request): object
    {
        return $this->advanced('loyalty')->batchUpdate($request);
    }

    public function addMessage(\Io\Message $request): object
    {
        return $this->distribution->addMessage($request);
    }

    /** @return list<\Io\Message> */
    public function getMessages(): array
    {
        return $this->distribution->getMessagesToArray(new \Google\Protobuf\GPBEmpty());
    }

    public function cancelMessage(\Io\Id $request): object
    {
        return $this->distribution->cancelMessage($request);
    }
}
