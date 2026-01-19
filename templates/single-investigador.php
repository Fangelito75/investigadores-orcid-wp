<?php
/**
 * Template del plugin: ficha de investigador.
 *
 * Puedes sobreescribirlo desde el tema creando:
 *  - wp-content/themes/TU-TEMA/single-investigador.php
 *  - o wp-content/themes/TU-TEMA/mpe-io/single-investigador.php
 */

if (!defined('ABSPATH')) exit;

get_header();

while (have_posts()) : the_post();
  echo do_shortcode('[investigador_perfil id="' . get_the_ID() . '"]');
endwhile;

get_footer();
