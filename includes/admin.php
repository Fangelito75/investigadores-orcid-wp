<?php
if (!defined('ABSPATH')) exit;

function mpe_io_admin_init() {
  add_action('add_meta_boxes', function () {
    add_meta_box('mpe_io_inv_data', __('Datos del investigador', 'mpe-investigadores-orcid'), 'mpe_io_metabox_inv_data', MPE_IO_CPT_INV, 'normal', 'high');
    add_meta_box('mpe_io_inv_orcid', __('ORCID y publicaciones', 'mpe-investigadores-orcid'), 'mpe_io_metabox_inv_orcid', MPE_IO_CPT_INV, 'side', 'high');
  });

  add_action('save_post_' . MPE_IO_CPT_INV, 'mpe_io_save_investigador_meta');

  // Columnas en listado
  add_filter('manage_' . MPE_IO_CPT_INV . '_posts_columns', function ($cols) {
    $cols['mpe_orcid'] = 'ORCID';
    $cols['mpe_area'] = __('Área', 'mpe-investigadores-orcid');
    $cols['mpe_puesto'] = __('Puesto', 'mpe-investigadores-orcid');
    $cols['mpe_sync'] = __('Últ. sync', 'mpe-investigadores-orcid');
    return $cols;
  });

  add_action('manage_' . MPE_IO_CPT_INV . '_posts_custom_column', function ($col, $post_id) {
    if ($col === 'mpe_orcid') {
      $o = get_post_meta($post_id, MPE_IO_META_ORCID, true);
      echo esc_html($o);
    }
    if ($col === 'mpe_area') {
      echo esc_html(get_post_meta($post_id, MPE_IO_META_AREA, true));
    }
    if ($col === 'mpe_puesto') {
      echo esc_html(get_post_meta($post_id, MPE_IO_META_PUESTO, true));
    }
    if ($col === 'mpe_sync') {
      $t = get_post_meta($post_id, MPE_IO_META_LAST_SYNC, true);
      if ($t) echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), intval($t)));
    }
  }, 10, 2);
}

function mpe_io_admin_menu() {
  add_submenu_page(
    'edit.php?post_type=' . MPE_IO_CPT_INV,
    __('Ajustes de diseño', 'mpe-investigadores-orcid'),
    __('Ajustes de diseño', 'mpe-investigadores-orcid'),
    'manage_options',
    'mpe-io-settings',
    'mpe_io_render_settings_page'
  );
}

function mpe_io_admin_assets($hook) {
  // Solo en pantallas relevantes
  $screen = function_exists('get_current_screen') ? get_current_screen() : null;
  if (!$screen) return;

  if ($screen->post_type === MPE_IO_CPT_INV) {
    wp_enqueue_style('mpe-io-admin', MPE_IO_URL . 'assets/css/admin.css', [], MPE_IO_VERSION);
    wp_enqueue_script('mpe-io-admin', MPE_IO_URL . 'assets/js/admin.js', ['jquery'], MPE_IO_VERSION, true);
    wp_localize_script('mpe-io-admin', 'MPE_IO', [
      'ajax_url' => admin_url('admin-ajax.php'),
      'nonce' => wp_create_nonce('mpe_io_orcid_import'),
    ]);
  }

  if ($hook === MPE_IO_CPT_INV . '_page_mpe-io-settings') {
    wp_enqueue_style('mpe-io-admin', MPE_IO_URL . 'assets/css/admin.css', [], MPE_IO_VERSION);
    wp_enqueue_style('wp-color-picker');
    wp_enqueue_script('wp-color-picker');
    wp_add_inline_script('wp-color-picker', 'jQuery(function($){$(".mpe-color").wpColorPicker();});');
  }
}

