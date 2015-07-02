<?php
/**
 * Plugin Name: Zip Downloads
 * Description: A simple way of uploading files to your site, so users can download as a zip file 
 * Version: 1.0
 * Author: Michael Such
 */

// Register Custom Post Types
add_action( 'init', array( 'Zip_Downloads', 'regsiter_post_type' ) );

// Add submenu
add_action('admin_menu', array( 'Zip_Downloads', 'register_zip_downloads_settings') );

// Register settings
add_action( 'admin_init', array( 'Zip_Downloads', 'register_my_setting' ) );
add_action( 'init', array( 'Zip_Downloads', 'add_options' ) );

// Thumbnails
 
// Enable thumbnail support
add_action( 'after_setup_theme', array( 'Zip_Downloads', 'theme_supports' ) );

// Move thumbnail from sidebar to normal position
add_action( 'do_meta_boxes', array( 'Zip_Downloads', 'customposttype_image_box' ) );

// Update thumbnail link text
add_action( 'admin_head-post-new.php', array( 'Zip_Downloads', 'change_thumbnail_html' ) );
add_action( 'admin_head-post.php', array( 'Zip_Downloads', 'change_thumbnail_html' ) );

// Add Shortcode
add_shortcode( 'zip_downloads', array( 'Zip_Downloads', 'zip_downloads_shortcode' ) ); 

// Scripts & Styles
add_action( 'wp_enqueue_scripts', array( 'Zip_Downloads', 'styles' ) );
add_action( 'wp_enqueue_scripts', array( 'Zip_Downloads', 'scripts' ) );

// Ajax Actions
add_action( 'wp_ajax_zip_files', array( 'Zip_Downloads', 'build_zip' ) );
add_action( 'wp_ajax_nopriv_zip_files', array( 'Zip_Downloads', 'build_zip' ) );

// Schedule hourly event
register_activation_hook( __FILE__, array( 'Zip_Downloads', 'zip_downloads_activation' ) );
register_deactivation_hook( __FILE__, array( 'Zip_Downloads', 'zip_downloads_deactivation' ) );

add_action( 'zip_downloads_hourly_event', array( 'Zip_Downloads', 'delete_directory' ) );

class Zip_Downloads{

      /**
       * Enqueue the styles.
       */
       public static function styles() {

        	wp_enqueue_style( 'zd-style', plugins_url( '/css/zd-style.css' , __FILE__ ) );
       }

       /**
     	* Enqueue the scripts.
        */
	public static function scripts(){

		wp_enqueue_script( 'jquery',
		            plugins_url( '/js/jquery-ui-1.9.0.custom.min.js' , __FILE__ ),
		            array(),
		            '1.11.1',
		            false 
	        );

		wp_enqueue_script( 'zd-script',
			    plugins_url( '/js/zd-script.js' , __FILE__ ),
			    array( 'jquery' )
		);

		wp_localize_script( 'zd-script', 'localizedscript', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );

	}

	/**
         *  Add the post format support
         */
        public static function theme_supports() {
	        add_theme_support( 'post-thumbnails' );
	}

	/**
	 * Register Settings
	 */
	 public static function register_my_setting(){
	
	    	register_setting( 'zip_downloads_group', 'zip_downloads' );
	 }

       /**
        * Add Options for the settings page
        */
        public static function add_options(){

    		$settings = array(
    			'foundation' => 0
    		);

    		add_option( 'zip_downloads', $settings ); 
	}

	/**
	 * Register a media kit post type.
	 * @link http://codex.wordpress.org/Function_Reference/register_post_type
	 */
	public static function regsiter_post_type() {

		$labels = array(
			'name'               => _x( 'Zip Downloads', 'post type general name', 'zip-downloads-textdomain' ),
			'singular_name'      => _x( 'Zip Downloads', 'post type singular name', 'zip-downloads-textdomain' ),
			'menu_name'          => _x( 'Zip Downloads', 'admin menu', 'zip-downloads-textdomain' ),
			'name_admin_bar'     => _x( 'Zip Downloads', 'add new on admin bar', 'zip-downloads-textdomain' ),
			'add_new'            => _x( 'Add New File', 'zip downloads', 'zip-downloads-textdomain' ),
			'add_new_item'       => __( 'Add New File', 'zip-downloads-textdomain' ),
			'new_item'           => __( 'New File', 'zip-downloads-textdomain' ),
			'edit_item'          => __( 'Edit File', 'zip-downloads-textdomain' ),
			'view_item'          => __( 'View File', 'zip-downloads-textdomain' ),
			'all_items'          => __( 'All File', 'zip-downloads-textdomain' ),
			'search_items'       => __( 'Search File', 'zip-downloads-textdomain' ),
			'parent_item_colon'  => __( 'Parent File:', 'zip-downloads-textdomain' ),
			'not_found'          => __( 'No file found.', 'zip-downloads-textdomain' ),
			'not_found_in_trash' => __( 'No files found in Trash.', 'zip-downloads-textdomain' )
		);

		$args = array(
			'labels'             => $labels,
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'zip-downloads' ),
			'capability_type'    => 'post',
			'has_archive'        => true,
			'hierarchical'       => false,
			'menu_position'      => 10,
			'menu_icon' 		 => 'dashicons-slides',
			'supports'           => array( 'title', 'thumbnail' )
		);

