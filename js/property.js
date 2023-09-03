/*global $, jQuery, ajaxcalls_vars,wprentals_per_hour_locale_vars, document,mega_details,booking_array,price_array,wpestate_convert_selected_days, control_vars, window, map, setTimeout, Modernizr, property_vars,control_vars_property*/

jQuery(window).scroll(function ($) {
    "use strict";
    var scroll = jQuery(window).scrollTop();
    if (scroll >= 400) {
        if (!Modernizr.mq('only all and (max-width: 1180px)')) {
            jQuery(".property_menu_wrapper_hidden").fadeIn(400);
            jQuery(".property_menu_wrapper").fadeOut(400);
        }
    } else {
        jQuery(".property_menu_wrapper_hidden").fadeOut(400);
        jQuery(".property_menu_wrapper").fadeIn(400);
    }
});

var who_is=1;
var booking_started=0;
price_array=[];
if(control_vars_property.custom_price!==''){
    price_array     = JSON.parse (control_vars_property.custom_price);
}

// booking_array=[];
// if( control_vars_property.booking_array!=='' && control_vars_property.booking_array.length!==0 ){
//     booking_array   = JSON.parse (control_vars_property.booking_array);
// }

//todo@@@ start customization
let booking_array = {};

for (const listingId in property_vars?.reservationGroupedData) {
    if (!Array.isArray(property_vars.reservationGroupedData[listingId])) {
        Object.assign(booking_array, property_vars.reservationGroupedData[listingId]);
    }
}
//todo@@@ end customization


cleaning_fee_per_day            =   control_vars_property.cleaning_fee_per_day;
city_fee_per_day                =   control_vars_property.city_fee_per_day;
price_per_guest_from_one        =   control_vars_property.price_per_guest_from_one;
checkin_change_over             =   control_vars_property.checkin_change_over;
checkin_checkout_change_over    =   control_vars_property.checkin_checkout_change_over;
min_days_booking                =   control_vars_property.min_days_booking;
extra_price_per_guest           =   control_vars_property.extra_price_per_guest;
price_per_weekeend              =   control_vars_property.price_per_weekeend;

mega_details=[];
if(control_vars_property.mega_details!==''){
    mega_details                    =   JSON.parse(control_vars_property.mega_details);
}

weekdays=[];
if(control_vars.weekdays!==''){
    weekdays                        =   JSON.parse(control_vars.weekdays);
}


jQuery( window ).on( "orientationchange", function( event ) {
      if ( !Modernizr.mq('(min-width: 1023px)') ) {
          jQuery('#booking_form_request').removeClass('booking_on_mobile').show();
      }
});



/**
* enable fancybox gallery
*
*
*
*
*/

function wprentals_enable_fancybox_gallery(){



      jQuery('[data-fancybox="website_rental_gallery"]').fancybox({
        thumbs : {
        autoStart : true
      },
         touch: true,
         loop:true,
          arrows: true,
      });



      jQuery('#carousel-listing .item img').on('click',function () {
          jQuery("a[rel^='data-fancybox-thumb']:first").click();
      });

      jQuery('.imagebody_new .image_gallery').on('click',function () {
          jQuery("a[rel^='data-fancybox-thumb']:first").click();
      });

}





/**
* show mobile booking
*
*
*
*
*/

function wprentals_show_mobile_booking(){

    jQuery('#mobile_booking_triger').on('click',function(){
      jQuery('#booking_form_request').addClass('booking_on_mobile').show();
    })

    jQuery('#booking_form_mobile_close').on('click',function(){
        jQuery('#booking_form_request').hide();
    })
}


/**
* PreSet booking calendar
*
*
*
*
*/


function wprentals_show_booking_calendars(in_date, out_date){
     if(property_vars.book_type==='2'){
        check_in_out_enable_calendar_per_hour_new(in_date, out_date);
    }else {
        check_in_out_enable2(in_date, out_date);
    }
}


/**
* Set booking calendar
*
*
*
*
*/
var show_daterange_picker_custom_mess="no";
function check_in_out_enable2(in_date, out_date) {
    var today, prev_date,read_in_date,date_format,calendar_opens;
    show_daterange_picker_custom_mess="yes";
    today           =   new Date();
    date_format     =   control_vars.date_format.toUpperCase();
    today           =   moment(today).format("MM/DD/YYYY");
    minim_days      =   parseFloat (min_days_booking,10);
    calendar_opens  =   'left';
    if(jQuery('#primary').hasClass('col-md-pull-8')){
        calendar_opens  =   'right';
    }
    jQuery("#" + in_date).attr('readonly','readonly');

    var options = {
            opens:calendar_opens,
            singleDatePicker: false,
            autoApply: true,
            alwaysShowCalendars: true,
            autoUpdateInput: false,
            minDate:today,
            locale:{
                daysOfWeek:dayNamesShort,
                monthNames:longmonths
            },

            isCustomDate:wpestate_booking_show_booked,

        };

    // set minimum days
    if(minim_days!==0){
        options.minSpan= {
            "days": minim_days
        };
    }



    var date_format     = control_vars.date_format.toUpperCase();
    date_format=date_format.replace("YY", "YYYY");

    var in_date_front   = jQuery('#' + in_date);
    var out_date_front  = jQuery('#' + out_date);



    jQuery("#" + out_date).removeAttr('disabled');


    var calendar= jQuery("#" + in_date).daterangepicker(
        options,
        function (start, end, label) {


            start_date  =                 start.format(date_format);
            end_date    =                 end.format(date_format);

            in_date_front.val(start_date);
            out_date_front.val(end_date);
            who_is=1;
            booking_started=1;
            jQuery('.wpestate_calendar').removeClass('minim_days_reservation').removeClass('wpestate_min_days_required');

            var prop_id=jQuery('#listing_edit').val();
            wpestate_setCookie('booking_prop_id_cookie',  prop_id , 1);
            wpestate_setCookie('booking_start_date_cookie',  jQuery('#start_date').val() , 1);
            wpestate_setCookie('booking_end_date_cookie',  jQuery('#end_date').val() , 1);

            show_booking_costs();

        }
    );

    jQuery("#" + in_date).on('click',function(){
        jQuery('.daterangepicker').css('margin-top','0px');
    });

    jQuery("#" + out_date).on('click',function(){
        jQuery("#" + in_date).trigger('click');
        jQuery('.daterangepicker').css('margin-top','65px');
    });



    jQuery("html").on("mouseenter",".wpestate_booking_class", function() {
        var  unit_class = jQuery(this).attr('class');
        unit_class = unit_class.match(/\d+/);
        jQuery(this).find('.wpestate_show_price_calendar').show();

        if(who_is===1){
            wpestate_show_min_days_reservation(unit_class);
        }
    });


    jQuery("html").on("mouseleave",".wpestate_booking_class", function() {
        jQuery(this).find('.wpestate_show_price_calendar').hide();
        wpestate_remove_min_days_reservation(this);
    });

}





/**
* Set invalid dates
*
*
*
*
*/


