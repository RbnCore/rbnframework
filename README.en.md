**TR** | [English](README.md)

# RBN Core Framework

**Version:** `0.9.0` · **License:** MIT · **PHP:** 8.1+

RBN Core Framework is a lightweight PHP application framework with a multi-tenant
architecture: a single core hosts many independent projects. Routing, templating,
the database layer, the security shield, the task scheduler and a command line tool
(`rbn`) are included; it depends on only three Composer packages.

> This repository is the framework **source code**. It is not an application.

## Requirements

| Component | Version / Requirement |
|---|---|
| PHP | 8.1 or newer (developed on 8.2 and 8.3) |
| PHP extensions | `pdo`, `pdo_mysql`, `mbstring`, `curl`, `openssl`, `zip` |
| Composer | 2.x |
| Database | MySQL / MariaDB |

Composer dependencies: `vlucas/phpdotenv`, `phpmailer/phpmailer`, `iyzico/iyzipay-php`.

## Installation

```bash
git clone https://github.com/RbnCore/rbnframework.git
cd rbnframework
composer install
```

## Secrets (`secrets.php`)

All secrets (database, SMTP, cPanel, application key, service API keys) are read from a
**single file**: `Core/System/Config/Secrets/secrets.php`. This file is never committed
(it is excluded by `.gitignore`). Copy the template:

```bash
cp Core/System/Config/Secrets/secrets.example.php Core/System/Config/Secrets/secrets.php
chmod 600 Core/System/Config/Secrets/secrets.php
```

Fill in every `CHANGE_ME` placeholder. Sections: `master_db`, `smtp`, `cpanel`,
`app_key`, `api`. See `Core/System/Config/Secrets/README.md` for details.

The reader is *fail-closed*: a missing, broken or incomplete file raises a clear error
instead of silently falling back to a default, and no secret value is ever written into
an error message. On a live server deploy the **code first**, then create `secrets.php`
by hand.

## Folder map

```
rbnframework/
├── Core/         # Base, Database, Http, Render, Routes, Services, Support, System
├── Bundles/      # RbnSuite (RbnAdmin, RbnAuth, RbnStudio), Internal
├── Packages/     # RbnApi, RbnEmail, RbnFile, RbnUtility (and others)
├── Resources/    # Assets, views and data files
├── vendor/       # Composer dependencies (never committed)
└── rbn           # Command line entry point
```

## Command line: `rbn`

```bash
php rbn                              # list all commands
php rbn system:doctor                # diagnose system, master DB and projects
php rbn project:list                 # list projects
php rbn make:controller AdimKontrol  # generate Controller/Model/Request/Migration
php rbn migrate                      # run pending migrations
php rbn migrate:status               # show migration status
php rbn cache:clear                  # clear cache
php rbn logs:clear                   # clear logs
php rbn system:cron                  # run scheduled tasks
```

Global flags: `--project=<key>` (run in a project context) and `--master` (master
database context). `master:migrate` never runs automatically; it must be given by hand.

## Security

Please **do not** open a public issue for a security vulnerability. See
[.github/SECURITY.md](.github/SECURITY.md) for supported versions, private reporting
channels and coordinated disclosure.

## Changelog and upgrading

- [.github/CHANGELOG.md](.github/CHANGELOG.md)
- [.github/UPGRADING.md](.github/UPGRADING.md)

Both documents are written in Turkish.

## License and attribution

Released under the **MIT** license — full text in [LICENSE](LICENSE). You may use,
modify and redistribute it; the only condition is that the copyright and license notice
are preserved.

If you build on this work, a mention of RbnBilisim / RbnCore is appreciated (optional,
not required).

## Contributing

Open an issue first, then send a small, single-purpose pull request. Never report a
security finding through an issue — see [SECURITY.md](.github/SECURITY.md).