# PassKit PHP Quickstart

[![CI](https://github.com/PassKit/passkit-php-quickstart/actions/workflows/ci.yml/badge.svg)](https://github.com/PassKit/passkit-php-quickstart/actions/workflows/ci.yml)
[![SDK](https://img.shields.io/badge/PassKit%20SDK-1.1.162-blue)](https://github.com/PassKit/passkit-php-grpc-sdk/tree/v1.1.162)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)

Use the official PHP SDK to create and manage membership cards, loyalty passes, coupons, event tickets, and flight boarding passes for Apple Wallet and Google Wallet.

## Quick start

You need PHP 8.2 or later, Composer, the PHP gRPC extension, a PassKit account, and PassKit SDK credentials.

### 1. Install PHP and gRPC

Check your setup:

    php --version
    composer --version
    php -m | grep grpc

If grpc is not listed, install and enable the extension:

    pecl install grpc

Add extension=grpc to the active php.ini shown by php --ini, then restart your terminal. See the [official gRPC PHP setup guide](https://grpc.io/docs/languages/php/quickstart/) for platform-specific help.

### 2. Download and install

    git clone https://github.com/PassKit/passkit-php-quickstart.git
    cd passkit-php-quickstart
    composer install

You can also download the repository as a ZIP, extract it, and open a terminal in that folder.

### 3. Add PassKit credentials

In PassKit, open Developer Tools, generate SDK Credentials, and download:

- certificate.pem
- key.pem
- ca-chain.pem

Copy the files into certs and create your local environment file:

    cp .env.example .env

The credential PEM files and .env are ignored by Git. Never commit or share them.

If key.pem is encrypted, decrypt a copy using the one-time password chosen when the credentials were generated:

    openssl ec -in certs/key.pem -out certs/key-decrypted.pem

Then set PASSKIT_PRIVATE_KEY=./certs/key-decrypted.pem in .env. Do not overwrite your only encrypted copy.

### 4. Choose the correct region

Open .env and set PASSKIT_ADDRESS:

- grpc.pub1.passkit.io for Europe
- grpc.pub2.passkit.io for the USA

Your PassKit data belongs to one region, so this must match Developer Tools → API Region.

### 5. Test the setup

    composer example -- loyalty

Other choices are coupons, tickets, and flights:

    composer example -- coupons
    composer example -- tickets
    composer example -- flights

All four commands run complete workflows: they create dependent resources, exercise the common methods in the correct order, print wallet pass URLs, and clean up the resources they created. You can also use the shorter Composer aliases:

    composer example:loyalty
    composer example:coupons
    composer example:tickets
    composer example:flights

Set PASSKIT_KEEP_ASSETS=true in .env if you want to inspect the generated resources; you must then delete them manually.

The focused single-method scripts remain available so developers can see individual protobuf requests. Run them from the repository root and replace blank IDs and sample recipient details before making live calls.

## Flights

Flights require an Apple pass certificate uploaded to PassKit. Add its Pass Type Identifier to .env:

    PASSKIT_APPLE_CERTIFICATE=pass.com.example.airline

The flight examples cover templates, carriers, airports, designators, flights, boarding passes, and cleanup methods.

## Shared API

src/PassKitApi.php provides a single, developer-friendly entry point for the PassKit API:

- loyalty and membership
- coupons
- event tickets
- flights
- templates, locations, beacons, links, and images
- analytics, distribution, scheduled messages, and direct messages
- webhook/sink integrations and scanner configuration
- read-only Apple certificate operations
- raw projects and passes

Example:

    $config = PassKit\Quickstart\Config::fromEnvironment(__DIR__);
    $pool = new PassKit\Quickstart\ConnectionPool($config);
    $api = $pool->api();
    $program = $api->loyalty->getProgram(new Io\Id(['id' => 'PROGRAM_ID']));
    $pool->close();

Unary methods return the protobuf response. Streaming methods return an iterator and also have a ToArray variant, such as listProgramsToArray, for small result sets.

Broad operations such as segment deletion and bulk voiding are disabled by default. Set PASSKIT_ALLOW_DESTRUCTIVE=true only in controlled tooling, then use api->advanced('loyalty'), api->advanced('coupons'), or api->advanced('eventTickets').

Administrative account deletion, password or API-secret changes, team administration, certificate upload, and NFC credential submission are intentionally excluded.

Typed helpers are available for batchUpdateMembers, addMessage, getMessages, and cancelMessage. The generated client for any exposed domain is also available through api->client('domain') when you need SDK options not wrapped by the friendly facade.

## Configuration

| Variable | Default | Purpose |
| --- | --- | --- |
| PASSKIT_ADDRESS | grpc.pub1.passkit.io | Account API region |
| PASSKIT_PORT | 443 | gRPC port |
| PASSKIT_ROOT_CERT | ./certs/ca-chain.pem | PassKit CA chain |
| PASSKIT_PRIVATE_KEY | ./certs/key.pem | Client private key |
| PASSKIT_CERTIFICATE | ./certs/certificate.pem | Client certificate |
| PASSKIT_CONNECTION_MODE | pool | pool or single |
| PASSKIT_POOL_SIZE | 5 | Reusable connection count |
| PASSKIT_RECIPIENT_EMAIL | empty | Optional pass delivery email |
| PASSKIT_APPLE_CERTIFICATE | empty | Required for flights |
| PASSKIT_KEEP_ASSETS | false | Preserve generated resources |
| PASSKIT_ALLOW_DESTRUCTIVE | false | Enable broad bulk calls |

## Troubleshooting

- Missing credential file: confirm the three paths in .env and run commands from the repository root.
- Authentication failure: check the API region and ensure all three credential files came from the same SDK credential set.
- Private key error: use the password chosen when SDK Credentials were generated, not your PassKit login password.
- gRPC extension missing: compare php --ini and php -m; CLI PHP can use a different php.ini from a web server.
- Flights skipped: upload an Apple pass certificate and set PASSKIT_APPLE_CERTIFICATE.

## Tests and checks

These checks do not call PassKit:

    composer lint
    composer test
    composer check
    composer security

The parity suite confirms that every method exposed by the PHP facade exists in SDK 1.1.162.

## Help

- [PassKit documentation](https://docs.passkit.io/)
- [PassKit Help Centre](https://help.passkit.com/)
- [PHP SDK](https://github.com/PassKit/passkit-php-grpc-sdk)
- support@passkit.com
