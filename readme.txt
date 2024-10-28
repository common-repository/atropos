=== Atropos ===
Contributors: jrrl
Tags: post, expiration, date
Requires at least: 2.5
Tested up to: 2.9.2
Stable tag: trunk

This plugin lets you set an expiration date for posts.  After that date,
the posts will be deleted.


== Description ==

This plugin lets you set an expiration date for posts.  After that
date, the posts will be deleted.  This is done in a new subsection of the 
Advanced Options section of the post edit page.

Just pick your month and put in the date and year and your post will
automatically be deleted at the end of that day. Note that the expired
posts are DELETED. Be sure that you want to do this. I disavow any
responsibility if you delete your entire blog because you weren't being
careful.

This plugin uses WordPress's cron feature. This allows you to set a
time for things to happen, such as deleting a post. All well and good,
except that it doesn't actually happen until someone looks at a page
AFTER that time. And then it takes a few seconds to actually
happen. The net result for this plugin is that expired posts will
still be there for the first page view of the deletion day and
possibly several more if there is a lot of traffic around
midnight. The post will be deleted, but not precisely at
midnight. Such is Wordpress's cron.

== Installation ==

Unzip it and activate it and you'll have the expiration capability.  

== Further Instructions ==

Any other information I have can be found at the [Atropos Homepage](http://templature.com/atropos-wordpress-plugin/).

== License ==

I dunno.  How about creative commons attribution?  Sound ok?  Good.

