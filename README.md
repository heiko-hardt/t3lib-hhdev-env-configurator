# heikohardt/typo3-env-configurator

Parses typed `TYPO3__*` environment variables into a nested config array, ready to be
merged into `$GLOBALS['TYPO3_CONF_VARS']`. Extracted from the `hhdev_cleverreach` TYPO3
extension so it can be reused across projects instead of being copy-pasted into every
extension's `Tests/Environment/` (or `config/system/`) directory.

## Installation

```
composer require heikohardt/typo3-env-configurator
```

## Usage

Call `Configurator::fromEnvironment()` early in the TYPO3 bootstrap — typically from
`config/system/additional.php` — and merge the result into `$GLOBALS['TYPO3_CONF_VARS']`
yourself:

```php
<?php

declare(strict_types=1);

use HeikoHardt\Typo3EnvConfigurator\Configurator;

$GLOBALS['TYPO3_CONF_VARS'] = array_replace_recursive(
    $GLOBALS['TYPO3_CONF_VARS'],
    Configurator::fromEnvironment()
);
```

`fromEnvironment()` reads all environment variables prefixed `TYPO3__`, splits the
remainder of the name on `__` into a config path, parses the (optionally typed) value,
and returns the resulting nested array — it never touches `$GLOBALS` itself. Merging it
into `TYPO3_CONF_VARS` (or anywhere else) is the caller's decision.

## Env-var syntax

```
TYPO3__<PATH__SEGMENTS>=[<TYPE>:]<value>
```

| Example env var | After merging into `TYPO3_CONF_VARS` |
| --- | --- |
| `TYPO3__DB__Connections__Default__host=db.example.com` | `$GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['host'] = 'db.example.com'` |
| `TYPO3__DB__Connections__Default__port=INT:3306` | `...['port'] = 3306` (int) |
| `TYPO3__SYS__devIPmask=BOOL:false` | `...['devIPmask'] = false` (bool) |
| `TYPO3__SYS__trustedHostsPattern=ARRAY:foo.tld, bar.tld` | `...['trustedHostsPattern'] = ['foo.tld', 'bar.tld']` |
| `TYPO3__SYS__foo=JSON:{"a":1}` | `...['foo'] = ['a' => 1]` |
| `TYPO3__SYS__foo=NULL:` | `...['foo'] = null` |

Supported type prefixes (case-insensitive): `INT`/`INTEGER`, `FLOAT`/`DOUBLE`,
`BOOL`/`BOOLEAN`, `ARRAY` (comma-separated, trimmed), `JSON`, `NULL`. Without a
recognized type prefix, the raw string value is used as-is. Values that fail to parse
(e.g. invalid JSON) trigger an `E_USER_WARNING` and are skipped rather than aborting the
whole bootstrap.

## Development / Testing

This repository ships a [Dev Container](.devcontainer/devcontainer.json) for a
ready-to-use setup. Prefer a container-native checkout over a local bind mount:

- **VS Code**: run "Dev Containers: Clone Repository in Container Volume…" from the
  Command Palette and point it at this repository's URL. The checkout then lives
  entirely inside a Docker volume instead of being bind-mounted from your host,
  avoiding bind-mount overhead (especially on macOS/Windows) and host/container
  drift.
- **[GitHub Codespaces](https://github.com/features/codespaces)**: works out of the
  box — Codespaces always runs remote, so there's no local checkout at all.

Cloning locally first and then using "Reopen in Container" is supported, but not the
preferred path.

Then, inside the container:

```
composer install
vendor/bin/phpunit
```

## License

[GPL-2.0-or-later](LICENSE)