function mpe_io_metabox_inv_data($post) {
  wp_nonce_field('mpe_io_save_inv', 'mpe_io_nonce');

  $area = get_post_meta($post->ID, MPE_IO_META_AREA, true);
  $puesto = get_post_meta($post->ID, MPE_IO_META_PUESTO, true);
  $email = get_post_meta($post->ID, MPE_IO_META_EMAIL, true);
  $tel = get_post_meta($post->ID, MPE_IO_META_TEL, true);
  $web = get_post_meta($post->ID, MPE_IO_META_WEB, true);
  $linkedin = get_post_meta($post->ID, MPE_IO_META_LINKEDIN, true);
  $scholar = get_post_meta($post->ID, MPE_IO_META_SCHOLAR, true);
  $cv = get_post_meta($post->ID, MPE_IO_META_CV, true);
  $orcid = get_post_meta($post->ID, MPE_IO_META_ORCID, true);

  ?>
  <p class="description" style="margin:0 0 12px;">La <strong>foto</strong> del investigador se gestiona desde el panel <em>Imagen destacada</em> (barra lateral derecha).</p>
  <div class="mpe-admin-grid">
    <div class="mpe-field">
      <label for="mpe_puesto"><strong><?php esc_html_e('Puesto / cargo', 'mpe-investigadores-orcid'); ?></strong></label>
      <input type="text" id="mpe_puesto" name="mpe_puesto" value="<?php echo esc_attr($puesto); ?>" class="widefat" placeholder="Ej: Profesora Titular" />
    </div>

    <div class="mpe-field">
      <label for="mpe_area"><strong><?php esc_html_e('Área / grupo', 'mpe-investigadores-orcid'); ?></strong></label>
      <input type="text" id="mpe_area" name="mpe_area" value="<?php echo esc_attr($area); ?>" class="widefat" placeholder="Ej: Inteligencia Artificial" />
    </div>

    <div class="mpe-field">
      <label for="mpe_email"><strong><?php esc_html_e('Email', 'mpe-investigadores-orcid'); ?></strong></label>
      <input type="email" id="mpe_email" name="mpe_email" value="<?php echo esc_attr($email); ?>" class="widefat" placeholder="nombre@universidad.es" />
    </div>

    <div class="mpe-field">
      <label for="mpe_tel"><strong><?php esc_html_e('Teléfono', 'mpe-investigadores-orcid'); ?></strong></label>
      <input type="text" id="mpe_tel" name="mpe_tel" value="<?php echo esc_attr($tel); ?>" class="widefat" placeholder="Ej: +34 ..." />
    </div>

    <div class="mpe-field">
      <label for="mpe_web"><strong><?php esc_html_e('Web / Perfil', 'mpe-investigadores-orcid'); ?></strong></label>
      <input type="url" id="mpe_web" name="mpe_web" value="<?php echo esc_attr($web); ?>" class="widefat" placeholder="https://..." />
    </div>
    <div class="mpe-field">
      <label for="mpe_linkedin"><strong>LinkedIn (URL)</strong></label>
      <input type="url" id="mpe_linkedin" name="mpe_linkedin" value="<?php echo esc_attr($linkedin); ?>" class="widefat" placeholder="https://www.linkedin.com/in/..." />
    </div>

    <div class="mpe-field">
      <label for="mpe_scholar"><strong>Google Scholar (URL)</strong></label>
      <input type="url" id="mpe_scholar" name="mpe_scholar" value="<?php echo esc_attr($scholar); ?>" class="widefat" placeholder="https://scholar.google.com/citations?user=..." />
    </div>

    <div class="mpe-field">
      <label for="mpe_cv"><strong>CV (URL)</strong></label>
      <input type="url" id="mpe_cv" name="mpe_cv" value="<?php echo esc_attr($cv); ?>" class="widefat" placeholder="https://.../cv.pdf" />
      <p class="description">Puedes subir el PDF a la biblioteca de medios y pegar aquí su URL.</p>
    </div>

    <div class="mpe-field">
      <label for="mpe_orcid"><strong><?php esc_html_e('ORCID iD', 'mpe-investigadores-orcid'); ?></strong></label>
      <input type="text" id="mpe_orcid" name="mpe_orcid" value="<?php echo esc_attr($orcid); ?>" class="widefat" placeholder="0000-0000-0000-0000" />
      <p class="description">Formato: 0000-0000-0000-0000 (último dígito puede ser X).</p>
    </div>
  </div>
  <?php
}

