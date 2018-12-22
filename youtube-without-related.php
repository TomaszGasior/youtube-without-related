<?php
/**
 * Plugin Name: YouTube without related
 * Description: Don't show related videos in embedded YouTube player. This plugin shows black overlay above player when video is stopped or paused.
 * Author: Tomasz Gąsior
 * Author URI: https://tomaszgasior.pl
 * Version: 2018-12-22
 * License: MIT
 */

final class _YTPWREL
{
	private $used = false;

	static public function init()
	{
		$ytpwrel = new self;

		add_filter('embed_oembed_html', [$ytpwrel, 'hook__embed_oembed_html'], 10, 2);
		add_action('wp_footer', [$ytpwrel, 'hook__wp_footer']);

		// Dirty hack.
		add_filter('script_loader_src', [$ytpwrel, 'hook__script_loader_src']);
	}

	private function __construct() {}

	public function hook__embed_oembed_html($html, $url)
	{
		// Only work with HTML markup of YouTube embed.
		$domains = ['youtube.com', 'www.youtube.com', 'youtu.be', 'www.youtu.be'];
		if (false === in_array(parse_url($url, PHP_URL_HOST), $domains)) {
			return $html;
		}

		$this->used = true;

		// Operate on <iframe> using DOM document.
		$document = DOMDocument::loadHTML(
			'<meta charset="utf-8">' . $html,
			LIBXML_NOERROR | LIBXML_NOWARNING
		);
		$iframe = $document->getElementsByTagName('iframe')[0];

		// Get query string and query parameters from <iframe src="...">.
		$src_url = $iframe->getAttribute('src');
		$old_query_string = parse_url($src_url, PHP_URL_QUERY);
		parse_str($old_query_string, $query_params);

		// Add needed query parameters.
		// Details here: https://developers.google.com/youtube/player_parameters
		$query_params['enablejsapi'] = '1';
		$query_params['iv_load_policy'] = '3';
		$query_params['rel'] = '0';
		$query_params['origin'] = site_url();

		// Update URL in <iframe src="...">.
		$new_query_string = http_build_query($query_params);
		$src_url = str_replace($old_query_string, $new_query_string, $src_url);
		$iframe->setAttribute('src', $src_url);

		// Return new <iframe> markup wrapped in <span> with custom CSS class.
		// It's needed for inline JS script.
		$html = $document->saveHTML($iframe);
		return '<span class="ytpwrel-wrapper">' . $html . '</span>';
	}

	public function hook__wp_footer()
	{
		if (false === $this->used) {
			return;
		}

		wp_enqueue_style('ytpwrel', plugin_dir_url(__FILE__) . 'ytpwrel.css');
		wp_enqueue_script('ytpwrel', plugin_dir_url(__FILE__) . 'ytpwrel.js');

		// Details here: https://developers.google.com/youtube/iframe_api_reference
		wp_enqueue_script('ytpwrel-yt-api', 'https://www.youtube.com/iframe_api');
	}

	public function hook__script_loader_src($src)
	{
		// For some reason there is collision between FitVids.js (http://fitvidsjs.com/) and YTPWREL
		// script. YouTube player shows error instead of video when both scripts are loaded on
		// the webpage. Following dirty code tries to disable FitVids if it's used by current theme.
		if (false === stripos($src, 'fitvid')) {
			return $src;
		}

		remove_filter('script_loader_src', [$ytpwrel, 'hook__script_loader_src']);
		add_filter('wp_head', function(){
			?>
				<script>
					if (window.jQuery || window.Zepto) {
						(window.jQuery || window.Zepto).fn.fitVids = function(){
							console.log('FitVids.js disabled by YTPWREL');
						};
					}
				</script>
			<?php
		}, 9999);

		return $src;
	}
}

_YTPWREL::init();