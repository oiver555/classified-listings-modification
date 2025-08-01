<?php

namespace Halwaytovegas\ClassifiedListingModifications\Metaboxes;
use Halwaytovegas\ClassifiedListingModifications\Traits\Singleton;
use  Halwaytovegas\ClassifiedListingModifications\Helpers\Helpers;

class Metaboxes
{

    use Singleton;
    
    public function __construct()
    {
        add_action('add_meta_boxes', [$this, 'register_metaboxes']);
    }

    /**
     * Register the metaboxes
     */
    public function register_metaboxes()
    {
        add_meta_box(
            'classified_listing_pricing_id',           
            'Pricing Package',               
            [$this, 'render_metabox'],
            'rtcl_listing',                          
            'advanced',                        
            'low'                    
        );

        add_meta_box(
            'classified_listing_pricing_package_id',           
            'Availability',               
            [$this, 'render_metabox_pricing_availability_slots'],
            'rtcl_pricing',                          
            'advanced',                        
            'low'                    
        );


        add_meta_box(
            'classified_listing_pricing_package_free_id',           
            'Free Package',               
            [$this, 'render_metabox_pricing_free'],
            'rtcl_pricing',                          
            'advanced',                        
            'low'                    
        );
    }
    public function render_metabox( $post )
    {
        $post_id       = isset($_GET['post']) ? $_GET['post'] : 0;
        $selected_data = ! empty(get_post_meta($post_id, 'rtcl_pricing_packages', true))  ? get_post_meta($post_id, 'rtcl_pricing_packages', true) : '';
        ?>
            <div class="rtcl-fb-field">
                <select name="package_pricings" id="package-pricings">
                    <?php 
                        $packages = Helpers::get_pricing_packages();
                    foreach( $packages as $package ) {
                        ?>
                                <option value="<?php echo $package['value']; ?>" <?php echo $selected_data == $package['value'] ? 'selected':''; ?>><?php echo esc_attr($package['label']);?></option>
                            <?php
                    }
                    ?>
                </select>
            </div>
        <?php
    }

    public function render_metabox_pricing_availability_slots()
    {
        $post_id                    = isset($_GET['post']) ? $_GET['post'] : 0;
        $existing_availability_data = ! empty(get_post_meta($post_id, 'rtcl_pricing_available_slots', true)) ? (int)get_post_meta($post_id, 'rtcl_pricing_available_slots', true) : 0;
        ?>
            <div class="rtcl-fb-field">
                <input id="rtcl-availability" placeholder="Enter Availability Number" type="number" class="rtcl-form-control" name="rtcl_pricing_available_slots" value="<?php echo $existing_availability_data; ?>"></input>
            </div>
        <?php
    }

    public function render_metabox_pricing_free()
    {
        $post_id               = isset($_GET['post']) ? $_GET['post'] : 0;
        $existing_free_package = get_post_meta($post_id, 'rtcl_free_package', true) != null ? get_post_meta($post_id, 'rtcl_free_package', true) : 0;
        $existing_free_package_with_validity = get_post_meta($post_id, 'free_package_with_validity', true) != null ? get_post_meta($post_id, 'free_package_with_validity', true) : 0;
        ?>
            <div class="form-check">
                <input class="form-check-input form-check-input-free-package" name="free_package" type="checkbox" id="form-check-input-free-package" value="<?php echo $existing_free_package; ?>" <?php  echo $existing_free_package == 1 ? 'checked' : ''; ?>>
                <label class="rtcl-form-check-label" for="form-check-input-free-package">Make This Package Free (<strong>Note: 'Validity Until' Will Be Set To 'Unlimited', 'Price' Will Be Set To 'Free', 'Availability' Will Be Set To 'Infinite'</strong>)</label>
            </div>
            <div class="form-check-with-validity">
                <input class="form-check-input form-check-input-free-package-with-validity" name="free_package_with_validity" type="checkbox" id="form-check-input-free-package-with-validity" value="<?php echo $existing_free_package_with_validity; ?>" <?php  echo $existing_free_package_with_validity == 1 ? 'checked' : ''; ?>>
                <label class="rtcl-form-check-label" for="form-check-input-free-package-with-validity">Make This Package Free (<strong>Note: 'Price' Will Be Set To 'Free', ('Availability', 'Validate Until' Will Not Be Affected) </strong></label>
            </div>
        <?php
    }
}
