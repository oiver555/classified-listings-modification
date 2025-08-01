<?php

/**
 * @author  RadiusTheme
 * @package classified-listing/templates
 * @version 1.0.0
 *
 * @var array $pricing_options
 */

use Rtcl\Helpers\Functions;
use Rtcl\Resources\Options;
use Rtcl\Models\Listing;
use Rtcl\Services\FormBuilder\FBHelper;
use Rtcl\Models\Form\Form;


$currency        = Functions::get_order_currency();
$currency_symbol = Functions::get_currency_symbol($currency);

global $listing_id;
$listing_id = $args['listing_id'];
$pricings = [];

$form_id           = absint(get_post_meta($listing_id, '_rtcl_form_id', true));
$form              = Form::query()->find($form_id);
$formData          = FBHelper::getFormData($listing_id, $form);
$banner_image_data = [];

foreach( $formData as $key => $value ) { // search for banner image only
    if(is_int(strpos($key, 'file_')) ) { // get the banner image data from array
        $banner_image_data = [...$value];
        break;
    }
}

$banner_image = isset($banner_image_data[0]['url']) ?  $banner_image_data[0]['url'] : '';

$args = array(
    'post_type' => 'listing',
    'p'         => $listing_id,
);

$query = new WP_Query($args);

if (class_exists('Rtcl\Models\Listing')) {
    $listing    = new Listing($listing_id);
    $categories = $listing->get_categories();
}


foreach ($categories as $category) {
    if ($category->parent == 0) {
        $term_id = $category->term_id;
        break;
    }
}

if(isset($term_id) ) { // if listing category is found fetch the pricing based on it from listing id, term meta
    $pricings =  ! empty(get_term_meta($term_id, '_rtcl_category_pricings', true)) ? (array)get_term_meta($term_id, '_rtcl_category_pricings', true) : [];
}

if(count($pricings) > 0 && count($pricing_options) > 0 ) { // if pricings options exists and category has pricing attached to it
    $filtered_pricings = [];

    foreach( $pricing_options as $pricing ) {
        if(in_array($pricing->ID, $pricings) ) {
            array_push($filtered_pricings, $pricing);
        }
    }

    if(! empty($filtered_pricings) ) {
        $pricing_options = $filtered_pricings;
    } 
} else {
    $pricing_options = [];
}


//if banner is empty, then remove banner based packages only
if(empty($banner_image) ) {
    foreach( $pricing_options as $index => $pricing ) {
        if(is_int(strpos($pricing->post_name, 'banner')) ) { //search for banner based packages
            unset($pricing_options[$index]);
        }
    }
}

?>

<table id="rtcl-checkout-form-data" class="rtcl-responsive-table rtcl-pricing-options form-group table table-hover table-stripped table-bordered">
    <tr>
        <th><?php esc_html_e("Pricing Option", "classified-listing"); ?></th>
        <th><?php esc_html_e("Description", "classified-listing"); ?></th>
        <th><?php esc_html_e("Days", "classified-listing"); ?></th>
        <th><?php printf(
            __('Price<br>[%1$s]', 'classified-listing'),
            esc_html($currency),
            esc_html($currency_symbol)
        ); ?></th>
        <th><?php esc_html_e("Avail.", "classified-listing"); ?></th>
    </tr>

    <?php if (!empty($pricing_options) ) : ?>
        <?php foreach ($pricing_options as $pricing) :
                $price        = get_post_meta($pricing->ID, 'price', true);
                $visible      = get_post_meta($pricing->ID, 'visible', true);
                $featured     = get_post_meta($pricing->ID, 'featured', true);
                $top          = get_post_meta($pricing->ID, '_top', true);
                $bump_up      = get_post_meta($pricing->ID, '_bump_up', true);
                $description  = get_post_meta($pricing->ID, 'description', true);
                $availability = ! empty(get_post_meta($pricing->ID, 'rtcl_pricing_available_slots', true)) ? (int)get_post_meta($pricing->ID, 'rtcl_pricing_available_slots', true) : 0;
                $free_package = get_post_meta($pricing->ID, 'rtcl_free_package', true) != null ? get_post_meta($pricing->ID, 'rtcl_free_package', true) : 0;
                $free_package_with_validity =  get_post_meta($pricing->ID, 'free_package_with_validity', true) != null ? get_post_meta($pricing->ID, 'free_package_with_validity', true) : 0;
            ?>
                <tr class="<?php echo ($availability === 0 && $free_package == 0) ? 'pricing-table disabled' : 'pricing-table'; ?>">
                    <td class="rtcl-pricing-option form-check"
                        data-label="<?php esc_attr_e("Pricing Option:", "classified-listing"); ?>">
                        <?php
                        printf(
                            '<label><input type="radio" name="%s" value="%s" class="rtcl-checkout-pricing %s" required data-price="%s"/> %s</label>',
                            'pricing_id',
                            esc_attr($pricing->ID),
                            $availability == 0 &&  $free_package == 0 ? 'disabled' : '',
                            esc_attr($price),
                            esc_html($pricing->post_title)
                        );
                        ?>
                    </td>
                    <td class="rtcl-pricing-features"
                        data-label="<?php esc_attr_e("Description:", "classified-listing"); ?>">
                        <?php Functions::print_html($description, true); ?>
                    </td>
                    <td class="rtcl-pricing-visibility"
                        data-label="<?php esc_attr_e("Visibility:", "classified-listing"); ?>">
                        <?php
                        printf('<span>%s</span>', $free_package == 1 ? 'Unlimited' : esc_html(sprintf(esc_html(number_format_i18n(absint($visible))))));
                        $promotions = Options::get_listing_promotions();
                        foreach ($promotions as $promo_id => $promotion) {
                            if (get_post_meta($pricing->ID, $promo_id, true)) {
                                echo '<span class="badge rtcl-badge-' . esc_attr($promo_id) . '">' . esc_html($promotion) . '</span>';
                            }
                        }
                        ?>
                    </td>
                    <td class="rtcl-pricing-price text-right"
                        data-label="<?php printf(
                            esc_html__('Price [%1$s %2$s]:', 'classified-listing'),
                            esc_html($currency),
                            esc_html($currency_symbol)
                        ); ?>">
                        <?php echo $free_package == 1 || $free_package_with_validity ? 'FREE' : Functions::get_payment_formatted_price($price); ?>
                    </td>
                    <td>
                        <?php echo $free_package == 1 ? '∞' : $availability; ?>
                    </td>
                </tr>
        <?php endforeach; ?>
    <?php else : ?>
        <tr>
            <th colspan="5"><?php esc_html_e("No promotion plan found.", "classified-listing"); ?></th>
        </tr>
    <?php endif; ?>
</table>