function wpestate_booking_invalid_Date_new(date){
    var display_price       =   '';

    var from_who            =   "start_date";
    var received_date       =   new Date(date);
    var today               =   Math.floor(Date.now() / 1000);
    var unixtime            =   received_date.getTime()/1000;
    var unixtime1           =   unixtime - received_date.getTimezoneOffset()*60;
    var unixtime1_key       =   String(unixtime1);
    var week_day            =   received_date.getDay();
    var block_check_in_check_out    =   0;
    var block_check_in      =   0;
    if(week_day===0){
        week_day=7;
    }

    // establish the start day block
    //////////////////////////////////////////////////////////////////////////
    if(mega_details[unixtime1_key] !== undefined){
        if( parseFloat(mega_details[unixtime1_key]['period_checkin_change_over'],10)!==0 ) {
            block_check_in =  parseFloat(mega_details[unixtime1_key]['period_checkin_change_over'],10);
        }
    }else if( parseFloat(checkin_change_over)!==0 ){
        block_check_in =  parseFloat(checkin_change_over,10);
    }

    // establish the start day - end day block
    ////////////////////////////////////////////////////////////////////////////
    if(mega_details[unixtime1_key] !== undefined){
        if( parseFloat(mega_details[unixtime1_key]['period_checkin_checkout_change_over'],10)!==0 ) {
            block_check_in_check_out =  parseFloat(mega_details[unixtime1_key]['period_checkin_checkout_change_over'],10);
        }
    }else if( parseFloat(checkin_checkout_change_over)!==-1 ){
        block_check_in_check_out =  parseFloat(checkin_checkout_change_over,10);
    }


        start_reservation=1;

        if(end_reservation===1){
            end_reservation=0;
        }
        if(week_day !== block_check_in_check_out && block_check_in_check_out!==0 && unixtime1_key > (today-24*60*60) ){

            return 'is_block_check_in_check_out';
        //Check in/Check out  only on '+weekdays[block_check_in]
        }else if(week_day !== block_check_in && block_check_in!==0 && from_who ==='start_date' && unixtime1_key > (today-24*60*60) ){

            return 'is_block_check_in';

        }

        return '';

}




/**
* Set booking days
*
*
*
*
*/


function wpestate_booking_show_booked(date){
    var display_price       =   '';
    var from_who            =   "start_date";
    var reservation_class   =   '';
    var from_css_class      =   '';
    var received_date       =   new Date(date);
    var today               =   Math.floor(Date.now() / 1000);
    var unixtime            =   received_date.getTime()/1000;
    var unixtime1           =   unixtime - received_date.getTimezoneOffset()*60;
    var unixtime1_key       =   String(unixtime1);
    var week_day            =   received_date.getDay();
    var block_check_in_check_out    =   0;
    var block_check_in      =   0;
    if(week_day===0){
        week_day=7;
    }
        // establish the start day block
    //////////////////////////////////////////////////////////////////////////
    if(mega_details[unixtime1_key] !== undefined){
        if( parseFloat(mega_details[unixtime1_key]['period_checkin_change_over'],10)!==0 ) {
            block_check_in =  parseFloat(mega_details[unixtime1_key]['period_checkin_change_over'],10);
        }
    }else if( parseFloat(checkin_change_over)!==0 ){
        block_check_in =  parseFloat(checkin_change_over,10);
    }

    // establish the start day - end day block
    ////////////////////////////////////////////////////////////////////////////
    if(mega_details[unixtime1_key] !== undefined){
        if( parseFloat(mega_details[unixtime1_key]['period_checkin_checkout_change_over'],10)!==0 ) {
            block_check_in_check_out =  parseFloat(mega_details[unixtime1_key]['period_checkin_checkout_change_over'],10);
        }
    }else if( parseFloat(checkin_checkout_change_over)!==-1 ){
        block_check_in_check_out =  parseFloat(checkin_checkout_change_over,10);
    }




    var block_class=' disabled off';
    if( booking_array[unixtime1] != undefined){
        end_reservation_class=1;

        if(start_reservation_class==1){
            reservation_class=' start_reservation';
            start_reservation_class=0;
            return "wpestate_calendar calendar-reserved"+reservation_class+" "+block_class;
        }

        return "wpestate_calendar calendar-reserved"+reservation_class+" "+block_class;;

    }else{

        start_reservation_class=1;
        if(end_reservation_class===1){
            reservation_class=' end_reservation';
            end_reservation_class=0;

        }

        if(week_day !== block_check_in_check_out && block_check_in_check_out!==0 && unixtime1_key > (today-24*60*60) ){

            if(reservation_class !== ' end_reservation'){
                reservation_class=reservation_class+' check_in_block 1';
            }
            return  "wpestate_calendar "+reservation_class+" date"+unixtime1_key;


        //Check in/Check out  only on '+weekdays[block_check_in]
        }else if(week_day !== block_check_in && block_check_in!==0 && from_who ==='start_date' && unixtime1_key > (today-24*60*60) ){
            return "wpestate_calendar "+reservation_class+" date"+unixtime1_key;

        }

        return "freetobook wpestate_calendar"+reservation_class+" date"+unixtime1_key+" "+from_css_class;
    }

}

/**
* Show price
*
*
*
*
*/



function wpestate_show_price_Daterangepicker(date){
    "use strict";
 
    var display_price       =   '';
    var received_date       =   new Date(date);
    var today               =   Math.floor(Date.now() / 1000);
    var unixtime            =   received_date.getTime()/1000;
    var unixtime1           =   unixtime - received_date.getTimezoneOffset()*60;
    var unixtime1_key       =   String(unixtime1);

    var week_day            =   received_date.getDay();

    if(week_day===0){
        week_day=7;
    }


    if ( control_vars.setup_weekend_status === '0' && (week_day==6 || week_day==7) ){
        display_price = wpestate_return_weekeend_price(week_day,unixtime1_key);
    }else if(control_vars.setup_weekend_status === '1'  && (week_day==5 || week_day==6) ){
       display_price = wpestate_return_weekeend_price(week_day,unixtime1_key);
    }else if(control_vars.setup_weekend_status === '2' && (week_day==5 || week_day==6 || week_day==7)){
       display_price = wpestate_return_weekeend_price(week_day,unixtime1_key);
    }

    return     wpestate_booking_calendat_get_price(unixtime1_key,display_price) ;
}




/**
* Show weekend price
*
*
*
*
*/



var start_reservation,start_reservation_class,end_reservation,end_reservation_class,reservation_class;

function  wpestate_return_weekeend_price(week_day,unixtime1_key){
    display_price='';
    if(mega_details[unixtime1_key] !== undefined){
        if (  parseFloat (mega_details[unixtime1_key]['period_price_per_weekeend'],10)!==0 ){
            display_price = parseFloat (mega_details[unixtime1_key]['period_price_per_weekeend'],10);
        }
    }else if( parseFloat(price_per_weekeend,10)!==0 ){
        display_price = parseFloat(price_per_weekeend,10);
    }

    return display_price;

}

/**
*
*
*
*
*/

function wpestate_show_min_days_reservation(item){
    var step, minim_days ,classad,item_count;
    step=0;
    minim_days=0;


    if( mega_details[item] != undefined  ){
        minim_days = parseFloat( mega_details[item]['period_min_days_booking'] ,10 );
    }else if(min_days_booking !=undefined && min_days_booking>0){
        minim_days=parseFloat (min_days_booking,10);
    }

    classad='date'+item;
    item_count=parseFloat(item,10);


    if(minim_days>0){
        while(step<minim_days){
            step++;
            jQuery('.'+classad).addClass('minim_days_reservation');
            item_count = item_count+(24*60*60);//next day
            classad='date'+item_count;
        }

    }


}

/**
*
*
*
*
*/

function wpestate_remove_min_days_reservation(item){
    jQuery('.wpestate_calendar').removeClass('minim_days_reservation');
}




/**
* Show boooking costs
*
*
*
*
*/


