=== Online Status inSL ===
Contributors: gwynethllewelyn
Donate link: http://gwynethllewelyn.net/
Tags: second life, online, status, profile, sl
Requires at least: 3.0
Tested up to: 4.0-alpha
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Allows you to show your Second Life online status on any WordPress blog (multiple widgets and shortcodes are possible)

== Description ==

This simple plugin with associated widget allows you to show your online status in Second Life.

It also includes a LSL script to place inside an in-world script, which is available from the Settings menu.

**Warning**: Versions 1.3.X and above are utterly incompatible with previous versions; you will need to add the new script to all your in-world scripted objects or your blog will not display the status at all!

**Warning**: Version 1.3.8 and above might make it impossible for you to delete old tracking objects. 1.4.0 adds a way to delete all of the tracking objects, but there have been plenty of changes that this simply might not work any longer.

== Installation ==

1. After installing the plugin, go to the Settings menu and look at the option for "Online Status inSL". You should be shown a pre-formatted LSL script.
2. Launch SL.
3. Create an object in your land.
4. Open the object, and go to the Contents tab.
5. Create a new script inside (just click on the button).
6. Delete everything in that script.
7. Now go back to the WordPress admin page you've opened, and copy the script and paste it inside your LSL script in SL.
8. Save the LSL script in SL; it should recompile.
9. The LSL script will now try to contact your blog and register itself.
10. You can go back to WordPress, go to *Appearance > Widgets*, and select the widget "Online Status inSL" by dragging it to one sidebar.
11. Select the avatar name and (optionally) change the text that gets displayed.
12. If you wish a SL profile picture, select "left", "right", or "center" for alignment. If you don't wish it, just leave the setting to "none".
13. Refresh your blog; it should now show your online status!

To have multiple widgets for multiple avatars, just drag & drop a few more. In-world, each avatar needs to have their own scripted object. You can have two or more widgets for a single avatar (although this is pointless...).

Styling of each widget is possible via additional CSS (see FAQ).

From version 1.1.0 onwards, this plugin incorporates the *Shortcode API*. You can now embed tags inside posts and pages like this:

`[osinsl avatar="Avatar Name"]`

See the section on *Shortcodes* for more information.

1.2 includes the ability to delete tracked avatars from the admin page. This is mostly needed if you're doing a lot of tests, lost an in-world object and need to create a new one for the same avatar, and so forth. Note that you can have several online status indicators on different sims for the same avatar.

1.2 also allows an optional SL profile picture to be shown on the widget (or via shortcodes on any page or post). For this to work, you need to have set your SL profile to visible on the Web.

From 1.3.0 onwards, the plugin has been rewritten from scratch and the previous in-world scripts will not work any longer. Now the widget won't contact the in-world object any longer; instead, it works the other way round: every minute, the in-world object will see if the status has changed. If it has, it will send an update to the blog. If not, it won't do anything and just patiently wait. The blog will just read stored information from the WP database but not initiate any new communication. This should get pages with this widget to display much faster.

Thanks to Celebrity Trollop for the suggestion for changing this, and to the ever-patient Puitia for the many tests!

1.4.0 changed the way the list of tracked objects are displayed, using an internal WP API. It also includes more communication with the in-world objects, allowing them to be resetted, deleted, or simply tested to see if they're alive.

== Frequently Asked Questions ==

= Uh oh! I have tried out earlier versions before, I did an upgrade, and now nothing works! =

Sorry about that. The code was completely rewritten and now uses HTTP-in instead of XML-RPC, retrieves a bit more data from in-world (to be used on future versions), and stores information on the blog itself instead of external files on the upload directory (and even has some caching).

These were simply too many changes, and it was next-to-impossible to make the plugin backwards-compatible with 0.9.X. You will need to go back to your scripted object and drop the new script inside.

Updating to 1.2 will probably get a profile picture by default; change your widget and save again.

1.0-1.2.1 scripts might still work with 1.3.X widgets, but it's a safer bet to use the new LSL script.

1.3.X scripts should still work with 1.4.X widgets, but you will not get the ability to reset or delete the objects remotely. Pings should work.

= I get a "status unknown" error =

This happens when the dataserver in Second Life is slow in responding; there is a limit to the number of successive queries that it replies to. Usually, after the next refresh (every minute or so), it should retrieve the correct status (online/offline) and start working again. Since the in-world object caches entries, this might mean waiting for about one minute for the error to disappear.

= I upgraded and now the widget does not update the status =

Go in-world and touch the in-world object. It should re-register and everything should work fine again.

= The LSL script cannot contact the webserver! =

This might happen if you have a very unusual WordPress installation. You'll have to change the line in the LSL script saying "http://_your-domain-name_/wp-content/plugins/online-status-insl/save-channel.php" to whatever is appropriate for your blog.

= I get errors on the LSL script =

Make sure you really, really have copied it correctly... in some cases, quotes might be changed into typographical quotes; ampersands into \&amp\; or something like that. If all failed, contact me in-world, I'll give you a copy of the script (you'll have to change the appropriate URL for your blog though)

= Nothing works! =

