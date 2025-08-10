<?php
    namespace Halwaytovegas\ClassifiedListingModifications\Common;

    use Halwaytovegas\ClassifiedListingModifications\Helpers\Helpers;
use Halwaytovegas\ClassifiedListingModifications\Traits\Singleton;
use RTLC\Helper;

use function Adminer\error;

class Hooks
{
    use Singleton;

    public $visibility_param;
    public $ran_once = false;
    public $_run_once_to_set_admin_listing_to_publish = false;

    public function __construct()
    {
        add_action('init', [$this, 'get_visibility_parameter']);
        add_action('rtcl_category_add_form_fields', [$this, 'category_add_meta_field'], 11, 2);
        add_action('rtcl_category_edit_form_fields', [$this, 'category_edit_meta_field'], 11, 2);
        add_filter('rtcl_locate_template', [$this, 'template_include'], 999, 2);
        add_action('wp_enqueue_scripts', [$this, 'action_wp_enqueue_scripts']);
        add_action('admin_enqueue_scripts', [$this, 'action_wp_enqueue_scripts_admin']);
        //             add_action( 'rtcl_checkout_data', [$this, 'set_listing_status_to_publish'], 9, 5 );
        add_action('rtcl_checkout_data', [$this, 'set_admin_fake_ads_to_draft'], 9, 5);
        add_action('rtcl_checkout_data', [$this, 'availability_decrement_based_on_selection'], 11, 5);
        add_action('run_cron_to_update_status_of_listing', [$this, 'trigger_expiry_date_check']);

        if (! wp_next_scheduled('run_cron_to_update_status_of_listing') ) {
            wp_schedule_event(time(), 'hourly', 'run_cron_to_update_status_of_listing');
        }

        add_filter('rtcl_listing_form_after_update_responses_redirect_url', [$this, 'append_visibility_in_url'], 10, 5);
        add_action('rtcl_before_delete_listing', [$this, 'increment_the_listing_availability'], 10, 1);
        add_action('rtcl_before_delete_listing', [$this, 'set_the_fake_ads_to_publish_from_draft_when_real_ad_is_deleted_by_user'], 10, 1);

        add_action('admin_menu', [$this, 'remove_add_new_for_rctl_menu'], 99999);

        add_filter('manage_rtcl_listing_posts_columns', [$this,'add_custom_column_to_rtcl_listing'], 10, 1);

        add_action('manage_rtcl_listing_posts_custom_column', [$this, 'add_custom_data_to_rtcl_listing'], 10, 2);

        add_action('wp_trash_post', [$this, 'set_the_fake_ads_to_publish_from_draft_when_real_ad_is_deleted_by_user'], 10, 1);
        add_action('wp_trash_post', [$this, 'increment_the_listing_availability'], 10, 1);

        // add_action( 'parse_query', [ $this, 'parse_query_for_pricing' ], 7, 1 );
    }

    public function add_custom_column_to_rtcl_listing($columns)
    {
        $first_half    = array_slice($columns, 0, 4);
        $mid_half      = ['rtcl_pricing_plans' => 'Pricing Plans'];
        $last_half     = array_slice($columns, 4);
        $merged_colums = array_merge($first_half, $mid_half, $last_half);
        return $merged_colums;
    }

    public function add_custom_data_to_rtcl_listing($column, $post_id)
    {
        switch( $column ) {
        case 'rtcl_pricing_plans':
            $package_id = get_post_meta($post_id, 'rtcl_pricing_packages', true);
            $package_name = empty($package_id) ? 'No Package Assigned' : get_the_title($package_id);
            echo $package_name;
            break;
        }
    }
 
    /**
     * Remove 'Add New Listing'
     *
     * @return void
     */
    public function remove_add_new_for_rctl_menu()
    {
        global $submenu;
        unset($submenu['edit.php?post_type=rtcl_listing'][10]);
    }

    public function increment_the_listing_availability($post_id)
    {

        if($this->ran_once ) {
            return;
        }
        
        $post             = get_post($post_id);
        $post_author_id   = isset($post->post_author) ? $post->post_author : 0;
        $user             = get_userdata($post_author_id);

        if(! in_array('administrator', $user->roles) ) {
            $selected_listing_package = ! empty(get_post_meta($post_id, 'rtcl_pricing_packages', true))  ? get_post_meta($post_id, 'rtcl_pricing_packages', true) : '';
            $availability             = ! empty(get_post_meta($selected_listing_package, 'rtcl_pricing_available_slots', true)) ? (int)get_post_meta($selected_listing_package, 'rtcl_pricing_available_slots', true) : 0;
            ++$availability;
            update_post_meta($selected_listing_package, 'rtcl_pricing_available_slots', $availability);
            $this->ran_once = true;
        }
    }

