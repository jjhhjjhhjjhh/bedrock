<?php if ( ! defined( 'ABSPATH' ) ) exit; // Folding: CTRL+K , CTRL+3
/**
 * Class to manage theme functions
 *
 * @class ClientTheme
 */
final class ClientTheme {
    /******* Variables *******/
    private $version;
    private $priority;
    private $autoload_dir;

    /******* Constructor *******/
    public function __construct() {
      $this->version  = wp_get_theme()->get('Version');
      $this->priority = '999';
      $this->autoload_dir = realpath( dirname(__FILE__) );
    }





    /******* Autoloading *******/
    /**
  	 * Autoloads eveyrthing in the autoload folder.
     *
     * Some folders are loaded without distinction, meaning everything in the
     * folder is loaded (recursively or not) except for maybe hidden files.
     *
     * Some folders are loaded with disctinction, meaning there's some process
     * to identify correctly formatted files and loading only those files which
     * utilize that process
     *
     * As of 0.0.0, the following is loaded:
     *   1. Classes    - in autoload/classes/ (recursive, without distinction)
     *   2. Post Types - in autoload/post-types/ (non-recursive, without distinction)
  	 *
     * @return void
     */
    public function autoload(){ //Publically acessible method for functions.php
      $this->autoload_classes();
      $this->autoload_custom_post_types();
    }
    private function recursive_autoloader($files){ //recursively load a set of files
      if(!$files){return false; }
      foreach($files as $file){
        $filepath = realpath($file);
        if(!$filepath){ return false; }

        if(is_dir($filepath)){ //recursive on directories
          $this->recursive_autoloader(glob($filepath . '/*'));
        }else{
          $this->autoload_file($filepath);
        }
      }
    }
    private function autoloader($files){ //load a set of files and ignore directories
      if(!$files){ return false;}
      foreach($files as $file){
        $this->autoload_file( realpath($file) );
      }
    }
    private function autoload_file($filepath){ // load a single file
      if($filepath && file_exists($filepath) && !is_dir($filepath)){
        $file_relative = str_replace($this->autoload_dir . '\\', "", realpath($filepath));
        if($file_relative){
          include_once $file_relative; // relative to this class's file (/autoload/)
        }
      }
    }
    /**
  	 * Autoload classes recursively from classes folder (without distinction - loads everything)
  	 *
     * @return void
     */
    private function autoload_classes(){
        /** Autoloading from classes/CLASS_NAME.php **/
        $files = glob( realpath($this->autoload_dir . '/classes') . '/*' );
        $this->recursive_autoloader($files);
    }
    /**
  	 * Autoload custom post types recursively from classes folder (without distinction - loads everything)
  	 *
     * @return void
     */
    private function autoload_custom_post_types(){
        /** Autoloading from custom_post_types/CLASS_NAME.php **/
        $files = glob( realpath($this->autoload_dir . '/custom-post-types') . '/*' );
        $this->recursive_autoloader($files);
    }





    /******* Enqueue *******/
    /**
  	 * Enqueue Styles
  	 *
     * @return void
     */
    public function enqueue_styles(){
      add_action( 'admin_enqueue_scripts', array( $this, 'admin_styles' ), $this->priority );
      add_action( 'login_enqueue_scripts', array( $this, 'admin_styles' ), $this->priority );
      add_action( 'wp_enqueue_scripts', array( $this, 'styles' ), $this->priority );
    }
    public function admin_styles(){
      $parent_style = 'dashicons';
      wp_enqueue_style( 'client-admin-style', CLIENT_THEME_URL . '/compile/assets/css/admin.min.css', array( $parent_style ), $this->version, 'all' );
    }
    public function styles(){
      /*** Removing clutter ***/
      /* * Core * */
      wp_dequeue_style( 'twenty-twenty-one-style' );
      wp_dequeue_style( 'twenty-twenty-one-style-inline' );
      wp_dequeue_style( 'twenty-twenty-one-print-style' );
      wp_dequeue_style( 'tt1-dark-mode' );

      /* * Beaver Builder Header/Footer * */
      wp_dequeue_style( 'bbhf-style' );

      /*** Adding supportive styles ***/
  		$parent_style = 'wp-block-library-theme';
  		wp_enqueue_style( 'client-fontawesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.14.0/css/all.min.css', array( $parent_style ), '5.14.0', 'all' );
  		wp_enqueue_style( 'client-style', CLIENT_THEME_URL . '/compile/assets/css/main.min.css', array( $parent_style ), $this->version, 'all' );
    }