Email me at <gwyneth.llewelyn@gwynethllewelyn.net>, I'll try to do my best to help, but take into account that some of your configurations might be very tough to debug and I might not be able to help you.

= I get a lot of errors, but it sort of works... =

I need more people like you to help debug me this! I have a rather standard configuration at Dreamhost and this works well with my blogs there. But I can imagine that stranger configurations will be harder to debug! Please try to figure out what version of WordPress you're using, what version of PHP is installed on your host, and if you have writing permissions to the "uploads" directory under wp-content

= I want to style my messages, but the widget doesn't allow HTML tags =

This is mostly for security reasons and to prevent users to make serious mistakes. You can use CSS to style your Online Status inSL widget instead; see the section on CSS.

= When I deactivate the plugin and activate it again, the widget stops working/my avatar names disappear =

If you deactivate the plugin and activate it again, all internal data with the online status is lost. You will need to go back in-world and touch the objects (or reset them) to send the information back to your blog. Note that the widgets will still be at the same places and keep the overall configuration *except* for the avatar name they're bound to.

= Sometimes more than one status indicator gets deleted, but I just clicked on one =

This should have been fixed now. Just go back in-world and touch your objects to re-activate them on WordPress.

= I can't delete one of the status indicators on WordPress, even if I have removed the in-world object manually =

From 1.4.0 onwards, you have more options to delete the status indicators (either just on WordPress, just in-world, or both). Keeping them in sync across plugin versions is a formidable task! Be patient...

= The list of tracked objects is completely out of sync with what WordPress displays and I cannot delete some of the old objects =

This will definitely happen if you have been using this plugin for a long, long time, and have several different versions. To make matters worse, the way the list of tracked objects is stored in WordPress has changed for 1.4.0, so many of your objects might become undeletable. In this case, all you can do is de-install and re-install the plugin, go in-world, and reset all your objects by touching on them.

= I'm a relatively recent user and my profile picture didn't work =

My apologies. New users have a "fake" last name, "Resident", which is however never used on profile pictures. 1.3.5 should fix this.

= Can I get translations in different languages? =

Starting with 1.3.5, you can. A default English .po file is supplied, so you can tweak it to your own language. I've added Portuguese as an example. More translations are welcome!

= Will this work for OpenSimulator-based grids? =

Not quite, but almost. Basically, you can certainly track if an avatar is online/offline on any grid (the tracking code is grid-independent). However, each OpenSimulator grid operator stores avatar profiles differently. This means that you won't get any fancy pictures, links to profiles, or to location — these are hard-coded to work with the Second Life grid.

If you just wish to have a text message saying "my avatar is online on grid X", then this plugin will certainly work.

In future versions, the options to extract profile data from OpenSimulator grids might have some extra settings, but it still won't work with every grid. Each grid really does this differently!

== Screenshots ==

1. Screenshot of widget in action in the Appearance > Widgets area 
2. Create an object in SL. Name it "SL Online Status Indicator".
3. Click on the "Contents" tab.
4. Now click on the "New Script" button.
5. Go back to the blog, and select and copy the *whole* script.
6. Paste the copied content inside the new script, overwriting it. Save! Done!

== Changelog ==

= 1.4.0 =
* New backoffice display, using WP_List_Table. This makes it more portable to future versions and is 100% compatible with the overall look & feel of WordPress
* In-world script now allows remote reset/deletion requests. Older objects will silently fail with reset/deletion requests, but the rest of the functionality will still work!
* You can now ping in-world objects to see if they're active and/or your WP database is in sync. This will work with older scripts, too
* Objects that have the current version will have its version number coloured green; older (but functional) versions will be displayed in yellow; ancient versions (which will not work) will be displayed red
* Status display on the plugin backoffice is coloured green for online, red for offline
* You can now use bulk actions to ping/delete/reset/destroy objects
* Several sections of the code were reshuffled, and lots of comments added 

= 1.3.8 =
* Changed the way avatar names are selected: now it's by object key, not avatar name (to allow avatars on OpenSim grids to be tracked)
* Because 1.3.7 didn't fix the deletion error, new code was developed using the above changes; this might invalidate previous versions! (Because objects are now tracked by object key and not avatar name, previous settings might have invalid data)
* Avatar profile pictures do not use align=XXX tags but instead the class gets aligncenter, alignleft, alignright for consistency with WP, current themes, and recent CSS versions
* When selecting an avatar name on the Widget, it displays region and position (to allow avatars with the same name in different grids to be easily selected)
* Added *objectkey* option for the shortcode (to deal with avatars with the same name in different grids)
* Added fancy banner
* Added a few extra translations (they only appeared on in-world HTTP-in responses, not on WP)
* Confirmed compatibility with WP 3.9
* Users upgrading directly from 1.0 (or earlier versions) should now manually delete the *channel.inc* file under *wp-content/uploads*

= 1.3.7 =
* Minor retweaking of the logic for deleting online status indicators from the admin page

= 1.3.6 =
* Fixed tiny bug with incompatible function names with other plugins of mine

