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
		$table_ocorrencias = $wpdb->prefix . 'gs_ocorrencias';
		$table_imagens     = $wpdb->prefix . 'gs_imagens_ocorrencias';

		switch ( $view ) {
			case 'form':
				$ocorrencia = null;
				$ocorrencia_id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
				if ( $ocorrencia_id ) {
					// Padroniza a consulta para ser igual à do 'case details', garantindo consistência.
					$ocorrencia = $wpdb->get_row( $wpdb->prepare(
						"SELECT o.*, u.display_name, us.display_name as solucionado_por_name 
						 FROM {$table_ocorrencias} o 
						 LEFT JOIN {$wpdb->users} u ON o.user_id = u.ID 
						 LEFT JOIN {$wpdb->users} us ON o.solucionado_por = us.ID WHERE o.id = %d",
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

						// Agora, busca as imagens, pois o usuário tem permissão.
						$imagens_db = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table_imagens} WHERE ocorrencia_id = %d", $ocorrencia_id ) );
						$ocorrencia->imagens = array();
						if ( ! empty( $imagens_db ) && function_exists( 'getFilesWPDrive' ) ) {
							foreach ( $imagens_db as $img_db ) {
								if ( ! empty( $img_db->imagem_id_drive ) ) {
									$display_url = getFilesWPDrive( $img_db->imagem_id_drive );
									if ( ! is_wp_error( $display_url ) && ! empty( $display_url ) ) {
										$img_db->display_url = $display_url;
									}
									$ocorrencia->imagens[] = $img_db;
								}
							}
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
				$processos_filter = isset( $_POST['processos'] ) ? absint( $_POST['processos'] ) : 0; // Padrão para ocorrências (0)

				$current_user = wp_get_current_user();
				$user_role    = ! empty( $current_user->roles ) ? $current_user->roles[0] : null;

				$where_clause = '';
				$where_conditions = array();
				$prepare_args     = array();

				if ( ! current_user_can( 'manage_options' ) && $user_role ) {
					$where_conditions[] = 'o.user_role = %s';
					$prepare_args[] = $user_role;
				}

				// Adiciona o filtro de processos (ocorrência ou processo)
				$where_conditions[] = 'o.processos = %d';
				$prepare_args[] = $processos_filter;

				if ( ! empty( $search_term ) ) {
					$search_like      = '%' . $wpdb->esc_like( $search_term ) . '%';
					$where_conditions[] = '(o.titulo LIKE %s OR o.descricao LIKE %s)';
					$prepare_args[] = $search_like;
					$prepare_args[] = $search_like;
				}

				if ( ! empty( $where_conditions ) ) {
					$where_clause = ' WHERE ' . implode( ' AND ', $where_conditions );
				}

				$total_items_query = "SELECT COUNT(o.id) FROM {$table_ocorrencias} o" . $where_clause;
				$total_items       = $wpdb->get_var( $wpdb->prepare( $total_items_query, $prepare_args ) );
				$total_pages       = ceil( $total_items / $items_per_page );

				$query_args = array_merge( $prepare_args, array( $items_per_page, $offset ) );

				$ocorrencias = $wpdb->get_results( $wpdb->prepare(
					"SELECT o.*, u.display_name FROM {$table_ocorrencias} o LEFT JOIN {$wpdb->users} u ON o.user_id = u.ID" . $where_clause . ' ORDER BY o.data_registro DESC LIMIT %d OFFSET %d',
					$query_args
				) );
				include GS_PLUGIN_PATH . 'app/assets/views/lista-ocorrencias.php';
				break;
			case 'details':
				$ocorrencia_id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
				if ( $ocorrencia_id ) {
					$ocorrencia = $wpdb->get_row( $wpdb->prepare(
						"SELECT o.*, u.display_name, us.display_name as solucionado_por_name 
						 FROM {$table_ocorrencias} o 
						 LEFT JOIN {$wpdb->users} u ON o.user_id = u.ID 
						 LEFT JOIN {$wpdb->users} us ON o.solucionado_por = us.ID WHERE o.id = %d",
						$ocorrencia_id
					) );
					if ( $ocorrencia ) {
						// Busca todas as imagens associadas a esta ocorrência.
						$imagens_db = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table_imagens} WHERE ocorrencia_id = %d", $ocorrencia_id ) );
						$ocorrencia->imagens = array();

						// Otimização: Busca todas as URLs de imagem em uma única requisição em lote.
						if ( ! empty( $imagens_db ) && function_exists( 'getFilesWPDrive' ) ) {
							foreach ( $imagens_db as $img_db ) {
								if ( ! empty( $img_db->imagem_id_drive ) ) {
									$display_url = getFilesWPDrive( $img_db->imagem_id_drive );
									if ( ! is_wp_error( $display_url ) && ! empty( $display_url ) ) {
										$img_db->display_url = $display_url;
									} else {
										$img_db->display_url = ''; // Garante que a propriedade exista, mesmo que vazia.
									}
									$ocorrencia->imagens[] = $img_db;
								}
							}
						}
						include GS_PLUGIN_PATH . 'app/assets/views/mostrar-ocorrencia.php';
					} else {
						echo '<p>Ocorrência não encontrada.</p>';
					}
				} else {
					echo '<p>ID da ocorrência não fornecido.</p>';
				}
				break;
			case 'count_images':
				$ocorrencia_id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
				if ( $ocorrencia_id ) {
					$count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM {$table_imagens} WHERE ocorrencia_id = %d", $ocorrencia_id ) );
					echo esc_html( $count );
				} else {
					echo '0';
				}
				break;
			case 'get_images':
				$ocorrencia_id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
				if ( ! $ocorrencia_id ) {
					echo '<p>Erro: ID da ocorrência ausente.</p>';
					wp_die();
				}

				$imagens_db = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table_imagens} WHERE ocorrencia_id = %d", $ocorrencia_id ) );

				if ( empty( $imagens_db ) ) {
					echo '<p>Nenhuma imagem encontrada para esta ocorrência.</p>';
					wp_die();
				}

				if ( function_exists( 'getFilesWPDrive' ) ) {
					echo '<div class="sna-gs-image-gallery">';
					foreach ( $imagens_db as $img_db ) {
						if ( ! empty( $img_db->imagem_id_drive ) ) {
							$display_url = getFilesWPDrive( $img_db->imagem_id_drive );
							echo '<div class="sna-gs-gallery-item">';
							echo '<img src="' . esc_url( $display_url ) . '" alt="Imagem da ocorrência" class="sna-gs-gallery-thumbnail">';
							echo '</div>';
						}
					}
					echo '</div>';
					wp_die(); // Encerra a execução aqui, após enviar o HTML da galeria.
				}
				break; // Adicionado para evitar "fall-through" para o default.
			default:
				echo '<p>View não encontrada.</p>';
				break;
		}
		wp_die();
	}
}