function show_booking_costs() {

        var guest_fromone, guest_no, fromdate, todate, property_id, ajaxurl;
        ajaxurl             =   control_vars.admin_url + 'admin-ajax.php';
        property_id         =   jQuery("#listing_edit").val();

        fromdate            =   jQuery("#start_date").val();
        todate              =   jQuery("#end_date").val();



        if(jQuery('#start_hour_wrapper_list').length>0){ // we have per hour
          var fromdate_base=fromdate;

          if( jQuery('#start_hour_no_wrapper').attr('data-value')==='all' || jQuery('#end_hour_no_wrapper').attr('data-value')==='all' ){
            return;
          }

           fromdate= fromdate_base+" "  + jQuery('#start_hour_no_wrapper').attr('data-value');
           fromdate            =   wpestate_convert_selected_days(fromdate);
           todate  = fromdate_base+" "  + jQuery('#end_hour_no_wrapper').attr('data-value');
           todate              =   wpestate_convert_selected_days(todate);


        }else{

            fromdate            =   wpestate_convert_selected_days(fromdate);
            todate              =   wpestate_convert_selected_days(todate);
        }



        guest_no            =   parseInt( jQuery('#booking_guest_no_wrapper').attr('data-value'),10);
        
        if(jQuery('.booking_form_request .guest_no_hidden').length>0){
            guest_no            =   parseInt( jQuery('.booking_form_request .guest_no_hidden').val(),10);
        }
      
        jQuery('.cost_row_extra input').each(function(){
            jQuery(this).prop("checked", false);
        });


        if (fromdate === '' || todate === '') {
            jQuery('#show_cost_form').remove();
            return;
        }


        guest_fromone       =   parseInt( jQuery('#submit_booking_front').attr('data-guestfromone'),10);
        if (document.getElementById('submit_booking_front_instant')) {
            guest_fromone       =   parseInt( jQuery('#submit_booking_front_instant').attr('data-guestfromone'),10);
        }



        if( isNaN(guest_fromone) ){
            guest_fromone=0;
        }

        if(isNaN(guest_no)){
            guest_no=0;
        }


        if(guest_fromone===1 && guest_no<1 ){
            return;
        }


        jQuery('#booking_form_request_mess').empty().hide();
        if(fromdate>todate && todate!=='' ){
            jQuery('#booking_form_request_mess').show().empty().addClass('book_not_available').append(property_vars.nostart);
            jQuery('#show_cost_form').remove();
            return;
        }

        if(todate=='Invalid date'){
            return;
        }


       var nonce = jQuery('#wprentals_add_booking').val();
        jQuery.ajax({
            type: 'POST',
            url: ajaxurl,
            data: {
                'action'            :   'wpestate_ajax_show_booking_costs',
                'fromdate'          :   fromdate,
                'todate'            :   todate,
                'property_id'       :   property_id,
                'guest_no'          :   guest_no,
                'guest_fromone'     :   guest_fromone,
                'security'          :   nonce
            },
            success: function (data) {

                jQuery('#show_cost_form,.cost_row_instant').remove();
                jQuery('#add_costs_here').before(data);
                wpestate_redo_listing_sidebar();
            },
            error: function (errorThrown) {

            }
        });
}


/**
*
*
*
*
*/