    public function set_the_fake_ads_to_publish_from_draft_when_real_ad_is_deleted_by_user($post_id)
    {
        if($this->_run_once_to_set_admin_listing_to_publish ) {
            return;
        }
        $pricing_id_of_the_post_being_deleted = ! empty(get_post_meta($post_id, 'rtcl_pricing_packages', true)) ? get_post_meta($post_id, 'rtcl_pricing_packages', true) : '';
        $listings_assigned_to_this_pricing_id =  Helpers::fetch_listing_ids_based_on_pricing_id($pricing_id_of_the_post_being_deleted, 'draft'); // get the posts, that are draft.
        
        foreach( $listings_assigned_to_this_pricing_id as $post_id ) {
            $post_data = ! empty(Helpers::get_author_id_and_post_status($post_id)) ? Helpers::get_author_id_and_post_status($post_id) : [];
            
            if(! empty($post_data) && isset($post_data['author_id']) &&  isset($post_data['post_status']) &&  $post_data['author_id'] == 1 && $post_data['post_status'] == 'draft' ) {
                wp_update_post(
                    array(
                    'ID'          => $post_id,
                    'post_status' => 'publish'
                    )
                );
                $this->_run_once_to_set_admin_listing_to_publish = true;
                break;
            }
        }
    }

    public function get_visibility_parameter()
    {
        if(isset($_GET['visibility'])) {
            $this->visibility_param = $_GET['visibility'];
        }
    }

    public function append_visibility_in_url( $url, $posting_type, $listing_id, $bool, $message )
    {
        return $url;
    }

    public function trigger_expiry_date_check()
    {
        $public_posts = get_posts(
            [
            'post_type'   => 'rtcl_listing',
            'post_status' => 'publish',
            'numberposts' => -1
            ] 
        );

        $number_of_listing_expired_belong_to_term_id = [];

        if (! empty($public_posts) ) {
            foreach ( $public_posts as $post ) {
                $post_id        = isset($post->ID) ? $post->ID : 0;
                $post_author_id = isset($post->post_author) ? $post->post_author : 0;
                $user           = get_userdata($post_author_id);
                $expiry_date    = get_post_meta($post_id, 'expiry_date', true);
                $category     = Helpers::get_parent_category(get_the_terms($post_id, 'rtcl_category'));

                if (in_array('administrator', $user->roles) ) { //exclude admin created posts, only update the status of non-admin created posts to draft
                    continue;
                }

                if (! empty($expiry_date) ) {
                    $timestamp = strtotime($expiry_date);
                    if (time() >= $timestamp ) {
                        $post_args = [
                            'ID'          => $post_id,
                            'post_status' => 'draft'
                        ];
                        wp_update_post($post_args);
                        if(! isset($number_of_listing_expired_belong_to_term_id[$category->term_id]) ) {
                            $number_of_listing_expired_belong_to_term_id[$category->term_id] = 0;
                        } else {
                            $number_of_listing_expired_belong_to_term_id[$category->term_id]++;
                        }
                    }
                }
            }
        }

        //revert the draft posts created by admin to publish if the original user listing expires
        $draft_posts = get_posts(
            [
            'post_type'   => 'rtcl_listing',
            'post_status' => 'draft',
            'numberposts' => -1
            ] 
        );

        if (! empty($draft_posts) ) {
            foreach ( $draft_posts as $post ) {
                $post_id        = isset($post->ID) ? $post->ID : 0;
                $post_author_id = isset($post->post_author) ? $post->post_author : 0;
                $user           = get_userdata($post_author_id);
                $category     = Helpers::get_parent_category(get_the_terms($post_id, 'rtcl_category'));

                if ($user && in_array('administrator', $user->roles) && isset($number_of_listing_expired_belong_to_term_id[$category->term_id]) && $number_of_listing_expired_belong_to_term_id[$category->term_id]  != 0 ) {
                    $post_args = [
                        'ID'          => $post_id,
                        'post_status' => 'publish'
                    ];
                    $selected_listing_package = ! empty(get_post_meta($post_id, 'rtcl_pricing_packages', true))  ? get_post_meta($post_id, 'rtcl_pricing_packages', true) : '';
                    $availability             = ! empty(get_post_meta($selected_listing_package, 'rtcl_pricing_available_slots', true)) ? (int)get_post_meta($selected_listing_package, 'rtcl_pricing_available_slots', true) : 0;
                    ++$availability;
                    wp_update_post($post_args);
                    update_post_meta($selected_listing_package, 'rtcl_pricing_available_slots', $availability);
                    $number_of_listing_expired_belong_to_term_id[$category->term_id]--;
                }
            }
        }
    }

