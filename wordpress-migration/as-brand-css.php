<?php
/**
 * Plugin Name: Autism Sanctuary Brand CSS
 * Description: Loads EmDash-aligned brand CSS and keeps page HTML intact.
 */

add_action('wp_enqueue_scripts', function () {
	$rel = 'wordpress-migration/custom.css';
	$path = ABSPATH . $rel;
	if (!file_exists($path)) {
		return;
	}
	wp_register_style('as-brand', home_url('/' . $rel), [], (string) filemtime($path));
	wp_enqueue_style('as-brand');
}, 999);

add_action('wp_head', function () {
	$path = ABSPATH . 'wordpress-migration/custom.css';
	if (!file_exists($path)) {
		return;
	}
	echo "<style id=\"as-brand-inline\">\n" . file_get_contents($path) . "\n</style>\n";
}, 100);

/**
 * Prevent wpautop from wrapping section/hero markup in <p> tags.
 */
add_action('init', function () {
	remove_filter('the_content', 'wpautop');
	remove_filter('the_excerpt', 'wpautop');
}, 9);

add_filter('the_content', function ($content) {
	if (is_singular('page') || is_front_page()) {
		return $content;
	}
	return wpautop($content);
}, 12);

/**
 * Hide classic theme page titles — banners/heroes already provide the H1.
 */
add_filter('et_post_meta_fields_to_remove', function ($fields) {
	$fields[] = 'title';
	return $fields;
});

add_action('wp', function () {
	if (is_page() || is_front_page()) {
		remove_action('et_before_post', 'et_add_post_meta_wrapper', 5);
	}
});

add_filter('body_class', function ($classes) {
	if (is_page() || is_front_page()) {
		$classes[] = 'as-hide-page-title';
	}
	$classes[] = 'as-custom-footer';
	return $classes;
});

/**
 * Blank the classic theme H1 inside the loop (menus/nav still keep titles).
 */
add_filter('the_title', function ($title, $post_id = 0) {
	if (is_admin() || !in_the_loop() || !is_main_query()) {
		return $title;
	}
	if (is_page() || is_front_page()) {
		return '';
	}
	return $title;
}, 10, 2);

/**
 * Soft redirects for retired Admissions and Fellowship pages.
 */
add_action('template_redirect', function () {
	if (is_admin()) {
		return;
	}
	$redirects = get_option('as_path_redirects', []);
	if (!is_array($redirects) || !$redirects) {
		$redirects = [
			'admissions' => home_url('/programs/#interest'),
			'fellowship' => home_url('/careers/'),
		];
	}
	$request = trim((string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/');
	$home_path = trim((string) parse_url(home_url('/'), PHP_URL_PATH), '/');
	if ($home_path && strpos($request, $home_path . '/') === 0) {
		$request = substr($request, strlen($home_path) + 1);
	} elseif ($home_path && $request === $home_path) {
		$request = '';
	}
	$slug = strtolower(explode('/', $request)[0] ?? '');
	if ($slug && isset($redirects[$slug])) {
		wp_safe_redirect($redirects[$slug], 301);
		exit;
	}
});

/**
 * Classic multi-column site footer (replaces Divi’s flat link strip).
 */
add_action('et_after_main_content', function () {
	if (is_admin()) {
		return;
	}
	if (function_exists('et_theme_builder_overrides_layout')
		&& defined('ET_THEME_BUILDER_FOOTER_LAYOUT_POST_TYPE')
		&& et_theme_builder_overrides_layout(ET_THEME_BUILDER_FOOTER_LAYOUT_POST_TYPE)
	) {
		return;
	}

	$year = gmdate('Y');
	$explore = [
		['About', '/about/'],
		['Programs', '/programs/'],
		['Our farm', '/our-farm/'],
		['Resources', '/resources/'],
		['News', '/news/'],
	];
	$connect = [
		['Careers', '/careers/'],
		['Donate', '/donate/'],
		['Contact', '/contact/'],
		['Privacy', '/privacy/'],
		['Terms', '/terms/'],
	];
	?>
	<footer class="as-site-footer" role="contentinfo">
		<div class="as-site-footer__inner">
			<div class="as-site-footer__brand">
				<p class="as-site-footer__name">Autism Sanctuary</p>
				<p class="as-site-footer__tagline">A working farm in the Blue Ridge foothills where people with developmental disabilities grow in nature, purpose, and belonging.</p>
				<p class="as-site-footer__meta">
					501(c)(3) nonprofit · Virginia DBHDS-licensed provider
				</p>
			</div>
			<div class="as-site-footer__col">
				<p class="as-site-footer__heading">Explore</p>
				<ul>
					<?php foreach ($explore as $item) : ?>
						<li><a href="<?php echo esc_url(home_url($item[1])); ?>"><?php echo esc_html($item[0]); ?></a></li>
					<?php endforeach; ?>
				</ul>
			</div>
			<div class="as-site-footer__col">
				<p class="as-site-footer__heading">Connect</p>
				<ul>
					<?php foreach ($connect as $item) : ?>
						<li><a href="<?php echo esc_url(home_url($item[1])); ?>"><?php echo esc_html($item[0]); ?></a></li>
					<?php endforeach; ?>
				</ul>
			</div>
			<div class="as-site-footer__col as-site-footer__col--contact">
				<p class="as-site-footer__heading">Visit &amp; reach us</p>
				<p>2860 Pea Ridge Road<br />Charlottesville, VA 22901</p>
				<p>
					<a href="tel:+14342072118">(434) 207-2118</a><br />
					<a href="mailto:info@autismsanctuary.org">info@autismsanctuary.org</a>
				</p>
				<p class="as-site-footer__social">
					<a href="https://www.instagram.com/autism.sanctuary/" rel="noopener noreferrer" target="_blank">Instagram</a>
					<span aria-hidden="true">·</span>
					<a href="https://www.facebook.com/autismsanctuary" rel="noopener noreferrer" target="_blank">Facebook</a>
					<span aria-hidden="true">·</span>
					<a href="https://www.linkedin.com/company/autismsanctuary" rel="noopener noreferrer" target="_blank">LinkedIn</a>
				</p>
			</div>
			<div class="as-site-footer__col as-site-footer__col--newsletter">
				<p class="as-site-footer__heading">Newsletter</p>
				<p class="as-site-footer__newsletter-copy">Farm news, program updates, and ways to support Autism Sanctuary.</p>
				<div class="as-site-footer__newsletter-form">
					<?php
					$hustle_id = (int) get_option('as_hustle_newsletter_module_id', 0);
					if ($hustle_id > 0 && shortcode_exists('wd_hustle')) {
						echo do_shortcode('[wd_hustle id="' . $hustle_id . '" type="embedded"/]');
					} else {
						echo '<p class="as-site-footer__newsletter-fallback"><a href="mailto:newsletters@autismsanctuary.org?subject=Subscribe%20to%20Trail%20Guide">Email us to subscribe</a></p>';
					}
					?>
				</div>
			</div>
		</div>
		<div class="as-site-footer__bottom">
			<div class="as-site-footer__bottom-inner">
				<p>&copy; <?php echo esc_html($year); ?> Autism Sanctuary. All rights reserved.</p>
			</div>
		</div>
	</footer>
	<?php
}, 5);
