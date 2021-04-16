# CHANGELOG for 2.x
This changelog references the relevant changes done in 2.x versions.


## v2.1.3
* Fix bug in ListAllRolesRequestHandler that was only returning the first page of search response.


## v2.1.2
* Add GetAllAppsRequestHandler for deprecated `gdbots:iam:mixin:get-all-apps-request:v1` requests.


## v2.1.1
* Fix bug in ListAllRolesRequestHandler that didn't return the response.


## v2.1.0
* Remove use of mixin/message constants for fields and schema refs as it's too noisy and isn't enough of a help to warrant it.
* Uses `"gdbots/ncr": "^2.1"`


## v2.0.0
__BREAKING CHANGES__

* Upgrade to support PHP 7.4.
* Uses `"gdbots/ncr": "^2.0"`
* Implement aggregates and remove command handlers for common gdbots:ncr operations.
