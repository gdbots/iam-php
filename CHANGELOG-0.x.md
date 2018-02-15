# CHANGELOG for 0.x
This changelog references the relevant changes done in 0.x versions.


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
