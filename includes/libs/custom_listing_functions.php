<?php

///////////////////////////////////////////////////////////////////////////////////////////
// List features and ammenities
///////////////////////////////////////////////////////////////////////////////////////////
function wpestate_build_terms_array()
{
    $permited_fields = wprentals_get_option('wp_estate_submission_page_fields', '');
    $parsed_features = wpestate_request_transient_cache('wpestate_get_features_array');

    if (defined('ICL_LANGUAGE_CODE')) {
        $parsed_features = false;
    }
    if ($parsed_features === false) {
        $parsed_features = array();
        $terms = get_terms(array(
            'taxonomy' => 'property_features',
            'hide_empty' => false,
            'parent' => 0

        ));

        foreach ($terms as $key => $term) {
//                todo@@@ custom code
            if (!in_array($term->slug, $permited_fields)) {
                continue;
            }

            $temp_array = array();
            $child_terms = get_terms(array(
                'taxonomy' => 'property_features',
                'hide_empty' => false,
                'parent' => $term->term_id
            ));

            $children = array();
            if (is_array($child_terms)) {
                foreach ($child_terms as $child_key => $child_term) {
                    $children[] = $child_term->name;
                }
            }

            $temp_array['name'] = $term->name;
            $temp_array['childs'] = $children;

            $parsed_features[] = $temp_array;
        }
        if (!defined('ICL_LANGUAGE_CODE')) {
            wpestate_set_transient_cache('wpestate_get_features_array', $parsed_features, 60 * 60 * 4);
        }
    }

    return $parsed_features;
}

