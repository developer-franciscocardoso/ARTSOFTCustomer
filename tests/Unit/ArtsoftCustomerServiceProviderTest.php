<?php

declare(strict_types=1);

namespace FranciscoCardoso\ARTSOFTCustomer\Tests\Unit;

use FranciscoCardoso\ARTSOFTCustomer\ServiceProviders\ArtsoftCustomerServiceProvider;
use PHPUnit\Framework\TestCase;

final class ArtsoftCustomerServiceProviderTest extends TestCase
{
    public function testPublishConfigCopiesBundledConfigToTargetPath(): void
    {
        $provider = new ArtsoftCustomerServiceProvider();
        $sourcePath = $provider->getSourceConfigPath();

        $targetDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'artsoft-customer-' . uniqid('', true);
        $targetPath = $targetDirectory . DIRECTORY_SEPARATOR . 'customer.php';

        try {
            $publishedPath = $provider->publishConfig($targetPath);

            self::assertSame($targetPath, $publishedPath);
            self::assertFileExists($targetPath);
            self::assertSame(file_get_contents($sourcePath), file_get_contents($targetPath));
        } finally {
            if (is_file($targetPath)) {
                unlink($targetPath);
            }

            if (is_dir($targetDirectory)) {
                rmdir($targetDirectory);
            }
        }
    }
}
