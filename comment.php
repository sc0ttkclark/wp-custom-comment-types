<?php
/**
 * Comment functions and comment utility function.
 *
 * @package WordPress
 * @subpackage Post
 * @since 4.0.0
 */

//
// Comment Type Registration
//

/**
 * Creates the initial comment types when 'init' action is fired.
 *
 * @since 4.0.0
 */
function create_initial_comment_types() {

	register_comment_type( 'comment', array(
		'labels' => array(),
		'public'  => true,
		'_builtin' => true, /* internal use only. don't use this when registering your own comment type. */
		'_edit_link' => 'comment.php?action=editcomment&c=%d', /* internal use only. don't use this when registering your own comment type. */
		'capability_type' => 'comment',
		'map_meta_cap' => true,
		'hierarchical' => true, // @todo Is this hierarchical?
		'delete_with_user' => true
	) );

	register_comment_type( 'pingback', array(
		'labels' => array(),
		'public'  => true,
		'_builtin' => true, /* internal use only. don't use this when registering your own comment type. */
		'_edit_link' => 'comment.php?action=editcomment&c=%d', /* internal use only. don't use this when registering your own comment type. */
		'capability_type' => 'comment',
		'map_meta_cap' => true,
		'hierarchical' => false,
		'delete_with_user' => true
	) );

	register_comment_status( 'approve', array(
		 /* translators: comment status  */
		'label'       => _x( 'Approved', 'adjective' ),
		'public'      => true,
		'_builtin'    => true, /* internal use only. */
		'label_count' => _n_noop( 'Approved <span class="count">(%s)</span>', 'Approved <span class="count">(%s)</span>' ),
	) );

	register_comment_status( 'spam', array(
		 /* translators: comment status  */
		'label'       => _x( 'Spam', 'adjective' ),
		'protected'   => true,
		'_builtin'    => true, /* internal use only. */
		'label_count' => _n_noop('Spam <span class="count">(%s)</span>', 'Spam <span class="count">(%s)</span>' ),
	) );

	register_comment_status( 'hold', array(
		'label'       => __( 'Unapproved' ),
		'protected'   => true,
		'_builtin'    => true, /* internal use only. */
		'label_count' => _n_noop( 'Pending <span class="count">(%s)</span>', 'Pending <span class="count">(%s)</span>' ),
	) );

	register_comment_status( 'private', array(
		'label'       => _x( 'Private', 'comment' ),
		'private'     => true,
		'_builtin'    => true, /* internal use only. */
		'label_count' => _n_noop( 'Private <span class="count">(%s)</span>', 'Private <span class="count">(%s)</span>' ),
	) );

	register_comment_status( 'trash', array(
		'label'       => _x( 'Trash', 'comment' ),
		'internal'    => true,
		'_builtin'    => true, /* internal use only. */
		'label_count' => _n_noop( 'Trash <span class="count">(%s)</span>', 'Trash <span class="count">(%s)</span>' ),
		'show_in_admin_status_list' => true,
	) );

}
add_action( 'init', 'create_initial_comment_types', 0 ); // highest priority

/**
 * WordPress Comment class.
 *
 * @since 4.0.0
 *
 */
final class WP_Comment {

	/**
	 * Comment ID.
	 *
	 * @var int
	 */
	public $comment_ID;

	/**
	 * Comment Comment ID.
	 *
	 * @var int
	 */
	public $comment_comment_ID = 0;

	/**
	 * ID of comment author.
	 *
	 * A numeric string, for compatibility reasons.
	 *
	 * @var string
	 */
	public $user_id = '0';

	/**
	 * Name of comment author.
	 *
	 * @var string
	 */
	public $comment_author = '';

	/**
	 * Email of comment author.
	 *
	 * @var string
	 */
	public $comment_author_email = '';

	/**
	 * URL of comment author.
	 *
	 * @var string
	 */
	public $comment_author_url = '';

	/**
	 * IP of comment author.
	 *
	 * @var string
	 */
	public $comment_author_IP = '';

	/**
	 * User Agent of comment author.
	 *
	 * @var int
	 */
	public $comment_agent = '';

	/**
	 * The comment's local publication time.
	 *
	 * @var string
	 */
	public $comment_date = '0000-00-00 00:00:00';

	/**
	 * The comment's GMT publication time.
	 *
	 * @var string
	 */
	public $comment_date_gmt = '0000-00-00 00:00:00';

	/**
	 * The comment's content.
	 *
	 * @var string
	 */
	public $comment_content = '';

	/**
	 * The comment's karma.
	 *
	 * @var int
	 */
	public $comment_karma = 0;

	/**
	 * The comment's approved status.
	 *
	 * @var string
	 */
	public $comment_approved = '';

	/**
	 * ID of a comment's parent comment.
	 *
	 * @var int
	 */
	public $comment_parent = 0;

	/**
	 * The comment's type, like comment or page.
	 *
	 * @var string
	 */
	public $comment_type = 'comment';

	/**
	 * Stores the comment object's sanitization level.
	 *
	 * Does not correspond to a DB field.
	 *
	 * @var string
	 */
	public $filter;

	public static function get_instance( $comment_ID ) {
		global $wpdb;

		$comment_ID = (int) $comment_ID;
		if ( ! $comment_ID )
			return false;

		$_comment = wp_cache_get( $comment_ID, 'comments' );

		if ( ! $_comment ) {
			$_comment = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->comments WHERE comment_ID = %d LIMIT 1", $comment_ID ) );

			if ( ! $_comment )
				return false;

			$_comment = sanitize_comment( $_comment, 'raw' );
			wp_cache_add( $_comment->comment_ID, $_comment, 'comments' );
		} elseif ( empty( $_comment->filter ) ) {
			$_comment = sanitize_comment( $_comment, 'raw' );
		}

		return new WP_Comment( $_comment );
	}

