<?php

    namespace Halwaytovegas\ClassifiedListingModifications\Shortcodes;
    use Halwaytovegas\ClassifiedListingModifications\Traits\Singleton;
    use Halwaytovegas\ClassifiedListingModifications\Helpers\Helpers;
    use RTLC\Helper;
use stdClass;

class Shortcodes
{
    use Singleton;

    public function __construct()
    {
        add_shortcode('check_banner', [$this, 'check_banner_shortcode']);
    }

    public function check_banner_shortcode($atts)
    {
        $atts = shortcode_atts(
            [
            'pricing_id' => ''
                 ], $atts, 'check_banner' 
        );
            

        // Ensure a plan ID is provided
        if (empty($atts['pricing_id']) ) {
            return '<p>No Pricing ID Provided</p>';
        }

        $category                                 =  "Placeholder";
        $category_link                            = '#';
        $banner_url                               = '#';
        $listing_id_belonging_to_pricing_package = ! empty(Helpers::fetch_listing_ids_based_on_pricing_id($atts['pricing_id'])) ? Helpers::fetch_listing_ids_based_on_pricing_id($atts['pricing_id'])[0] : 'empty';

        if($listing_id_belonging_to_pricing_package != 'empty' ) {
            $listing_link = get_permalink($listing_id_belonging_to_pricing_package);
            $term = new stdClass();
            $terms        = get_the_terms($listing_id_belonging_to_pricing_package, 'rtcl_category') != null ?  get_the_terms($listing_id_belonging_to_pricing_package, 'rtcl_category') : [];
            foreach( $terms as $term_obj ) {
                if($term_obj->parent == 0 ) {
                    $term =  $term_obj;
                }
            }
            $category     = isset($term->name) ?  $term->name  : 'Placeholder';
            $term_id      = isset($term->term_id) ? $term->term_id : 0;
            $category_url = get_term_link($term_id, 'rtcl_category');
            $banner_url   = Helpers::get_image_banner_url($listing_id_belonging_to_pricing_package);
        }

        ob_start();
        ?>
                    <div class="oliver-custom-header rt-el-title rtin-style-2"
                        style="background-image: url('<?php echo $banner_url; ?>'); height: 313px; background-size: cover; background-position: center; position: relative;" data-category="entertainment">
                        <a class="custom-title-link-betterdocs" style="color: white; height:75%; display:block;" href="<?php echo $listing_link; ?>"></a>
                        <a href="<?php echo $category_url; ?>" class="rtin-title" style="background: rgba(0, 0, 0, 0.6); color: white; padding: 20px; position: absolute; top: 235px; width: 100%; height: 25%;">
                        <?php echo $category; ?>
                        </a>
                    </div>
                <?php
                return ob_get_clean(); // Capture the output and return it
    }
}
