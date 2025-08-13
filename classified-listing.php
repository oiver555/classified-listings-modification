<?php
/**
 * Plugin Name: Classified Listing Modificaiton
 * Plugin URI:  https://www.radiustheme.com/downloads/classima-classified-ads-wordpress-theme/
 * Description: Modification Of Classified Listing For Halway To Vegas.
 * Version:     1.0.1
 * Author:      Cupid Chakma
 * Author URI:  https://profiles.wordpress.org/cu121/
 * Text Domain: classified-listing-modifiaction
 * Domain Path: /languages
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 *
 * @package   Classified Listing Modificaiton
 * @author    Cupid Chakma
 * @copyright 2025 w3Aliens
 * @license   GPL-2.0+
 */
require_once 'vendor/autoload.php';

/**
 * Constant Defined
 */
define('LISTING_TEMPLATES_FOLDER', __DIR__ . '/templates/');
define('LISTING_ASSETS_FOLDER', plugins_url('', __FILE__) . '/assets/');


use Halwaytovegas\ClassifiedListingModifications\Metaboxes\Controller;
use Halwaytovegas\ClassifiedListingModifications\Metaboxes\Metaboxes;
use Halwaytovegas\ClassifiedListingModifications\Elementor\Filters;
use Halwaytovegas\ClassifiedListingModifications\Shortcodes\Shortcodes;
use Halwaytovegas\ClassifiedListingModifications\Common\Hooks;
use Rtcl\Controllers\Admin\Meta\ListingMetaColumn;

Controller::get_instance();
Metaboxes::get_instance();
Filters::get_instance();
Shortcodes::get_instance();
Hooks::get_instance();

add_action(
    'rtcl_init', function () {
    
    }
);


// add_action('init', 'action_init');

// function action_init()
// {
//         $zones =  include __DIR__ . '/zones.php';
//     foreach( $zones as $country => $states ) {
//         $parent_term = wp_insert_term($country, 'rtcl_location');
//         if(!is_wp_error($parent_term) ) {
//             foreach( $states as $state) {
//                 $state = wp_insert_term(
//                     $state, 
//                     'rtcl_location', 
//                     [
//                         'parent' => $parent_term['term_id']
//                     ]
//                 );
//             }
//         }
//     }
// }