	public function __construct( $comment ) {
		foreach ( get_object_vars( $comment ) as $key => $value )
			$this->$key = $value;
	}

	public function __isset( $key ) {
		return metadata_exists( 'comment', $this->comment_ID, $key );
	}

	public function __get( $key ) {
		$value = get_comment_meta( $this->comment_ID, $key, true );

		if ( $this->filter )
			$value = sanitize_comment_field( $key, $value, $this->comment_ID, $this->filter );

		return $value;
	}

	public function filter( $filter ) {
		if ( $this->filter == $filter )
			return $this;

		if ( $filter == 'raw' )
			return self::get_instance( $this->comment_ID );

		return sanitize_comment( $this, $filter );
	}

	public function to_array() {
		$comment = get_object_vars( $this );

		unset( $comment->filter );

		return $comment;
	}
}

/**
 * Register a comment status. Do not use before init.
 *
 * A simple function for creating or modifying a comment status based on the
 * parameters given. The function will accept an array (second optional
 * parameter), along with a string for the comment status name.
 *
 *
 * Optional $args contents:
 *
 * label - A descriptive name for the comment status marked for translation. Defaults to $comment_status.
 * public - Whether comments of this status should be shown in the front end of the site. Defaults to true.
 * exclude_from_search - Whether to exclude comments with this comment status from search results. Defaults to false.
 * show_in_admin_all_list - Whether to include comments in the edit listing for their comment type
 * show_in_admin_status_list - Show in the list of statuses with comment counts at the top of the edit
 *                             listings, e.g. All (12) | Published (9) | My Custom Status (2) ...
 *
 * Arguments prefixed with an _underscore shouldn't be used by plugins and themes.
 *
 * @package WordPress
 * @subpackage Post
 * @since 4.0.0
 * @uses $wp_comment_statuses Inserts new comment status object into the list
 *
 * @param string $comment_status Name of the comment status.
 * @param array|string $args See above description.
 */
function register_comment_status($comment_status, $args = array()) {
	global $wp_comment_statuses;

	if (!is_array($wp_comment_statuses))
		$wp_comment_statuses = array();

	// Args prefixed with an underscore are reserved for internal use.
	$defaults = array(
		'label' => false,
		'label_count' => false,
		'_builtin' => false,
		'public' => null,
		'internal' => null,
		'protected' => null,
		'private' => null,
		'publicly_queryable' => null,
		'show_in_admin_status_list' => null,
		'show_in_admin_all_list' => null,
	);
	$args = wp_parse_args($args, $defaults);
	$args = (object) $args;

	$comment_status = sanitize_key($comment_status);
	$args->name = $comment_status;

	if ( null === $args->public && null === $args->internal && null === $args->protected && null === $args->private )
		$args->internal = true;

	if ( null === $args->public  )
		$args->public = false;

	if ( null === $args->private  )
		$args->private = false;

	if ( null === $args->protected  )
		$args->protected = false;

	if ( null === $args->internal  )
		$args->internal = false;

	if ( null === $args->publicly_queryable )
		$args->publicly_queryable = $args->public;

	if ( null === $args->show_in_admin_all_list )
		$args->show_in_admin_all_list = !$args->internal;

	if ( null === $args->show_in_admin_status_list )
		$args->show_in_admin_status_list = !$args->internal;

	if ( false === $args->label )
		$args->label = $comment_status;

	if ( false === $args->label_count )
		$args->label_count = array( $args->label, $args->label );

	$wp_comment_statuses[$comment_status] = $args;

	return $args;
}

/**
 * Retrieve a comment status object by name
 *
 * @package WordPress
 * @subpackage Post
 * @since 4.0.0
 * @uses $wp_comment_statuses
 * @see register_comment_status
 * @see get_comment_statuses
 *
 * @param string $comment_status The name of a registered comment status
 * @return object A comment status object
 */
function get_comment_status_object( $comment_status ) {
	global $wp_comment_statuses;

	if ( empty($wp_comment_statuses[$comment_status]) )
		return null;

	return $wp_comment_statuses[$comment_status];
}

/**
 * Get a list of all registered comment status objects.
 *
 * @package WordPress
 * @subpackage Post
 * @since 4.0.0
 * @uses $wp_comment_statuses
 * @see register_comment_status
 * @see get_comment_status_object
 *
 * @param array|string $args An array of key => value arguments to match against the comment status objects.
 * @param string $output The type of output to return, either comment status 'names' or 'objects'. 'names' is the default.
 * @param string $operator The logical operation to perform. 'or' means only one element
 *  from the array needs to match; 'and' means all elements must match. The default is 'and'.
 * @return array A list of comment status names or objects
 */
function get_comment_stati( $args = array(), $output = 'names', $operator = 'and' ) {
	global $wp_comment_statuses;

	$field = ('names' == $output) ? 'name' : false;

	return wp_filter_object_list($wp_comment_statuses, $args, $operator, $field);
}

/**
 * Whether the comment type is hierarchical.
 *
 * A false return value might also mean that the comment type does not exist.
 *
 * @since 4.0.0
 * @see get_comment_type_object
 *
 * @param string $comment_type Comment type name
 * @return bool Whether comment type is hierarchical.
 */
