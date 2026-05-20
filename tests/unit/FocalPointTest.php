<?php

class FocalPointTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    protected function _before()
    {
        if (!defined('ABSPATH')) {
            define('ABSPATH', dirname(__DIR__, 2) . '/');
        }
        if (!defined('AIARC_UNIT_TEST')) {
            define('AIARC_UNIT_TEST', true);
        }
        if (!function_exists('aiarc_normalize_focal_point')) {
            require_once dirname(__DIR__, 2) . '/acf-image-aspect-ratio-crop.php';
        }
    }

    public function testNormalizeFocalPointDefaultsToCenter()
    {
        $fp = aiarc_normalize_focal_point(null, null);
        $this->assertSame(50.0, $fp['x']);
        $this->assertSame(50.0, $fp['y']);
    }

    public function testNormalizeFocalPointClampsAndRounds()
    {
        $fp = aiarc_normalize_focal_point(-10, 150.456);
        $this->assertSame(0.0, $fp['x']);
        $this->assertSame(100.0, $fp['y']);
    }

    public function testGetFocalGravityFromPercentages()
    {
        $crop_data = [
            'crop' => [
                'focal_point' => ['x' => 50, 'y' => 50],
            ],
        ];
        $this->assertSame('0.500x0.500', aiarc_get_focal_gravity($crop_data));

        $crop_data['crop']['focal_point'] = ['x' => 0, 'y' => 100];
        $this->assertSame('0.000x1.000', aiarc_get_focal_gravity($crop_data));
    }

    public function testGetFocalPointDefaultsWhenMissing()
    {
        $fp = aiarc_get_focal_point(['crop' => ['x' => 0, 'y' => 0, 'width' => 100, 'height' => 100]]);
        $this->assertSame(50.0, $fp['x']);
        $this->assertSame(50.0, $fp['y']);
    }

    public function testCloudflareRecropUrlIncludesGravity()
    {
        $crop_data = [
            'original_url' => 'https://example.com/image.jpg',
            'crop' => [
                'x' => 10,
                'y' => 20,
                'width' => 800,
                'height' => 450,
                'focal_point' => ['x' => 25, 'y' => 75],
            ],
        ];

        $url = aiarc_cloudflare_recrop_url($crop_data, 400, 400);
        $this->assertStringContainsString('gravity=0.250x0.750', $url);
        $this->assertStringContainsString('fit=cover', $url);
        $this->assertStringContainsString('trim.left=10', $url);
    }
}
