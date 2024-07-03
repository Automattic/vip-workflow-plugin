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
