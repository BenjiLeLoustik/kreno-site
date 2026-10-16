# NeoPHP Skeleton

Creates a new [NeoPHP](https://github.com/NeoPHP-Framework/neophp) 2 application.

## Requirements

- PHP 8.2 or higher with the `ctype`, `json`, `mbstring` and `tokenizer` extensions
- Composer 2

## Installation

```bash
composer create-project neophp/skeleton my-project
cd my-project
php bin/neo serve
```

Open http://127.0.0.1:8000.

## Generated files

```
.env
.gitignore
assets/css/app.css
bin/neo
config/config.php
config/routes.yaml
config/services.yaml
config/framework/
config/packages/
public/.htaccess
public/index.php
src/Kernel.php
src/Controller/HomeController.php
templates/base.php
templates/home/index.php
```

Directories: `migrations/`, `public/builds/`, `src/Command/`, `src/Entity/`, `src/Event/`, `src/Form/`, `src/Listener/`, `src/Message/`, `src/MessageHandler/`, `src/Middleware/`, `src/Repository/`, `src/Security/`, `src/Service/`, `src/Task/`, `tests/`, `translations/`, `var/`.

## Modules

Every component, package and process of the framework is enabled. `config/config.php` lists only what the project changes:

```php
return [
    NeoPHP\Package\WebProfiler\WebProfilerManager::class => ['dev' => true],
    NeoPHP\Package\NeoAI\NeoAiManager::class => ['dev' => true],
    NeoPHP\Component\Mailer\MailerManager::class => false,
];
```

`false` disables a module, an array enables it only in the listed environments.

## Console

| Command | Description |
|---|---|
| `php bin/neo` | lists the commands |
| `php bin/neo serve` | starts the development server |
| `php bin/neo route:list` | lists the routes |
| `php bin/neo install` | regenerates the missing project files |

## Documentation

- Getting started: https://github.com/NeoPHP-Framework/neophp/blob/v2.x/docs/v2.x/README.md
- Upgrade from 1.x: https://github.com/NeoPHP-Framework/neophp/blob/v2.x/UPGRADE-2.0.md
- NeoPHP 1: https://github.com/NeoPHP-Framework/neophp/blob/v1.x/docs/v1.x/README.md

## License

MIT