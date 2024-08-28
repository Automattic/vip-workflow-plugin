<?php

namespace VIPWorkflow\Modules\Telemetry\Events;

use VIPWorkflow\Modules\Telemetry\Tracker;
use WP_Post;
use WP_User;

function record_notification_sent(
	WP_Post $post,
	string $subject,
	WP_User $user,
	Tracker $tracker
) {
	$tracker->record_event( 'notification_sent', [
		'subject' => $subject,
		'post_id' => $post->ID,
	] );
}
