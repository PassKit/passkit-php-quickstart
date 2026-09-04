<?php

declare(strict_types=1);

namespace PassKit\Quickstart;

use Io\DefaultTemplateRequest;
use Io\Filters;
use Io\Id;
use Io\Person;
use Members\EarnBurnPointsRequest;
use Members\ListRequest;
use Members\Member;
use Members\Program;
use Members\Tier;
use Members\TierRequestInput;

final class LoyaltyQuickstart implements QuickstartWorkflow
{
    private CleanupStack $cleanup;

    public function __construct(private readonly PassKitApi $api, private readonly Config $config)
    {
        $this->cleanup = new CleanupStack();
    }

    public function run(): QuickstartResult
    {
        $suffix = gmdate('YmdHis') . '-' . bin2hex(random_bytes(2));

        echo "Creating loyalty program...\n";
        $program = new Program();
        $program->setName("PHP Quickstart Loyalty {$suffix}");
        $program->setStatus([1, 4]);
        $programId = $this->api->loyalty->createProgram($program)->getId();
        $this->cleanup->add('loyalty program', fn () => $this->api->loyalty->deleteProgram(self::id($programId)));

        echo "Creating membership template...\n";
        $templateRequest = new DefaultTemplateRequest();
        $templateRequest->setProtocol(100);
        $templateRequest->setRevision(1);
        $template = $this->api->templates->getDefaultTemplate($templateRequest);
        $template->setName("PHP Quickstart Membership {$suffix}");
        $template->setDescription('PassKit PHP quickstart membership pass');
        $template->setTimezone('Europe/London');
        $templateId = $this->api->templates->createTemplate($template)->getId();
        $this->cleanup->add('membership template', fn () => $this->api->templates->deleteTemplate(self::id($templateId)));

        echo "Creating tier...\n";
        $tier = new Tier();
        $tier->setId('base');
        $tier->setName('Base Tier');
        $tier->setProgramId($programId);
        $tier->setPassTemplateId($templateId);
        $tier->setTierIndex(1);
        $tier->setTimezone('Europe/London');
        $tierId = $this->api->loyalty->createTier($tier)->getId();
        $this->cleanup->add('membership tier', function () use ($programId, $tierId): void {
            $request = new TierRequestInput();
            $request->setProgramId($programId);
            $request->setTierId($tierId);
            $this->api->loyalty->deleteTier($request);
        });

        echo "Enrolling member...\n";
        $person = new Person();
        $person->setDisplayName('PHP Quickstart Member');
        if ($this->config->recipientEmail !== null) {
            $person->setEmailAddress($this->config->recipientEmail);
        }
        $member = new Member();
        $member->setProgramId($programId);
        $member->setTierId($tierId);
        $member->setExternalId("php-{$suffix}");
        $member->setPerson($person);
        $memberId = $this->api->loyalty->enrolMember($member)->getId();
        $this->cleanup->add('member', function () use ($memberId, $programId, $tierId): void {
            $request = new Member();
            $request->setId($memberId);
            $request->setProgramId($programId);
            $request->setTierId($tierId);
            $this->api->loyalty->deleteMember($request);
        });

        echo "Getting, listing, and updating member...\n";
        $this->api->loyalty->getMemberRecordById(self::id($memberId));
        $list = new ListRequest();
        $list->setProgramId($programId);
        $list->setFilters(new Filters());
        $this->api->loyalty->listMembersToArray($list);

        $member->setId($memberId);
        $member->setPoints(100);
        $this->api->loyalty->updateMember($member);
        $this->points('earnPoints', $memberId, $programId, $tierId, 25);
        $this->points('burnPoints', $memberId, $programId, $tierId, 5);

        return new QuickstartResult(['membershipPassUrl' => $this->config->passUrl($memberId)]);
    }

    public function cleanup(): void
    {
        $this->cleanup->run();
    }

    private function points(string $method, string $memberId, string $programId, string $tierId, int $points): void
    {
        $request = new EarnBurnPointsRequest();
        $request->setId($memberId);
        $request->setProgramId($programId);
        $request->setTierId($tierId);
        $request->setPoints($points);
        $this->api->loyalty->{$method}($request);
    }

    private static function id(string $value): Id
    {
        $id = new Id();
        $id->setId($value);
        return $id;
    }
}
