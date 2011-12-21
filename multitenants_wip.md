Multitenant Technological Demo
==============================

How to set up:

1. make a fresh new Moodle install (there is no upgrade code)
2. set up DNS so that the server is accessible via different host names
3. enable "tenant sites" in Exprimental settings
4. create new tenant site in "Administration / Manage tenants"
5. create some users
6. create cohort for tenant manager, add user account
7. enable Tenant SSO authentication plugin
8. set up cohort tenant sync in "Administration / Plugins / Auth / Tenant SSO / Cohort sync
9. add Tenant SSO block to frontpage
10. login as tenant manager and jump to the tenant site via the SSO block

What works:

* basic user management - you need a manager role in the tenant site
* everything bellow course level should mostly work

What does not work? Pretty much everything outside needs a lot of tweaking and rewriting.

Proposed milestones
===================

1. end of 2011 - Multitenant Technological Demo and proposal
2. early 2012 - discussions and decisions (partners, integrators, developers, community)
3. mid 2012 - integration of tenant DB tables and fields into late 2.3dev, continuous core and plugin cleanup
4. end 2012 - integration of experimental tenants code into 2.4dev, finishing core and plugin cleanup
5. mid 2013 - continuous tenant code improvements, marked as stable in 2.5

Support for more tenants in one Moodle installation is a very complex problem, it would require major changes
all over the codebase. Nearly all plugins would need to be updated and many parts of Moodle would have to ne redesigned.

It is very important to integrate only fully tested and documented code. We would need full cooperation/support
of contrib developers. Every core developer would have to understand the design and internal implementation.


Implementation tricks
=====================

* new tenant context level
* new tenantid field in several DB tables (course, course_categories, user, etc.)
* each tenant site using different URL
* overloading of main and plugin settings in tenants
* different mnet host record for each tenant
* tenant SSO authentication - tenant user accounts can be attached to main user accounts via cohort sync
* hardcoded full tenant separation


Otehr interesting ideas
=======================

* new 'guest' auth plugin that deals with guest users only
* we do not really need the usernames for synced users from the main site, NULL usernames could prevent sync problems
* cohort context and ability to manage/view users from a specific cohort only


Backwards compatibility
=======================

All plugins and all core subsystems would have to be updated.


Core subsystems
===============

Role and permissions
--------------------

Implemented and partially tested.

Admin settings
--------------

Only links to external pages work in demo. Technically we can create a new set of settings classes that could be used
from tenantsettings.php files.

Messaging
---------

Completely disabled in the demo. This subsystem needs major changes.
I believe it would be easiest to throw out all current code and start from scratch, fulltime maintainer is necessary.

Calendar
--------

Not updated yet, it would be better to rewrite it from scratch.

Blog
----

Disabled in demo. Need major cleanup and minor tweaks to support tenants.

Conditional activities and completion
-------------------------------------

Disabled in demo. Only minor changes necessary.

User profiles
-------------

Disabled. Major internal changes necessary.

Gradebook
---------

No changes necessary.

Cohorts
-------

Fixed in demo. It would be very useful to add cohort contexts.

MNET
----

Not compatible with the design of tenant subsites. it may or may not work in the global site when tenants enabeld.

Notes
-----

Disabled in demo. Cleanup and minor changes required.

Tags
----

Disabled in demo. I believe it would be best to rewrite the code completely from scratch.

Questions
---------

No changes should be necessary because they already fully support contexts.

Ratings and comments
--------------------

Disabled in demo. No comment.

Site registration
-----------------

Disabled in demo. Rewrite.

RSS
---

Disabled in demo. Rewrite.

Web services
------------

Disabled in demo. Internals should be fine, the admin UI may need major changes.

Command line support
--------------------

Incompatible with the design of tenant subsites.

Cron
----

Totally borked in demo. It needs badly full redesinging. Full rewrite necessary.

Logging
-------

Needs full rewrite in any case.


Plugin types
============

Modules
-------

Only minor changes are necessary.

Authentication plugins
----------------------

New Tenant SSO plugin implemented. It shoudl be technically possible to add support for tenants into any plugin or
simply disable the plugins in tenant sites.

Enrolment plugins
-----------------

Half of the plugins already updated in demo. Plugins with only global settings such as enrol/ldap may require
major changes. Most of the problems are UI and configuration related only.


Repositories
------------

Disabled in demo. The administration should be completely rewritten. The plugins need minor changes only.

Portfolios
----------

Disabled in demo.

Filters
-------

Only minor changes necessary because contexts are properly supported.

Reports
-------

Minor changes only in code, need the logging improvements pretty badly.

Blocks
------

Minor changes only necessary because it kind of supports contexts already.

Admin tools
-----------

Majority is not necessary in tenants, trivial context related changes are necessary to make them work in tenants.

Themes
------

No major changes necessary, theme creators might want to create tenant specific configuration UI.