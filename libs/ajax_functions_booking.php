<?php
/*
* Reply to review 
*
*
*/
//todo@@@ this file no need to add to git
add_action('wp_ajax_wpestate_review_message_reply', 'wpestate_review_message_reply' );

if( !function_exists('wpestate_review_message_reply') ):
    function wpestate_review_message_reply(){


        check_ajax_referer( 'wprentals_reviews_actions_nonce', 'security' );
        $current_user   =   wp_get_current_user();
        $userID         =   $current_user->ID;
        if ( !is_user_logged_in() ) {
            exit('ko');
        }
        if($userID === 0 ){
            exit('out pls');
        }




        $commentId         =   intval($_POST['commentId']);
        $propertyid        =   esc_html($_POST['propertyid']);
        $content           =   esc_html($_POST['content']);

        $post_author_id = get_post_field( 'post_author', $propertyid );




        if($post_author_id!=$userID){
            $answer=array(
                'succes'    =>  false,
                'message'   =>  esc_html__('You are not the property owner!','wprentals')
            );
            print json_encode($answer);
        }else{

            update_comment_meta($commentId,'owner_reply',$content);
            $author_email   = get_comment_author_email( $commentId );
            $arguments=array(
                'reply_content'     =>  $content,
                'property_name'     =>  get_the_title($propertyid),
                'author_email'      =>  $author_email
            );







            wpestate_send_booking_email('review_reply',$author_email,$arguments);


            $answer=array(
                'succes'        =>  true,
                'message'       =>  esc_html__('We posted your reply','wprentals'),
                'arguments'=>$arguments


            );
            print json_encode($answer);
        }

        die();
    }
endif;




/*
*
*
*
*/


add_action('wp_ajax_wpestate_send_full_pay_reminder', 'wpestate_send_full_pay_reminder' );
if( !function_exists('wpestate_send_full_pay_reminder') ):
    function wpestate_send_full_pay_reminder(){
        check_ajax_referer( 'wprentals_bookings_actions_nonce', 'security' );
        $current_user = wp_get_current_user();
        $userID                         =   $current_user->ID;


        if ( !is_user_logged_in() ) {
            exit('ko');
        }
        if($userID === 0 ){
            exit('out pls');
        }


        $userID             =   $current_user->ID;
        $user_email         =   $current_user->user_email;
        $invoice_id         =   intval($_POST['invoice_id']);
        $bookid             =   intval($_POST['booking_id']);
        $property_id        =   get_post_meta($bookid, 'booking_id', true);
        $booking_from_date  =   get_post_meta($bookid, 'booking_from_date', true);

        $the_post       =   get_post( $bookid);
        $book_author    =   $the_post->post_author;
        $user           =   get_user_by('id',$book_author);
        $user_email     =   $user->user_email;


        $arguments=array(
            'invoice_id'        =>  $invoice_id,
            'booking_id'        =>  $bookid,
            'property_url'      =>  esc_url ( get_permalink($property_id) ),
            'property_title'    =>  get_the_title($property_id),
            'until_date'        =>   wpestate_convert_dateformat_reverse($booking_from_date)
        );


        wpestate_select_email_type($user_email,'full_invoice_reminder',$arguments);

        die();
    }
endif;





////////////////////////////////////////////////////////////////////////////////
/// Ajax  check booking
////////////////////////////////////////////////////////////////////////////////

add_action('wp_ajax_wpestate_ajax_check_booking_valability_on_invoice', 'wpestate_ajax_check_booking_valability_on_invoice' );

if( !function_exists('wpestate_ajax_check_booking_valability_on_invoice') ):
    function wpestate_ajax_check_booking_valability_on_invoice(){
    exit();
        //  check_ajax_referer('booking_ajax_nonce_front','security');

        $bookid                 =   intval($_POST['bookid']);
        $wpestate_book_from     =   get_post_meta($bookid, 'booking_from_date', true);
        $wpestate_book_to       =   get_post_meta($bookid, 'booking_to_date', true);
        $listing_id             =   get_post_meta($bookid, 'booking_id', true);
        $wprentals_is_per_hour  =   wprentals_return_booking_type($listing_id);

        $reservation_array  = get_post_meta($listing_id, 'booking_dates',true);
        if($reservation_array==''){
            $reservation_array = wpestate_get_booking_dates($listing_id);
        }

        $wpestate_book_from     =  wpestate_convert_dateformat($wpestate_book_from);
        $wpestate_book_to       =  wpestate_convert_dateformat($wpestate_book_to);

        $from_date      =   new DateTime($wpestate_book_from);
        $from_date_unix =   $from_date->getTimestamp();

        $to_date        =   new DateTime($wpestate_book_to);
        $to_date_unix_check   =   $to_date->getTimestamp();


        if($wprentals_is_per_hour==2){
            $to_date->modify('-1 hour');
        } else {
            $to_date->modify('yesterday');
        }


        $to_date_unix   =   $to_date->getTimestamp();


        // checking booking avalability
        while ($from_date_unix < $to_date_unix){
            $from_date->modify('tomorrow');
            if($wprentals_is_per_hour==2){
                $to_date->modify('+1 hour');
            } else {
                $to_date->modify('tomorrow');
            }

            $from_date_unix =   $from_date->getTimestamp();
            if( array_key_exists($from_date_unix,$reservation_array ) ){
                print 'stop';
                die();
            }
        }
        print 'run';
        die();

    }
endif;


  ////////////////////////////////////////////////////////////////////////////////
/// Ajax  check booking
////////////////////////////////////////////////////////////////////////////////

add_action('wp_ajax_wpestate_ajax_check_booking_valability_internal', 'wpestate_ajax_check_booking_valability_internal' );

if( !function_exists('wpestate_ajax_check_booking_valability_internal') ):
    function wpestate_ajax_check_booking_valability_internal(){

        if(isset($_POST['allinone'])&& intval($_POST['allinone']==1) ){
            check_ajax_referer( 'wprentals_allinone_nonce','security');
        }else{
            check_ajax_referer( 'wprentals_add_booking_nonce','security');

        }

        $current_user = wp_get_current_user();
        $userID                         =   $current_user->ID;


        if ( !is_user_logged_in() ) {
            exit('ko');
        }
        if($userID === 0 ){
            exit('out pls');
        }

        //  check_ajax_referer('booking_ajax_nonce_front','security');
        $wpestate_book_from  =   esc_html($_POST['book_from']);
        $wpestate_book_to    =   esc_html($_POST['book_to']);

        $wpestate_book_from  = wpestate_convert_dateformat($wpestate_book_from);
        $wpestate_book_to    = wpestate_convert_dateformat($wpestate_book_to);


        $listing_id =   intval($_POST['listing_id']);
        $internal   =   intval($_POST['internal']);
        $wprentals_is_per_hour  =   wprentals_return_booking_type($listing_id);
        $mega                   =   wpml_mega_details_adjust($listing_id);
        $reservation_array      =   get_post_meta($listing_id, 'booking_dates',true);

        if($reservation_array   ==  ''){
            $reservation_array = wpestate_get_booking_dates($listing_id);
        }


        $from_date      =   new DateTime($wpestate_book_from);
        $from_date_unix =   $from_date->getTimestamp();

        $to_date        =   new DateTime($wpestate_book_to);
        $to_date_unix_check   =   $to_date->getTimestamp();

        $date_checker=  strtotime(date("Y-m-d 00:00", $from_date_unix));

        if($wprentals_is_per_hour==2){
            $to_date->modify('-1 hour');
            $diff=3600;
        }else{
            $to_date->modify('yesterday');
            $diff=86400;
        }


        $to_date_unix   =   $to_date->getTimestamp();



        //check min days situation
        /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        if($internal==0){

            $min_days_booking   =   intval   ( get_post_meta($listing_id, 'min_days_booking', true) );
            $min_days_value     =   0;

            if (is_array($mega) && array_key_exists ($date_checker,$mega)){
                if( isset( $mega[$from_date_unix]['period_min_days_booking'] ) ){
                    $min_days_value=  $mega[$date_checker]['period_min_days_booking'];

                    if( ($from_date_unix + ($min_days_value-1)*$diff) > $to_date_unix ) {
                        print 'stopdays';
                        die();
                    }

                }

            }else if($min_days_booking > 0 ){
                    if( ($from_date_unix + $min_days_booking*$diff) > $to_date_unix ) {
                        print 'stopdays';
                        die();
                    }
            }
        }


        // checking booking avalability
        while ($from_date_unix < $to_date_unix){
            if($wprentals_is_per_hour==2){
                $from_date->modify('+1 hour');
            }else{
                $from_date->modify('tomorrow');
            }

            $from_date_unix =   $from_date->getTimestamp();

            if( array_key_exists($from_date_unix,$reservation_array ) ){
                print 'stop';
                die();
            }
        }
        print 'run';
        die();

    }
endif;




////////////////////////////////////////////////////////////////////////////////
/// Ajax  check booking
////////////////////////////////////////////////////////////////////////////////
add_action('wp_ajax_wpestate_ajax_check_booking_valability', 'wpestate_ajax_check_booking_valability' );
add_action('wp_ajax_nopriv_wpestate_ajax_check_booking_valability', 'wpestate_ajax_check_booking_valability' );
if( !function_exists('wpestate_ajax_check_booking_valability') ):
    //todo@@@ customized function
    function wpestate_ajax_check_booking_valability(){


        $wpestate_book_from  =   esc_html($_POST['book_from']);
        $wpestate_book_to    =   esc_html($_POST['book_to']);
        $listing_id =   intval($_POST['listing_id']);
        $internal   =   intval($_POST['internal']);
        $mega       =   wpml_mega_details_adjust($listing_id);
        $wprentals_is_per_hour      =   wprentals_return_booking_type($listing_id);
        $all_listings_ids_in_group = get_all_listings_ids_in_group($listing_id);
        $wpestate_book_from  = wpestate_convert_dateformat($wpestate_book_from);
        $wpestate_book_to    = wpestate_convert_dateformat($wpestate_book_to);
        $from_date      =   new DateTime($wpestate_book_from);
        $from_date_unix =   $from_date->getTimestamp();
        $to_date        =   new DateTime($wpestate_book_to);
        $to_date_unix_check   =   $to_date->getTimestamp();
        $date_checker=  strtotime(date("Y-m-d 00:00", $from_date_unix));

        $to_date_unix   =   $to_date->getTimestamp();
        if($wprentals_is_per_hour==2){
            $diff=3600;
        }else{
            $diff=86400;
        }



        //check min days situation
        /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


        if($internal==0){

            $min_days_booking   =   intval   ( get_post_meta($listing_id, 'min_days_booking', true) );
            $min_days_value     =   0;

            if (is_array($mega) && array_key_exists ($date_checker,$mega)){

                if( isset( $mega[$date_checker]['period_min_days_booking'] ) ){
                    $min_days_value=  $mega[$date_checker]['period_min_days_booking'];


                    if( abs($from_date_unix- $to_date_unix)/$diff  < $min_days_value ) {
                        print 'stopdays';
                        die();
                    }

                }

            }else if($min_days_booking > 0 ){

                    if( abs($from_date_unix- $to_date_unix)/$diff  < $min_days_booking ) {
                        print 'stopdays';
                        die();
                    }
            }
        }

        // check in check out days
        /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        $checkin_checkout_change_over   =   floatval   ( get_post_meta($listing_id, 'checkin_checkout_change_over', true) );
        $weekday                        =   date('N', $from_date_unix);
        $end_bookday                    =   date('N', $to_date_unix_check);
        if (is_array($mega) && array_key_exists ($from_date_unix,$mega)){
            if( isset( $mega[$from_date_unix]['period_checkin_checkout_change_over'] ) &&  $mega[$from_date_unix]['period_checkin_checkout_change_over']!=0 ){
                $period_checkin_checkout_change_over=  $mega[$from_date_unix]['period_checkin_checkout_change_over'];


                if($weekday!= $period_checkin_checkout_change_over || $end_bookday !=$period_checkin_checkout_change_over) {
                    print 'stopcheckinout';
                    die();
                }

            }

        }else if($checkin_checkout_change_over > 0 ){
            if($weekday!= $checkin_checkout_change_over || $end_bookday !=$checkin_checkout_change_over) {
                print 'stopcheckinout';
                die();
            }
        }

        // check in  days
        /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        $checkin_change_over            =   floatval   ( get_post_meta($listing_id, 'checkin_change_over', true) );

        if (is_array($mega) && array_key_exists ($from_date_unix,$mega)){
            if( isset( $mega[$from_date_unix]['period_checkin_change_over'] ) &&  $mega[$from_date_unix]['period_checkin_change_over']!=0){
                $period_checkin_change_over=  $mega[$from_date_unix]['period_checkin_change_over'];


                if($weekday!= $period_checkin_change_over) {
                    print 'stopcheckin';
                    die();
                }

            }

        }else if($checkin_change_over > 0 ){
            if($weekday!= $checkin_change_over) {
                print 'stopcheckin';
                die();
            }
        }

        if (current_user_is_timeshare() && !empty($all_listings_ids_in_group)) {

            foreach ($all_listings_ids_in_group as $current_listings_id) {
                $reservation_grouped_array[$current_listings_id] = get_post_meta($current_listings_id, 'booking_dates', true);

                if ($reservation_grouped_array[$current_listings_id] == '') {
                    $reservation_grouped_array[$current_listings_id] = wpestate_get_booking_dates($current_listings_id);
                }

            }

        }else{
            $reservation_grouped_array[] = get_post_meta($listing_id, 'booking_dates',true);
            if($reservation_grouped_array[0]==''){
                $reservation_grouped_array[] = wpestate_get_booking_dates($listing_id);
            }
        }

        foreach ($reservation_grouped_array as $reservation_array){
            if( array_key_exists($from_date_unix,$reservation_array ) ){
                print 'stop array_key_exists';
                die();
            }
        }


        if(!$wprentals_is_per_hour==2){
            $to_date->modify('yesterday');
        }
        $to_date_unix   =   $to_date->getTimestamp();


        // checking booking avalability
        if($wprentals_is_per_hour==2){
            foreach ($reservation_grouped_array as $reservation_array){
                if(wprentals_check_hour_booking_overlap_reservations($from_date_unix,$to_date_unix,$reservation_array)){
                    print 'stop hour';
                    die();
                }
            }

        }else{
            foreach ($reservation_grouped_array as $reservation_array) {
                if (wprentals_check_booking_overlap_reservations(
                    $from_date,
                    $from_date_unix,
                    $to_date_unix,
                    $reservation_array
                )) {
                    print 'stop';
                    die();
                }
            }
        }

        print 'run';
        die();

    }
endif;



/*
*
* check if one interval overlap another interval wheb hour booking
* return true if overlaps - false if not
*
*/

function wprentals_check_hour_booking_overlap_reservations($from_date_unix,$to_date_unix,$reservation_array){
  foreach($reservation_array as $reservation_start=>$reservation_end):
    if( wprentals_check_is_timestamp($reservation_start) ){
      if( wprentals_check_dates_overlap($reservation_start,$reservation_end,$from_date_unix,$to_date_unix) ){
        return true;
      }
    }
  endforeach;

  return false;
}



/*
*
* check if one interval overlap another interval
* return true if overlaps - false if not
*
*/

function wprentals_check_booking_overlap_reservations($from_date,$from_date_unix,$to_date_unix,$reservation_array){

    while ($from_date_unix < $to_date_unix){
      $from_date->modify('tomorrow');
      $from_date_unix =   $from_date->getTimestamp();
      if(is_array($reservation_array) && array_key_exists($from_date_unix,$reservation_array ) && $from_date_unix<$to_date_unix){
         return true;
       }
    }
    return false;
}







/*
*
* check if one interval overlap another interval
* return true if overlap
*
*/
function wprentals_check_dates_overlap($reservation_start,$reservation_end,$booking_start,$booking_end){

    if( $booking_start >= $reservation_start  ){
      if($booking_start<$reservation_end):
        return true;
      endif;
    }else{
      if($booking_end > $reservation_start):
        return true;
      endif;
    }

    return false;
}



