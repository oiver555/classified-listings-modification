<?php

namespace Halwaytovegas\ClassifiedListingModifications\Elementor;
use Elementor\Plugin;
use Halwaytovegas\ClassifiedListingModifications\Helpers\Helpers;
use Halwaytovegas\ClassifiedListingModifications\Traits\Singleton;
use Halwaytovegas\ClassifiedListingModifications\Widgets\ModifiedListingSlider;

class Filters {

    use Singleton;

    private static $duplicate_posts = [];

    public function __construct() {
        add_action( 'elementor/widgets/register', [$this, 'init'], 11 );
        add_filter( 'rtcl_listing_promotions', [$this, 'add_pricing_options'], 10, 1 );
        add_filter( 'posts_results', [$this, 'filter_post_ids_to_avoid_duplicate_'], 10, 2 );
    }

    public function replace_existing_listing_slider( $boolean, $widget_instance ) {
        if ( $widget_instance->get_name() == 'rt-listing-slider' || $widget_instance->get_name() == 'rtcl-listing-slider' ) {
            return false;
        }

        return $boolean;
    }

    public function init() {
        Plugin::instance()->widgets_manager->register( new ModifiedListingSlider() );
    }

    public function add_pricing_options( $options ) {
        $options = array_merge( Helpers::get_pricing_packages( [], false, false, true ), $options );
        return $options;
    }

    public function filter_post_ids_to_avoid_duplicate_( $posts, $wp_query ) {
        if ( isset( $wp_query->query['post_type'] ) && $wp_query->query['post_type'] == 'rtcl_listing' && ! empty( self::$duplicate_posts ) ) {
            foreach ( $posts as $index => $post ) {
                foreach ( self::$duplicate_posts as $duplicate_post ) {
                    if ( $post->ID == $duplicate_post->ID ) {
                        unset( $posts[$index] );
                        break;
                    }
                }
            }
            $posts = array_values( $posts );
            return $posts;
        }

        if ( isset( $wp_query->query['post_type'] ) && $wp_query->query['post_type'] == 'rtcl_listing' && empty( self::$duplicate_posts ) ) {
            self::$duplicate_posts = $posts;
        }
        return $posts;
    }
}