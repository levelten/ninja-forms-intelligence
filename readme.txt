=== Ninja Forms - Intelligence ===
Contributors: tomdude
Donate link: getlevelten.com/blog/tom
Tags: analytics, contact, contact form, form, google analytics, marketing, metrics, stats, tracking, web form
Requires at least: 4.5
Tested up to: 4.9.2
Stable tag: 3.0.3.0-dev
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Automates Ninja Forms tracking in Google Analytics.

== Description ==

Google Analytics tracking for Ninja Forms made easy.

#### Core Features

* Track form submissions as Google Analytics goals or events
* Track form views
* Customize tracking per form
* Default tracking ensures all forms are tracked even if custom tracking is not configured
* Create and manage Google Analytics goals directly in WordPress
* No coding required
* No advanced Google Analytics skills needed
* No Google Tag Manager setup needed
* 5 minute installation

#### Enhanced Google Analytics

The plugin integrates with the Intelligence API to automate Google Analytics goal management in WordPress. Intelligence is a framework for enhancing Google Analytics.

To learn more about Intelligence for WordPress visit [intelligencewp.com](https://intelligencewp.com)

== Installation ==

=== Install Files Within WordPress ===

=== Plugin Install & Setup ===

1. Add the 'ninja-forms-intelligence' folder to the `/wp-content/plugins/` directory.
1. If you manually add plugin files to your site, also download the [Intelligence plugin](https://wordpress.org/plugins/intelligence) plugin and the 'intelligence' folder to the `/wp-content/plugins/` directory.
1. Go to "Ninja Forms > Settings", under the Intelligence field group, click the "Setup Intelligence" button.
1. Go through the setup wizard and set up the plugin for your site.
1. You're done!

=== Changing default tracking ===
1. Go to 'Admin Menu > Ninja Forms > Settings'. Scroll to the "Intelligence" fieldset. Next to the "Default submission event/goal" value, click the "Change" button.
1. On the Default form tracking page, use the "Submission event/goal" dropdown to select an existing goal. If you want to create a new goal, click the "Add Goal" link.
1. When done, click "Save"

=== Custom Form Tracking ===
1. To customize the tracking of a form from the default tracking, go to 'Admin Menu > Ninja Forms'.
1. Click to edit a form you want to customize.
1. On the form edit page, click on the "Emails & Actions" tab
1. To add Intelligence settings to the form, click the "+" button at the bottom right. Select the "Intelligence" box under the Installed grouping.
1. Use the "Submission event/goal" drop down to select how you want to track the form. 
1. Input the "Submission value" if you want to set a custom goal value for the form.
1. Click "Done" to save the settings.
1. Click "Publish" to update the form with the new settings.

=== Popular options ===
Track and manage Intelligence goals and events in existing Google Analytics tracking ID:

1. Go to "Intelligence > Settings"
1. Under "Tracking settings" fieldset, open the "Base Google Analytics profile" fieldset
1. If base profile is not set, click "Set profile" link to set your existing tracking ID
1. Check "Track Intelligence events & goals in base profile"
1. Check "Sync Intelligence goals configuration to base profile"
1. Click "Save settings" button at bottom of page

Embed Google Analytics tracking code if site does not already embed tracking code through some other method.

1. Go to "Intelligence > Settings"
1. Under "Tracking settings" fieldset, open the "Advanced" fieldset.
1. For "Include Google Analytics tracking code" select the "Analytics" option
1. Click "Save settings" button at bottom of page

== Screen Shots ==

1. Select a goal and goal value to track on different form submissions
2. Easily add goals to your in Google Analytics
3. Manage Google Analytics goals without leaving WordPress
4. Automatically trigger goals on form submission
5. Set a default goal to make sure no form submissions are missed

== Changelog ==

= 1.0.0 =
* Initial version

== Upgrade Notice ==