function is_comment_type_hierarchical( $comment_type ) {
	if ( ! comment_type_exists( $comment_type ) )
		return false;

	$comment_type = get_comment_type_object( $comment_type );
	return $comment_type->hierarchical;
}

/**
 * Checks if a comment type is registered.
 *
 * @since 4.0.0
 * @uses get_comment_type_object()
 *
 * @param string $comment_type Comment type name
 * @return bool Whether comment type is registered.
 */
function comment_type_exists( $comment_type ) {
	return (bool) get_comment_type_object( $comment_type );
}

/**
 * Retrieve the comment type of the current comment or of a given comment.
 *
 * @since 4.0.0
 *
 * @param int|object $comment Optional. Comment ID or comment object. Default is the current comment from the loop.
 * @return string|bool Comment type on success, false on failure.
 */
function get_comment_type( $comment = null ) {
	if ( $comment = get_comment( $comment ) )
		return $comment->comment_type;

	return false;
}

/**
 * Retrieve a comment type object by name
 *
 * @package WordPress
 * @subpackage Post
 * @since 4.0.0
 * @uses $wp_comment_types
 * @see register_comment_type
 * @see get_comment_types
 *
 * @param string $comment_type The name of a registered comment type
 * @return object A comment type object
 */
function get_comment_type_object( $comment_type ) {
	global $wp_comment_types;

	if ( empty($wp_comment_types[$comment_type]) )
		return null;

	return $wp_comment_types[$comment_type];
}

/**
 * Get a list of all registered comment type objects.
 *
 * @package WordPress
 * @subpackage Post
 * @since 4.0.0
 * @uses $wp_comment_types
 * @see register_comment_type
 *
 * @param array|string $args An array of key => value arguments to match against the comment type objects.
 * @param string $output The type of output to return, either comment type 'names' or 'objects'. 'names' is the default.
 * @param string $operator The logical operation to perform. 'or' means only one element
 *  from the array needs to match; 'and' means all elements must match. The default is 'and'.
 * @return array A list of comment type names or objects
 */
function get_comment_types( $args = array(), $output = 'names', $operator = 'and' ) {
	global $wp_comment_types;

	$field = ('names' == $output) ? 'name' : false;

	return wp_filter_object_list($wp_comment_types, $args, $operator, $field);
}

/**
 * Register a comment type. Do not use before init.
 *
 * A function for creating or modifying a comment type based on the
 * parameters given. The function will accept an array (second optional
 * parameter), along with a string for the comment type name.
 *
 * Optional $args contents:
 *
 * - label - Name of the comment type shown in the menu. Usually plural. If not set, labels['name'] will be used.
 * - labels - An array of labels for this comment type.
 *     * If not set, comment labels are inherited for non-hierarchical types and page labels for hierarchical ones.
 *     * You can see accepted values in {@link get_comment_type_labels()}.
 * - description - A short descriptive summary of what the comment type is. Defaults to blank.
 * - public - Whether a comment type is intended for use publicly either via the admin interface or by front-end users.
 *     * Defaults to false.
 *     * While the default settings of exclude_from_search, publicly_queryable, show_ui, and show_in_nav_menus are
 *       inherited from public, each does not rely on this relationship and controls a very specific intention.
 * - hierarchical - Whether the comment type is hierarchical (e.g. page). Defaults to false.
 * - publicly_queryable - Whether queries can be performed on the front end for the comment type as part of parse_request().
 *     * ?comment_type={comment_type_key}
 *     * ?{comment_type_key}={single_comment_slug}
 *     * ?{comment_type_query_var}={single_comment_slug}
 *     * If not set, the default is inherited from public.
 * - show_ui - Whether to generate a default UI for managing this comment type in the admin.
 *     * If not set, the default is inherited from public.
 * - show_in_menu - Where to show the comment type in the admin menu.
 *     * If true, the comment type is shown in its own top level menu.
 *     * If false, no menu is shown
 *     * If a string of an existing top level menu (eg. 'tools.php' or 'edit.php?comment_type=page'), the comment type will
 *       be placed as a sub menu of that.
 *     * show_ui must be true.
 *     * If not set, the default is inherited from show_ui
 * - menu_position - The position in the menu order the comment type should appear.
 *     * show_in_menu must be true
 *     * Defaults to null, which places it at the bottom of its area.
 * - menu_icon - The url to the icon to be used for this menu. Defaults to use the comments icon.
 * - capability_type - The string to use to build the read, edit, and delete capabilities. Defaults to 'comment'.
 *     * May be passed as an array to allow for alternative plurals when using this argument as a base to construct the
 *       capabilities, e.g. array('story', 'stories').
 * - capabilities - Array of capabilities for this comment type.
 *     * By default the capability_type is used as a base to construct capabilities.
 *     * You can see accepted values in {@link get_comment_type_capabilities()}.
 * - map_meta_cap - Whether to use the internal default meta capability handling. Defaults to false.
 * - register_meta_box_cb - Provide a callback function that sets up the meta boxes
 *     for the edit form. Do remove_meta_box() and add_meta_box() calls in the callback.
 * - post_types - An array of post type identifiers that will be registered for the comment type.
 *     * Default is no post types.
 *     * Post Types can be registered later with register_post_type() or register_post_type_for_comment_type().
 * - can_export - Allows this comment type to be exported. Defaults to true.
 * - delete_with_user - Whether to delete comments of this type when deleting a user.
 *     * If true, comments of this type belonging to the user will be moved to trash when then user is deleted.
 *     * If false, comments of this type belonging to the user will *not* be trashed or deleted.
 *     * If not set (the default), comments are trashed if comment_type_supports('author'). Otherwise comments are not trashed or deleted.
 * - _builtin - true if this comment type is a native or "built-in" comment_type. THIS IS FOR INTERNAL USE ONLY!
 * - _edit_link - URL segement to use for edit link of this comment type. THIS IS FOR INTERNAL USE ONLY!
 *
 * @since 4.0.0
 * @uses $wp_comment_types Inserts new comment type object into the list
 * @uses $wp_rewrite Gets default feeds
 * @uses $wp Adds query vars
 *
 * @param string $comment_type Comment type key, must not exceed 20 characters.
 * @param array|string $args See optional args description above.
 * @return object|WP_Error the registered comment type object, or an error object.
 */
