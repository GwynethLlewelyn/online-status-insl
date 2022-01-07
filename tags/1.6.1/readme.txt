=== Online Status inSL ===
Contributors: gwynethllewelyn
Donate link: https://paypal.me/GwynethLlewelyn
Tags: second life, opensimulator, online, status, profile, sl
Requires at least: 5.0
Requires PHP: 7.3
Tested up to: 5.8
Stable tag: 1.6.1
License: BSD-3-Clause
License URI: https://directory.fsf.org/wiki/License:BSD-3-Clause

Allows you to show your Second Life® or OpenSimulator online status on any WordPress blog (multiple widgets and shortcodes are possible)

== Description ==

This simple plugin with associated widget allows you to show your online status in Second Life® or any OpenSimulator grid.

It also includes a LSL script to place inside an in-world script, which is available from the Settings menu.

**Warning**: Versions 1.3.X and above are utterly incompatible with previous versions; you will need to add the new script to all your in-world scripted objects or your blog will not display the status at all!

**Warning**: Version 1.3.8 and above might make it impossible for you to delete old tracking objects. 1.4.0 adds a way to delete all of the tracking objects, but there have been plenty of changes that this simply might not work any longer.

**Note**: Versions before 1.4.0 are so old that I have deleted them from the WordPress repository.

== Installation ==

1. After installing the plugin, go to the Settings menu and look at the option for `Online Status inSL`. You should be shown a pre-formatted LSL script.
2. Launch your favourite Second Life® viewer and connect to your Second Life or OpenSimulator grid account.
3. Create an object in your land.
4. Open the object, and go to the Contents tab.
5. Create a new script inside (just click on the button).
6. Delete everything in that script.
7. Now go back to the WordPress admin page you've opened, and copy the script and paste it inside your LSL script in SL.
8. Save the LSL script in SL; it should recompile.
9. The LSL script will now try to contact your blog and register itself.
10. You can go back to WordPress, go to *Appearance > Widgets*, and select the widget `Online Status inSL` by dragging it to one sidebar.
11. Select the avatar name and (optionally) change the text that gets displayed.
12. If you wish a SL profile picture, select "left", "right", or "center" for alignment. If you don't wish it, just leave the setting to "none".
13. Refresh your blog; it should now show your online status!

To have multiple widgets for multiple avatars, just drag & drop a few more. In-world, each avatar needs to have their own scripted object. You can have two or more widgets for a single avatar (although this is pointless...). And, of course, you can have widgets for Second Life® and several OpenSimulator grids at the same time (so that your website visitors can see where exactly you are at the moment)!

Styling of each widget is possible via additional CSS (see FAQ).

From version 1.1.0 onwards, this plugin incorporates the [Shortcode API](https://codex.wordpress.org/Shortcode_API). You can now place shortcodes inside posts and pages like this:

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

Go in-world and touch the in-world object. It should re-register and everything should work fine again. If not, create a new LSL script — using the provided pre-generated one — and replace the old one. The idea is that the API hasn't changed much, to make sure that old scripts will still work (see the first question in this FAQ), but, alas, I'm merely human and prone to err.

= The LSL script cannot contact the webserver! =

This might happen if you have a very unusual WordPress installation. You'll have to change the line in the LSL script saying `http://_your-domain-name_/wp-content/plugins/online-status-insl/save-channel.php` to whatever is appropriate for your blog.

= I get errors on the LSL script =

Make sure you really, really have copied it correctly... in some cases, quotes might be changed into typographical quotes; ampersands into \&amp\; or something like that. If all failed, contact me in-world, I'll give you a copy of the script (you'll have to change the appropriate URL for your blog though)

= Nothing works! =

Email me at <gwyneth.llewelyn@gwynethllewelyn.net>, I'll try to do my best to help, but take into account that some of your configurations might be very tough to debug and I might not be able to help you.

= I get a lot of errors, but it sort of works... =

I need more people like you to help debug me this! I have a rather standard configuration at my own server and this works well with my blogs there. But I can imagine that stranger configurations will be harder to debug! Please try to figure out what version of WordPress you're using, what version of PHP is installed on your host, and if you have writing permissions to the `uploads` directory under `wp-content`

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

