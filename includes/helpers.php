<?php
if (!defined('ABSPATH')) exit;

function mpe_io_get_settings() {
  $defaults = [
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

    // Botones
    'btn_bg' => '#2563eb',
    'btn_text' => '#ffffff',
    'btn_hover' => '#1e40af',

    // Etiquetas (chips)
    'chip_bg' => '#dbeafe',
    'chip_text' => '#1d4ed8',
    'chip_border' => '#bfdbfe',
  ];
  $opt = get_option(MPE_IO_OPT);
  if (!is_array($opt)) $opt = [];
  return array_merge($defaults, $opt);
}

/**
 * strtolower seguro (algunos servidores no tienen mbstring).
 */
function mpe_io_lower($s) {
  $s = (string)$s;
  if (function_exists('mb_strtolower')) return mb_strtolower($s);
  return strtolower($s);
}

function mpe_io_css_vars_inline() {
  $s = mpe_io_get_settings();
  $radius = max(0, intval($s['radius']));
  $maxw = max(600, intval($s['maxw']));
  $shadow = intval($s['shadow'] ?? 1);
  $card_style = sanitize_key($s['card_style'] ?? 'pro');

  $primary = sanitize_hex_color($s['primary'] ?? '') ?: '#2563eb';
  $bg = sanitize_hex_color($s['bg'] ?? '') ?: '#ffffff';
  $surface = sanitize_hex_color($s['surface'] ?? '') ?: '#ffffff';
  $text = sanitize_hex_color($s['text'] ?? '') ?: '#0f172a';
  $muted = sanitize_hex_color($s['muted'] ?? '') ?: '#475569';

  $icon_size = max(10, min(48, intval($s['icon_size'] ?? 18)));
  $icon_color = sanitize_hex_color($s['icon_color'] ?? '') ?: '#475569';
  $icon_hover = sanitize_hex_color($s['icon_hover'] ?? '') ?: '#1e3a8a';

  $btn_bg = sanitize_hex_color($s['btn_bg'] ?? '') ?: $primary;
  $btn_text = sanitize_hex_color($s['btn_text'] ?? '') ?: '#ffffff';
  $btn_hover = sanitize_hex_color($s['btn_hover'] ?? '') ?: $primary;

  $chip_bg = sanitize_hex_color($s['chip_bg'] ?? '') ?: '#dbeafe';
  $chip_text = sanitize_hex_color($s['chip_text'] ?? '') ?: '#1d4ed8';
  $chip_border = sanitize_hex_color($s['chip_border'] ?? '') ?: '#bfdbfe';

  $font_sans = trim((string)$s['font_sans']);
  $font_heading = trim((string)$s['font_heading']);
  if ($font_heading === '') $font_heading = 'inherit';

  // $primary, $bg, $surface, $text, $muted ya calculados arriba.

  // Nota: font-family no se puede sanitizar con una función nativa. Hacemos un allowlist muy simple.
  $safe_font = preg_replace('/[^\w\s\-\,\"\'\(\)\.]+/u', '', $font_sans);
  $safe_heading = preg_replace('/[^\w\s\-\,\"\'\(\)\.]+/u', '', $font_heading);

  $css = ":root{\n";
  $css .= "  --mpe-font-sans: {$safe_font};\n";
  $css .= "  --mpe-font-heading: " . ($safe_heading === 'inherit' ? 'var(--mpe-font-sans)' : $safe_heading) . ";\n";
  $css .= "  --mpe-primary: {$primary};\n";
  $css .= "  --mpe-bg: {$bg};\n";
  $css .= "  --mpe-surface: {$surface};\n";
  $css .= "  --mpe-text: {$text};\n";
  $css .= "  --mpe-muted: {$muted};\n";
  $css .= "  --mpe-radius: {$radius}px;\n";
  $css .= "  --mpe-maxw: {$maxw}px;\n";
  $css .= "  --mpe-shadow-on: {$shadow};\n";
  $css .= "  --mpe-card-style: {$card_style};\n";
  $css .= "  --mpe-icon-size: {$icon_size}px;\n";
  $css .= "  --mpe-icon-color: {$icon_color};\n";
  $css .= "  --mpe-icon-hover: {$icon_hover};\n";
  $css .= "  --mpe-btn-bg: {$btn_bg};\n";
  $css .= "  --mpe-btn-text: {$btn_text};\n";
  $css .= "  --mpe-btn-hover: {$btn_hover};\n";
  $css .= "  --mpe-chip-bg: {$chip_bg};\n";
  $css .= "  --mpe-chip-text: {$chip_text};\n";
  $css .= "  --mpe-chip-border: {$chip_border};\n";
  $css .= "}\n";

  return $css;
}

