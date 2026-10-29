=== Panelladikes Widgets ===
Tags: panelladikes, calculator, education, greece, widget
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 8.2
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Embed the Panelladikes widgets (points calculator, admission bases, exam schedule, school details, Mixanografiko) with a block or a shortcode.

== Description ==

Panelladikes Widgets shows the widgets of [panelladikes.edu.gr](https://panelladikes.edu.gr) on your WordPress site, for both the General Lyceum (GEL) and the Vocational Lyceum (EPAL):

* **Points calculator** (`calculator`)
* **Admission bases** (`baseis`)
* **Exam schedule** (`program`)
* **School details** (`sxoli`)
* **Mixanografiko** (`mixanografiko`)

Add the **Panelladikes Widget** block in the editor (category Widgets) and see a live preview of the real widget, or use the shortcode:

`[panelladikes type="calculator"]`
`[panelladikes type="sxoli" edu="gel" id="123"]`
`[panelladikes type="baseis" edu="epal" theme="dark" width="60%" align="center"]`

The plugin has no settings page, creates no options (it keeps only one temporary transient of its update check, removed when the plugin is deleted), sets no cookies and runs no analytics.

= Attributes =

The block and the shortcode accept the same attributes. An invalid value falls back to the default.

* `type`: `calculator`, `baseis`, `program`, `sxoli` or `mixanografiko`. Required.
* `edu`: `gel` (default) or `epal`.
* `id`: school number, 1 to 999999. Required for `sxoli` only.
* `pedio`: field, 1 to 4. Applies to GEL only; it is ignored for EPAL.
* `theme`: `auto` (default), `light` or `dark`.
* `width`: `200` to `2000` (pixels) or `10%` to `100%`. Default: full width.
* `align`: `left` (default), `center` or `right`. Only applies when the width is not full.
* `height`: `auto` or `200` to `10000` (pixels). Default: 720 for `calculator` and `mixanografiko`, automatic for the others.
* `radius`: corner radius, 0 to 48 pixels. Default: 12.
* `shadow`: `none` (default), `sm`, `md` or `lg`.
* `border`: `true` or `false`. Default: false.
* `lazy`: `true` (default) or `false`.

= Appearance customization =

Colors, the font and the "Powered by" notice are set per site by the panelladikes.edu.gr team. To ask for them, write to info@panelladikes.edu.gr.

= Support =

Report bugs and suggestions about the plugin code on [GitHub Issues](https://github.com/PanelladikesApp/panelladikes-wordpress/issues). There is no response-time commitment. For questions about the data or the widgets, write to info@panelladikes.edu.gr.

== External services ==

This plugin connects to the following services.

**panelladikes.edu.gr**: pages that contain a widget load the script `https://panelladikes.edu.gr/js/embed.v2.js` and the widget itself, which is shown in an iframe. The visitor's browser contacts this service directly, so it receives the usual details of a web request (for example the IP address and the browser). The service reads the Referer of the iframe, which contains only the origin of your site, to find the site and apply its settings. It counts views per site, widget type and school system, in memory, without the IP address, user agent, full page address or any identifier of the visitor. The script and the widgets run no analytics, and the plugin sets no cookies; the Google services of the Mixanografiko email form (reCAPTCHA, App Check) may set cookies or use local storage. As a rule the widget loads resources from this service; if the appearance of a site has been customized, images, fonts or stylesheets may also be loaded from other sources. The block editor loads the same content for its live preview, and only when the page has a widget block.

**Mixanografiko email form (Google Firebase, App Check, reCAPTCHA Enterprise)**: the form is available only when the service has turned it on; otherwise the widget offers only copy, print and a continue link. When it is on, the visitor's browser writes directly to the database of panelladikes.edu.gr, not through your site and not to you. It sends the list of schools (numbers), the school system, the field or sector, the email address, the language and, only by a separate choice, a newsletter subscription with the version of the consent text. No name or grades are sent. App Check and reCAPTCHA Enterprise load scripts from Google, only in this form. The student's list is kept in the local storage of the iframe (the origin panelladikes.edu.gr, not your site) and after sending only a masked email address. If you use Mixanografiko, mention this in your privacy policy.

**search-paneladikesapp.web.app**: when you search for a school in the block editor, your browser sends the search text and the school type (GEL or EPAL) to this service. No cookies are set and no referrer is sent; as with any request to another origin, the browser sends the Origin header (the address of your site) and the service sees the IP address. If it is not available, you can type the school number instead.

**github.com**: to look for updates the plugin requests a static file from the releases of the plugin's GitHub repository. The request contains no information about your site. The result is cached for 12 hours. Update packages are checked with a sha256 checksum and an Ed25519 signature before they are installed.

In addition, WordPress itself sends the list of plugins of your site to wordpress.org each time it checks for updates. The plugin does not do this.

== Installation ==

1. Download `panelladikes-widgets.zip` from https://github.com/PanelladikesApp/panelladikes-wordpress/releases/latest/download/panelladikes-widgets.zip. Do not use the "Source code (zip)" that GitHub adds automatically.
2. In your dashboard go to Plugins > Add New > Upload Plugin, choose the file and click Install Now.
3. Click Activate.
4. Add the Panelladikes Widget block to a page, or use the `[panelladikes]` shortcode.

== Frequently Asked Questions ==

= The widget does not appear or stays empty. =

* A caching or optimization plugin with a "delay JS" or "defer all scripts" option may block the script. Exclude `panelladikes.edu.gr/js/embed.v2.js` from it.
* If your site sends a strict Content-Security-Policy, it must allow `https://panelladikes.edu.gr` in `script-src` and `frame-src`.
* If your site sends `Referrer-Policy: no-referrer`, the widget cannot recognize the site and uses its default settings.
* For `sxoli`, check that `id` is valid. Without it nothing is shown.

= The editor says the preview could not be loaded. =

The widget still appears on the page. Check your connection and that no content blocker in your browser blocks `panelladikes.edu.gr`.

= The school search does not work. =

Type the school number in the "School number" field.

= Does the plugin store any data on my site? =

Almost nothing. It has no settings, creates no options and sets no cookies. The only thing it keeps is one temporary transient with the description of the latest release (for 12 hours, or 1 hour if the check fails), which is removed when the plugin is deleted.

= What do I need to put in my privacy policy? =

Say that your site shows third-party content (panelladikes.edu.gr) that the visitor's browser loads directly and, if you use Mixanografiko, that its email form, when enabled, sends email addresses to panelladikes.edu.gr and not to you. Suggested text is in docs/privacy.md in the GitHub repository (https://github.com/PanelladikesApp/panelladikes-wordpress/blob/main/docs/privacy.md); it is not part of the plugin package.

= Can I remove "Powered by" or change colors? =

These are set per site by the panelladikes.edu.gr team. Write to info@panelladikes.edu.gr.

== Changelog ==

= 1.0.0 =
* Block "Panelladikes Widget" with live preview and school search.
* Shortcode `[panelladikes]`.
* Widgets: points calculator, admission bases, exam schedule, school details, Mixanografiko, for GEL and EPAL.
* Updates from GitHub Releases, verified with sha256 and an Ed25519 signature.
* Greek translation.

== Upgrade Notice ==

= 1.0.0 =
First release.
