// ── GRS NESTE ──────────────────────────────────────────
add_shortcode('grs_neste', function($atts) {
    static $style_printed = false;
    $a = shortcode_atts(array('offset' => 0, 'konsept' => '', 'size' => 'stor'), $atts);
    $liten = ($a['size'] === 'liten');
    $tax_query = array();
    if (!empty($a['konsept'])) {
        $tax_query = array(
            'relation' => 'OR',
            array('taxonomy' => 'konsept', 'field' => 'slug', 'terms' => $a['konsept'], 'operator' => 'IN'),
            array('taxonomy' => 'konsept', 'operator' => 'NOT EXISTS'),
        );
    }
    $query = new WP_Query(array(
        'post_type'      => 'konsert',
        'posts_per_page' => 1,
        'offset'         => intval($a['offset']),
        'meta_key'       => 'konsertdato',
        'orderby'        => 'meta_value',
        'order'          => 'ASC',
        'meta_query'     => array(array(
            'key'     => 'konsertdato',
            'value'   => date('Y-m-d'),
            'compare' => '>=',
            'type'    => 'DATE',
        )),
        'tax_query' => $tax_query,
    ));
    if (!$query->have_posts()) return '';
    $query->the_post();
    $post_id     = get_the_ID();
    $dato        = get_field('konsertdato', $post_id);
    $tid         = get_field('konsertstart', $post_id);
    $billettpris = get_field('billettpris', $post_id);
    $billett_url = get_field('billett_url', $post_id);
    $lenke       = get_permalink($post_id);
    $bilde_url   = get_the_post_thumbnail_url($post_id, 'large');
    $tittel      = get_the_title($post_id);
    $scener      = wp_get_post_terms($post_id, 'sted');
    $sted        = (!empty($scener) && !is_wp_error($scener)) ? $scener[0]->name : 'Gamle Raadhus Scene';
    wp_reset_postdata();
    $pris_lower = strtolower(trim((string)$billettpris));
    $gratis = (
        strpos($pris_lower, 'gratis') !== false || strpos($pris_lower, 'fri') !== false ||
        $pris_lower === '0' || $pris_lower === '0,-' ||
        stripos($sted, 'boulebar') !== false || stripos($sted, 'boule bar') !== false
    );
    $dag = $mnd = $aar = '';
    if ($dato) {
        $ts = strtotime($dato);
        $dag = date('j', $ts);
        $mnd_navn = array(1=>'januar',2=>'februar',3=>'mars',4=>'april',5=>'mai',6=>'juni',
                          7=>'juli',8=>'august',9=>'september',10=>'oktober',11=>'november',12=>'desember');
        $mnd = strtoupper($mnd_navn[(int)date('n', $ts)]);
        $aar = date('Y', $ts);
    }
    ob_start();
    if (!$style_printed) {
        $style_printed = true;
        ?>
        <style>
        /* ── SHARED FONT STACK ── */
        .gnk-kort, .gnk-kort *,
        .gnk-mini, .gnk-mini * {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif !important;
            box-sizing: border-box;
        }

        /* ── STOR ── */
        .gnk-kort {
            display: block !important; width: 100% !important; max-width: 100% !important;
            background: #ffffff !important; border-radius: 10px !important;
            overflow: hidden !important; box-shadow: 0 2px 16px rgba(0,0,0,0.09) !important;
            text-decoration: none !important; color: #1a1815 !important;
            cursor: pointer; position: relative !important;
            margin: 0 !important; padding: 0 !important; border: none !important;
        }
        .gnk-kort__bilde {
            display: block !important; width: 100% !important; position: relative !important;
            overflow: hidden !important; aspect-ratio: 16/8 !important;
            margin: 0 !important; padding: 0 !important; background: #111 !important;
        }
        .gnk-kort__bilde img {
            display: block !important; width: 100% !important; height: 100% !important;
            object-fit: cover !important; margin: 0 !important; padding: 0 !important; border: none !important;
        }
        .gnk-kort__gratis {
            position: absolute !important; top: 12px !important; right: 12px !important;
            background: #2a9e2a !important; color: #fff !important;
            font-size: 10px !important; font-weight: 700 !important;
            letter-spacing: 0.12em !important; text-transform: uppercase !important;
            padding: 4px 10px !important; border-radius: 4px !important; line-height: 1.4 !important;
        }
        .gnk-kort__innhold { padding: 16px 20px 18px !important; background: #ffffff !important; margin: 0 !important; }
        .gnk-kort__tittel {
            font-size: 22px !important; font-weight: 600 !important;
            line-height: 1.2 !important; color: #1a1815 !important;
            margin: 0 0 4px 0 !important; padding: 0 !important;
            text-decoration: none !important; display: block !important; border: none !important; background: none !important;
        }
        .gnk-kort__sted { font-size: 12px !important; color: #999 !important; margin: 0 0 14px 0 !important; padding: 0 !important; }
        .gnk-kort__bunn {
            display: flex !important; align-items: center !important; justify-content: space-between !important;
            gap: 10px !important; padding-top: 12px !important; border-top: 1px solid #eeebe6 !important;
            background: #ffffff !important; margin: 0 !important; flex-wrap: wrap !important;
        }
        .gnk-kort__dato { display: flex !important; align-items: center !important; gap: 8px !important; flex-shrink: 0 !important; }
        .gnk-kort__dato-dag {
            font-size: 40px !important; font-weight: 300 !important;
            line-height: 1 !important; color: #1a1815 !important; letter-spacing: -0.02em !important;
        }
        .gnk-kort__dato-info { display: flex !important; flex-direction: column !important; gap: 2px !important; }
        .gnk-kort__dato-mnd {
            font-size: 11px !important; font-weight: 600 !important; letter-spacing: 0.08em !important;
            color: #1a1815 !important; text-transform: uppercase !important;
        }
        .gnk-kort__dato-sub {
            font-size: 11px !important; color: #aaa !important;
            display: flex !important; align-items: center !important; gap: 5px !important;
            white-space: nowrap !important;
        }
        .gnk-kort__knapper {
            display: flex !important; align-items: center !important; gap: 8px !important;
            flex-shrink: 0 !important; flex-wrap: wrap !important;
        }
        .gnk-kort__pris { font-size: 13px !important; font-weight: 500 !important; color: #555 !important; }
        .gnk-kort__btn {
            display: inline-block !important; font-size: 13px !important; font-weight: 500 !important;
            padding: 8px 16px !important; border-radius: 5px !important;
            text-decoration: none !important; line-height: 1.4 !important;
            cursor: pointer !important; border: none !important; white-space: nowrap !important;
        }
        .gnk-kort__btn--sek { background: transparent !important; color: #666 !important; border: 1px solid #ddd !important; }
        .gnk-kort__btn--prim { background: #3a5c33 !important; color: #fff !important; }

        /* ── LITEN ── */
        .gnk-mini {
            display: block !important; width: 100% !important; max-width: 100% !important;
            background: #ffffff !important; border-radius: 10px !important;
            overflow: hidden !important; box-shadow: 0 2px 10px rgba(0,0,0,0.07) !important;
            text-decoration: none !important; color: #1a1815 !important;
            cursor: pointer; margin: 0 !important; padding: 0 !important; border: none !important;
        }
        .gnk-mini__bilde {
            display: block !important; width: 100% !important; position: relative !important;
            overflow: hidden !important; aspect-ratio: 16/6 !important;
            margin: 0 !important; padding: 0 !important; background: #111 !important;
        }
        .gnk-mini__bilde img {
            display: block !important; width: 100% !important; height: 100% !important;
            object-fit: cover !important; margin: 0 !important; padding: 0 !important; border: none !important;
        }
        .gnk-mini__gratis {
            position: absolute !important; top: 8px !important; right: 8px !important;
            background: #2a9e2a !important; color: #fff !important;
            font-size: 8px !important; font-weight: 700 !important;
            letter-spacing: 0.12em !important; text-transform: uppercase !important;
            padding: 3px 7px !important; border-radius: 3px !important; line-height: 1.4 !important;
        }
        .gnk-mini__innhold { padding: 9px 12px 11px !important; background: #ffffff !important; margin: 0 !important; }
        .gnk-mini__tittel {
            font-size: 15px !important; font-weight: 600 !important;
            line-height: 1.2 !important; color: #1a1815 !important;
            margin: 0 0 2px 0 !important; padding: 0 !important;
            text-decoration: none !important; display: block !important; border: none !important; background: none !important;
        }
        .gnk-mini__sted { font-size: 10px !important; color: #999 !important; margin: 0 0 8px 0 !important; padding: 0 !important; }
        .gnk-mini__bunn {
            display: flex !important; align-items: center !important; justify-content: space-between !important;
            gap: 8px !important; padding-top: 8px !important; border-top: 1px solid #eeebe6 !important;
            background: #ffffff !important; margin: 0 !important; flex-wrap: wrap !important;
        }
        .gnk-mini__dato { display: flex !important; align-items: center !important; gap: 5px !important; flex-shrink: 0 !important; }
        .gnk-mini__dato-dag {
            font-size: 26px !important; font-weight: 300 !important;
            line-height: 1 !important; color: #1a1815 !important; letter-spacing: -0.02em !important;
        }
        .gnk-mini__dato-info { display: flex !important; flex-direction: column !important; gap: 1px !important; }
        .gnk-mini__dato-mnd {
            font-size: 9px !important; font-weight: 600 !important; letter-spacing: 0.08em !important;
            color: #1a1815 !important; text-transform: uppercase !important;
        }
        .gnk-mini__dato-sub {
            font-size: 9px !important; color: #aaa !important;
            display: flex !important; align-items: center !important; gap: 3px !important;
            white-space: nowrap !important;
        }
        .gnk-mini__knapper { display: flex !important; align-items: center !important; gap: 6px !important; flex-shrink: 0 !important; }
        .gnk-mini__pris { font-size: 11px !important; font-weight: 500 !important; color: #555 !important; }
        .gnk-mini__btn {
            display: inline-block !important; font-size: 11px !important; font-weight: 500 !important;
            padding: 6px 12px !important; border-radius: 5px !important;
            text-decoration: none !important; line-height: 1.4 !important;
            cursor: pointer !important; border: none !important; white-space: nowrap !important;
        }
        .gnk-mini__btn--sek { background: transparent !important; color: #666 !important; border: 1px solid #ddd !important; }
        .gnk-mini__btn--prim { background: #3a5c33 !important; color: #fff !important; }

        /* ── MOBILE ── */
        @media (max-width: 480px) {
            .gnk-mini__bunn { flex-direction: column !important; align-items: flex-start !important; }
            .gnk-mini__knapper { width: 100% !important; justify-content: flex-end !important; }
            .gnk-kort__bunn { flex-direction: column !important; align-items: flex-start !important; }
            .gnk-kort__knapper { width: 100% !important; justify-content: flex-end !important; }
        }
        </style>
        <?php
    }
    if ($liten): ?>
    <div class="gnk-mini" onclick="window.location='<?php echo esc_js($lenke); ?>'">
        <div class="gnk-mini__bilde">
            <?php if ($bilde_url): ?>
            <img src="<?php echo esc_url($bilde_url); ?>" alt="<?php echo esc_attr($tittel); ?>">
            <?php endif; ?>
            <?php if ($gratis): ?><span class="gnk-mini__gratis">Gratis</span><?php endif; ?>
        </div>
        <div class="gnk-mini__innhold">
            <span class="gnk-mini__tittel"><?php echo esc_html($tittel); ?></span>
            <div class="gnk-mini__sted">📍 <?php echo esc_html($sted); ?></div>
            <div class="gnk-mini__bunn">
                <div class="gnk-mini__dato">
                    <span class="gnk-mini__dato-dag"><?php echo esc_html($dag); ?></span>
                    <div class="gnk-mini__dato-info">
                        <span class="gnk-mini__dato-mnd"><?php echo esc_html($mnd); ?></span>
                        <div class="gnk-mini__dato-sub">
                            <?php if ($aar): ?><span><?php echo esc_html($aar); ?></span><?php endif; ?>
                            <?php if ($tid): ?><span>·</span><span>kl <?php echo esc_html($tid); ?></span><?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="gnk-mini__knapper">
                    <?php if (!$gratis && !empty($billettpris)): ?><span class="gnk-mini__pris"><?php echo esc_html($billettpris); ?></span><?php endif; ?>
                    <a href="<?php echo esc_url($lenke); ?>" class="gnk-mini__btn gnk-mini__btn--sek" onclick="event.stopPropagation()">Les mer</a>
                    <?php if (!$gratis && !empty($billett_url)): ?>
                    <a href="<?php echo esc_url($billett_url); ?>" class="gnk-mini__btn gnk-mini__btn--prim" onclick="event.stopPropagation()">Kjøp billett</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="gnk-kort" onclick="window.location='<?php echo esc_js($lenke); ?>'">
        <div class="gnk-kort__bilde">
            <?php if ($bilde_url): ?>
            <img src="<?php echo esc_url($bilde_url); ?>" alt="<?php echo esc_attr($tittel); ?>">
            <?php endif; ?>
            <?php if ($gratis): ?><span class="gnk-kort__gratis">Gratis</span><?php endif; ?>
        </div>
        <div class="gnk-kort__innhold">
            <span class="gnk-kort__tittel"><?php echo esc_html($tittel); ?></span>
            <div class="gnk-kort__sted">📍 <?php echo esc_html($sted); ?></div>
            <div class="gnk-kort__bunn">
                <div class="gnk-kort__dato">
                    <span class="gnk-kort__dato-dag"><?php echo esc_html($dag); ?></span>
                    <div class="gnk-kort__dato-info">
                        <span class="gnk-kort__dato-mnd"><?php echo esc_html($mnd); ?></span>
                        <div class="gnk-kort__dato-sub">
                            <?php if ($aar): ?><span><?php echo esc_html($aar); ?></span><?php endif; ?>
                            <?php if ($tid): ?><span>·</span><span>kl <?php echo esc_html($tid); ?></span><?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="gnk-kort__knapper">
                    <?php if (!$gratis && !empty($billettpris)): ?><span class="gnk-kort__pris"><?php echo esc_html($billettpris); ?></span><?php endif; ?>
                    <a href="<?php echo esc_url($lenke); ?>" class="gnk-kort__btn gnk-kort__btn--sek" onclick="event.stopPropagation()">Les mer</a>
                    <?php if (!$gratis && !empty($billett_url)): ?>
                    <a href="<?php echo esc_url($billett_url); ?>" class="gnk-kort__btn gnk-kort__btn--prim" onclick="event.stopPropagation()">Kjøp billett</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif;
    return ob_get_clean();
});

// ── GRS KONSERT GRID ───────────────────────────────────
add_shortcode('grs_konsert_grid', function($atts) {
    static $grid_style_printed = false;
    $a = shortcode_atts(array('konsept' => ''), $atts);
    $tax_query = array();
    if (!empty($a['konsept'])) {
        $tax_query = array(
            'relation' => 'OR',
            array('taxonomy' => 'konsept', 'field' => 'slug', 'terms' => $a['konsept'], 'operator' => 'IN'),
            array('taxonomy' => 'konsept', 'operator' => 'NOT EXISTS'),
        );
    }
    $query = new WP_Query(array(
        'post_type'      => 'konsert',
        'posts_per_page' => 3,
        'meta_key'       => 'konsertdato',
        'orderby'        => 'meta_value',
        'order'          => 'ASC',
        'meta_query'     => array(array(
            'key'     => 'konsertdato',
            'value'   => date('Y-m-d'),
            'compare' => '>=',
            'type'    => 'DATE',
        )),
        'tax_query' => $tax_query,
    ));
    if (!$query->have_posts()) return '';
    $konsert = array();
    while ($query->have_posts()) {
        $query->the_post();
        $pid    = get_the_ID();
        $dato   = get_field('konsertdato', $pid);
        $ts     = $dato ? strtotime($dato) : 0;
        $mnd_navn = array(1=>'jan',2=>'feb',3=>'mar',4=>'apr',5=>'mai',6=>'jun',
                          7=>'jul',8=>'aug',9=>'sep',10=>'okt',11=>'nov',12=>'des');
        $scener = wp_get_post_terms($pid, 'sted');
        $sted   = (!empty($scener) && !is_wp_error($scener)) ? $scener[0]->name : 'Gamle Raadhus Scene';
        $pris   = get_field('billettpris', $pid);
        $pris_l = strtolower(trim((string)$pris));
        $gratis = (
            strpos($pris_l, 'gratis') !== false || strpos($pris_l, 'fri') !== false ||
            $pris_l === '0' || $pris_l === '0,-' ||
            stripos($sted, 'boulebar') !== false || stripos($sted, 'boule bar') !== false
        );
        $konsert[] = array(
            'tittel'  => get_the_title($pid),
            'lenke'   => get_permalink($pid),
            'bilde'   => get_the_post_thumbnail_url($pid, 'large'),
            'dag'     => $ts ? date('j', $ts) : '',
            'mnd'     => $ts ? strtoupper($mnd_navn[(int)date('n', $ts)]) : '',
            'aar'     => $ts ? date('Y', $ts) : '',
            'tid'     => get_field('konsertstart', $pid),
            'sted'    => $sted,
            'pris'    => $pris,
            'billett' => get_field('billett_url', $pid),
            'gratis'  => $gratis,
        );
    }
    wp_reset_postdata();
    if (empty($konsert)) return '';
    ob_start();
    if (!$grid_style_printed) {
        $grid_style_printed = true;
        ?>
        <style>
        /* ── GRID SHARED FONT ── */
        .grsg-grid, .grsg-grid * {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            box-sizing: border-box;
        }

        .grsg-grid {
            display: grid;
            grid-template-columns: 3fr 2fr;
            gap: 14px;
            align-items: stretch;
            width: 100%;
        }

        /* ── STOR ── */
        .grsg-stor {
            display: flex; flex-direction: column;
            background: #fff; border-radius: 10px; overflow: hidden;
            box-shadow: 0 2px 14px rgba(0,0,0,0.08); cursor: pointer; height: 100%;
        }
        .grsg-stor__bilde { position: relative; width: 100%; aspect-ratio: 4/3; overflow: hidden; flex-shrink: 0; }
        .grsg-stor__bilde img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .grsg-stor__badge {
            position: absolute; top: 10px; right: 10px;
            background: #2a9e2a; color: #fff;
            font-size: 10px; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase;
            padding: 3px 9px; border-radius: 3px;
        }
        .grsg-stor__innhold { padding: 16px 18px 18px; display: flex; flex-direction: column; flex: 1; }
        .grsg-stor__tittel {
            font-size: 22px; font-weight: 600; line-height: 1.2;
            color: #1a1815 !important; margin: 0 0 5px; text-decoration: none; display: block;
        }
        .grsg-stor__sted { font-size: 11px; color: #aaa; margin-bottom: 14px; }
        .grsg-stor__bunn {
            display: flex; align-items: center; justify-content: space-between;
            gap: 8px; margin-top: auto; padding-top: 12px; border-top: 1px solid #eeebe6;
            flex-wrap: wrap;
        }
        .grsg-stor__dato { display: flex; align-items: center; gap: 6px; flex-shrink: 0; }
        .grsg-stor__dag { font-size: 36px; font-weight: 300; line-height: 1; color: #1a1815; letter-spacing: -0.02em; }
        .grsg-stor__dato-info { display: flex; flex-direction: column; gap: 2px; }
        .grsg-stor__mnd { font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.08em; color: #1a1815; }
        .grsg-stor__sub { font-size: 10px; color: #aaa; display: flex; align-items: center; gap: 4px; white-space: nowrap; }
        .grsg-stor__knapper { display: flex; align-items: center; gap: 7px; flex-shrink: 0; }
        .grsg-stor__pris { font-size: 12px; font-weight: 500; color: #555; }

        /* ── SIDE ── */
        .grsg-side { display: flex; flex-direction: column; gap: 14px; height: 100%; }
        .grsg-mini {
            flex: 1; min-height: 0; display: flex; flex-direction: column;
            background: #fff; border-radius: 10px; overflow: hidden;
            box-shadow: 0 2px 14px rgba(0,0,0,0.08); cursor: pointer;
        }
        .grsg-mini__bilde { position: relative; width: 100%; flex: 1; min-height: 60px; overflow: hidden; }
        .grsg-mini__bilde img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .grsg-mini__badge {
            position: absolute; top: 6px; right: 6px;
            background: #2a9e2a; color: #fff;
            font-size: 8px; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase;
            padding: 2px 6px; border-radius: 3px;
        }
        .grsg-mini__innhold { padding: 8px 10px 10px; flex-shrink: 0; }
        .grsg-mini__tittel {
            font-size: 13px; font-weight: 600; line-height: 1.25;
            color: #1a1815 !important; margin: 0 0 2px;
            display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
        }
        .grsg-mini__sted { font-size: 9px; color: #aaa; margin-bottom: 6px; }
        .grsg-mini__bunn {
            display: flex; align-items: center; justify-content: space-between;
            padding-top: 6px; border-top: 1px solid #eeebe6;
            gap: 6px; flex-wrap: wrap;
        }
        .grsg-mini__dato { display: flex; align-items: center; gap: 4px; flex-shrink: 0; }
        .grsg-mini__dag { font-size: 20px; font-weight: 300; line-height: 1; color: #1a1815; letter-spacing: -0.02em; }
        .grsg-mini__dato-info { display: flex; flex-direction: column; }
        .grsg-mini__mnd { font-size: 8px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.06em; color: #1a1815; }
        .grsg-mini__sub { display: flex; align-items: center; gap: 3px; font-size: 8px; color: #aaa; white-space: nowrap; }
        .grsg-mini__knapper { display: flex; align-items: center; gap: 4px; flex-shrink: 0; }
        .grsg-mini__pris { font-size: 9px; font-weight: 500; color: #555; }

        /* ── BUTTONS ── */
        .grsg-btn, .grsg-mini-btn {
            font-weight: 500; border-radius: 5px;
            text-decoration: none !important; cursor: pointer;
            display: inline-block; white-space: nowrap;
        }
        .grsg-btn      { font-size: 12px; padding: 7px 14px; }
        .grsg-mini-btn { font-size: 9px;  padding: 4px 8px; }
        .grsg-btn--sek,      .grsg-mini-btn--sek { background: transparent; color: #666 !important; border: 1px solid #ddd; }
        .grsg-btn--prim,     .grsg-mini-btn--prim { background: #3a5c33; color: #fff !important; border: none; }

        /* ── RESPONSIVE ── */
        @media (max-width: 768px) {
            .grsg-grid { grid-template-columns: 1fr; }
            .grsg-side { height: auto; }
            .grsg-mini { flex: none; min-height: 0; }
            .grsg-mini__bilde { height: 160px; flex: none; }
            .grsg-stor__bilde { aspect-ratio: 16/7; }
        }
        @media (max-width: 480px) {
            .grsg-stor__bunn  { flex-direction: column; align-items: flex-start; }
            .grsg-stor__knapper { width: 100%; justify-content: flex-end; }
            .grsg-mini__bunn  { flex-direction: column; align-items: flex-start; }
            .grsg-mini__knapper { width: 100%; justify-content: flex-end; }
        }
        </style>
        <?php
    }
    ?>
    <div class="grsg-grid">
        <?php if (!empty($konsert[0])): $k = $konsert[0]; ?>
        <div class="grsg-stor" onclick="window.location='<?php echo esc_js($k['lenke']); ?>'">
            <?php if ($k['bilde']): ?>
            <div class="grsg-stor__bilde">
                <img src="<?php echo esc_url($k['bilde']); ?>" alt="<?php echo esc_attr($k['tittel']); ?>">
                <?php if ($k['gratis']): ?><span class="grsg-stor__badge">Gratis</span><?php endif; ?>
            </div>
            <?php endif; ?>
            <div class="grsg-stor__innhold">
                <span class="grsg-stor__tittel"><?php echo esc_html($k['tittel']); ?></span>
                <div class="grsg-stor__sted">📍 <?php echo esc_html($k['sted']); ?></div>
                <div class="grsg-stor__bunn">
                    <div class="grsg-stor__dato">
                        <span class="grsg-stor__dag"><?php echo esc_html($k['dag']); ?></span>
                        <div class="grsg-stor__dato-info">
                            <span class="grsg-stor__mnd"><?php echo esc_html($k['mnd']); ?></span>
                            <div class="grsg-stor__sub">
                                <span><?php echo esc_html($k['aar']); ?></span>
                                <?php if ($k['tid']): ?><span>·</span><span>kl <?php echo esc_html($k['tid']); ?></span><?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="grsg-stor__knapper">
                        <?php if (!$k['gratis'] && $k['pris']): ?><span class="grsg-stor__pris"><?php echo esc_html($k['pris']); ?></span><?php endif; ?>
                        <a href="<?php echo esc_url($k['lenke']); ?>" class="grsg-btn grsg-btn--sek" onclick="event.stopPropagation()">Les mer</a>
                        <?php if (!$k['gratis'] && $k['billett']): ?>
                        <a href="<?php echo esc_url($k['billett']); ?>" class="grsg-btn grsg-btn--prim" onclick="event.stopPropagation()">Kjøp billett</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="grsg-side">
            <?php foreach (array(1, 2) as $idx):
                if (empty($konsert[$idx])) continue;
                $k = $konsert[$idx]; ?>
            <div class="grsg-mini" onclick="window.location='<?php echo esc_js($k['lenke']); ?>'">
                <?php if ($k['bilde']): ?>
                <div class="grsg-mini__bilde">
                    <img src="<?php echo esc_url($k['bilde']); ?>" alt="<?php echo esc_attr($k['tittel']); ?>">
                    <?php if ($k['gratis']): ?><span class="grsg-mini__badge">Gratis</span><?php endif; ?>
                </div>
                <?php endif; ?>
                <div class="grsg-mini__innhold">
                    <div class="grsg-mini__tittel"><?php echo esc_html($k['tittel']); ?></div>
                    <div class="grsg-mini__sted">📍 <?php echo esc_html($k['sted']); ?></div>
                    <div class="grsg-mini__bunn">
                        <div class="grsg-mini__dato">
                            <span class="grsg-mini__dag"><?php echo esc_html($k['dag']); ?></span>
                            <div class="grsg-mini__dato-info">
                                <span class="grsg-mini__mnd"><?php echo esc_html($k['mnd']); ?></span>
                                <div class="grsg-mini__sub">
                                    <span><?php echo esc_html($k['aar']); ?></span>
                                    <?php if ($k['tid']): ?><span>·</span><span>kl <?php echo esc_html($k['tid']); ?></span><?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="grsg-mini__knapper">
                            <?php if (!$k['gratis'] && $k['pris']): ?><span class="grsg-mini__pris"><?php echo esc_html($k['pris']); ?></span><?php endif; ?>
                            <a href="<?php echo esc_url($k['lenke']); ?>" class="grsg-mini-btn grsg-mini-btn--sek" onclick="event.stopPropagation()">Les mer</a>
                            <?php if (!$k['gratis'] && $k['billett']): ?>
                            <a href="<?php echo esc_url($k['billett']); ?>" class="grsg-mini-btn grsg-mini-btn--prim" onclick="event.stopPropagation()">Kjøp billett</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
});