    /**
  	 * Enqueue Scripts
  	 *
     * @return void
     */
    public function enqueue_scripts(){
      add_action( 'wp_default_scripts', array( $this, 'default_scripts' ), $this->priority );
      add_action( 'wp_enqueue_scripts', array( $this, 'scripts' ), $this->priority );
    }
    public function default_scripts($scripts){
      if ( ! is_admin() && ! empty( $scripts->registered['jquery'] ) ) {
        $jquery_dependencies = $scripts->registered['jquery']->deps;
        $scripts->registered['jquery']->deps = array_diff( $jquery_dependencies, array( 'jquery-migrate' ) );
      }
    }
    public function scripts(){
      /*** Removing clutter ***/
      /* * Core * */
    	// if ( ! is_admin() && ! empty( $scripts->registered['jquery'] ) ) {
    		// $jquery_dependencies = $scripts->registered['jquery']->deps;
    		// $scripts->registered['jquery']->deps = array_diff( $jquery_dependencies, array( 'jquery-migrate' ) );
    	// }
      wp_dequeue_script( 'twenty-twenty-one-primary-navigation-script' );
      wp_dequeue_script( 'twenty-twenty-one-responsive-embeds-script' );

      /*** Adding supportive scripts ***/
      wp_enqueue_script( 'client-magnific-script', get_stylesheet_directory_uri() . '/compile/assets/js/vendor/jquery.magnific-popup.min.js', array('wp-embed'), $this->version, true );
      wp_enqueue_script( 'client-main-script', get_stylesheet_directory_uri() . '/compile/assets/js/main.min.js', array('wp-embed'), $this->version, true );
    }



    /**
  	 * Add logo to login
  	 *
     * @return void
     */
    public function add_my_login_logo(){
      add_action( 'login_enqueue_scripts', array( $this, 'my_login_logo' ) );
    }
    public function my_login_logo() {
      //If login page ( https://stackoverflow.com/questions/5266945/wordpress-how-detect-if-current-page-is-the-login-page )
      if(in_array($GLOBALS['pagenow'], array('wp-login.php', 'wp-register.php'))){
        ob_start();
          ?>
            <style type="text/css">
                #login h1, .login h1 {
                  width:100%;
                  margin:auto;
                }
                #login h1 a, .login h1 a {
                    background-image: url( '<?php echo get_theme_file_uri( ) . '/compile/assets/images/client_logo.png';  ?> ' );
                    background-size:contain;
                    width:100%;
                    height:0;
              	    padding-bottom: 50%;
                }
            </style>
          <?php
        $output = ob_get_clean();
        echo $output;

      }
    }



    /**
     * Support for different themes and any custom filters/actions to add/remove
     * support for certain features (like custom sort order in woocommerce)
     *
     * @return void
     */
    public function add_theme_support(){
      // add_action( 'after_setup_theme', array( $this, 'extra_theme_support' ) );
    }
    // public function extra_theme_support(){
    //   add_theme_support( 'extra-theme' );
    // }

    /**
     * Remove the display showing Wordpress needs to be updated
     * 
     * Why? Because this is handled via `composer update`, not through WP Admin
     *
     * @return void
     */
     public function remove_core_updates_nag() {
         global $wp_version;
         return(object) array(
             'last_checked'=> time(),
             'version_checked'=> $wp_version,
             'updates' => array()
         );
     }



    /******* Browser Sync *******/
    /**
     * Adding browser sync feature in testing/local environments.
     * To use browser sync with gulp, add `WP_ENV='development'` to .env
     *
     * @return void
     */
    public function include_browser_sync() {
      if( defined('WP_ENV') && WP_ENV=='development'){
        add_action( 'wp_enqueue_scripts', array( $this, 'browser_sync_script' ), $this->priority );
      }
    }
     public function browser_sync_script() {
       echo '<script id="__bs_script__">//<![CDATA[
         document.write("<script async src=\'http://HOST:3000/browser-sync/browser-sync-client.js?v=2.26.13\'><\/script>".replace("HOST", location.hostname));
       //]]></script>';
     }




    /******* General *******/
    /**
  	 * Add mime types
  	 *
     * @return void
     */
    public function add_custom_mime_types(){
      add_filter('upload_mimes', array( $this, 'custom_mime_types' ), $this->priority);
    }

    public function custom_mime_types($mimes) {
        return array_merge($mimes, array (
            'webm' => 'video/webm',
            'mp4'  => 'video/mp4',
        ));
    }
}
