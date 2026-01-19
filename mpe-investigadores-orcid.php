<?php
/**
 * Plugin Name: MPE Investigadores + ORCID
 * Description: Gestiona personal investigador (foto, datos básicos, ORCID) e importa publicaciones desde ORCID (API pública).
 * Version: 1.2.0
 * Author: Félix González, Paloma Campos
 * Author URI: https://www.csic.es
 * Plugin URI: https://www.csic.es
 * Text Domain: mpe-investigadores-orcid
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * Developed by IRNAS-CSIC
 * Contact: penhaloza@gmail.com
 */

if (!defined('ABSPATH')) exit;

define('MPE_IO_VERSION', '1.2.0');
define('MPE_IO_PATH', plugin_dir_path(__FILE__));
define('MPE_IO_URL', plugin_dir_url(__FILE__));

define('MPE_IO_OPT', 'mpe_io_settings');

define('MPE_IO_CPT_INV', 'investigador');
define('MPE_IO_CPT_PUB', 'mpe_publicacion');

define('MPE_IO_META_ORCID', '_mpe_orcid');
define('MPE_IO_META_AREA', '_mpe_area');
define('MPE_IO_META_PUESTO', '_mpe_puesto');
define('MPE_IO_META_EMAIL', '_mpe_email');
define('MPE_IO_META_TEL', '_mpe_tel');
define('MPE_IO_META_WEB', '_mpe_web');
define('MPE_IO_META_LAST_SYNC', '_mpe_orcid_last_sync');

define('MPE_IO_META_LINKEDIN', '_mpe_linkedin');
define('MPE_IO_META_SCHOLAR', '_mpe_scholar');
define('MPE_IO_META_CV', '_mpe_cv');

require_once MPE_IO_PATH . 'includes/helpers.php';
require_once MPE_IO_PATH . 'includes/cpt.php';
require_once MPE_IO_PATH . 'includes/admin.php';
require_once MPE_IO_PATH . 'includes/public.php';
require_once MPE_IO_PATH . 'includes/orcid.php';

register_activation_hook(__FILE__, function () {
  mpe_io_register_cpts();
  flush_rewrite_rules();

  if (get_option(MPE_IO_OPT) === false) {
    add_option(MPE_IO_OPT, [
      'font_sans' => 'system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, "Apple Color Emoji", "Segoe UI Emoji"',
      'font_heading' => 'inherit',
      'primary' => '#2563eb',
      'bg' => '#ffffff',
      'surface' => '#ffffff',
      'text' => '#0f172a',
      'muted' => '#475569',
      'radius' => 16,
      'maxw' => 1100,
      'shadow' => 1,
      'card_style' => 'pro',

      // Iconos (ORCID / LinkedIn / Scholar)
      'icon_size' => 18,
      'icon_color' => '#475569',
      'icon_hover' => '#1e3a8a',

      // Botones (frontend)
      'btn_bg' => '#2563eb',
      'btn_text' => '#ffffff',
      'btn_hover' => '#1e40af',

      // Etiquetas (chips)
      'chip_bg' => '#dbeafe',
      'chip_text' => '#1d4ed8',
      'chip_border' => '#bfdbfe',
    ]);
  }
});

register_deactivation_hook(__FILE__, function () {
  flush_rewrite_rules();
});

add_action('init', 'mpe_io_register_cpts');

// Asegurar soporte de imágenes destacadas para el CPT "investigador" incluso si el tema es restrictivo.
add_action('after_setup_theme', function () {
  add_theme_support('post-thumbnails');
  add_post_type_support(MPE_IO_CPT_INV, 'thumbnail');
});

add_action('admin_init', 'mpe_io_admin_init');
add_action('admin_menu', 'mpe_io_admin_menu');
add_action('admin_enqueue_scripts', 'mpe_io_admin_assets');
add_action('wp_enqueue_scripts', 'mpe_io_public_assets');

// Template propio para la ficha individual del investigador
add_filter('single_template', 'mpe_io_single_template');

add_action('wp_ajax_mpe_io_orcid_import', 'mpe_io_ajax_orcid_import');

add_shortcode('investigadores', 'mpe_io_shortcode_investigadores');
add_shortcode('investigador_perfil', 'mpe_io_shortcode_investigador_perfil');

