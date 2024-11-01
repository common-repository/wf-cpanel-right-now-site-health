=== WebFacing™ - Storage, resource usage and errors in cPanel® ===
Contributors: knutsp, proisp
Donate link: https://paypal.me/knutsp
Tags: disk-space, security, server, cpanel
Requires at least: 5.7.1
Tested up to: 6.5.3
Stable tag: 3.8
Requires PHP: 7.4
License: GPLv2
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Shows storage information, recommendations and alerts in your dashboard At a Glance widget and on Site Health panel tabs.

== Description ==

🕸️ By [WebFacing™](https://webfacing.eu/). Shows disk usage, memory, cpu and processes resource usage information, recommendations and alerts, plus number of created email accounts, in your admin Dashboard At a Glance widget, a special Dashboard Gauges widget, plus error logs and extensive info on Tools - Site Health panels.

Resource gauges in custom dasboard widget.

Includes Server Error log test in Site Health.

Includes a Site Health test for HTTPS only (http should not be allowed, but forwarded to https) with information about the issue, recommendation and actions.

Made with a little help from [cPanel, L.L.C., USA](http://www.cpanel.net/) and [PRO ISP AS, Norway](https://proisp.eu/) - many thanks.

See also [WebFacing™ – Email Accounts management for cPanel®](https://wordpress.org/plugins/wf-cpanel-email-accounts/)

## This plugin adds the following:

### Dashboard

#### At a Glance (core widget)
 - One item showing used disk space
 - One line widget footer line mentioning the name of your web hosting provider, and maximum disk space for current plan/account (on PRO ISP only)

#### cPanel Resource Usage (custom widget)
 - Memory usage (gauge)
 - CPU usage (gauge)
 - Number of active Entry Processes (gauge)
 - Disk in/out usage (gauge)
 - cPanel® Server Errors last 24 hours

### Tools - Site Health panel

#### Status (tab)

- A disk space test with explaining text and possible actions (cPanel® only) with following result types and actions
	* Good (less than 90%)
	* Recommended (over 90%, but less than 95%)
	* Critical (over 95%)
- A HTTPS only test with explaining text (with HTTPS enabled only), actions with link to a relevant guide (special guide in case PRO ISP), and with the following result types and actions
	* Good (http loopback requests are rejected)
	* Recommended fix (http loopback requests successful)
- A Sever Error test with error log
	- Good (no errors last 24 hours)
	- Recommended (less than 25 errors)
	- Critical (more than 24 errors)

#### Info (tab)

- A disk space section containing (cPanel® only)

	- cPanel® user name (private)
	- cPanel® user subaccounts and assigned services (private)
	- Two Factor Authentication enabled in cPanel®?
	- Max disk space available
	- Total disk space used
		- Disk used by media files
	- Main domain in cPanel®
		- Addon domains
		- Parked domains
		- Dead domains
	- MySQL® Disk Usage
	- CPU Usage
	- Entry Processes
	- Physical Memory Usage
	- In/Out Operations Per Second (IOPS)
	- In/Out Usage
	- Number of Processes
	- Number of cPanel® Server Errors
	- Contact email addresses in cPanel®

- Adds one line to the WordPress Server values section

	- Number of cPanel® Server Errors

- Adds some lines to the WordPress Constant values section

	- `WP_`[`CONTENT`|`PLUGIN`]`_URL`s
	- `WPMU_PLUGIN_`[`DIR`|`URL`]
	- `WP_TEMP_DIR`
	- `UPLOADS`
	- `WP_DEFAULT_THEME`
	- `MEDIA_TRASH`
	- `IMAGE_EDIT_OVERWRITE`
	- [`TEMPLATE`|`STYLESHEET`]`PATH`s
	- `COOKIE_DOMAIN`
	- [`SITE`]`COOKIEPATH`
	- `COOKIEHASH`
	- `*_COOKIE`s
	- `*_COOKIE_PATH`s
	- `FORCE_SSL_`[`ADMIN`|`LOGIN`]
	- `WP_DISABLE_FATAL_ERROR_HANDLER`
	- `RECOVERY_MODE_EMAIL`
	- `AUTOMATIC_UPDATER_DISABLED`
	- `WP_AUTO_UPDATE_CORE`
	- `ALLOW_`[`UNFILTERED_UPLOADS`|`REPAIR`]
	- `CORE_UPGRADE_SKIP_NEW_BUNDLED`
	- `DISALLOW_UNFILTERED_HTML`
	- `DISALLOW_FILE_`[`MOD`|`EDIT`]`S`
	- `WP_POST_REVISIONS`
	- `EMPTY_TRASH_DAYS`
	- `AUTOSAVE_INTERVAL`
	- `WP_LOCAL_DEV`
	- `SAVEQUERIES`
	- `RELOCATE`
	- [`DISABLE_`|`ALTERNATE_`]`WP_CRON`[`_LOCK_TIMEOUT`]
	- `CUSTOM_USER_`[`META_`]`TABLE`s
	- [`ALLOW_`]`MULTISITE`

## Translation ready, ready translations are
- Norwegian (Bokmål)

## Filter interval for fetching data for Dashboard Gauges widget
`wf_cpanel_gauges_interval` (default: 10 seconds)

## Filter capability for showing resource gauges widget
`wf_cpanel_widget_capability` (default: `manage_options`)

## Debug setting

For extra debug information, add this line to your `wp-config.php` or in another plugin:

`const WF_DEBUG = true;`

## Known limitations

- Requires PHP `shell_exec´ to be available
- Reports data from your own cPanel® server, not remote
- Reports data from your cPanel® server, all sites and all other web or cPanel® applications, in case more than your WordPress is installed on it
- Links to documentation to resolve reported issues are shown to PRO ISP AS customers only.
- This plugin will not show much if the site is not on a cPanel® managed server, but will do the test for HTTPS only.

== Frequently Asked Questions ==

= Does this plugin add database tables, store options, scheduled tasks or writing to `wp-config.php`? =

No, not, none, no.

= Does it require my login information to cPanel®? =

No.

= Does work if ´shell_exec´ function is disabled in PHP? =

No.

= What if I don't want the cPanel® Resource Usage widget (gauges)?

Close it with the up arrow icon, or hide it using Screen Options (the top right hidden panel) - as usual. No data will be fetched from cPanel® while widget is closed or hidden.

= Does it work on other web hosts than PRO ISP? =

Yes, should work on any cPanel® hosting.

= Does it work without cPanel®? =

Very, very limited. The 'HTTPS only' security test should work, and disk used info, but max space test will not be performed and the result will just show 'N/A'.

= Can I contribute to this plugin? =

No longer. Please see [WebFacing™ – cPanel® Email Accounts management &amp; Account backup](https://wordpress.org/plugins/wf-cpanel-email-accounts/) 

= Can I donate to the continued maintenance and further development of this plugin? =

No. Use the Donate button in the right sidebar on the other recommended plugin page.

== Screenshots ==

1. Dashboard - At a Glance widget
2. Site Health Disk Space Test
3. Site Health HTTPS only test
4. Dashboard - Resource usage &amp; errors widget

== Changelog ==

= 3.8 =

 - Released May 14, 2024
 - Plugin now requires plugin wf-cpanel-email-accounts
 - Some plugin features no longer working on cPanel version 120
 - Plugin will be closed May 21, 2024
 - This the final and last release. Please delete it and use wf-cpanel-email-accounts only

= 3.7.1 =

- Fix: Main::to_bytes returning string in some cases

= 3.7.0 =

- Fix: self::$host_label must not be accessed before initialized

= 3.6.9 =

- Fix: Site Health Info: Plugin auto updated
- Fix: Namespace the 'PLUGIN_BASENAME' constant

= 3.6.4 =

- Bugfix: Site Health Info section title mess

= 3.6.2 =

- Apr 19, 2023
- Constants cleanup in Site Health Info

= 3.6 =

- Feb 1, 2023
- Fixed: Type error
- Removed some Site Health Info, please install [WebFacing™ – cPanel® Email Accounts management &amp; Account backup](https://wordpress.org/plugins/wf-cpanel-email-accounts/) 

= 3.5.6 =

- Dec 3, 2022
- Rename plugin
- Fix how cPanel® is detected to avoid fatal errors

= 3.5.5 =

- Nov 6, 2022
- Rename plugin
- Site Health Info: Do not show auto update on multisite
- Fix how cPanel® is detected to avoid fatal errors

= 3.5 =

- Nov 4, 2022
- Removed all email related info. Please use [WebFacing – Email Accounts management &amp; Hosting Account backup in cPanel®](https://wordpress.org/plugins/wf-cpanel-email-accounts/)
- Removed uploads disk space used
- Removed Site Health Status Email Routing test
- Added Site Health Status test for the recommended plugin
- Site Health Info reorganized

= 3.4 =

- Nov 3, 2022
- Removed some email related info.

= 3.3 =

- Oct 20, 2022
- Will soon be discontinued

= 3.2 =

- Jul 18, 2022
- Fix for default `UPLOADS` dir
- Major performance enhancement - no impact in admin outside Dashboard & Site Health screens
- cPanel® charts enabled with Google Site Kit plugin active
- Requires PHP 7.4+
- Tested with PHP 8.2

= 3.1 =

- Apr 29, 2022
- Protection against fatal error when PHP <code>shell_exec</code> is not allowed
- New Site Health test for unique keys and salts constants
- Several extra config constants in Site Health Info
- Showing defaults for constants in Site Health Info
- Indication of strange values for constants in Site Health Info

= 3.0 =

- Mar 31, 2022
- Several extra config constants in Site Health Info
- Shows defults when constants are undefined
- File/class renaming, code cleanup

= 2.9.2 =

- Mar 14, 2022
- Fixed fatal error in case PECL intl function <code>idn_to_utf8</code> is not available

= 2.9.1 =

- Mar 08, 2022
- Enhancement: Plugin promotion at bottom of Dashboard widget can now be dismissed or removed
- Enhancement: Some additional constansts to Site Health Info WordPress Constants
- Fix: Coding standards

= 2.9 =

- Feb 01, 2022
- Major performance enhacement when loading dashboard
- WP 5.9 tested
- PHP 8.1 ready

= 2.8 =

- Add PHP Errors count to gauges widget and in Site Health Info

= 2.7 =

- Oct 19, 2021
- Add PHP Error/Debug Log File Size to Site Health Info
- Expired transients cleanup
- Add <code>WP_CONTENT_URL</code> to Site Health Info WordPress Constants
- Add <code>FORCE_SSL_ADMIN</code> to Site Health Info WordPress Constants
- Restructured code with simpler translations
- Integrated with Query Monitor plugin
- Files & classes renaming

= 2.6 =

- Add subaccounts info to Site Health Info tab
- Add disk i/o gauge in Dashboard Resources widget

= 2.5 =

- Translatable gauge labels
- Server errors count in Dashboard Resource Usage & Server Errors widget
- Two new constants i Site Health Info WordPress Constants section
- Server errors in Site Health Info Server section
- Make sure no errors from gethostbyaddr() when no <code>SERVER_ADDR</code> (cron, CLI)
- Make resource gauges responsive to current widget width
- Added cPanel® user last modified to Site Health Info

= 2.4 =

- Added Server error test and log
- Better security for getting resources from JavaScript (generated token as a secret)
- Detect interference with Google Site Kit plugin
- Fix for some sites not showing gauges due to referrer policy

= 2.3 =

* Added gauges in new widget on dashboard to show resource usage (Memory, CPU and Processes)
- Translation fixes

= 2.2 =

- Less strict cPanel® features check on load
- Add dead domains to Site Health Info tab
- Count addon, parked and dead domains as label suffix
- Include main email account in email account count in Dashboard - At a Glance widget
- More translation contexts

= 2.1 =

- Add Site Health cPanel® Info tab main account disk usage
- Add Site Health cPanel® Info tab maximum emails sending frequency per hour
- Add cPanel® version info in Site Health Info tab
- Tidy up Site Health for cPanel® entries in Info tab
- Reorder, and make more logically hierarchical, Site Health for cPanel® Info tab
- Remove Site Health cPanel® forwarders in Info tab (install my other plugin 'WebFacing – Email Accounts in cPanel®' to list them)
- Better handling of IDN domains where overlooked
- A few extra, useful WordPress constants in Site Health Info tab, but removed WP_ENVIRONMENT_TYPE as redundant
- Recommending my other plugin 'WebFacing – Email Accounts in cPanel®' in Dashboard - At a glance widget
- Fixed a bug (oversight) in 2.0 that alerted about email routing in the case that the MX-record points to self. In that case, no worry.

= 2.0 =

- Email accounts number (and size as tooltip) in Dashboard Right Now widget
- New test for Email Routing under Site Health Status tab
- More constants under WordPress Constants in Site Health Info tab
- A lot more information in cPanel® & Disk Usage in Site Health Info tab

= 1.6 =

- March 11, 2021
- Fix for fatal error when undefined constant in PHP 7.4
- Urgent: Safeguard against PHP fatal errors when installed on a site not using cPanel®
- In case on PRO ISP AS: Added link to PRO ISP's support article for enabling HTTPS in cPanel® in Site Health - Status - Security
- Database disk space shown in Dashboard widget tooltip
- A few more useful constants in Site Health - Info - WordPress Constants
- Correct language neutral values in Site Health - Info for debug copy results
- Some minor translation fixes

= 1.5 =

- Bugfix: Database disk space was counted twice, leading to too high value for total disk space used
- Added Database disk usage to Site Health Info tab
- Partly rewritten to use more cPanel® <code>uapi</code> calls
- Removed cPanel® <code>Quota</code> calls
- Introducing some cPanel® Usage Statistics parametres, like CPU Usage and number of Entry Processes, in Site Health Info tab
- Better caching of values in short lived transients
- Added <code>DISALLOW_FILE_EDIT</code> to Site Health Info WordPress Constants
- Added detection for Enterprise hosting packages at PRO ISP.
- Spelling error for Pro Premium package.
- Tested for WP 5.6
- Some minor text changes in ´readme.txt´.
- Some old code cleanup.

= 1.2 =

 Switched to new `quota` command on cPanel® for disk space max & used. Thanks to [@proisp](https://profiles.wordpress.org/proisp/) for implementing it.

= 1.1 =

- Cap check for showing cPanel® username in At a Glance.

= 1.0 =

- Initial release, Sep 2020.