/*
*
* check if string is a valid unixtimestap
*
*
*/
function wprentals_check_is_timestamp($string){
   try {
       new DateTime('@' . $string);
   } catch(Exception $e) {
       return false;
   }
   return true;
}

////////////////////////////////////////////////////////////////////////////////
/// Ajax message reply
////////////////////////////////////////////////////////////////////////////////


add_action('wp_ajax_wpestate_message_reply', 'wpestate_message_reply' );

if( !function_exists('wpestate_message_reply') ):
    function wpestate_message_reply(){
        check_ajax_referer( 'wprentals_inbox_actions_nonce', 'security' );
        $current_user = wp_get_current_user();
        $userID                         =   $current_user->ID;
        if ( !is_user_logged_in() ) {
            exit('ko');
        }
        if($userID === 0 ){
            exit('out pls');
        }


        wp_reset_postdata();
        wp_reset_query();

        $messid         =   intval($_POST['messid']);
        $title          =   esc_html($_POST['title']);
        $content        =   esc_html($_POST['content']);
        $receiver_id    =   wpsestate_get_author($messid);
        $reply_to_id    =   intval($_POST['reply_to_id']);

        $message_to_user    = get_post_meta($messid,'message_to_user',true);

        if( $current_user->ID != $message_to_user && $current_user->ID != $receiver_id ) {
            exit('you don\'t have the right');
        }

        $my_post = array(
            'post_title'    => $title,
            'post_content'  => $content,
            'post_status'   => 'publish',
            'post_type'     => 'wpestate_message',
            'post_author'   => $userID,
            'post_parent'   => $messid
        );

        $post_id = wp_insert_post( $my_post );


        update_post_meta($post_id, 'delete_source', 0);
        update_post_meta($post_id, 'delete_destination', 0);
        update_post_meta($post_id, 'message_to_user', $receiver_id);


        $mes_to     =   get_post_meta($messid, 'message_to_user',true );
        $mess_from  =   get_post_meta($messid, 'message_from_user',true );
        wpestate_increment_mess_mo($reply_to_id);
        update_post_meta($messid, 'message_status'.$userID, 'read' );
        update_post_meta($post_id, 'message_status'.$reply_to_id, 'unread' );

        $email_sender=get_userdata($userID);
        update_post_meta($post_id, 'message_from_user', $userID);

        // decide who is receiver
        if($userID == $mes_to ){
            $receiver           =   get_userdata($mess_from);
            $receiver_email     =   $receiver->user_email;
            wpestate_send_booking_email('inbox',$receiver_email,$content);
        }else{

            $receiver           =   get_userdata($mes_to);
            $receiver_email     =   $receiver->user_email;
            wpestate_send_booking_email('inbox',$receiver_email,$content);
        }


        print intval($post_id);
        die();
    }
endif;



////////////////////////////////////////////////////////////////////////////////
/// Ajax  delete invoice
////////////////////////////////////////////////////////////////////////////////


add_action('wp_ajax_wpestate_booking_mark_as_read', 'wpestate_booking_mark_as_read' );

if( !function_exists('wpestate_booking_mark_as_read') ):
    function wpestate_booking_mark_as_read(){
        check_ajax_referer( 'wprentals_inbox_actions_nonce', 'security' );
        $current_user   =   wp_get_current_user();
        $userID         =   $current_user->ID;


        if ( !is_user_logged_in() ) {
            exit('ko');
        }
        if($userID === 0 ){
            exit('out pls');
        }

        $messid             =   intval($_POST['messid']);
        $receiver_id        =   wpsestate_get_author($messid);
        $message_to_user    =   get_post_meta($messid,'message_to_user',true);
        $replies_to_mark    =   intval($_POST['replies_to_mark']);


        if( $current_user->ID != $message_to_user && $current_user->ID != $receiver_id ) {
            exit('you don\'t have the right');
        }

        $mess_status =      get_post_meta($messid, 'message_status'.$current_user->ID, true);
        if($mess_status!=='read'){
            update_post_meta($messid, 'message_status'.$current_user->ID, 'read');
        }

        $unread=abs(intval ( get_user_meta($userID,'unread_mess',true) - $replies_to_mark));

        update_user_meta($userID,'unread_mess',$unread);
        $args_child = array(
            'post_type'         => 'wpestate_message',
            'post_status'       => 'publish',
            'posts_per_page'    => -1,
            'order'             => 'ASC',
            'post_parent'       => $messid,
        );

        $message_selection_child = new WP_Query($args_child);

        while ($message_selection_child->have_posts()): $message_selection_child->the_post();
            $mess_id=get_the_ID();
            update_post_meta($mess_id, 'message_status'.$userID, 'read' );

            print 'update '.$mess_id.' /';
        endwhile;



        die();
    }
endif;

////////////////////////////////////////////////////////////////////////////////
/// Ajax  delete invoice
////////////////////////////////////////////////////////////////////////////////


add_action('wp_ajax_wpestate_booking_delete_mess', 'wpestate_booking_delete_mess' );

if( !function_exists('wpestate_booking_delete_mess') ):
    function wpestate_booking_delete_mess(){
        check_ajax_referer( 'wprentals_inbox_actions_nonce', 'security' );
        $current_user = wp_get_current_user();
        $userID                         =   $current_user->ID;

        if ( !is_user_logged_in() ) {
            exit('ko');
        }

        if($userID === 0 ){
            exit('out pls');
        }


        $userID             =   $current_user->ID;
        $messid             =   intval($_POST['messid']);
        $receiver_id        =   wpsestate_get_author($messid);
        $message_to_user    =   get_post_meta($messid,'message_to_user',true);

        if( $current_user->ID != $message_to_user && $current_user->ID != $receiver_id ) {
            exit('you don\'t have the right');
        }

        update_post_meta($messid, 'delete_destination'.$userID, 1);

        $mess_status =      get_post_meta($messid, 'message_status'.$current_user->ID, true);
        if($mess_status!=='read'){
            $unread=abs(intval ( get_user_meta($current_user->ID,'unread_mess',true) - 1));
            update_user_meta($current_user->ID,'unread_mess',$unread);
        }


        die();
    }
endif;

////////////////////////////////////////////////////////////////////////////////
/// Ajax  delete invoice
////////////////////////////////////////////////////////////////////////////////


add_action('wp_ajax_wpestate_create_pay_user_invoice_form', 'wpestate_create_pay_user_invoice_form' );

if( !function_exists('wpestate_create_pay_user_invoice_form') ):
    function wpestate_create_pay_user_invoice_form(){
        check_ajax_referer( 'wprentals_reservation_actions_nonce', 'security' );
        $current_user   =   wp_get_current_user();
        $userID         =   $current_user->ID;
        $user_email     =   $current_user->user_email;

        if ( !is_user_logged_in() ) {
            exit('ko');
        }
        if($userID === 0 ){
            exit('out pls');
        }


        $bookid         =   intval($_POST['booking_id']);
        $the_post       =   get_post( $bookid);
        $is_full        =   intval($_POST['is_full']);
        $invoice_id     =   intval($_POST['invoice_id']);
        $bookid         =   intval($_POST['booking_id']);

        if( $current_user->ID != $the_post->post_author ) {
            exit('you don\'t have the right to see this');
        }


        if($is_full!=1){
            if( !wpestate_check_reservation_period($bookid)){
                die('');
            }
        }




        $wpestate_currency      =   esc_html( get_post_meta($invoice_id, 'invoice_currency',true) );
        $default_price          =   get_post_meta($invoice_id, 'default_price', true);
        $wpestate_where_currency=   esc_html( wprentals_get_option('wp_estate_where_currency_symbol', '') );
        $booking_from_date      =   esc_html(get_post_meta($bookid, 'booking_from_date', true));
        $property_id            =   esc_html(get_post_meta($bookid, 'booking_id', true));
        $rental_type            =   esc_html(wprentals_get_option('wp_estate_item_rental_type', ''));
        $booking_type           =   wprentals_return_booking_type($property_id);

        $booking_to_date        =   esc_html(get_post_meta($bookid, 'booking_to_date', true));
        $booking_guests         =   floatval(get_post_meta($bookid, 'booking_guests', true));
        $booking_array          =   wpestate_booking_price($booking_guests,$invoice_id,$property_id, $booking_from_date, $booking_to_date,$bookid);
        $price_per_weekeend     =   floatval(get_post_meta($property_id, 'price_per_weekeend', true));

        $classic_period_days = wprentals_return_standart_days_period();

        if($booking_array['numberDays']>=$classic_period_days['week_days'] && $booking_array['numberDays']< $classic_period_days['month_days'] && intval($booking_array['week_price']!=0)){
            $default_price=$booking_array['week_price'];
        }else if($booking_array['numberDays']> $classic_period_days['month_days'] && intval($booking_array['month_price']!=0) ){
            $default_price=$booking_array['month_price'];
        }

        $wp_estate_book_down            =   get_post_meta($invoice_id, 'invoice_percent', true);
        $wp_estate_book_down_fixed_fee  =   get_post_meta($invoice_id, 'invoice_percent_fixed_fee', true);
        $include_expeses                =   esc_html ( wprentals_get_option('wp_estate_include_expenses','') );
        $invoice_price                  =   floatval( get_post_meta($invoice_id, 'item_price', true)) ;

        if($include_expeses=='yes'){
            $total_price_comp               =   $invoice_price;
        }else{
            $total_price_comp               =   $invoice_price - $booking_array['city_fee'] - $booking_array['cleaning_fee'];
        }



        $depozit                    =   wpestate_calculate_deposit($wp_estate_book_down,$wp_estate_book_down_fixed_fee,$total_price_comp);
        $balance                    =   $invoice_price-$depozit;
        $price_show                 =   wpestate_show_price_booking_for_invoice($default_price,$wpestate_currency,$wpestate_where_currency,0,1);
        $price_per_weekeend_show    =   wpestate_show_price_booking_for_invoice($price_per_weekeend,$wpestate_currency,$wpestate_where_currency,0,1);
        $total_price_show           =   wpestate_show_price_booking_for_invoice($invoice_price,$wpestate_currency,$wpestate_where_currency,0,1);
        $depozit_show               =   wpestate_show_price_booking_for_invoice($depozit,$wpestate_currency,$wpestate_where_currency,0,1);
        $balance_show               =   wpestate_show_price_booking_for_invoice($balance,$wpestate_currency,$wpestate_where_currency,0,1);
        $city_fee_show              =   wpestate_show_price_booking_for_invoice($booking_array['city_fee'],$wpestate_currency,$wpestate_where_currency,0,1);
        $cleaning_fee_show          =   wpestate_show_price_booking_for_invoice($booking_array['cleaning_fee'],$wpestate_currency,$wpestate_where_currency,0,1);
        $inter_price_show           =   wpestate_show_price_booking_for_invoice($booking_array['inter_price'],$wpestate_currency,$wpestate_where_currency,0,1);
        $total_guest                =   wpestate_show_price_booking_for_invoice($booking_array['total_extra_price_per_guest'],$wpestate_currency,$wpestate_where_currency,1,1);
        $guest_price                =   wpestate_show_price_booking_for_invoice($booking_array['extra_price_per_guest'],$wpestate_currency,$wpestate_where_currency,1,1);
        $extra_price_per_guest      =   wpestate_show_price_booking($booking_array['extra_price_per_guest'],$wpestate_currency,$wpestate_where_currency,1);




        $depozit_stripe     =   $depozit;

        $details            =   get_post_meta($invoice_id, 'renting_details', true);


        // strip details generation
        $is_stripe_live= esc_html ( wprentals_get_option('wp_estate_enable_stripe','') );


        print '
            <div class="create_invoice_form">
                   <h3>'.esc_html__( 'Invoice INV','wprentals').$invoice_id.'</h3>';


                print '
                   <div class="invoice_table">';
                    if($invoice_id!=0){

                    }

                        print'
                       <div class="invoice_data">
                            <span class="date_interval invoice_date_period_wrapper"><span class="invoice_data_legend">'.esc_html__( 'Period','wprentals').' : </span>'.wpestate_convert_dateformat_reverse($booking_from_date).' '.esc_html__( 'to','wprentals').' '.wpestate_convert_dateformat_reverse($booking_to_date).'</span>
                            <span class="date_duration invoice_date_nights_wrapper"><span class="invoice_data_legend">'.wpestate_show_labels('no_of_nights',$rental_type,$booking_type).': </span>'.$booking_array['numberDays'].'</span>
                            <span class="date_duration invoice_date_guests_wrapper "><span class="invoice_data_legend">'.esc_html__( 'Guests','wprentals').': </span>'.$booking_guests.wpestate_booking_guest_explanations($bookid).'</span>';
                            if($booking_array['price_per_guest_from_one']==1){
                                print'<span class="date_duration invoice_date_price_guest_wrapper"><span class="invoice_data_legend">'.esc_html__( 'Price per Guest','wprentals').': </span>';
                                print trim($extra_price_per_guest);
                                print'</span>';
                            }else{
                                print '<span class="date_duration invoice_date_label_wrapper"><span class="invoice_data_legend">'.wpestate_show_labels('price_label',$rental_type,$booking_type).': </span>';

                                print trim($price_show);
                                if($booking_array['has_custom']){
                                    print ', '.esc_html__('has custom price','wprentals');
                                }


                                if($booking_array['cover_weekend']){
                                    print ', '.esc_html__('has weekend price of','wprentals').' '.$price_per_weekeend_show;
                                }

                                print'</span>';

                                print '</span>';

                                if($booking_array['has_custom']){
                                    print '<span class="invoice_data_legend">'.__('Price details:','wprentals').'</span>';
                                    foreach($booking_array['custom_price_array'] as $date=>$price){
                                        $day_price = wpestate_show_price_booking_for_invoice($price,$wpestate_currency,$wpestate_where_currency,1,1);
                                        print '<span class="price_custom_explained">'.__('on','wprentals').' '.wpestate_convert_dateformat_reverse(date("Y-m-d",$date)).' '.__('price is','wprentals').' '.$day_price.'</span>';
                                    }
                                }

                            }
                        print '
                        </div>

                        <div class="invoice_details">
                            <div class="invoice_row header_legend">
                               <span class="inv_legend">'.esc_html__( 'Cost','wprentals').'</span>
                               <span class="inv_data">  '.esc_html__( 'Price','wprentals').'</span>
                               <span class="inv_exp">   '.esc_html__( 'Detail','wprentals').'</span>
                            </div>';
                        $computed_total=0;

                        foreach($details as $detail){
                            print'<div class="invoice_row invoice_content">
                                    <span class="inv_legend">  '.$detail[0].'</span>
                                    <span class="inv_data">  '. wpestate_show_price_booking_for_invoice($detail[1],$wpestate_currency,$wpestate_where_currency,0,1).'</span>
                                    <span class="inv_exp">';
                                        if(trim($detail[0])==esc_html__('Security Deposit','wprentals') || trim($detail[0])==esc_html__('Security Depozit','wprentals')){
                                            esc_html_e('*refundable' ,'wprentals');
                                        }

                                        if(trim($detail[0])==esc_html__( 'Subtotal','wprentals')){
                                            if($booking_array['price_per_guest_from_one']==1){
                                                print  esc_html($extra_price_per_guest).' x '.$booking_array['count_days'].' '.wpestate_show_labels('nights',$rental_type,$booking_type).' x '.$booking_array['curent_guest_no'].' '.esc_html__( 'guests','wprentals');

                                                if($booking_array['price_per_guest_from_one']==1 && $booking_array['custom_period_quest']==1){
                                                    echo " - ".esc_html__("period with custom price per guest","wprentals");
                                                }


                                            }else{
                                                print esc_html($booking_array['numberDays']).' '.wpestate_show_labels('nights',$rental_type,$booking_type).' x ';
                                                if($booking_array['cover_weekend']){
                                                    print esc_html__('has weekend price of','wprentals').' '.$price_per_weekeend_show;
                                                }else{
                                                    if ( $booking_array['has_custom']==1  ){
                                                        print esc_html__( 'custom price','wprentals');
                                                    }else{
                                                        print  trim($price_show);
                                                    }
                                                }


                                            }

                                        }

                                        if(trim($detail[0])==esc_html__( 'Extra Guests','wprentals')){
                                            print esc_html($booking_array['numberDays']).' '.wpestate_show_labels('nights',$rental_type,$booking_type).' x '.$booking_array['extra_guests'].' '.esc_html__('extra guests','wprentals');
                                            if ( $booking_array['custom_period_quest']==1 ){
                                                echo  esc_html__(" , period with custom price per guest","wprentals");
                                            }
                                        }

                                        if(isset($detail[2])){
                                            print trim($detail[2]);
                                        }


                                    print'
                                    </span>
                                </div>';
                        }

                        print '
                            <div class="invoice_row invoice_total total_inv_span total_invoice_for_payment">
                               <span class="inv_legend"><strong>'.esc_html__( 'Total','wprentals').'</strong></span>
                               <span class="inv_data" id="total_amm" data-total="'.esc_attr($invoice_price).'">'.$total_price_show.'</span></br>

                               <span class="inv_legend invoice_reseration_fee_req">'.esc_html__( 'Reservation Fee Required','wprentals').':</span> <span class="inv_depozit depozit_show" data-value="'.esc_attr($depozit).'"> '.$depozit_show.'</span></br>
                               <span class="inv_legend invoice_balance_owed">'.esc_html__( 'Balance owed','wprentals').':</span> <span class="inv_depozit balance_show"  data-value="'.esc_attr($balance).'">'.$balance_show.'</span>
                           </div>
                       </div>';

                    $is_paypal_live= esc_html ( wprentals_get_option('wp_estate_enable_paypal','') );
                    $is_stripe_live= esc_html ( wprentals_get_option('wp_estate_enable_stripe','') );
                    $submission_curency_status  =   esc_html( wprentals_get_option('wp_estate_submission_curency','') );

                    if($is_full!=1){
                        if( $balance>0 ){
                            print '<div class="invoice_pay_status_note">'.__('You are paying only the deposit required to confirm the booking:','wprentals').' '.$depozit_show.'</div>';
                            print '<div class="invoice_pay_status_note">'.__('You will need to pay the remaining balance before the first day of your booked period!','wprentals').'</div>';

                            }

                    }else{
                        if( $balance>0 ){
                            $depozit_stripe =   $balance;
                            $depozit        =   $balance;
                            print '<div class="invoice_pay_status_note">'.__('You are paying the remaining balance of your invoice:','wprentals').' '.$balance_show.'</div><input type="hidden" id="is_full_pay" value="'.$balance.'">';
                        }
                    }


                    print '<span class="pay_notice_booking">'.esc_html__( 'Pay Deposit & Confirm Reservation','wprentals').'</span>';

                    global $wpestate_global_payments;

                    if($wpestate_global_payments->is_woo=='yes'){
                        $to_pay=$depozit;

                        if($is_full==1){
                            $to_pay=$balance;
                            $invoice_id=$invoice_id.'f';
                        }
                        $wpestate_global_payments->show_button_pay($property_id,$bookid,$invoice_id,$to_pay,1);

                    }else{
                        if ( $is_stripe_live=='yes'){
                                global $wpestate_global_payments;
                                $metadata=array(
                                        'booking_id'=>  $bookid,
                                        'invoice_id'=>  $invoice_id,
                                        'listing_id'=>  $property_id,
                                        'user_id'   =>  $userID,
                                        'pay_type'  =>  1,
                                        'message'   =>  esc_html__( 'Pay Deposit & Confirm Reservation','wprentals')
                                );

                                $wpestate_global_payments->stripe_payments->wpestate_show_stripe_form($depozit_stripe,$metadata);
                        }
                        if ( $is_paypal_live=='yes'){
                            print '<span id="paypal_booking" data-propid="'.esc_attr($property_id).'" data-deposit="'.esc_attr($depozit).'" data-bookid="'.esc_attr($bookid).'" data-invoiceid="'.esc_attr($invoice_id).'">'.esc_html__( 'Pay with Paypal','wprentals').'</span>';
                        }
                        $enable_direct_pay      =   esc_html ( wprentals_get_option('wp_estate_enable_direct_pay','') );

                        if ( $enable_direct_pay=='yes'){
                            print '<span id="direct_pay_booking" data-propid="'.esc_attr($property_id).'" data-bookid="'.esc_attr($bookid).'" data-invoiceid="'.esc_attr($invoice_id).'">'.esc_html__( 'Wire Transfer','wprentals').'</span>';
                        }
                    }



                  print'
                  </div>


            </div>';
        die();
    }
