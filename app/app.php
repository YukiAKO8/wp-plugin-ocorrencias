<?php
/**
 * Arquivo principal da lógica do plugin.
 *
 * @package GS_Plugin
 * @since   1.0.0
 */

// Se este arquivo for chamado diretamente, aborte.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Classe principal da aplicação do plugin.
 *
 * @since 1.0.0
 */
class GS_Plugin_App {

	/**
	 * Construtor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->setup_hooks();
	}

	/**
	 * Registra os hooks do WordPress.
	 *
	 * @since 1.0.0
	 */
	private function setup_hooks() {
		add_action( 'admin_menu', array( $this, 'register_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
		add_action( 'wp_ajax_gs_load_view', array( $this, 'ajax_load_view' ) );
		add_action( 'wp_ajax_gs_save_ocorrencia', array( $this, 'handle_form_submission' ) );
		add_action( 'wp_ajax_gs_increment_counter', array( $this, 'ajax_increment_counter' ) );
		add_action( 'wp_ajax_gs_save_solution', array( $this, 'ajax_save_solution' ) );
		add_action( 'wp_ajax_gs_delete_solution', array( $this, 'ajax_delete_solution' ) );
	}

	public function handle_form_submission() {
		// A verificação de segurança agora é feita via AJAX nonce.
		check_ajax_referer( 'gs_ajax_nonce', 'nonce' );
		if ( ! isset( $_POST['titulo'] ) || ! isset( $_POST['descricao'] ) ) {
			wp_send_json_error( array( 'message' => 'Dados do formulário ausentes.' ) );
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'gs_ocorrencias';

		$user_id       = get_current_user_id();
		$titulo        = sanitize_text_field( wp_unslash( $_POST['titulo'] ) );
		$descricao     = sanitize_textarea_field( wp_unslash( $_POST['descricao'] ) );
		$data_registro = current_time( 'mysql' );

		$result = $wpdb->insert(
			$table_name,
			array(
				'user_id'       => $user_id,
				'titulo'        => $titulo,
				'descricao'     => $descricao,
				'data_registro' => $data_registro,
			)
		);

		if ( $result ) {
			wp_send_json_success( array( 'message' => 'Ocorrência salva com sucesso!' ) );
		} else {
			wp_send_json_error( array( 'message' => 'Falha ao salvar a ocorrência.' ) );
		}
	}

	/**
	 * Callback AJAX para incrementar o contador de uma ocorrência.
	 *
	 * @since 1.0.0
	 */
	public function ajax_increment_counter() {
		check_ajax_referer( 'gs_ajax_nonce', 'nonce' );

		if ( ! isset( $_POST['id'] ) || ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Ação não permitida ou ID ausente.' ) );
		}

		global $wpdb;
		$id         = absint( $_POST['id'] );
		$table_name = $wpdb->prefix . 'gs_ocorrencias';

		// Incrementa o contador no banco de dados de forma atômica.
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table_name} SET contador = contador + 1 WHERE id = %d",
				$id
			)
		);

		// Pega o novo valor para retornar ao frontend.
		$new_count = $wpdb->get_var(
			$wpdb->prepare( "SELECT contador FROM {$table_name} WHERE id = %d", $id )
		);

		wp_send_json_success( array( 'new_count' => $new_count ) );
	}

