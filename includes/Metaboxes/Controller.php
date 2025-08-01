<?php

namespace Halwaytovegas\ClassifiedListingModifications\Metaboxes;
use Halwaytovegas\ClassifiedListingModifications\Traits\Singleton;

class Controller
{

    use Singleton;

    public function __construct()
    {
        add_action('create_rtcl_category', [$this, 'save_category_meta'], 10, 3);
        add_action('edited_rtcl_category', [$this, 'save_category_meta'], 10, 3);
        add_filter('rtcl_checkout_process_new_order_args', [$this, 'save_pricing_package_id_on_listing'], 10, 4); // in checkout billing process save the pricing id on the listing id(to view on the admin side)
        add_action('save_post_rtcl_pricing', [$this, 'save_pricing_availability_slots'], 10);
        add_action('save_post_rtcl_listing', [$this, 'save_selected_package_data'], 10);
        add_action('save_post_rtcl_pricing', [$this, 'save_selected_free_package'], 10);
        add_action('save_post_rtcl_pricing', [$this, 'save_selected_free_package_without_validity'], 10);
    }

    public function save_pricing_package_id_on_listing( $newOrderArgs, $pricing, $gateway, $checkout_data )
    {
        $listing_id = isset($checkout_data['listing_id']) ? $checkout_data['listing_id'] : 'empty';
        $pricing_id = isset($checkout_data['pricing_id']) ? $checkout_data['pricing_id'] : 'empty';

        if ($listing_id != 'empty' && $pricing_id != 'empty' ) {
            update_post_meta($listing_id, 'rtcl_pricing_packages', $pricing_id);
        }

        return $newOrderArgs;
    }

    public function save_category_meta( $term_id, $tt_id, $args )
    {
        $selected_pricing_packages = isset($_POST['pricing_packages']) ? $_POST['pricing_packages'] : [];

        update_term_meta($term_id, '_rtcl_category_pricings', $selected_pricing_packages);
    }

    public function save_pricing_availability_slots()
    {
        $post_id      = isset($_POST['post_ID']) ? $_POST['post_ID'] : 0;
        $availability = isset($_POST['rtcl_pricing_available_slots']) ? $_POST['rtcl_pricing_available_slots'] : 0;
        if ($post_id != 0 ) {
            update_post_meta($post_id, 'rtcl_pricing_available_slots', $availability);
        }
    }

    public function save_selected_package_data()
    {
        $selected_package = isset($_POST['package_pricings']) ? $_POST['package_pricings'] : '';
        $post_id          = isset($_POST['post_ID']) ? $_POST['post_ID'] : '';
        if (! empty($selected_package) && ! empty($post_id) ) {
            update_post_meta($post_id, 'rtcl_pricing_packages', $selected_package);
        }
    }

    public function save_selected_free_package()
    {
        $post_id      = isset($_POST['post_ID']) ? $_POST['post_ID'] : 0;
        $free_package = isset($_POST['free_package']) ? $_POST['free_package'] : 0;
        update_post_meta($post_id, 'rtcl_free_package', $free_package);
    }

    public function save_selected_free_package_without_validity()
    {
        $post_id      = isset($_POST['post_ID']) ? $_POST['post_ID'] : 0;
        $free_package_with_validity = isset($_POST['free_package_with_validity']) ? $_POST['free_package_with_validity'] : 0;
        update_post_meta($post_id, 'free_package_with_validity', $free_package_with_validity);
    }
}
