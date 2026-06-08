<?php

if (! defined('ABSPATH')) {
	exit;
}

class WC2026_Updater {

	private string $plugin_file;
	private string $plugin_slug;
	private string $metadata_url;
	private string $version;

	public function __construct(string $plugin_file, string $metadata_url, string $version) {
		$this->plugin_file  = $plugin_file;
		$this->plugin_slug  = plugin_basename($plugin_file);
		$this->metadata_url = $metadata_url;
		$this->version      = $version;
	}

	public function init(): void {
		add_filter('pre_set_site_transient_update_plugins', array($this, 'check_for_update'));
		add_filter('plugins_api', array($this, 'plugin_info'), 20, 3);
	}

	public function check_for_update(object $transient): object {
		if (empty($transient->checked)) {
			return $transient;
		}

		$remote = $this->fetch_metadata();
		if (! $remote || ! isset($remote->version)) {
			return $transient;
		}

		if (version_compare($this->version, $remote->version, '<')) {
			$transient->response[$this->plugin_slug] = (object) array(
				'slug'        => dirname($this->plugin_slug),
				'plugin'      => $this->plugin_slug,
				'new_version' => $remote->version,
				'url'         => $remote->details_url ?? '',
				'package'     => $remote->download_url ?? '',
			);
		}

		return $transient;
	}

	public function plugin_info(mixed $result, string $action, object $args): mixed {
		if ($action !== 'plugin_information') {
			return $result;
		}
		if (! isset($args->slug) || $args->slug !== dirname($this->plugin_slug)) {
			return $result;
		}

		$remote = $this->fetch_metadata();
		if (! $remote) {
			return $result;
		}

		return (object) array(
			'name'          => $remote->name ?? '',
			'slug'          => dirname($this->plugin_slug),
			'version'       => $remote->version ?? '',
			'author'        => $remote->author ?? '',
			'homepage'      => $remote->details_url ?? '',
			'download_link' => $remote->download_url ?? '',
			'sections'      => array(
				'description' => $remote->description ?? '',
				'changelog'   => $remote->changelog ?? '',
			),
		);
	}

	private function fetch_metadata(): object|false {
		$cache_key = 'wc2026_update_check';
		$cached    = get_transient($cache_key);
		if ($cached !== false) {
			return $cached;
		}

		$response = wp_remote_get($this->metadata_url, array('timeout' => 10));
		if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
			return false;
		}

		$data = json_decode(wp_remote_retrieve_body($response));
		if (! $data) {
			return false;
		}

		// Cache for 12 hours.
		set_transient($cache_key, $data, 12 * HOUR_IN_SECONDS);

		return $data;
	}
}
