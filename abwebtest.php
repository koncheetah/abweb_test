<?php
/**
 * @package Abwebtest
 */
/*
Plugin Name: Abweb Test plugin
Description: Тестовое задание на примере простого магазина. Добавьте фронтенд магазина на любую страницу шорткодом [show_shop]
Version: 1.0
Author: Konstantin Churakov
Author URI: 
License: GPLv2 or later
Text Domain: abwebtest
*/

/*
  Creating product
*/

function create_product() {
  register_post_type( 'product',
    array(
      'labels' => array(
        'name' => __( 'Продукты' ),
        'singular_name' => __( 'Продукт' ),
        'add_new' => __( 'Добавить Продукт' ),
        'add_new_item' => __( 'Добавить Продукт' ),
      ),
      'public' => true,
      'has_archive' => false,
      'rewrite' => array('slug' => 'products'),
      'menu_position' => 20,
      'menu_icon' => 'dashicons-carrot',
      'supports' => array('title', 'editor'),
    )
  );
}
add_action( 'init', 'create_product' );

function product_meta_box() {  
  add_meta_box(  
    'product_meta_box',
    'Параметры продукта',
    'show_meta_box',
    'product',
    'normal',
    'high');
}  
add_action('add_meta_boxes', 'product_meta_box');

$meta_fields = array(  
  array(  
    'label' => 'Базовая цена',  
    'desc'  => '',  
    'id'    => 'price',
    'type'  => 'number'
  )
);

/*
  Somebody's code for saving meta
*/

function show_meta_box() {  
global $meta_fields;
global $post;
// Выводим скрытый input, для верификации. Безопасность прежде всего!
echo '<input type="hidden" name="custom_meta_box_nonce" value="'.wp_create_nonce(basename(__FILE__)).'" />';  
  
  echo '<table class="form-table">';  
  foreach ($meta_fields as $field) {  
      $meta = get_post_meta($post->ID, $field['id'], true);  
      echo '<tr> 
              <th><label for="'.$field['id'].'">'.$field['label'].'</label></th> 
              <td>';  
      echo '<input type="number" name="'.$field['id'].'" id="'.$field['id'].'" value="'.$meta.'" size="3" />
      <br /><span class="description">'.$field['desc'].'</span>';  
      echo '</td></tr>';  
  }  
  echo '</table>'; 
}
 
function save_my_meta_fields($post_id) {  
  global $meta_fields;
  // проверяем наш проверочный код 
  if (!wp_verify_nonce($_POST['custom_meta_box_nonce'], basename(__FILE__)))   
      return $post_id;  
  // Проверяем авто-сохранение 
  if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)  
      return $post_id;  
  // Проверяем права доступа  
  if ('page' == $_POST['post_type']) {  
      if (!current_user_can('edit_page', $post_id))  
          return $post_id;  
      } elseif (!current_user_can('edit_post', $post_id)) {  
          return $post_id;  
  }
   // Если все отлично, прогоняем массив через foreach
  foreach ($meta_fields as $field) {  
      $old = get_post_meta($post_id, $field['id'], true); // Получаем старые данные (если они есть), для сверки
      $new = $_POST[$field['id']];  
      if ($new && $new != $old) {  // Если данные новые
          update_post_meta($post_id, $field['id'], $new); // Обновляем данные
      } elseif ('' == $new && $old) {  
          delete_post_meta($post_id, $field['id'], $old); // Если данных нету, удаляем мету.
      }  
  } // end foreach  
}  
add_action('save_post', 'save_my_meta_fields'); // Запускаем функцию сохранения

/*
  Products in admin with price column
*/

function display_product_price( $column, $post_id ) {
  echo get_post_meta( $post_id, 'price', true );
}
add_action( 'manage_product_posts_custom_column' , 'display_product_price', 10, 2 );

function add_my_product_column( $column ) {
    $column['price'] = 'Базовая цена';
    return $column;
}
add_filter( 'manage_product_posts_columns', 'add_my_product_column' );

/*
  Visitors IP logic
*/

global $wpdb;
$sql = "CREATE TABLE visitors (
  id mediumint(9) NOT NULL AUTO_INCREMENT,
  ip text NOT NULL,
  visits VARCHAR(55) DEFAULT 0 NOT NULL,
  discount mediumint(1) DEFAULT 0 NOT NULL,
  UNIQUE KEY id (id)
);";

require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
dbDelta( $sql );

$visitor_ip = $_SERVER['REMOTE_ADDR'];
$current_user_visits = $wpdb->get_var( "SELECT visits FROM visitors WHERE ip='$visitor_ip'" );
// echo $current_user_visits;
if ( $current_user_visits < 1 ) {
  $wpdb->query( "INSERT INTO visitors (ip, visits) VALUES ('$visitor_ip', 1)" );
}
else {
  $wpdb->query( "UPDATE visitors SET visits=visits+1 WHERE ip='$visitor_ip'" );
}

if ($current_user_visits == 9) {
  $wpdb->query( "UPDATE visitors SET discount=1 WHERE ip='$visitor_ip'" );
}

