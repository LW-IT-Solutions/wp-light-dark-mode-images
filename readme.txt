=== Light and Dark Mode Images ===
Contributors: lwitsolutions
Tags: dark mode, images, featured image, prefers-color-scheme, picture
Requires at least: 6.5
Tested up to: 7.2
Requires PHP: 7.4
Stable tag: 0.3.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Give the featured image and Image blocks a second version for the other color scheme. Visitors in dark or light mode see the matching image.

== Description ==

A diagram drawn on white glares on a dark site, and a dark screenshot disappears on a light one. Light and Dark Mode
Images lets authors add a second version of an image for the other color scheme. The browser picks the version that
matches the visitor's light or dark mode, without JavaScript and without loading both images.

* **Featured image:** a panel "Featured image for dark mode" in the post sidebar (a meta box in the classic editor).
* **Image block:** a panel "Image for dark mode" in the block settings.
* **Output:** a standard `<picture>` element with a `<source media="(prefers-color-scheme: dark)">` and the regular `<img>` as fallback, including `srcset`, `sizes`, `width` and `height` of the second image.
* **Either direction:** if the site is dark by default, switch the setting and add light versions instead.
* **Theme switches:** if the theme has its own light/dark toggle, enter the CSS selectors it sets (for example `html.dark` and `html.light`). A small script then follows the visitor's choice, also for images added to the page later. It is loaded only on pages that are output with at least one image that has a second version; without selectors no script is loaded.

Posts without a second image are output exactly as before.

= Scope and limits =

* Featured images printed through `the_post_thumbnail()` or the Post Featured Image block, and Image blocks (`core/image`). Gallery, Cover and Media & Text blocks, background images and images in classic content are not changed.
* The second image should have the same aspect ratio as the regular one; the `<img>` keeps its own width and height attributes.
* The Image block lightbox (Expand on click) enlarges the regular image.
* Caches and CDNs keep working: both versions are in the same HTML, the browser decides.

= Privacy =

No external requests, telemetry, cookies or visitor tracking. The plugin stores one option and, per post, the ID of the second featured image; Image blocks keep the ID in their own block attributes.

== Installation ==

1. Upload the light-dark-mode-images folder to wp-content/plugins, or install the ZIP, and activate it.
2. Optional: under Settings > Media, choose whether your regular images are made for light or dark mode and enter the selectors of a theme switch.
3. Edit a post: choose the second featured image in the sidebar, or select an Image block and choose its second image in the block settings.

== Frequently Asked Questions ==

= Does the visitor download both images? =

No. Browsers download only the source that matches the current media query. Without a second image nothing changes.

= My theme has a light/dark button. Does the image follow it? =

Yes, if you enter the CSS selectors the theme sets on the page, for example `[data-theme="dark"]` and `[data-theme="light"]`. When neither selector matches, the system setting decides again.

= What happens to existing content when I deactivate the plugin? =

The regular images are shown as before. The block attribute stays in the content and is ignored by WordPress. Uninstalling removes the setting and the stored featured image references; the image files stay in the media library.

== Screenshots ==

1. The Image block with its second image for dark mode in the block settings.
2. The same post with the system set to light mode and to dark mode: the browser picks the matching image.
3. Settings under Settings > Media: base color scheme and optional selectors for a theme switch.

== Changelog ==

= 0.3.0 =
* Settings > Media: "Preview as" switches the example screens between this device, light and dark mode and names the image a visitor in that mode gets.
* The example screens show a real pair of images from a recent post, when one exists.
* New coverage figures: how many featured images and Image blocks of the latest 200 published posts and pages have a second image, as rings with the share.
* A list of recent posts still without a second image, each with a link to its editor; a check mark when every image has one.
* Block editor: a slider wipes between the regular and the second image, each on the background of its color scheme.
* Classic editor: the meta box shows the regular and the second featured image side by side and checks their aspect ratios.
* No change to the output of the front end or to what is stored.

= 0.2.0 =
* Settings > Media: two small example screens show which image visitors get in light and in dark mode, and follow the chosen base scheme right away.
* The base scheme is chosen from two cards with a sun and a moon symbol.
* The selector fields check the CSS syntax while typing and offer common selectors of theme switches with one click.
* Block editor: the panels show the regular and the second image side by side on light and dark backgrounds.
* Block editor: a warning appears when the two images have different aspect ratios.
* Classic editor: the preview of the second image sits on a background of its color scheme.
* The editor reads the images through getEntityRecord instead of the deprecated getMedia, so WordPress 6.9 and later log no deprecation notice.
* The Settings link on the Plugins screen opens the plugin section directly. Front-end output is unchanged.

= 0.1.1 =
* The script for theme switches is loaded only on pages that contain an image with a second version.
* The classic editor meta box prints its preview through wp_kses_post() and styles it with a class instead of inline styles.

= 0.1.0 =
* First release: second image for featured images and Image blocks, light or dark as the base scheme, optional theme switch selectors.
