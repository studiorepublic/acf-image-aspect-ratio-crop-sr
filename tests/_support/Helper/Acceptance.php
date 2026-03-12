<?php
namespace Helper;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

use AcceptanceTester;

class Acceptance extends \Codeception\Module
{
    function verifyImage(
        AcceptanceTester $I,
        $comparison_image,
        $width,
        $height
    ) {
        $I->waitForElementVisible(
            '.acf-image-uploader-aspect-ratio-crop div img',
            10
        );
        $img_src = $I->grabAttributeFrom('.acf-image-uploader-aspect-ratio-crop div img', 'src');
        codecept_debug($img_src);
        if (version_compare(PHP_VERSION, '7.1.0', '>=')) {
            $this->assertStringContainsString('aiarc/v1/preview', $img_src);
        } else {
            $this->assertTrue(strpos($img_src, 'aiarc/v1/preview') !== false);
        }
    }

    public function assertEqualsWithDeltaCompat($expected, $actual, $delta)
    {
        if (version_compare(PHP_VERSION, '7.1.0', '>=')) {
            $this->assertEqualsWithDelta($expected, $actual, $delta);
        } elseif (
            version_compare(PHP_VERSION, '7.0.0', '>=') &&
            version_compare(PHP_VERSION, '7.1.0', '<')
        ) {
            \PHPUnit\Framework\Assert::assertEquals(
                $expected,
                $actual,
                '',
                $delta
            );
        } else {
            \PHPUnit_Framework_Assert::assertEquals(
                $expected,
                $actual,
                '',
                $delta
            );
        }
    }
}