function register_comment_type( $comment_type, $args = array() ) {
	global $wp_comment_types, $wp_rewrite, $wp;

	if ( ! is_array( $wp_comment_types ) )
		$wp_comment_types = array();

	// Args prefixed with an underscore are reserved for internal use.
	$defaults = array(
		'labels'               => array(),
		'description'          => '',
		'public'               => false,
		'hierarchical'         => false,
		'publicly_queryable'   => null,
		'show_ui'              => null,
		'show_in_menu'         => null,
		'menu_position'        => null,
		'menu_icon'            => null,
		'capability_type'      => 'comment',
		'capabilities'         => array(),
		'map_meta_cap'         => null,
		'comment_types'           => array(),
		'can_export'           => true,
		'delete_with_user'     => null,
		'_builtin'             => false,
		'_edit_link'           => 'comment.php?action=editcomment&c=%d',
	);
	$args = wp_parse_args( $args, $defaults );
	$args = (object) $args;

	$comment_type = sanitize_key( $comment_type );
	$args->name = $comment_type;

	if ( strlen( $comment_type ) > 20 )
		return new WP_Error( 'comment_type_too_long', __( 'Comment types cannot exceed 20 characters in length' ) );

	// If not set, default to the setting for public.
	if ( null === $args->publicly_queryable )
		$args->publicly_queryable = $args->public;

	// If not set, default to the setting for public.
	if ( null === $args->show_ui )
		$args->show_ui = $args->public;

	// If not set, default to the setting for show_ui.
	if ( null === $args->show_in_menu || ! $args->show_ui )
		$args->show_in_menu = $args->show_ui;

	// Back compat with quirky handling in version 3.0. #14122
	if ( empty( $args->capabilities ) && null === $args->map_meta_cap && in_array( $args->capability_type, array( 'comment', 'page' ) ) )
		$args->map_meta_cap = true;

	// If not set, default to false.
	if ( null === $args->map_meta_cap )
		$args->map_meta_cap = false;

	$args->cap = get_comment_type_capabilities( $args );
	unset( $args->capabilities );

	if ( is_array( $args->capability_type ) )
		$args->capability_type = $args->capability_type[0];

	if ( $args->register_meta_box_cb )
		add_action( 'add_meta_boxes_' . $comment_type, $args->register_meta_box_cb, 10, 1 );

	$args->labels = get_comment_type_labels( $args );
	$args->label = $args->labels->name;

	$wp_comment_types[ $comment_type ] = $args;

	add_action( 'future_' . $comment_type, '_future_comment_hook', 5, 2 );

	foreach ( $args->post_types as $post_types ) {
		register_post_type_for_comment_type( $post_types, $comment_type );
	}

	do_action( 'registered_comment_type', $comment_type, $args );

	return $args;
}

/**
 * Add an already registered post type to a comment type.
 *
 * @package WordPress
 * @subpackage Taxonomy
 * @since 3.0.0
 * @uses $wp_post_types Modifies post type object
 *
 * @param string $taxonomy Name of post type object
 * @param string $comment_type Name of the comment type
 * @return bool True if successful, false if not
 */
function register_post_type_for_comment_type( $post_type, $comment_type) {
	global $wp_post_types;

	if ( !isset($wp_post_types[$post_type]) )
		return false;

	if ( ! get_comment_type_object($comment_type) )
		return false;

	// @todo Remove when it's added to post type objects
	if ( !isset( $wp_post_types[$post_type]->comment_type ) ) {
		$wp_post_types[$post_type]->comment_type = array();
	}

	if ( ! in_array( $comment_type, $wp_post_types[$post_type]->comment_type ) )
		$wp_post_types[$post_type]->comment_type[] = $comment_type;

	return true;
}

