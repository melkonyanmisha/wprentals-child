<?php
$current_user               =   wp_get_current_user();
$user_custom_picture        =   get_the_author_meta( 'small_custom_picture' , $current_user->ID  );
$user_small_picture_id      =   get_the_author_meta( 'small_custom_picture' , $current_user->ID  );
if( $user_small_picture_id == '' ){
    $user_small_picture[0]=get_stylesheet_directory_uri().'/img/default_user_small.png';
}else{
    $user_small_picture=wp_get_attachment_image_src($user_small_picture_id,'wpestate_user_thumb'); 
}


global $wpestate_global_payments;

if(is_user_logged_in()){ ?>
    <div class="user_menu user_loged" id="user_menu_u">
        <?php
        if(  class_exists( 'WooCommerce' ) ){
            $wpestate_global_payments->show_cart_icon();
        }
        ?>
        
        <div class="menu_user_picture" style="background-image: url('<?php print esc_url($user_small_picture[0]); ?>');"></div>
        <a class="menu_user_tools dropdown" id="user_menu_trigger" data-toggle="dropdown">    
        <?php
        echo '<span class="menu_username">'.ucwords($current_user->user_login).'</span>'; 
        ?>   <i class="fas fa-caret-down"></i> 
         </a>
       
<?php }else{ ?>
    <div class="user_menu" id="user_menu_u">   
        <?php
        if(  class_exists( 'WooCommerce' ) ){
            $wpestate_global_payments->show_cart_icon();
        }
        ?>
        
        <div class="signuplink" id="topbarlogin"><?php esc_html_e('Login','wprentals');?></div>
<!--        <div class="signuplink" id="topbarregister">--><?php //esc_html_e('Sign Up','wprentals')?><!--</div>    -->
        <?php if(  esc_html ( wprentals_get_option('wp_estate_show_submit','') ) ==='yes'){ ?>
            <a href="<?php print wpestate_get_template_link('user_dashboard_add_step1.php');?>" id="submit_action"><?php esc_html_e('Submit Property','wprentals');?></a>
        <?php } ?>                   
       
<?php } ?>                  
    </div> 
     
<?php 

if ( 0 != $current_user->ID  && is_user_logged_in() ) {
    $username               =   $current_user->user_login ;
    $userID                 =   $current_user->ID;
    $add_link               =   wpestate_get_template_link('user_dashboard_add_step1.php');
    $dash_profile           =   wpestate_get_template_link('user_dashboard_profile.php');
    $dash_favorite          =   wpestate_get_template_link('user_dashboard_favorite.php');
    $dash_link              =   wpestate_get_template_link('user_dashboard.php');
    $dash_searches          =   wpestate_get_template_link('user_dashboard_searches.php'); 
    $dash_reservation       =   wpestate_get_template_link('user_dashboard_my_reservations.php');
    $dash_bookings          =   wpestate_get_template_link('user_dashboard_my_bookings.php');
    $dash_reviews           =   wpestate_get_template_link('user_dashboard_my_reviews.php');
    $dash_inbox             =   wpestate_get_template_link('user_dashboard_inbox.php');
    $dash_invoices          =   wpestate_get_template_link('user_dashboard_invoices.php');
    $logout_url             =   wp_logout_url(wpestate_wpml_logout_url());      
    $home_url               =   esc_html( home_url('/') );
    
?> 
    <div id="user_menu_open"> 
        <?php if($home_url!=$dash_profile){?>
                <a href="<?php print esc_url($dash_profile);?>" ><i class="fas fa-cog"></i><?php esc_html_e('My Profile','wprentals');?></a>   
        <?php   
        }
        ?>
        
        <?php if($home_url!=$dash_link && wpestate_check_user_level()){?>
            <a href="<?php print esc_url($dash_link);?>" ><i class="fas fa-map-marker"></i><?php esc_html_e('My Listings','wprentals');?></a>
        <?php   
        }
        ?>
        
        <?php if($home_url!=$add_link && wpestate_check_user_level()){?>
            <a href="<?php print esc_url($add_link);?>" ><i class="fas fa-plus"></i><?php esc_html_e('Add New Listing','wprentals');?></a>        
        <?php   
        }
        ?>
        
        <?php if($home_url!=$dash_favorite){?>
            <a href="<?php print esc_url($dash_favorite);?>" class="active_fav"><i class="fas fa-heart"></i><?php esc_html_e('Favorites','wprentals');?></a>
        <?php   
        }
        
        if($home_url!=$dash_reservation  ){?>
            <a href="<?php print esc_url($dash_reservation);?>" class="active_fav"><i class="fas fa-folder-open"></i><?php esc_html_e('Reservations','wprentals');?></a>
        <?php   
        }
        
        if($home_url!=$dash_bookings && wpestate_check_user_level() ){?>
            <a href="<?php print esc_url($dash_bookings);?>" class="active_fav"><i class="far fa-folder-open"></i><?php esc_html_e('Bookings','wprentals');?></a>
        <?php   
        }


        if( wp_is_mobile() && $home_url!=$dash_reviews ){?>
            <a href="<?php print esc_url($dash_reviews);?>" class="active_fav"><i class="fa-solid fa-chart-simple"></i><?php esc_html_e('Reviews','wprentals');?></a>
        <?php   
        }



        
        if($home_url!=$dash_inbox){
             $no_unread=  intval(get_user_meta($userID,'unread_mess',true));?>
            <a href="<?php print esc_url($dash_inbox);?>" class="active_fav"><div class="unread_mess_wrap_menu"><?php print trim($no_unread);?></div><i class="fas fa-inbox"></i><?php esc_html_e('Inbox','wprentals');?></a>
        <?php   
         }
        if($home_url!=$dash_invoices && wpestate_check_user_level() ){?>
            <a href="<?php print esc_url($dash_invoices);?>" class="active_fav"><i class="far fa-file"></i><?php esc_html_e('Invoices','wprentals');?></a>
        <?php   
        }
        ?>
           
       
        <a href="<?php echo wp_logout_url(wpestate_wpml_logout_url());?>" title="Logout" class="menulogout"><i class="fas fa-power-off"></i><?php esc_html_e('Log Out','wprentals');?></a>
    </div>
    
<?php } 

   if(  class_exists( 'WooCommerce' ) ){
      
        $wpestate_global_payments->show_cart();
   }


?>

        
        