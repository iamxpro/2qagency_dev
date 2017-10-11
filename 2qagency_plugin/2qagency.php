<?php
/*
  Plugin Name: 2qagency task
  Plugin URI: https://2qagency.com/
  Description: 2qagency task WordPress
  Version: 1.0
  Author: Burkhan Maksym
  Author URI: http://mbn.pp.ua/
 */

function custom_add_to_cart_redirect() {
    return 'http://2qagency.dev/checkout/';
}

add_filter('woocommerce_add_to_cart_redirect', 'custom_add_to_cart_redirect');

if (!function_exists('genres_taxonomy')) :

    function genres_taxonomy() {
        $labels = array(
            'name' => 'Жанры',
            'singular_name' => 'Жанр',
            'search_items' => 'Поиск Жанра',
            'all_items' => 'Все Жанры',
            'parent_item' => 'Родительский Жанр:',
            'edit_item' => 'Редактировать Жанр:',
            'update_item' => 'Обновить Жанр',
            'add_new_item' => 'Добавить новый Жанр',
            'new_item_name' => 'Новый Жанр имя',
            'menu_name' => 'Жанры',
            'view_item' => 'Посмотреть Жанры'
        );

        $args = array(
            'labels' => $labels,
            'hierarchical' => true,
            'query_var' => true
        );

        register_taxonomy('genres', 'films', $args);
    }

endif;

add_action('init', 'genres_taxonomy');

if (!function_exists('films_tag_taxonomy')) :

    function films_tag_taxonomy() {
        $labels = array(
            'name' => 'Теги',
            'singular_name' => 'Тег',
            'search_items' => 'Поиск Теги',
            'popular_items' => ( 'Популярные Теги' ),
            'parent_item' => null,
            'parent_item_colon' => null,
            'all_items' => 'Все Теги',
            'edit_item' => 'Редактировать Тег:',
            'update_item' => 'Обновить Тег',
            'add_new_item' => 'Добавить новый Тег',
            'new_item_name' => 'Новый Тег имя',
            'menu_name' => 'Теги',
            'view_item' => 'Посмотреть Теги'
        );

        $args = array(
            'labels' => $labels,
            'hierarchical' => false,
            'query_var' => true
        );

        register_taxonomy('tag_films', 'films', $args);
    }

endif;

add_action('init', 'films_tag_taxonomy');

if (!function_exists('register_films')) :

    function register_films() {
        $labels = array(
            'name' => 'Фильмы',
            'singular_name' => 'Фильм',
            'add_new' => 'Добавить новый Фильм',
            'add_new_item' => 'Добавить новый Фильм',
            'edit_item' => 'Редактировать Фильм',
            'new item' => 'Новый Фильм',
            'all_items' => 'Все Фильмы',
            'view_item' => 'Посмотреть Фильм',
            'search_items' => 'Поиск Фильмы',
            'not_found' => 'Не найдено ни одного фильма',
            'not_found_in_trash' => 'В корзине нет фильмов',
            'menu_name' => 'Фильмы'
        );

        $args = array(
            'labels' => $labels,
            'public' => true,
            'taxonomies' => array('genres', 'tag_films'),
            'rewrite' => array('slug' => 'films'),
            'hierarchical' => false,
            'has_archive' => true,
            'supports' => array(
                'title',
                'editor',
                'thumbnail',
                'excerpt',
            ),
            'menu_icon' => 'dashicons-format-video',
            'menu_position' => 5,
        );

        register_post_type('films', $args);
    }

endif;

add_action('init', 'register_films');

function films_cpt_install() {
    genres_taxonomy();
    films_tag_taxonomy();
    register_films();
    flush_rewrite_rules();
}

register_deactivation_hook(__FILE__, 'flush_rewrite_rules');
register_activation_hook(__FILE__, 'films_cpt_install');

//add_filter('woocommerce_get_price', 'films_woocommerce_get_price', 20, 2);
//add_filter('woocommerce_get_regular_price', 'films_woocommerce_get_price', 10, 2);
//add_filter('woocommerce_get_sale_price', 'films_woocommerce_get_price', 10, 2);
//add_filter('add_to_cart', 'films_woocommerce_get_price', 10, 2);
//
//function films_woocommerce_get_price($price, $post) {
//    if ($post->post->post_type === 'films')
//        $price = get_post_meta($post->id, "price", true);
//    return $price;
//}

add_filter('the_content', 'films_add_to_cart_button', 20, 1);