Starting with 1.3.5, you can. A default English (US) `.pot` file is supplied, so [you can tweak it to your own language using POEdit](https://poedit.net/). I've added Portuguese and British English as examples. More translations are most welcome!

**Note:** WordPress multilingual support is currently being done differently (no need to directly tweak `.po` files), but it requires the WordPress 'translation team' to approve them (this is not under my control). See also the developer notes for tags 1.5.0 and 1.6.1.

= Will this work for OpenSimulator-based grids? =

Essentially, yes. Basically, you can certainly track if an avatar is online/offline on any grid (the tracking code is grid-independent). However, each OpenSimulator grid operator stores avatar profiles differently. This means that you won't get any fancy pictures, links to profiles, or to location — these are hard-coded to work with the Second Life grid.

If you just wish to have a text message saying "my avatar is online on grid X", then this plugin will certainly work.

In future versions, the options to extract profile data from OpenSimulator grids might have some extra settings, but it still won't work with every grid (however, getting the profile picture _may_ be possible). Each grid really does this differently!

= Can I also track NPCs/bots? =

NPCs (Non-Player Characters) or 'bots (automated avatars, controlled by software) are perfectly 'normal' avatars from the perspective of the Second Life Grid; they have no more and no less features than human-controlled avatars and are created exactly in the same way. The sole difference is that, instead of a human behind a Second Life Viewer (released by Linden Lab or by a third party, such as the Firestorm Viewer), there is just software 'speaking' the network protocol developed by Linden Lab for the communication between the Grid and the Viewers; from the perspective of the Grid, therefore, it has no way of distinguishing if there is a human behind a viewer or merely software 'talking' exactly the same protocol, using a popular software development kit such as [LibreMetaverse](https://github.com/cinderblocks/libremetaverse).

Because of that, it's perfectly possible to track NPCs/bots for their online status, exactly as if these were 'normal', human-driven avatars. It's likely, however, that you'll need to log in to the Grid as the NPC/bot to properly set up everything as required (i.e. dropping the script inside an object owned by the NPC/bot).

LibreMetaverse works not only for the Second Life Grid, but also for any OpenSimulator-based grid. As such, any NPCs/bots that are set up using a 'normal' avatar which gets then driven by software using LibreMetaverse will be able to be tracked for their online status, in a grid-independent way, within the limitations addressed on the above question.

However, OpenSimulator _also_ supports 'native' NPCs. This is a feature that Linden Lab _could_ have offered on their Grid as well, but decided not to. Under OpenSimulator, it's possible to 'create' a NPC/bot from a script, which will look exactly like a 'normal', regular avatar, but which does _not_ require external software running under LibreMetaverse — you can do it within the environment provided by OpenSimulator itself, using extended LSL functions to directly script avatar-based NPCs/bots. While these can do pretty much everything that a 'normal' (human) avatar can, at the database level, they are distinguished from humans by a special flag, which gets set when a NPC is 'created', and which will also bind that NPC to a human avatar (i.e. the human avatar running the script creating a NPC will become its 'owner'). This a way to deal with potential security issues: OpenSimulator NPCs aren't anonymous, in the sense that nobody knows who is behind it, but rather 'belong' to a specific user (and therefore can be flagged for abuse like any other object), and that link is enforced at the core level — it cannot be tampered with using LSL.

This allows, among many other things, to use LSL to 'detect' if an avatar is being driven by a human or just by software. It also allows parcel owners to, say, allow human avatars to run scripts on their land, but forbid NPCs to do so. NPCs may be even banned from entering land parcels, whole regions, or even full estates; and grid owners can also ban NPCs altogether (effectively turning off the ability to create and script them), just like on the Second Life Grid.

As such, _these_ kinds of special, native OpenSimulator NPC avatars _may_ fail to be tracked properly regarding their online status. On a fully permissible OpenSimulator grid, it might be possible to consistently get the online status, but it will be tricky — when a NPC goes offline and comes back online, in general, even though it might look the same and have the same name as before, it _will_ have a _different_ UUID (at least, that's what I think that happens; there might be a way to override that procedure), and, as such, any online tracking script that is looking for a specific, valid UUID will fail — the 'reborn' NPC will technically be a _different_ avatar in the database, even if it looks exactly the same. Again, I believe this is done for security considerations. It means that everything that a NPC 'owns' is, at best, _transient_ — it will only be 'owned' as long as that NPC avatar is online. If it drops out of the grid, and comes back, whatever it has owned is now gone. Also, there is no way for a _human_ to log in as a NPC (and vice-versa: you cannot use an _existing_ account for a NPC; each NPC is created on the spot as a completely new entry in the avatar database), so that means it's impossible to log in with the NPC's account (technically, NPC avatars don't have associated 'accounts', even though they certainly have inventory, a profile, worn & attached items, etc.) and write a script to be dropped by the NPC inside an object it owns. This will simply be impossible.

Nevertheless, with some clever scripting, it _may_ be possible to achieve something similar. Because NPCs are created with an inventory — including items worn & attached — it's conceivable that the avatar may come with a box with a copy of the online tracking script. During creation, that box will be assigned ownership to either the NPC or possibly to the NPC's owner (depends on the scripting used): in the former case, it's certainly plausible to admit that the NPC can be scripted to drop that box on the ground once it gets launched. Assuming that the NPC is created on land specifically designed for that purpose, with the proper permissions set (allowing NPCs from a certain group — which can _also_ be set when the NPC is created — to drop objects and run scripts in the parcel), it is conceivable therefore that such an avatar _may_ have an online tracker script active (and therefore its online status being actively tracked!), although it might be pointless: after all, an NPC will either be online or not exist at all (from the perspective of the database!).

In short: aye, you _can_ track the online status of bots and NPCs, if they're scripted using the LibreMetaverse toolkit; there will be some limitations for OpenSimulator bots (lack of a profile picture, for example); but tracking native OpenSimulator NPCs will very likely _not_ work, or, if it does, it will be essentially pointless to do so.

= This plugin is awesome! I have lots of suggestions on how to improve it! Can I send them to you? =

I'm glad you find it useful. Sure, to make collaboration easier, the code for this plugin is also on [GitHub]()

== Screenshots ==

1. Screenshot of widget in action in the Appearance > Widgets area
2. Create an object in SL. Name it "SL Online Status Indicator".
3. Click on the "Contents" tab.
4. Now click on the "New Script" button.
5. Go back to the blog, and select and copy the *whole* script.
6. Paste the copied content inside the new script, overwriting it. Save! Done!

== Changelog ==

= 1.6.1 =
* Major code refactoring to comply as much as possible with the WordPress coding guidelines!
* Code is now being audited via [PHP CodeSniffer](https://github.com/tommcfarlin/phpcs-wpcs-vscode) to check for non-conformities to overall style (even if sometimes I have no choice but to override it); I've also run [PHP Mess Detector](https://phpmd.org/) to figure out more tricky/obscure aspects. There are still some outstanding issues to fix!
* PHP 8+ is stricter when handling wrong types, so some changes were warranted...
* Added gazillions of anti-XSS checks, sometimes to the point of exasperation. The current code should run much slower, but at least it will be a _bit_ more secure...
* Gutenberg support (blocks & widgets) is, for the moment, on hold (too complex for my tiny little head); bumped to 2.0.0
* Changing " to ' wherever appropriate to conform to some new fancy PHP guidelines!
* Publishing to GitHub as well (which also required SVN to ignore Git-specific things, as well as ignoring my own local configuration, and whatever Mac-specific hidden files that are pointless to save).
* Lots of code refactoring _with_ PHPDoc-compliant code documentation, since being on GitHub _may_ mean that more people contribute code (wishful thinking, I know); also, there will be (potentially) some automated tasks in GitHub looking for the documentation...
* Getting translations updated for the new code. Note that only the English-UK team responded (the Portuguese team couldn't care less about this plugin), so I'm sticking with the old method of providing translations. I've tried putting the files on Crowdin, but I think they do not work yet...
* TODO: how to fix 'fake' requests (after all, the only thing you need to do is to fake a few headers using, say, Postman...)
* TODO: re-audit those edge cases when a 'fake' request can potentially still do some XSS injection (!)

The remaining (historical) changelog is kept on the file `changelog.txt`, as per the WP recommendations.

== Upgrade Notice ==

= 1.6.0 =
Major code refactoring, trying to keep it cleanly styled according to the WordPress Coding Standard (as checked by the PHP CodeSniffer). Also tested under PHP 8.1 and WP 5.8+.

= 1.5.0 =
Due to changes in the way WP handles translations, and the level of restrictions imposed by recent
versions of PHP, this plugin has been bumped to work only with PHP > 7.3 and WP > 4.6.

= 1.4.0 =
If you had problems in deleting unused items, this version might fix them for you. Notice that things changed in the way the list of objects/avatars are tracked. You might have problems when upgrading this plugin and trying to delete previous objects! Sorry for that, there was a nasty bug in the code.

If you cannot keep your objects in-sync with WordPress, uninstall and reinstall the plugin, which should give you an empty table of tracked objects. Now go in-world and touch each of them in turn, to get them re-registering again with your WordPress site. Remember to change their LSL scripts to the latest version!

== CSS ==

To allow styling of the plugin, the following styles are emitted by the widget:

The whole content of the widget is included inside a `<div class='osinsl'>`

The text before the status is a `span` with class `osinsl-before-status`. The status itself is `osinsl-status` and the text afterwards is `osinsl-after-status`.

_Status unknown_ (i.e. SL dataserver issues) is styled as `osinsl-problems` and the text for an unconfigured widget is styled `osinsl-unconfigured`.

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

**picture** is optional and defaults to *none* (i.e. profile picture is not shown); if the user has set the Second Life web profile to be visible, this will retrieve their profile picture, and resize it to 80x80. Options are *left*, *right*, and *center* which will provide minimal formatting (additional styling requires CSS; image size is fixed for now; see the **CSS** section for more information). However, for the time being, pictures for avatars in OpenSimulator grids will _not_ be displayed.

If the **picture** is set, **status** can be set to *off* (just show the picture but not the actual status).

If the **picture** is set, **profilelink** can be set to *on* (default is *off*) to link the picture to SL's web profile page.