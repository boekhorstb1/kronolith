# What is Kronolith?

contact:  <kronolith@lists.horde.org>

Kronolith is the Horde calendar application. It provides web-based
calendars backed by a SQL database or a Kolab server. Supported features
include Ajax and mobile interfaces, CalDAV support, shared calendars,
remote calendars, invitation management (iCalendar/iTip), free/busy
management, resource management, alarms, recurring events, tasks
integration, import and export functions (iCalendar and CSV formats),
and a number of different calender views.

This software is OSI Certified Open Source Software. OSI Certified is a
certification mark of the [Open Source
Initiative](http://www.opensource.org/).

# Obtaining Kronolith

Further information on Kronolith and the latest version can be obtained
at

> <http://www.horde.org/apps/kronolith>

# Documentation

The following documentation is available in the Kronolith distribution:

:   Copyright and license information

[doc/CHANGES](doc/CHANGES)

:   Changes by release

[doc/CREDITS](doc/CREDITS.rst)

:   Project developers

[doc/INSTALL](doc/INSTALL.rst)

:   Old Installation instructions and notes

[doc/TODO](doc/TODO.rst)

:   Development TODO list

[doc/UPGRADING](doc/UPGRADING.rst)

:   Pointers on upgrading from previous Kronolith versions

# Installation

Old Instructions:

Instructions for installing Kronolith can be found in the file
[INSTALL](doc/INSTALL.rst) in the `doc/` directory of the Kronolith
distribution.

New Instructions:

- Adapt the composer.json file of your groupware setup.
- Additional and Optional plugins and code:
     + Some are outdated and can only be installed with pear, see Old Instructions
     + Date_Holidays: This is a pear setup that can currenlty partly be installed with composer. Still, this feature should be adapted to use a more modern librarby. In order to install with composer you have to add the following lines to you groupware setups composer.json:
     + in the required section:

                   ````
                           \"pear/date_holidays\": \"dev-trunk\",
                            \"pear/date\": \"dev-master\"
                   ````
     + in the repository section:

                    ````
                          \"date\": {
                              \"type\": \"vcs\", \"url\":
                              \"<https://github.com/pear/Date>\" },
                           \"0\": { \"url\":
                              \"<https://horde-satis.maintaina.com>\",
                              \"type\": \"composer\" }
                    ````

# Assistance

If you encounter problems with Kronolith, help is available!

The Horde Frequently Asked Questions List (FAQ), available on the Web at

> <http://wiki.horde.org/FAQ>

Horde LLC runs a number of mailing lists, for individual applications
and for issues relating to the project as a whole. Information,
archives, and subscription information can be found at

> <http://www.horde.org/community/mail>

Lastly, Horde developers, contributors and users also make occasional
appearances on IRC, on the channel #horde on the Freenode Network
(irc.freenode.net).

# Licensing

For licensing and copyright information, please see the file
[LICENSE](http://www.horde.org/licenses/gpl) in the Kronolith
distribution.

Thanks,

The Kronolith Team
