<?php

/**
 * Builds a hierarchical array of property features terms
 *
 * Retrieves and structures property feature terms into a parent-child relationship.
 * Uses transient caching for performance except when WPML is active.
 * Only declared if function doesn't already exist to prevent conflicts.
 *
 * @return array Array of features with their children, cached when possible
 */
function wpestate_build_terms_array()
{
    //todo@@@ custom $permited_fields
    $permited_fields = wprentals_get_option('wp_estate_submission_page_fields', '');
    $parsed_features = wpestate_request_transient_cache('wpestate_get_features_array');

    if (defined('ICL_LANGUAGE_CODE')) {
        $parsed_features = false;
    }
    if ($parsed_features === false) {
        $parsed_features = array();

        //todo@@@ requeried to get Terms without cache
        $terms = get_terms(array(
            'taxonomy' => 'property_features',
            'hide_empty' => false,
            'parent' => 0

        ));

        foreach ($terms as $key => $term) {
            //todo@@@ custom code
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