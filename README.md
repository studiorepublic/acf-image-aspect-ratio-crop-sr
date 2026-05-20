# ACF Image Aspect Ratio Crop Field (Studio Republic)

A field for Advanced Custom Fields that forces the user to crop their image to a specific aspect ratio or pixel size after uploading. Using an aspect ratio is especially useful in responsive image use cases.

This **Studio Republic fork** ([`acf-image-aspect-ratio-crop-sr`](https://github.com/studiorepublic/acf-image-aspect-ratio-crop-sr)) extends the [original WordPress plugin](https://wordpress.org/plugins/acf-image-aspect-ratio-crop/) by [Johannes Siipola](https://github.com/joppuyo/acf-image-aspect-ratio-crop). It stores **crop metadata only** (no cropped image files in the media library), generates crops on the front end, and adds an optional **focal point** inside the crop selection.

## Stored value format

The field returns an array (not a cropped attachment ID):

```php
[
    'attachment_id' => 123,           // Original image attachment ID
    'original_url'  => 'https://…', // URL of the original upload
    'crop' => [
        'x' => 120,
        'y' => 80,
        'width' => 1600,
        'height' => 900,
        'focal_point' => [
            'x' => 50.0,              // Percent from left of crop box (0–100)
            'y' => 50.0,              // Percent from top of crop box (0–100)
        ],
    ],
    'aspect_ratio' => '16:9',
]
```

Use [Timber](https://timber.github.io/) (`|aiarc_crop`) or `aiarc_crop_url()` to output cropped image URLs. Use `crop.focal_point` (or `aiarc_get_focal_point()`) for CSS `object-position` or Cloudflare `gravity` when recropping to other sizes.

Legacy posts without `focal_point` default to **50%, 50%** (center).

## Modes of operation

There are three modes of operation: aspect ratio, pixel size and free crop. You can select this option when creating the field in ACF field options.

### Aspect ratio

Use this option if you want the image to be of specific aspect ratio like 16:9 but the pixel size is not important.

After selecting an image, the user selects an area that matches the aspect ratio. The crop coordinates are saved as metadata; the front end renders the crop via `aiarc_crop_url()` or Timber.

### Pixel size

Use this option if you need a specific pixel size image like 640×480. The selection cannot be smaller than the defined size; aspect ratio is locked to the pixel dimensions.

When the crop is confirmed, metadata reflects the selected region. Scaling to the target pixel size happens when generating URLs (or via Cloudflare transforms).

### Free crop

Crop can be done freely, with no fixed aspect ratio constraint.

## Focal point

While cropping in the admin, a **focal marker** appears inside the crop box (default: center). Drag it to choose which part of the crop should stay visible when the image is reframed to a different aspect ratio or placed in a `object-fit: cover` container.

- Stored as `crop.focal_point.x` and `crop.focal_point.y` (percentages **relative to the crop rectangle**, 0–100).
- The marker cannot be dragged outside the crop box.
- **Reset crop** restores the focal point to center (50, 50).

### CSS (any host)

Use the original image URL with `object-fit: cover` and focal percentages as `object-position`:

```twig
{% set hero = post.hero_image %}
{% if hero %}
  {% set fp = hero.crop.focal_point|default({ x: 50, y: 50 }) %}
  <img
    src="{{ hero.original_url }}"
    alt="{{ post.title }}"
    style="object-fit: cover; object-position: {{ fp.x }}% {{ fp.y }}%;"
  />
{% endif %}
```

```php
$hero = get_field('hero_image');
echo aiarc_object_position_style($hero, true); // style="object-position: 50% 50%;"
```

```twig
<img src="{{ hero|aiarc_crop(800) }}" alt=""{{ hero|aiarc_object_position_style(object_position) }} />
```

### Cloudflare Image Transformations

When **Use Cloudflare Image Transformations** is enabled (Settings → ACF Image Aspect Ratio Crop) and the site is behind the Cloudflare proxy:

- `aiarc_crop_url()` / `|aiarc_crop` — trim to the stored crop (and optional max dimensions). **Format** (AVIF, WebP, or JPEG) and **quality** (1–100, default 90) are set under Settings → ACF Image Aspect Ratio Crop; used in Cloudflare URLs as `format=` / `quality=` and for files in `uploads/aiarc-cache/` (quality via `wp_editor_set_quality`). Filters: `aiarc_crop_output_format`, `aiarc_crop_output_quality`. `aiarc_crop_source_mime_type()` returns the matching MIME type for `<picture>` sources. With Cloudflare enabled, both max width and height use `fit=cover` and focal **gravity** automatically.
- `aiarc_cloudflare_recrop_url($hero, $width, $height)` — same recrop URL builder used internally by `aiarc_crop_url()` when both dimensions are passed.
- `aiarc_get_focal_gravity($hero)` — returns a Cloudflare gravity string (e.g. `0.5x0.5` for center).

Requires [Image Resizing](https://developers.cloudflare.com/images/transform-images/) on your Cloudflare zone.

## Timber usage

`|aiarc_crop` and `aiarc_crop_url()` accept **AIARC crop metadata**, a standard **ACF image** array, an **attachment ID**, an image **URL string**, or a **Timber image** object. Non-cropped values return the image URL (optionally scaled to max width/height).

```twig
{% set hero = post.hero_image %}
{% if hero %}
  <img src="{{ hero|aiarc_crop }}" alt="{{ post.title }}" />
  {# Responsive: max 800px wide #}
  <img src="{{ hero|aiarc_crop(800) }}" alt="{{ post.title }}" />
{% endif %}

{# Same filter works for a standard ACF image field on another post #}
<img src="{{ post.thumbnail|aiarc_crop(400) }}" alt="">
```

```php
$hero = get_field('hero_image');
$url = aiarc_crop_url($hero);
$responsive_url = aiarc_crop_url($hero, 800);

// Attachment ID or URL also work
$url = aiarc_crop_url(123, 800);
$url = aiarc_crop_url('https://example.com/photo.jpg', 1200);
```

## Upgrading from the original plugin

The [WordPress.org plugin](https://wordpress.org/plugins/acf-image-aspect-ratio-crop/) (and older GitHub releases before the metadata-only change) **created a new cropped attachment** in the media library and typically stored its **attachment ID** in the ACF field. This fork **does not** create cropped files; it stores the array structure above and builds image URLs at runtime.

**Migration is one-way.** After content is saved in the new format, switching back to the original plugin will not restore correct behaviour.

### Before you switch

1. **Back up the database** (and uploads if you rely on cropped files elsewhere).
2. **Audit templates** that treat the field as an image ID or URL from a cropped attachment.
3. Plan how crops will be rendered: `aiarc_crop_url()`, Timber `|aiarc_crop`, Cloudflare transforms, or CSS `object-position` with `original_url`.

### Install this fork

1. Deactivate the original **ACF Image Aspect Ratio Crop** plugin (do not run both).
2. Install [acf-image-aspect-ratio-crop-sr](https://github.com/studiorepublic/acf-image-aspect-ratio-crop-sr) from a [release zip](https://github.com/studiorepublic/acf-image-aspect-ratio-crop-sr/releases) or your deployment process.
3. ACF field groups are unchanged — the field type remains `image_aspect_ratio_crop`.

Updates from GitHub use [plugin-update-checker](https://github.com/YahnisElsts/plugin-update-checker). Override the repo with:

```php
add_filter('aiarc_update_repo_url', function ($url) {
    return 'https://github.com/studiorepublic/acf-image-aspect-ratio-crop-sr';
});
```

### Automatic data migration

When a post (or options page, etc.) is **loaded in the admin**, legacy values are converted automatically:

| Legacy stored value | Migration behaviour |
|---------------------|---------------------|
| Numeric attachment ID (cropped image) | Reads `acf_image_aspect_ratio_crop_original_image_id` and `acf_image_aspect_ratio_crop_coordinates` from that attachment, builds metadata from the **original** image, sets `focal_point` to 50, 50. |
| JSON from [ACF Image Crop](https://wordpress.org/plugins/acf-image-crop-add-on/) | Uses `original_image` / `cropped_image` IDs and coordinates when present. |
| Already metadata array | Used as-is; missing `focal_point` defaults to 50, 50 when read. |

The migrated value is **written back** when the field loads in the admin (`acf_update_value` on load). Saving the post persists the new format. You do not need a separate migration script for standard cases.

**Recommendation:** Open important posts in the admin and save once (or bulk-edit) so metadata is stored before relying on front-end URL generation. Re-open crops to set a focal point if needed (legacy migrations use center).

### Update theme and template code

**Original (cropped attachment ID):**

```php
$image_id = get_field('hero_image'); // int — cropped attachment
$url = wp_get_attachment_image_url($image_id, 'large');
echo '<img src="' . esc_url($url) . '" alt="">';
```

**This fork (metadata array):**

```php
$hero = get_field('hero_image');
if ($hero && !empty($hero['crop'])) {
    echo '<img src="' . esc_url(aiarc_crop_url($hero, 1200)) . '" alt="">';
}
```

**Timber:**

```twig
{# Before: often treated as attachment ID #}
<img src="{{ Image(hero_image).src }}" alt="">

{# After #}
<img src="{{ hero_image|aiarc_crop(1200) }}" alt="">
```

**Checks in PHP:** use `function_exists('aiarc_crop_url')` or test for an array with `crop` keys instead of `is_numeric(get_field(...))`.

### Cropped files in the media library

Old **cropped attachments are not deleted** by this plugin. They remain in the media library but are no longer referenced by the field after migration. You may clean them up manually once you have verified front-end output.

### Focal point after upgrade

- Migrated fields get `focal_point` at **50, 50** until an editor re-crops and moves the marker.
- For responsive layouts using `object-position`, re-crop important images or accept centered focal until updated.
- For Cloudflare recrops to new aspect ratios, set focal point before relying on `aiarc_cloudflare_recrop_url()`.

### Cloudflare

If you enable Cloudflare Image Transformations after upgrading, update templates from static cropped URLs to `aiarc_crop_url()` or `aiarc_cloudflare_recrop_url()` as appropriate. Test on a staging environment behind the orange-cloud proxy first.

### Rollback

Rolling back to the original plugin after metadata is saved is **not supported** without restoring a database backup. The original plugin expects a cropped attachment ID, not the metadata array.

## Screenshots

### Cropping an image to 16:9 aspect ratio

![Screenshot of cropping an image](./.wordpress-org/screenshot-1.jpg)

### Cropping in progress

![Screenshot of cropping in progress](./.wordpress-org/screenshot-2.jpg)

### Option to re-crop the image after upload

![Screenshot of the image field](./.wordpress-org/screenshot-3.jpg)

## Download

- **This fork:** [GitHub releases — studiorepublic/acf-image-aspect-ratio-crop-sr](https://github.com/studiorepublic/acf-image-aspect-ratio-crop-sr/releases)
  - Install **`acf-image-aspect-ratio-crop-sr.zip`** from the release assets (built JS/CSS included).
  - Do **not** use the auto-generated “Source code” archive — it has no `assets/dist/` files.
- **Original:** [WordPress plugin directory](https://wordpress.org/plugins/acf-image-aspect-ratio-crop/) or [joppuyo/acf-image-aspect-ratio-crop](https://github.com/joppuyo/acf-image-aspect-ratio-crop/releases)

## Requirements

- WordPress 4.9 or later
- PHP 5.6 or later (PHP 7.4+ recommended)
- Advanced Custom Fields 5.8 or later (Pro or Free)
- Node 20+ to build admin assets (`npm run build`)

## Compatibility

- Polylang Pro
- Enable Media Replace
- WP Offload Media, Media Cloud and other plugins that move media files to a remote location
- **Cloudflare Image Transformations** — Settings → ACF Image Aspect Ratio Crop. Serves crops via `/cdn-cgi/image/` when the site is behind the Cloudflare proxy. Requires Image Resizing in the Cloudflare dashboard.
- **Crop output quality** — Settings → ACF Image Aspect Ratio Crop (1–100, default 90). Applies to Cloudflare transform URLs and local disk cache.

## Frequently Asked Questions

### Can I use this plugin with a front-end acf_form?

Yes, this functionality has been available since version 5.0.0 of the original plugin. Please test on your forms and report issues on the GitHub repo.

### Can I access metadata from the original image?

Yes. The field value includes `attachment_id` and `original_url` for the **uncropped** upload. Use `get_post_meta($hero['attachment_id'], '_wp_attachment_image_alt', true)` for alt text, or ACF on that attachment if configured.

### Can I use this plugin with Elementor?

Elementor only supports built-in ACF fields out of the box. See [this support thread](https://wordpress.org/support/topic/excellent-plugin-5518/) for possible workarounds.

### Can I use this plugin with Beaver Builder?

Beaver Builder has limited third-party ACF field support. [Toolbox For Beaver Builder](https://beaverplugins.com/) is a common workaround.

### How is this different from ACF Image Crop?

This plugin was created as an alternative to [Advanced Custom Fields: Image Crop Add-on](https://wordpress.org/plugins/acf-image-crop-add-on/), with aspect ratio and pixel size modes. The Studio Republic fork adds metadata-only storage, on-the-fly crops, Cloudflare transforms, and focal point support.

## Thanks

Thanks to Anders Thorborg for [ACF Image Crop](https://github.com/andersthorborg/ACF-Image-Crop), Johannes Siipola for the original **ACF Image Aspect Ratio Crop**, and Fengyuan Chen for [Cropper.js](https://fengyuanchen.github.io/cropperjs/).

## License

GPL v2 or later