endif;



if( !function_exists('wpestate_randomStringStripe') ):
    function wpestate_randomStringStripe()
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randstring = '';
        for ($i = 0; $i < 10; $i++) {
            $randstring = $characters[rand(0, strlen($characters))];
        }
        return $randstring;
    }
endif;

////////////////////////////////////////////////////////////////////////////////
/// Ajax  delete invoice
////////////////////////////////////////////////////////////////////////////////


add_action('wp_ajax_wpestate_delete_invoice', 'wpestate_delete_invoice' );
if( !function_exists('wpestate_delete_invoice') ):
    function wpestate_delete_invoice(){
        //scheck
        check_ajax_referer( 'wprentals_bookings_actions_nonce', 'security' );
        $current_user   =   wp_get_current_user();
        $userID         =   $current_user->ID;

        if ( !is_user_logged_in() ) {
            exit('ko');
        }
        if($userID === 0 ){
            exit('out pls');
        }


        $userID         =   $current_user->ID;
        $invoice_id     =   intval($_POST['invoice_id']);
        $booking_id     =   intval($_POST['booking_id']);
        $user_id        =   wpse119881_get_author($invoice_id);


        // check if correct post types
        if( get_post_type($invoice_id)!='wpestate_invoice' || get_post_type($booking_id)!='wpestate_booking'){
          exit('you don\'t have the right to delete this by by');
        }

        //check if invoice belongs to user

        if($invoice_id!='' &&  $user_id == $userID ){
            //check if invoice belogns to booking
            $invoice_no         =   get_post_meta($booking_id, 'booking_invoice_no', true);
            if($invoice_id!=$invoice_no){
                exit('you don\'t have the right to delete this by by2');
            }else{
                update_post_meta($booking_id, 'booking_invoice_no', '');
                update_post_meta($booking_id, 'booking_status', 'request');
            }
            wp_delete_post($invoice_id);

        }else{
            exit ('you dont have the right to delete ');
        }



        die();
    }
endif;

////////////////////////////////////////////////////////////////////////////////
/// Ajax  delete booking
////////////////////////////////////////////////////////////////////////////////

add_action('wp_ajax_wpestate_delete_booking_request', 'wpestate_delete_booking_request' );
if( !function_exists('wpestate_delete_booking_request') ):
    function wpestate_delete_booking_request(){
        //scheck -should split
        check_ajax_referer( 'wprentals_booking_confirmed_actions_nonce', 'security' );
        $current_user = wp_get_current_user();
        $userID                         =   $current_user->ID;

        if ( !is_user_logged_in() ) {
            exit('ko');
        }

        if($userID === 0 ){
            exit('out pls');
        }

        $bookid      =   intval($_POST['booking_id']);

        // check if correct post types
        if(  get_post_type($bookid)!='wpestate_booking'){
            exit('you don\'t have the right to delete this by by');
        }

        $is_user                =   intval($_POST['isuser']);
        $invoice_id             =   get_post_meta($bookid, 'booking_invoice_no', 'true');
        $lisiting_id            =   get_post_meta($bookid, 'booking_id', true);
        $reservation_array      =   wpestate_get_booking_dates($lisiting_id);
        update_post_meta($lisiting_id, 'booking_dates', $reservation_array);




        $user_id           =   wpse119881_get_author($lisiting_id);
        $boooking_owner    =   wpse119881_get_author($bookid);
        $receiver          =   get_userdata($boooking_owner);
        $receiver_email    =   $receiver->user_email;
        $receiver_name     =   $receiver->user_login;

        if( ($user_id!=$userID) && ($boooking_owner!=$userID) ){
            exit('out pls w2');
        }


        $from             =   $current_user->ID;

        if($is_user==1){

            $prop_id    =   get_post_meta($bookid, 'booking_id', true);
            $to_id      =   wpse119881_get_author($prop_id);
            $to_userdata=   get_userdata($to_id);
            $to_email   =   $to_userdata->user_email;

            $args = array('receiver_email'=>$receiver_email,'receiver_name'=>$receiver_name);

            wpestate_send_booking_email('deletebookinguser',$to_email,$args);
            $subject        =   esc_html__( 'Request Cancelled','wprentals');
            $description    =   esc_html__( 'User ','wprentals').$receiver_name.esc_html__( ' cancelled his booking request','wprentals');
            wpestate_add_to_inbox($userID,$from,$to_id,$subject,$description,"isfirst");
        }else{
            wpestate_send_booking_email('deletebooking',$receiver_email);
            $subject        =   esc_html__( 'Request Denied','wprentals');
            $description    =   esc_html__( 'Your booking request was denied.','wprentals');
            wpestate_add_to_inbox($userID,$from,$boooking_owner,$subject,$description,"isfirst");
        }


        if($invoice_id!=''){
            wp_delete_post($invoice_id);
        }




        $booking_details =array(
                'booking_status'            => 'canceled',
                'booking_status_full'        => 'canceled',

        );

        $reservation_array      =   wpestate_get_booking_dates($lisiting_id);


        $fromd                      =   esc_html(get_post_meta($bookid, 'booking_from_date', true));
        $reservation_array          =   wpestate_get_booking_dates($lisiting_id);
        $wprentals_is_per_hour      =   wprentals_return_booking_type($lisiting_id);
        if($wprentals_is_per_hour==2){
             // this is per h
            unset($reservation_array[strtotime($fromd)]);
        }else{
            // this is per day
            foreach($reservation_array as $key=>$value){

                if ($value == $bookid){
                    unset($reservation_array[$key]);
                }

            }

        }

        update_post_meta($lisiting_id, 'booking_dates', $reservation_array);
        wp_delete_post($bookid);

        die();

    }

endif;


////////////////////////////////////////////////////////////////////////////////
/// Cancel own booking
////////////////////////////////////////////////////////////////////////////////


add_action('wp_ajax_wpestate_cancel_own_booking', 'wpestate_cancel_own_booking' );
if( !function_exists('wpestate_cancel_own_booking') ):
    function wpestate_cancel_own_booking(){
        //scheck
        check_ajax_referer( 'wprentals_booking_confirmed_actions_nonce', 'security' );
        $current_user = wp_get_current_user();
        $userID         =   $current_user->ID;

        if ( !is_user_logged_in() ) {
            exit('ko');
        }
        if($userID === 0 ){
            exit('out pls');
        }


        $from           =   $current_user->user_login;
        $bookid         =   intval($_POST['booking_id']);
        $listing_id     =   intval($_POST['listing_id']);


        if($listing_id==0 || $bookid==0 ){
            exit('buh');
        }

        // check if correct post types
        if( get_post_type($listing_id)!='estate_property' || get_post_type($bookid)!='wpestate_booking'){
          exit('you don\'t have the right to delete this by by');
        }

        // check if listing has the same owner
        $the_post= get_post( $listing_id);
        if( $current_user->ID != $the_post->post_author ) {
            exit('you don\'t have the right to delete this');
        }

        // check if booking belogins to approved listing
        $property_id         =   get_post_meta($bookid, 'booking_id', true);
        if($property_id!=$listing_id){
            exit('you don\'t have the right to delete this2');
        }

        // get invoice id of approved booking and listing
        $invoice_id     =   get_post_meta($bookid, 'booking_invoice_no', 'true');


        $user_id           =   wpse119881_get_author($bookid);
        $receiver          =   get_userdata($user_id);
        $receiver_email    =   $receiver->user_email;
        $receiver_name     =   $receiver->user_login;


        wpestate_send_booking_email('deletebookingconfirmed',$receiver_email);
        $to                 =   $userID;

        $subject    =esc_html__( 'Your reservation was canceled','wprentals');
        $description=esc_html__( 'Your reservation was canceled by property owner','wprentals');


        $booking_details =array(
                'booking_status'            => 'canceled',
                'booking_status_full'        => 'canceled',

        );



        $fromd                      =   esc_html(get_post_meta($bookid, 'booking_from_date', true));
        $reservation_array          =   wpestate_get_booking_dates($listing_id);
        $wprentals_is_per_hour      =   wprentals_return_booking_type($listing_id);
        if($wprentals_is_per_hour==2){
             // this is per h
            unset($reservation_array[strtotime($fromd)]);
        }else{
            // this is per day
            foreach($reservation_array as $key=>$value){

                if ($value == $bookid){
                    unset($reservation_array[$key]);
                }

            }

        }
        $booking_status     =   get_post_meta($bookid, 'booking_status', true);
        wp_delete_post($bookid);
        update_post_meta($listing_id, 'booking_dates', $reservation_array);



        if($invoice_id!=''){
            if($booking_status=='confirmed'){
                $status =  esc_html(get_post_meta($invoice_id, 'invoice_status', true));
                update_post_meta($invoice_id, 'invoice_status', $status.'/ booking canceled by user ');
            }else{
                wp_delete_post($invoice_id);
            }
        }


        die();

    }

endif;


////////////////////////////////////////////////////////////////////////////////
/// USER Cancel own booking
////////////////////////////////////////////////////////////////////////////////





if( !function_exists('wpestate_check_for_booked_time') ):
    function wpestate_check_for_booked_time($wpestate_book_from,$wpestate_book_to,$reservation_array,$listing_id){


        $wprentals_is_per_hour  =   wprentals_return_booking_type($listing_id);
        $from_date      =   new DateTime($wpestate_book_from);
        $from_date_unix =   $from_date->getTimestamp();

        $to_date                =   new DateTime($wpestate_book_to);
        $to_date_unix_check     =   $to_date->getTimestamp();


        $to_date_unix   =   $to_date->getTimestamp();



        // checking booking avalability
        while ($from_date_unix < $to_date_unix){
            if( array_key_exists($from_date_unix,$reservation_array ) ){
                print 'stop';
                die();
            }
            if($wprentals_is_per_hour==2){
                $from_date->modify('+1 hour');
            }else{
                $from_date->modify('tomorrow');
            }

            $from_date_unix =   $from_date->getTimestamp();
        }
    }
endif;


////////////////////////////////////////////////////////////////////////////////
/// Ajax  add invoice
////////////////////////////////////////////////////////////////////////////////



