=== Light and Dark Mode Images ===
Contributors: lwitsolutions
Tags: dark mode, images, featured image, prefers-color-scheme, picture
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.1.0
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
* **Theme switches:** if the theme has its own light/dark toggle, enter the CSS selectors it sets (for example `html.dark` and `html.light`). A small script then follows the visitor's choice, also for content loaded later. Without selectors no script is loaded.

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

= 0.1.0 =
* First release: second image for featured images and Image blocks, light or dark as the base scheme, optional theme switch selectors.
