<?php

if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

/**
 * Helper utilities for slimming databases by removing old posts, users. etc.
 *
 */
class WP_Prune_Command extends WP_CLI_Command {

	/**
	 * Remove posts to save database space.
	 *
	 * Defaults to removing 90% of posts in all post types older than six months old.
	 *
	 * [--before=<before>]
	 * : Cutoff date to prune posts before. Defaults to "6 months ago".
	 *
	 * [--sample_rate=<sample_rate>]
	 * : Adjust the number of posts to remove. Defaults to 0.8 (remove 80% of posts).
	 *
	 * [--post_type=<post_type>]
	 * : Limit post types to prune with comma-separated list. Defaults to all.
	 *
	 */
	function posts( $args, $assoc_args ) {
		global $wpdb;

		$before = $assoc_args['before'] ?? date( 'Y-m-d H:i:s', strtotime( '-6 months' ) );
		$sample_rate = floatval( $assoc_args['sample_rate'] ?? '.8' );

		$posts_where = [
			'type' => isset( $assoc_args['post_type'] ) ? "post_type IN ({$assoc_args['post_type']})" : '',
			'date' => $wpdb->prepare( "post_date < %s", $before ),
			'sample' => $wpdb->prepare( "RAND() < %s", $sample_rate ),
		];

		$where_sql = implode( " AND ", array_filter( $posts_where ) );

		$sql = "
			DELETE {$wpdb->posts}, {$wpdb->postmeta}
			FROM {$wpdb->posts}
			INNER JOIN {$wpdb->postmeta} ON {$wpdb->postmeta}.post_id = {$wpdb->posts}.ID
			WHERE $where_sql;
		";

		$post_count = $wpdb->query( $sql );

		WP_CLI::success( "Deleted $post_count rows (including posts and postmeta)." );
	}

	/**
	 * Remove revisions and auto-drafts to save database space.
	 *
	 */
	function revisions() {

	}

}

WP_CLI::add_command( 'prune', 'WP_Prune_Command' );