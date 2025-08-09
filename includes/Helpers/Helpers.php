<?php

namespace Halwaytovegas\ClassifiedListingModifications\Helpers;

use stdClass;
use Rtcl\Services\FormBuilder\FBField;
use Rtcl\Services\FormBuilder\FBHelper;

use function Adminer\error;
use function IAWPSCOPED\retry;

class Helpers
{
    public static function get_pricing_packages( $extra_args = [], $include_labels_and_ids = true, $include_labels_as_values = false, $include_slugs_as_values = false )
    {
        global $wpdb;

        $sql        = "SELECT ID, post_title, post_name FROM {$wpdb->posts} WHERE post_type = %s AND post_status = 'publish'";
        $query_args = ['rtcl_pricing'];

        // Merge $extra_args for additional filtering (example: post_status, order)
        if (isset($extra_args['post_status']) ) {
            $sql .= " AND post_status = %s";
            $query_args[] = $extra_args['post_status'];
        }

        // Optional ordering
        if (isset($extra_args['orderby']) && isset($extra_args['order']) ) {
            $sql .= " ORDER BY {$extra_args['orderby']} {$extra_args['order']}";
        }

        $results = $wpdb->get_results($wpdb->prepare($sql, ...$query_args));

        $pricing_datas = [];

        foreach ( $results as $row ) {
            $title = html_entity_decode($row->post_title, ENT_NOQUOTES, 'UTF-8');

            if ($include_labels_and_ids ) {
                $pricing_datas[] = [
                    'label' => $title,
                    'value' => $row->ID
                ];
            } else if ($include_labels_as_values ) {
                $pricing_datas[$row->ID] = $title;
            } else if ($include_slugs_as_values ) {
                $pricing_datas[$row->post_name] = $title;
            }
        }

        return $pricing_datas;
    }

    public static function get_banner_image_url( $listing_id )
    {
        $listing = rtcl()->factory->get_listing($listing_id);
        $form    = $listing->getForm();
        $fields  = $form->getFieldAsGroup(FBField::CUSTOM);

        foreach ( $fields as $fieldName => $field ) {
            // Check if the field is the banner upload field
            if (strpos($fieldName, 'file_') !== false ) { // Assuming banner fields start with "file_"
                $field = new FBField($field);
                $value = $field->getFormattedCustomFieldValue($listing_id);

                $is_local = self::is_localhost();

                // If it's an array and contains the URL, return it
                if (! empty($value) && is_array($value) ) {
                    foreach ( $value as $file ) {
                        if (! empty($file['url']) ) {
                            $transformed_url = $file['url'];

                            if ($is_local ) {
                                // Convert HTTPS to HTTP in local mode
                                $transformed_url = str_replace('https://', 'http://', $transformed_url);
                            } else {
                                // Convert HTTP to HTTPS and .local to .com in production
                                $transformed_url = str_replace('http://', 'https://', $transformed_url);
                                $transformed_url = str_replace('.local', '.com', $transformed_url);
                            }

                            return esc_url($transformed_url); // Return the modified URL
                        }
                    }
                }
            }
        }

        return 'https://halfwaytovegas.com/wp-content/uploads/2024/09/Banner_Placeholder.jpg'; // Return empty if no banner is found
    }

    public static function remove_spaces_around_dashes( $string )
    {
        // Replace spaces around en dash, em dash, or hyphen with no space
        return preg_replace('/\s*([–—-])\s*/u', '$1', $string);
    }

    public static function is_localhost()
    {
        $whitelist = ['127.0.0.1', '::1', 'localhost'];
        return in_array($_SERVER['REMOTE_ADDR'], $whitelist) ||
        strpos($_SERVER['HTTP_HOST'], 'localhost') !== false;
    }

    public static function get_image_banner_url( $listing_id )
    {
        $listing = rtcl()->factory->get_listing($listing_id);
        $form    = $listing->getForm();
        $fields  = FBHelper::getFormData($listing_id, $form);

        foreach ( $fields as $fieldName => $field ) {
            // Check if the field is the banner upload field
            if (strpos($fieldName, 'file_') !== false ) { // Assuming banner fields start with "file_"
                $url = isset($field[0]['url']) ? $field[0]['url'] : 'https://halfwaytovegas.com/wp-content/uploads/2024/09/Banner_Placeholder.jpg';
                return $url;
            }
        }

        return 'https://halfwaytovegas.com/wp-content/uploads/2024/09/Banner_Placeholder.jpg'; // Return empty if no banner is found
    }

    public static function get_parent_category( $categories )
    {
        //runs in O(n) linear time
        $parent_category = new stdClass();
        foreach ( $categories as $category ) {
            if ($category->parent == 0 ) {
                $parent_category = $category;
            }
        }
        return $parent_category;
    }

    /**
     * Fetch The Post ID's Belonging To Specific Pricing ID
     *
     * @param  int $pricing_id
     * @return array
     */
    public static function fetch_listing_ids_based_on_pricing_id( $pricing_id, $post_status = 'publish' )
    {
        global $wpdb;
        $sql    = $wpdb->prepare("SELECT post_id FROM {$wpdb->prefix}postmeta WHERE meta_value = %d AND meta_key = %s", [$pricing_id, 'rtcl_pricing_packages']);
        $result = $wpdb->get_results($sql, ARRAY_A);
        $datas = array_column($result, 'post_id');
        $filteredData = [];

        foreach( $datas as $data ) {
            if(get_post_status($data) == $post_status ) {
                array_push($filteredData, $data);
            }
        }
        
        return  $filteredData;
    }

    public static function get_author_id_and_post_status($post_id)
    {
        $post = get_post($post_id);
    
        if (! $post ) {
            return false; // Post not found
        }
    
        return array(
            'author_id'  => $post->post_author,
            'post_status' => $post->post_status,
        );
    }
}