add_action('wp_ajax_wpestate_add_booking_invoice', 'wpestate_add_booking_invoice' );
if( !function_exists('wpestate_add_booking_invoice') ):
    function wpestate_add_booking_invoice(){

        check_ajax_referer('create_invoice_ajax_nonce','security');
        $price =(double) round ( floatval($_POST['price']),2 )  ;
        $current_user =     wp_get_current_user();
        $userID       =     $current_user->ID;
        if ( !is_user_logged_in() ) {
            exit('ko');
        }
        if($userID === 0 ){
            exit('out pls');
        }




        $is_confirmed            =   intval($_POST['is_confirmed']);
        $bookid                  =   intval($_POST['bookid']);
        $wpestate_book_from      =   get_post_meta($bookid, 'booking_from_date', true);
        $wpestate_book_to        =   get_post_meta($bookid, 'booking_to_date', true);
        $listing_id              =   get_post_meta($bookid, 'booking_id', true);

        $the_post= get_post( $listing_id);

        if( $current_user->ID != $the_post->post_author ) {
            exit('you don\'t have the right to see this');
        }

        // prepare
        $full_pay_invoice_id        =   0;
        $early_bird_percent         =   floatval(get_post_meta($listing_id, 'early_bird_percent', true));
        $early_bird_days            =   floatval(get_post_meta($listing_id, 'early_bird_days', true));
        $taxes_value                =   floatval(get_post_meta($listing_id, 'property_taxes', true));

        //check if period already reserverd
        $reservation_array  = get_post_meta($listing_id, 'booking_dates',true);
        if($reservation_array==''){
            $reservation_array = wpestate_get_booking_dates($listing_id);
        }

        wpestate_check_for_booked_time($wpestate_book_from,$wpestate_book_to,$reservation_array,$listing_id);
        // end check


        // we proceed with issuing the invoice
        $allowed_html   =   array();
        $details='';
        if(isset( $_POST['details'])){
            $details        =  wpestate_sanitize_array( $_POST['details'] );
        }
        $manual_expenses='';
        if(isset( $_POST['manual_expenses'])){
            $manual_expenses    = wpestate_sanitize_array( $_POST['manual_expenses']);
        }



        $billing_for    =   esc_html__( 'Reservation fee','wprentals');
        $type           =   esc_html__( 'One Time','wprentals');
        $pack_id        =   $bookid; // booking id

        $time           =   time();
        $date           =   date('Y-m-d H:i:s',$time);
        $user_id        =   wpse119881_get_author($bookid);
        $is_featured    =   '';
        $is_upgrade     =   '';
        $paypal_tax_id  =   '';



        // get the booking array
        $invoice_id          =   0;
        $booking_guests      =   get_post_meta($bookid, 'booking_guests', true);
        $extra_options       =   esc_html(get_post_meta($bookid, 'extra_options', true));
        $extra_options_array =   explode(',', $extra_options);
        $booking_array       =   wpestate_booking_price($booking_guests,$invoice_id, $listing_id, $wpestate_book_from, $wpestate_book_to,$bookid,$extra_options_array,$manual_expenses);
        // done



        $invoice_id                 =  wpestate_booking_insert_invoice($billing_for,$type,$pack_id,$date,$user_id,$is_featured,$is_upgrade,$paypal_tax_id,$details,$price);
        $submission_curency_status  = wpestate_curency_submission_pick();



        // update booking data
        update_post_meta($bookid, 'full_pay_invoice_id', $full_pay_invoice_id);
        update_post_meta($bookid, 'booking_taxes', $taxes_value);
        update_post_meta($bookid, 'early_bird_percent', $early_bird_percent);
        update_post_meta($bookid, 'early_bird_days', $early_bird_days);
        update_post_meta($bookid, 'security_deposit', $booking_array['security_deposit']);
        update_post_meta($bookid, 'booking_taxes', $booking_array['taxes']);
        update_post_meta($bookid, 'service_fee', $booking_array['service_fee']);
        update_post_meta($bookid, 'youearned', $booking_array['youearned']);
        update_post_meta($bookid, 'to_be_paid',$booking_array['deposit'] );
        update_post_meta($bookid, 'booking_status', 'waiting');
        update_post_meta($bookid, 'booking_invoice_no', $invoice_id);
        update_post_meta($bookid, 'total_price', $booking_array['total_price']);
        update_post_meta($bookid, 'balance'  , $booking_array['balance']);

        //update invoice data
        update_post_meta($invoice_id, 'booking_taxes', $taxes_value);
        update_post_meta($invoice_id, 'security_deposit', $booking_array['security_deposit']);
        update_post_meta($invoice_id, 'early_bird_percent', $early_bird_percent);
        update_post_meta($invoice_id, 'early_bird_days', $early_bird_days);
        update_post_meta($invoice_id, 'booking_taxes', $booking_array['taxes']);
        update_post_meta($invoice_id, 'service_fee', $booking_array['service_fee']);
        update_post_meta($invoice_id, 'youearned', $booking_array['youearned'] );
        update_post_meta($invoice_id, 'depozit_to_be_paid', $booking_array['deposit'] );
        update_post_meta($invoice_id, 'balance'  , $booking_array['balance']);
        update_post_meta($invoice_id, 'manual_expense',$manual_expenses);

        $cleaning_fee_per_day       =   floatval(get_post_meta($listing_id, 'cleaning_fee_per_day', true));
        $city_fee_per_day           =   floatval(get_post_meta($listing_id, 'city_fee_per_day', true));
        $city_fee_percent           =   floatval(get_post_meta($listing_id, 'city_fee_percent', true));

        update_post_meta($invoice_id, 'cleaning_fee_per_day',$cleaning_fee_per_day);
        update_post_meta($invoice_id, 'city_fee_per_day',$city_fee_per_day);
        update_post_meta($invoice_id, 'city_fee_percent',$city_fee_percent);




        $booking_details=array(
            'total_price'           =>  $booking_array['total_price'],
            'to_be_paid'            =>  $booking_array['deposit'],
            'youearned'             =>  $booking_array['youearned'],
            'full_pay_invoice_id'   =>  $full_pay_invoice_id,
            'service_fee'           =>  $booking_array['service_fee'],
            'booking_taxes'         =>  $booking_array['taxes'],
            'security_deposit'      =>  $booking_array['security_deposit'],
            'booking_status'        =>  'waiting',
            'booking_invoice_no'    =>  $invoice_id,
            'balance'               =>  $booking_array['balance']
        );
        if($is_confirmed==1){
            update_post_meta($bookid, 'booking_status', 'confirmed');
            $booking_detail['booking_status']='confirmed';
        }



        update_post_meta($invoice_id, 'custom_price_array',$booking_array['custom_price_array']);




        $invoice_details=array(
            "invoice_status"                =>  "issued",
            "purchase_date"                 =>  $date,
            "buyer_id"                      =>  $user_id,
            "item_price"                    =>  $booking_array['total_price'],
            "orignal_invoice_id"            =>  $invoice_id,
            "billing_for"                   =>  $billing_for,
            "type"                          =>  $type,
            "pack_id"                       =>  $pack_id,
            "date"                          =>  $date,
            "user_id"                       =>  $user_id,
            "is_featured"                   =>  $is_featured,
            "is_upgrade"                    =>  $is_upgrade,
            "paypal_tax_id"                 =>  $paypal_tax_id,
            "details"                       =>  $details,
            "price"                         =>  $price,
            "to_be_paid"                    =>  $booking_array['deposit'],
            "submission_curency_status"     =>  $submission_curency_status,
            "bookid"                        =>  $bookid,
            "author_id"                     =>  $userID,
            "youearned"                     =>  $booking_array['youearned'],
            "service_fee"                   =>  $booking_array['service_fee'],
            "booking_taxes"                 =>  $booking_array['taxes'],
            "security_deposit"              =>  $booking_array['security_deposit'],
            "renting_details"               =>  $details,
            "custom_price_array"            =>  $booking_array['custom_price_array'],
            "balance"                       =>  $booking_array['balance'],
            "cleaning_fee_per_day"          =>  $cleaning_fee_per_day,
            "city_fee_per_day"              =>  $city_fee_per_day,
            "city_fee_percent"              =>  $city_fee_percent,
        );

        if($booking_array['balance'] > 0){
            update_post_meta($bookid, 'booking_status_full','waiting' );
            update_post_meta($invoice_id, 'invoice_status_full','waiting');
            $booking_details['booking_status_full'] =   'waiting';
            $booking_details['booking_invoice_no']  =   $invoice_id;
            $invoice_details['invoice_status_full'] =   'waiting';
        }

        $wp_estate_book_down            =   floatval( get_post_meta($invoice_id, 'invoice_percent', true) );
        $invoice_price                  =   floatval( get_post_meta($invoice_id, 'item_price', true)) ;

        if($wp_estate_book_down==100 ){
           $booking_details['booking_invoice_no']  =   $invoice_id;
        }



        if($is_confirmed==1){
            update_post_meta($bookid, 'booking_status', 'confirmed');
            $booking_details['booking_status']='confirmed';

            update_post_meta($invoice_id, 'invoice_status', 'confirmed');
            update_post_meta($invoice_id, 'depozit_paid', 0);
            update_post_meta($invoice_id, 'depozit_to_be_paid', 0);
            update_post_meta($invoice_id, 'balance'  , $booking_array['balance']);
            $invoice_details['invoice_status']  =   'confirmed';
            $invoice_details['to_be_paid']      =   0;
            $invoice_details['balance']         =   $booking_array['balance'];
        }






        if($is_confirmed==1){
            $curent_listng_id   =   get_post_meta($bookid,'booking_id',true);
            $reservation_array  =   wpestate_get_booking_dates($curent_listng_id);
            update_post_meta($curent_listng_id, 'booking_dates', $reservation_array);

        }


        // send notification emails
        if($is_confirmed!==1){
            $receiver          =   get_userdata($user_id);
            $receiver_email    =   $receiver->user_email;
            $receiver_login    =   $receiver->user_login;
            $from               =   $current_user->user_login;
            $to                 =   $user_id;
            $subject            =   esc_html__( 'New Invoice','wprentals');
            $description        =   esc_html__( 'A new invoice was generated for your booking request','wprentals');

            wpestate_add_to_inbox($userID,$userID,$to,$subject,$description,1);
            wpestate_send_booking_email('newinvoice',$receiver_email);
        }else{
            //direct confirmation emails
            $user_email         =   $current_user->user_email;

            $receiver          =   get_userdata($user_id);
            $receiver_email    =   $receiver->user_email;
            $receiver_login    =   $receiver->user_login;


            //$receiver_id    =   wpsestate_get_author($booking_id);

            $receiver_email =   get_the_author_meta('user_email', $user_id);
            $receiver_name  =   get_the_author_meta('user_login', $user_id);
            wpestate_send_booking_email("bookingconfirmeduser",$receiver_email);// for user
            wpestate_send_booking_email("bookingconfirmed_nodeposit",$user_email);// for owner
            // add messages to inbox

            $subject=esc_html__( 'Booking Confirmation','wprentals');
            $description=esc_html__( 'A booking was confirmed','wprentals');
            wpestate_add_to_inbox($userID,$receiver_name,$userID,$subject,$description);

            $subject=esc_html__( 'Booking Confirmed','wprentals');
            $description=esc_html__( 'A booking was confirmed','wprentals');
            wpestate_add_to_inbox($receiver_id,$username,$receiver_id,$subject,$description);

        }




        print intval($invoice_id);
        die();

    }
endif;









if( !function_exists('wpestate_add_booking_invoice_no_deposit') ):
    function wpestate_add_booking_invoice_no_deposit($bookid,$details,$price){


        $pack_id        =   $bookid; // booking id

        $billing_for    =   'Reservation fee';
        $type           =   esc_html__( 'One Time','wprentals');

        $date           =   time();
        $user_id        =   wpse119881_get_author($bookid);

        $is_featured    =   '';
        $is_upgrade     =   '';
        $paypal_tax_id  =   '';


        $invoice_id =  wpestate_booking_insert_invoice($billing_for,$type,$pack_id,$date,$user_id,$is_featured,$is_upgrade,$paypal_tax_id,$details,$price);


        update_post_meta($bookid, 'booking_invoice_no', $invoice_id);

        $receiver          =   get_userdata($user_id);
        $receiver_email    =   $receiver->user_email;
        $receiver_login    =   $receiver->user_login;

        $current_user = wp_get_current_user();
        $userID             =   $current_user->ID;
        $from               =   $current_user->user_login;
        $to                 =   $user_id;
        $subject=esc_html__( 'New Invoice','wprentals');
        $description=esc_html__( 'A new invoice was generated for your booking request','wprentals');
        wpestate_add_to_inbox($userID,$userID,$to,$subject,$description,1);



        print intval($invoice_id);
        die();

    }
endif;
////////////////////////////////////////////////////////////////////////////////
/// Ajax  direct confirmation
////////////////////////////////////////////////////////////////////////////////



add_action('wp_ajax_wpestate_direct_confirmation', 'wpestate_direct_confirmation' );
if( !function_exists('wpestate_direct_confirmation') ):
    function wpestate_direct_confirmation(){
        $current_user = wp_get_current_user();
        $userID                         =   $current_user->ID;


        if ( !is_user_logged_in() ) {
            exit('ko');
        }
        if($userID === 0 ){
            exit('out pls');
        }



        check_ajax_referer('create_invoice_ajax_nonce','security');
        $allowed_html=array();
        $bookid         =   $booking_id = intval($_POST['bookid']);

        $lisiting_id    =   $listing_id            =   get_post_meta($bookid, 'booking_id', true);
        $wpestate_book_from      =   get_post_meta($bookid, 'booking_from_date', true);
        $wpestate_book_to        =   get_post_meta($bookid, 'booking_to_date', true);

        $the_post= get_post( $lisiting_id);
        if( $current_user->ID != $the_post->post_author ) {
            exit('you don\'t have the right to see this');
        }

        // double book change
        $reservation_array  = get_post_meta($listing_id, 'booking_dates',true);
        if($reservation_array==''){
            $reservation_array = wpestate_get_booking_dates($listing_id);
        }

        $wpestate_book_from  = wpestate_convert_dateformat($wpestate_book_from);
        $wpestate_book_to    = wpestate_convert_dateformat($wpestate_book_to);

        $from_date      =   new DateTime($wpestate_book_from);
        $from_date_unix =   $from_date->getTimestamp();

        $to_date                =   new DateTime($wpestate_book_to);
        $to_date_unix_check     =   $to_date->getTimestamp();


        $to_date_unix   =   $to_date->getTimestamp();



        // checking booking avalability
        while ($from_date_unix < $to_date_unix){

            if( array_key_exists($from_date_unix,$reservation_array ) ){
                print'doublebook';
                die();
            }
            $from_date->modify('tomorrow');
            $from_date_unix =   $from_date->getTimestamp();
        }



        if( isset($_POST['details']) ){
            $details  =     ( $_POST['details']);
        }else{
            $details  =    '';
        }

        $price          =   floatval($_POST['price']);
        $billing_for    =   esc_html__( 'Reservation fee','wprentals');
        $type           =   esc_html__( 'One Time','wprentals');
        $pack_id        =   $bookid; // booking id
        $date           =   time();
        $user_id        =   wpse119881_get_author($bookid);

        $is_featured    =   '';
        $is_upgrade     =   '';
        $paypal_tax_id  =   '';



        $receiver          =   get_userdata($user_id);
        $receiver_email    =   $receiver->user_email;
        $receiver_login    =   $receiver->user_login;


        $userID             =   $current_user->ID;
        $user_email         =   $current_user->user_email;
        $username           =   $current_user->user_login;
        $from               =   $current_user->user_login;
        $to                 =   $user_id;



        $invoice_id =  wpestate_booking_insert_invoice($billing_for,$type,$pack_id,$date,$user_id,$is_featured,$is_upgrade,$paypal_tax_id,$details,$price);

        // get the booking array
        $listing_id=0;
        $booking_guests      =   get_post_meta($booking_id, 'booking_guests', true);
        $extra_options       =   esc_html(get_post_meta($booking_id, 'extra_options', true));
        $extra_options_array =   explode(',', $extra_options);
        $booking_array       =   wpestate_booking_price($booking_guests,$invoice_id, $lisiting_id, $wpestate_book_from, $wpestate_book_to,$bookid,$extra_options_array);


        // confirm booking
        update_post_meta($booking_id, 'booking_status', 'confirmed');
        update_post_meta($booking_id, 'booking_invoice_no', $invoice_id);
        update_post_meta($bookid, 'balance'  , $booking_array['balance']);

        $curent_listng_id   =   get_post_meta($booking_id,'booking_id',true);
        $reservation_array  =   wpestate_get_booking_dates($curent_listng_id);


        update_post_meta($curent_listng_id, 'booking_dates', $reservation_array);


        // set invoice to paid
        update_post_meta($invoice_id, 'invoice_status', 'confirmed');
        update_post_meta($invoice_id, 'depozit_paid', 0);
        update_post_meta($invoice_id, 'balance'  , $booking_array['balance']);


        /////////////////////////////////////////////////////////////////////////////
        // send confirmation emails
        /////////////////////////////////////////////////////////////////////////////



        $receiver_id    =   wpsestate_get_author($bookid);
        $receiver_email =   get_the_author_meta('user_email', $receiver_id);
        $receiver_name  =   get_the_author_meta('user_login', $receiver_id);
        wpestate_send_booking_email("bookingconfirmeduser",$receiver_email);// for user
        wpestate_send_booking_email("bookingconfirmed_nodeposit",$user_email);// for owner
        // add messages to inbox

        $subject=esc_html__( 'Booking Confirmation','wprentals');
        $description=esc_html__( 'A booking was confirmed','wprentals');
        wpestate_add_to_inbox($userID,$receiver_name,$userID,$subject,$description);

        $subject=esc_html__( 'Booking Confirmed','wprentals');
        $description=esc_html__( 'A booking was confirmed','wprentals');
        wpestate_add_to_inbox($receiver_id,$username,$receiver_id,$subject,$description);


        print intval($invoice_id);
        exit();

    }