/**
 * Builds an object with all comment type capabilities out of a comment type object
 *
 * Comment type capabilities use the 'capability_type' argument as a base, if the
 * capability is not set in the 'capabilities' argument array or if the
 * 'capabilities' argument is not supplied.
 *
 * The capability_type argument can optionally be registered as an array, with
 * the first value being singular and the second plural, e.g. array('story, 'stories')
 * Otherwise, an 's' will be added to the value for the plural form. After
 * registration, capability_type will always be a string of the singular value.
 *
 * By default, seven keys are accepted as part of the capabilities array:
 *
 * - edit_comment, read_comment, and delete_comment are meta capabilities, which are then
 *   generally mapped to corresponding primitive capabilities depending on the
 *   context, which would be the comment being edited/read/deleted and the user or
 *   role being checked. Thus these capabilities would generally not be granted
 *   directly to users or roles.
 *
 * - edit_comments - Controls whether objects of this comment type can be edited.
 * - edit_others_comments - Controls whether objects of this type owned by other users
 *   can be edited. If the comment type does not support an author, then this will
 *   behave like edit_comments.
 * - publish_comments - Controls publishing objects of this comment type.
 * - read_private_comments - Controls whether private objects can be read.
 *
 * These four primitive capabilities are checked in core in various locations.
 * There are also seven other primitive capabilities which are not referenced
 * directly in core, except in map_meta_cap(), which takes the three aforementioned
 * meta capabilities and translates them into one or more primitive capabilities
 * that must then be checked against the user or role, depending on the context.
 *
 * - read - Controls whether objects of this comment type can be read.
 * - delete_comments - Controls whether objects of this comment type can be deleted.
 * - delete_private_comments - Controls whether private objects can be deleted.
 * - delete_published_comments - Controls whether published objects can be deleted.
 * - delete_others_comments - Controls whether objects owned by other users can be
 *   can be deleted. If the comment type does not support an author, then this will
 *   behave like delete_comments.
 * - edit_private_comments - Controls whether private objects can be edited.
 * - edit_published_comments - Controls whether published objects can be edited.
 *
 * These additional capabilities are only used in map_meta_cap(). Thus, they are
 * only assigned by default if the comment type is registered with the 'map_meta_cap'
 * argument set to true (default is false).
 *
 * @see map_meta_cap()
 * @since 4.0.0
 *
 * @param object $args Comment type registration arguments
 * @return object object with all the capabilities as member variables
 */
function get_comment_type_capabilities( $args ) {
	if ( ! is_array( $args->capability_type ) )
		$args->capability_type = array( $args->capability_type, $args->capability_type . 's' );

	// Singular base for meta capabilities, plural base for primitive capabilities.
	list( $singular_base, $plural_base ) = $args->capability_type;

	$default_capabilities = array(
		// Meta capabilities
		'edit_comment'          => 'edit_'         . $singular_base,
		'read_comment'          => 'read_'         . $singular_base,
		'delete_comment'        => 'delete_'       . $singular_base,
		// Primitive capabilities used outside of map_meta_cap():
		'edit_comments'         => 'edit_'         . $plural_base,
		'edit_others_comments'  => 'edit_others_'  . $plural_base,
		'publish_comments'      => 'publish_'      . $plural_base,
		'read_private_comments' => 'read_private_' . $plural_base,
	);

	// Primitive capabilities used within map_meta_cap():
	if ( $args->map_meta_cap ) {
		$default_capabilities_for_mapping = array(
			'read'                      => 'read',
			'delete_comments'           => 'delete_'           . $plural_base,
			'delete_private_comments'   => 'delete_private_'   . $plural_base,
			'delete_published_comments' => 'delete_published_' . $plural_base,
			'delete_others_comments'    => 'delete_others_'    . $plural_base,
			'edit_private_comments'     => 'edit_private_'     . $plural_base,
			'edit_published_comments'   => 'edit_published_'   . $plural_base,
		);
		$default_capabilities = array_merge( $default_capabilities, $default_capabilities_for_mapping );
	}

	$capabilities = array_merge( $default_capabilities, $args->capabilities );

	// Comment creation capability simply maps to edit_comments by default:
	if ( ! isset( $capabilities['create_comments'] ) )
		$capabilities['create_comments'] = $capabilities['edit_comments'];

	// Remember meta capabilities for future reference.
	if ( $args->map_meta_cap )
		_comment_type_meta_capabilities( $capabilities );

	return (object) $capabilities;
}

/**
 * Stores or returns a list of comment type meta caps for map_meta_cap().
 *
 * @since 4.0.0
 * @access private
 */
function _comment_type_meta_capabilities( $capabilities = null ) {
	static $meta_caps = array();
	if ( null === $capabilities )
		return $meta_caps;
	foreach ( $capabilities as $core => $custom ) {
		if ( in_array( $core, array( 'read_comment', 'delete_comment', 'edit_comment' ) ) )
			$meta_caps[ $custom ] = $core;
	}
}

/**
 * Builds an object with all comment type labels out of a comment type object
 *
 * Accepted keys of the label array in the comment type object:
 * - name - general name for the comment type, usually plural. The same and overridden by $comment_type_object->label. Default is Posts/Pages
 * - singular_name - name for one object of this comment type. Default is Post/Page
 * - add_new - Default is Add New for both hierarchical and non-hierarchical types. When internationalizing this string, please use a {@link http://codex.wordpress.org/I18n_for_WordPress_Developers#Disambiguation_by_context gettext context} matching your comment type. Example: <code>_x('Add New', 'product');</code>
 * - add_new_item - Default is Add New Post/Add New Page
 * - edit_item - Default is Edit Post/Edit Page
 * - new_item - Default is New Post/New Page
 * - view_item - Default is View Post/View Page
 * - search_items - Default is Search Posts/Search Pages
 * - not_found - Default is No comments found/No pages found
 * - not_found_in_trash - Default is No comments found in Trash/No pages found in Trash
 * - parent_item_colon - This string isn't used on non-hierarchical types. In hierarchical ones the default is Parent Page:
 * - all_items - String for the submenu. Default is All Posts/All Pages
 * - menu_name - Default is the same as <code>name</code>
 *
 * Above, the first default value is for non-hierarchical comment types (like comments) and the second one is for hierarchical comment types (like pages).
 *
 * @since 4.0.0
 * @access private
 *
 * @param object $comment_type_object
 * @return object object with all the labels as member variables
 */
