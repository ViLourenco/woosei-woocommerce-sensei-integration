<?php /**
 * Plugin Name: WooCommerce + Sensei Integration
 * Plugin URI: http://webvl.com.br
 * Description: Plugin customizado - WooCommerce + Sensei
 * Author: Vinícius Lourenço
 * Author URI: http://blog.vilourenco.com.br
 * Version: 1.0
 *
 * Copyright: (c) 2016
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
*/

if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WooCommerce_Sensei_Integration' ) ) {

/**
 * Plugin main class.
 */
class WooSei_WooCommerce_Sensei_Integration {

  /**
   * Plugin version.
   *
   * @var string
   */
  const VERSION = '1.0.0';

  /**
   * Instance of this class.
   *
   * @var object
   */

  /**
  * Instance of this class.
  *
  * @var object
  */  
  protected static $instance = null;

  /**
  * Check if user has the WooCommerce class active.
  *
  * @var bool
  */
  protected $hasWoo = false;

  /**
  * Check if user has the Sensei class active.
  *
  * @var bool
  */
  protected $hasSensei = false;

  /**
  * Check if user has the WooCommerce Extra Fields For Brazil class active.
  *
  * @var bool
  */
  protected $hasWooExtraFields = false;

  /**
   * Initialize the plugin.
   *
   */
  private function __construct() {
    // Load plugin text domain.    

    if ( class_exists( 'Sensei_Main' ) && class_exists( 'WooCommerce' ) && class_exists( 'Extra_Checkout_Fields_For_Brazil' )) {
      $this->includes();
      $this->include_scripts();
    } else {
      if( !class_exists( 'Sensei_Main' ) ){
        $this->hasSensei = true;
      }
      if( !class_exists( 'WooCommerce' ) ){
        $this->hasWoo = true;
      }
      if( !class_exists( 'Extra_Checkout_Fields_For_Brazil' ) ){
        $this->hasWooExtraFields = true;
      }      
      add_action( 'admin_notices', array( $this, 'fallback_notice' ) );
    }
  }

  /**
   * Return an instance of this class.
   *
   * @return object single instance of this class.
   */
  public static function get_instance() {    
    if ( null == self::$instance ) {
      self::$instance = new self;
    }

    return self::$instance;
  }

  /**
   * Admin includes.
   */
  private function includes() {
    include_once 'includes/class-woosei-woocommerce-sense-integration.php';
  }


  /**
   * Method to call the custom scripts.
   */  
  private function include_scripts(){
    add_action( 'wp_enqueue_scripts', array( $this, 'woosei_custom_scripts' ) );
  }

  /**
   * Method to register and call the scripts!
   */
  public function woosei_custom_scripts(){
    wp_register_script('woosei-custom-js', plugins_url( 'js/custom-js.js' , __FILE__ ) , '', false, false );
    wp_enqueue_script('woosei-custom-js');
  }

  /**
   * WooSei fallback notice.
   *
   *Wee need some plugins to work, and if any isn't active we'll show you!
   */
  public function fallback_notice() {
    $plugins_needed = array();
    if($this->hasWoo){
      $plugins_needed[] = '<li>WooCommerce - <a href="https://br.wordpress.org/plugins/woocommerce/">Download</a></li>';
    }
    if($this->hasSensei){
      $plugins_needed[] = '<li>Sensei</li> - <a href="https://github.com/Automattic/sensei">Download</a>';
    }
    if($this->hasWooExtraFields){
      $plugins_needed[] = '<li>WooCommerce Extra Fields For Brazil - <a href="https://br.wordpress.org/plugins/woocommerce-extra-checkout-fields-for-brazil/">Download</li>';
    }
    
    echo '<div class="error"><p>Atenção, para funcionamento completo do plugin <strong>WooSei - WooCommerce Sensei Integration</strong> é necessário ativar os seguintes plugins abaixo:</p><ul>';
    foreach ($plugins_needed as $plugin_name){
      echo $plugin_name;
    }
    echo '</ul></div>';
  }
}

/**
 * Initialize the plugin.
 */
$WooSei_WooCommerce_Sensei_Integration = WooSei_WooCommerce_Sensei_Integration::get_instance();

}
