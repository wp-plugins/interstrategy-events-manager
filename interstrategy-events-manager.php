<?php
/*
Plugin Name: Interstrategy Events Manager
Plugin URI: http://www.interstrategy.net
Description: Allows you to manage events
Version: 1.0
Author: Interstrategy Inc.
Author URI: http://www.interstrategy.net
License: GPLv2
*/

class BE_Events_Manager {
	var $instance;

	public function __construct() {
		$this->instance =& $this;
		add_action( 'plugins_loaded', array( $this, 'init' ) );	
	}

	public function init() {
		// Create Post Type
		add_action( 'init', array( $this, 'post_type' ) );
		add_filter( 'manage_edit-event_columns', array( $this, 'edit_event_columns' ) ) ;
		add_action( 'manage_event_posts_custom_column', array( $this, 'manage_event_columns' ), 10, 2 );
		add_filter( 'manage_edit-event_sortable_columns', array( $this, 'event_sortable_columns' ) );
		add_action( 'load-edit.php', array( $this, 'edit_event_load' ) );
		// Create Taxonomy
		add_action( 'init', array( $this, 'taxonomies' ) );
		// Create Metabox
		add_filter( 'cmb_meta_boxes', array( $this, 'metaboxes' ) );
		add_action( 'init', array( $this, 'initialize_meta_boxes' ), 9999 );
		// Modify Event Listings query
		add_action( 'pre_get_posts', array( $this, 'event_query' ) );
	}
	
	/** 
	 * Register Post Type
	 * @link http://codex.wordpress.org/Function_Reference/register_post_type
	 *
	 */

	public function post_type() {
		$labels = array(
			'name' => 'Events',
			'singular_name' => 'Event',
			'add_new' => 'Add New',
			'add_new_item' => 'Add New Event',
			'edit_item' => 'Edit Event',
			'new_item' => 'New Event',
			'view_item' => 'View Event',
			'search_items' => 'Search Events',
			'not_found' =>  'No events found',
			'not_found_in_trash' => 'No events found in trash',
			'parent_item_colon' => '',
			'menu_name' => 'Events'
		);
		
		$args = array(
			'labels' => $labels,
			'public' => true,
			'publicly_queryable' => true,
			'show_ui' => true, 
			'show_in_menu' => true, 
			'query_var' => true,
			'rewrite' => true,
			'capability_type' => 'post',
			'has_archive' => true, 
			'hierarchical' => false,
			'menu_position' => null,
			'supports' => array('title','editor', 'excerpt')
		); 
	
		register_post_type( 'event', $args );	
	}
	
	/**
	 * Edit Column Titles
	 * @link http://devpress.com/blog/custom-columns-for-custom-post-types/
	 *
	 */
	
	function edit_event_columns( $columns ) {
	
		$columns = array(
			'cb' => '<input type="checkbox" />',
			'title' => __( 'Event' ),
			'start_date' => __( 'Start Date' ),
			'end_date' => __( 'End Date' ),
			'date' => __( 'Published Date' )
		);
	
		return $columns;
	}
	
	/**
	 * Edit Column Content
	 * @link http://devpress.com/blog/custom-columns-for-custom-post-types/
	 *
	 */

	function manage_event_columns( $column, $post_id ) {
		global $post;
	
		switch( $column ) {
	
			/* If displaying the 'duration' column. */
			case 'start_date' :
	
				/* Get the post meta. */
				$start = esc_attr( date( 'F j, Y', get_post_meta( $post_id, 'be_events_manager_start_date', true ) ) );
	
				/* If no duration is found, output a default message. */
				if ( empty( $start ) )
					echo __( 'Unknown' );
	
				/* If there is a duration, append 'minutes' to the text string. */
				else
					echo $start;
	
				break;
	
			/* If displaying the 'genre' column. */
			case 'end_date' :
	
				/* Get the post meta. */
				$end = esc_attr( date( 'F j, Y', get_post_meta( $post_id, 'be_events_manager_end_date', true ) ) );
	
				/* If no duration is found, output a default message. */
				if ( empty( $end ) )
					echo __( 'Unknown' );
	
				/* If there is a duration, append 'minutes' to the text string. */
				else
					echo $end;
	
				break;
	
			/* Just break out of the switch statement for everything else. */
			default :
				break;
		}
	}	 
	
	/**
	 * Make Columns Sortable
	 * @link http://devpress.com/blog/custom-columns-for-custom-post-types/
	 *
	 */

	function event_sortable_columns( $columns ) {
	
		$columns['start_date'] = 'start_date';
		$columns['end_date'] = 'end_date';
	
		return $columns;
	}	 
	
