<?php

declare(strict_types=1);

namespace PassKit\Quickstart\Tests;

use PassKit\Quickstart\Env;
use PHPUnit\Framework\TestCase;

final class EnvTest extends TestCase
{
    public function testLoadsValuesWithoutOverwritingEnvironment(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'passkit-env-');
        self::assertNotFalse($file);
        file_put_contents($file, "PASSKIT_TEST_EXISTING=file\nPASSKIT_TEST_NEW='new value'\n");
        putenv('PASSKIT_TEST_EXISTING=environment');
        putenv('PASSKIT_TEST_NEW');

        Env::load($file);

        self::assertSame('environment', getenv('PASSKIT_TEST_EXISTING'));
        self::assertSame('new value', getenv('PASSKIT_TEST_NEW'));
        unlink($file);
        putenv('PASSKIT_TEST_EXISTING');
        putenv('PASSKIT_TEST_NEW');
    }
}
