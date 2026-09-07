// ── GRS NESTE ──────────────────────────────────────────
add_shortcode('grs_neste', function($atts) {
    static $style_printed = false;
    $a = shortcode_atts(array('offset' => 0, 'konsept' => '', 'size' => 'stor'), $atts);
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
        @import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;600&family=Inter:wght@400;500;600&display=swap');
        .gnk-kort {
            display: block !important; width: 100% !important; max-width: 100% !important;
            background: #ffffff !important; border-radius: 10px !important;
            overflow: hidden !important; box-shadow: 0 2px 16px rgba(0,0,0,0.09) !important;
            text-decoration: none !important; color: #1a1815 !important;
            font-family: 'Inter', sans-serif !important; cursor: pointer;
            position: relative !important; margin: 0 !important; padding: 0 !important;
            border: none !important; box-sizing: border-box !important;
        }
        .gnk-kort * { box-sizing: border-box; }
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

        /* ── Glass content panel ──────────────────────────────
           Pulled up over the bottom of the photo (negative
           margin-top) so backdrop-filter blurs the real image
           behind it. Base tint is the color sampled from the
           image right at the seam (see the companion script),
           darkened slightly, with white text on top. */
        .gnk-kort__innhold {
            position: relative !important;
            z-index: 2 !important;
            margin: -54px 14px 0 !important;
            padding: 16px 20px 18px !important;
            border-radius: 12px !important;
            background: var(--gnk-tint, rgba(26,24,21,0.62)) !important;
            -webkit-backdrop-filter: blur(16px) !important;
            backdrop-filter: blur(16px) !important;
            backdrop-filter: blur(16px) saturate(150%) url(#gnkGlassDistortion) !important;
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.18) !important;
            overflow: hidden !important;
        }
        .gnk-kort__tittel {
            font-family: 'Cormorant Garamond', serif !important; font-size: 26px !important;
            font-weight: 600 !important; line-height: 1.2 !important; color: #ffffff !important;
            margin: 0 0 4px 0 !important; padding: 0 !important;
            text-decoration: none !important; display: block !important; border: none !important; background: none !important;
        }
        .gnk-kort__sted { font-size: 12px !important; color: rgba(255,255,255,0.72) !important; margin: 0 0 14px 0 !important; padding: 0 !important; }
        .gnk-kort__bunn {
            display: flex !important; align-items: center !important; justify-content: space-between !important;
            gap: 10px !important; padding-top: 12px !important; border-top: 1px solid rgba(255,255,255,0.22) !important;
            background: none !important; margin: 0 !important;
        }
        .gnk-kort__dato { display: flex !important; align-items: center !important; gap: 8px !important; }
        .gnk-kort__dato-dag {
            font-family: 'Cormorant Garamond', serif !important; font-size: 44px !important;
            font-weight: 600 !important; line-height: 1 !important; color: #ffffff !important;
        }
        .gnk-kort__dato-info { display: flex !important; flex-direction: column !important; gap: 3px !important; }
        .gnk-kort__dato-mnd {
            font-size: 11px !important; font-weight: 600 !important; letter-spacing: 0.05em !important;
            color: #ffffff !important; text-transform: uppercase !important;
        }
        .gnk-kort__dato-sub {
            font-size: 11px !important; color: rgba(255,255,255,0.62) !important;
            display: flex !important; align-items: center !important; gap: 5px !important;
        }
        .gnk-kort__knapper { display: flex !important; align-items: center !important; gap: 8px !important; flex-shrink: 0 !important; }
        .gnk-kort__pris { font-size: 13px !important; font-weight: 500 !important; color: rgba(255,255,255,0.82) !important; }
        .gnk-kort__btn {
            display: inline-block !important; font-family: 'Inter', sans-serif !important;
            font-size: 13px !important; font-weight: 500 !important; padding: 8px 16px !important;
            border-radius: 5px !important; text-decoration: none !important; line-height: 1.4 !important;
            cursor: pointer !important; border: none !important;
        }
        .gnk-kort__btn--sek { background: rgba(255,255,255,0.10) !important; color: #ffffff !important; border: 1px solid rgba(255,255,255,0.35) !important; }
        .gnk-kort__btn--prim { background: #3a5c33 !important; color: #fff !important; }
        </style>
        <?php
    }
    ?>
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
    <?php
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
        .grsg-grid {
            display: grid; grid-template-columns: 3fr 2fr;
            gap: 14px; align-items: stretch;
            font-family: 'Inter', sans-serif; width: 100%;
        }
        .grsg-stor {
            display: flex; flex-direction: column; background: #fff;
            border-radius: 10px; overflow: hidden; box-shadow: 0 2px 14px rgba(0,0,0,0.08);
            cursor: pointer; height: 100%;
        }
        .grsg-stor__bilde { position: relative; width: 100%; aspect-ratio: 4/3; overflow: hidden; flex-shrink: 0; }
        .grsg-stor__bilde img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .grsg-stor__badge {
            position: absolute; top: 10px; right: 10px; background: #2a9e2a; color: #fff;
            font-size: 10px; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase;
            padding: 3px 9px; border-radius: 3px;
        }

        /* ── Glass content panel (same technique as .gnk-kort__innhold) ── */
        .grsg-stor__innhold {
            position: relative;
            z-index: 2;
            margin: -46px 12px 0;
            padding: 14px 16px 16px;
            border-radius: 10px;
            display: flex; flex-direction: column; flex: 1;
            background: var(--grsg-tint, rgba(26,24,21,0.62));
            -webkit-backdrop-filter: blur(14px);
            backdrop-filter: blur(14px);
            backdrop-filter: blur(14px) saturate(150%) url(#grsgGlassDistortion);
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.18);
            overflow: hidden;
        }
        .grsg-stor__tittel {
            font-family: 'Cormorant Garamond', serif; font-size: 24px; font-weight: 600;
            line-height: 1.2; color: #ffffff !important; margin: 0 0 5px; text-decoration: none; display: block;
        }
        .grsg-stor__sted { font-size: 11px; color: rgba(255,255,255,0.68); margin-bottom: 14px; }
        .grsg-stor__bunn {
            display: flex; align-items: center; justify-content: space-between;
            gap: 8px; margin-top: auto; padding-top: 12px; border-top: 1px solid rgba(255,255,255,0.22);
        }
        .grsg-stor__dato { display: flex; align-items: center; gap: 5px; }
        .grsg-stor__dag { font-family: 'Cormorant Garamond', serif; font-size: 38px; font-weight: 600; line-height: 1; color: #ffffff; }
        .grsg-stor__dato-info { display: flex; flex-direction: column; gap: 2px; }
        .grsg-stor__mnd { font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #ffffff; }
        .grsg-stor__aar { font-size: 10px; color: rgba(255,255,255,0.55); }
        .grsg-stor__tid { font-size: 10px; color: rgba(255,255,255,0.55); }
        .grsg-stor__knapper { display: flex; align-items: center; gap: 7px; flex-shrink: 0; }
        .grsg-stor__pris { font-size: 12px; font-weight: 500; color: rgba(255,255,255,0.8); }
        .grsg-side { display: flex; flex-direction: column; gap: 14px; height: 100%; }
        .grsg-mini {
            flex: 1; min-height: 0; display: flex; flex-direction: column;
            background: #fff; border-radius: 10px; overflow: hidden;
            box-shadow: 0 2px 14px rgba(0,0,0,0.08); cursor: pointer;
        }
        .grsg-mini__bilde { position: relative; width: 100%; flex: 1; min-height: 40px; overflow: hidden; }
        .grsg-mini__bilde img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .grsg-mini__badge {
            position: absolute; top: 6px; right: 6px; background: #2a9e2a; color: #fff;
            font-size: 8px; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase;
            padding: 2px 6px; border-radius: 3px;
        }

        .grsg-mini__innhold {
            position: relative;
            z-index: 2;
            margin: -26px 8px 0;
            padding: 6px 9px 8px;
            border-radius: 8px;
            flex-shrink: 0;
            background: var(--grsg-mini-tint, rgba(26,24,21,0.62));
            -webkit-backdrop-filter: blur(10px);
            backdrop-filter: blur(10px);
            backdrop-filter: blur(10px) saturate(150%) url(#grsgGlassDistortion);
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.18);
            overflow: hidden;
        }
        .grsg-mini__tittel {
            font-family: 'Cormorant Garamond', serif; font-size: 12px; font-weight: 600;
            line-height: 1.2; color: #ffffff !important; margin: 0 0 1px;
            display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
        }
        .grsg-mini__sted { font-size: 9px; color: rgba(255,255,255,0.68); margin-bottom: 5px; }
        .grsg-mini__bunn {
            display: flex; align-items: center; justify-content: space-between;
            padding-top: 5px; border-top: 1px solid rgba(255,255,255,0.22);
        }
        .grsg-mini__dato { display: flex; align-items: center; gap: 4px; }
        .grsg-mini__dag { font-family: 'Cormorant Garamond', serif; font-size: 18px; font-weight: 600; line-height: 1; color: #ffffff; }
        .grsg-mini__dato-info { display: flex; flex-direction: column; }
        .grsg-mini__mnd { font-size: 8px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; color: #ffffff; }
        .grsg-mini__sub { display: flex; align-items: center; gap: 3px; font-size: 8px; color: rgba(255,255,255,0.6); }
        .grsg-mini__knapper { display: flex; align-items: center; gap: 4px; flex-shrink: 0; }
        .grsg-mini__pris { font-size: 9px; font-weight: 500; color: rgba(255,255,255,0.8); }
        .grsg-btn, .grsg-mini-btn {
            font-family: 'Inter', sans-serif; font-weight: 500;
            border-radius: 5px; text-decoration: none !important; cursor: pointer; display: inline-block;
        }
        .grsg-btn { font-size: 12px; padding: 7px 14px; }
        .grsg-mini-btn { font-size: 9px; padding: 3px 7px; }
        .grsg-btn--sek, .grsg-mini-btn--sek { background: rgba(255,255,255,0.10); color: #ffffff !important; border: 1px solid rgba(255,255,255,0.35); }
        .grsg-btn--prim, .grsg-mini-btn--prim { background: #3a5c33; color: #fff !important; border: none; }
        @media (max-width: 600px) {
            .grsg-grid { grid-template-columns: 1fr; }
            .grsg-side { height: auto; }
            .grsg-mini { flex: none; }
            .grsg-mini__bilde { height: 120px; flex: none; }
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
                            <span class="grsg-stor__aar"><?php echo esc_html($k['aar']); ?></span>
                            <?php if ($k['tid']): ?><span class="grsg-stor__tid">kl <?php echo esc_html($k['tid']); ?></span><?php endif; ?>
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


// ── GRS GLASS PANEL COLOR SAMPLING ─────────────────────
// Prints once (any page that has at least one of the two shortcodes
// above). For every card, reads the pixel color of the photo right
// where the glass panel overlaps it (horizontal middle of the
// panel's top edge, 2px up into the image) via <canvas>, darkens it
// a bit, and sets it as --gnk-tint / --grsg-tint / --grsg-mini-tint
// so the glass panel's background matches the photo instead of a
// fixed color. Falls back to the CSS default (a plain dark tint) if
// the canvas read fails (e.g. the image isn't same-origin).
add_action('wp_footer', function () {
    ?>
    <svg width="0" height="0" style="position:absolute" aria-hidden="true">
        <filter id="gnkGlassDistortion" x="-20%" y="-20%" width="140%" height="140%">
            <feTurbulence type="fractalNoise" baseFrequency="0.012 0.012" numOctaves="2" seed="4" result="gnkNoise"/>
            <feGaussianBlur in="gnkNoise" stdDeviation="2" result="gnkSoftNoise"/>
            <feDisplacementMap in="SourceGraphic" in2="gnkSoftNoise" scale="14" xChannelSelector="R" yChannelSelector="G"/>
        </filter>
        <filter id="grsgGlassDistortion" x="-20%" y="-20%" width="140%" height="140%">
            <feTurbulence type="fractalNoise" baseFrequency="0.012 0.012" numOctaves="2" seed="9" result="grsgNoise"/>
            <feGaussianBlur in="grsgNoise" stdDeviation="2" result="grsgSoftNoise"/>
            <feDisplacementMap in="SourceGraphic" in2="grsgSoftNoise" scale="14" xChannelSelector="R" yChannelSelector="G"/>
        </filter>
    </svg>
    <script>
    (function () {
        "use strict";

        var DARKEN = 0.78; // "litt mørknet"
        var ALPHA  = 0.62;

        function darken(channel) {
            return Math.max(0, Math.round(channel * DARKEN));
        }

        function sampleTint(img, panel, cssVar) {
            try {
                var imgRect   = img.getBoundingClientRect();
                var panelRect = panel.getBoundingClientRect();
                if (!img.naturalWidth || !img.naturalHeight || !imgRect.width || !imgRect.height) return;

                // Point in viewport space: middle of the panel's top edge, 2px up.
                var pointX = panelRect.left + panelRect.width / 2;
                var pointY = panelRect.top - 2;

                // Map that viewport point into natural image pixel coordinates,
                // accounting for object-fit: cover's scale + crop.
                var scale     = Math.max(imgRect.width / img.naturalWidth, imgRect.height / img.naturalHeight);
                var renderedW = img.naturalWidth * scale;
                var renderedH = img.naturalHeight * scale;
                var cropX     = (renderedW - imgRect.width) / 2;
                var cropY     = (renderedH - imgRect.height) / 2;

                var relX = pointX - imgRect.left;
                var relY = pointY - imgRect.top;

                var nx = Math.round((relX + cropX) / scale);
                var ny = Math.round((relY + cropY) / scale);
                nx = Math.max(0, Math.min(img.naturalWidth - 1, nx));
                ny = Math.max(0, Math.min(img.naturalHeight - 1, ny));

                var canvas = document.createElement('canvas');
                canvas.width = img.naturalWidth;
                canvas.height = img.naturalHeight;
                var ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0);
                var d = ctx.getImageData(nx, ny, 1, 1).data;

                var rgba = 'rgba(' + darken(d[0]) + ',' + darken(d[1]) + ',' + darken(d[2]) + ',' + ALPHA + ')';
                panel.style.setProperty(cssVar, rgba);
            } catch (e) {
                // Cross-origin image without CORS headers taints the canvas —
                // leave the CSS fallback color in place.
            }
        }

        function wire(imgSelector, panelSelector, cssVar) {
            document.querySelectorAll(panelSelector).forEach(function (panel) {
                var card = panel.closest('.gnk-kort, .grsg-stor, .grsg-mini');
                if (!card) return;
                var img = card.querySelector(imgSelector);
                if (!img) return;

                function run() { sampleTint(img, panel, cssVar); }
                if (img.complete && img.naturalWidth) run();
                else img.addEventListener('load', run);
            });
        }

        wire('.gnk-kort__bilde img',   '.gnk-kort__innhold',    '--gnk-tint');
        wire('.grsg-stor__bilde img',  '.grsg-stor__innhold',   '--grsg-tint');
        wire('.grsg-mini__bilde img',  '.grsg-mini__innhold',   '--grsg-mini-tint');
    })();
    </script>
    <?php
});