function mpe_io_metabox_inv_orcid($post) {
  $orcid = get_post_meta($post->ID, MPE_IO_META_ORCID, true);
  $last = get_post_meta($post->ID, MPE_IO_META_LAST_SYNC, true);
  $pub_count = mpe_io_count_publicaciones($post->ID);

  ?>
  <div class="mpe-orcid-box">
    <p><strong><?php esc_html_e('Publicaciones importadas', 'mpe-investigadores-orcid'); ?>:</strong> <?php echo intval($pub_count); ?></p>

    <?php if ($last): ?>
      <p><strong><?php esc_html_e('Última sincronización', 'mpe-investigadores-orcid'); ?>:</strong><br>
        <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), intval($last))); ?>
      </p>
    <?php endif; ?>

    <?php if ($orcid): ?>
      <button type="button" class="button button-primary mpe-orcid-import" data-post-id="<?php echo esc_attr($post->ID); ?>">
        <?php esc_html_e('Importar publicaciones', 'mpe-investigadores-orcid'); ?>
      </button>
      <p class="description" style="margin-top:8px;">Se importa desde la API pública de ORCID (solo datos públicos).</p>
      <div class="mpe-orcid-status" aria-live="polite" style="margin-top:10px;"></div>
    <?php else: ?>
      <p class="description">Añade un ORCID para habilitar la importación.</p>
    <?php endif; ?>
  </div>
  <?php
}

function mpe_io_save_investigador_meta($post_id) {
  if (!isset($_POST['mpe_io_nonce']) || !wp_verify_nonce($_POST['mpe_io_nonce'], 'mpe_io_save_inv')) return;
  if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
  if (!current_user_can('edit_post', $post_id)) return;

  $puesto = sanitize_text_field($_POST['mpe_puesto'] ?? '');
  $area = sanitize_text_field($_POST['mpe_area'] ?? '');
  $email = sanitize_email($_POST['mpe_email'] ?? '');
  $tel = sanitize_text_field($_POST['mpe_tel'] ?? '');
  $web = esc_url_raw($_POST['mpe_web'] ?? '');
  $linkedin = esc_url_raw($_POST['mpe_linkedin'] ?? '');
  $scholar = esc_url_raw($_POST['mpe_scholar'] ?? '');
  $cv = esc_url_raw($_POST['mpe_cv'] ?? '');
  $orcid = mpe_io_clean_orcid($_POST['mpe_orcid'] ?? '');

  update_post_meta($post_id, MPE_IO_META_PUESTO, $puesto);
  update_post_meta($post_id, MPE_IO_META_AREA, $area);
  update_post_meta($post_id, MPE_IO_META_EMAIL, $email);
  update_post_meta($post_id, MPE_IO_META_TEL, $tel);
  update_post_meta($post_id, MPE_IO_META_WEB, $web);
  update_post_meta($post_id, MPE_IO_META_LINKEDIN, $linkedin);
  update_post_meta($post_id, MPE_IO_META_SCHOLAR, $scholar);
  update_post_meta($post_id, MPE_IO_META_CV, $cv);
  update_post_meta($post_id, MPE_IO_META_ORCID, $orcid);
}