endif;








 ////////////////////////////////////////////////////////////////////////////////
/// Ajax  direct confirmation
////////////////////////////////////////////////////////////////////////////////
if( !function_exists('wpestate_booking_insert_invoice') ):
    function wpestate_booking_insert_invoice($billing_for,$type,$pack_id,$date,$user_id,$is_featured,$is_upgrade,$paypal_tax_id,$details,$price,$author_id=''){

        $price =(double) round ( floatval($price),2 )  ;

        $post = array(
                   'post_title'     => 'Invoice ',
                   'post_status'    => 'publish',
                   'post_type'      => 'wpestate_invoice',

                );

        if($author_id!=''){
           $post[ 'post_author']       = intval($author_id);
        }

        $post_id =  wp_insert_post($post );



        update_post_meta($post_id, 'invoice_type', $billing_for);
        update_post_meta($post_id, 'biling_type', $type);
        update_post_meta($post_id, 'item_id', $pack_id);

        update_post_meta($post_id, 'item_price',$price);
        update_post_meta($post_id, 'purchase_date', $date);
        update_post_meta($post_id, 'buyer_id', $user_id);
        update_post_meta($post_id, 'txn_id', '');
        update_post_meta($post_id, 'renting_details', $details);
        update_post_meta($post_id, 'invoice_status', 'issued');
        update_post_meta($post_id, 'invoice_percent',  floatval ( wprentals_get_option('wp_estate_book_down', '') ));
        update_post_meta($post_id, 'invoice_percent_fixed_fee',  floatval ( wprentals_get_option('wp_estate_book_down_fixed_fee', '') ));

        $service_fee_fixed_fee  =   floatval ( wprentals_get_option('wp_estate_service_fee_fixed_fee','') );
        $service_fee            =   floatval ( wprentals_get_option('wp_estate_service_fee','') );
        update_post_meta($post_id, 'service_fee_fixed_fee', $service_fee_fixed_fee );
        update_post_meta($post_id, 'service_fee', $service_fee);

        $property_id    = get_post_meta($pack_id, 'booking_id',true);

        update_post_meta($post_id, 'for_property', $property_id);
        update_post_meta($post_id, 'rented_by', get_post_field( 'post_author', $pack_id ));


        update_post_meta($post_id, 'prop_taxed', floatval(get_post_meta($property_id, 'property_taxes', true)) );

        //$submission_curency_status = esc_html( wprentals_get_option('wp_estate_submission_curency','') );
        $submission_curency_status = wpestate_curency_submission_pick();
        update_post_meta($post_id, 'invoice_currency', $submission_curency_status);

        //todo@@ set custom default discounted default price
        $timeshare_session_info = get_timeshare_session_info();
        $booked_days_count = $timeshare_session_info[$user_id][$pack_id]['booked_days_count'];
        $price_per_day_after_discount = $price / $booked_days_count;

        update_post_meta($post_id, 'default_price', $price_per_day_after_discount);

        $week_price = floatval   ( get_post_meta($property_id, 'property_price_per_week', true) );
        update_post_meta($post_id, 'week_price', $week_price);

        $month_price = floatval   ( get_post_meta($property_id, 'property_price_per_month', true) );
        update_post_meta($post_id, 'month_price', $month_price);

        $cleaning_fee = floatval   ( get_post_meta($property_id, 'cleaning_fee', true) );
        update_post_meta($post_id, 'cleaning_fee', $cleaning_fee);

        $city_fee = floatval   ( get_post_meta($property_id, 'city_fee', true) );
        update_post_meta($post_id, 'city_fee', $city_fee);



        $my_post = array(
           'ID'           => $post_id,
           'post_title'	=> 'Invoice '.$post_id,
        );

        wp_update_post( $my_post );

        return $post_id;

    }
endif;
////////////////////////////////////////////////////////////////////////////////
/// Ajax  create invoice form
////////////////////////////////////////////////////////////////////////////////


add_action('wp_ajax_wpestate_create_invoice_form', 'wpestate_create_invoice_form' );
if( !function_exists('wpestate_create_invoice_form') ):
    function wpestate_create_invoice_form(){
        check_ajax_referer( 'wprentals_bookings_actions_nonce', 'security' );

        $current_user   =   wp_get_current_user();
        $userID         =   $current_user->ID;


        if ( !is_user_logged_in() ) {
            exit('ko');
        }
        if($userID === 0 ){
            exit('out pls');
        }


        $invoice_id=0;
        $bookid              =      intval($_POST['bookid']);
        $lisiting_id         =      get_post_meta($bookid, 'booking_id', true);
        $the_post            =      get_post( $lisiting_id);
        $booking_type        =      wprentals_return_booking_type($lisiting_id);
        $rental_type         =      wprentals_get_option('wp_estate_item_rental_type');

        if( $current_user->ID != $the_post->post_author ) {
            exit('you don\'t have the right to see this');
        }



        $booking_from_date   =   esc_html(get_post_meta($bookid, 'booking_from_date', true));
        $property_id         =   esc_html(get_post_meta($bookid, 'booking_id', true));
        $booking_to_date     =   esc_html(get_post_meta($bookid, 'booking_to_date', true));
        $extra_options       =   esc_html(get_post_meta($bookid, 'extra_options', true));
        $extra_options_array =   explode(',', $extra_options);
        $booking_guests      =   get_post_meta($bookid, 'booking_guests', true);
        $booking_array       =   wpestate_booking_price($booking_guests,$invoice_id,$property_id, $booking_from_date, $booking_to_date,$bookid,$extra_options_array);
        $wpestate_where_currency      =   esc_html( wprentals_get_option('wp_estate_where_currency_symbol', '') );
        $wpestate_currency   =   esc_html( wprentals_get_option('wp_estate_submission_curency', '') );
        $wpestate_currency   =   wpestate_curency_submission_pick();
        $include_expeses     =   esc_html ( wprentals_get_option('wp_estate_include_expenses','') );
        $security_depozit    =   floatval(get_post_meta($property_id, 'security_deposit', true));
        $price_per_weekeend  =   floatval(get_post_meta($property_id, 'price_per_weekeend', true));




        $total_price_comp = floatval($booking_array['total_price']);

        if($include_expeses=='yes'){
            $total_price_comp2  =   $total_price_comp;
        }else{
            $total_price_comp2  =   $booking_array['total_price'] - $booking_array['city_fee'] - $booking_array['cleaning_fee'];
        }



        $wp_estate_book_down                      =   floatval( wprentals_get_option('wp_estate_book_down','') );
        $wp_estate_book_down_fixed_fee            =   floatval( wprentals_get_option('wp_estate_book_down_fixed_fee','') );

        $depozit                    =   floatval( wpestate_calculate_deposit($wp_estate_book_down,$wp_estate_book_down_fixed_fee,$total_price_comp2));
        $balance                    =   $total_price_comp - $depozit;
        $price_show                 =   wpestate_show_price_booking_for_invoice($booking_array['default_price'],$wpestate_currency,$wpestate_where_currency,0,1);
        $price_per_weekeend_show    =   wpestate_show_price_booking_for_invoice($price_per_weekeend,$wpestate_currency,$wpestate_where_currency,0,1);
        $total_price_show           =   wpestate_show_price_booking_for_invoice($total_price_comp,$wpestate_currency,$wpestate_where_currency,0,1);
        $security_depozit_show      =   wpestate_show_price_booking_for_invoice($security_depozit,$wpestate_currency,$wpestate_where_currency,1,1);
        $deposit_show               =   wpestate_show_price_booking_for_invoice($depozit,$wpestate_currency,$wpestate_where_currency,0,1);
        $balance_show               =   wpestate_show_price_booking_for_invoice($balance,$wpestate_currency,$wpestate_where_currency,0,1);
        $city_fee_show              =   wpestate_show_price_booking_for_invoice($booking_array['city_fee'],$wpestate_currency,$wpestate_where_currency,1,1);
        $cleaning_fee_show          =   wpestate_show_price_booking_for_invoice($booking_array['cleaning_fee'],$wpestate_currency,$wpestate_where_currency,1,1);
        $inter_price_show           =   wpestate_show_price_booking_for_invoice($booking_array['inter_price'],$wpestate_currency,$wpestate_where_currency,1,1);
        $total_guest                =   wpestate_show_price_booking_for_invoice($booking_array['total_extra_price_per_guest'],$wpestate_currency,$wpestate_where_currency,1,1);
        $guest_price                =   wpestate_show_price_booking_for_invoice($booking_array['extra_price_per_guest'],$wpestate_currency,$wpestate_where_currency,1,1);
        $extra_price_per_guest      =   wpestate_show_price_booking($booking_array['extra_price_per_guest'],$wpestate_currency,$wpestate_where_currency,1);
        $early_bird_discount_show   =   wpestate_show_price_booking_for_invoice(  $booking_array['early_bird_discount'],$wpestate_currency,$wpestate_where_currency,1,1);


        if(trim($deposit_show)==''){
            $deposit_show=0;
        }




            print '
            <div class="create_invoice_form">
                <h3>'.esc_html__( 'Create Invoice','wprentals').'</h3>

                <div class="invoice_table">
                    <div class="invoice_data">
                        <div style="display:none" id="property_details_invoice" data-taxes_value="'.floatval(get_post_meta($property_id, 'property_taxes', true)).'" data-earlyb="'.floatval(get_post_meta($property_id, 'early_bird_percent', true)).'"></div>
                        <span class="date_interval invoice_date_period_wrapper "><span class="invoice_data_legend">'.esc_html__( 'Period','wprentals').' : </span>'.wpestate_convert_dateformat_reverse($booking_from_date).' '.esc_html__( 'to','wprentals').' '.wpestate_convert_dateformat_reverse($booking_to_date).'</span>
                        <span class="date_duration invoice_date_nights_wrapper"><span class="invoice_data_legend">'.wpestate_show_labels('no_of_nights',$rental_type,$booking_type).': </span>'.$booking_array['count_days'].'</span>
                        <span class="date_duration invoice_date_guest_wrapper"><span class="invoice_data_legend">'.esc_html__( 'No of guests','wprentals').': </span>'.$booking_guests.wpestate_booking_guest_explanations($bookid).'</span>';
                        if($booking_array['price_per_guest_from_one']==1){
                            print'
                            <span class="date_duration invoice_date_price_guest_wrapper"><span class="invoice_data_legend">'.esc_html__( 'Price per Guest','wprentals').': </span>';
                                print trim($extra_price_per_guest);
                            print'</span>';
                        }else{
                            print'
                            <span class="date_duration invoice_date_label_wrapper "><span class="invoice_data_legend">'.wpestate_show_labels('price_label',$rental_type,$booking_type).': </span>';
                            print __('default price:','wprentals').' '.trim($price_show);
                            if($booking_array['has_custom']){
                                print ', '.esc_html__('has custom price','wprentals');

                            }
                            if($booking_array['cover_weekend']){
                                print ', '.esc_html__('has weekend price of','wprentals').' '.$price_per_weekeend_show;
                            }
                            print'</span>';
                            if($booking_array['has_custom']){
                                print '<span class="invoice_data_legend">'.__('Price details:','wprentals').'</span>';
                                foreach($booking_array['custom_price_array'] as $date=>$price){
                                    $day_price = wpestate_show_price_booking_for_invoice($price,$wpestate_currency,$wpestate_where_currency,1,1);
                                    print '<span class="price_custom_explained">'.__('on','wprentals').' '.wpestate_convert_dateformat_reverse(date("Y-m-d",$date)).' '.__('price is','wprentals').' '.$day_price.'</span>';
                                }
                            }



                        }
                        $post_author_id = get_post_field( 'post_author', $bookid );
                        $first_name             =   get_the_author_meta( 'first_name' , $post_author_id );
                        $last_name              =   get_the_author_meta( 'last_name' , $post_author_id );
                        $user_email             =   get_the_author_meta( 'user_email' , $post_author_id );
                        $user_mobile            =   get_the_author_meta( 'mobile' , $post_author_id );


                        print'<span class="date_duration invoice_date_property_name_wrapper"><span class="invoice_data_legend">'.esc_html__('Property','wprentals').': </span><a href="'.esc_url(get_permalink($property_id)).'" target="_blank">'.esc_html(get_the_title($property_id)).'</a></span>';
                        print'<span class="date_duration invoice_date_renter_name_wrapper"><span class="invoice_data_legend">'.esc_html__('Rented by','wprentals').': </span>'.$first_name.' '.$last_name.'</span>';
                        print'<span class="date_duration invoice_date_renter_email_wrapper"><span class="invoice_data_legend">'.esc_html__('Email','wprentals').': </span>'.$user_email.'</span>';
                        print'<span class="date_duration invoice_date_renter_phone_wrapper"><span class="invoice_data_legend">'.esc_html__('Phone','wprentals').': </span>'.$user_mobile.'</span>';

                    print '
                    </div>
                    <div class="invoice_details">
                        <div class="invoice_row header_legend">
                           <span class="inv_legend">    '.esc_html__( 'Cost','wprentals').'</span>
                           <span class="inv_data">      '.esc_html__( 'Price','wprentals').'</span>
                           <span class="inv_exp">       '.esc_html__( 'Detail','wprentals').' </span>
                        </div>
                        <div class="invoice_row invoice_content">
                            <span class="inv_legend">   '.esc_html__( 'Subtotal','wprentals').'</span>
                            <span class="inv_data">   '.$inter_price_show.'</span>';

                            if($booking_array['price_per_guest_from_one']==1){
                                print  trim($extra_price_per_guest).' x '.$booking_array['count_days'].' '.wpestate_show_labels('nights',$rental_type,$booking_type).' x '.$booking_array['curent_guest_no'].' '.esc_html__( 'guests','wprentals');
                            } else{

                                if($booking_array['cover_weekend']){
                                    $new_price_to_show=esc_html__('has weekend price of','wprentals').' '.$price_per_weekeend_show;
                                }else{
                                    if($booking_array['has_custom']){
                                        $new_price_to_show=esc_html__("custom price","wprentals");
                                    }else{
                                        $new_price_to_show=$price_show.' '.wpestate_show_labels('per night',$rental_type);
                                    }
                                }



                                if($booking_array['numberDays']==1){
                                    print ' <span class="inv_exp">   ('.$booking_array['numberDays'].' '.wpestate_show_labels('night',$rental_type,$booking_type).' | '.$new_price_to_show.') </span>';
                                }else{
                                    print ' <span class="inv_exp">   ('.$booking_array['numberDays'].' '.wpestate_show_labels('nights',$rental_type,$booking_type).' | '.$new_price_to_show.') </span>';
                                }
                            }

                            if($booking_array['price_per_guest_from_one']==1 && $booking_array['custom_period_quest']==1){
                                esc_html_e(" period with custom price per guest","wprentals");
                            }

                            print'

                            </div>';



                            if($booking_array['has_guest_overload']!=0 && $booking_array['total_extra_price_per_guest']!=0){
                                print'
                                <div class="invoice_row invoice_content">
                                    <span class="inv_legend">   '.esc_html__( 'Extra Guests','wprentals').'</span>
                                    <span class="inv_data" id="extra-guests" data-extra-guests="'.esc_attr($booking_array['total_extra_price_per_guest']).'">  '.$total_guest.'</span>
                                    <span class="inv_exp">   ('.$booking_array['numberDays'].' '.wpestate_show_labels('nights',$rental_type,$booking_type).' | '.$booking_array['extra_guests'].' '.esc_html__('extra guests','wprentals').' )';

                                    if ( $booking_array['custom_period_quest']==1 ){
                                      echo  esc_html__(" period with custom price per guest","wprentals");
                                    }

                                    print'</span>

                                </div>';
                            }

                            if($booking_array['cleaning_fee']!=0 && $booking_array['cleaning_fee']!=''){
                               print'
                               <div class="invoice_row invoice_content">
                                   <span class="inv_legend">   '.esc_html__( 'Cleaning fee','wprentals').'</span>
                                   <span class="inv_data" id="cleaning-fee" data-cleaning-fee="'.esc_attr($booking_array['cleaning_fee']).'">  '.$cleaning_fee_show.'</span>
                               </div>';
                            }

                            if($booking_array['city_fee']!=0 && $booking_array['city_fee']!=''){
                               print'
                               <div class="invoice_row invoice_content">
                                   <span class="inv_legend">   '.esc_html__( 'City fee','wprentals').'</span>
                                   <span class="inv_data" id="city-fee" data-city-fee="'.esc_attr($booking_array['city_fee']).'">  '.$city_fee_show.'</span>
                               </div>';
                            }




                            $extra_pay_options          =      ( get_post_meta($property_id,  'extra_pay_options', true) );
                            if($extra_options!=''){
                                $extra_options_array    =   explode(',',$extra_options);
                            }


                            $options_array=array(
                                0   =>  esc_html__('Single Fee','wprentals'),
                                1   =>  ucfirst( wpestate_show_labels('per_night',$rental_type,$booking_type) ),
                                2   =>  esc_html__('Per Guest','wprentals'),
                                3   =>  ucfirst( wpestate_show_labels('per_night',$rental_type,$booking_type)).' '.esc_html__('per Guest','wprentals')
                            );

                            foreach ($extra_options_array as $key=>$value){
                                if(isset($extra_pay_options[$value][0])){
                                    $extra_option_value                 =   wpestate_calculate_extra_options_value($booking_array['count_days'],$booking_guests,$extra_pay_options[$value][2],$extra_pay_options[$value][1]);
                                    $extra_option_value_show            =   wpestate_show_price_booking_for_invoice($extra_option_value,$wpestate_currency,$wpestate_where_currency,1,1);
                                    $extra_option_value_show_single     =   wpestate_show_price_booking_for_invoice($extra_pay_options[$value][1],$wpestate_currency,$wpestate_where_currency,0,1);

                                    print'
                                    <div class="invoice_row invoice_content">
                                        <span class="inv_legend">   '.$extra_pay_options[$value][0].'</span>
                                        <span class="inv_data invoice_default_extra" data-value="'.esc_attr($extra_option_value).'" >  '.$extra_option_value_show.'</span>
                                        <span class="inv_data inv_data_exp">'.$extra_option_value_show_single.' '.$options_array_explanations[$extra_pay_options[$value][2]].'</span>
                                    </div>';
                                }
                            }

                            if($security_depozit!=0){
                                print'
                                <div class="invoice_row invoice_content">
                                    <span class="inv_legend">   '.__('Security Deposit','wprentals').'</span>
                                    <span id="security_depozit_row" data-val="'.esc_attr($security_depozit).'" class="inv_data">  '.$security_depozit_show.'</span>
                                    <span  class="inv_data">'.__('*refundable','wprentals').'</span>
                                </div>';
                            }


                            if( $booking_array['early_bird_discount'] >0){
                                print'
                                <div class="invoice_row invoice_content">
                                    <span class="inv_legend">   '.__('Early Bird Discount','wprentals').'</span>
                                    <span id="erarly_bird_row" data-val="'.esc_attr($booking_array['early_bird_discount']).'"  class="inv_data">  '.$early_bird_discount_show.'</span>
                                    <span class="inv_data"></span>
                                </div>';
                            }


                            print'
                            <div class="invoice_row invoice_total invoice_total_generate_invoice">
                                <div style="display:none;" id="inter_price" data-value="'.esc_attr($booking_array ['inter_price']).'"></div>
                                <span class="inv_legend"><strong>'.esc_html__( 'Guest Pays','wprentals').'</strong></span>
                                <span class="inv_data" id="total_amm" data-total="'.esc_attr($total_price_comp).'">'.$total_price_show.'</span>

                                <span class="total_inv_span"><span class="inv_legend invoice_reseration_fee_req"> '.esc_html__( 'Reservation Fee Required','wprentals').':</span> <span id="inv_depozit" data-value="'.esc_attr($depozit).'">'.$deposit_show.'</span>
                                <div style="width:100%"></div>
                                <span class="inv_legend invoice_balance_owed">'.esc_html__( 'Balance Owed','wprentals').':</span> <span id="inv_balance" data-val="'.esc_attr($balance).'">'.$balance_show.'</span>
                            </div>';


                            $taxes_show          =      wpestate_show_price_booking_for_invoice($booking_array ['taxes'],$wpestate_currency,$wpestate_where_currency,0,1);
                            $you_earn_show       =      wpestate_show_price_booking_for_invoice($booking_array ['youearned'],$wpestate_currency,$wpestate_where_currency,0,1);
                            $service_fee_show    =      wpestate_show_price_booking_for_invoice($booking_array ['service_fee'],$wpestate_currency,$wpestate_where_currency,0,1);
                            print'
                            <div class="invoice_row invoice_totalx invoice_total_generate_invoice">
                                <span class="inv_legend"><strong>'.esc_html__( 'You Earn','wprentals').'</strong></span>
                                <span class="inv_data" id="youearned" data-youearned="'.esc_attr($booking_array ['youearned']).'"><strong>'.$you_earn_show.'</strong></span>


                                <div class="invoice_explantions">'.esc_html__('we deduct security deposit, city fees, cleaning fees and website service fee','wprentals').'</div>

                                <span class="total_inv_span">
                                    <span class="inv_legend invoice_legend_service_fee">'.esc_html__( 'Service Fee','wprentals').':</span>
                                    <span id="inv_service_fee" class="invoice_legend_service_fee_value" data-value="'.esc_attr($booking_array ['service_fee']).'"  data-value-fixed-hidden="'.  floatval ( wprentals_get_option('wp_estate_service_fee_fixed_fee','') ).'">'.$service_fee_show.'</span>

                                    <div style="width:100%"></div>

                                    <span class="inv_legend invoice_legend_taxes">'.esc_html__( 'Taxes','wprentals').':</span>
                                    <span id="inv_taxes" class="invoice_legend_taxes_value" data-value="'.esc_attr($booking_array ['taxes']).'" >'.$taxes_show.'</span>
                                </span>

                                <div class="invoice_explantions">'.esc_html__('*taxes are included in your earnings and you are responsible for paying these taxes','wprentals').'</div>
                            </div>';

                        print'</div>   ';


                    $book_down              =   floatval( wprentals_get_option('wp_estate_book_down','') );
                    $book_down_fixed_fee    =   floatval( wprentals_get_option('wp_estate_book_down_fixed_fee','') );

                    if($book_down != 0 || $book_down_fixed_fee!=0){
                        $label          =   esc_html__( 'Send Invoice','wprentals');
                        $is_confirmed   =   0;
                    }else{
                        $label  =   esc_html__( 'Confirm Booking - No Reservation Fee Required','wprentals');
                        $is_confirmed   =   1;

                    }

                    print '<div class="action1_booking" id="invoice_submit" data-is_confirmed="'.esc_attr($is_confirmed).'" data-bookid="'.esc_attr($bookid).'">'.$label.'</div>';

                    print '</div>';


                    print '
                    <div class="invoice_actions">
                        <h4>'.esc_html__( 'Add extra expense','wprentals').'</h4>
                        <input type="text" id="inv_expense_name" size="40" name="inv_expense_name" placeholder="'.esc_html__("type expense name","wprentals").'">
                        <input type="text" id="inv_expense_value" size="40" name="inv_expense_value" placeholder="'.esc_html__("type expense value","wprentals").'">
                        <div class="action1_booking" id="add_inv_expenses" data-include_ex="'.esc_attr($include_expeses).'">'.esc_html__( 'add','wprentals').'</div>

                        <h4>'.esc_html__( 'Add discount','wprentals').'</h4>
                        <input type="text" id="inv_expense_discount" size="40" name="inv_expense_discount" placeholder="'.esc_html__("type discount value","wprentals").'">
                        <div class="action1_booking" id="add_inv_discount" data-include_ex="'.esc_attr($include_expeses).'">'.esc_html__( 'add','wprentals').'</div>
                    </div>';

                    print  wp_nonce_field( 'create_invoice_ajax_nonce', 'security-create_invoice_ajax_nonce' ).'
            </div>';
        die();
    }
