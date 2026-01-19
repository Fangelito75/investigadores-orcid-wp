<?php
if (!defined('ABSPATH')) exit;

// API pública ORCID v3.0 (base URL producción)
// https://pub.orcid.org/v3.0/

function mpe_io_orcid_get_json($url) {
  $args = [
    'timeout' => 25,
    'redirection' => 5,
    'headers' => [
      // ORCID admite negociación de contenido; este suele ser el JSON recomendado.
      'Accept' => 'application/vnd.orcid+json',
      // Identifícate con un User-Agent útil (idealmente con tu dominio real)
      'User-Agent' => 'WP-MPE-Investigadores/1.0 (+https://tusitio.ejemplo)',
    ],
  ];

  $resp = wp_remote_get($url, $args);

  if (is_wp_error($resp)) return $resp;

  $code = wp_remote_retrieve_response_code($resp);
  // Algunos servidores devuelven 406 si no les gusta el Accept; probamos un fallback.
  if ($code === 406) {
    $args['headers']['Accept'] = 'application/json';
    $resp = wp_remote_get($url, $args);
    if (is_wp_error($resp)) return $resp;
    $code = wp_remote_retrieve_response_code($resp);
  }

  if ($code !== 200) {
    $body = wp_remote_retrieve_body($resp);
    $snippet = trim(wp_strip_all_tags((string)$body));
    $snippet = substr($snippet, 0, 160);
    return new WP_Error('mpe_orcid_http', 'ORCID devolvió HTTP ' . $code . ($snippet ? (' — ' . $snippet) : ''));
  }

  $body = wp_remote_retrieve_body($resp);
  $json = json_decode($body, true);
  if (!is_array($json)) {
    return new WP_Error('mpe_orcid_json', 'Respuesta ORCID no es JSON válido');
  }

  return $json;
}

function mpe_io_orcid_import_publicaciones($investigador_id) {
  $orcid = get_post_meta($investigador_id, MPE_IO_META_ORCID, true);
  $orcid = mpe_io_clean_orcid($orcid);
  if (!$orcid) {
    return new WP_Error('mpe_orcid_missing', 'ORCID inválido o ausente.');
  }

  // Cache: 10 minutos (evitar "polling" constante; buena práctica)
  $cache_key = 'mpe_orcid_works_' . md5($orcid);
  $works = get_transient($cache_key);

  if (!$works) {
    $url = 'https://pub.orcid.org/v3.0/' . rawurlencode($orcid) . '/works';
    $works = mpe_io_orcid_get_json($url);
    if (is_wp_error($works)) return $works;
    set_transient($cache_key, $works, 10 * MINUTE_IN_SECONDS);
  }

  $groups = $works['group'] ?? [];
  if (!is_array($groups)) $groups = [];

  $created = 0;
  $updated = 0;
  $skipped = 0;

  foreach ($groups as $g) {
    if (!is_array($g)) continue;
    $summaries = $g['work-summary'] ?? [];
    if (!is_array($summaries)) continue;

    foreach ($summaries as $s) {
      if (!is_array($s)) continue;

      $put = $s['put-code'] ?? null;
      if ($put === null) continue;
      $put = intval($put);

      $title = $s['title']['title']['value'] ?? '';
      $title = trim((string)$title);
      if ($title === '') $title = 'Publicación (ORCID)';

      $type = sanitize_text_field($s['type'] ?? '');
      $year = mpe_io_pub_get_year_from_date($s['publication-date'] ?? []);
      $doi = mpe_io_pub_get_doi_from_external_ids($s['external-ids'] ?? []);
      $url = mpe_io_pub_get_url($s);

      // ¿Existe ya por put-code?
      $existing = get_posts([
        'post_type' => MPE_IO_CPT_PUB,
        'post_status' => 'publish',
        'fields' => 'ids',
        'posts_per_page' => 1,
        'meta_query' => [
          [ 'key' => '_mpe_orcid_put_code', 'value' => $put, 'compare' => '=' ],
          [ 'key' => '_mpe_investigador_id', 'value' => intval($investigador_id), 'compare' => '=', 'type' => 'NUMERIC' ],
        ],
      ]);

      if ($existing) {
        $pub_id = intval($existing[0]);
        // Actualiza si cambió algo
        $changed = false;

        if (get_the_title($pub_id) !== $title) {
          wp_update_post(['ID' => $pub_id, 'post_title' => $title]);
          $changed = true;
        }

        $changed = mpe_io_update_meta_if_diff($pub_id, '_mpe_pub_type', $type) || $changed;
        $changed = mpe_io_update_meta_if_diff($pub_id, '_mpe_pub_year', $year) || $changed;
        $changed = mpe_io_update_meta_if_diff($pub_id, '_mpe_pub_doi', $doi) || $changed;
        $changed = mpe_io_update_meta_if_diff($pub_id, '_mpe_pub_url', $url) || $changed;

        if ($changed) $updated++; else $skipped++;
        continue;
      }

      $pub_id = wp_insert_post([
        'post_type' => MPE_IO_CPT_PUB,
        'post_status' => 'publish',
        'post_title' => $title,
      ], true);

      if (is_wp_error($pub_id)) continue;

      update_post_meta($pub_id, '_mpe_orcid_put_code', $put);
      update_post_meta($pub_id, '_mpe_investigador_id', intval($investigador_id));
      update_post_meta($pub_id, '_mpe_pub_type', $type);
      update_post_meta($pub_id, '_mpe_pub_year', $year);
      update_post_meta($pub_id, '_mpe_pub_doi', $doi);
      update_post_meta($pub_id, '_mpe_pub_url', $url);

      $created++;
    }
  }

  update_post_meta($investigador_id, MPE_IO_META_LAST_SYNC, time());

  return [
    'created' => $created,
    'updated' => $updated,
    'skipped' => $skipped,
    'total_groups' => count($groups),
  ];
}

function mpe_io_update_meta_if_diff($post_id, $key, $new) {
  $old = get_post_meta($post_id, $key, true);
  if ((string)$old !== (string)$new) {
    update_post_meta($post_id, $key, $new);
    return true;
  }
  return false;
}

function mpe_io_ajax_orcid_import() {
  if (!current_user_can('edit_posts')) {
    wp_send_json_error(['message' => 'No autorizado'], 403);
  }
  check_ajax_referer('mpe_io_orcid_import', 'nonce');

  $post_id = intval($_POST['post_id'] ?? 0);
  if (!$post_id || get_post_type($post_id) !== MPE_IO_CPT_INV) {
    wp_send_json_error(['message' => 'Investigador inválido'], 400);
  }

  if (!current_user_can('edit_post', $post_id)) {
    wp_send_json_error(['message' => 'No autorizado'], 403);
  }

  $res = mpe_io_orcid_import_publicaciones($post_id);
  if (is_wp_error($res)) {
    wp_send_json_error(['message' => $res->get_error_message()], 500);
  }

  wp_send_json_success([
    'message' => 'Importación completada',
    'stats' => $res,
    'last_sync' => date_i18n(get_option('date_format') . ' ' . get_option('time_format'), time()),
  ]);
}

