<?php


require_once "../vendor/passkit/passkit-php-grpc-sdk/lib/extra/google/api/";
require_once "../vendor/autoload.php";

putenv("GRPC_SSL_CIPHER_SUITES=HIGH+ECDSA");

// MODIFY WITH THE VARIABLES OF YOUR CAMPAIGN, OFFER AND EMAIL
$campaignId = "05faUsEvatLifOwxKWmS0Q";
$offerId = "base";
$email = "claudia@passkit.com";
// create-coupon takes campaignId, offerId and couponDetails, creates a new coupon record, and sends a welcome email to deliver coupon card url.
// The method returns the coupon id. Coupon id is a part of card url.

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
    // Generate a coupons module client
    $client = new  Single_use_coupons\SingleUseCouponsClient('grpc.pub1.passkit.io:443', [
        'credentials' => $credentials
    ]);
    // Generates coupon with mandatory fields, more fields can be added, refer to docs.passkit.io and select Coupons for the full list
    $coupon = new Single_use_coupons\Coupon();
    $coupon->setCampaignId($campaignId);
    $coupon->setOfferId($offerId);
    $person = new Io\Person();
    $person->setDisplayName("Loyal Larry");
    $dateOfBirth = new Io\Date();
    $dateOfBirth->setDay(22);
    $dateOfBirth->setMonth(6);
    $dateOfBirth->setYear(2020);
    $person->setDateOfBirth($dateOfBirth);
    $person->setEmailAddress($email);
    $coupon->setPerson($person);

    list($id, $status) = $client->createCoupon($coupon)->wait();
    if ($status->code !== 0) {
        throw new Exception(sprintf('Status Code: %s, Details: %s, Meta: %s', $status->code, $status->details, var_dump($status->metadata)));
    }
    //You can use the couponId displayed below for other coupon methods
    echo "https://pub1.pskt.io/" . $id->getId() . "\n";
    echo "CouponId" . $coupon->getId() . "\n";
} catch (Exception $e) {
    echo $e;
}