endif;



if( !function_exists('wpestate_calculate_service_fee') ):
    function wpestate_calculate_service_fee($total,$invoice_id){

        if(intval($invoice_id)==0){
            $service_fee_fixed_fee  =   floatval ( wprentals_get_option('wp_estate_service_fee_fixed_fee','') );
            $service_fee            =   floatval ( wprentals_get_option('wp_estate_service_fee','') );
        }else{
            $service_fee_fixed_fee  =   floatval (  get_post_meta($invoice_id,  'service_fee_fixed_fee', true));
            $service_fee            =   floatval (  get_post_meta($invoice_id,  'service_fee', true) );
        }

        if($service_fee_fixed_fee!=0){
            return $service_fee_fixed_fee;
        }else{
            $fee = round( $total*$service_fee/100,2);
            return $fee;

        }

    }
endif;





if( !function_exists('wpestate_calculate_extra_options_value') ):
    function wpestate_calculate_extra_options_value($count_days,$guests,$type,$value){
        $return_value=0;

        switch ($type) {
            case 0:// single fee
                $return_value = $value;
                break;
            case 1://per night
                $return_value = $value*$count_days;
                break;
            case 2://per guest
                $return_value = $value*$guests;
                break;
            case 3://per guest and night
                $return_value = $value*$guests*$count_days;
                break;
        }
        return $return_value;

    }
endif;



////////////////////////////////////////////////////////////////////////////////
/// Ajax  add inbox
////////////////////////////////////////////////////////////////////////////////
if( !function_exists('wpestate_add_to_inbox') ):
    function wpestate_add_to_inbox($userID,$from,$to,$subject,$description,$first_content=''){

        if($subject!=''){
            $subject = $subject.' '.$from;
        }else{
            $subject = esc_html__( 'Message from ','wprentals').$from;
        }


        $user = get_user_by( 'id',$from );

        $post = array(
            'post_title'	=> esc_html__( 'Message from ','wprentals').$user->user_login,
            'post_content'	=> $description,
            'post_status'	=> 'publish',
            'post_type'         => 'wpestate_message' ,
            'post_author'       => $userID
        );
        $post_id =  wp_insert_post($post );
        update_post_meta($post_id, 'mess_status', 'new' );
        update_post_meta($post_id, 'message_from_user', $from );
        update_post_meta($post_id, 'message_to_user', $to );
        wpestate_increment_mess_mo($to);
        update_post_meta($post_id, 'delete_destination'.$from,0  );
        update_post_meta($post_id, 'delete_destination'.$to, 0 );
        update_post_meta($post_id, 'message_status', 'unread');
        update_post_meta($post_id, 'delete_source', 0);
        update_post_meta($post_id, 'delete_destination', 0);
        if($first_content!=''){
            update_post_meta($post_id, 'first_content', 1);
            update_post_meta($post_id, 'message_status'.$to, 'unread' );

        }
    }
endif;


/*
*
*Ajax  add booking  FRONT END
*
*
*/
add_action('wp_ajax_nopriv_wpestate_mess_front_end', 'wpestate_mess_front_end');
add_action('wp_ajax_wpestate_mess_front_end', 'wpestate_mess_front_end' );
if( !function_exists('wpestate_mess_front_end') ):
    function wpestate_mess_front_end(){
        check_ajax_referer( 'wprentals_submit_mess_front_nonce', 'security' );
        $current_user       =   wp_get_current_user();
        $allowed_html       =   array();
        $userID             =   $current_user->ID;
        $user_login         =   $current_user->user_login;
        $subject            =   esc_html__( 'Message from ','wprentals').$user_login;
        $message_from_user  =   esc_html($_POST['message']);
        $message_phone_no   =   '';
        if( isset( $_POST['phone_no']) ){
            $message_phone_no   =   esc_html($_POST['phone_no']);
        }

        $property_id        =   intval ( $_POST['agent_property_id']);
        $agent_id           =   intval ( $_POST['agent_id'] );

        if($agent_id === 0){
            $owner_id           =   wpsestate_get_author($property_id);
        }else{
            $owner_id           =   get_post_meta($agent_id, 'user_agent_id', true);
        }

        $owner              =   get_userdata($owner_id);
        $owner_email        =   $owner->user_email;
        $owner_login        =   $owner->ID;
        $subject            =   esc_html__( 'Message from ','wprentals').$user_login;


        $booking_guest_no   =   intval  ( $_POST['booking_guest_no'] );
        $booking_from_date  =   wp_kses ( $_POST['booking_from_date'],$allowed_html  );
        $booking_to_date    =   wp_kses ( $_POST['booking_to_date'],$allowed_html  );


        $contact_u_name  =   wp_kses ( $_POST['contact_u_name'],$allowed_html  );
        $contact_u_email =   wp_kses ( $_POST['contact_u_email'],$allowed_html  );



        if($property_id!=0 && get_post_type($property_id) === 'estate_property' ){
            $message_user .='<strong>'. esc_html__(' Sent for property: ','wprentals').'</strong>'.get_the_title($property_id).', '.esc_html__('with the link:','wprentals').' '.esc_url ( get_permalink($property_id) ).'<br>';
        }




        $message_user .=' <strong>'.esc_html__('Selected dates','wprentals').': </strong>'.$booking_from_date.esc_html__( ' to ','wprentals').$booking_to_date.",<br>";
        $message_user .='<strong>'.esc_html__('Guests','wprentals').': </strong>'.$booking_guest_no.'.<br>';
        $message_user .='<strong>'.esc_html__('Content','wprentals').': </strong>'.$message_from_user.'.<br>';

        if (!is_user_logged_in() ) {
            $message_user .= esc_html__( 'Sent by unregistered user with name: ','wprentals').$contact_u_name.', '.esc_html__( 'email: ','wprentals').$contact_u_email;
            if($message_phone_no!=''){
                $message_user .=', '.esc_html('phone: ','wprentals').$message_phone_no;
            }
        }

        wpestate_send_booking_email('inbox',$owner_email,$message_user);

        // add into inbox
        wpestate_add_to_inbox($userID,$userID,$owner_login,$subject,$message_user,1);

        esc_html_e('Your message was sent! You will be notified by email when a reply is received.','wprentals');
        die();
    }
endif;






/*
*
*Ajax  add booking  FRONT END
*
*
*/
add_action('wp_ajax_wpestate_message_client_dashboard', 'wpestate_message_client_dashboard' );
if( !function_exists('wpestate_message_client_dashboard') ):
    function wpestate_message_client_dashboard(){
        check_ajax_referer( 'wprentals_submit_mess_front_nonce', 'security' );
        $current_user       =   wp_get_current_user();
        $allowed_html       =   array();
        $userID             =   $current_user->ID;
        $user_login         =   $current_user->user_login;
        $subject            =   esc_html__( 'Message from ','wprentals').$user_login;
        $message_from_user  =   esc_html($_POST['message']);
        $bookingID          =   intval ( $_POST['bookingID']);

        $propertyID         =   get_post_meta($bookingID, 'booking_id', true);

        $client_id           =   wpsestate_get_author($bookingID);
        $client              =   get_userdata($client_id);
        $client_email        =   $client->user_email;
        $client_login        =   $client->ID;



        $owner_id =   wpsestate_get_author($propertyID);

        if( $owner_id != $userID  ){
            $answer = array(
                        'success'=>false,
                        'propertyID'=>$propertyID,
                        'owner_id'=>$owner_id,
                        'userID'=>$userID,
                        'message'=>'the booking is not for your property'
            );
            print json_encode($answer);
            die();
        }


        if($propertyID!=0 && get_post_type($propertyID) === 'estate_property' ){
            $message_user .='<strong>'. esc_html__(' Sent for property: ','wprentals').'</strong>'.get_the_title($propertyID).', '.esc_html__('with the link:','wprentals').' '.esc_url ( get_permalink($propertyID) ).'<br>';
        }
        $message_user .='<strong>'.esc_html__('Content','wprentals').': </strong>'.$message_from_user.'.<br>';





        wpestate_send_booking_email('inbox',$client_email,$message_user);

        // add into inbox
        //$userID,$from,$to,$subject,$description,$first_content=''
        wpestate_add_to_inbox($userID,$userID,$client_login,$subject,$message_user,1);


        $answer = array(
            'success'=>true,
            'email' =>$client_email,
            'message_user'=>$message_user,
            'message'=>   esc_html__('Your message was sent! You will be notified by email when a reply is received.','wprentals')
        );
        print json_encode($answer);
        die();


    }