    public function set_listing_status_to_publish( $checkout_data, $pricing, $gateway, $request, $errors )
    {
        $listing_id = isset($checkout_data['listing_id']) ? $checkout_data['listing_id'] : 0;
        $post_args  = [
            'ID'          => $listing_id,
            'post_status' => 'publish'
        ];
        wp_update_post($post_args);
    }

    public function set_admin_fake_ads_to_draft( $checkout_data, $pricing, $gateway, $request, $errors )
    {
        $pricing_id = isset($checkout_data['pricing_id']) ?  $checkout_data['pricing_id'] : 0;

        if($pricing_id != 0 ) {
            $listing_ids = Helpers::fetch_listing_ids_based_on_pricing_id($pricing_id);
            
            foreach( $listing_ids as $listing_id ) {
                $post = get_post($listing_id);
                
                if(isset($post->post_author) && $post->post_author ==1  ) { // set one admin fake add to draft, when real ad is posted.
                    $post_args = [
                        'ID'          => $listing_id,
                        'post_status' => 'draft'
                    ];

                    $data = wp_update_post($post_args);
                    break;
                }
            }
        }
        // $listing_id = isset($checkout_data['listing_id']) ? $checkout_data['listing_id'] : 0;
        // $categories = get_the_terms($listing_id, 'rtcl_category');
   
        // if (! empty($categories) ) {
        //     $category     = Helpers::get_parent_category($categories);

        //     $default_args = [
        //         'post_type'  => 'rtcl_listing',
        //         'hide_empty' => true,
        //         'tax_query'  => [
        //             [
        //                 'taxonomy' => 'rtcl_category',
        //                 'field'    => 'term_id',
        //                 'terms'    => isset($category->term_id) ? $category->term_id : 0
        //             ]
        //         ]
        //     ];

        //     $query_posts = get_posts($default_args);

        //     if (count($query_posts) > 0 ) {
        //         foreach ( $query_posts as $post ) {
        //             $post_id            = isset($post->ID) ? $post->ID : 0;
        //             $post_author_id     = isset($post->post_author) ? $post->post_author : 0;
        //             $post_status        = $post->post_status;
        //             $user               = get_userdata($post_author_id);
        //             $current_user_roles = wp_get_current_user() != null ? wp_get_current_user()->roles : [];

        //             if (in_array('administrator', $current_user_roles) ) { // if admin is creating listing posts, then do not update the fake ad's post status
        //                 continue;
        //             }
                 
        //             if ($user && in_array('administrator', $user->roles) && $post_status == 'publish' ) {
        //                 $post_args = [
        //                     'ID'          => $post_id,
        //                     'post_status' => 'draft'
        //                 ];

        //                 wp_update_post($post_args);
        //                 break;
        //             }
        //         }
        //     }
        // }
    }

    public function availability_decrement_based_on_selection( $checkout_data, $pricing, $gateway, $request, $errors )
    {
        $pricing_id                 = isset($checkout_data['pricing_id']) ? $checkout_data['pricing_id'] : 0;
        $listing_id                 = isset($checkout_data['listing_id']) ? $checkout_data['listing_id'] : 0;
        $existing_availability_data = ! empty(get_post_meta($pricing_id, 'rtcl_pricing_available_slots', true)) ? (int) get_post_meta($pricing_id, 'rtcl_pricing_available_slots', true) : 0;
        $existing_availability_data -= 1;
        $current_user_role = isset(wp_get_current_user()->roles) ? wp_get_current_user()->roles : [];

        if ($existing_availability_data != -1 && ( ! in_array('administrator', $current_user_role) ) ) { // if its zero do not save it in db (this is reducing the availability of spots), but do not reduce the availability for the admin
            update_post_meta($pricing_id, 'rtcl_pricing_available_slots', $existing_availability_data);
        }

        // this is for setting the validity of the listing product logic \\
        $pricing_package_validity = (int) get_post_meta($pricing_id, "visible", true); // data type is returned in number of days e.g:20, 30, etc

        if ($pricing_package_validity <= 0 ) { //if package validity is 0 days, set to never expires
            update_post_meta($listing_id, 'never_expires', true);
        } else { // if package is not zero days, set the package validity days and time
            $future_time_stamp = strtotime("+{$pricing_package_validity} days");
            $date_and_time     = date('Y-m-d H:i:s', $future_time_stamp);
            update_post_meta($listing_id, 'never_expires', false);
            update_post_meta($listing_id, 'expiry_date', $date_and_time);
        }
    }

