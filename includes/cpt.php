<?php
if (!defined('ABSPATH')) exit;

function mpe_io_register_cpts() {
  // Investigadores
  register_post_type(MPE_IO_CPT_INV, [
    'labels' => [
      'name' => __('Investigadores', 'mpe-investigadores-orcid'),
      'singular_name' => __('Investigador', 'mpe-investigadores-orcid'),
      'add_new_item' => __('Añadir investigador', 'mpe-investigadores-orcid'),
      'edit_item' => __('Editar investigador', 'mpe-investigadores-orcid'),
    ],
    'public' => true,
    'has_archive' => true,
    'menu_icon' => 'dashicons-id',
    'supports' => ['title', 'editor', 'thumbnail'],
    'show_in_rest' => true,
    'rewrite' => ['slug' => 'investigadores'],
  ]);

  // Publicaciones
  register_post_type(MPE_IO_CPT_PUB, [
    'labels' => [
      'name' => __('Publicaciones', 'mpe-investigadores-orcid'),
      'singular_name' => __('Publicación', 'mpe-investigadores-orcid'),
      'add_new_item' => __('Añadir publicación', 'mpe-investigadores-orcid'),
      'edit_item' => __('Editar publicación', 'mpe-investigadores-orcid'),
    ],
    'public' => false,
    'show_ui' => true,
    'show_in_menu' => 'edit.php?post_type=' . MPE_IO_CPT_INV,
    'menu_icon' => 'dashicons-media-document',
    'supports' => ['title'],
    'show_in_rest' => false,
  ]);
}