function mpe_io_render_settings_page() {
  if (!current_user_can('manage_options')) return;

  if (isset($_POST['mpe_io_settings_nonce']) && wp_verify_nonce($_POST['mpe_io_settings_nonce'], 'mpe_io_save_settings')) {
    $s = mpe_io_get_settings();

    $s['font_sans'] = sanitize_text_field($_POST['font_sans'] ?? $s['font_sans']);
    $s['font_heading'] = sanitize_text_field($_POST['font_heading'] ?? $s['font_heading']);

    $s['primary'] = sanitize_hex_color($_POST['primary'] ?? $s['primary']) ?: $s['primary'];
    $s['bg'] = sanitize_hex_color($_POST['bg'] ?? $s['bg']) ?: $s['bg'];
    $s['surface'] = sanitize_hex_color($_POST['surface'] ?? $s['surface']) ?: $s['surface'];
    $s['text'] = sanitize_hex_color($_POST['text'] ?? $s['text']) ?: $s['text'];
    $s['muted'] = sanitize_hex_color($_POST['muted'] ?? $s['muted']) ?: $s['muted'];

    $s['radius'] = max(0, intval($_POST['radius'] ?? $s['radius']));
    $s['maxw'] = max(600, intval($_POST['maxw'] ?? $s['maxw']));

    $s['shadow'] = !empty($_POST['shadow']) ? 1 : 0;
    $s['card_style'] = sanitize_key($_POST['card_style'] ?? ($s['card_style'] ?? 'pro'));

    // Iconos
    $s['icon_size'] = max(10, min(48, intval($_POST['icon_size'] ?? ($s['icon_size'] ?? 18))));
    $s['icon_color'] = sanitize_hex_color($_POST['icon_color'] ?? ($s['icon_color'] ?? '#475569')) ?: ($s['icon_color'] ?? '#475569');
    $s['icon_hover'] = sanitize_hex_color($_POST['icon_hover'] ?? ($s['icon_hover'] ?? '#1e3a8a')) ?: ($s['icon_hover'] ?? '#1e3a8a');

    // Botones
    $s['btn_bg'] = sanitize_hex_color($_POST['btn_bg'] ?? ($s['btn_bg'] ?? $s['primary'])) ?: ($s['btn_bg'] ?? $s['primary']);
    $s['btn_text'] = sanitize_hex_color($_POST['btn_text'] ?? ($s['btn_text'] ?? '#ffffff')) ?: ($s['btn_text'] ?? '#ffffff');
    $s['btn_hover'] = sanitize_hex_color($_POST['btn_hover'] ?? ($s['btn_hover'] ?? $s['primary'])) ?: ($s['btn_hover'] ?? $s['primary']);

    // Etiquetas (chips)
    $s['chip_bg'] = sanitize_hex_color($_POST['chip_bg'] ?? ($s['chip_bg'] ?? '#dbeafe')) ?: ($s['chip_bg'] ?? '#dbeafe');
    $s['chip_text'] = sanitize_hex_color($_POST['chip_text'] ?? ($s['chip_text'] ?? '#1d4ed8')) ?: ($s['chip_text'] ?? '#1d4ed8');
    $s['chip_border'] = sanitize_hex_color($_POST['chip_border'] ?? ($s['chip_border'] ?? '#bfdbfe')) ?: ($s['chip_border'] ?? '#bfdbfe');

    update_option(MPE_IO_OPT, $s);

    echo '<div class="notice notice-success"><p>Ajustes guardados.</p></div>';
  }

  $s = mpe_io_get_settings();

  ?>
  <div class="wrap">
    <h1><?php esc_html_e('Ajustes de diseño', 'mpe-investigadores-orcid'); ?></h1>
    <p>Estos ajustes controlan variables CSS del listado y la ficha para que encajen con el diseño de tu web.</p>

    <form method="post">
      <?php wp_nonce_field('mpe_io_save_settings', 'mpe_io_settings_nonce'); ?>

      <table class="form-table" role="presentation">
        <tr>
          <th scope="row"><label for="font_sans">Fuente principal</label></th>
          <td><input type="text" class="regular-text" name="font_sans" id="font_sans" value="<?php echo esc_attr($s['font_sans']); ?>" /></td>
        </tr>
        <tr>
          <th scope="row"><label for="font_heading">Fuente títulos</label></th>
          <td><input type="text" class="regular-text" name="font_heading" id="font_heading" value="<?php echo esc_attr($s['font_heading']); ?>" /></td>
        </tr>

        <tr>
          <th scope="row"><label for="primary">Color principal</label></th>
          <td><input type="text" class="regular-text mpe-color" name="primary" id="primary" value="<?php echo esc_attr($s['primary']); ?>" /></td>
        </tr>
        <tr>
          <th scope="row"><label for="bg">Fondo</label></th>
          <td><input type="text" class="regular-text mpe-color" name="bg" id="bg" value="<?php echo esc_attr($s['bg']); ?>" /></td>
        </tr>
        <tr>
          <th scope="row"><label for="surface">Superficie (tarjetas)</label></th>
          <td><input type="text" class="regular-text mpe-color" name="surface" id="surface" value="<?php echo esc_attr($s['surface']); ?>" /></td>
        </tr>
        <tr>
          <th scope="row"><label for="text">Texto</label></th>
          <td><input type="text" class="regular-text mpe-color" name="text" id="text" value="<?php echo esc_attr($s['text']); ?>" /></td>
        </tr>
        <tr>
          <th scope="row"><label for="muted">Texto secundario</label></th>
          <td><input type="text" class="regular-text mpe-color" name="muted" id="muted" value="<?php echo esc_attr($s['muted']); ?>" /></td>
        </tr>
        <tr>
          <th scope="row"><label for="radius">Radio (px)</label></th>
          <td><input type="number" min="0" name="radius" id="radius" value="<?php echo esc_attr($s['radius']); ?>" /></td>
        </tr>
        <tr>
          <th scope="row"><label for="maxw">Ancho máximo (px)</label></th>
          <td><input type="number" min="600" name="maxw" id="maxw" value="<?php echo esc_attr($s['maxw']); ?>" /></td>
        </tr>

        <tr>
          <th scope="row">Sombra</th>
          <td><label><input type="checkbox" name="shadow" value="1" <?php checked(!empty($s['shadow'])); ?> /> Activar sombras suaves</label></td>
        </tr>
        <tr>
          <th scope="row"><label for="card_style">Estilo de tarjetas</label></th>
          <td>
            <select name="card_style" id="card_style">
              <option value="pro" <?php selected(($s['card_style'] ?? 'pro'), 'pro'); ?>>Profesional</option>
              <option value="simple" <?php selected(($s['card_style'] ?? 'pro'), 'simple'); ?>>Simple</option>
            </select>
          </td>
        </tr>

        <tr>
          <th scope="row"><label for="icon_size">Tamaño de iconos (px)</label></th>
          <td>
            <input type="number" min="10" max="48" name="icon_size" id="icon_size" value="<?php echo esc_attr($s['icon_size']); ?>" />
            <p class="description">Afecta a los iconos de ORCID, LinkedIn y Scholar (recomendado: 18px).</p>
          </td>
        </tr>
        <tr>
          <th scope="row"><label for="icon_color">Color iconos</label></th>
          <td><input type="text" class="regular-text mpe-color" name="icon_color" id="icon_color" value="<?php echo esc_attr($s['icon_color']); ?>" /></td>
        </tr>
        <tr>
          <th scope="row"><label for="icon_hover">Color iconos (hover)</label></th>
          <td><input type="text" class="regular-text mpe-color" name="icon_hover" id="icon_hover" value="<?php echo esc_attr($s['icon_hover']); ?>" /></td>
        </tr>

        <tr><th colspan="2"><hr></th></tr>

        <tr>
          <th scope="row"><label for="btn_bg">Color botón primario (fondo)</label></th>
          <td><input type="text" class="regular-text mpe-color" name="btn_bg" id="btn_bg" value="<?php echo esc_attr($s['btn_bg']); ?>" /></td>
        </tr>
        <tr>
          <th scope="row"><label for="btn_text">Color botón primario (texto)</label></th>
          <td><input type="text" class="regular-text mpe-color" name="btn_text" id="btn_text" value="<?php echo esc_attr($s['btn_text']); ?>" /></td>
        </tr>
        <tr>
          <th scope="row"><label for="btn_hover">Color botón primario (hover)</label></th>
          <td><input type="text" class="regular-text mpe-color" name="btn_hover" id="btn_hover" value="<?php echo esc_attr($s['btn_hover']); ?>" /></td>
        </tr>

        <tr><th colspan="2"><hr></th></tr>

        <tr>
          <th scope="row"><label for="chip_bg">Etiquetas (fondo)</label></th>
          <td><input type="text" class="regular-text mpe-color" name="chip_bg" id="chip_bg" value="<?php echo esc_attr($s['chip_bg']); ?>" /></td>
        </tr>
        <tr>
          <th scope="row"><label for="chip_text">Etiquetas (texto)</label></th>
          <td><input type="text" class="regular-text mpe-color" name="chip_text" id="chip_text" value="<?php echo esc_attr($s['chip_text']); ?>" /></td>
        </tr>
        <tr>
          <th scope="row"><label for="chip_border">Etiquetas (borde)</label></th>
          <td><input type="text" class="regular-text mpe-color" name="chip_border" id="chip_border" value="<?php echo esc_attr($s['chip_border']); ?>" /></td>
        </tr>

      </table>

      <?php submit_button(__('Guardar cambios', 'mpe-investigadores-orcid')); ?>
    </form>

    <hr>
    <h2>Shortcodes</h2>
    <ul>
      <li><code>[investigadores]</code> — listado con buscador/filtro.</li>
      <li><code>[investigador_perfil id="123"]</code> — ficha + publicaciones del investigador.</li>
    </ul>
  </div>
  <?php
}

function mpe_io_count_publicaciones($investigador_id) {
  $q = new WP_Query([
    'post_type' => MPE_IO_CPT_PUB,
    'post_status' => 'publish',
    'posts_per_page' => 1,
    'fields' => 'ids',
    'meta_query' => [
      [
        'key' => '_mpe_investigador_id',
        'value' => intval($investigador_id),
        'compare' => '=',
        'type' => 'NUMERIC',
      ]
    ]
  ]);
  // WP_Query no da found_posts con fields ids? sí.
  return intval($q->found_posts);
}

