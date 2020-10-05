<?php
/**
 * Contains all the functions for the TinyMCE plugin.
 *
 * @package skaut-google-drive-gallery
 */

namespace Sgdg\Admin\TinyMCE;

if ( ! is_admin() ) {
	return;
}

/**
 * Registers all the hooks for the TinyMCE plugin and the "list_gallery_dir" AJAX endpoint
 */
function register() {
	add_action( 'media_buttons', '\\Sgdg\\Admin\\TinyMCE\\add' );
	add_action( 'wp_enqueue_media', '\\Sgdg\\Admin\\TinyMCE\\register_scripts_styles' );
	add_action( 'wp_ajax_list_gallery_dir', '\\Sgdg\\Admin\\TinyMCE\\handle_ajax' );
}

/**
 * Adds the Google Drive gallery button to TinyMCE and enables the use of ThickBox
 */
function add() {
	if ( ( ! current_user_can( 'edit_posts' ) && ! current_user_can( 'edit_pages' ) ) || 'true' !== get_user_option( 'rich_editing' ) ) {
		return;
	}
	echo( '<a href="#" id="sgdg-tinymce-button" class="button"><img class="sgdg-tinymce-button-icon" src="' . esc_attr( plugins_url( '/skaut-google-drive-gallery/admin/icon.png' ) ) . '">' . esc_html__( 'Google Drive gallery', 'skaut-google-drive-gallery' ) . '</a>' );
	add_thickbox();
}

/**
 * Enqueues the scripts and styles used by the Tiny MCE plugin.
 */
function register_scripts_styles() {
	if ( ( ! current_user_can( 'edit_posts' ) && ! current_user_can( 'edit_pages' ) ) || 'true' !== get_user_option( 'rich_editing' ) ) {
		return;
	}
	\Sgdg\enqueue_style( 'sgdg_tinymce', 'admin/css/tinymce.min.css' );
	\Sgdg\enqueue_script( 'sgdg_tinymce', 'admin/js/tinymce.min.js' );
	wp_localize_script(
		'sgdg_tinymce',
		'sgdgTinymceLocalize',
		array(
			'dialog_title'  => esc_html__( 'Google Drive gallery', 'skaut-google-drive-gallery' ),
			'root_name'     => esc_html__( 'Google Drive gallery', 'skaut-google-drive-gallery' ),
			'insert_button' => esc_html__( 'Insert', 'skaut-google-drive-gallery' ),
			'ajax_url'      => admin_url( 'admin-ajax.php' ),
			'nonce'         => wp_create_nonce( 'sgdg_editor_plugin' ),
		)
	);
}

/**
 * Handles errors for the "list_gallery_dir" AJAX endpoint.
 *
 * This function is a wrapper around `handle_ajax_body` that handles all the possible errors that can occur and sends them back as error messages.
 */
function handle_ajax() {
	try {
		ajax_handler_body();
	} catch ( \Sgdg\Vendor\Google_Service_Exception $e ) {
		if ( 'userRateLimitExceeded' === $e->getErrors()[0]['reason'] ) {
			wp_send_json( array( 'error' => esc_html__( 'The maximum number of requests has been exceeded. Please try again in a minute.', 'skaut-google-drive-gallery' ) ) );
		} else {
			wp_send_json( array( 'error' => $e->getErrors()[0]['message'] ) );
		}
	} catch ( \Exception $e ) {
		wp_send_json( array( 'error' => $e->getMessage() ) );
	}
}

/**
 * Actually handles the "list_gallery_dir" AJAX endpoint.
 *
 * Returns a list of all directories inside the last directory of a path.
 *
 * @throws \Sgdg\Exceptions\Cant_Edit_Exception Insufficient role.
 * @throws \Sgdg\Exceptions\No_Access_Token_Exception Plugin not authorized.
 */
function ajax_handler_body() {
	check_ajax_referer( 'sgdg_editor_plugin' );
	if ( ! current_user_can( 'edit_posts' ) && ! current_user_can( 'edit_pages' ) ) {
		throw new \Sgdg\Exceptions\Cant_Edit_Exception();
	}
	if ( false === get_option( 'sgdg_access_token', false ) ) {
		throw new \Sgdg\Exceptions\No_Access_Token_Exception();
	}

	$path      = isset( $_GET['path'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_GET['path'] ) ) : array();
	$root_path = \Sgdg\Options::$root_path->get();
	$root      = end( $root_path );

	wp_send_json( array( 'directories' => list_directories_in_path( $path, $root ) ) );
}

/**
 * Returns a list of all directories inside the last directory of a path
 *
 * @param array  $path A path represented as an array of directory names.
 * @param string $root The root directory relative to which the path is taken.
 *
 * @return array A list of directory names.
 */
function list_directories_in_path( array $path, $root ) {
	if ( 0 === count( $path ) ) {
		$extract_names = static function( $directory ) {
			return $directory['name'];
		};
		return array_map( $extract_names, \Sgdg\API_Client::list_directories( $root ) );
	}
	$next_dir_id = \Sgdg\API_Client::get_directory_id( $root, $path[0] );
	array_shift( $path );
	return list_directories_in_path( $path, $next_dir_id );
}
