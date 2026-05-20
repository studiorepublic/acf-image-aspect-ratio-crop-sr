<?php

class FocalPointTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    /**
     * @var array<string, mixed>|null
     */
    private static $test_plugin_settings;

    protected function _before()
    {
        self::$test_plugin_settings = null;
        if (!defined('ABSPATH')) {
            define('ABSPATH', dirname(__DIR__, 2) . '/');
        }
        if (!defined('AIARC_UNIT_TEST')) {
            define('AIARC_UNIT_TEST', true);
        }
        if (!function_exists('esc_attr')) {
            function esc_attr($text)
            {
                return (string) $text;
            }
        }
        if (!function_exists('wp_image_editor_supports')) {
            function wp_image_editor_supports($args = [])
            {
                return false;
            }
        }
        if (!function_exists('get_option')) {
            function get_option($option, $default = false)
            {
                if (
                    $option === 'acf-image-aspect-ratio-crop-settings' &&
                    FocalPointTest::$test_plugin_settings !== null
                ) {
                    return FocalPointTest::$test_plugin_settings;
                }

                return $default;
            }
        }
        if (!function_exists('apply_filters')) {
            function apply_filters($hook, $value)
            {
                return $value;
            }
        }
        if (!function_exists('aiarc_normalize_focal_point')) {
            require_once dirname(__DIR__, 2) . '/acf-image-aspect-ratio-crop-sr.php';
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

    public function testObjectPositionStyleOutputsAttribute()
    {
        $crop_data = [
            'attachment_id' => 1,
            'crop' => [
                'x' => 0,
                'y' => 0,
                'width' => 100,
                'height' => 50,
                'focal_point' => ['x' => 25, 'y' => 75],
            ],
        ];

        $this->assertSame(
            ' style="object-position: 25% 75%;"',
            aiarc_object_position_style($crop_data)
        );
    }

    public function testObjectPositionStyleEmptyWhenDisabledOrNotCropData()
    {
        $crop_data = [
            'attachment_id' => 1,
            'crop' => ['x' => 0, 'y' => 0, 'width' => 100, 'height' => 50],
        ];

        $this->assertSame('', aiarc_object_position_style($crop_data, false));
        $this->assertSame('', aiarc_object_position_style(['ID' => 1, 'url' => 'https://example.com/a.jpg']));
    }

    public function testCropOutputFormatFromPluginSettings()
    {
        self::$test_plugin_settings = [
            'crop_output_format' => 'webp',
        ];

        $this->assertSame('webp', aiarc_crop_output_format());
    }

    public function testCropOutputFormatSettingRejectsInvalidValue()
    {
        self::$test_plugin_settings = [
            'crop_output_format' => 'png',
        ];

        $this->assertSame('avif', aiarc_crop_output_format());
    }

    public function testCropOutputQualityFromPluginSettings()
    {
        self::$test_plugin_settings = [
            'crop_output_quality' => 75,
        ];

        $this->assertSame(75, aiarc_crop_output_quality());
    }

    public function testCloudflareRecropUrlUsesFormatFromSettings()
    {
        self::$test_plugin_settings = [
            'crop_output_format' => 'webp',
            'crop_output_quality' => 90,
        ];

        $crop_data = [
            'original_url' => 'https://example.com/image.jpg',
            'crop' => [
                'x' => 10,
                'y' => 20,
                'width' => 800,
                'height' => 450,
            ],
        ];

        $url = aiarc_cloudflare_recrop_url($crop_data, 400, 400);
        $this->assertStringContainsString('format=webp', $url);
    }

    public function testCloudflareRecropUrlUsesQualityFromSettings()
    {
        self::$test_plugin_settings = [
            'crop_output_quality' => 75,
        ];

        $crop_data = [
            'original_url' => 'https://example.com/image.jpg',
            'crop' => [
                'x' => 10,
                'y' => 20,
                'width' => 800,
                'height' => 450,
            ],
        ];

        $url = aiarc_cloudflare_recrop_url($crop_data, 400, 400);
        $this->assertStringContainsString('quality=75', $url);
    }

    public function testCloudflareRecropUrlIncludesGravity()
    {
        self::$test_plugin_settings = [
            'crop_output_quality' => 90,
        ];

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
        $this->assertStringContainsString('format=avif', $url);
        $this->assertStringContainsString('quality=90', $url);
    }

    public function testCropFormatFallbackChainForAvif()
    {
        $this->assertSame(['avif', 'webp', 'jpeg'], aiarc_crop_format_fallback_chain('avif'));
        $this->assertSame(['webp', 'jpeg'], aiarc_crop_format_fallback_chain('webp'));
    }

    public function testCropCacheExtensionMapsFormats()
    {
        $this->assertSame('avif', aiarc_crop_cache_extension('avif'));
        $this->assertSame('webp', aiarc_crop_cache_extension('webp'));
        $this->assertSame('jpg', aiarc_crop_cache_extension('jpeg'));
    }

    public function testResolveDiskCacheFormatFallsBackToJpegWithoutEditorSupport()
    {
        $this->assertSame('jpeg', aiarc_resolve_disk_cache_format());
    }

    public function testCropSourceMimeTypeDefaultsToAvifOnCloudflarePath()
    {
        $_SERVER['HTTP_CF_RAY'] = 'test';
        $this->assertSame('image/avif', aiarc_crop_source_mime_type());
        unset($_SERVER['HTTP_CF_RAY']);
    }

    public function testIsCropDataRecognizesMetadata()
    {
        $this->assertTrue(
            aiarc_is_crop_data([
                'attachment_id' => 1,
                'crop' => ['x' => 0, 'y' => 0, 'width' => 100, 'height' => 50],
            ])
        );
    }

    public function testIsCropDataRejectsStandardAcfImageArray()
    {
        $this->assertFalse(
            aiarc_is_crop_data([
                'ID' => 1,
                'url' => 'https://example.com/photo.jpg',
            ])
        );
    }
}
