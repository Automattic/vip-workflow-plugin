----
### :warning: This plugin is currently in Beta. It is designed to run on [WordPress VIP](https://wpvip.com). This beta release is not intended for use on a production environment.
----

# WordPress VIP Workflow Plugin (Beta)

[![PHP Tests](https://github.com/Automattic/vip-workflow-plugin/actions/workflows/php-tests.yml/badge.svg)](https://github.com/Automattic/vip-workflow-plugin/actions/workflows/php-tests.yml)[![JS Tests](https://github.com/Automattic/vip-workflow-plugin/actions/workflows/js-tests.yml/badge.svg)](https://github.com/Automattic/vip-workflow-plugin/actions/workflows/php-tests.yml)

The WordPress VIP Workflow Plugin allows you to control your editorial workflow. You can set up custom statuses for every phase of your workflow. Each of these custom statuses can be restricted to certain user roles and transitions between statuses generate actions, like notifications.

This plugin is currently developed for use on WordPress sites hosted on [WordPress VIP](https://wpvip.com).

- [Installation](#installation)
	- [Install via `git subtree`](#install-via-git-subtree)
	- [Install via ZIP file](#install-via-zip-file)
	- [Plugin activation](#plugin-activation)
- [Usage](#usage)
	- [Admin](#admin)
	- [Block Editor](#block-editor)
- [Code Filters](#code-filters)
	- [`vw_notification_ignored_statuses`](#vw_notification_ignored_statuses)
	- [`vw_notification_send_to_webhook_payload`](#vw_notification_send_to_webhook_payload)
- [Development](#development)
	- [Building the plugin](#building-the-plugin)
	- [Using Hot Module Replacement](#using-hot-module-replacement)
- [Credits](#credits)

## Installation

The latest version of the plugin is available in the default `trunk` branch of this repository.

### Install via `git subtree`

We recommend installing the latest plugin version [via `git subtree`][wpvip-plugin-subtrees] within your site's repository:

```bash
# Enter your project's root directory:
cd my-site-repo/

# Add a subtree for the trunk branch:
git subtree add --prefix plugins/vip-workflow-plugin git@github.com:Automattic/vip-workflow-plugin.git trunk --squash
```

To deploy the plugin to a remote branch, `git push` the committed subtree.

The `trunk` branch will stay up to date with the latest version of the plugin. Use this command to pull the latest `trunk` branch changes:

```bash
git subtree pull --prefix plugins/vip-workflow-plugin git@github.com:Automattic/vip-workflow-plugin.git trunk --squash
```

Ensure that the plugin is up-to-date by pulling changes often.

Note: We **do not recommend** using `git submodule`. [Submodules on WPVIP that require authentication][wpvip-plugin-submodules] will fail to deploy.

### Install via ZIP file

The latest version of the plugin can be downloaded from the [repository's Releases page][repo-releases]. Unzip the downloaded plugin and add it to the `plugins/` directory of your site's GitHub repository.

### Plugin activation

We recommend [activating plugins with code][wpvip-plugin-activate].

## Usage

### Admin

Once the plugin is activated, go to the `VIP Workflow` menu in `wp-admin` to configure your workflow. 

By default, the following post statuses are created:

1. Pitch
2. Assigned
3. In Progress
4. Draft
5. Pending Review

The plugin doesn't expect any specific configuration, so your first step is to set up statuses that reflect your workflow. You may notice that the steps are listed in a linear order. The plugin assumes a linear workflow where content is moving from creation to publish.

By default, the plugin prevents publishing a post or page (the supported post types are configurable within settings) unless it has reached the last status on your list. This feature can be turned off under settings under the `Publish Guard` option.

The plugin also sends notifications when a post's status changes. By default, email notifications are turned on for the blog admin. Additional email recipients can be configured. You can also set up webhook notifications under settings.

### Editorial Experience

To switch your post status, simply select the new status from the dropdown in the sidebar. 

## Code Filters

### `vw_notification_ignored_statuses`

When a post changes status, configured notifications are sent. You can use this filter to prevent notifications when a post transitions into specific statuses. By default, the plugin already filters the built-in `inherit` and `auto-draft` statuses.

```php
/**
* Filter the statuses that should be ignored when sending notifications
*
* @param array $ignored_statuses Array of statuses that should be ignored when sending notifications
* @param string $post_type The post type of the post
*/
apply_filters( 'vw_notification_ignored_statuses', [ $old_status, 'inherit', 'auto-draft' ], $post->post_type );
```

### `vw_notification_send_to_webhook_payload`

Change the payload sent to the webhook, when the status of a post changes. By default, it is as follows:

```
{
  "text": "*vipgo* changed the status of *Post #59 - <http://test-site.vipdev.lndo.site/wp-admin/post.php?post=59&action=edit|hello>* from *Draft* to *Pending Review*"
}
```

```php
/**
* Filter the payload before sending it to the webhook
*
* @param array $payload Payload to be sent to the webhook
* @param string $action Action being taken
* @param WP_User $user User who is taking the action
* @param WP_Post $post Post that the action is being taken on
*/
apply_filters( 'vw_notification_send_to_webhook_payload', $payload, $action, $user, $post );
```

## Development

This plugin uses `wp-env` for development, and `wp-env` to run the tests written for the plugin. `wp-env` requires Docker so please ensure you have that installed on your system first. 

To install `wp-env`, use the following command:

```
npm -g i @wordpress/env
```

Read more about `wp-env` [here](https://www.npmjs.com/package/@wordpress/env).

Optionally, it's also possible to use `vip dev-env` instead for development. Installation instructions for the VIP cli can be found [here](https://docs.wpvip.com/vip-cli/installing-vip-cli/), and instructions on how to setup `vip dev-env` can be found [here](https://docs.wpvip.com/vip-cli/commands/dev-env/).

This plugin also uses Composer to manage PHP dependencies. Composer can be downloaded [here](https://getcomposer.org/download/).

### Building the plugin

1. Install PHP dependencies: `composer install`
2. Install NPM dependencies: `npm install`
3. Start dev environment: `wp-env start`

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

## Credits

This plugin has been based on the wonderful [EditFlow](https://github.com/Automattic/Edit-Flow) plugin developed by Daniel Bachhuber, Scott Bressler, Mohammad Jangda, and others.

<!-- Links -->
[repo-releases]: https://github.com/Automattic/vip-workflow-plugin/releases
[wpvip-plugin-activate]: https://docs.wpvip.com/how-tos/activate-plugins-through-code/
[wpvip-plugin-submodules]: https://docs.wpvip.com/technical-references/plugins/installing-plugins-best-practices/#h-submodules
[wpvip-plugin-subtrees]: https://docs.wpvip.com/technical-references/plugins/installing-plugins-best-practices/#h-subtrees