jQuery(document).ready(function ($) {
    "use strict";

    wprentals_enable_fancybox_gallery();
    wprentals_show_mobile_booking();
    wpestate_redo_listing_sidebar();
    var today, booking_error;
    booking_error = 0;
    today = new Date();
    wpestare_booking_retrive_cookies();
    wpestate_contact_hosts_actions();
    wpestate_contact_owner_actions();
    wpestate_contact_client_actions();

    if( $('#listing_description').outerHeight() > 169 ){
        $('#view_more_desc').show();
    }

    //180
    var sidebar_padding=0;

    $('#view_more_desc').on('click',function(event){


        var new_margin = 0;
        if( $(this).hasClass('lessismore') ){

            $(this).text(property_vars.viewmore).removeClass('lessismore');

            $('#listing_description .panel-body').removeAttr('style');
            $('#listing_description .panel-body').css('max-height','129px');
            $('#listing_description .panel-body').css('overflow','hidden');
            wpestate_redo_listing_sidebar();
           

        }else{
            sidebar_padding=$('.listingsidebar').css('margin-top');

            $(this).text(property_vars.viewless).addClass('lessismore');
            $('#listing_description .panel-body').css('max-height','100%');
            $('#listing_description .panel-body').css('overflow','initial');

            if ( !jQuery('#primary').hasClass('listing_type_1') ){
                new_margin = $('.property_header').outerHeight() - 390;
                
                var current_padding = parseInt( $('#primary').css('margin-top'),10);
                current_padding=current_padding-new_margin;
                $('#primary').css('margin-top',current_padding+'px');
                   
            }

        }


    });


    ////////////////////////////////////////////////////////////////////////////
    /// tooltip property
    ////////////////////////////////////////////////////////////////////////////
    $('#listing_main_image_photo').bind('mousemove', function (e) {
        $('#tooltip-pic').css({'top': e.pageY, 'left': e.pageX, 'z-index': '1'});
    });
    setTimeout(function () {
        $('#tooltip-pic').fadeOut("fast");
    }, 10000);
    /////////////////////////////////////////////////////////////////////////////////////////
    // booking form calendars
    /////////////////////////////////////////////////////////////////////////////////////////


    wprentals_show_booking_calendars('start_date', 'end_date'); //booking form search

    wprentals_show_static_calendar();



    $('#end_date').change(function () {
        var prop_id=jQuery('#listing_edit').val();
        wpestate_setCookie('booking_prop_id_cookie',  prop_id , 1);
        wpestate_setCookie('booking_end_date_cookie',  $('#end_date').val() , 1);
        booking_started=1;
        show_booking_costs();
    });

    $('#start_date').change(function () {
        var prop_id=jQuery('#listing_edit').val();
        wpestate_setCookie('booking_prop_id_cookie',  prop_id , 1);
        wpestate_setCookie('booking_start_date_cookie',  $('#start_date').val() , 1);
        if( booking_started===1){
            show_booking_costs();
        }

    });

    $('#booking_guest_no_wrapper_list li').on('click',function (){
        var prop_id=jQuery('#listing_edit').val();
        wpestate_setCookie('booking_prop_id_cookie',  prop_id , 1);
        var booking_guest_no    =   parseInt( jQuery('#booking_guest_no_wrapper').attr('data-value') );
        wpestate_setCookie('booking_guest_cookie',  booking_guest_no , 1);

        if( booking_started===1 || jQuery('#start_hour_wrapper_list').length>0 ){
            show_booking_costs();
        }

    });



    $('#end_hour_wrapper_list li,#start_hour_wrapper_list li').on('click',function (){

        if(jQuery('#end_hour_input').val()===''){
          return;
        }

        if(jQuery('start_hour_input').val()===''){
          return;
        }

        show_booking_costs();

        wpestate_setCookie('booking_start_hour_cookie',  jQuery('#start_hour_no_wrapper').attr('data-value') , 1);
        wpestate_setCookie('booking_end_hour_cookie',jQuery('#end_hour_no_wrapper').attr('data-value') , 1);

    });




    function wpestare_booking_retrive_cookies(){

        var booking_guest_cookie        =   wpestate_getCookie( "booking_guest_cookie");
        var booking_start_date_cookie   =   wpestate_getCookie("booking_start_date_cookie");
        var booking_end_date_cookie     =   wpestate_getCookie("booking_end_date_cookie");
        var booking_prop_id             =   wpestate_getCookie("booking_prop_id_cookie");
        var booking_start_hour_cookie   =   wpestate_getCookie("booking_start_hour_cookie");
        var booking_end_hour_cookie     =   wpestate_getCookie("booking_end_hour_cookie");
        var prop_id                     =   jQuery('#listing_edit').val();


        var adult_guest_int             =   parseInt( wpestate_getCookie('adult_guest_int'),10);
        var children_guest_int          =   parseInt( wpestate_getCookie('children_guest_int'),10);
        var infant_guest_int            =   parseInt( wpestate_getCookie('infant_guest_int'),10);
        var label_to_return             =   wpestate_getCookie('label_to_return');
        var guest_no_hidden             =   parseInt( wpestate_getCookie('guest_no_hidden'),10);

        if(jQuery('#submit_booking_front').length>0){
            var  maxguest =parseInt( jQuery('#submit_booking_front').attr('data-maxguest') , 10);
            var maxoverload=parseInt( jQuery('#submit_booking_front').attr('data-max-overlooad') , 10);
        }
        if(jQuery('#submit_booking_front_instant').length>0){
            var  maxguest =parseInt( jQuery('#submit_booking_front_instant').attr('data-maxguest'),10);
            var  maxoverload=parseInt( jQuery('#submit_booking_front_instant').attr('data-max-overlooad') , 10);
        }
        
        maxguest=maxguest+maxoverload;


       

        if ( prop_id === booking_prop_id &&  property_vars.logged_in==="yes" ){
            if(booking_start_date_cookie!==''){
                jQuery('#start_date').val(booking_start_date_cookie);
            }

            if(booking_end_date_cookie!==''){
                jQuery('#end_date').val(booking_end_date_cookie);
            }

            if(booking_start_hour_cookie!==''){
                jQuery('#start_hour_no_wrapper').attr('data-value',booking_start_hour_cookie);
                jQuery('#start_hour_no_wrapper .text_selection').html(booking_start_hour_cookie);
            }


            if(booking_end_hour_cookie!==''){
                jQuery('#end_hour_no_wrapper').attr('data-value',booking_end_hour_cookie);
                jQuery('#end_hour_no_wrapper .text_selection').html(booking_end_hour_cookie);
            }



            if(booking_guest_cookie!==''){
                jQuery('#booking_guest_no_wrapper').attr('data-value',booking_guest_cookie);
                jQuery('#booking_guest_no_wrapper .text_selection').html(booking_guest_cookie+' '+property_vars.guests);
            }

            if(booking_start_date_cookie!=='' && booking_end_date_cookie!=='' && booking_guest_cookie!==''){
                booking_started=1;
                show_booking_costs();
            }
            
          
            
            
            
            if(adult_guest_int!==''){
                jQuery('.steper_value_adults').text(adult_guest_int);
                jQuery(( "input[name='adults_fvalue]" )).val(adult_guest_int);
                
            }
            
            if(children_guest_int!==''){
                jQuery('.steper_value_childs').text(children_guest_int);
                jQuery(( "input[name='childs_fvalue]" )).val(children_guest_int);
            }
            
            if(infant_guest_int!==''){
                jQuery('.steper_value_infants').text(infant_guest_int);
                jQuery(( "input[name='infants_fvalue]" )).val(infant_guest_int);
                
            }
            
            if(jQuery('.on_booking_control0').length == 0 ){
                var temp_total = adult_guest_int;
            }else{
                var temp_total = adult_guest_int + children_guest_int; 
            }
         
            
            
            if(temp_total>=maxguest){
                var section_wrapper = jQuery('.wpestate_guest_no_buttons');
                wpestate_block_unblock_plus_buttons(section_wrapper,'block');
            }
            
            
            if(label_to_return!==''){
                jQuery('.wpestate_guest_no_control_info').text(label_to_return);
            }
            
            if(guest_no_hidden!==''){
                jQuery('.booking_form_request .guest_no_hidden').val(guest_no_hidden);
            }
            
            if(booking_start_date_cookie!=='' && booking_end_date_cookie!=='' && guest_no_hidden!==''){
                booking_started=1;
                show_booking_costs();
            }
            
        

            if(jQuery('#start_hour_no_wrapper').length>0 && booking_start_date_cookie!=='' && booking_start_hour_cookie!=='' && booking_end_hour_cookie!==''  ){
              show_booking_costs();
            }
            


        }


    }





    $('#booking_form_request li').on('click',function (event){
        event.preventDefault();
        var guest_fromone, guest_overload;

        guest_overload      =   parseInt(jQuery('#submit_booking_front').attr('data-overload'),10);
        guest_fromone       =   parseInt( jQuery('#submit_booking_front').attr('data-guestfromone'),10);
        if (document.getElementById('submit_booking_front_instant')) {
            guest_overload      =   parseInt(jQuery('#submit_booking_front_instant').attr('data-overload'),10);
            guest_fromone       =   parseInt( jQuery('#submit_booking_front_instant').attr('data-guestfromone'),10);
        }

        if( ( guest_overload===1 &&  booking_started===1) || guest_fromone===1 ){
            show_booking_costs();
        }
    });




    /////////////////////////////////////////////////////////////////////////////////////////
    // extra expenses front
    /////////////////////////////////////////////////////////////////////////////////////////

    $('.cost_row_extra input').on('click',function(){
        var key_to_add,row_to_add,total_value,value_to_add,value_how,value_name,parent,fromdate,todate,listing_edit,booking_guest_no,cost_value_show;

        parent= $(this).parent().parent();
        value_to_add    =   parseFloat( parent.attr('data-value_add') );
        value_to_add    =   parseFloat( wpestate_booking_form_currency_convert(value_to_add) );

        value_how           =   parseFloat ( parent.attr('data-value_how') );
        value_name          =   parent.attr('data-value_name');
        key_to_add          =   jQuery(this).attr('data-key');
        fromdate            =   wpestate_convert_selected_days( jQuery("#start_date").val() );
        todate              =   wpestate_convert_selected_days( jQuery("#end_date").val() );


        if(jQuery('#start_hour_wrapper_list').length>0){ // we have per hour
            var  fromdate            =   jQuery("#start_date").val();
            var fromdate_base=fromdate;

            if( jQuery('#start_hour_no_wrapper').attr('data-value')==='all' || jQuery('#end_hour_no_wrapper').attr('data-value')==='all' ){
              return;
            }
            fromdate= fromdate_base+" "  + jQuery('#start_hour_no_wrapper').attr('data-value');
            fromdate            =   wpestate_convert_selected_days(fromdate);
            todate  = fromdate_base+" "  + jQuery('#end_hour_no_wrapper').attr('data-value');
            todate              =   wpestate_convert_selected_days(todate);
        }



        listing_edit        =   jQuery('#listing_edit').val();
        booking_guest_no    =   parseInt( jQuery('#booking_guest_no_wrapper').attr('data-value') );
        if(jQuery('.booking_form_request').length>0){
            booking_guest_no            =   parseInt( jQuery('.booking_form_request .guest_no_hidden').val(),10);
        }

        cost_value_show     =   parent.find('.cost_value_show').text();
        var firstDate   =   new Date(fromdate);
        var secondDate  =   new Date(todate);
        var oneDay      =   24*60*60*1000;
        if(property_vars.book_type==='2'){
            oneDay=60*60*1000; // one hour
        }

        var diffDays    =   Math.round(Math.abs((firstDate.getTime() - secondDate.getTime())/(oneDay)));



        var total_curent    =   parseFloat( $('#total_cost_row .cost_value').attr('data_total_price') );
        total_curent        =   parseFloat( wpestate_booking_form_currency_convert(total_curent) );
        if(booking_guest_no === 0 || isNaN(booking_guest_no)){
            booking_guest_no=1;
        }




        if( ($(this).is(":checked")) ){

            if(value_how===0){
                total_value = value_to_add;
            }else if( value_how === 1 ){
                total_value = value_to_add * diffDays;
            }else if( value_how === 2 ){
                total_value = value_to_add * booking_guest_no;
            }else if( value_how === 3 ){
                total_value = value_to_add * diffDays*booking_guest_no;
            }

            row_to_add='<div class="cost_row" id="'+estate_makeSafeForCSS(value_name)+'" data-added="'+total_value+'"><div class="cost_explanation">'+value_name+'</div><div class="cost_value">'+estate_format_number_with_currency( total_value.toFixed(2) )+'</div></div>';
            $('#total_cost_row').before(row_to_add);

            var new_early_bird_before_convert;
            var new_early_bird  =   parseFloat( $('#early_bird_discount').attr('data-early-bird') );
            new_early_bird      =   parseFloat( wpestate_booking_form_currency_convert(new_early_bird) );

            if( isNaN(new_early_bird) ||new_early_bird === 0){
                new_early_bird=0;
            }
            new_early_bird.toFixed(2);





            total_curent    =   total_curent    +   new_early_bird;
            total_curent    =   total_curent    +   total_value;
            if(new_early_bird !==0){
                new_early_bird  =   new_early_bird  +    total_value * property_vars.early_discount/100;

                var new_early_bird_before_convert =  wpestate_booking_form_currency_convert_back(new_early_bird);
                new_early_bird.toFixed(2);
            }


            total_curent    =   total_curent    -   new_early_bird;
            total_curent    =   total_curent.toFixed(2);

            var  total_curent_deposit=total_curent;

            if(control_vars.include_expeses==='no'){


                var cleaning_fee=parseFloat ( $('.cleaning_fee_value').attr('data_cleaning_fee') );
                cleaning_fee.toFixed(2);
                var city_fee=parseFloat ( $('.city_fee_value').attr('data_city_fee') );
                city_fee.toFixed(2);

                if(isNaN(city_fee)){
                    city_fee=0;
                }
                if(isNaN(cleaning_fee)){
                    cleaning_fee=0;
                }

                total_curent_deposit=total_curent_deposit-cleaning_fee-city_fee;
                total_curent_deposit.toFixed(2);
            }



            $('#total_cost_row .cost_value').text( estate_format_number_with_currency( total_curent ) );
            var total_curent_before_convert = wpestate_booking_form_currency_convert_back(total_curent);

            $('#total_cost_row .cost_value').attr('data_total_price',total_curent_before_convert);
            var new_depozit =   wpestate_instant_book_depozit(total_curent_deposit);
            var new_balance =   total_curent-new_depozit;
            $('.instant_depozit_value').text(estate_format_number_with_currency( new_depozit.toFixed(2) ) );
            $('.instant_balance_value').text(estate_format_number_with_currency( new_balance.toFixed(2) ) );
            $('#early_bird_discount').text(estate_format_number_with_currency( new_early_bird.toFixed(2) ) );
            $('#early_bird_discount').attr('data-early-bird',new_early_bird_before_convert);


        } else{
            value_name           =  estate_makeSafeForCSS(value_name);
            var remove_row_value =  parseFloat( $('#'+value_name).attr('data-added') );


            $('#'+value_name).remove();

            var new_early_bird =   parseFloat( $('#early_bird_discount').attr('data-early-bird') );
            if( isNaN(new_early_bird) ||new_early_bird === 0){
                new_early_bird=0;
            }
            var new_early_bird_before_convert =  wpestate_booking_form_currency_convert(new_early_bird);
            new_early_bird.toFixed(2);

            total_curent    =   total_curent    +   new_early_bird_before_convert;
            total_curent    =   total_curent    -   remove_row_value;

            if(new_early_bird !==0){
                new_early_bird  =   new_early_bird_before_convert  -   remove_row_value * property_vars.early_discount/100;
                new_early_bird_before_convert =  wpestate_booking_form_currency_convert_back(new_early_bird);
                new_early_bird.toFixed(2);

            }


            total_curent    =   total_curent    -   new_early_bird;


            total_curent = total_curent.toFixed(2);

             var  total_curent_deposit=total_curent;
            if(control_vars.include_expeses==='no'){
                var cleaning_fee=parseFloat ( $('.cleaning_fee_value').attr('data_cleaning_fee') );
                cleaning_fee = isNaN(cleaning_fee) ? 0 : cleaning_fee;
                var city_fee=parseFloat ( $('.city_fee_value').attr('data_city_fee') );
                city_fee = isNaN(city_fee) ? 0 : city_fee;
                total_curent_deposit=total_curent_deposit-cleaning_fee-city_fee;
                total_curent_deposit.toFixed(2);
            }


            $('#total_cost_row .cost_value').text( estate_format_number_with_currency (total_curent) );
            var total_curent_before_convert = wpestate_booking_form_currency_convert_back(total_curent);
            $('#total_cost_row .cost_value').attr('data_total_price',total_curent_before_convert);

            var new_depozit =   wpestate_instant_book_depozit(total_curent_deposit);
            var new_balance =   total_curent-new_depozit;

            $('.instant_depozit_value').text(estate_format_number_with_currency(new_depozit) );
            $('.instant_balance_value').text(estate_format_number_with_currency(new_balance) );
            $('#early_bird_discount').text(estate_format_number_with_currency(new_early_bird) );
            $('#early_bird_discount').attr('data-early-bird',new_early_bird_before_convert);
        }
        wpestate_redo_listing_sidebar();

    });


    /*
    *
    *
    *
    *
    *
    */

    function wpestate_instant_book_depozit(total_price){
        var deposit=0;


        if (  control_vars.wp_estate_book_down_fixed_fee === '0') {

            if(control_vars.wp_estate_book_down === '' || control_vars.wp_estate_book_down === '0'){
                deposit                =   0;
            }else{
                deposit                =  control_vars.wp_estate_book_down*total_price/100;
            }
        }else{
            deposit = control_vars.wp_estate_book_down_fixed_fee;
        }
        return deposit;

    }



    /*
    *
    *
    *
    *
    *
    */


function wpestate_booking_form_currency_convert(display_price){
    var return_price;
    return_price ='';


    if (!isNaN(my_custom_curr_pos) && my_custom_curr_pos !== -1) { // if we have custom curency
        return_price =     ( display_price * my_custom_curr_coef) ;
        return return_price;
    }else{
        return display_price;
    }



}

/*
*
*
*
*
*
*/


function wpestate_booking_form_currency_convert_back(display_price){
    var return_price;
    return_price ='';

    if (!isNaN(my_custom_curr_pos) && my_custom_curr_pos !== -1) { // if we have custom curency
        return_price =     ( display_price / my_custom_curr_coef) ;
        return return_price;
    }else{
        return display_price;
    }



}






    /////////////////////////////////////////////////////////////////////////////////////////
    // submit booking front
    /////////////////////////////////////////////////////////////////////////////////////////
    $('#submit_booking_front,#submit_booking_front_instant').on('click',function (event) {
        event.preventDefault();
        var button=jQuery(this);
        button.val(property_vars.processing);
        button.prop('disabled', true);
        var wrapper_button = jQuery(this).parents().eq(2);

        var scroll_val =$('#booking_form_request').offset().top -100;
        $("html, body").animate({ scrollTop: scroll_val}, 400);

        if(property_vars.logged_in==="no"){
            $('#booking_form_request_mess').show().empty().addClass('book_not_available').append(property_vars.notlog);

        }

        var guest_number, guest_overload,guestfromone,max_guest;
        if (!check_booking_form()  || booking_error === 1) {
    
            wprentals_restore_button(button);
            return;
        }

        guest_number = jQuery('#booking_guest_no_wrapper').attr('data-value');
        guest_number = parseInt(guest_number,10);
        
        if(jQuery('.guest_no_hidden').length>0){
            guest_number            =   parseInt( wrapper_button.find('.guest_no_hidden').val(),10);
        }


        if (isNaN(guest_number)){
            guest_number=0;
        }


        if(property_vars.rental_type==='1'){
            guest_number=1;
        }

        max_guest       =   parseInt  (jQuery('#submit_booking_front').attr('data-maxguest'),10);
        guest_overload  =   parseInt  (jQuery('#submit_booking_front').attr('data-overload'),10);
        guestfromone    =   parseInt  (jQuery('#submit_booking_front').attr('data-guestfromone'),10);


        if (document.getElementById('submit_booking_front_instant')) {
            max_guest       =   parseInt  (jQuery('#submit_booking_front_instant').attr('data-maxguest'),10);
            guest_overload  =   parseInt  (jQuery('#submit_booking_front_instant').attr('data-overload'),10);
            guestfromone    =   parseInt  (jQuery('#submit_booking_front_instant').attr('data-guestfromone'),10);
        }


        if (guestfromone===1 && guest_number < 1){
            $('#booking_form_request_mess').show().empty().addClass('book_not_available').append(property_vars.noguest);
            wprentals_restore_button(button);
            return;
        }


        if(guest_number < 1){
            $('#booking_form_request_mess').show().empty().addClass('book_not_available').append(property_vars.noguest);
            wprentals_restore_button(button);
            return;
        }

        if(guest_overload===0 && guest_number>max_guest){
            $('#booking_form_request_mess').show().empty().addClass('book_not_available').append(property_vars.guestoverload+max_guest+' '+property_vars.guests);
            wprentals_restore_button(button);
            return;
        }

        var is_woo=0;
        if(jQuery('#submit_booking_front_instant').length>0 && property_vars.is_woo==='yes' ){
           // wprentals_restore_button(button);
            is_woo=1;
        }

        if(jQuery('#start_hour_wrapper_list').length>0){ // we have per hour
            var  fromdate            =   jQuery("#start_date").val();
            var fromdate_base=fromdate;

            if( jQuery('#start_hour_no_wrapper').attr('data-value')==='all' || jQuery('#end_hour_no_wrapper').attr('data-value')==='all' ){
              return;
            }
            fromdate= fromdate_base+" "  + jQuery('#start_hour_no_wrapper').attr('data-value');
            fromdate            =   wpestate_convert_selected_days(fromdate);
            var todate  = fromdate_base+" "  + jQuery('#end_hour_no_wrapper').attr('data-value');
            todate              =   wpestate_convert_selected_days(todate);
            if(fromdate>todate && todate!=='' ){
                   jQuery('#booking_form_request_mess').show().empty().addClass('book_not_available').append(property_vars.nostart);
                   jQuery('#show_cost_form').remove();
                   wprentals_restore_button(button);
                   return;
               }
        }

        if(property_vars.logged_in==="no"  && is_woo===0){
            $('#booking_form_request_mess').show().empty().addClass('book_not_available').append(property_vars.notlog);
            login_modal_type=3;
            wpestate_show_login_form(1, 9, 0);
            wprentals_restore_button(button);

        }else{
            $('#booking_form_request_mess').show().empty().removeClass('book_not_available').append(property_vars.sending);
            wpestate_redo_listing_sidebar();
            wprentals_check_booking_valability(button);
        }

    });




    function wprentals_restore_button(button){
      if(button.attr('id')==='submit_booking_front_instant'){
        button.val(property_vars.instant_booking);
      }else{
          button.val(property_vars.book_now);
      }
      button.prop('disabled', false);
    }






    function check_booking_form() {
        var book_from, book_to;
        book_from         =   $("#start_date").val();
        book_to           =   $("#end_date").val();

        if (book_from === '' || book_to === '') {
            $('#booking_form_request_mess').empty().addClass('book_not_available').show().append(property_vars.plsfill);
            return false;
        } else {
            return true;
        }
    }






/// end jquery
});



