<?php
if (!defined('ABSPATH')) exit;

function mpe_io_public_assets() {
  wp_register_style('mpe-io-public', MPE_IO_URL . 'assets/css/public.css', [], MPE_IO_VERSION);
  wp_register_script('mpe-io-public', MPE_IO_URL . 'assets/js/public.js', [], MPE_IO_VERSION, true);

  // Siempre añadimos variables CSS inline cuando se use el stylesheet
  wp_add_inline_style('mpe-io-public', mpe_io_css_vars_inline());
}

function mpe_io_shortcode_investigadores($atts) {
  wp_enqueue_style('mpe-io-public');
  wp_enqueue_script('mpe-io-public');

  $atts = shortcode_atts([
    'per_page' => 999,
    'orderby' => 'title',
    'order' => 'ASC',
  ], $atts);

  $q = new WP_Query([
    'post_type' => MPE_IO_CPT_INV,
    'post_status' => 'publish',
    'posts_per_page' => intval($atts['per_page']),
    'orderby' => sanitize_text_field($atts['orderby']),
    'order' => sanitize_text_field($atts['order']),
  ]);

  ob_start();
  ?>
  <div class="mpe-wrap">
    <header class="mpe-header">
      <div>
        <h2 class="mpe-title">Personal Investigador</h2>
        <p class="mpe-subtitle">Busca por nombre o filtra por área.</p>
      </div>
      <div class="mpe-toolbar">
        <input class="mpe-input" type="search" placeholder="Buscar..." data-mpe-search>
        <select class="mpe-select" data-mpe-area>
          <option value="">Todas las áreas</option>
          <?php
          // Construir listado de áreas
          $areas = [];
          if ($q->have_posts()) {
            foreach ($q->posts as $p) {
              $a = get_post_meta($p->ID, MPE_IO_META_AREA, true);
              $a = trim((string)$a);
              if ($a !== '') $areas[$a] = true;
            }
          }
          $areas = array_keys($areas);
          sort($areas, SORT_NATURAL | SORT_FLAG_CASE);
          foreach ($areas as $a) {
            echo '<option value="' . esc_attr(mpe_io_lower($a)) . '">' . esc_html($a) . '</option>';
          }
          ?>
        </select>
      </div>
    </header>

    <section class="mpe-grid" data-mpe-grid>
      <?php if ($q->have_posts()): while ($q->have_posts()): $q->the_post();
        $id = get_the_ID();
        $puesto = get_post_meta($id, MPE_IO_META_PUESTO, true);
        $area = get_post_meta($id, MPE_IO_META_AREA, true);
        $orcid = get_post_meta($id, MPE_IO_META_ORCID, true);
        $linkedin = get_post_meta($id, MPE_IO_META_LINKEDIN, true);
        $scholar = get_post_meta($id, MPE_IO_META_SCHOLAR, true);
        $orcid_link = $orcid ? 'https://orcid.org/' . rawurlencode($orcid) : '';
        $thumb = get_the_post_thumbnail_url($id, 'medium');
        $thumb = $thumb ?: 'data:image/svg+xml;charset=utf-8,' . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" width="300" height="300"><rect width="100%" height="100%" fill="#f1f5f9"/><text x="50%" y="52%" text-anchor="middle" font-size="18" fill="#64748b" font-family="Arial">Foto</text></svg>');
        $perfil_url = get_permalink($id);

        $search_blob = mpe_io_lower(get_the_title() . ' ' . $puesto . ' ' . $area);
        $area_blob = mpe_io_lower((string)$area);
      ?>
        <article class="mpe-card" data-mpe-item data-search="<?php echo esc_attr($search_blob); ?>" data-area="<?php echo esc_attr($area_blob); ?>">
          <img class="mpe-avatar" src="<?php echo esc_url($thumb); ?>" alt="<?php echo esc_attr(get_the_title()); ?>">
          <div class="mpe-card-body">
            <h3 class="mpe-name"><?php the_title(); ?></h3>
            <p class="mpe-meta"><?php echo esc_html(trim($puesto . ($puesto && $area ? ' · ' : '') . $area)); ?></p>

            <div class="mpe-tags">
              <?php if ($area): ?><span class="mpe-chip"><?php echo esc_html($area); ?></span><?php endif; ?>
              <?php if ($orcid): ?>
                <span class="mpe-chip">ORCID: <a class="mpe-link" href="<?php echo esc_url($orcid_link); ?>" rel="nofollow noopener" target="_blank"><?php echo esc_html($orcid); ?></a></span>
              <?php endif; ?>
            </div>

            <div class="mpe-actions">
              <a class="mpe-btn mpe-btn-primary" href="<?php echo esc_url($perfil_url); ?>">Ver perfil</a>

              <div class="mpe-socials" aria-label="Perfiles externos">
                <?php if ($orcid): ?>
                  <a class="mpe-social" href="<?php echo esc_url($orcid_link); ?>" target="_blank" rel="nofollow noopener" aria-label="ORCID"><?php echo mpe_io_svg_orcid(); ?></a>
                <?php endif; ?>
                <?php if ($linkedin): ?>
                  <a class="mpe-social" href="<?php echo esc_url($linkedin); ?>" target="_blank" rel="nofollow noopener" aria-label="LinkedIn"><?php echo mpe_io_svg_linkedin(); ?></a>
                <?php endif; ?>
                <?php if ($scholar): ?>
                  <a class="mpe-social" href="<?php echo esc_url($scholar); ?>" target="_blank" rel="nofollow noopener" aria-label="Google Scholar"><?php echo mpe_io_svg_scholar(); ?></a>
                <?php endif; ?>
              </div>
              <?php if ($orcid): ?>
                <a class="mpe-btn mpe-btn-ghost" href="<?php echo esc_url($orcid_link); ?>" rel="nofollow noopener" target="_blank">ORCID</a>
              <?php endif; ?>
            </div>
          </div>
        </article>
      <?php endwhile; wp_reset_postdata(); else: ?>
        <p>No hay investigadores publicados.</p>
      <?php endif; ?>
    </section>

    <p class="mpe-empty" data-mpe-empty style="display:none;">No se han encontrado resultados.</p>
  </div>
  <?php
  return ob_get_clean();
}