function get_comment_type_labels( $comment_type_object ) {
	$nohier_vs_hier_defaults = array(
		'name' => array( _x('Comments', 'comment type general name'), _x('Pingbacks', 'comment type general name') ),
		'singular_name' => array( _x('Comment', 'comment type singular name'), _x('Pingback', 'comment type singular name') ),
		'add_new' => array( _x('Add New', 'comment'), _x('Add New', 'page') ),
		'add_new_item' => array( __('Add New Comment'), __('Add New Pingback') ),
		'edit_item' => array( __('Edit Comment'), __('Edit Pingback') ),
		'new_item' => array( __('New Comment'), __('New Pingback') ),
		'view_item' => array( __('View Comment'), __('View Pingback') ),
		'search_items' => array( __('Search Comments'), __('Search Pingbacks') ),
		'not_found' => array( __('No comments found.'), __('No pingbacks found.') ),
		'not_found_in_trash' => array( __('No comments found in Trash.'), __('No pingbacks found in Trash.') ),
		'parent_item_colon' => array( __('Parent Comment:'), null ),
		'all_items' => array( __( 'All Comments' ), __( 'All Pingbacks' ) )
	);
	$nohier_vs_hier_defaults['menu_name'] = $nohier_vs_hier_defaults['name'];

	$labels = _get_custom_object_labels( $comment_type_object, $nohier_vs_hier_defaults );

	$comment_type = $comment_type_object->name;
	return apply_filters( "comment_type_labels_{$comment_type}", $labels );
}

/**
 * Builds an object with custom-something object (comment type, taxonomy) labels out of a custom-something object
 *
 * @access private
 * @since 4.0.0
 */
function _get_custom_object_labels( $object, $nohier_vs_hier_defaults ) {
	$object->labels = (array) $object->labels;

	if ( isset( $object->label ) && empty( $object->labels['name'] ) )
		$object->labels['name'] = $object->label;

	if ( !isset( $object->labels['singular_name'] ) && isset( $object->labels['name'] ) )
		$object->labels['singular_name'] = $object->labels['name'];

	if ( ! isset( $object->labels['name_admin_bar'] ) )
		$object->labels['name_admin_bar'] = isset( $object->labels['singular_name'] ) ? $object->labels['singular_name'] : $object->name;

	if ( !isset( $object->labels['menu_name'] ) && isset( $object->labels['name'] ) )
		$object->labels['menu_name'] = $object->labels['name'];

	if ( !isset( $object->labels['all_items'] ) && isset( $object->labels['menu_name'] ) )
		$object->labels['all_items'] = $object->labels['menu_name'];

	$defaults = array();

	foreach ( $nohier_vs_hier_defaults as $key => $value )
			$defaults[$key] = $object->hierarchical ? $value[1] : $value[0];

	$labels = array_merge( $defaults, $object->labels );
	return (object)$labels;
}

/**
 * Adds submenus for comment types.
 *
 * @access private
 * @since 4.0.0
 */
function _add_comment_type_submenus() {
	foreach ( get_comment_types( array( 'show_ui' => true ) ) as $comment_type ) {
		$comment_type_obj = get_comment_type_object( $comment_type );

		// Submenus only.
		if ( ! $comment_type_obj->show_in_menu || $comment_type_obj->show_in_menu === true )
			continue;

		add_submenu_page( $comment_type_obj->show_in_menu, $comment_type_obj->labels->name, $comment_type_obj->labels->all_items, $comment_type_obj->cap->edit_comments, "edit-comments.php?comment_type=$comment_type" );
	}
}
add_action( 'admin_menu', '_add_comment_type_submenus' );

/**
 * Updates the comment type for the comment ID.
 *
 * The comment cache will be cleaned for the comment ID.
 *
 * @since 4.0.0
 *
 * @uses $wpdb
 *
 * @param int $comment_id Comment ID to change comment type. Not actually optional.
 * @param string $comment_type Optional, default is comment. Supported values are 'comment' or 'pingback' to
 *  name a few.
 * @return int Amount of rows changed. Should be 1 for success and 0 for failure.
 */
function set_comment_type( $comment_id = 0, $comment_type = 'comment' ) {
	global $wpdb;

	$comment_type = sanitize_comment_field('comment_type', $comment_type, $comment_id, 'db');
	$return = $wpdb->update( $wpdb->comments, array('comment_type' => $comment_type), array('comment_ID' => $comment_id) );

	clean_comment_cache( $comment_id );

	return $return;
}

/**
 * Delete everything from comment meta matching meta key.
 *
 * @since 4.0.0
 *
 * @param string $comment_meta_key Key to search for when deleting.
 * @return bool Whether the comment meta key was deleted from the database
 */
function delete_comment_meta_by_key($comment_meta_key) {
	return delete_metadata( 'comment', null, $comment_meta_key, '', true );
}

/**
 * Retrieve comment meta fields, based on comment ID.
 *
 * The comment meta fields are retrieved from the cache where possible,
 * so the function is optimized to be called more than once.
 *
 * @since 4.0.0
 * @link http://codex.wordpress.org/Function_Reference/get_comment_custom
 *
 * @param int $comment_ID Comment ID.
 * @return array
 */
function get_comment_custom( $comment_ID = 0 ) {
	$comment_ID = absint( $comment_ID );
	if ( ! $comment_ID )
		$comment_ID = get_the_ID();

	return get_comment_meta( $comment_ID );
}