		register_post_type( 'zip_downloads', $args );
	}

	/**
	 * Move featured image to the normal postion instead of sidebar
	 */
	public static function customposttype_image_box() {

		remove_meta_box( 'postimagediv', 'zip_downloads', 'side' );
		add_meta_box('postimagediv', __('Add File'), 'post_thumbnail_meta_box', 'zip_downloads', 'normal', 'high');

	}

	/**
	 * Update featured image link text
	 */
	public static function change_thumbnail_html( $content ) {

	        if ( 'zip_downloads' == $GLOBALS['post_type'] )
	         	add_filter( 'admin_post_thumbnail_html', array( 'Zip_Downloads', 'do_thumb' ) );
	}

	public static function do_thumb( $content ){
		 return str_replace(__('Upload a file'), __('Upload a file'), $content );
	}

	/**
	 * Render Zip Downloads shortcode
	 * @param array $atts array of shortcode attributes
	 * @return string 
	 */
	public static function zip_downloads_shortcode( $atts ) { ?>

		<?php $args = array(
						'post_type' => 'zip_downloads',
						'posts_per_page' => -1,
						'status' => 'publish',
						'orderby' => 'name',
						'order' => 'ASC'
					);

				      // the query
					$query = new WP_Query( $args );

		if( $query->found_posts > 0){

			$zip_downloads = get_option( 'zip_downloads' ); 
			$zd_style = $zip_downloads['style'] != '' ? $zip_downloads['style'] : 'foundation';

			switch ($zd_style) {
				case 'default':
					$zd_class = 'zd-xs-12 zd-md-6 span6';
					break;

				case 'foundation':
					$zd_class = 'columns small-12 medium-6';
					break;

				case 'bootstrap':     
					$zd_class = 'col-xs-12 col-md-6 span6';
				break;
				
				default:
					$zd_class = 'zd-xs-12 zd-md-6 zd-span6';
					break;
			}

			$output = '';
			$output .= '<article>';
				$output .= '<div class="row" data-equalizer>';
					$output .= '<div class="' . $zd_class . '">';
						$output .= '<div class="media-page-number">';
							$output .= '<p class="media-number-title">Select file/s to download</p>';
						$output .= '</div>';
						$output .= '<div class="media-page-box media-select" data-equalizer-watch>';

							$output .= '<p id="media-all-banners"><input type="checkbox" name="banners-all" value="all" checked /><label> All Files</label></p>';

					        // the query
						$query = new WP_Query( $args );

						if ( $query->have_posts() ) :

							$output .= '<form id="media-sizes" method="get" action="#">';

							while ( $query->have_posts() ) : $query->the_post();

								$id = get_the_ID();
								$title = get_the_title();
								$post_thumbnail_id = get_post_thumbnail_id( $id );
								$file_path = get_attached_file( $post_thumbnail_id ); 

								$output .= '<span><input type="checkbox" name="file-upload" value="' . $id . '" /><label for="size-' . $id . '">' . $title . '</label></span>';

							endwhile;
							wp_reset_postdata();

							$output .= '</form>';

						else :

							$output .= '<p>No files are currently available for download.</p>';

						endif;

						$output .= '</div>';
					$output .= '</div>';
					$output .= '<div class="' . $zd_class . '">';
						$output .= '<div class="media-page-number">';
							$output .= '<p class="media-number-title">Download file/s</p>';
						$output .= '</div>';
						$output .= '<div class="media-page-box media-download" data-equalizer-watch>';	
							$output .= '<a id="media-create-zip" class="media-download-btn" href="#">Create ZIP File</a>';
							$output .= '<p id="media-none-selected" class="hide">Please select at least one file to create the zip</p>';
							$output .= '<p id="media-progress-bar" class="hide"></p>';
							$output .= '<a id="media-download-zip" class="media-download-btn hide" href="#">Download ZIP File</a>';

						$output .= '</div>';
					$output .= '</div>';
					$output .= '<div class="clear:both;"></div>';
				$output .= '</div>';//END Row
			$output .= '</article>';
			$output .= '<div class="caret"></div>';

		} else {

			$output .= '<p>No files are currently available.</p>';

			if ( is_user_logged_in() ) {

				if ( is_admin() || is_super_admin() ) {

					$output .= '<p>Click <a href="/wp-admin/edit.php?post_type=zip_downloads">here</a> to add files.</p>';

				}
			}
		}

		return $output;
	}

	/**
	 * Builds a zip from a list of filepaths
	 *
	 * This takes the list of path generated by list_files. ZipArchive is used to create the new zip on the server
	 * @return  none
	 */
	public static function build_zip() {

		$file_uploads = isset($_POST['file_uploads']) ? $_POST['file_uploads'] : '';

		$all_file_paths = array();
		if($file_uploads){
			foreach ($file_uploads as $key => $id) {
				$post_thumbnail_id = get_post_thumbnail_id( $id );
				$file_path = get_attached_file( $post_thumbnail_id ); 

				if( file_exists( $file_path ) ){
					$all_file_paths[] = $file_path;
				}
			}
		}

        	ini_set('max_execution_time', 0);

        	$file_name = time() . '_all_banners.zip';
        	$dirname = ABSPATH . trailingslashit( get_option('upload_path') ) . 'zip_downloads';

        	if ( !is_dir( $dirname ) )
        		mkdir( $dirname, 0777, true );

        	$zip_path = $dirname . '/' . $file_name;//location of zip on server. set in construct

        	$files_to_zip = $all_file_paths;

        	if( count( $files_to_zip ) ){//check we have valid files

	            	$zip = new ZipArchive;
	            	$opened = $zip->open( $zip_path, ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE );

	            	if( $opened !== true ){
	
	                	die("cannot open file.zip for writing. Please try again in a moment.");
	
	            	}//endif
	
	            	foreach ( $files_to_zip as $file ) {
	
	                     	$short_name = basename( $file );
	                     	$zip->addFile( $file, $short_name );
	
	                }//end foreach

            		$zip->close();

        	}//endif

    		$download_link = trailingslashit( get_site_url() ) . trailingslashit( get_option('upload_path') ) . 'zip_downloads'. '/' . $file_name;
    		echo $download_link;

        	die();

	}//end build_zip

	/**
	 * Empty media_kit directory every hour
	 * @return bool false if no directory exists
	 */
	public static function delete_directory() {

	    	$dirname = ABSPATH . trailingslashit( get_option('upload_path') ) . 'zip_downloads';
	
	        if ( is_dir( $dirname ) )
	        	$dir_handle = opendir( $dirname ); 
	
	        if ( !$dir_handle )
			return false;
	
	        while( $file = readdir( $dir_handle ) ) {
	
	            	if ( $file != "." && $file != ".." ) {
	                	if ( !is_dir( $dirname . "/" . $file ) )             
	                		unlink( $dirname . "/" . $file );                     
	            	}    
	        } 
	
	        closedir( $dir_handle );
	        rmdir( $dirname );        
	        return true; 
    	}

	/**
	 * Add a submenu
	 */
	public static function register_zip_downloads_settings() {
		add_submenu_page( 'edit.php?post_type=zip_downloads', 'Settings', 'Settings', 'administrator', 'zip-downloads-settings', array( 'Zip_Downloads', 'zip_downloads_settings' ) ); 
	}

	/**
	 * Add a submenu options
	 * @return string
	 */
	public static function zip_downloads_settings() {
		
		?>
		<div class="wrap"><div id="icon-tools" class="icon32"></div>
			<h2>Zip Downloads Settings</h2>

			<form method="post" action="options.php">
				<?php settings_fields( 'zip_downloads_group' ); ?>
    			<?php do_settings_sections( 'zip_downloads_group' ); ?>

    			<h3>Shortcode</h3>

    			<p><em>Add the below shortcode to a page to display the files available for download</em></p>

    			<p>[zip_downloads]</p>

    			<h3>Styling</h3>

    				<table>
    					<tbody>
    						<tr>
    							<td><label for="style">Select Style template:</label></td>
    							<td>
    								<select name="zip_downloads[style]">

	    								<?php $styles = array( 'default', 'foundation', 'bootstrap' ); 
	    								$mk_style = $zip_downloads['style'] != '' ? $zip_downloads['style'] : 'foundation';

	    								foreach ( $styles as $style ) {
	    									
										$select = $zip_downloads['style'] == $style ? 'selected' : ''; ?>
										
										<option value="<?php echo $style; ?>" <?php echo $select; ?>><?php echo $style; ?></option>

	    								<?php } ?>

								</select>
    							</td>
    						</tr>
    					</tbody>
    				</table>

    			<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * On activation, set a time, frequency and name of an action hook to be scheduled.
	 */
	public static function zip_downloads_activation() {
		wp_schedule_event( time(), 'hourly', 'zip_downloads_hourly_event' );
	}

	public static function zip_downloads_deactivation() {
		wp_clear_scheduled_hook( 'zip_downloads_hourly_event' );
	}

}
