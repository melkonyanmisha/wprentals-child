<?php

///////////////////////////////////////////////////////////////////////////////////////////
// List features and ammenities
///////////////////////////////////////////////////////////////////////////////////////////
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


function estate_listing_price($post_id)
{
    $return_string                  =   '';
    $property_price                 =   floatval(get_post_meta($post_id, 'property_price', true));
    $property_price_before_label    =   esc_html(get_post_meta($post_id, 'property_price_before_label', true));
    $property_price_after_label     =   esc_html(get_post_meta($post_id, 'property_price_after_label', true));
    $property_price_per_week        =   floatval(get_post_meta($post_id, 'property_price_per_week', true));
    $property_price_per_month       =   floatval(get_post_meta($post_id, 'property_price_per_month', true));
    $cleaning_fee                   =   floatval(get_post_meta($post_id, 'cleaning_fee', true));
    $city_fee                       =   floatval(get_post_meta($post_id, 'city_fee', true));
    $cleaning_fee_per_day           =   floatval(get_post_meta($post_id, 'cleaning_fee_per_day', true));
    $city_fee_percent               =   floatval(get_post_meta($post_id, 'city_fee_percent', true));
    $city_fee_per_day               =   floatval(get_post_meta($post_id, 'city_fee_per_day', true));
    $price_per_guest_from_one       =   floatval(get_post_meta($post_id, 'price_per_guest_from_one', true));
    $overload_guest                 =   floatval(get_post_meta($post_id, 'overload_guest', true));
    $checkin_change_over            =   floatval(get_post_meta($post_id, 'checkin_change_over', true));
    $checkin_checkout_change_over   =   floatval(get_post_meta($post_id, 'checkin_checkout_change_over', true));
    $min_days_booking               =   floatval(get_post_meta($post_id, 'min_days_booking', true));
    $extra_price_per_guest          =   floatval(get_post_meta($post_id, 'extra_price_per_guest', true));
    $price_per_weekeend             =   floatval(get_post_meta($post_id, 'price_per_weekeend', true));
    $security_deposit               =   floatval(get_post_meta($post_id, 'security_deposit', true));
    $early_bird_percent             =   floatval(get_post_meta($post_id, 'early_bird_percent', true));
    $early_bird_days                =   floatval(get_post_meta($post_id, 'early_bird_days', true));
    $rental_type                    =   esc_html(wprentals_get_option('wp_estate_item_rental_type'));
    $booking_type                   =   wprentals_return_booking_type($post_id);
    $max_extra_guest_no             =   floatval(get_post_meta($post_id, 'max_extra_guest_no', true));
    $week_days=array(
        '0'=>esc_html__('All', 'wprentals'),
        '1'=>esc_html__('Monday', 'wprentals'),
        '2'=>esc_html__('Tuesday', 'wprentals'),
        '3'=>esc_html__('Wednesday', 'wprentals'),
        '4'=>esc_html__('Thursday', 'wprentals'),
        '5'=>esc_html__('Friday', 'wprentals'),
        '6'=>esc_html__('Saturday', 'wprentals'),
        '7'=>esc_html__('Sunday', 'wprentals')

    );

    $wpestate_currency              = esc_html(wprentals_get_option('wp_estate_currency_label_main', ''));
    $wpestate_where_currency        = esc_html(wprentals_get_option('wp_estate_where_currency_symbol', ''));

    $th_separator   =   wprentals_get_option('wp_estate_prices_th_separator', '');
    $custom_fields  =   wprentals_get_option('wpestate_currency', '');

    $property_price_show                 =  wpestate_show_price_booking($property_price, $wpestate_currency, $wpestate_where_currency, 1);
    $property_price_per_week_show        =  wpestate_show_price_booking($property_price_per_week, $wpestate_currency, $wpestate_where_currency, 1);
    $property_price_per_month_show       =  wpestate_show_price_booking($property_price_per_month, $wpestate_currency, $wpestate_where_currency, 1);
    $cleaning_fee_show                   =  wpestate_show_price_booking($cleaning_fee, $wpestate_currency, $wpestate_where_currency, 1);
    $city_fee_show                       =  wpestate_show_price_booking($city_fee, $wpestate_currency, $wpestate_where_currency, 1);

    $price_per_weekeend_show             =  wpestate_show_price_booking($price_per_weekeend, $wpestate_currency, $wpestate_where_currency, 1);
    $extra_price_per_guest_show          =  wpestate_show_price_booking($extra_price_per_guest, $wpestate_currency, $wpestate_where_currency, 1);
    $extra_price_per_guest_show          =  wpestate_show_price_booking($extra_price_per_guest, $wpestate_currency, $wpestate_where_currency, 1);
    $security_deposit_show               =  wpestate_show_price_booking($security_deposit, $wpestate_currency, $wpestate_where_currency, 1);

    $setup_weekend_status= esc_html(wprentals_get_option('wp_estate_setup_weekend', ''));
    $weekedn = array(
        0 => __("Sunday and Saturday", "wprentals"),
        1 => __("Friday and Saturday", "wprentals"),
        2 => __("Friday, Saturday and Sunday", "wprentals")
    );





    if ($price_per_guest_from_one!=1) {
        if ($property_price != 0) {
            $return_string.='<div class="listing_detail list_detail_prop_price_per_night col-md-6"><span class="item_head">'.wpestate_show_labels('price_label', $rental_type, $booking_type).':</span> ' .$property_price_before_label.' '. $property_price_show.' '.$property_price_after_label. '</div>';
        }

        if ($property_price_per_week != 0) {
            $return_string.='<div class="listing_detail list_detail_prop_price_per_night_7d col-md-6"><span class="item_head">'.wpestate_show_labels('price_week_label', $rental_type, $booking_type).':</span> ' . $property_price_per_week_show . '</div>';
        }

        if ($property_price_per_month != 0) {
            $return_string.='<div class="listing_detail list_detail_prop_price_per_night_30d col-md-6"><span class="item_head">'.wpestate_show_labels('price_month_label', $rental_type, $booking_type).':</span> ' . $property_price_per_month_show . '</div>';
        }

        if ($price_per_weekeend!=0) {
            $return_string.='<div class="listing_detail list_detail_prop_price_per_night_weekend col-md-6"><span class="item_head">'.esc_html__('Price per weekend ', 'wprentals').'('.$weekedn[$setup_weekend_status].') '.':</span> ' . $price_per_weekeend_show . '</div>';
        }

        if ($extra_price_per_guest!=0) {
            $return_string.='<div class="listing_detail list_detail_prop_price_per_night_extra_guest col-md-6"><span class="item_head">'.esc_html__('Extra Price per guest', 'wprentals').':</span> ' . $extra_price_per_guest_show . '</div>';
        }
    } else {
        if ($extra_price_per_guest!=0) {
            $return_string.='<div class="listing_detail list_detail_prop_price_per_night_extra_guest_price col-md-6"><span class="item_head">'.esc_html__('Price per guest', 'wprentals').':</span> ' . $extra_price_per_guest_show . '</div>';
        }
    }

    $options_array=array(
        0   =>  esc_html__('Single Fee', 'wprentals'),
        1   =>  ucfirst(wpestate_show_labels('per_night', $rental_type, $booking_type)),
        2   =>  esc_html__('Per Guest', 'wprentals'),
        3   =>  ucfirst(wpestate_show_labels('per_night', $rental_type, $booking_type)).' '.esc_html__('per Guest', 'wprentals')
    );

    if ($cleaning_fee != 0) {
        $return_string.='<div class="listing_detail list_detail_prop_price_cleaning_fee col-md-6"><span class="item_head">'.esc_html__('Cleaning Fee', 'wprentals').':</span> ' . $cleaning_fee_show ;
        $return_string .= ' '.$options_array[$cleaning_fee_per_day];

        $return_string.='</div>';
    }

    if ($city_fee != 0) {
        $return_string.='<div class="listing_detail list_detail_prop_price_tax_fee col-md-6"><span class="item_head">'.esc_html__('City Tax Fee', 'wprentals').':</span> ' ;
        if ($city_fee_percent==0) {
            $return_string .= $city_fee_show.' '.$options_array[$city_fee_per_day];
        } else {
            $return_string .= $city_fee.'%'.' '.__('of price per night', 'wprentals');
        }
        $return_string.='</div>';
    }

    if ($min_days_booking!=0) {
        $return_string.='<div class="listing_detail list_detail_prop_price_min_nights col-md-6"><span class="item_head">'.esc_html__('Minimum no of', 'wprentals').' '.wpestate_show_labels('nights', $rental_type, $booking_type) .':</span> ' . $min_days_booking . '</div>';
    }

    if ($overload_guest!=0) {
        $return_string.='<div class="listing_detail list_detail_prop_price_overload_guest col-md-6"><span class="item_head">'.esc_html__('Allow more guests than the capacity: ', 'wprentals').' </span>'.esc_html__('yes', 'wprentals').'</div>';
    }



    if ($checkin_change_over!=0) {
        $return_string.='<div class="listing_detail list_detail_prop_book_starts col-md-6"><span class="item_head">'.esc_html__('Booking starts only on', 'wprentals').':</span> ' . $week_days[$checkin_change_over ]. '</div>';
    }

    if ($security_deposit!=0) {
        $return_string.='<div class="listing_detail list_detail_prop_book_starts col-md-6"><span class="item_head">'.esc_html__('Security deposit', 'wprentals').':</span> ' . $security_deposit_show. '</div>';
    }

    if ($checkin_checkout_change_over!=0) {
        $return_string.='<div class="listing_detail list_detail_prop_book_starts_end col-md-6"><span class="item_head">'.esc_html__('Booking starts/ends only on', 'wprentals').':</span> ' .$week_days[$checkin_checkout_change_over] . '</div>';
    }


    if ($early_bird_percent!=0) {
        $return_string.='<div class="listing_detail list_detail_prop_book_starts_end col-md-6"><span class="item_head">'.esc_html__('Early Bird Discount', 'wprentals').':</span> '.$early_bird_percent.'% '.esc_html__('discount', 'wprentals').' '.esc_html__('for bookings made', 'wprentals').' '.$early_bird_days.' '.esc_html__('nights in advance', 'wprentals').'</div>';
    }

    if ($max_extra_guest_no!=0) {
        $return_string.='<div class="listing_detail list_detail_prop_book_starts_end col-md-6"><span class="item_head">'.esc_html__('Maximum extra guests allowed', 'wprentals').':</span> ' .sprintf( _n( '%s Guest', '%s Guests', $max_extra_guest_no, 'wprentals' ), number_format_i18n( $max_extra_guest_no ) ).'</div>';
    }



    $extra_pay_options          =      (get_post_meta($post_id, 'extra_pay_options', true));

    if (is_array($extra_pay_options) && !empty($extra_pay_options)) {
        $return_string.='<div class="listing_detail list_detail_prop_book_starts_end col-md-12"><span class="item_head">'.esc_html__('Extra options', 'wprentals').':</span></div>';
        foreach ($extra_pay_options as $key=>$wpestate_options) {
//            todo@@@ added custom class col-md-6
            $return_string.='<div class="extra_pay_option col-md-6"> ';
            $extra_option_price_show                       =  wpestate_show_price_booking($wpestate_options[1], $wpestate_currency, $wpestate_where_currency, 1);
            $return_string.= ''.$wpestate_options[0].': '. $extra_option_price_show.' '.$options_array[$wpestate_options[2]];

            $return_string.= '</div>';
        }
    }


    return $return_string;
}