function mpe_io_clean_orcid($orcid) {
  $orcid = strtoupper(trim((string)$orcid));

  // Permitir que el usuario pegue la URL completa, p.ej. https://orcid.org/0000-...
  $orcid = preg_replace('#^https?://(www\.)?orcid\.org/#i', '', $orcid);
  $orcid = trim($orcid);

  // Formato básico ORCID 0000-0000-0000-0000 (último puede ser X)
  if (!preg_match('/^\d{4}-\d{4}-\d{4}-\d{3}[\dX]$/', $orcid)) {
    return '';
  }
  return $orcid;
}

function mpe_io_pub_get_year_from_date($dateArr) {
  // ORCID suele devolver publication-date: { year: { value: "2024" }, month... }
  if (!is_array($dateArr)) return '';
  $y = $dateArr['year']['value'] ?? '';
  $y = preg_replace('/[^0-9]/', '', (string)$y);
  return $y;
}

function mpe_io_pub_get_doi_from_external_ids($externalIds) {
  if (!is_array($externalIds)) return '';
  $items = $externalIds['external-id'] ?? [];
  if (!is_array($items)) return '';
  foreach ($items as $it) {
    if (!is_array($it)) continue;
    $type = strtolower((string)($it['external-id-type'] ?? ''));
    if ($type === 'doi') {
      $val = trim((string)($it['external-id-value'] ?? ''));
      return $val;
    }
  }
  return '';
}

function mpe_io_pub_get_url($summary) {
  // Algunas veces viene url->value
  $u = $summary['url']['value'] ?? '';
  if ($u) return esc_url_raw($u);
  return '';
}



function mpe_io_svg_orcid() {
  return '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M7.5 7.2h-2v9.6h2V7.2zm-.99-3.9a1.2 1.2 0 1 0 0 2.4 1.2 1.2 0 0 0 0-2.4zM12.2 7.2h-3v9.6h3c2.7 0 4.6-1.8 4.6-4.8 0-3-1.9-4.8-4.6-4.8zm-.1 8h-1V8.8h1c1.7 0 2.6 1.1 2.6 3.2 0 2.1-.9 3.2-2.6 3.2z"/><path d="M12 0C5.37 0 0 5.37 0 12s5.37 12 12 12 12-5.37 12-12S18.63 0 12 0zm0 22C6.49 22 2 17.51 2 12S6.49 2 12 2s10 4.49 10 10-4.49 10-10 10z"/></svg>';
}

function mpe_io_svg_linkedin() {
  return '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M20.45 20.45h-3.55v-5.56c0-1.33-.02-3.04-1.85-3.04-1.85 0-2.13 1.45-2.13 2.95v5.65H9.37V9h3.41v1.56h.05c.47-.9 1.63-1.85 3.36-1.85 3.6 0 4.26 2.37 4.26 5.45v6.29zM5.34 7.43a2.06 2.06 0 1 1 0-4.12 2.06 2.06 0 0 1 0 4.12zM7.12 20.45H3.56V9h3.56v11.45z"/></svg>';
}

function mpe_io_svg_scholar() {
  return '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 3 1 9l11 6 9-4.91V17h2V9L12 3zm0 13L4.24 9 12 4.76 19.76 9 12 16z"/><path d="M6 12.5V17c0 1.66 3.13 3 6 3s6-1.34 6-3v-4.5l-6 3.27-6-3.27z"/></svg>';
}