/**
 * Retrieve meta field names for a comment.
 *
 * If there are no meta fields, then nothing (null) will be returned.
 *
 * @since 4.0.0
 * @link http://codex.wordpress.org/Function_Reference/get_comment_custom_keys
 *
 * @param int $comment_ID comment ID
 * @return array|null Either array of the keys, or null if keys could not be retrieved.
 */
function get_comment_custom_keys( $comment_ID = 0 ) {
	$custom = get_comment_custom( $comment_ID );

	if ( !is_array($custom) )
		return null;

	if ( $keys = array_keys($custom) )
		return $keys;
}

/**
 * Retrieve values for a custom comment field.
 *
 * The parameters must not be considered optional. All of the comment meta fields
 * will be retrieved and only the meta field key values returned.
 *
 * @since 4.0.0
 * @link http://codex.wordpress.org/Function_Reference/get_comment_custom_values
 *
 * @param string $key Meta field key.
 * @param int $comment_ID Comment ID
 * @return array Meta field values.
 */
function get_comment_custom_values( $key = '', $comment_ID = 0 ) {
	if ( !$key )
		return null;

	$custom = get_comment_custom($comment_ID);

	return isset($custom[$key]) ? $custom[$key] : null;
}

/**
 * Sanitize every comment field.
 *
 * If the context is 'raw', then the comment object or array will get minimal santization of the int fields.
 *
 * @since 4.0.0
 * @uses sanitize_comment_field() Used to sanitize the fields.
 *
 * @param object|WP_Comment|array $comment The Comment Object or Array
 * @param string $context Optional, default is 'display'. How to sanitize comment fields.
 * @return object|WP_Comment|array The now sanitized Comment Object or Array (will be the same type as $comment)
 */
function sanitize_comment($comment, $context = 'display') {
	if ( is_object($comment) ) {
		// Check if comment already filtered for this context
		if ( isset($comment->filter) && $context == $comment->filter )
			return $comment;
		if ( !isset($comment->comment_ID) )
			$comment->comment_ID = 0;
		foreach ( array_keys(get_object_vars($comment)) as $field )
			$comment->$field = sanitize_comment_field($field, $comment->$field, $comment->comment_ID, $context);
		$comment->filter = $context;
	} else {
		// Check if comment already filtered for this context
		if ( isset($comment['filter']) && $context == $comment['filter'] )
			return $comment;
		if ( !isset($comment['ID']) )
			$comment['ID'] = 0;
		foreach ( array_keys($comment) as $field )
			$comment[$field] = sanitize_comment_field($field, $comment[$field], $comment['ID'], $context);
		$comment['filter'] = $context;
	}
	return $comment;
}

/**
 * Sanitize comment field based on context.
 *
 * Possible context values are:  'raw', 'edit', 'db', 'display', 'attribute' and 'js'. The
 * 'display' context is used by default. 'attribute' and 'js' contexts are treated like 'display'
 * when calling filters.
 *
 * @since 4.0.0
 * @uses apply_filters() Calls 'edit_$field' and '{$field_no_prefix}_edit_pre' passing $value and
 *  $comment_ID if $context == 'edit' and field name prefix == 'comment_'.
 *
 * @uses apply_filters() Calls 'edit_comment_$field' passing $value and $comment_ID if $context == 'db'.
 * @uses apply_filters() Calls 'pre_$field' passing $value if $context == 'db' and field name prefix == 'comment_'.
 * @uses apply_filters() Calls '{$field}_pre' passing $value if $context == 'db' and field name prefix != 'comment_'.
 *
 * @uses apply_filters() Calls '$field' passing $value, $comment_ID and $context if $context == anything
 *  other than 'raw', 'edit' and 'db' and field name prefix == 'comment_'.
 * @uses apply_filters() Calls 'comment_$field' passing $value if $context == anything other than 'raw',
 *  'edit' and 'db' and field name prefix != 'comment_'.
 *
 * @param string $field The Comment Object field name.
 * @param mixed $value The Comment Object value.
 * @param int $comment_ID Comment ID.
 * @param string $context How to sanitize comment fields. Looks for 'raw', 'edit', 'db', 'display',
 *               'attribute' and 'js'.
 * @return mixed Sanitized value.
 */
function sanitize_comment_field($field, $value, $comment_ID, $context) {
	$int_fields = array('comment_ID', 'comment_post_ID', 'comment_karma', 'comment_parent', 'user_id');
	if ( in_array($field, $int_fields) )
		$value = (int) $value;

	if ( 'raw' == $context )
		return $value;

	$prefixed = false;
	if ( false !== strpos($field, 'comment_') ) {
		$prefixed = true;
		$field_no_prefix = str_replace('comment_', '', $field);
	}

	if ( 'edit' == $context ) {
		$format_to_edit = array('comment_content');

		if ( $prefixed ) {
			$value = apply_filters("edit_{$field}", $value, $comment_ID);
			// Old school
			$value = apply_filters("{$field_no_prefix}_edit_pre", $value, $comment_ID);
		} else {
			$value = apply_filters("edit_comment_{$field}", $value, $comment_ID);
		}

		if ( in_array($field, $format_to_edit) ) {
			if ( 'comment_content' == $field )
				$value = format_to_edit($value, user_can_richedit());
			else
				$value = format_to_edit($value);
		} else {
			$value = esc_attr($value);
		}
	} else if ( 'db' == $context ) {
		if ( $prefixed ) {
			$value = apply_filters("pre_{$field}", $value);
			$value = apply_filters("{$field_no_prefix}_save_pre", $value);
		} else {
			$value = apply_filters("pre_comment_{$field}", $value);
			$value = apply_filters("{$field}_pre", $value);
		}
	} else {
		// Use display filters by default.
		if ( $prefixed )
			$value = apply_filters($field, $value, $comment_ID, $context);
		else
			$value = apply_filters("comment_{$field}", $value, $comment_ID, $context);
	}

	if ( 'attribute' == $context )
		$value = esc_attr($value);
	else if ( 'js' == $context )
		$value = esc_js($value);

	return $value;
}

