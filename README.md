iam-php
=============

Php library that provides implementations for __gdbots:iam__ schemas.  Using this library assumes that you've already created and compiled your own pbj classes using the [Pbjc](https://github.com/gdbots/pbjc-php) and are making use of the __"gdbots:iam:mixin:*"__ mixins from [gdbots/schemas](https://github.com/gdbots/schemas).


## Symfony Integration
Enabling these services in a Symfony app is done by importing classes and letting Symfony autoconfigure and autowire them.

__config/packages/iam.yml:__

```yaml
services:
  _defaults:
    autowire: true
    autoconfigure: true
    public: false

  Gdbots\Iam\:
    resource: '%kernel.project_dir%/vendor/gdbots/iam/src/**/*'
    exclude: '*Aggregate*'

  Gdbots\Iam\NcrAppProjector:
    arguments:
      $enabled: '%env(bool:NCR_PROJECTOR_ENABLED)%'

  Gdbots\Iam\NcrUserProjector:
    arguments:
      $enabled: '%env(bool:NCR_PROJECTOR_ENABLED)%'

```

The above services do __NOT__ handle security though, to get that we need deeper integration with Symfony which is provided by the [gdbots/iam-bundle-php](https://github.com/gdbots/iam-bundle-php).
