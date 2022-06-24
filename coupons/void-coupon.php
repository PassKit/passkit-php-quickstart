<?php


require_once "../vendor/passkit/passkit-php-grpc-sdk/lib/extra/google/api/";
require_once "../vendor/autoload.php";

putenv("GRPC_SSL_CIPHER_SUITES=HIGH+ECDSA");

// MODIFY WITH THE VARIABLES OF YOUR CAMPAIGN, OFFER AND COUPON
$campaignId = "05faUsEvatLifOwxKWmS0Q";
$offerId = "base";
$couponId = "";
// void-coupon takes the couponId, offerId and campaignId to void an existing coupon.
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
    // Set the void coupon body
    $coupon = new Single_use_coupons\Coupon();
    $coupon->setCampaignId($campaignId);
    $coupon->setOfferId($offerId);
    $coupon->setId($couponId);

    list($id, $status) = $client->voidCoupon($coupon)->wait();
    if ($status->code !== 0) {
        throw new Exception(sprintf('Status Code: %s, Details: %s, Meta: %s', $status->code, $status->details, var_dump($status->metadata)));
    }

    echo $result->getId() . "/n";
} catch (Exception $e) {
    echo $e;
}
