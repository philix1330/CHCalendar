=== CH Calendar - WordPress Custom Plugin ===
Contributors: philix
Tested up to: 5.6
License: GPLv2

== Description ==

Each user only has access to their own cluster headache attacks calendar and only to this one.
However, for scientific purposes, it is possible for the site administrators to access all the calendars simultaneously in order to provide data for a possible future study.
In this configuration, the listed and printable calendars are anonymised, making any individual non-identifiable.

== Changelog ==

= 1.0.2 =
* [Added] Member card.

= 1.0.1 =
* [Added] Control that user is admin to access the full calendar list.

= 1.0.0 =
* [Added] List my calender
* [Added] Add to my calendar
* [Added] Modify an attack
* [Added] Delete an attack
* [Added] Delete all my data
* [Added] List all anonymised calendars (reserved to admin) 
* [Added] Create database table on install 

== Shortcodes ==

* Shortcodes list to be added to your pages

* [ch_read_table]
* Allows two values for one parameter:
*  user : [ch_read_table uid='user'] = [ch_read_table]
*     Displays the attack list and associated monthly graph(s) if uid=user (or no parameter)
*  all :  [ch_read_table uid='all']
*     Displays a series of attack lists (one table per user) in an anonymous form
*  
* [ch_insert_table]
*  Allows the user to add an attack to his/her calendar
* 
* [ch_mod_table]
* Allows the user to modify a previously added attack in case of encoding error
* 
* [ch_del_rec_table]
* Allows the user to suppress an entry in his/her calendar
* 
* [ch_del_all_table]
* Allows the user to delete all his/her data