	function edit_event_load() {
		add_filter( 'request', array( $this, 'sort_events' ) );
	}
	
	function sort_events( $vars ) {

		/* Check if we're viewing the 'event' post type. */
		if ( isset( $vars['post_type'] ) && 'event' == $vars['post_type'] ) {
	
			/* Check if 'orderby' is set to 'start_date'. */
			if ( isset( $vars['start_date'] ) && 'start_date' == $vars['orderby'] ) {
	
				/* Merge the query vars with our custom variables. */
				$vars = array_merge(
					$vars,
					array(
						'meta_key' => 'be_events_manager_start_date',
						'orderby' => 'meta_value_num'
					)
				);
			}
			
			/* Check if 'orderby' is set to 'end_date'. */
			if ( isset( $vars['end_date'] ) && 'end_date' == $vars['orderby'] ) {
	
				/* Merge the query vars with our custom variables. */
				$vars = array_merge(
					$vars,
					array(
						'meta_key' => 'be_events_manager_end_date',
						'orderby' => 'meta_value_num'
					)
				);
			}
			
		}
	
		return $vars;
	
	}

	/**
	 * Create Taxonomies
	 * @link http://codex.wordpress.org/Function_Reference/register_taxonomy
	 *
	 */
	
	function taxonomies() {
		$override = apply_filters( 'be_events_manager_taxonomy_override', false );
		if( false === $override ) {
		
			$labels = array(
				'name' => 'Categories',
				'singular_name' => 'Category',
				'search_items' =>  'Search Categories',
				'all_items' => 'All Categories',
				'parent_item' => 'Parent Category',
				'parent_item_colon' => 'Parent Category:',
				'edit_item' => 'Edit Category',
				'update_item' => 'Update Category',
				'add_new_item' => 'Add New Category',
				'new_item_name' => 'New Category Name',
				'menu_name' => 'Category'
			); 	
		
			register_taxonomy( 'event-category', array('event'), array(
				'hierarchical' => true,
				'labels' => $labels,
				'show_ui' => true,
				'query_var' => true,
				'rewrite' => array( 'slug' => 'event-category' ),
			));
		
		}
	}
	
	/**
	 * Create Metaboxes
	 * @link http://www.billerickson.net/wordpress-metaboxes/
	 *
	 */
	
	function metaboxes( $meta_boxes ) {
		
		$prefix = 'be_events_manager_';
		$events_metabox = array(
		    'id' => 'event-details',
		    'title' => 'Event Details',
		    'pages' => array('event'), 
			'context' => 'normal',
			'priority' => 'high',
			'show_names' => true, 
		    'fields' => array(
		    	array(
		    		'name' => 'Location',
		    		'id' => $prefix . 'location',
		    		'desc' => '',
		    		'type' => 'text'
		    	),
		    	array(
		    		'name' => 'Start Date',
		    		'id' => $prefix . 'start_date',
		    		'desc' => '',
		    		'type' => 'text_date_timestamp',
		    	),
		    	array(
		    		'name' => 'End Date',
		    		'id' => $prefix . 'end_date',
		    		'desc' => '',
		    		'type' => 'text_date_timestamp',
		    	),
		    	array(
		    		'name' => 'Cost',
		    		'id' => $prefix . 'cost',
		    		'desc' => '',
		    		'type' => 'text_money',
		    	),
		    	array(
		    		'name' => 'Telephone',
		    		'id' => $prefix . 'telephone',
		    		'desc' => '',
		    		'type' => 'text_medium'
		    	)
		    )
		
		);
		
		// Use this to override the metabox and create your own
		$override = apply_filters( 'be_events_manager_metabox_override', false );
		if ( false === $override ) $meta_boxes[] = $events_metabox;
		
		return $meta_boxes;
	}

	function initialize_meta_boxes() {
	    if (!class_exists('cmb_Meta_Box')) {
	        require_once( 'lib/metabox/init.php' );
	    }
	}
	
	function event_query( $query ) {
		global $wp_query;
		// If you don't want the plugin to mess with the query, use this filter to override it
		$override = apply_filters( 'be_events_manager_query_override', false );
		if ( !is_admin() && $wp_query === $query && ( false === $override ) && ( is_post_type_archive( 'event' ) || is_tax( 'event-category' ) ) ) {
			$meta_query = array(
				array(
					'key' => 'be_events_manager_end_date',
					'value' => time(),
					'compare' => '>'
				)
			);
			$query->set( 'orderby', 'meta_value_num' );
			$query->set( 'order', 'ASC' );
			$query->set( 'meta_query', $meta_query );
			$query->set( 'meta_key', 'be_events_manager_start_date' );
		}
		
	}
	
}

new BE_Events_Manager;