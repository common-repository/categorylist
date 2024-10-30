<?php
if ( !defined( 'ABSPATH' ) ) exit;
/*
  Plugin Name: Simple Category List 
  Plugin URI: http://nzdemocategorylist.com/
  Description: This plugin generally used for simple cateogy listing at any where. 
  Version: 1.3
  Author: Nilesh Ziniwal
  Author URI: https://profiles.wordpress.org/nziniwal
  License: GPLv2+
  Text Domain: wp-categorylist-show
*/
class CategoryListPage
{
    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;

    /**
     * Start up
     */
    public function __construct()
    {
        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
        add_action( 'admin_init', array( $this, 'page_init' ) );
    }

    /**
     * Add options page
     */
    public function add_plugin_page()
    {
        // This page will be under "Settings"
        add_options_page(
            'Settings Admin', 
            'Category List', 
            'manage_options', 
            'nzp-setting-admin', 
            array( $this, 'create_admin_page' )
        );
    }

    /**
     * Options page callback
     */
    public function create_admin_page()
    {
        // Set class property
        $this->options = get_option( 'nzp_option_name' );
        ?>
        <div class="wrap">
            <h1>Category Settings</h1>
            <form method="post" action="options.php">
            <?php
                // This prints out all hidden setting fields
                settings_fields( 'nzp_option_group' );
                do_settings_sections( 'nzp-setting-admin' );
                submit_button();

            ?>
            </form>
        </div>
        <?php
    }

    /**
     * Register and add settings
     */
    public function page_init()
    {        
        register_setting(
            'nzp_option_group', // Option group
            'nzp_option_name', // Option name
            array( $this, 'sanitize' ) // Sanitize
        );

        add_settings_section(
            'setting_section_id', // ID
            'Generate the shortcode from here', // Title
            array( $this, 'print_section_info' ), // Callback
            'nzp-setting-admin' // Page
        );  

        add_settings_field(
            'taxonomy_name', // ID
            'Valid Taxonomy Name', // Title 
            array( $this, 'taxonomy_name_callback' ), // Callback
            'nzp-setting-admin', // Page
            'setting_section_id' // Section           
        );   
         add_settings_field(
            'exclude_ids', // ID
            'Exclude Categories', // Title 
            array( $this, 'exclude_ids_callback' ), // Callback
            'nzp-setting-admin', // Page
            'setting_section_id' // Section           
        );      
   

        add_settings_field(
            'title', 
            'Title', 
            array( $this, 'title_callback' ), 
            'nzp-setting-admin', 
            'setting_section_id'
        );      
        add_settings_field(
            'Shortcode', 
            '', 
            array( $this, 'shortcode_callback' ), 
            'nzp-setting-admin', 
            'setting_section_id'
        );      
    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function sanitize( $input )
    {
        $new_input = array();
        if( isset( $input['taxonomy_name'] ) )
            $new_input['taxonomy_name'] = sanitize_text_field( $input['taxonomy_name'] );

        if( isset( $input['title'] ) )
            $new_input['title'] = sanitize_text_field( $input['title'] );

       if( isset( $input['exclude_ids'] ) )
            $new_input['exclude_ids'] = sanitize_text_field( $input['exclude_ids'] );

        return $new_input;
    }

    /** 
     * Print the Section text
     */
    public function print_section_info()
    {
        print 'Enter your settings below:';
    }

    /** 
     * Get the settings option array and print one of its values
     */
    public function taxonomy_name_callback()
    {
      printf(
            '<input type="text" id="taxonomy_name" name="nzp_option_name[taxonomy_name]" value="%s" />',
            isset( $this->options['taxonomy_name'] ) ? esc_attr( $this->options['taxonomy_name']) : ''
        );           
    }

     /** 
     * Get the settings option exclude_ids_callback
     */
    public function exclude_ids_callback()
    {
      printf(
            '<input type="text" id="exclude_ids" placeholder="Use ids with comma (,)" name="nzp_option_name[exclude_ids]" value="%s" />',
            isset( $this->options['exclude_ids'] ) ? esc_attr( $this->options['exclude_ids']) : ''
        );           
    }

    /** 
     * Get the settings title_callback
     */
    public function title_callback()
    { 
        printf(
            '<input type="text" id="title" name="nzp_option_name[title]" value="%s" />',
            isset( $this->options['title'] ) ? esc_attr( $this->options['title']) : ''
        );
    }
     /** 
     * Get the settings shortcode_callback
     */
    public function shortcode_callback()
    {
      if( $this->options['taxonomy_name']){
        echo "<h2>Copy this Shortcode</h2>";
        echo "[category_list custom_taxonomy=".$this->options['taxonomy_name']."]</br></br>";
        echo "Use this shortcode for php code : echo do_shortcode('[category_list custom_taxonomy=".$this->options['taxonomy_name']."]') ";
    }
    }

}
  /** 
   * Get the settings category_list_shortcode
   */

	function category_list_shortcode( $atts ) {
    $nzp_option_name = get_option('nzp_option_name');
 //print_r( $nzp_option_name['exclude_ids'] );

    extract( shortcode_atts( array(
        'custom_taxonomy' => '',
    ), $atts ) );
 
      if($custom_taxonomy){
          $taxonomies = get_terms( array(
              'taxonomy' => $custom_taxonomy,
              'hide_empty' => false,
              'exclude' => $nzp_option_name['exclude_ids'] 

          ) );
          if ( !empty($taxonomies) ) :
              $output = '<h2><b>'.$nzp_option_name['title'].'</b></h2>';        
              foreach( $taxonomies as $category ) {
                  if( $category->parent == 0 ) {
                      $parent_cat_link = get_category_link( $category->term_id );
                      $output.= '<ul><li><a href="'.$parent_cat_link.'">
                              '. esc_html( $category->name ) .'</a>';
                      foreach( $taxonomies as $subcategory ) {
                          if($subcategory->parent == $category->term_id) {
                          $category_link = get_category_link( $subcategory->term_id );
                          $output.= '<ul><li class="'. esc_attr( $subcategory->name ) .'"><a href="'.$category_link.'">
                              '. esc_html( $subcategory->name ) .'</a></li></ul>';
                          }
                      }
                      $output.='</ul>';
                  }
              }
              echo $output;
          endif;
      }			

}

	add_shortcode("category_list", "category_list_shortcode");

if( is_admin() )
    $nzp_settings_page = new CategoryListPage();