# WordPress VIP Workflow Plugin

This WordPress plugin adds additional editorial workflow capabilities to WordPress. This plugin owes it's existance to the wonderful [EditFlow](https://github.com/Automattic/Edit-Flow) plugin developed by Daniel Bachhuber, Scott Bressler, Mohammad Jangda, Automattic, and others.

This plugin is currently developed for use on WordPress sites hosted on the VIP Platform.

## Development

This plugin uses `wp-env` for development and is required to run the tests written for the plugin. `wp-env` requires Docker so please ensure you have that installed on your system first. To install `wp-env`, use the following command:

```
npm -g i @wordpress/env
```

Read more about `wp-env` [here](https://www.npmjs.com/package/@wordpress/env).

This plugin also uses Composer to manage PHP dependencies. Composer can be downloaded [here](https://getcomposer.org/download/).

### Getting started

1. Clone the plugin repo: `git clone git@github.com:Automattic/vip-workflow-plugin.git`
2. Changed to cloned directory: `cd /path/to/repo`
3. Install PHP dependencies: `composer install`
4. Install NPM dependencies: `npm install`
5. Start dev environment: `wp-env start`

### Running tests

Ensure that the dev environment has already been started with `wp-env start`.

1. Integration test: `composer run integration`
2. Multi-site integration test: `composer run integration-ms`

### Using Hot Module Replacement

React hot reloading is supported. A few configuration steps are required for the setup:

1. Set `define( 'SCRIPT_DEBUG', true );` in your `wp-config.php` or `vip-config.php`. At the time of writing, this is [a `wp-scripts` limitation](https://github.com/WordPress/gutenberg/blob/9e07a75/packages/scripts/README.md?plain=1#L390).
2. Run `npm run dev:hot`. If you're running WordPress on a non-localhost hostname, e.g. on `vip dev-env`, you may also need to specify the hostname:

    ```bash
    HOST=mysite.vipdev.lndo.site npm run dev:hot
    ```

    This can also be specified using a `.env` configuration file:

    ```
    HOST=mysite.vipdev.lndo.site
    ```

    If you use `wp-env`, you should be able to skip specifying `HOST` manually.

3. If HMR is not working and you're developing out of a new component tree, you may also need to opt-in to hot module reloading via [`module.hot.accept()`](https://github.com/Automattic/vip-workflow-plugin/blob/e058354/modules/custom-status/lib/custom-status-configure.js#L19-L21)
