<?php

declare(strict_types=1);

namespace PassKit\Quickstart;

use Google\Protobuf\Timestamp;
use Io\DefaultTemplateRequest;
use Io\Filters;
use Io\Id;
use Io\Person;
use Single_use_coupons\Coupon;
use Single_use_coupons\CouponCampaign;
use Single_use_coupons\CouponOffer;
use Single_use_coupons\ListRequest;

final class CouponsQuickstart implements QuickstartWorkflow
{
    private CleanupStack $cleanup;

    public function __construct(private readonly PassKitApi $api, private readonly Config $config)
    {
        $this->cleanup = new CleanupStack();
    }

    public function run(): QuickstartResult
    {
        $suffix = gmdate('YmdHis') . '-' . bin2hex(random_bytes(2));

        echo "Creating coupon campaign...\n";
        $campaign = new CouponCampaign();
        $campaign->setName("PHP Quickstart Coupons {$suffix}");
        $campaign->setStatus([1, 4]);
        $campaign->setIanaTimezone('Europe/London');
        $campaignId = $this->api->coupons->createCouponCampaign($campaign)->getId();

        echo "Creating coupon template...\n";
        $templateRequest = new DefaultTemplateRequest();
        $templateRequest->setProtocol(101);
        $templateRequest->setRevision(1);
        $template = $this->api->templates->getDefaultTemplate($templateRequest);
        $template->setName("PHP Quickstart Coupon {$suffix}");
        $template->setDescription('PassKit PHP quickstart coupon pass');
        $template->setTimezone('Europe/London');
        $templateId = $this->api->templates->createTemplate($template)->getId();
        $this->cleanup->add('coupon template', fn () => $this->api->templates->deleteTemplate(self::id($templateId)));
        $this->cleanup->add('coupon campaign', fn () => $this->api->coupons->deleteCouponCampaign(self::id($campaignId)));

        echo "Creating coupon offer...\n";
        $offer = new CouponOffer();
        $offer->setId('base');
        $offer->setCampaignId($campaignId);
        $offer->setBeforeRedeemPassTemplateId($templateId);
        $offer->setOfferTitle('PHP Quickstart Offer');
        $offer->setOfferShortTitle('Quickstart Offer');
        $offer->setOfferDetails('Created by the PassKit PHP quickstart');
        $offer->setIanaTimezone('Europe/London');
        $offer->setIssueStartDate(self::timestamp(time() - 60));
        $offer->setIssueEndDate(self::timestamp(time() + 86400));
        $offerId = $this->api->coupons->createCouponOffer($offer)->getId();

        echo "Creating coupons...\n";
        $baseCouponId = $this->createCoupon($campaignId, $offerId, "base-{$suffix}", 'PHP Quickstart Customer');
        $voidCouponId = $this->createCoupon($campaignId, $offerId, "void-{$suffix}", 'PHP Quickstart Void Sample');

        echo "Getting, listing, counting, updating, redeeming, and voiding coupons...\n";
        $this->api->coupons->getCouponById(self::id($baseCouponId));
        $list = new ListRequest();
        $list->setCouponCampaignId($campaignId);
        $list->setFilters(new Filters());
        $this->api->coupons->listCouponsByCouponCampaignToArray($list);
        $this->api->coupons->countCouponsByCouponCampaign($list);

        $updated = new Coupon();
        $updated->setId($baseCouponId);
        $updated->setCampaignId($campaignId);
        $updated->setOfferId($offerId);
        $updatedPerson = new Person();
        $updatedPerson->setDisplayName('Updated PHP Quickstart Customer');
        $updated->setPerson($updatedPerson);
        $this->api->coupons->updateCoupon($updated);

        $updated->setStatus(1);
        $this->api->coupons->redeemCoupon($updated);
        $void = new Coupon();
        $void->setId($voidCouponId);
        $void->setCampaignId($campaignId);
        $void->setOfferId($offerId);
        $this->api->coupons->voidCoupon($void);

        return new QuickstartResult([
            'redeemedCouponUrl' => $this->config->passUrl($baseCouponId),
            'voidedCouponUrl' => $this->config->passUrl($voidCouponId),
        ]);
    }

    public function cleanup(): void
    {
        $this->cleanup->run();
    }

    private function createCoupon(string $campaignId, string $offerId, string $externalId, string $name): string
    {
        $person = new Person();
        $person->setDisplayName($name);
        if ($this->config->recipientEmail !== null) {
            $person->setEmailAddress($this->config->recipientEmail);
        }
        $coupon = new Coupon();
        $coupon->setCampaignId($campaignId);
        $coupon->setOfferId($offerId);
        $coupon->setExternalId($externalId);
        $coupon->setPerson($person);
        return $this->api->coupons->createCoupon($coupon)->getId();
    }

    private static function id(string $value): Id
    {
        $id = new Id();
        $id->setId($value);
        return $id;
    }

    private static function timestamp(int $seconds): Timestamp
    {
        $timestamp = new Timestamp();
        $timestamp->setSeconds($seconds);
        return $timestamp;
    }
}
