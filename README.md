# zend-jmap

[![Build Status](https://secure.travis-ci.org/zendframework/zend-jmap.svg?branch=master)](https://secure.travis-ci.org/zendframework/zend-jmap)
[![Coverage Status](https://coveralls.io/repos/github/zendframework/zend-jmap/badge.svg?branch=master)](https://coveralls.io/github/zendframework/zend-jmap?branch=master)

This library provides JMAP for Zend Framework (JSON Meta Application Protocol)

JMAP is a modern standard for email clients to connect to mail stores. It therefore primarily replaces IMAP + SMTP submission. It does not replace MTA-to-MTA SMTP transmission. JMAP was built by the community, and continues to improve via the [IETF standardization process](https://datatracker.ietf.org/wg/jmap/). Upcoming work includes adding contacts and calendars (replacing CardDAV/CalDAV).

Please see https://jmap.io

## Installation

Run the following to install this library:

```bash
$ composer require zendframework/zend-jmap
```

## Documentation

Browse the documentation online at https://docs.zendframework.com/zend-jmap/

## Support

* [Issues](https://github.com/zendframework/zend-jmap/issues/)
* [Chat](https://zendframework-slack.herokuapp.com/)
* [Forum](https://discourse.zendframework.com/)

## More about JMAP (JSON Meta Application Protocol)

JMAP is a modern and generic protocol for synchronising data, such as mail, calendars or contacts, between a client and a server.  It is optimised for mobile and web environments, and aims to provide a consistent interface to different data types.

This specification is for the generic mechanism of data synchronisation.  Further specifications define the data models for different data types that may be synchronised via JMAP.

JMAP is designed to make efficient use of limited network resources. Multiple API calls may be batched in a single request to the server, reducing round trips and improving battery life on mobile devices. Push connections remove the need for polling, and an efficient delta update mechanism ensures a minimum of data is transferred.

JMAP is designed to be horizontally scalable to a very large number of users.  This is facilitated by the separate end points for users after login, the separation of binary and structured data, and a shared data model that does not allow data dependencies between accounts.

JMAP for Mail is a specification that defines a data model for synchronising mail between a client and a server using JMAP.

Please check out [JMAP at IETF](https://datatracker.ietf.org/wg/jmap/) and get involved!