/**
*
*
*
*
*/
function wpestate_show_contact_owner_form(booking_id, agent_id) {
    var  ajaxurl;
    ajaxurl     =   ajaxcalls_vars.admin_url + 'admin-ajax.php';
    jQuery('#contact_owner_modal').modal();
    enable_actions_modal_contact();

}

/**
* contact host actions
*
*
*
*/

function wpestate_contact_hosts_actions(){
    jQuery('#contact_host,#contact_me_long,.contact_owner_reservation').on('click',function () {
        var booking_id, agent_id,property_id;
        agent_id    =   0;
        booking_id  =   jQuery(this).attr('data-postid');
        property_id =   jQuery(this).attr('data-bookid');
        jQuery('#submit_mess_front').attr('data-property_id',property_id);
        wpestate_show_contact_owner_form(booking_id, agent_id);
    });
}

/**
* contact client actions
*
*
*
*/
function wpestate_contact_client_actions(){
    jQuery('.contact_client_reservation').on('click',function () {
        var booking_id, agent_id,property_id;
        agent_id    =   0;
        booking_id  =   jQuery(this).attr('data-bookid');     
        jQuery('#submit_message_to_client_dashboard').attr('data-bookid',booking_id); 
        wpestate_show_contact_owner_form(booking_id, agent_id);
    });
}