function mpe_io_shortcode_investigador_perfil($atts) {
  wp_enqueue_style('mpe-io-public');

  $atts = shortcode_atts([
    'id' => 0,
  ], $atts);

  $id = intval($atts['id']);
  if (!$id) return '<p>Falta el id del investigador.</p>';
  if (get_post_type($id) !== MPE_IO_CPT_INV) return '<p>Investigador no válido.</p>';

  $puesto = get_post_meta($id, MPE_IO_META_PUESTO, true);
  $area = get_post_meta($id, MPE_IO_META_AREA, true);
  $email = get_post_meta($id, MPE_IO_META_EMAIL, true);
  $tel = get_post_meta($id, MPE_IO_META_TEL, true);
  $web = get_post_meta($id, MPE_IO_META_WEB, true);
  $linkedin = get_post_meta($id, MPE_IO_META_LINKEDIN, true);
  $scholar = get_post_meta($id, MPE_IO_META_SCHOLAR, true);
  $cv = get_post_meta($id, MPE_IO_META_CV, true);
  $orcid = get_post_meta($id, MPE_IO_META_ORCID, true);
  $orcid_link = $orcid ? 'https://orcid.org/' . rawurlencode($orcid) : '';

  $thumb = get_the_post_thumbnail_url($id, 'large');
  $thumb = $thumb ?: 'data:image/svg+xml;charset=utf-8,' . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" width="300" height="300"><rect width="100%" height="100%" fill="#f1f5f9"/><text x="50%" y="52%" text-anchor="middle" font-size="18" fill="#64748b" font-family="Arial">Foto</text></svg>');

  $bio = get_post_field('post_content', $id);

  $pubs = get_posts([
    'post_type' => MPE_IO_CPT_PUB,
    'post_status' => 'publish',
    'posts_per_page' => 200,
    'orderby' => 'meta_value_num',
    'meta_key' => '_mpe_pub_year',
    'order' => 'DESC',
    'meta_query' => [
      [
        'key' => '_mpe_investigador_id',
        'value' => $id,
        'compare' => '=',
        'type' => 'NUMERIC',
      ]
    ]
  ]);

  ob_start();
  ?>
  <div class="mpe-wrap">
    <section class="mpe-profile">
      <div class="mpe-profile-header">
        <img class="mpe-avatar" src="<?php echo esc_url($thumb); ?>" alt="<?php echo esc_attr(get_the_title($id)); ?>">
        <div>
          <h2 class="mpe-name" style="font-size:22px;margin:0;"><?php echo esc_html(get_the_title($id)); ?></h2>
          <p class="mpe-meta" style="margin-top:6px;">
            <?php echo esc_html(trim($puesto . ($puesto && $area ? ' · ' : '') . $area)); ?>
            <?php if ($orcid): ?> · ORCID: <a class="mpe-link" href="<?php echo esc_url($orcid_link); ?>" rel="nofollow noopener" target="_blank"><?php echo esc_html($orcid); ?></a><?php endif; ?>
          </p>
          <div class="mpe-actions" style="margin-top:10px;">
            <?php if ($email): ?><a class="mpe-btn mpe-btn-primary" href="mailto:<?php echo esc_attr($email); ?>">Email</a><?php endif; ?>
            <?php if ($web): ?><a class="mpe-btn mpe-btn-ghost" href="<?php echo esc_url($web); ?>" target="_blank" rel="nofollow noopener">Web</a><?php endif; ?>
            <?php if ($cv): ?><a class="mpe-btn mpe-btn-ghost" href="<?php echo esc_url($cv); ?>" target="_blank" rel="nofollow noopener">CV (PDF)</a><?php endif; ?>
          </div>

          <div class="mpe-socials mpe-socials-profile" aria-label="Perfiles externos">
            <?php if ($orcid): ?><a class="mpe-social" href="<?php echo esc_url($orcid_link); ?>" target="_blank" rel="nofollow noopener" aria-label="ORCID"><?php echo mpe_io_svg_orcid(); ?></a><?php endif; ?>
            <?php if ($linkedin): ?><a class="mpe-social" href="<?php echo esc_url($linkedin); ?>" target="_blank" rel="nofollow noopener" aria-label="LinkedIn"><?php echo mpe_io_svg_linkedin(); ?></a><?php endif; ?>
            <?php if ($scholar): ?><a class="mpe-social" href="<?php echo esc_url($scholar); ?>" target="_blank" rel="nofollow noopener" aria-label="Google Scholar"><?php echo mpe_io_svg_scholar(); ?></a><?php endif; ?>
          </div>
        </div>
      </div>

      <?php if ($bio): ?>
        <div class="mpe-bio"><?php echo wp_kses_post(wpautop($bio)); ?></div>
      <?php endif; ?>

      <h3 class="mpe-section-title">Publicaciones</h3>

      <?php if ($pubs): ?>
        <ul class="mpe-pubs">
          <?php foreach ($pubs as $p):
            $pid = $p->ID;
            $year = get_post_meta($pid, '_mpe_pub_year', true);
            $type = get_post_meta($pid, '_mpe_pub_type', true);
            $doi = get_post_meta($pid, '_mpe_pub_doi', true);
            $url = get_post_meta($pid, '_mpe_pub_url', true);

            $doi_url = $doi ? ('https://doi.org/' . rawurlencode($doi)) : '';
          ?>
            <li class="mpe-pub">
              <p class="mpe-pub-title"><?php echo esc_html(get_the_title($pid)); ?><?php echo $year ? ' (' . esc_html($year) . ')' : ''; ?></p>
              <div class="mpe-pub-meta">
                <?php if ($type): ?><span><?php echo esc_html($type); ?></span><?php endif; ?>
                <?php if ($doi): ?><span>DOI: <a class="mpe-link" href="<?php echo esc_url($doi_url); ?>" target="_blank" rel="nofollow noopener"><?php echo esc_html($doi); ?></a></span><?php endif; ?>
                <?php if ($url): ?><span><a class="mpe-link" href="<?php echo esc_url($url); ?>" target="_blank" rel="nofollow noopener">Enlace</a></span><?php endif; ?>
                <?php if ($orcid): ?><span><a class="mpe-link" href="<?php echo esc_url('https://orcid.org/' . rawurlencode($orcid)); ?>" target="_blank" rel="nofollow noopener">Ver en ORCID</a></span><?php endif; ?>
              </div>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php else: ?>
        <p>No hay publicaciones importadas todavía.</p>
      <?php endif; ?>
    </section>
  </div>
  <?php
  return ob_get_clean();
}

/**
 * TEMPLATE propio para la ficha (single) del CPT investigador.
 *
 * - Si el tema define `single-investigador.php` o `mpe-io/single-investigador.php`, se usará el del tema.
 * - Si no, se usa el template incluido en el plugin.
 */
function mpe_io_single_template($single_template) {
  if (!is_singular(MPE_IO_CPT_INV)) return $single_template;

  // Permitir override desde el tema
  $theme_template = locate_template([
    'mpe-io/single-investigador.php',
    'single-investigador.php',
  ]);
  if ($theme_template) return $theme_template;

  $plugin_template = MPE_IO_PATH . 'templates/single-investigador.php';
  if (file_exists($plugin_template)) return $plugin_template;

  return $single_template;
}

