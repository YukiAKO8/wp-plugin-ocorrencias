<?php
/**
 * Plugin Name:       Gestão de Ocorrencias
 * Description:       Centralizar as informações e permitir que cada ocorrência seja monitorada desde o momento em que é registrada até sua resolução
 * Version:           1.0.0
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Yuki AK Oddis
 * Author URI:        https://example.com/
 * Text Domain:       gs-plugin
 * Domain Path:       /languages
 */

// Se este arquivo for chamado diretamente, aborte.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * A classe principal do plugin.
 *
 * A classe principal do plugin que é usada para definir constantes,
 * carregar todos os hooks e inicializar a funcionalidade.
 *
 * @since 1.0.0
 */
final class GS_Plugin {

	/**
	 * A única instância da classe.
	 *
	 * @since 1.0.0
	 * @var   GS_Plugin
	 */
	private static $_instance = null;

	/**
	 * Versão do Plugin.
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	public $version = '1.0.0';

	/**
	 * Instância principal do GS_Plugin.
	 *
	 * Garante que apenas uma instância do GS_Plugin seja carregada.
	 *
	 * @since  1.0.0
	 * @static
	 * @return GS_Plugin - Instância principal.
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Construtor do GS_Plugin.
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
		$this->define_constants();
		$this->setup_hooks();
		$this->includes();
	}

	/**
	 * Define as constantes do plugin.
	 *
	 * @since 1.0.0
	 */
	private function define_constants() {
		define( 'GS_PLUGIN_VERSION', $this->version );
		define( 'GS_PLUGIN_FILE', __FILE__ );
		define( 'GS_PLUGIN_PATH', plugin_dir_path( GS_PLUGIN_FILE ) );
		define( 'GS_PLUGIN_URL', plugin_dir_url( GS_PLUGIN_FILE ) );
	}

	/**
	 * Inclui os arquivos necessários do plugin.
	 *
	 * @since 1.0.0
	 */
	private function includes() {
		// Carrega o arquivo principal da aplicação.
		require_once GS_PLUGIN_PATH . 'utils/db.php';
		require_once GS_PLUGIN_PATH . 'app/app.php';
	}

	/**
	 * Registra todos os hooks do WordPress.
	 *
	 * @since 1.0.0
	 */
	private function setup_hooks() {
		register_activation_hook( GS_PLUGIN_FILE, array( $this, 'activate' ) );
		register_deactivation_hook( GS_PLUGIN_FILE, array( $this, 'deactivate' ) );
	}

	/**
	 * Código a ser executado na ativação do plugin.
	 * @since 1.0.0
	 */
	public function activate() {
		GS_Plugin_DB::create_table();
	}

	/**
	 * Código a ser executado na desativação do plugin.
	 * @since 1.0.0
	 */
	public function deactivate() {}
}

// Inicia o plugin.
GS_Plugin::instance();