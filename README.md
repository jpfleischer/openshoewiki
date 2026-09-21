<p align="center"><img height="150" src="/.github/banner.png" alt="OpenShoeWiki banner"></p>

## OpenShoeWiki

This repository contains OpenShoeWiki (OSW), a Laravel-based archive and catalog application for shoes and footwear. It includes the public browsing experience, search/filtering, user closets and wishlists, and a Filament admin panel for managing catalog data.

### Technology

This codebase is primarily written in PHP, using the [Laravel](https://laravel.com/) framework. Some search functionality is written in [Vue.js](https://vuejs.org). UI styling is based on [Bootstrap 4](https://getbootstrap.com/docs/4.6/getting-started/introduction/). The admin interface uses [Filament](https://filamentphp.com/).

### Local setup

For a fresh checkout, create a local environment file and generate a unique Laravel application key after installing the Composer dependencies:

```sh
cp .env.example .env
php artisan key:generate
```

The command writes the generated key to `.env`. If running Artisan in a container that does not have the checkout's `.env` mounted, use `php artisan key:generate --show` and copy the displayed value into the host `.env` instead. Keep `.env` private and do not commit it. Do not regenerate the key for an existing installation: doing so logs users out and can make data encrypted with the old key unreadable.

### Licensing

The majority of this repository is offered under [the BSD 3-Clause license](https://choosealicense.com/licenses/bsd-3-clause/).

Asset files under `/public` are **not** licensed for reuse as-is. They contain branded images and visual assets from the original upstream project and should be replaced in any public fork.