= 1.3.5 =
* Added support for multiple languages. Portuguese was added. Translators welcome!
* Fixed issue for avatars with "Resident" as their last name (links to profile images were broken)
* Fixed minor programming issues and alt/title tags for the images for full HTML compliance

= 1.3.0 =
* Completely changed the communications protocol. Now no HTTP-in calls are used. This makes the widget much faster to show on pages, and should lead to less "status unknown errors". Thanks to Celebrity Trollop for proposing a much simpler way to the code design!
* Additionally, shortcodes were not really working _unless_ there was also a widget on the blog. The change should now fix this.

= 1.2.1 =
* Fixed lack of return code on HTTP requests to in-world objects, probably caused by timeouts. Thanks for Puilia for checking it and to Celebrity Trollop for providing a simple fix (I've also added 60 seconds of timeout instead of the default of 5)

= 1.2.0 =
* Adds SL profile picture to the widget, to the backoffice, and to shortcodes
* Added the ability to delete entries from the database
* Changed LSL script to be inside a textarea for better layout
* Fixed shortcode bugs and added new options
* Fixed incorrect saving of serialized settings and added maybe_unserialize to deal with warnings when serialization was not required (happened when multiple widgets were used)
* Added target="_blank" on links to avatar profile and object location
* Removed dependency on cURL

= 1.1.2 =
* More URL bug fixes, solved with rawurlencode

= 1.1.1 =
* Fixes a bug on links when in-world object names have single quotes in the names

= 1.1.0 =
* Implements the Shortcode API for embedding the status on a page or post (with its own CSS classes)
* Added further snapshots to help out the installation process in Second Life

= 1.0 =
Major upgrade! Note that the old version of the LSL script will not work any longer! You need to replace all in-world scripted objects manually

* Uses HTTPRequest and HTTP-in instead of XML-RPC
* Supports multiple avatars and multiple widgets
* Options are now saved using the WP mechanism instead of using external files
* Uses a caching mechanism to reduce the number of requests to the simulators
* Added CSS div and span for the widget to allow restyling

= 0.9.1 =

* Added some contributed code by SignpostMarv replacing obsolete attribute escaping code
* Added smaller screenshot
* Added a FAQ Q&A for "status unknown"

= 0.9 =
* First release.

== Upgrade Notice ==

= 1.4.0 =
If you had problems in deleting unused items, this version might fix them for you. Notice that things changed in the way the list of objects/avatars are tracked. You might have problems when upgrading this plugin and trying to delete previous objects! Sorry for that, there was a nasty bug in the code.

If you cannot keep your objects in-sync with WordPress, uninstall and reinstall the plugin, which should give you an empty table of tracked objects. Now go in-world and touch each of them in turn, to get them re-registering again with your WordPress site. Remember to change their LSL scripts to the latest version!

== CSS ==

To allow styling of the plugin, the following styles are emitted by the widget:

The whole content of the widget is included inside a `<div class='osinsl'>`

The text before the status is a `span` with class `osinsl-before-status`. The status itself is `osinsl-status` and the text afterwards is `osinsl-after-status`.

Status unknown (i.e. SL dataserver issues) is styled as `osinsl-problems` and the text for an unconfigured widget is styled `osinsl-unconfigured`.

The profile picture (if visible) will have the class `osinsl-profile-picture`. Users can set the horizontal alignment (using the standard *alignleft*, *aligncenter*, *alignright* classes) but nothing else. Size is limited to 80x80 (all this might be changed).

To style embedded shortcode, change the CSS class for `osinsl-shortcode`.

== Shortcodes ==

1.2 and onwards support shortcodes, to embed online status and SL profile pictures inside posts and pages.

The overall syntax is:

`[osinsl avatar="<avatar name>" picture="[none|center|right|left]" status="[on|off]" profilelink="[on|off]"]`
or
`[osinsl objectkey="<UUID>" picture="[none|center|right|left]" status="[on|off]" profilelink="[on|off]"]`

**avatar** should have a valid Second Life/OpenSimulator avatar name which has an associated online status indicator in SL/OpenSimulator. This will expand to show the online status (e.g. usually *online*, *offline*, or an error message if no widget was configured or if the avatar is not being tracked). Note that if you have avatars with the same name on different grids, this will just get you one of them.

**objectkey** should be the Object Key of an in-world online status tracking object. This should be used alternatively to **avatar** and is useful if you have several objects tracking your avatar across different grids, all for the same avatar name. Note that object keys may change over time (when they get copied, duplicated, taken back to inventory and rezzed again, etc.) so this should be used only as a last alternative, when you really have several avatars in different grids, all with the same name.

**picture** is optional and defaults to *none* (i.e. profile picture is not shown); if the user has set the SL web profile to be visible, this will retrieve their profile picture, and resize it to 80x80. Options are *left*, *right*, and *center* which will provide minimal formatting (additional styling requires CSS; image size is fixed for now; see the **CSS** section for more information).

If the **picture** is set, **status** can be set to *off* (just show the picture but not the actual status).

If the **picture** is set, **profilelink** can be set to *on* (default is *off*) to link the picture to SL's web profile page.