/**
*  contact owner
*
*
*
*/
function wpestate_contact_owner_actions(){
    jQuery('#contact_me_long_owner').on('click',function () {
        var agent_id, booking_id;
        booking_id =   0;
        agent_id  =   jQuery(this).attr('data-postid');
        wpestate_show_contact_owner_form(booking_id, agent_id);
    });
}



/*
*  Modal for contact actions
*
*
*
*
*/

function enable_actions_modal_contact() {

    wpestate_sending_message_to_client();

        jQuery('#contact_owner_modal').on('hidden.bs.modal', function (e) {
            jQuery('#contact_owner_modal').hide();
        });
        var today =new Date().getTime();

        jQuery("#booking_from_date").change(function () {
            var  prev_date = new Date(jQuery('#booking_from_date').val());
            var read_in_date = jQuery("#booking_from_date").val();
            prev_date = wpestate_convert_selected_days_simple_add_days(read_in_date,1);


            jQuery("#booking_to_date").datepicker("destroy");
            jQuery("#booking_to_date").datepicker({
                minDate: prev_date
            }, jQuery.datepicker.regional[control_vars.datepick_lang]);
        });


        jQuery('#submit_mess_front').on('click',function (event) {
            event.preventDefault();
            var button,ajaxurl,phone_no, subject, booking_from_date, booking_to_date, booking_guest_no, message, nonce, agent_property_id, agent_id;
            button                  =   jQuery(this);
            button.val(property_vars.processing);
            button.text(property_vars.processing);
            button.prop('disabled', true);
            ajaxurl                 =   control_vars.admin_url + 'admin-ajax.php';
            booking_from_date       =   jQuery("#booking_from_date").val();
            booking_to_date         =   jQuery("#booking_to_date").val();
            booking_guest_no        =   jQuery("#booking_guest_no").val();
            message                 =   jQuery("#booking_mes_mess").val();
            agent_property_id       =   jQuery("#agent_property_id").val();
            agent_id                =   jQuery('#agent_id').val();
            phone_no                =   jQuery('#booking_phone_no').val();
            nonce                   =   jQuery("#security-register-mess-front").val();

            var contact_u_email     =   jQuery("#contact_u_email").val();
            var contact_u_name      =   jQuery("#contact_u_name").val();

            if (subject === '' || message === ''|| (  jQuery('#booking_phone_no').length>0 && phone_no=='')  ) {
                jQuery("#booking_form_request_mess_modal").empty().addClass('book_not_available').append(property_vars.plsfill);
                button.val(property_vars.send_mess);
                button.text(property_vars.send_mess);
                button.prop('disabled', false);
                return;
            }
            if( property_vars.logged_in!=="yes" && ( contact_u_email === '' || contact_u_name === '')) {
                jQuery("#booking_form_request_mess_modal").empty().addClass('book_not_available').append(property_vars.plsfill);
                button.val(property_vars.send_mess);
                button.text(property_vars.send_mess);
                button.prop('disabled', false);
                return;
            }


            if(agent_property_id==0 && jQuery('.contact_owner_reservation').length>0 ){
                agent_property_id=jQuery(this).attr('data-property_id');
                agent_id=0;
            }


            var nonce = jQuery('#wprentals_submit_mess_front_nonce').val();

            if(property_vars.use_gdpr==='yes'){
            if ( !jQuery('#wpestate_agree_gdpr').is(':checked') ){
                jQuery("#booking_form_request_mess_modal").empty().addClass('book_not_available').append(property_vars.gdpr_terms);
                button.val(property_vars.send_mess);
                button.text(property_vars.send_mess);
                button.prop('disabled', false);
                return;
            }
        }


            jQuery.ajax({
                type: 'POST',
                url: ajaxurl,
                data: {
                    'action'            :   'wpestate_mess_front_end',
                    'message'           :   message,
                    'booking_guest_no'  :   booking_guest_no,
                    'booking_from_date' :   booking_from_date,
                    'booking_to_date'   :   booking_to_date,
                    'agent_property_id' :   agent_property_id,
                    'agent_id'          :   agent_id,
                    'contact_u_email'   :   contact_u_email,
                    'contact_u_name'    :   contact_u_name,
                    'phone_no'          :   phone_no,
                    'security'          :   nonce
                },
                success: function (data) {

                    jQuery("#booking_form_request_mess_modal").empty().removeClass('book_not_available').append(data);
                    setTimeout(function () {
                        jQuery('#contact_owner_modal').modal('hide');

                            // reset contact form
                            button.val(property_vars.send_mess);
                            button.text(property_vars.send_mess);
                            button.prop('disabled', false);
                            jQuery("#contact_u_email").val('');
                            jQuery("#contact_u_name").val('');
                            jQuery("#booking_from_date").val('');
                            jQuery("#booking_to_date").val('');
                            jQuery("#booking_guest_no").val('1');
                            jQuery("#booking_mes_mess").val('');
                            jQuery('#booking_phone_no').val('');
                            if(jQuery('#contact_for_reservation').length===0){
                                jQuery('#submit_mess_front').unbind('click');
                                jQuery("#booking_form_request_mess_modal").empty();
                            }else{

                            }

                    }, 2000);



                },
                error: function (errorThrown) {

                }

            });
        });
}



