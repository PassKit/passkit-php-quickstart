<?php
require_once "../vendor/autoload.php";

putenv("GRPC_SSL_CIPHER_SUITES=HIGH+ECDSA");

// MODIFY WITH THE VARIABLES OF YOUR CAMPAIGN, OFFER AND COUPON
$campaignId = "";
$couponId = "";

// update-coupon takes a campaignId of an existing campaign and couponId of existing coupon to update that coupon.
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
    // Set the coupon to update as well as updated info
    $coupon = new Single_use_coupons\Coupon();
    $coupon->setCampaignId($campaignId);
    $coupon->setId($couponId);
    $person = new Io\Person();
    $person->setDisplayName("Highroller Harry");  //Changes customers name
    $coupon->setPerson($person);


    list($id, $status) = $client->updateCoupon($coupon)->wait();
    if ($status->code !== 0) {
        throw new Exception(sprintf('Status Code: %s, Details: %s, Meta: %s', $status->code, $status->details, var_dump($status->metadata)));
    }

    echo "Coupon: " . $id->getId() . " has been updated. \n";
} catch (Exception $e) {
    echo $e;
}