    public function template_include( $template, $template_name )
    {
        if ($template_name == 'checkout/promotions.php' ) {
            $template = LISTING_TEMPLATES_FOLDER . 'promotions.php';
        }

        return $template;
    }

    public function category_edit_meta_field( $tag, $taxonomy )
    {
        $pricing                = Helpers::get_pricing_packages();
        $term_id                = isset($tag->term_id) ? $tag->term_id : 0;
        $selected_terms_pricing = ! empty(get_term_meta($term_id, '_rtcl_category_pricings', true)) ? get_term_meta($term_id, '_rtcl_category_pricings', true) : [];
        ?>
            <tr class="form-field rtcl-term-group-wrap" id="rtcl-category-types">
                <th scope="row">
                    <label for="rtcl-category-types"><?php esc_html_e('Pricing', 'classified-listing'); ?></label>
                </th>
                <td>
                    <fieldset class="rtcl-checkbox-wrap">
        <?php if (! empty($pricing) ) : ?>
            <?php foreach ( $pricing as $price ):
                ?>
                                <label>
                                    <input type="checkbox" name="pricing_packages[]" value="<?php echo $price['value']; ?>"<?php echo in_array($price['value'], $selected_terms_pricing) ? 'checked' : ''; ?>/><?php echo esc_html($price['label']); ?>
                                </label>
            <?php endforeach; ?>
        <?php endif; ?>
                    </fieldset>
                </td>
            </tr>
        <?php
    }

    public function category_add_meta_field()
    {
        $pricing = Helpers::get_pricing_packages();
        ?>
            <tr class="form-field rtcl-term-group-wrap" id="rtcl-category-types">
                <th scope="row">
                    <label for="rtcl-category-types"><?php esc_html_e('Pricing', 'classified-listing'); ?></label>
                </th>
                <td>
                    <fieldset class="rtcl-checkbox-wrap">
        <?php if (! empty($pricing) ) : ?>
            <?php foreach ( $pricing as $price ):
                ?>
                                <label>
                                    <input type="checkbox" name="pricing_packages[]" value="<?php echo $price['value']; ?>"/><?php echo esc_html($price['label']); ?>
                                </label>
            <?php endforeach; ?>
        <?php endif; ?>
                    </fieldset>
                </td>
            </tr>
        <?php
    }

    public function action_wp_enqueue_scripts()
    {
        wp_enqueue_script('custom-pricing-listing', LISTING_ASSETS_FOLDER . 'js/package-listing.js', ['jquery'], wp_rand(), true);
        wp_enqueue_style('custom-pricing-listing', LISTING_ASSETS_FOLDER . 'css/package-listing.css', [], wp_rand());
    }

    public function action_wp_enqueue_scripts_admin()
    {
        $post_id               = isset($_GET['post']) ? $_GET['post'] : 0;
        $existing_free_package = get_post_meta($post_id, 'rtcl_free_package', true) != null ? get_post_meta($post_id, 'rtcl_free_package', true) : 0;
        wp_enqueue_script('custom-admin-listing', LISTING_ASSETS_FOLDER . 'js/admin.js', ['jquery'], wp_rand(), true);
        wp_enqueue_style('custom-admin-listing', LISTING_ASSETS_FOLDER . 'css/admin.css', [], wp_rand());
        wp_localize_script(
            'custom-admin-listing', 'adminListing', [
            'isFree' => $existing_free_package
                 ] 
        );
    }

    public function parse_query_for_pricing( $query )
    {
        global $post_type;
        $pricing        = Helpers::get_pricing_packages([], false, false, true);
        $keys_as_values = array_keys($pricing);

        if (isset($_GET['promotion']) && in_array($_GET['promotion'], $keys_as_values) && rtcl()->post_type == $post_type ) {
            $post_object = get_page_by_path($_GET['promotion'], OBJECT, 'rtcl_pricing');
            $post_id     = isset($post_object->ID) ? $post_object->ID : 0;

            if ($post_id > 0 ) {
                // Initialize the meta query
                $post_ids = Helpers::fetch_listing_ids_based_on_pricing_id($post_id);

                if (! empty($post_ids) ) {
                    $query->set('post__in', $post_ids);
                }
            }
        }
    }
}
