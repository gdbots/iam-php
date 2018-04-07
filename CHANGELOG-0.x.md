# CHANGELOG for 0.x
This changelog references the relevant changes done in 0.x versions.


## v0.4.0
__BREAKING CHANGES__

* Use new conventional features of gdbots/ncr abstract handlers (only override factory methods if needed).
* Delete `GetRoleBatchRequestHandler` as the `gdbots:iam:mixin:get-role-batch-request` has been deleted.
* Delete `GetUserBatchRequestHandler` as the `gdbots:iam:mixin:get-user-batch-request` has been deleted.
* Delete `UniqueRoleValidator` as gdbots/ncr now provides `UniqueNodeValidator` which covers id and slug uniqueness.
* Rename `UniqueUserValidator` to `UserValidator` as it covers validation other than uniqueness.


## v0.3.0
* Require `"gdbots/ncr": "^0.2.4 || ~0.3"` and refactor all handlers and projector to use abstract classes provided by gdbots/ncr.


## v0.2.1
* Add check in command handlers to ensure the node is really a proper node (user/role).
* Remove setting of `etag` in `CreateRoleHandler` and `UpdateRoleHandler` as that is now handled in gdbots/ncr.
* Rename `RoleProjector` and `UserProjector` to `NcrRoleProjector` and `NcrUserProjector` to clarify that these project to the Ncr. 


## v0.2.0
__BREAKING CHANGES__

* Require `"gdbots/ncr": "~0.2"`.
* Add `handlesCuries` method to all handlers so Symfony autoconfigure works.
* Add `PbjxValidator` and `PbjxProjector` marker interfaces to appropriate classes.
* issue #5: Update `RoleProjector` to use hard delete.


## v0.1.0
* Initial version.
