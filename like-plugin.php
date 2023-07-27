<?php
/*
Nome: Like Plugin
Descrição: Adicionar funcionalidaded de Like/Dislike aos posts.
Version: 1.0
Author: Matheus Alonso Damasceno Santos  whatsapp: (13)99116-9000
*/

// Função para adicionar botões Like/Dislike ao final do conteúdo do post
function like_plugin_add_like_dislike_buttons($content) {
    if (is_single()) {
        $post_id = get_the_ID();
        $like_count = get_post_meta($post_id, '_like_count', true);
        $dislike_count = get_post_meta($post_id, '_dislike_count', true);

        $content .= '<div class="like-dislike-buttons">';
        $content .= '<button class="like-button" data-action="like" data-post_id="' . $post_id . '">Like (' . $like_count . ')</button>';
        $content .= '<button class="dislike-button" data-action="dislike" data-post_id="' . $post_id . '">Dislike (' . $dislike_count . ')</button>';
        $content .= '</div>';
    }

    return $content;
}
add_filter('the_content', 'like_plugin_add_like_dislike_buttons');

// Adicionar scripts e estilos
function like_plugin_enqueue_scripts() {
    wp_enqueue_style('like-plugin-style', plugin_dir_url(__FILE__) . 'css/style.css');
    wp_enqueue_script('like-plugin-script', plugin_dir_url(__FILE__) . 'js/script.js', array('jquery'), '1.0', true);
    wp_localize_script('like-plugin-script', 'like_plugin_ajax', array('ajax_url' => admin_url('admin-ajax.php')));
}
add_action('wp_enqueue_scripts', 'like_plugin_enqueue_scripts');

// Manipular votos via Ajax
function like_plugin_handle_vote() {
    if (isset($_POST['action']) && isset($_POST['post_id'])) {
        $action = $_POST['action'];
        $post_id = intval($_POST['post_id']);

        $like_count = get_post_meta($post_id, '_like_count', true);
        $dislike_count = get_post_meta($post_id, '_dislike_count', true);

        if ($action === 'like') {
            update_post_meta($post_id, '_like_count', $like_count + 1);
        } elseif ($action === 'dislike') {
            update_post_meta($post_id, '_dislike_count', $dislike_count + 1);
        }
    }

    wp_die();
}
add_action('wp_ajax_like_dislike', 'like_plugin_handle_vote');
add_action('wp_ajax_nopriv_like_dislike', 'like_plugin_handle_vote');

// Configurar cookie para guardar votos
function like_plugin_set_cookie() {
    if (isset($_POST['action']) && isset($_POST['post_id'])) {
        $action = $_POST['action'];
        $post_id = intval($_POST['post_id']);

        $liked_posts = array();
        if (isset($_COOKIE['liked_posts'])) {
            $liked_posts = json_decode(stripslashes($_COOKIE['liked_posts']), true);
        }

        if ($action === 'like' || $action === 'dislike') {
            if (!in_array($post_id, $liked_posts)) {
                $liked_posts[] = $post_id;
                setcookie('liked_posts', json_encode($liked_posts), time() + 3600 * 24 * 30, '/');
            }
        } elseif ($action === 'remove_vote') {
            $liked_posts = array_diff($liked_posts, array($post_id));
            setcookie('liked_posts', json_encode($liked_posts), time() + 3600 * 24 * 30, '/');
        }
    }
}
add_action('wp_ajax_like_dislike', 'like_plugin_set_cookie');
add_action('wp_ajax_nopriv_like_dislike', 'like_plugin_set_cookie');

// Adicionar Post Meta para armazenar contagem de Likes e Dislikes
function like_plugin_add_post_meta() {
    $args = array(
        'post_type' => 'post',
        'posts_per_page' => -1,
    );
    $posts = get_posts($args);

    foreach ($posts as $post) {
        add_post_meta($post->ID, '_like_count', 0, true);
        add_post_meta($post->ID, '_dislike_count', 0, true);
    }
}
register_activation_hook(__FILE__, 'like_plugin_add_post_meta');

// Criar o shortcode [top-liked]
function like_plugin_top_liked_shortcode($atts) {
    $args = array(
        'post_type' => 'post',
        'posts_per_page' => -1,
        'meta_key' => '_like_count',
        'orderby' => 'meta_value_num',
        'order' => 'DESC',
    );

    $liked_posts = get_posts($args);
    $output = '<ul class="top-liked-posts">';
    foreach ($liked_posts as $post) {
        $like_count = get_post_meta($post->ID, '_like_count', true);
        $dislike_count = get_post_meta($post->ID, '_dislike_count', true);
        $output .= '<li>';
        $output .= '<a href="' . get_permalink($post->ID) . '">' . get_the_title($post->ID) . '</a> - Likes: ' . $like_count . ' - Dislikes: ' . $dislike_count;
        $output .= '</li>';
    }
    $output .= '</ul>';

    return $output;
}
add_shortcode('top-liked', 'like_plugin_top_liked_shortcode');