	/**
	 * Callback AJAX para salvar a solução de uma ocorrência.
	 *
	 * @since 1.0.0
	 */
	public function ajax_save_solution() {
		check_ajax_referer( 'gs_ajax_nonce', 'nonce' );

		if ( ! isset( $_POST['id'], $_POST['solucao'] ) || ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Dados ausentes ou permissão insuficiente.' ) );
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'gs_ocorrencias';
		$id         = absint( $_POST['id'] );
		$solucao    = sanitize_textarea_field( wp_unslash( $_POST['solucao'] ) ); // Sanitize the solution text

		$current_user_id = get_current_user_id();
		$current_time    = current_time( 'mysql' );

		$data_to_update = array(
			'solucao' => $solucao,
		);
		$data_formats   = array( '%s' ); // Formato para 'solucao'

		// Determina o novo status baseado no conteúdo da solução
		if ( ! empty( $solucao ) ) {
			$data_to_update['status']            = 'solucionada';
			$data_to_update['solucionado_por']   = $current_user_id;
			$data_to_update['data_hora_solucao'] = $current_time;
			$data_formats[]                      = '%s'; // Formato para 'status'
			$data_formats[]                      = '%d'; // Formato para 'solucionado_por'
			$data_formats[]                      = '%s'; // Formato para 'data_hora_solucao'
		} else {
			// Se a solução for limpa, reverte o status e limpa os dados da solução
			$data_to_update['status']            = 'aberto';
			$data_to_update['solucionado_por']   = null;
			$data_to_update['data_hora_solucao'] = null;
			$data_formats[]                      = '%s'; // Formato para 'status'
			$data_formats[]                      = null; // Formato para 'solucionado_por'
			$data_formats[]                      = null; // Formato para 'data_hora_solucao'
		}

		$result = $wpdb->update(
			$table_name,
			$data_to_update,
			array( 'id' => $id ),
			$data_formats,
			array( '%d' )
		);

		if ( false === $result ) {
			wp_send_json_error( array( 'message' => 'Falha ao salvar a solução e/ou status no banco de dados.' ) );
		} elseif ( 0 === $result ) {
			// Nenhuma linha afetada, o que significa que os dados já estavam atualizados
			wp_send_json_success( array( 'message' => 'Nenhuma alteração necessária. Solução e status já estavam atualizados.' ) );
		} else {
			wp_send_json_success( array( 'message' => 'Solução e status atualizados com sucesso!' ) );
		}
	}

	/**
	 * Callback AJAX para excluir a solução de uma ocorrência.
	 *
	 * @since 1.0.0
	 */
	public function ajax_delete_solution() {
		check_ajax_referer( 'gs_ajax_nonce', 'nonce' );

		if ( ! isset( $_POST['id'] ) || ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Dados ausentes ou permissão insuficiente.' ) );
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'gs_ocorrencias';
		$id         = absint( $_POST['id'] );

		$result = $wpdb->update(
			$table_name,
			array(
				'solucao' => null,     // Define a solução como nula
				'status'  => 'aberto', // Reverte o status para 'aberto'
				'solucionado_por' => null, // Limpa quem solucionou
				'data_hora_solucao' => null, // Limpa a data/hora da solução
			),
			array( 'id' => $id ),
			array( '%s', '%s', '%d', '%s' ), // Formatos para solucao, status, solucionado_por, data_hora_solucao
			array( '%d' )
		);

		if ( false === $result ) {
			wp_send_json_error( array( 'message' => 'Falha ao excluir a solução.' ) );
		}

		wp_send_json_success( array( 'message' => 'Solução excluída com sucesso!' ) );
	}

	public function register_admin_menu() {
		add_menu_page(
			'Lista de Ocorrências', // Título da página
			'Ocorrências',          // Título do menu
			'manage_options',       // Permissão necessária
			'gs-ocorrencias',       // Slug do menu
			array( $this, 'render_admin_manager_page' ), // Função de callback para renderizar a página
			'dashicons-list-view',  // Ícone do menu
			25                      // Posição no menu
		);
	}

	/**
	 * Renderiza a página principal de gerenciamento no painel de administração.
	 *
	 * @since 1.0.0
	 */
	public function render_admin_manager_page() {
		echo '<div class="wrap">';
		echo '<div class="sna-gs-header-title-wrapper">'; // Mantém o wrapper para o logo e o título
		echo '<img src="' . esc_url( GS_PLUGIN_URL . 'app/assets/views/logo.png' ) . '" alt="Logo" class="sna-gs-header-logo">';
		echo '<h1 class="wp-heading-inline">Gerenciar Ocorrências</h1>';
		echo '</div>';
		echo '<p class="sna-gs-page-description">Esta é uma ferramenta desenvolvida para registrar, e acompanhar problemas. Seu principal objetivo é centralizar as informações e permitir que cada ocorrência seja monitorada desde o momento em que é registrada até sua resolução.</p>';


		// Exibe mensagem de sucesso se houver.
		if ( isset( $_GET['ocorrencia_salva'] ) && '1' === $_GET['ocorrencia_salva'] ) {
			echo '<div class="notice notice-success is-dismissible"><p>Ocorrência salva com sucesso!</p></div>';
		}

		// Botão Flutuante - movido para fora do container para ser persistente.
		echo '<a href="#" id="sna-gs-load-form-btn" class="sna-gs-fab">
				<span class="sna-gs-fab-icon">+</span>
				<span class="sna-gs-fab-text">Nova Ocorrência</span>
			  </a>';

		// Container para o conteúdo AJAX
		echo '<div id="sna-gs-view-container">';
			// Carrega a view inicial (lista)
			global $wpdb;
			$table_name     = $wpdb->prefix . 'gs_ocorrencias';
			$items_per_page = 8;
			$current_page   = 1;
			$offset         = ( $current_page - 1 ) * $items_per_page;

			// Get total items for pagination
			$total_items = $wpdb->get_var( "SELECT COUNT(id) FROM $table_name" );
			$total_pages = ceil( $total_items / $items_per_page );

			$ocorrencias = $wpdb->get_results( $wpdb->prepare(
				"SELECT o.*, u.display_name FROM %i o LEFT JOIN %i u ON o.user_id = u.ID ORDER BY o.data_registro DESC LIMIT %d OFFSET %d",
				$table_name,
				$wpdb->users,
				$items_per_page,
				$offset
			) );
			require GS_PLUGIN_PATH . 'app/assets/views/lista-ocorrencias.php';
		echo '</div>'; // Fim do #sna-gs-view-container

		echo '</div>'; // Fim do .wrap
	}

	/**
	 * Enfileira os scripts e estilos do admin.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_admin_scripts( $hook ) {
		// Garante que o script só seja carregado na nossa página do plugin.
		if ( 'toplevel_page_gs-ocorrencias' !== $hook ) {
			return;
		}
		// Usa a data de modificação do arquivo para versionamento, forçando o navegador a recarregar se houver mudanças.
		$style_version = filemtime( GS_PLUGIN_PATH . 'app/assets/style.css' );
		$script_version = filemtime( GS_PLUGIN_PATH . 'app/ajax/admin-main.js' );
		wp_enqueue_style( 'gs-admin-styles', GS_PLUGIN_URL . 'app/assets/style.css', array(), $style_version );
		wp_enqueue_script( 'gs-admin-main', GS_PLUGIN_URL . 'app/ajax/admin-main.js', array( 'jquery' ), $script_version, true );
		// Passa dados do PHP para o JavaScript (como o nonce de segurança).
		wp_localize_script( 'gs-admin-main', 'gs_ajax_object', array( 'nonce' => wp_create_nonce( 'gs_ajax_nonce' ) ) );
	}

	/**
	 * Callback para a chamada AJAX.
	 */
	public function ajax_load_view() {
		require_once GS_PLUGIN_PATH . 'app/ajax/controller_form.php';
		GS_Ajax_Controller::load_view();
	}
}

new GS_Plugin_App();