$discount = $wpdb->get_var( "SELECT discount FROM visitors WHERE ip='$visitor_ip'" );
$current_id = $wpdb->get_var( "SELECT id FROM visitors WHERE ip='$visitor_ip'" );

/*
  Shortcode for showing shop on page
*/

function show_shop_on_page() {
global $discount;
global $current_id;
$args = array(
  'post_type' => 'product',
  'post_status' => 'publish',
);
$products_loop = new WP_Query( $args );
if ( $products_loop->have_posts() ) : 
  ?>
  <table>
    <?php while ( $products_loop->have_posts() ) : $products_loop->the_post(); ?>
      <tr>
        <td>
        <?php echo the_title(); ?>
        </td>
        <td>
        <?php 
        $base_price = get_post_meta(get_the_ID(), 'price', true);
        $sale_price = $base_price * ( 1 - $discount * 0.1);
        echo $sale_price;
         ?>
        </td>
        <td>
        <form id="buy-product-<?php echo get_the_ID(); ?>" data-id="<?php echo $current_id ?>" action="" method="post"> 
          <input type="submit" name="add-to-cart" value="Купить" /> 
        </form>
        </td>
      </tr>
    <?php endwhile; ?>
  </table>
    <?php wp_reset_postdata();
endif;
}

add_shortcode('show_shop', 'show_shop_on_page');

/*
  Visitors page for Wordpress admin
*/

function visitors_plugin_menu() {
  add_menu_page(
    'Посетители',
    'Посетители сайта',
    'manage_options',
    'site-visitors',
    'display_visitors_plugin_menu',
    'dashicons-groups',
    24
    );
}


function display_visitors_plugin_menu() {
  global $wpdb;
  if ( !current_user_can( 'manage_options' ) )  {
    wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
  }
  $site_visitors = $wpdb->get_results( "SELECT * FROM visitors" );
  ?>
  <div class="wrap">
  <h1>Посетители сайта</h1>
  <table class="wp-list-table widefat striped posts">
  <thead><tr><th>ID</th><th>IP адрес</th><th>Посещений</th><th>Скидка</th></tr></thead>
  <tbody>
  <?php
  foreach ($site_visitors as $visitor) {
    echo '<tr>';
    echo '<td>'.$visitor->id.'</td>';
    echo '<td>'.$visitor->ip.'</td>';
    echo '<td>'.$visitor->visits.'</td>'; ?>
    <td> <form id="toggle-discount-<?php echo $visitor->id ?>" data-id="<?php echo $visitor->id ?>" data-discount="<?php echo $visitor->discount ?>" action="" method="post"><label><?php if ( $visitor->discount == 1 ) { echo 'Активна'; } else { echo 'Неактивна'; } ?></label><input type="submit" name="discount-submit"
    value="<?php if ( $visitor->discount == 1 ) { echo 'Отменить скидку'; } else { echo 'Сделать скидку'; } ?>" class="button action"></form></td>
    <?php
    echo '</tr>';
  } ?>
  </tbody></table>
  </div>
  <?php
}

add_action( 'admin_menu', 'visitors_plugin_menu' );
 
/*
  Forms processing
*/

function load_form_script() {
  wp_enqueue_script( 'my_ajax', plugin_dir_url(__FILE__).'js/form-ajax.js', array('jquery'), '1.0.0', true );
  wp_localize_script( 'my_ajax', 'my_ajax_object', array(
    'ajaxurl' => admin_url( 'admin-ajax.php' )
  ));
}

add_action( 'admin_enqueue_scripts', 'load_form_script' );
add_action( 'wp_enqueue_scripts', 'load_form_script');

function toggle_discount_status() {
  global $wpdb;
  $user_id = intval( $_POST['user_id'] );
  $user_discount = intval( $_POST['user_discount'] );
  if ( $user_discount == 1 ) {
    $wpdb->query( "UPDATE visitors SET visits=1, discount=0 WHERE id='$user_id'" );
    $results['button'] = 'Сделать скидку';
    $results['label'] = 'Неактивна';
    echo json_encode($results);
    wp_die();
  }
  if ( $user_discount == 0 ) {
    $wpdb->query( "UPDATE visitors SET discount=1 WHERE id='$user_id'" );
    $results['button'] = 'Отменить скидку';
    $results['label'] = 'Активна';
    echo json_encode($results);
    wp_die();
  }
}

add_action( 'wp_ajax_toggle_discount', 'toggle_discount_status' );
add_action( 'wp_ajax_nopriv_toggle_discount', 'toggle_discount_status' );

function add_to_cart() {
  global $wpdb;
  global $current_id;
  $wpdb->query( "UPDATE visitors SET visits=1, discount=0 WHERE id='$current_id'" );
  echo 'Товар куплен! Ваша скидка использована :(';
  wp_die();
}

add_action( 'wp_ajax_buy_product', 'add_to_cart' );
add_action( 'wp_ajax_nopriv_buy_product', 'add_to_cart' );