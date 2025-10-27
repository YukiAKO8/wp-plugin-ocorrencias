<?php
/**
 * Controlador AJAX para carregar diferentes views do plugin.
 *
 * @package GS_Plugin
 * @since   1.0.0
 */

// Se este arquivo for chamado diretamente, aborte.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class GS_Ajax_Controller {
	/**
	 * Carrega a view solicitada via AJAX.
	 *
	 * @since 1.0.0
	 */
	public static function load_view() {
		check_ajax_referer( 'gs_ajax_nonce', 'nonce' );

		$view = isset( $_POST['view'] ) ? sanitize_text_field( wp_unslash( $_POST['view'] ) ) : '';

		global $wpdb;
		$table_name = $wpdb->prefix . 'gs_ocorrencias';

		switch ( $view ) {
			case 'form':
				$ocorrencia = null;
				$ocorrencia_id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
				if ( $ocorrencia_id ) {
					$ocorrencia = $wpdb->get_row( $wpdb->prepare(
						"SELECT o.* FROM {$table_name} o WHERE o.id = %d",
						$ocorrencia_id
					) );
					// Check permission before loading form for editing
					if ( $ocorrencia ) {
						$current_user_id = get_current_user_id();
						$can_edit        = ( (int) $current_user_id === (int) $ocorrencia->user_id ) || current_user_can( 'manage_options' );
						if ( ! $can_edit ) {
							echo '<p>Você não tem permissão para editar esta ocorrência.</p>';
							wp_die();
						}
					}
				}
				include GS_PLUGIN_PATH . 'app/assets/views/formulario-gestao.php';
				break;
			case 'list':
				$items_per_page = 8;
				$current_page   = isset( $_POST['paged'] ) ? absint( $_POST['paged'] ) : 1;
				$offset         = ( $current_page - 1 ) * $items_per_page;
				$search_term    = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';

				$current_user = wp_get_current_user();
				$user_role    = ! empty( $current_user->roles ) ? $current_user->roles[0] : null;

				$where_clause = '';
				$prepare_args = array();

				if ( ! current_user_can( 'manage_options' ) && $user_role ) {
					$where_clause   = ' WHERE o.user_role = %s';
					$prepare_args[] = $user_role;
				}

				if ( ! empty( $search_term ) ) {
					$search_like    = '%' . $wpdb->esc_like( $search_term ) . '%';
					if ( empty( $where_clause ) ) {
						$where_clause = ' WHERE';
					} else {
						$where_clause .= ' AND';
					}
					$where_clause .= ' (o.titulo LIKE %s OR o.descricao LIKE %s)';
					$prepare_args[] = $search_like;
					$prepare_args[] = $search_like;
				}

				$total_items_query = "SELECT COUNT(o.id) FROM {$table_name} o" . $where_clause;
				$total_items       = $wpdb->get_var( $wpdb->prepare( $total_items_query, $prepare_args ) );
				$total_pages       = ceil( $total_items / $items_per_page );

				$query_args = array_merge( $prepare_args, array( $items_per_page, $offset ) );

				$ocorrencias = $wpdb->get_results( $wpdb->prepare(
					"SELECT o.*, u.display_name FROM {$table_name} o LEFT JOIN {$wpdb->users} u ON o.user_id = u.ID" . $where_clause . ' ORDER BY o.data_registro DESC LIMIT %d OFFSET %d',
					$query_args
				) );
				include GS_PLUGIN_PATH . 'app/assets/views/lista-ocorrencias.php';
				break;
			case 'details':
				$ocorrencia_id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
				if ( $ocorrencia_id ) {
					$ocorrencia = $wpdb->get_row( $wpdb->prepare(
						"SELECT o.*, u.display_name, us.display_name as solucionado_por_name FROM {$table_name} o LEFT JOIN {$wpdb->users} u ON o.user_id = u.ID LEFT JOIN {$wpdb->users} us ON o.solucionado_por = us.ID WHERE o.id = %d",
						$ocorrencia_id
					) );
					if ( $ocorrencia ) {
						include GS_PLUGIN_PATH . 'app/assets/views/mostrar-ocorrencia.php';
					} else {
						echo '<p>Ocorrência não encontrada.</p>';
					}
				} else {
					echo '<p>ID da ocorrência não fornecido.</p>';
				}
				break;
			default:
				echo '<p>View não encontrada.</p>';
				break;
		}
		wp_die();
	}
}