endif;




////////////////////////////////////////////////////////////////////////////////
/// Ajax  add booking  FRONT END
////////////////////////////////////////////////////////////////////////////////




if( !function_exists('wpse119881_get_author') ):
    function wpse119881_get_author($post_id){
        $post = get_post( $post_id );
        return $post->post_author;
    }
endif;







////////////////////////////////////////////////////////////////////////////////
/// Ajax  add booking  function
////////////////////////////////////////////////////////////////////////////////
add_action( 'wp_ajax_nopriv_wpestate_ajax_show_booking_costs', 'wpestate_ajax_show_booking_costs' );
add_action( 'wp_ajax_wpestate_ajax_show_booking_costs', 'wpestate_ajax_show_booking_costs' );

if( !function_exists('wpestate_ajax_show_booking_costs') ):
    function wpestate_ajax_show_booking_costs(){
        check_ajax_referer( 'wprentals_add_booking_nonce', 'security' );
        $allowed_html       =   array();
        $property_id        =   intval($_POST['property_id']);
        $wpestate_guest_no  =   intval($_POST['guest_no']);
        $guest_fromone      =   intval ($_POST['guest_fromone']);
        $booking_from_date  =   wp_kses ( $_POST['fromdate'],$allowed_html);
        $booking_to_date    =   wp_kses ( $_POST['todate'],$allowed_html);

        $invoice_id         =   0;
        $price_per_day      =   floatval(get_post_meta($property_id, 'property_price', true));



        $booking_array = wpestate_booking_price($wpestate_guest_no,$invoice_id, $property_id, $booking_from_date, $booking_to_date,$property_id);

        $deposit_show       =   '';
        $balance_show       =   '';
        $wpestate_currency  =   esc_html( wprentals_get_option('wp_estate_currency_label_main', '') ); //currency_symbol
        $wpestate_where_currency     =   esc_html( wprentals_get_option('wp_estate_where_currency_symbol', '') );//where_currency_symbol

        $price_show                         =   wpestate_show_price_booking($booking_array['default_price'],$wpestate_currency,$wpestate_where_currency,1);
        $total_price_show                   =   wpestate_show_price_booking($booking_array['total_price'],$wpestate_currency,$wpestate_where_currency,1);
        $deposit_show                       =   wpestate_show_price_booking($booking_array['deposit'],$wpestate_currency,$wpestate_where_currency,1);
        $balance_show                       =   wpestate_show_price_booking($booking_array['balance'],$wpestate_currency,$wpestate_where_currency,1);
        $city_fee_show                      =   wpestate_show_price_booking($booking_array['city_fee'],$wpestate_currency,$wpestate_where_currency,1);
        $cleaning_fee_show                  =   wpestate_show_price_booking($booking_array['cleaning_fee'],$wpestate_currency,$wpestate_where_currency,1);
        $total_extra_price_per_guest_show   =   wpestate_show_price_booking($booking_array['total_extra_price_per_guest'],$wpestate_currency,$wpestate_where_currency,1);
        $inter_price_show                   =   wpestate_show_price_booking($booking_array['inter_price'],$wpestate_currency,$wpestate_where_currency,1);
        $extra_price_per_guest              =   wpestate_show_price_booking($booking_array['extra_price_per_guest'],$wpestate_currency,$wpestate_where_currency,1);
        $security_fee_show                  =   wpestate_show_price_booking($booking_array['security_deposit'],$wpestate_currency,$wpestate_where_currency,1);
        $early_bird_discount_show           =   wpestate_show_price_booking($booking_array['early_bird_discount'],$wpestate_currency,$wpestate_where_currency,1);
        $rental_type                        =   wprentals_get_option('wp_estate_item_rental_type');
        $booking_type                       =   wprentals_return_booking_type($property_id);

        print '
        <div class="show_cost_form" id="show_cost_form" >
            <div class="cost_row">
                <div class="cost_explanation">';
                if($booking_array['price_per_guest_from_one']==1){

                    if( $booking_array['custom_period_quest'] != 1 ){
                        print trim($extra_price_per_guest).' x ';
                    }

                    print esc_html($booking_array['count_days']).' '.wpestate_show_labels('nights',$rental_type,$booking_type).' x '.$booking_array['curent_guest_no'].' '.esc_html__( 'guests','wprentals');

                    if( $booking_array['custom_period_quest'] == 1 ){
                       echo ' - ';esc_html_e( ' period with custom price per guest','wprentals');
                    }



                }else{

                    if( $booking_array['has_custom'] == 1 ){
                        print  esc_html($booking_array['numberDays']).' '.wpestate_show_labels('nights_custom_price',$rental_type,$booking_type);
                    }else if( $booking_array['has_wkend_price']===1 && $booking_array['cover_weekend']===1) {
                        print  esc_html($booking_array['numberDays']).' '.wpestate_show_labels('days_custom_price',$rental_type,$booking_type);
                    }else{
                        print  esc_html($price_show).' x '.$booking_array['numberDays'].' '.wpestate_show_labels('nights',$rental_type,$booking_type);
                    }



                }


        print '</div>
                <div class="cost_value">'.$inter_price_show.'</div>
            </div>';


        if($booking_array['has_guest_overload']!=0 && $booking_array['total_extra_price_per_guest']!=0 ){
            print '
                <div class="cost_row">
                    <div class="cost_explanation">'.esc_html__( 'Costs for ','wprentals').'  '.$booking_array['extra_guests'].' '.esc_html__('extra guests','wprentals').'</div>
                    <div class="cost_value">'.$total_extra_price_per_guest_show.'</div>
                </div>';
        }


        if($booking_array['cleaning_fee']!=0 && $booking_array['cleaning_fee']!=''){
            print '

                <div class="cost_row">
                    <div class="cost_explanation">'.esc_html__( 'Cleaning Fee','wprentals').'</div>
                    <div class="cost_value cleaning_fee_value" data_cleaning_fee="'.$booking_array['cleaning_fee'].'">'.$cleaning_fee_show.'</div>
                </div>';
        }

        if($booking_array['city_fee']!=0 && $booking_array['city_fee']!=''){
            print '

                <div class="cost_row">
                    <div class="cost_explanation">'.esc_html__( 'City Fee','wprentals').'</div>
                    <div class="cost_value city_fee_value" data_city_fee="'.$booking_array['city_fee'].'">'.$city_fee_show.'</div>
                </div>';
        }



        if($booking_array['security_deposit']!=0 && $booking_array['security_deposit']!=''){
            print '
                <div class="cost_row">
                    <div class="cost_explanation">'.esc_html__( 'Security Deposit (*refundable)','wprentals').'</div>
                    <div class="cost_value">'.$security_fee_show.'</div>
                </div>';
        }

        if($booking_array['early_bird_discount']!=0 && $booking_array['early_bird_discount']!=''){
            print '
                <div class="cost_row">
                    <div class="cost_explanation">'.esc_html__( 'Early Bird Discount','wprentals').'</div>
                    <div class="cost_value" id="early_bird_discount" data-early-bird="'.esc_attr($booking_array['early_bird_discount']).'">'.$early_bird_discount_show.'</div>
                </div>';
        }


        print '
                <div class="cost_row" id="total_cost_row">
                    <div class="cost_explanation"><strong>'.esc_html__( 'TOTAL','wprentals').'</strong></div>
                    <div class="cost_value" data_total_price="'.$booking_array['total_price'].'" >'.$total_price_show.'</div>
                </div>
            </div>';

        $instant_booking=$instant_booking                 =   floatval   ( get_post_meta($property_id, 'instant_booking', true) );



        if($instant_booking==1){

            print '<div class="cost_row_instant instant_depozit">'.esc_html__( 'Deposit for instant booking','wprentals').': ';
            print '<span class="instant_depozit_value">';
                if(floatval($booking_array['deposit'])!=0){
                   print trim($deposit_show);
                }else{
                    echo '0';
                }
            print '</span>';

            print '</div>';

            if(floatval($booking_array['balance'])!=0){
                print '<div class="cost_row_instant instant_balance">'.esc_html__( 'Balance remaining','wprentals').': <span class="instant_balance_value">'.$balance_show.'</span></div>';
            }

            print'<div class="instant_book_info" data-total_price="'.esc_attr($booking_array['total_price']).'" data-deposit="'.esc_attr($booking_array['deposit']).'" data-balance="'.esc_attr($booking_array['balance']).'"> ';
        }

        die();
    }
endif;



////////////////////////////////////////////////////////////////////////////////
/// Ajax  add booking  function
////////////////////////////////////////////////////////////////////////////////
add_action( 'wp_ajax_nopriv_wpestate_new_ajax_add_booking', 'wpestate_new_ajax_add_booking' );
add_action( 'wp_ajax_wpestate_new_ajax_add_booking', 'wpestate_new_ajax_add_booking' );
if( !function_exists('wpestate_new_ajax_add_booking') ):
    function wpestate_new_ajax_add_booking(){
    exit();
        $allowed_html=array();
        check_ajax_referer( 'booking_ajax_nonce','security');
        $current_user = wp_get_current_user();
        $userID             =   $current_user->ID;
        if ( !is_user_logged_in() ) {
            exit('ko');
        }
        if($userID === 0 ){
            exit('out pls');
        }


        $comment            =   trim ( wp_kses ( $_POST['comment'],$allowed_html)) ;
        $guests             =   intval(  wp_kses ( $_POST['guests'],$allowed_html));
        $property_id        =   intval( $_POST['property_name'] );
        $fromdate           =   trim (  wp_kses ( $_POST['fromdate'],$allowed_html));
        $to_date            =   trim (  wp_kses ( $_POST['todate'],$allowed_html) );
        $event_name         =   esc_html__( 'Booking Request','wprentals');


       $post = array(
            'post_title'	=> $event_name,
            'post_content'	=> $comment,
            'post_status'	=> 'publish',
            'post_type'         => 'wpestate_booking' ,
            'post_author'       => $userID
        );
        $post_id =  wp_insert_post($post );

        $post = array(
            'ID'                => $post_id,
            'post_title'	=> $event_name.' '.$post_id
        );
        wp_update_post( $post );

        update_post_meta($post_id, 'booking_id', $property_id);
        update_post_meta($post_id, 'booking_from_date', $fromdate);
        update_post_meta($post_id, 'booking_to_date', $to_date);
        update_post_meta($post_id, 'booking_status', 'confirmed');
        update_post_meta($post_id, 'booking_invoice_no', 0);
        update_post_meta($post_id, 'booking_pay_ammount', 0);
        update_post_meta($post_id, 'booking_guests', $guests);

        update_post_meta($post_id, 'booking_from_date_unix', strtotime($fromdate));
        update_post_meta($post_id, 'booking_to_date_unix', strtotime($to_date));

        $unix_time_start    =   strtotime ($fromd);




        $preview            =   wp_get_attachment_image_src(get_post_thumbnail_id($property_id), 'property_sidebar');

         // build the reservation array
        $reservation_array = wpestate_get_booking_dates($property_id);


        update_post_meta($property_id, 'booking_dates', $reservation_array);


        print '
        <div class="dasboard-prop-listing">
           <div class="blog_listing_image">
                   <img  src="'.esc_url($preview[0]).'"  alt="'.esc_html__('thumb','wprentals').'" /></a>
           </div>

            <div class="prop-info">
                <h3 class="listing_title">
                    '.$event_name.'
                </h3>

                <div class="user_dashboard_listed">
                    <strong>'.esc_html__( 'Request by ','wprentals').'</strong>'.get_the_author_meta( 'user_login', $userID ).'<strong>
                </div>

                <div class="user_dashboard_listed">
                    <strong>'.esc_html__( 'Period: ','wprentals').'</strong>  '.$fromdate.' <strong>'.esc_html__( 'to','wprentals').'</strong> '.$to_date.'
                </div>

                <div class="user_dashboard_listed">
                    <strong>'.esc_html__( 'Invoice No: ','wprentals').'</strong>
                </div>

                <div class="user_dashboard_listed">
                    <strong>'.esc_html__( 'Pay Ammount: ','wprentals').' </strong>
                </div>
            </div>


            <div class="info-container_booking">
                <span class="tag-published">'.esc_html__( 'Confirmed','wprentals').'</span>
            </div>

         </div>';

        die();
    }
endif;







////////////////////////////////////////////////////////////////////////////////
/// Ajax  Register function
////////////////////////////////////////////////////////////////////////////////
add_action( 'wp_ajax_nopriv_wpestate_ajax_register_form_booking', 'wpestate_ajax_register_form_booking' );
add_action( 'wp_ajax_ajax_wpestate_ajax_register_form_booking', 'wpestate_ajax_register_form_booking' );
if( !function_exists('wpestate_ajax_register_form_booking') ):
    function wpestate_ajax_register_form_booking(){
            $allowed_html=array();
            check_ajax_referer( 'register_ajax_nonce','security-register');

            $user_email  =   trim(  wp_kses ( $_POST['user_email_register'],$allowed_html) ) ;
            $user_name   =   trim(  wp_kses ( $_POST['user_login_register'],$allowed_html) ) ;
            $group       =   trim(  wp_kses ( $_POST['group_register'],$allowed_html) ) ;
            if (preg_match("/^[0-9A-Za-z_]+$/", $user_name) == 0) {
                print esc_html__( 'Invalid username( *do not use special characters or spaces ) ','wprentals');
                die();
            }

            if ($user_email=='' || $user_name==''){
              print esc_html__( 'Username and/or Email field is empty!','wprentals');
              exit();
            }

            if(filter_var($user_email,FILTER_VALIDATE_EMAIL) === false) {
                 print esc_html__( 'The email doesn\'t look right !','wprentals');
                exit();
            }

            $domain = substr(strrchr($user_email, "@"), 1);
            if( !checkdnsrr ($domain) ){
                print esc_html__( 'The email\'s domain doesn\'t look right.','wprentals');
                exit();
            }

            $user_id     =   username_exists( $user_name );
            if ($user_id){
                print esc_html__( 'Username already exists. Please choose a different one!','wprentals');
                exit();
             }


            if ( !$user_id && email_exists($user_email) == false ) {
                $random_password = wp_generate_password( $length=12, $include_standard_special_chars=false );

                $user_id  = wp_create_user( $user_name, $random_password, $user_email );

                if ( is_wp_error($user_id) ){

                }else{
                    print esc_html__( 'An email with the generated password was sent!','wprentals');
                    wpestate_update_profile_booking($user_id, $group);
                    wpestate_wp_new_user_notification( $user_id, $random_password ) ;
                    if('renter' ==  $group ){
                        wpestate_register_as_user($user_name,$user_id);
                    }


                }

            } else {
               print esc_html__( 'Email already exists.  Please choose a new one.','wprentals');
            }


            die();
    }

endif;