/*
*  send message to client from dasboard
*
*
*
*
*/

function wpestate_sending_message_to_client(){

    jQuery('#submit_message_to_client_dashboard').on('click',function(event)  {
        var button,ajaxurl,bookingID, message, nonce;
        button                  =   jQuery(this);
        button.val(property_vars.processing);
        button.text(property_vars.processing);
        button.prop('disabled', true);
        ajaxurl                 =   control_vars.admin_url + 'admin-ajax.php';
        message                 =   jQuery("#booking_mes_mess").val();
        nonce                   =   jQuery("#security-register-mess-front").val();

        bookingID       =   button.attr('data-bookid');

        if ( message === ''  ) {
            jQuery("#booking_form_request_mess_modal").empty().addClass('book_not_available').append(property_vars.plsfill);
            button.val(property_vars.send_mess);
            button.text(property_vars.send_mess);
            button.prop('disabled', false);
            return;
        }
       

        var nonce = jQuery('#wprentals_submit_mess_front_nonce').val();

        jQuery.ajax({
            type: 'POST',
            url: ajaxurl,
            dataType:'json',
            data: {
                'action'            :   'wpestate_message_client_dashboard',
                'message'           :   message,
                'bookingID'         :   bookingID,
                'security'          :   nonce
            },
            success: function (data) {

                jQuery("#booking_form_request_mess_modal").empty().removeClass('book_not_available').append(data);
                setTimeout(function () {
                    jQuery('#contact_owner_modal').modal('hide');

                        // reset contact form
                        button.val(property_vars.send_mess);
                        button.text(property_vars.send_mess);
                        button.prop('disabled', false);
                        jQuery("#booking_mes_mess").val('');
                     

                }, 2000);



            },
            error: function (errorThrown) {

            }

        });

    });

}














/*
*
*
*
*
*
*/

function estate_format_number_with_currency(number){

    if (!isNaN(my_custom_curr_pos) && my_custom_curr_pos !== -1){
        if (my_custom_curr_cur_post === 'before') {
            return  ( my_custom_curr_symbol2 ) +" "+number;
        }else{
            return number+" "+ ( my_custom_curr_symbol2 );
        }
    }else{
        if( control_vars.where_curency==='before'){
            return control_vars.curency+" "+number;
        }else{
            return number+" "+control_vars.curency;
        }
    }


}


/*
*
*
*
*
*
*/

function estate_makeSafeForCSS(name) {
    return name.replace(/[^a-z0-9]/g, function(s) {
        var c = s.charCodeAt(0);
        if (c == 32) return '-';
        if (c >= 65 && c <= 90) return '_' + s.toLowerCase();
        return '__' + ('000' + c.toString(16)).slice(-4);
    });
}

/*
*
*
*
*
*
*/

function wpestate_setCookie(cname, cvalue, exdays) {
    var d = new Date();
    d.setTime(d.getTime() + (exdays*24*60*60*1000));
    var expires = "expires="+ d.toUTCString();
    document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
}

/*
*
*
*
*
*
*/

function wpestate_getCookie(cname) {
    var name = cname + "=";
    var decodedCookie = decodeURIComponent(document.cookie);
    var ca = decodedCookie.split(';');
    for(var i = 0; i <ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0) == ' ') {
            c = c.substring(1);
        }
        if (c.indexOf(name) == 0) {
            return c.substring(name.length, c.length);
        }
    }
    return "";
}



/*
*
*
*
*
*
*/


function wprentals_show_static_calendar(){

    if( jQuery("#all-front-calendars_per_hour").length >0){
        var  today = new Date();
        var start_temp='';
        var listing_book_dates=[];

        for (var key in booking_array) {
            if (booking_array.hasOwnProperty(key) && key!=='') {
                var temp_book=[];
                temp_book['title']     =   property_vars.reserved;
                temp_book ['start']    =   moment.unix(key).utc().format();
                temp_book ['end']      =   moment.unix( booking_array[key]).utc().format();
                temp_book ['editable'] =   false;
                listing_book_dates.push(temp_book);
            }
        }



  var calendarEl = document.getElementById('all-front-calendars_per_hour');
  var calendar = new FullCalendar.Calendar(calendarEl, {
            //defaultView: 'agendaWeek',
             initialView: 'timeGridWeek',

            navLinks: false,
          //  defaultDate: today,
            selectable:false,
          //  selectHelper:true,
            selectOverlap :false,

            height: 'auto',
            slotDuration:'01:00:00',
            allDayText:property_vars.hours,
            weekNumbers: false,
          //  weekNumbersWithinDays: true,
            weekNumberCalculation: 'ISO',
            editable: false,
          //  eventLimit: true,
            unselectAuto:false,
            isRTL:control_vars_property.rtl_book_hours_calendar,
          /*  header: {
                left: 'prev,next today',
                center: 'title',
                right: ''
            },*/

            slotMinTime:control_vars_property.booking_start_hour,
            slotMaxTime:control_vars_property.booking_end_hour,
            businessHours:{
              // days of week. an array of zero-based day of week integers (0=Sunday)
              daysOfWeek: [ 0,1, 2, 3, 4 ,5,6,7], // Monday - Thursday

              startTime: control_vars_property.booking_start_hour, // a start time (10am in this example)
              endTime: control_vars_property.booking_end_hour, // an end time (6pm in this example)
            },
             timeZone: 'UTC',
            events: listing_book_dates
         });

         if( typeof(wprentals_per_hour_locale_vars)!=='undefined' && typeof(wprentals_per_hour_locale_vars.date_lang_status)!=='undefined'){
           calendar.setOption('locale', wprentals_per_hour_locale_vars.date_lang_status);
         }


         calendar.render();


    }
}


