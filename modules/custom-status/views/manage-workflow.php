<?php

defined( 'ABSPATH' ) || exit();

?>

<div class="wrap vip-workflow-admin">
	<div class="explanation">
		<h3><?php esc_html_e( 'Configure your editorial workflow.', 'vip-workflow' ); ?></h3>
		<p><?php esc_html_e( 'Starting from the top, each post status represents the publishing worklow to be followed. This workflow can be configured by re-ordering statuses as well as editing/deleting and creating new ones.', 'vip-workflow' ); ?></p>
	</div>

	<div id="workflow-manager-root"></div>
</div>