function estate_listing_details($post_id)
{
    $wpestate_currency          =   esc_html(wprentals_get_option('wp_estate_currency_label_main', ''));
    $wpestate_where_currency    =   esc_html(wprentals_get_option('wp_estate_where_currency_symbol', ''));
    $measure_sys                =   esc_html(wprentals_get_option('wp_estate_measure_sys', ''));

    $property_size              =   floatval( get_post_meta($post_id, 'property_size', true));
    $property_lot_size          =  floatval(get_post_meta($post_id, 'property_lot_size', true));
    $property_rooms             = get_post_meta($post_id, 'property_rooms', true);
    $property_bedrooms          = get_post_meta($post_id, 'property_bedrooms', true);
    $property_bathrooms         = get_post_meta($post_id, 'property_bathrooms', true);
    $property_status            = wpestate_return_property_status($post_id, 'pin');

    $return_string='';

    $property_status = apply_filters('wpml_translate_single_string', $property_status, 'wprentals', 'property_status_'.$property_status);
    if ($property_status != '' && $property_status != 'normal') {
        if (wprentals_get_option('wp_estate_item_rental_type')!=1) {
            $return_string.= '<div class="listing_detail list_detail_prop_status col-md-6"><span class="item_head">'.esc_html__('Property Status', 'wprentals').':</span> ' .' '. $property_status . '</div>';
        } else {
            $return_string.= '<div class="listing_detail list_detail_prop_status col-md-6"><span class="item_head">'.esc_html__('Listing Status', 'wprentals').': </span> ' . $property_status . '</div>';
        }
    }
//    todo@@@ custom comment
//    if (wprentals_get_option('wp_estate_item_rental_type')!=1) {
//        $return_string.= '<div  class="listing_detail list_detail_prop_id col-md-6"><span class="item_head">'.esc_html__('Property ID', 'wprentals').': </span> ' . $post_id . '</div>';
//    } else {
//        $return_string.= '<div  class="listing_detail list_detail_prop_id col-md-6"><span class="item_head">'.esc_html__('Listing ID', 'wprentals').': </span> ' . $post_id . '</div>';
//    }
    if ($property_size != 0) {
        $property_size  = wprentals_custom_number_format($property_size,2) . ' '.$measure_sys.'<sup>2</sup>';
        if (wprentals_get_option('wp_estate_item_rental_type')!=1) {
            $return_string.= '<div class="listing_detail list_detail_prop_size col-md-6"><span class="item_head">'.esc_html__('Property Size', 'wprentals').':</span> ' . $property_size . '</div>';
        } else {
            $return_string.= '<div class="listing_detail list_detail_prop_size col-md-6"><span class="item_head">'.esc_html__('Listing Size', 'wprentals').':</span> ' . $property_size . '</div>';
        }
    }
    if ($property_lot_size != 0) {
        $property_lot_size = wprentals_custom_number_format($property_lot_size,2) . ' '.$measure_sys.'<sup>2</sup>';

        if (wprentals_get_option('wp_estate_item_rental_type')!=1) {
            $return_string.= '<div class="listing_detail list_detail_prop_lot_size  col-md-6"><span class="item_head">'.esc_html__('Property Lot Size', 'wprentals').':</span> ' . $property_lot_size . '</div>';
        } else {
            $return_string.= '<div class="listing_detail list_detail_prop_lot_size  col-md-6"><span class="item_head">'.esc_html__('Listing Lot Size', 'wprentals').':</span> ' . $property_lot_size . '</div>';
        }
    }
    if ($property_rooms != '') {
        $return_string.= '<div class="listing_detail list_detail_prop_rooms col-md-6"><span class="item_head">'.esc_html__('Rooms', 'wprentals').':</span> ' . floatval( $property_rooms ) . '</div>';
    }
    if ($property_bedrooms != '') {
        $return_string.= '<div class="listing_detail list_detail_prop_bedrooms col-md-6"><span class="item_head">'.esc_html__('Bedrooms', 'wprentals').':</span> ' .floatval( $property_bedrooms ). '</div>';
    }
    if ($property_bathrooms != '') {
        $return_string.= '<div class="listing_detail list_detail_prop_bathrooms col-md-6"><span class="item_head">'.esc_html__('Bathrooms', 'wprentals').':</span> ' . floatval( $property_bathrooms) . '</div>';
    }

    // Custom Fields
    $i=0;
    $custom_fields = wprentals_get_option('wpestate_custom_fields_list', '');

    if (!empty($custom_fields)) {
        while ($i< count($custom_fields)) {
            $name =   $custom_fields[$i][0];
            $label=   $custom_fields[$i][1];
            $type =   $custom_fields[$i][2];
            //    $slug =   sanitize_key ( str_replace(' ','_',$name) );
            $slug         =   wpestate_limit45(sanitize_title($name));
            $slug         =   sanitize_key($slug);

            $value=esc_html(get_post_meta($post_id, $slug, true));
            if (function_exists('icl_translate')) {
                $label     =   icl_translate('wprentals', 'wp_estate_property_custom_'.$label, $label) ;
                $value     =   icl_translate('wprentals', 'wp_estate_property_custom_'.$value, $value) ;
            }

            $label = stripslashes($label);

            if ($label!='' && $value!='') {
                $return_string.= '<div class="listing_detail list_detail_prop_'.(strtolower(str_replace(' ', '_', $label))).' col-md-6"><span class="item_head">'.ucwords($label).':</span> ';
                $return_string.= stripslashes($value);
                $return_string.='</div>';
            }
            $i++;
        }
    }

    //END Custom Fields
    $i=0;
    $custom_details = get_post_meta($post_id, 'property_custom_details', true);

    if (!empty($custom_details)) {
        foreach ($custom_details as $label=>$value) {
            if (function_exists('icl_translate')) {
                $label     =   icl_translate('wprentals', 'wp_estate_property_custom_'.$label, $label) ;
                $value     =   icl_translate('wprentals', 'wp_estate_property_custom_'.$value, $value) ;
            }

            $label = stripslashes($label);

            if ($value!='') {
                $return_string.= '<div class="listing_detail list_detail_prop_'.(strtolower(str_replace(' ', '_', $label))).' col-md-6"><span class="item_head">'.ucwords($label).':</span> ';
                $return_string.= stripslashes($value);
                $return_string.='</div>';
            }
            $i++;
        }
    }
    //END Custom Details

    return $return_string;
}