/*
*
*
* New Book per hour
*
*
*/
function check_in_out_enable_calendar_per_hour_new(in_date, out_date){
    "use strict";
    //wprentals_is_per_hour

    var today, prev_date,read_in_date,date_format,calendar_opens;
    today           =   new Date();
    date_format     =   control_vars.date_format.toUpperCase();
    today           =   moment(today).format("MM/DD/YYYY");
    show_daterange_picker_custom_mess="no";
    calendar_opens  =   'left';
    if(jQuery('#primary').hasClass('col-md-pull-8')){
        calendar_opens  =   'right';
    }
    jQuery("#" + in_date).attr('readonly','readonly');

    var options = {
            opens:calendar_opens,
            singleDatePicker: true,
            autoApply: true,
            alwaysShowCalendars: true,
            autoUpdateInput: false,
            minDate:today,
            startDate :today,
            locale:{
                daysOfWeek:dayNamesShort,
                monthNames:longmonths
            },
          //  isCustomDate:wpestate_booking_show_booked,
        };

        var date_format     = control_vars.date_format.toUpperCase();
        date_format=date_format.replace("YY", "YYYY");

        var in_date_front   = jQuery('#' + in_date);
        var out_date_front  = jQuery('#' + out_date);




    var calendar= jQuery("#" + in_date).daterangepicker(
        options,
        function (start, end, label) {
            var start_date  =                 start.format(date_format);
            in_date_front.val(start_date);
            show_booking_costs();
            var prop_id=jQuery('#listing_edit').val();
            wpestate_setCookie('booking_prop_id_cookie',  prop_id , 1);
            wpestate_setCookie('booking_start_date_cookie',  jQuery('#start_date').val() , 1);

        }
    );


}





function check_in_out_enable_calendar_per_hour_old(in_date, out_date){
    "use strict";
    //wprentals_is_per_hour

    jQuery("#" + out_date).removeAttr('disabled');
    jQuery("#" + in_date).on('click',function(){
        jQuery('#book_per_hour_wrapper').show();
        wprentals_show_Calendar(in_date, out_date);
        jQuery('#book_per_hour_calendar').fullCalendar('render');
    });

    jQuery("#" + out_date).on('click',function(){
        jQuery('#book_per_hour_wrapper').show();
        wprentals_show_Calendar(in_date, out_date);
        jQuery('#book_per_hour_calendar').fullCalendar('render');
    });

    jQuery('#book_per_hour_close').on('click',function(){
        jQuery('#book_per_hour_wrapper').hide();
    });

     jQuery('#per_hour_ok').on('click',function(){
        jQuery('#book_per_hour_wrapper').hide();
        show_booking_costs();
    });


    jQuery('#per_hour_cancel').on('click',function(){
        jQuery('#book_per_hour_wrapper').hide();
        jQuery("#" + in_date).val('');
        jQuery("#" + out_date).val('');
    });


    var today, prev_date,read_in_date;
    jQuery("#" + in_date+',#'+out_date).blur();



}





window.wprentals_mobilecheck = function() {
  var check = false;
  (function(a){if(/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i.test(a)||/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i.test(a.substr(0,4))) check = true;})(navigator.userAgent||navigator.vendor||window.opera);
  return check;
};

function wprentals_show_Calendar(in_date,out_date){
    var  today = new Date();
    var start_temp='';
    var listing_book_dates=[];

    for (var key in booking_array) {
        if (booking_array.hasOwnProperty(key) && key!=='') {
            var temp_book=[];
            temp_book['title']     =   property_vars.reserved;
            temp_book ['start']    =   moment.unix(key).utc().format(),
            temp_book ['end']      =   moment.unix( booking_array[key]).utc().format(),
            temp_book ['editable'] =   false;
            listing_book_dates.push(temp_book);
        }
    }

    //minTime, maxTime ,  isRTL: true .   'locale':control_vars.datepick_lang,
    jQuery("#book_per_hour_calendar").fullCalendar({
        defaultView: 'agendaWeek',
        defaultDate: today,
        selectable:true,
        selectHelper:true,
        selectOverlap :false,
        footer:false,
        slotDuration:'01:00:00',
        validRange: {
            start: today,
            end: '2025-06-01'
        },
        allDayText:property_vars.hours,
        allDay :false,
        forceEventDuration:true,
        defaultTimedEventDuration:'05:00:00',
        navLinks: false,
        weekNumbers: false,
        weekNumbersWithinDays: false,
        weekNumberCalculation: 'ISO',
        editable: true,
        eventLimit: true,
        unselectAuto:false,
        nowIndicator :true,
        defaultEventMinutes :200,
        isRTL:control_vars_property.rtl_book_hours_calendar,
        longPressDelay :100,
        eventLongPressDelay:100,
        selectLongPressDelay:100,
        header: {
            left: 'prev,next today',
            center: 'title',
            right: ''
        },
        minTime:control_vars_property.booking_start_hour,
        maxTime:control_vars_property.booking_end_hour,






        dayRender:function( date, cell ) {

            if (date.isSame(today, "day")) {
                cell.attr("xday", "meday");
            }
        },



        dayClick: function(date, jsEvent, view) {

         },
        select:function( start, end, jsEvent, view ){
            end             =   moment(end);
            var min_hours   =   parseFloat( control_vars_property.min_days_booking);

            var start_of_day=( moment(start).startOf('day') )/1000;
            if( mega_details[start_of_day] !== undefined){
                min_hours=  mega_details[start_of_day]['period_min_days_booking'];
            }

            var should_end  =   moment(start).add(min_hours, 'hours');




            if(should_end>end){
               end = should_end;
            }

            jQuery('#book_per_hour_calendar').fullCalendar('unselect');
            jQuery('#book_per_hour_calendar').fullCalendar('removeEvents','rentals_custom_book_initial');

            jQuery('#book_per_hour_calendar').fullCalendar('renderEvent',{
                id:'rentals_custom_book_initial',
                start: start,
                end: end,
                allDay: false,
                editable:false,
                },
                true // stick the event
           );



            jQuery('#book_per_hour_calendar').fullCalendar('removeEvents','rentals_custom_book');
            var date_format = control_vars.date_format.toUpperCase()+" HH:mm";
            var new_date_format=date_format.replace("YY", "YYYY");

            jQuery("#" + in_date).val(start.format(new_date_format));
            jQuery("#" + out_date).val(end.format(new_date_format));
            jQuery('.fc-center .hour_selection').empty().html(start.format(new_date_format)+' to '+end.format(new_date_format));
            var prop_id=jQuery('#listing_edit').val();
            wpestate_setCookie('booking_prop_id_cookie',  prop_id , 1);
            wpestate_setCookie('booking_start_date_cookie',  start.format(new_date_format) , 1);
            wpestate_setCookie('booking_end_date_cookie', end.format(new_date_format) , 1);
            booking_started=1;



        },



        events: listing_book_dates
    });

    jQuery('.fc-center .hour_selection').remove();
    jQuery('#book_per_hour .fc-center').append('<div class="hour_selection">'+property_vars.clickandragtext+'</div>');
}
