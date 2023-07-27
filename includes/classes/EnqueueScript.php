<?php
/**
 * This class use to register script.
 *
 * This class handles:
 *   - Script dependencies.
 *     This uses asset information to set script dependencies,
 *     and version generated by @wordpress/dependency-extraction-webpack-plugin package.
 *   - Script localization.
 *     It also handles script translation registration.
 *
 * @package distributor
 * @since   2.0.0
 */

namespace Distributor;

use Exception;

/**
 * Class EnqueueScript
 *
 * @since 2.0.0
 */
class EnqueueScript {
	/**
	 * Script Handle.
	 *
	 * @since 2.0.0
	 * @var string
	 */
	private string $script_handle;

	/**
	 * Script path relative to plugin root directory.
	 *
	 * @since 2.0.0
	 * @var string
	 */
	private string $relative_script_path;

	/**
	 * Script path absolute to plugin root directory.
	 *
	 * @since 2.0.0
	 * @var string
	 */
	private string $absolute_script_path;

	/**
	 * Script dependencies.
	 *
	 * @since 2.0.0
	 * @var array
	 */
	private array $script_dependencies = [];

	/**
	 * Script version.
	 *
	 * @since 2.0.0
	 * @var string
	 */
	private string $version = DT_VERSION;

	/**
	 * Flag to decide whether load script in footer.
	 *
	 * @since 2.0.0
	 * @var bool
	 */
	private bool $load_script_in_footer = false;

	/**
	 * Flag to decide whether register script translation.
	 *
	 * @since 2.0.0
	 * @var bool
	 */
	private bool $register_translations = false;

	/**
	 * Script localization parameter name.
	 *
	 * @since 2.0.0
	 * @var string|null
	 */
	private ?string $localize_script_param_name = null;

	/**
	 * Script localization parameter data.
	 *
	 * @since 2.0.0
	 * @var array|null
	 */
	private ?array $localize_script_param_data = null;

	/**
	 * Plugin root directory path.
	 *
	 * @since 2.0.0
	 * @var string
	 */
	private string $plugin_dir_path;

	/**
	 * Plugin root directory URL.
	 *
	 * @since 2.0.0
	 * @var string
	 */
	private string $plugin_dir_url;

	/**
	 * Plugin text domain.
	 *
	 * @since 2.0.0
	 * @var string
	 */
	private string $text_domain;

	/**
	 * EnqueueScript constructor.
	 *
	 * @since 2.0.0
	 *
	 * @param string $script_handle    Script handle.
	 * @param string $script_file_name Script file name.
	 *
	 * @throws Exception If script file not found.
	 */
	public function __construct( string $script_handle, string $script_file_name ) {
		$this->plugin_dir_path      = DT_PLUGIN_PATH;
		$this->plugin_dir_url       = trailingslashit( plugin_dir_url( DT_PLUGIN_FULL_FILE ) );
		$this->text_domain          = 'distributor';
		$this->script_handle        = $script_handle;
		$this->relative_script_path = 'dist/js/' . $script_file_name . '.js';
		$this->absolute_script_path = $this->plugin_dir_path . $this->relative_script_path;
	}

	/**
	 * Flag to decide whether load script in footer.
	 *
	 * @since 2.0.0
	 */
	public function load_in_footer(): EnqueueScript {
		$this->load_script_in_footer = true;

		return $this;
	}

	/**
	 * Set script dependencies.
	 *
	 * @since 2.0.0
	 *
	 * @param array $script_dependencies Script dependencies.
	 */
	public function dependencies( array $script_dependencies ): EnqueueScript {
		$this->script_dependencies = $script_dependencies;

		return $this;
	}

	/**
	 * Register script.
	 *
	 * @since 2.0.0
	 */
	public function register(): EnqueueScript {
		$script_url   = $this->plugin_dir_url . $this->relative_script_path;
		$script_asset = $this->get_asset_file_data();

		$this->version = $script_asset['version'];

		wp_register_script(
			$this->script_handle,
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			$this->load_script_in_footer
		);

		if ( $this->register_translations ) {
			wp_set_script_translations(
				$this->script_handle,
				$this->text_domain,
				$this->plugin_dir_path . 'lang'
			);
		}

		if ( $this->localize_script_param_data ) {
			wp_localize_script(
				$this->script_handle,
				$this->localize_script_param_name,
				$this->localize_script_param_data
			);
		}

		return $this;
	}

	/**
	 * This function should be called before enqueue or register method.
	 *
	 * @since 2.0.0
	 */
	public function register_translations(): EnqueueScript {
		$this->register_translations = true;

		return $this;
	}

	/**
	 * This function should be called after enqueue or register method.
	 *
	 * @since 2.0.0
	 *
	 * @param string $js_variable_name JS variable name.
	 * @param array  $data             Data to be localized.
	 */
	public function register_localize_data( string $js_variable_name, array $data ): EnqueueScript {
		$this->localize_script_param_name = $js_variable_name;
		$this->localize_script_param_data = $data;

		return $this;
	}

	/**
	 * Enqueue script.
	 *
	 * @since 2.0.0
	 */
	public function enqueue(): EnqueueScript {
		if ( ! wp_script_is( $this->script_handle, 'registered' ) ) {
			$this->register();
		}
		wp_enqueue_script( $this->script_handle );

		return $this;
	}

	/**
	 * Should return script handle.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	public function get_script_handle(): string {
		return $this->script_handle;
	}

	/**
	 * Get asset file data.
	 *
	 * @since 2.0.0
	 *
	 * @return array
	 */
	public function get_asset_file_data(): array {
		$script_asset_path = trailingslashit( dirname( $this->absolute_script_path ) )
			. basename( $this->absolute_script_path, '.js' )
			. '.asset.php';

		if ( file_exists( $script_asset_path ) ) {
			$script_asset = require $script_asset_path;
		} else {
			$script_asset = [
				'dependencies' => [],
				'version'      => $this->version,
			];
		}

		if ( $this->script_dependencies ) {
			$script_asset['dependencies'] = array_merge( $this->script_dependencies, $script_asset['dependencies'] );
		}

		return $script_asset;
	}

	/**
	 * Should return script version.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	public function get_version(): string {
		$script_asset = $this->get_asset_file_data();

		return $script_asset['version'];
	}
}
