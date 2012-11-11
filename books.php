<?php
/*
 Plugin Name: Books Post Type
 Plugin URI: 
 Description: This plugin will create a custom post-type, complete with author, and license media data.
 Author: Matt Rude
 Version: 0.1
 Author URI: http://mattrude.com
*/

// Add MIME Types epub & mobi
function addUploadMimes($mimes) {
    $mimes = array_merge($mimes, array(
        'epub|mobi' => 'application/octet-stream'
    ));
    return $mimes;
}
add_filter('upload_mimes', 'addUploadMimes');


// Add Custom Post Types for WordPress 2.9
add_action( 'init', 'create_books_post_type' );

function create_books_post_type() {
    register_post_type('books', array(
        'labels' => array(
            'name' => __('Books'),
            'singular_name' => _x('Book','mdr-book'),
            'add_new' => __('Add New Book', 'mdr-book'),
            'add_new_item' => __('Add New Book', 'mdr-book'),
            'edit_item' => __('Edit Book', 'mdr-book'),
            'new_item' => __('New Book', 'mdr-book'),
            'view_item' => __('View Book', 'mdr-book'),
            'search_items' => __('Search Books', 'mdr-book'),
            'not_found' =>  __('No books found', 'mdr-book'),
            'not_found_in_trash' => __('No books found in Trash', 'mdr-book')
        ),
        'description' => __('Imported Books Posts', 'mdr-book'),
        'exclude_from_search' => true,
        'public' => true,
        'has_archive' => true,
        'show_ui' => true,
        'hierarchical' => false,
        'rewrite' => array('slug' => 'books'),
        'supports' => array('title', 'editor', 'thumbnail'),
        'feed' => true,
        'register_meta_box_cb' => 'books_save_metabox'
    ));

    //create two taxonomies, genres and writers for the post type "book"
    // Add new taxonomy, make it hierarchical (like categories)
    $labels = array(
        'name' => _x( 'Genres', 'mdr-book' ),
        'singular_name' => _x( 'Genre', 'taxonomy singular name' ),
        'search_items' =>  __( 'Search Genres' ),
        'all_items' => __( 'All Genres' ),
        'parent_item' => __( 'Parent Genre' ),
        'parent_item_colon' => __( 'Parent Genre:' ),
        'edit_item' => __( 'Edit Genre' ), 
        'update_item' => __( 'Update Genre' ),
        'add_new_item' => __( 'Add New Genre' ),
        'new_item_name' => __( 'New Genre Name' ),
        'menu_name' => __( 'Genre' ),
    );    

    register_taxonomy('books-genre',array('books'), array(
        'hierarchical' => true,
        'labels' => $labels,
        'show_ui' => true,
        'query_var' => true,
        'rewrite' => array( 'slug' => 'books-genre' ),
    ));

    // Add new taxonomy, NOT hierarchical (like tags)
    $labels = array(
        'name' => _x( 'Author', 'mdr-book' ),
        'singular_name' => _x( 'Author', 'taxonomy singular name' ),
        'search_items' =>  __( 'Search Authors' ),
        'popular_items' => __( 'Popular Authors' ),
        'all_items' => __( 'All Authorss' ),
        'parent_item' => null,
        'parent_item_colon' => null,
        'edit_item' => __( 'Edit Author' ), 
        'update_item' => __( 'Update Author' ),
        'add_new_item' => __( 'Add New Author' ),
        'new_item_name' => __( 'New Author Name' ),
        'separate_items_with_commas' => __( 'Separate authors with commas' ),
        'add_or_remove_items' => __( 'Add or remove authors' ),
        'choose_from_most_used' => __( 'Choose from the most used authors' ),
        'menu_name' => __( 'Authors' ),
    ); 

    register_taxonomy('books-author','books',array(
        'hierarchical' => false,
        'labels' => $labels,
        'show_ui' => true,
        'update_count_callback' => '_update_post_term_count',
        'query_var' => true,
        'rewrite' => array( 'slug' => 'books-author' ),
    ));

    $labels = array(
        'name' => _x( 'Licenses', 'mdr-book' ),
        'singular_name' => _x( 'License', 'taxonomy singular name' ),
        'search_items' =>  __( 'Search Licenses' ),
        'all_items' => __( 'All Licenses' ),
        'parent_item' => __( 'Parent Licensee' ),
        'parent_item_colon' => __( 'Parent License:' ),
        'edit_item' => __( 'Edit License' ),
        'update_item' => __( 'Update License' ),
        'add_new_item' => __( 'Add New License' ),
        'new_item_name' => __( 'New License Name' ),
        'menu_name' => __( 'License' ),
    );

    register_taxonomy('books-license',array('books'), array(
        'hierarchical' => true,
        'labels' => $labels,
        'show_ui' => true,
        'query_var' => true,
        'rewrite' => array( 'slug' => 'books-licenses' ),
    ));

}
// Books Posts Meta Data
add_action('admin_menu', 'books_add_metabox');
add_action('save_post', 'books_save_metabox');
function books_add_metabox() {
    add_meta_box('books-metabox', __('Books Meta Data'), 'books_metabox', 'books', 'side');
}

function books_metabox() {
    echo '<input type="hidden" name="books_id_metabox" id="books_id_metabox" value="' . 
    wp_create_nonce( plugin_basename(__FILE__) ) . '" />';

    // The actual fields for data entry
    global $post;
    $values = get_post_custom( $post->ID );
    $gutenberg_id = isset( $values['gutenberg_id'] ) ? $values['gutenberg_id'] : '';
    $openlibrary_id = isset( $values['openlibrary_id'] ) ? $values['openlibrary_id'] : '';
    $isbd = isset( $values['isbd'] ) ? $values['isbd'] : '';

    echo '<label for="isbd">' . __("ISBN") . '</label>';
    echo '<input type="text" name="isbn" value="' . $isbd . '" size="15" /><br />';
    echo '<label for="openlibrary_id">' . __("Open Library ID") . '</label>';
    echo '<input type="text" name="openlibrary_id" value="' . $openlibrary_id . '" size="15" /><br />';
    echo '<label for="gutenberg_id">' . __("Gutenberg ID") . '</label>';
    echo '<input type="text" name="gutenberg_id" value="' . $gutenberg_id . '" size="15" />';
}

function books_save_metabox() {
    global $post;
    if(isset($post->ID)) {
        $post_id = $post->ID;
        if( isset( $_POST['gutenberg_id'] ) ) {
            update_post_meta($post_id, 'gutenberg_id', $_POST['gutenberg_id']); 
        }

        if( isset( $_POST['openlibrary_id'] ) ) {
            update_post_meta($post_id, 'openlibrary_id', $_POST['openlibrary_id']);
        }

        if( isset( $_POST['isbn'] ) ) {
            update_post_meta($post_id, 'isbn', $_POST['isbn']);
        }
    }
}

?>
