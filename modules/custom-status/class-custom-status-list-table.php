<?php

/**
 * Custom Statuses uses WordPress' List Table API for generating the custom status management table
 *
 * @since 0.7
 */
class VW_Custom_Status_List_Table extends WP_List_Table {

	protected $callback_args;
	protected $default_status;

	/**
	 * Construct the extended class
	 */
	public function __construct() {

		parent::__construct( [
			'plural'   => 'custom statuses',
			'singular' => 'custom status',
			'ajax'     => true,
		] );
	}

	/**
	 * Pull in the data we'll be displaying on the table
	 *
	 * @since 0.7
	 */
	public function prepare_items() {
		global $vip_workflow;

		$columns               = $this->get_columns();
		$hidden                = [
			'position',
		];
		$sortable              = [];
		$this->_column_headers = [ $columns, $hidden, $sortable ];

		$this->items          = $vip_workflow->custom_status->get_custom_statuses();
		$total_items          = count( $this->items );
		$this->default_status = $vip_workflow->custom_status->get_default_custom_status()->slug;

		$this->set_pagination_args( [
			'total_items' => $total_items,
			'per_page'    => $total_items,
		] );
	}

	/**
	 * Message to be displayed when there are no custom statuses. Should never be displayed, but we'll customize it
	 * just in case.
	 *
	 * @since 0.7
	 */
	public function no_items() {
		_e( 'No custom statuses found.', 'vip-workflow' );
	}

	/**
	 * Table shows (hidden) position, status name, status description, and the post count for each activated
	 * post type
	 *
	 * @since 0.7
	 *
	 * @return array $columns Columns to be registered with the List Table
	 */
	public function get_columns() {
		global $vip_workflow;

		$columns = [
			'position'    => __( 'Position', 'vip-workflow' ),
			'name'        => __( 'Name', 'vip-workflow' ),
			'description' => __( 'Description', 'vip-workflow' ),
		];

		$post_types           = get_post_types( '', 'objects' );
		$supported_post_types = $vip_workflow->helpers->get_post_types_for_module( $vip_workflow->custom_status->module );
		foreach ( $post_types as $post_type ) {
			if ( in_array( $post_type->name, $supported_post_types ) ) {
				$columns[ $post_type->name ] = $post_type->label;
			}
		}

		return $columns;
	}

	/**
	 * Fallback column callback.
	 * Primarily used to display post count for each post type
	 *
	 * @since 0.7
	 *
	 * @param object $item Custom status as an object
	 * @param string $column_name Name of the column as registered in $this->prepare_items()
	 * @return string $output What will be rendered
	 */
	public function column_default( $item, $column_name ) {
		global $vip_workflow;

		// Handle custom post counts for different post types
		$post_types = get_post_types( '', 'names' );
		if ( in_array( $column_name, $post_types ) ) {

			// @todo Cachify this
			$post_count = wp_cache_get( "vw_custom_status_count_$column_name" );
			if ( false === $post_count ) {
				$posts       = wp_count_posts( $column_name );
				$post_status = $item->slug;
				// To avoid error notices when changing the name of non-standard statuses
				if ( isset( $posts->$post_status ) ) {
					$post_count = $posts->$post_status;
				} else {
					$post_count = 0;
				}
				//wp_cache_set( "vw_custom_status_count_$column_name", $post_count );
			}
			$output = sprintf( '<a title="See all %1$ss saved as \'%2$s\'" href="%3$s">%4$s</a>', $column_name, $item->name, $vip_workflow->helpers->filter_posts_link( $item->slug, $column_name ), $post_count );
			return $output;
		}
	}

	/**
	 * Hidden column for storing the status position
	 *
	 * @since 0.7
	 *
	 * @param object $item Custom status as an object
	 * @return string $output What will be rendered
	 */
	public function column_position( $item ) {
		return esc_html( $item->position );
	}

	/**
	 * Displayed column showing the name of the status
	 *
	 * @since 0.7
	 *
	 * @param object $item Custom status as an object
	 * @return string $output What will be rendered
	 */
	public function column_name( $item ) {
		global $vip_workflow;

		$item_edit_link = esc_url( $vip_workflow->custom_status->get_link( [
			'action'  => 'edit-status',
			'term-id' => $item->term_id,
		] ) );

		$output = '<strong><a href="' . $item_edit_link . '">' . esc_html( $item->name ) . '</a>';
		if ( $item->slug == $this->default_status ) {
			$output .= ' - ' . __( 'Default', 'vip-workflow' );
		}
		$output .= '</strong>';

		// Don't allow for any of these status actions when adding a new custom status
		if ( isset( $_GET['action'] ) && 'add' == $_GET['action'] ) {
			return $output;
		}

		$actions                         = [];
		$actions['edit']                 = "<a href='$item_edit_link'>" . __( 'Edit', 'vip-workflow' ) . '</a>';
		$actions['inline hide-if-no-js'] = '<a href="#" class="editinline">' . __( 'Quick&nbsp;Edit' ) . '</a>';
		$actions['make_default']         = sprintf( '<a href="%1$s">' . __( 'Make&nbsp;Default', 'vip-workflow' ) . '</a>', $vip_workflow->custom_status->get_link( [
			'action'  => 'make-default',
			'term-id' => $item->term_id,
		] ) );

		// Prevent deleting draft status
		if ( 'draft' !== $item->slug && $item->slug !== $this->default_status ) {
			$actions['delete delete-status'] = sprintf( '<a href="%1$s">' . __( 'Delete', 'vip-workflow' ) . '</a>', $vip_workflow->custom_status->get_link( [
				'action'  => 'delete-status',
				'term-id' => $item->term_id,
			] ) );
		}

		$output .= $this->row_actions( $actions, false );
		$output .= '<div class="hidden" id="inline_' . esc_attr( $item->term_id ) . '">';
		$output .= '<div class="name">' . esc_html( $item->name ) . '</div>';
		$output .= '<div class="description">' . esc_html( $item->description ) . '</div>';
		$output .= '</div>';

		return $output;
	}

	/**
	 * Displayed column showing the description of the status
	 *
	 * @since 0.7
	 *
	 * @param object $item Custom status as an object
	 * @return string $output What will be rendered
	 */
	public function column_description( $item ) {
		return esc_html( $item->description );
	}

	/**
	 * Prepare and echo a single custom status row
	 *
	 * @since 0.7
	 */
	public function single_row( $item ) {
		static $alternate_class = '';
		$alternate_class        = ( '' == $alternate_class ? ' alternate' : '' );
		$row_class              = ' class="term-static' . $alternate_class . '"';

		echo wp_kses_post( '<tr id="term-' . $item->term_id . '"' . $row_class . '>' );
		echo wp_kses_post( $this->single_row_columns( $item ) );
		echo '</tr>';
	}

	/**
	 * Hidden form used for inline editing functionality
	 *
	 * @since 0.7
	 */
	public function inline_edit() {
		include_once __DIR__ . '/views/inline-edit.php';
	}
}