/**
 * Retrieve the private comment SQL based on capability.
 *
 * This function provides a standardized way to appropriately select on the
 * comment_status of a comment type. The function will return a piece of SQL code
 * that can be added to a WHERE clause; this SQL is constructed to allow all
 * published comments, and all private comments to which the user has access.
 *
 * @since 4.0.0
 *
 * @param string $comment_type currently only supports 'comment' or 'page'.
 * @return string SQL code that can be added to a where clause.
 */
function get_private_comments_cap_sql( $comment_type ) {
	return get_comments_by_author_sql( $comment_type, false );
}

/**
 * Retrieve the comment SQL based on capability, author, and type.
 *
 * @see get_private_comments_cap_sql() for full description.
 *
 * @since 4.0.0
 * @param string $comment_type Comment type.
 * @param bool $full Optional. Returns a full WHERE statement instead of just an 'andalso' term.
 * @param int $comment_author Optional. Query comments having a single author ID.
 * @param bool $public_only Optional. Only return public comments. Skips cap checks for $current_user.  Default is false.
 * @return string SQL WHERE code that can be added to a query.
 */
function get_comments_by_author_sql( $comment_type, $full = true, $comment_author = null, $public_only = false ) {
	global $wpdb;

	// Private comments
	$comment_type_obj = get_comment_type_object( $comment_type );
	if ( ! $comment_type_obj )
		return $full ? 'WHERE 1 = 0' : ' 1 = 0 ';

	// This hook is deprecated. Why you'd want to use it, I dunno.
	if ( ! $cap = apply_filters( 'pub_priv_sql_capability', '' ) )
		$cap = $comment_type_obj->cap->read_private_comments;

	if ( $full ) {
		if ( null === $comment_author ) {
			$sql = $wpdb->prepare( 'WHERE comment_type = %s AND ', $comment_type );
		} else {
			$sql = $wpdb->prepare( 'WHERE user_id = %d AND comment_type = %s AND ', $comment_author, $comment_type );
		}
	} else {
		$sql = '';
	}

	$sql .= "(comment_approved = '1'";

	// Only need to check the cap if $public_only is false
	if ( false === $public_only ) {
		if ( current_user_can( $cap ) ) {
			// Does the user have the capability to view private comments? Guess so.
			$sql .= " OR comment_approved = 'private'";
		} elseif ( is_user_logged_in() ) {
			// Users can view their own private comments.
			$id = get_current_user_id();
			if ( null === $comment_author || ! $full ) {
				$sql .= " OR comment_approved = 'private' AND user_id = $id";
			} elseif ( $id == (int) $comment_author ) {
				$sql .= " OR comment_approved = 'private'";
			} // else none
		} // else none
	}

	$sql .= ')';

	return $sql;
}

/**
 * Call major cache updating functions for list of Comment objects.
 *
 * @package WordPress
 * @subpackage Cache
 * @since 4.0.0
 *
 * @uses update_comment_cache()
 * @uses update_object_term_cache()
 * @uses update_commentmeta_cache()
 *
 * @param array $comments Array of Comment objects
 * @param bool $update_meta_cache Whether to update the meta cache. Default is true.
 */
function update_comment_caches(&$comments, $update_meta_cache = true) {
	// No point in doing all this work if we didn't match any comments.
	if ( !$comments )
		return;

	update_comment_cache($comments);

	$comment_IDs = array();
	foreach ( $comments as $comment )
		$comment_IDs[] = $comment->comment_ID;

	if ( $update_meta_cache )
		update_commentmeta_cache($comment_IDs);
}

/**
 * Updates metadata cache for list of comment IDs.
 *
 * Performs SQL query to retrieve the metadata for the comment IDs and updates the
 * metadata cache for the comments. Therefore, the functions, which call this
 * function, do not need to perform SQL queries on their own.
 *
 * @package WordPress
 * @subpackage Cache
 * @since 4.0.0
 *
 * @param array $comment_IDs List of comment IDs.
 * @return bool|array Returns false if there is nothing to update or an array of metadata.
 */
function update_commentmeta_cache($comment_IDs) {
	return update_meta_cache('comment', $comment_IDs);
}

/**
 * Adds any comments from the given ids to the cache that do not already exist in cache
 *
 * @since 4.0.0
 *
 * @access private
 *
 * @param array $comment_IDs ID list
 * @param bool $update_meta_cache Whether to update the meta cache. Default is true.
 */
function _prime_comment_caches( $ids, $update_meta_cache = true ) {
	global $wpdb;

	$non_cached_ids = _get_non_cached_ids( $ids, 'comments' );
	if ( !empty( $non_cached_ids ) ) {
		$fresh_comments = $wpdb->get_results( sprintf( "SELECT $wpdb->comments.* FROM $wpdb->comments WHERE ID IN (%s)", join( ",", $non_cached_ids ) ) );

		update_comment_caches( $fresh_comments, $update_meta_cache );
	}
}