if( !function_exists('wpestate_update_profile_booking') ):
    function wpestate_update_profile_booking($userID,$group){
        if(1==1){ // if membership is on

            if( wprentals_get_option('wp_estate_free_mem_list_unl', '' ) ==1 ){
                $package_listings =-1;
                $featured_package_listings  = esc_html( wprentals_get_option('wp_estate_free_feat_list','') );
            }else{
                $package_listings           = esc_html( wprentals_get_option('wp_estate_free_mem_list','') );
                $featured_package_listings  = esc_html( wprentals_get_option('wp_estate_free_feat_list','') );

                if($package_listings==''){
                    $package_listings=0;
                }
                if($featured_package_listings==''){
                    $featured_package_listings=0;
                }
            }
            update_user_meta($userID, 'user_group',$group);
            update_user_meta( $userID, 'package_listings', $package_listings) ;
            update_user_meta( $userID, 'package_featured_listings', $featured_package_listings) ;
            $time = time();
            $date = date('Y-m-d H:i:s',$time);
            update_user_meta( $userID, 'package_activation', $date);
            //package_id no id since the pack is free

        }

    }
endif;




////////////////////////////////////////////////////////////////////////////////
/// Ajax  Start Stripr
////////////////////////////////////////////////////////////////////////////////

add_action( 'wp_ajax_wpestate_start_stripe', 'wpestate_start_stripe' );
if( !function_exists('wpestate_start_stripe') ):
    function wpestate_start_stripe(){

        $current_user = wp_get_current_user();
        $userID                         =   $current_user->ID;


        if ( !is_user_logged_in() ) {
            exit('ko');
        }
        if($userID === 0 ){
            exit('out pls');
        }


            $stripe_secret_key              =   esc_html( wprentals_get_option('wp_estate_stripe_secret_key','') );
            $stripe_publishable_key         =   esc_html( wprentals_get_option('wp_estate_stripe_publishable_key','') );


            $stripe = array(
                "secret_key"       => $stripe_secret_key,
                "publishable_key"  => $stripe_publishable_key
            );

            Stripe::setApiKey($stripe['secret_key']);

            print '
            <div id="cover" style="display:block;"></div><div id="ajax_login_container">
                <h5>'.esc_html__( 'Proceed to payment.','wprentals').'</h5>
                <div id="closeadvancedlogin"></div>
                <div id="ajax_login_div">
                    <form action="charge.php" method="post">';
                        wp_nonce_field( 'wpestate_stripe_payments', 'wpestate_stripe_payments_nonce' );
                        print'<script src="https://checkout.stripe.com/checkout.js" class="stripe-button"
                        data-locale="auto"
                        data-key="'. esc_attr($stripe['publishable_key']).'"
                        data-label="'.esc_attr__( 'Pay with Credit Card','wprentals').'"
                        data-zip-code="true"
                        data-amount="5000" data-description="'.esc_attr__('reservation payment','wprentals').'"></script>';

                    print'
                    </form>
                </div>
            </div>';


            die();
    }
endif;




////////////////////////////////////////////////////////////////////////////////
/// Ajax  create invoice
////////////////////////////////////////////////////////////////////////////////


add_action('wp_ajax_wpestate_show_confirmed_booking', 'wpestate_show_confirmed_booking' );
if( !function_exists('wpestate_show_confirmed_booking') ):
    function wpestate_show_confirmed_booking(){
        check_ajax_referer( 'wprentals_booking_confirmed_actions_nonce', 'security' );
        $current_user = wp_get_current_user();
        $userID                         =   $current_user->ID;


        if ( !is_user_logged_in() ) {
            exit('ko');
        }
        if($userID === 0 ){
            exit('out pls');
        }

        $userID         =   $current_user->ID;
        $user_email     =   $current_user->user_email;
        $invoice_id     =   intval($_POST['invoice_id']);
        $bookid         =   intval($_POST['booking_id']);

        $the_post= get_post( $bookid);
        $book_author=$the_post->post_author;

        $the_post= get_post( $invoice_id);
        $inv_author=$the_post->post_author;

        if($userID!=$inv_author && $book_author!=$userID){
            exit('out pls');
        }

        wpestate_super_invoice_details($invoice_id);

        die();
    }
endif;


////////////////////////////////////////////////////////////////////////////////
/// Ajax  create trip details
////////////////////////////////////////////////////////////////////////////////




add_action('wp_ajax_wpestate_show_trip_details', 'wpestate_show_trip_details' );
if( !function_exists('wpestate_show_trip_details') ):
    function wpestate_show_trip_details(){
        check_ajax_referer( 'wprentals_booking_confirmed_actions_nonce', 'security' );
        $current_user = wp_get_current_user();
        $userID                         =   $current_user->ID;


        if ( !is_user_logged_in() ) {
            exit('ko');
        }
        if($userID === 0 ){
            exit('out pls');
        }



        $invoice_id     =   intval($_POST['invoice_id']);
        $bookid         =   intval($_POST['booking_id']);

        $the_post= get_post( $bookid);
        $book_author=$the_post->post_author;

        $the_post= get_post( $invoice_id);
        $inv_author=$the_post->post_author;

        $property_id         =   get_post_meta($bookid, 'booking_id', true);

        if($userID!=$inv_author && $book_author!=$userID){
            exit('out pls');
        }

        wpestate_generate_trip_details($property_id,$invoice_id,$bookid,'show');

        die();
    }
endif;




////////////////////////////////////////////////////////////////////////////////
/// Show  invoice dashboard
////////////////////////////////////////////////////////////////////////////////


add_action('wp_ajax_wpestate_show_invoice_dashboard', 'wpestate_show_invoice_dashboard' );
if( !function_exists('wpestate_show_invoice_dashboard') ):
    function wpestate_show_invoice_dashboard(){
        check_ajax_referer( 'wprentals_invoice_pace_actions_nonce', 'security' );
        $current_user = wp_get_current_user();
        $userID         =   $current_user->ID;

        if ( !is_user_logged_in() ) {
            exit('ko');
        }
        if($userID === 0 ){
            exit('out pls');
        }


        $user_email     =   $current_user->user_email;
        $invoice_id     =   intval($_POST['invoice_id']);
        $bookid         =   intval($_POST['booking_id']);

        $the_post= get_post( $bookid);
        $book_author=$the_post->post_author;

        $the_post= get_post( $invoice_id);
        $inv_author=$the_post->post_author;

        if($userID!=$inv_author && $book_author!=$userID){
            exit('out pls');
        }


        $invoice_saved      =   esc_html(get_post_meta($invoice_id, 'invoice_type', true));
        wpestate_super_invoice_details($invoice_id);

        if($invoice_saved=='Listing'){
            $item_id        =   esc_html(get_post_meta($invoice_id, 'item_id', true));
            $item_price     =   esc_html(get_post_meta($invoice_id, 'item_price', true));
            $purchase_date  =   esc_html(get_post_meta($invoice_id, 'purchase_date', true));
            print  '<div class="create_invoice_form">
                        <h3>'.esc_html__( 'Invoice INV','wprentals').$invoice_id.'</h3>
                        <div class="dashboard_invoice_details"><strong>'.esc_html__( 'Type','wprentals').': </strong>'.$invoice_saved.'</div>
                        <div class="dashboard_invoice_details"><strong>'.esc_html__( 'Listing Id','wprentals').': </strong>'.wpestate_show_product_type($item_id).'</div>
                        <div class="dashboard_invoice_details"><strong>'.esc_html__( 'Price','wprentals').': </strong>'.$item_price.'</div>
                        <div class="dashboard_invoice_details"><strong>'.esc_html__( 'Date','wprentals').': </strong>';
                    if(is_numeric($purchase_date)){
                        echo date('l jS \of F Y',$purchase_date);
                    }else{
                        print esc_html($purchase_date);
                    }
            print  '</div>
                   </div>';
        }

        if($invoice_saved=='Upgrade to Featured'){
            $item_id        =   esc_html(get_post_meta($invoice_id, 'item_id', true));
            $item_price     =   esc_html(get_post_meta($invoice_id, 'item_price', true));
            $purchase_date  =   esc_html(get_post_meta($invoice_id, 'purchase_date', true));
            print  '<div class="create_invoice_form">
                        <h3>'.esc_html__( 'Invoice INV','wprentals').$invoice_id.'</h3>
                        <div class="dashboard_invoice_details"><strong>'.esc_html__( 'Type','wprentals').': </strong>'.$invoice_saved.'</div>
                        <div class="dashboard_invoice_details"><strong>'.esc_html__( 'Listing Id','wprentals').': </strong>'.wpestate_show_product_type($item_id).'</div>
                        <div class="dashboard_invoice_details"><strong>'.esc_html__( 'Price','wprentals').': </strong>'.$item_price.'</div>
                        <div class="dashboard_invoice_details"><strong>'.esc_html__( 'Date','wprentals').': </strong>';
                    if(is_numeric($purchase_date)){
                        echo date('l jS \of F Y',$purchase_date);
                    }else{
                        print esc_html($purchase_date);
                    }
            print  '</div>
                   </div>';

        }

        if($invoice_saved=='Publish Listing with Featured'){
            $item_id        =   esc_html(get_post_meta($invoice_id, 'item_id', true));
            $item_price     =   esc_html(get_post_meta($invoice_id, 'item_price', true));
            $purchase_date  =   esc_html(get_post_meta($invoice_id, 'purchase_date', true));
            print  '<div class="create_invoice_form">
                        <h3>'.esc_html__( 'Invoice INV','wprentals').$invoice_id.'</h3>
                        <div class="dashboard_invoice_details"><strong>'.esc_html__( 'Type','wprentals').': </strong>'.$invoice_saved.'</div>
                        <div class="dashboard_invoice_details"><strong>'.esc_html__( 'Listing Id','wprentals').': </strong>'.wpestate_show_product_type($item_id).'</div>
                        <div class="dashboard_invoice_details"><strong>'.esc_html__( 'Price','wprentals').': </strong>'.$item_price.'</div>
                        <div class="dashboard_invoice_details"><strong>'.esc_html__( 'Date','wprentals').': </strong>';
                    if(is_numeric($purchase_date)){
                        echo date('l jS \of F Y',$purchase_date);
                    }else{
                        print esc_html($purchase_date);
                    }
            print  '</div>
                   </div>';
        }

        if($invoice_saved=='Package'){
            $invoice_period_saved      =  esc_html(get_post_meta($invoice_id, 'biling_type', true));
            $item_id        =   esc_html(get_post_meta($invoice_id, 'item_id', true));
            $item_price     =   esc_html(get_post_meta($invoice_id, 'item_price', true));
            $purchase_date  =   esc_html(get_post_meta($invoice_id, 'purchase_date', true));
            print  '<div class="create_invoice_form">
                        <h3>'.esc_html__( 'Invoice INV','wprentals').$invoice_id.'</h3>
                        <div class="dashboard_invoice_details"><strong>'.esc_html__( 'Type','wprentals').': </strong>'.$invoice_saved.'</div>
                        <div class="dashboard_invoice_details"><strong>'.esc_html__( 'Package','wprentals').': </strong>'.wpestate_show_product_type($item_id).'</div>
                        <div class="dashboard_invoice_details"><strong>'.esc_html__( 'Price','wprentals').': </strong>'.$item_price.'</div>
                        <div class="dashboard_invoice_details"><strong>'.esc_html__( 'Period','wprentals').': </strong>'.$invoice_period_saved.'</div>

                        <div class="dashboard_invoice_details"><strong>'.esc_html__( 'Date','wprentals').': </strong>';
                    if(is_numeric($purchase_date)){
                        echo date('l jS \of F Y',$purchase_date);
                    }else{
                        print esc_html($purchase_date);
                    }
            print  '</div>
                   </div>';
            }
        die();

    }
endif;


////////////////////////////////////////////////////////////////////////////////
/// Ajax  create review form
////////////////////////////////////////////////////////////////////////////////


add_action('wp_ajax_wpestate_show_review_form', 'wpestate_show_review_form' );

if (!function_exists('wpestate_show_review_form')):
    function wpestate_show_review_form(){
        check_ajax_referer( 'wprentals_reservation_actions_nonce', 'security' );
        $current_user = wp_get_current_user();
        $userID         =   $current_user->ID;

        if ( !is_user_logged_in() ) {
            exit('ko');
        }
        if($userID === 0 ){
            exit('out pls');
        }


        $user_email     =   $current_user->user_email;
        $listing_id     =   intval($_POST['listing_id']);
        $bookid         =   intval ($_POST['bookid']);

        $the_post= get_post( $bookid);

        if( $current_user->ID != $the_post->post_author ) {
            exit('you don\'t have the right to see this');
        }

        $review_fields = wpestate_get_review_fields();
	    $max_stars = wpestate_get_max_stars();
	    $field_html = '';
        foreach ( $review_fields['fields'] as $key => $label ) {
	        $fields = 0;
        	$field_html .= sprintf('<div class="%s">', esc_attr($key)) . PHP_EOL;
	        $field_html .= sprintf('<span class="rating_legend">%s</span>', $label);
        	while ( $fields < $max_stars) {
		        $fields++;
		        $field_html .= '<span class="empty_star"></span>' . PHP_EOL;
	        }
        	$field_html .= sprintf('</div><!-- end .%s -->', esc_attr($key)) . PHP_EOL;
        }

        print '
            <div class="create_invoice_form">
                    <h3>'.esc_html__( 'Post Review','wprentals').'</h3>'
              . $field_html .
                    '<textarea id="review_content" name="review_content" class="form-control"></textarea>

                    <div class="action1_booking" id="post_review" data-bookid="'.esc_attr($bookid).'" data-listing_id="'.esc_attr($listing_id).'">'.esc_html__( 'Submit Review','wprentals').'</div>
            </div>';
        die();
    }
endif;



add_action('wp_ajax_wpestate_post_review', 'wpestate_post_review' );


if (!function_exists('wpestate_post_review')):
    function wpestate_post_review(){
        check_ajax_referer( 'wprentals_reservation_actions_nonce', 'security' );
        $current_user = wp_get_current_user();
        $allowed_html=array();

        $bookid     =   intval($_POST['bookid']);
        $userID                         =   $current_user->ID;

        if ( !is_user_logged_in() ) {
            exit('ko');
        }
        if($userID === 0 ){
            exit('out pls');
        }

        $the_post= get_post( $bookid);
        if( $current_user->ID != $the_post->post_author ) {
            exit('you don\'t have the right to see this');
        }





        $userID         =   $current_user->ID;
        $user_login     =   $current_user->user_login;
        $user_email     =   $current_user->user_email;
        $listing_id     =   intval($_POST['listing_id']);

        $stars          =   html_entity_decode($_POST['stars']);
        $content        =   wp_kses($_POST['content'],$allowed_html);
        $time           =   time();
        $time = current_time('mysql');
        $data = array(
            'comment_post_ID' => $listing_id,
            'comment_author' => $user_login,
            'comment_author_email' => $user_email,
            'comment_author_url' => '',
            'comment_content' => $content,
            'comment_type' => 'comment',
            'comment_parent' => 0,
            'user_id' => $userID,
            'comment_author_IP' => '127.0.0.1',
            'comment_agent' => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.0.10) Gecko/2009042316 Firefox/3.0.10 (.NET CLR 3.5.30729)',
            'comment_date' => $time,
            'comment_approved' => 1,
        );

        $comment_id =    wp_insert_comment($data);
        add_comment_meta( $comment_id, 'review_stars',$stars  );
        update_post_meta($listing_id,'review_by_'.$userID,'has');

        wpestate_calculate_property_rating( $listing_id );

        $author_id = get_post_field('post_author', $listing_id);

        // Get the user data for the author
        $user_data = get_userdata($author_id);
        $owner_email = $user_data->user_email;


        $total_stars_raw = get_comment_meta( $comment_id , 'review_stars', true );
        $total_stars = wpestate_get_star_total_rating($total_stars_raw);

        $content= array(
            'stars'     =>  $total_stars,
            'user'      =>  $user_login,
            'content'   =>  $content,
            'listing_id'=>  $listing_id,

        );
        wpestate_send_booking_email('new_review',$owner_email,$content);


die();

    }
endif;