function films_add_to_cart_button($content) {
    global $post;
    global $woocommerce;
    if ($post->post_type !== 'films') {
        return $content;
    }
    ob_start();
    $post_id = $post->ID;
    $submit = 'submit_film_' . $post_id;
    ?>
    <form class="cart" method="post" id="buy_film_<?php echo $post_id; ?>">
        <button type="submit" name="<?php echo $submit; ?>" >Купить</button>
    </form>
    <?php
    if (isset($_POST[$submit])) {
        global $wpdb;
        $post = get_post($post_id);
        $current_user = wp_get_current_user();
        $new_post_author = $current_user->ID;
        $price = get_post_meta($post_id, 'price', true);
        if (!empty($price) || $price != '') {
            if (isset($post) && $post != null) {
                $args = array(
                    'comment_status' => $post->comment_status,
                    'ping_status' => $post->ping_status,
                    'post_author' => $new_post_author,
                    'post_content' => $post->post_content,
                    'post_excerpt' => $post->post_excerpt,
                    'post_name' => $post->post_name,
                    'post_parent' => $post->post_parent,
                    'post_password' => $post->post_password,
                    'post_status' => 'publish',
                    'post_title' => 'Покупка ' . $post->post_title,
                    'post_type' => 'product',
                    'to_ping' => $post->to_ping,
                    'menu_order' => $post->menu_order
                );
                $new_post_id = wp_insert_post($args);
                $post_meta_infos = $wpdb->get_results("SELECT meta_key, meta_value FROM $wpdb->postmeta WHERE post_id=$post_id");
                if (count($post_meta_infos) != 0) {
                    $sql_query = "INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value) ";
                    foreach ($post_meta_infos as $meta_info) {
                        $meta_key = $meta_info->meta_key;
                        if ($meta_key == '_wp_old_slug')
                            continue;
                        $meta_value = addslashes($meta_info->meta_value);
                        $sql_query_sel[] = "SELECT $new_post_id, '$meta_key', '$meta_value'";
                    }
                    $sql_query .= implode(" UNION ALL ", $sql_query_sel);
                    $wpdb->query($sql_query);
                }
                if (!empty($new_post_id)) {
                    $total_sale_film = get_post_meta($post_id, 'total_sale_film', true);
                    update_post_meta($post_id, "total_sale_film", $total_sale_film + 1, $total_sale_film);
                    echo '<script>location.href = "#buy_film_' . $post_id . '";</script>';
                    echo do_shortcode('[add_to_cart id="' . $new_post_id . '"]');
                }
            }
        } else {
            echo 'нет стоимости фильма!!!';
        }
    }
    return $content . ob_get_clean();
}

add_filter('woocommerce_login_widget_redirect', 'custom_login_redirect');

function custom_login_redirect($redirect_to) {
    $redirect_to = '/film';
}

function custom_meta_box_markup($object) {
    wp_nonce_field(basename(__FILE__), "meta-box-nonce");
    ?>
    <div>
        <label for="meta-box-film">Цена: </label>
        <input name="meta-box-film" type="text" value="<?php echo get_post_meta($object->ID, "_regular_price", true); ?>">
        <br>Общее количество продаж: <?php
        $total_sale_film = get_post_meta($object->ID, 'total_sale_film', true);
        if (!empty($total_sale_film)) {
            echo $total_sale_film;
        } else {
            echo '0';
        }
        ?>
    </div>
    <?php
}

function add_film_meta_box() {
    add_meta_box("film-meta-box", "Стоимость Фильма", "custom_meta_box_markup", "films", "side", "high", null);
}

add_action("add_meta_boxes", "add_film_meta_box");

function save_film_meta_box($post_id, $post, $update) {
    if (!isset($_POST["meta-box-nonce"]) || !wp_verify_nonce($_POST["meta-box-nonce"], basename(__FILE__)))
        return $post_id;

    if (!current_user_can("edit_post", $post_id))
        return $post_id;

    if (defined("DOING_AUTOSAVE") && DOING_AUTOSAVE)
        return $post_id;

    $slug = "films";
    if ($slug != $post->post_type)
        return $post_id;

    $meta_box_film_value = "";

    if (isset($_POST["meta-box-film"])) {
        $meta_box_film_value = $_POST["meta-box-film"];
    }
    $total_sale_film = get_post_meta($post_id, 'total_sale_film', true);
    update_post_meta($post_id, "total_sale_film", $total_sale_film);
    update_post_meta($post_id, "_regular_price", $meta_box_film_value);
    update_post_meta($post_id, "_price", $meta_box_film_value);
    update_post_meta($post_id, "price", $meta_box_film_value);
    update_post_meta($post_id, "_stock_status", 'instock');
    update_post_meta($post_id, "_tax_status", 'taxable');
}

add_action("save_post", "save_film_meta_box", 10, 3);

function skype_registation_fields() {?>
    <p class="form-row form-row-wide">
        <label for="reg_billing_skype"><?php _e( 'Skype', 'woocommerce' ); ?> <span class="required">*</span></label></label>
        <input type="text" class="input-text" name="billing_skype" id="reg_billing_skype" value="<?php esc_attr_e( $_POST['billing_skype'] ); ?>" />
    </p>
    <div class="clear"></div>
    <?php
}
add_action( 'woocommerce_register_form_start', 'skype_registation_fields');

function skype_validate_reg_form_fields($username, $email, $validation_errors) {
    if (isset($_POST['billing_skype']) && empty($_POST['billing_skype']) ) {
        $validation_errors->add('billing_skype_error', __('Skype is required!', 'woocommerce'));
    }
    return $validation_errors;
}
add_action('woocommerce_register_post', 'skype_validate_reg_form_fields', 10, 3);

function skype_save_registration_form_fields($customer_id) {
    if (isset($_POST['billing_skype'])) {
        update_user_meta($customer_id, 'billing_skype', sanitize_text_field($_POST['billing_skype']));
    }
}
add_action('woocommerce_created_customer', 'skype_save_registration_form_fields');

function skype_edit_account_form() {
    $user_id = get_current_user_id();
    $current_user = get_userdata( $user_id );
    if (!$current_user) return;
    $billing_skype = get_user_meta( $user_id, 'billing_skype', true );
    ?>    
    <fieldset>
        <legend>Skype information</legend>
        <p class="form-row form-row-wide">
            <label for="reg_billing_skype"><?php _e( 'Skype', 'woocommerce' ); ?> <span class="required">*</span></label></label>
            <input type="text" class="input-text" name="billing_skype" id="reg_billing_skype" value="<?php echo esc_attr($billing_skype); ?>" />
        </p>
        <div class="clear"></div>
    </fieldset>
    <?php
}

function skype_save_account_details( $user_id ) {
    update_user_meta($user_id, 'billing_skype', sanitize_text_field($_POST['billing_skype']));
}

add_action( 'woocommerce_edit_account_form', 'skype_edit_account_form' );
add_action( 'woocommerce_save_account_details', 'skype_save_account_details' );