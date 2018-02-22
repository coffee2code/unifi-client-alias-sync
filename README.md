# UniFi Controller Client Alias Sync

A PHP script to synchronize client aliases between all sites managed by a UniFi Controller.

The [UniFi Controller](https://www.ubnt.com/software/) from [Ubiquiti Networks](https://www.ubnt.com/) allows sites to provide an alias for each connecting client. However, this alias is not propagated to other sites managed by that controller. This script allows you to sync client aliases defined on one site to other sites under that controller. Or you can originate aliases from the script itself (see info on `UNIFI_ALIAS_SYNC_ALIASES`).

A few things to note:

* A dry run mode is available (see info on `UNIFI_ALIAS_SYNC_DRY_RUN`) to permit running the script and reviewing status messages about its operation without actually having it set any aliases. This is recommended for an initial run to ensure everything is operating as expected.
* A client alias can only be synced to a site to which the client has connected to within the last year. Running the script periodically will help to sync aliases to clients on sites they've only ever visited since the latest sync, in addition to syncing newly added aliases.
* Once an alias for a client is found, that alias is used to sync across sites, taking precendence over a potentially different alias for that client that may be subsequently encountered. Therefore, the order of traversal for sites matters. The script can be configured to prioritize certain sites over others.
* Barring explicit site priority ordering (see info on `UNIFI_ALIAS_SYNC_PRIORITIZED_SITES`) the site with the name "default", if present, takes precedence over all other sites. The remaining sites are ordered alphabetically by name.
* If a client has an alias on a given site, that alias is not overridden under any circumstances. (A future version may introduce this capability.)
* Makes use of Ubiquiti's UniFi Controller API. Versions 4.x.x and 5.x.x of the UniFi Controller software are supported (version 5.6.29 has been confirmed to work).
* Disclaimer: Many of the functions in the underlying API client class are not officially supported by Ubiquiti Networks and, as such, may not be supported in future versions of the UniFi Controller API.


## Requirements

* Command line PHP 7+ with cURL installed
* Network connection to a server and port running the UniFi Controller, along with admin credentials to access the controller


## Instructions

1. Clone the [GitHub repository](https://github.com/coffee2code/unifi-client-alias-sync/) or download and unarchive the [zip file](https://github.com/coffee2code/unifi-client-alias-sync/archive/master.zip).
2. Copy the `config-sample.php` file to `config.php` and customize the constants.
   - Refer to the file as it contains full documentation of all the constants.
   - Three constants are required:
     - `UNIFI_ALIAS_SYNC_CONTROLLER`: the fully qualified URL of the controller with protocol and port number (e.g. https://example.com:8443)
     - `UNIFI_ALIAS_SYNC_USER`: the admin username for the controller
     - `UNIFI_ALIAS_SYNC_PASSWORD`: the password for the admin user
   - Five constants are optional:
     - `UNIFI_ALIAS_SYNC_DRY_RUN`: should the script operate in a dry run mode, which doesn't actually change any data?
     - `UNIFI_ALIAS_SYNC_DEBUG`: should the script operate in debug mode, which provides more verbose output about what is happening and what may have gone wrong?
     - `UNIFI_ALIAS_SYNC_ALIASES`: associative array of client MAC addresses and their associated aliases; use this if you want to proactively set client aliases across sites
     - `UNIFI_ALIAS_SYNC_PRIORITIZED_SITES` : array of sites to be given explicit priority ahead of any unspecified sites
     - `UNIFI_ALIAS_SYNC_VERIFY_SSL`: should the SSL connection to the controller should be verified?
3. Run the script from the command line:
   ```sh
   php unifi-client-alias-sync.php
   ```
   Notes:
   - By default, `UNIFI_ALIAS_SYNC_DRY_RUN` is set to true. It is recommended that the script be initially run this way so you can verify it is operating as expected.
   - If the dry run looks satisfactory, change `UNIFI_ALIAS_SYNC_DRY_RUN` to false.
4. You may want to periodically run the script in order to sync aliases to clients connecting to new sites since the last sync (and, of course, to sync newly added aliases).


## Credits

Thanks to the [UniFi Client API project](https://github.com/Art-of-WiFi/UniFi-API-client) for the API library used to connect with a UniFi Controller.


## License

This script is free software; you can redistribute it and/or modify it under the terms of the [GNU General Public License](http://www.gnu.org/licenses/gpl-2.0.html) as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.