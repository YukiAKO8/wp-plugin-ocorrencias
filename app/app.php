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
		add_action( 'wp_ajax_gs_update_ocorrencia', array( $this, 'handle_update_submission' ) ); // New action for updating
		add_action( 'wp_ajax_gs_increment_counter', array( $this, 'ajax_increment_counter' ) );
		add_action( 'wp_ajax_gs_save_solution', array( $this, 'ajax_save_solution' ) );
		add_action( 'wp_ajax_gs_delete_solution', array( $this, 'ajax_delete_solution' ) );
		add_action( 'wp_ajax_gs_delete_ocorrencia', array( $this, 'ajax_delete_ocorrencia' ) );
		// Adicionado o hook para a nova função AJAX de exclusão de imagens
		add_action( 'wp_ajax_gs_delete_images_ajax', array( $this, 'ajax_delete_images' ) );
	}

	public function handle_form_submission() {
		// A verificação de segurança agora é feita via AJAX nonce.
		check_ajax_referer( 'gs_ajax_nonce', 'nonce' );
		if ( ! isset( $_POST['titulo'] ) || ! isset( $_POST['descricao'] ) ) {
			wp_send_json_error( array( 'message' => 'Dados do formulário ausentes.' ) );
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'gs_ocorrencias';

		$current_user  = wp_get_current_user();
		$user_id       = $current_user->ID;
		$user_roles    = $current_user->roles;
		$user_role     = ! empty( $user_roles ) ? $user_roles[0] : null; // Pega a primeira função do usuário

		$titulo        = sanitize_text_field( wp_unslash( $_POST['titulo'] ) );
		$descricao     = sanitize_textarea_field( wp_unslash( $_POST['descricao'] ) );
		$data_registro = current_time( 'mysql' );
		$is_processo   = isset( $_POST['processos'] ) ? absint( $_POST['processos'] ) : 0;

		$result = $wpdb->insert(
			$table_name,
			array(
				'user_id'       => $user_id,
				'user_role'     => $user_role,
				'titulo'        => $titulo,
				'descricao'     => $descricao,
				'data_registro' => $data_registro,
				'processos'     => $is_processo,
			)
		);

		if ( ! $result ) {
			wp_send_json_error( array( 'message' => 'Falha ao salvar a ocorrência.' ) );
		}

		$ocorrencia_id = $wpdb->insert_id;

		// Lida com o upload de múltiplas imagens e salva na tabela de imagens
		if ( isset( $_FILES['imagem_ocorrencia'] ) && is_array( $_FILES['imagem_ocorrencia']['name'] ) ) {
			$files = $_FILES['imagem_ocorrencia'];
			$table_imagens = $wpdb->prefix . 'gs_imagens_ocorrencias';			

			// Determina quais campos de título/descrição usar com base no tipo (processo ou ocorrência)
			if ( $is_processo ) {
				$title_field_name = 'imagem_titulo_processo';
				$desc_field_name  = 'imagem_descricao_processo';
			} else {
				$title_field_name = 'imagem_titulo_ocorrencia';
				$desc_field_name  = 'imagem_descricao_ocorrencia';
			}
			$image_titles       = isset( $_POST[ $title_field_name ] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST[ $title_field_name ] ) ) : array();
			$image_descriptions = isset( $_POST[ $desc_field_name ] ) ? array_map( 'sanitize_textarea_field', wp_unslash( $_POST[ $desc_field_name ] ) ) : array();

			for ( $i = 0; $i < count( $files['name'] ); $i++ ) {
				if ( $files['error'][ $i ] === UPLOAD_ERR_OK ) {
					$file_data = array(
						'name'     => $files['name'][ $i ],
						'type'     => $files['type'][ $i ],
						'tmp_name' => $files['tmp_name'][ $i ],
						'error'    => $files['error'][ $i ],
						'size'     => $files['size'][ $i ],
					);
					if ( function_exists( 'uploadFilesWPDrive' ) ) {
						$drive_file_response = uploadFilesWPDrive( $file_data, $file_data['name'], 'gestao_ocorrencias' );
						// VERIFICAÇÃO ROBUSTA: Garante que o upload foi bem-sucedido e que um ID foi retornado.
						if ( isset( $drive_file_response['error'] ) && 0 === $drive_file_response['error'] && ! empty( $drive_file_response['resposta']['id'] ) ) {
							$wpdb->insert(
								$table_imagens,
								array(
									'ocorrencia_id'   => $ocorrencia_id,
									'imagem_url'      => $drive_file_response['resposta']['webViewLink'] ?? '',
									'titulo'          => $image_titles[ $i ] ?? '', // Salva o título
									'descricao'       => $image_descriptions[ $i ] ?? '', // Salva a descrição
									'imagem_id_drive' => $drive_file_response['resposta']['id'],
								)
							);
						} else {
							error_log( 'GS Plugin - Falha no upload para o Drive: ' . print_r( $drive_file_response, true ) );
						}
					}
				}
			}
		}

		$type_label = ( 1 === $is_processo ) ? 'Processo' : 'Ocorrência';
		wp_send_json_success( array( 'message' => "{$type_label} salvo com sucesso!", 'action_taken' => true ) );
	}

	/**
	 * Lida com a submissão do formulário de edição de ocorrência.
	 *
	 * @since 1.0.0
	 */
	public function handle_update_submission() {
		check_ajax_referer( 'gs_ajax_nonce', 'nonce' );

		if ( ! isset( $_POST['ocorrencia_id'], $_POST['titulo'], $_POST['descricao'] ) ) {
			wp_send_json_error( array( 'message' => 'Dados do formulário ausentes.' ) );
		}

		global $wpdb;
		$table_ocorrencias = $wpdb->prefix . 'gs_ocorrencias';
		$table_imagens     = $wpdb->prefix . 'gs_imagens_ocorrencias';
		$ocorrencia_id     = absint( $_POST['ocorrencia_id'] );

		// Busca a ocorrência existente para verificar permissões.
		$existing_ocorrencia = $wpdb->get_row( $wpdb->prepare( "SELECT id, user_id FROM {$table_ocorrencias} WHERE id = %d", $ocorrencia_id ) );

		if ( ! $existing_ocorrencia ) {
			wp_send_json_error( array( 'message' => 'Ocorrência não encontrada.' ) );
		}

		$current_user_id = get_current_user_id();
		$can_edit        = ( (int) $current_user_id === (int) $existing_ocorrencia->user_id ) || current_user_can( 'manage_options' );

		if ( ! $can_edit ) {
			wp_send_json_error( array( 'message' => 'Você não tem permissão para editar esta ocorrência.' ) );
		}

		$titulo    = sanitize_text_field( wp_unslash( $_POST['titulo'] ) );
		$descricao = sanitize_textarea_field( wp_unslash( $_POST['descricao'] ) );
		$is_processo = isset( $_POST['processos'] ) ? absint( $_POST['processos'] ) : 0;

		// A lógica de remoção de imagens foi movida para uma chamada AJAX separada (ajax_delete_images)
		$images_removed_count = 0;

		// Lida com o upload de novas imagens
		$new_images_added = false;
		if ( isset( $_FILES['imagem_ocorrencia'] ) && is_array( $_FILES['imagem_ocorrencia']['name'] ) ) {
			$files         = $_FILES['imagem_ocorrencia'];
			$table_imagens = $wpdb->prefix . 'gs_imagens_ocorrencias';			

			// Determina quais campos de título/descrição usar com base no tipo (processo ou ocorrência)
			if ( $is_processo ) {
				$title_field_name = 'imagem_titulo_processo';
				$desc_field_name  = 'imagem_descricao_processo';
			} else {
				$title_field_name = 'imagem_titulo_ocorrencia';
				$desc_field_name  = 'imagem_descricao_ocorrencia';
			}
			$image_titles       = isset( $_POST[ $title_field_name ] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST[ $title_field_name ] ) ) : array();
			$image_descriptions = isset( $_POST[ $desc_field_name ] ) ? array_map( 'sanitize_textarea_field', wp_unslash( $_POST[ $desc_field_name ] ) ) : array();

			for ( $i = 0; $i < count( $files['name'] ); $i++ ) {
				// Apenas processa se um arquivo foi realmente enviado para este índice
				if ( $files['error'][ $i ] === UPLOAD_ERR_OK ) {
					$file_data = array(
						'name'     => $files['name'][ $i ],
						'type'     => $files['type'][ $i ],
						'tmp_name' => $files['tmp_name'][ $i ],
						'error'    => $files['error'][ $i ],
						'size'     => $files['size'][ $i ],
					);
					if ( function_exists( 'uploadFilesWPDrive' ) ) {
						$drive_file_response = uploadFilesWPDrive( $file_data, $file_data['name'], 'gestao_ocorrencias' );
						// VERIFICAÇÃO ROBUSTA: Garante que o upload foi bem-sucedido e que um ID foi retornado.
						if ( isset( $drive_file_response['error'] ) && 0 === $drive_file_response['error'] && ! empty( $drive_file_response['resposta']['id'] ) ) {
							$wpdb->insert(
								$table_imagens,
								array(
									'ocorrencia_id'   => $ocorrencia_id,
									'imagem_url'      => $drive_file_response['resposta']['webViewLink'] ?? '',
									'titulo'          => $image_titles[ $i ] ?? '', // Salva o título
									'descricao'       => $image_descriptions[ $i ] ?? '', // Salva a descrição
									'imagem_id_drive' => $drive_file_response['resposta']['id'],
								)
							);
							$new_images_added = true;
						} else {
							error_log( 'GS Plugin - Falha no upload para o Drive (update): ' . print_r( $drive_file_response, true ) );
						}
					}
				}
			}
		}

		$result = $wpdb->update(
			$table_ocorrencias,
			array(
				'titulo'             => $titulo,
				'descricao'          => $descricao,
				'processos'          => $is_processo,
				'data_ultima_edicao' => current_time( 'mysql' ), // Sempre atualiza a data de modificação
			),
			array( 'id' => $ocorrencia_id ),
			array( '%s', '%s', '%d', '%s' ),
			array( '%d' )
		);

		// Constrói a mensagem de sucesso com base nas ações realizadas.
		if ( false === $result ) {
			wp_send_json_error( array( 'message' => 'Falha ao atualizar a ocorrência no banco de dados.' ) );
		}

		// Esta lógica agora está fora do 'else' para ser sempre executada.
		$messages = array();
		if ( $result > 0 ) {
			$messages[] = 'Ocorrência atualizada com sucesso.';
		}
		if ( $images_removed_count > 0 ) {
			$messages[] = "$images_removed_count imagem(ns) removida(s).";
		}
		if ( $new_images_added ) {
			$messages[] = 'Novas imagens foram adicionadas.';
		}

		$final_message = ! empty( $messages ) ? implode( ' ', $messages ) : 'Nenhuma alteração detectada.';
		// Ação é considerada válida se houver alteração no texto, remoção ou adição de imagens.
		$action_taken = ( $result > 0 || $images_removed_count > 0 || $new_images_added );

		// Envia a resposta JSON.
		wp_send_json_success( array( 'message' => $final_message, 'action_taken' => $action_taken ) );
	}

	/**
	 * Função auxiliar para deletar uma imagem do Drive e do Banco de Dados.
	 *
	 * @param int $image_db_id ID da imagem na tabela gs_imagens_ocorrencias.
	 * @param int $ocorrencia_id ID da ocorrência para verificação de propriedade.
	 * @return bool True em sucesso, False em falha.
	 */
	private function _delete_image_by_id( $image_db_id, $ocorrencia_id ) {
		global $wpdb;
		$table_imagens = $wpdb->prefix . 'gs_imagens_ocorrencias';

		$image_data = $wpdb->get_row( $wpdb->prepare( "SELECT imagem_id_drive FROM {$table_imagens} WHERE id = %d AND ocorrencia_id = %d", $image_db_id, $ocorrencia_id ) );

		// Se a imagem não existe ou não pertence à ocorrência, considera como "sucesso" para não travar o processo.
		if ( ! $image_data ) {
			return true;
		}

		// Se tem um ID do Drive, tenta deletar de lá primeiro.
		if ( ! empty( $image_data->imagem_id_drive ) && function_exists( 'deleteFileWPDrive' ) ) {
			$delete_drive_result = deleteFileWPDrive( $image_data->imagem_id_drive );

			// Se a exclusão no Drive falhar, não remove do banco e retorna erro.
			if ( ! isset( $delete_drive_result['error'] ) || 0 !== $delete_drive_result['error'] ) {
				error_log( 'GS Plugin - FALHA ao deletar do Drive. Imagem DB ID ' . $image_db_id . ' não será removida do banco.' );
				return false;
			}
		}

		// Se chegou aqui, ou deletou do Drive com sucesso, ou não tinha ID do Drive. Deleta do banco.
		$delete_db_result = $wpdb->delete( $table_imagens, array( 'id' => $image_db_id ) );

		return false !== $delete_db_result;
	}

	/**
	 * Callback AJAX para excluir imagens selecionadas de uma ocorrência.
	 *
	 * @since 1.0.0
	 */
	public function ajax_delete_images() {
		check_ajax_referer( 'gs_ajax_nonce', 'nonce' );

		if ( ! isset( $_POST['ocorrencia_id'], $_POST['image_ids'] ) || ! is_array( $_POST['image_ids'] ) ) {
			wp_send_json_error( array( 'message' => 'Dados inválidos para exclusão.' ) );
		}

		$ocorrencia_id = absint( $_POST['ocorrencia_id'] );
		$image_ids     = array_map( 'absint', $_POST['image_ids'] );

		// --- VERIFICAÇÃO DE PERMISSÃO PARA EXCLUIR IMAGENS ---
		global $wpdb;
		$table_ocorrencias = $wpdb->prefix . 'gs_ocorrencias';
		$ocorrencia_creator_id = $wpdb->get_var( $wpdb->prepare( "SELECT user_id FROM {$table_ocorrencias} WHERE id = %d", $ocorrencia_id ) );
		$current_user_id       = get_current_user_id();
		$can_delete_images     = ( (int) $current_user_id === (int) $ocorrencia_creator_id ) || current_user_can( 'manage_options' );

		if ( ! $can_delete_images ) {
			wp_send_json_error( array( 'message' => 'Você não tem permissão para excluir imagens desta ocorrência.' ) );
		}
		// --- FIM DA VERIFICAÇÃO DE PERMISSÃO ---


		$deleted_count = 0;
		$failed_count  = 0;

		foreach ( $image_ids as $image_id ) {
			if ( $this->_delete_image_by_id( $image_id, $ocorrencia_id ) ) {
				$deleted_count++;
			} else {
				$failed_count++;
			}
		}

		if ( $failed_count > 0 ) {
			wp_send_json_error( array( 'message' => "Falha ao excluir {$failed_count} imagem(ns). {$deleted_count} foram removidas com sucesso." ) );
		}

		wp_send_json_success( array( 'message' => "{$deleted_count} imagem(ns) excluída(s) com sucesso!" ) );
	}

	/**
	 * Callback AJAX para excluir uma ocorrência e seus dados associados.
	 *
	 * @since 1.0.0
	 */
	public function ajax_delete_ocorrencia() {
		check_ajax_referer( 'gs_ajax_nonce', 'nonce' );

		if ( ! isset( $_POST['id'] ) ) {
			wp_send_json_error( array( 'message' => 'ID da ocorrência ausente.' ) );
		}

		global $wpdb;
		$ocorrencia_id     = absint( $_POST['id'] );
		$table_ocorrencias = $wpdb->prefix . 'gs_ocorrencias';
		$table_imagens     = $wpdb->prefix . 'gs_imagens_ocorrencias';

		// Busca a ocorrência para verificar permissões.
		$ocorrencia = $wpdb->get_row( $wpdb->prepare( "SELECT id, user_id FROM {$table_ocorrencias} WHERE id = %d", $ocorrencia_id ) );
		if ( ! $ocorrencia ) {
			wp_send_json_error( array( 'message' => 'Ocorrência não encontrada.' ) );
		}

		// Verifica permissão.
		$current_user_id = get_current_user_id();
		$can_delete      = ( (int) $current_user_id === (int) $ocorrencia->user_id ) || current_user_can( 'manage_options' );
		if ( ! $can_delete ) {
			wp_send_json_error( array( 'message' => 'Você não tem permissão para excluir esta ocorrência.' ) );
		}

		// 1. Excluir imagens do Google Drive.
		$imagens_a_deletar = $wpdb->get_results( $wpdb->prepare( "SELECT id, imagem_id_drive FROM {$table_imagens} WHERE ocorrencia_id = %d", $ocorrencia_id ) );
		if ( ! empty( $imagens_a_deletar ) && function_exists( 'deleteFileWPDrive' ) ) {
			foreach ( $imagens_a_deletar as $imagem ) {
				$this->_delete_image_by_id( $imagem->id, $ocorrencia_id );
			}
		}

		// 2. Excluir registros de imagens do banco de dados.
		$wpdb->delete( $table_imagens, array( 'ocorrencia_id' => $ocorrencia_id ), array( '%d' ) );

		// 3. Excluir a ocorrência principal.
		$result = $wpdb->delete( $table_ocorrencias, array( 'id' => $ocorrencia_id ), array( '%d' ) );

		if ( false === $result ) {
			wp_send_json_error( array( 'message' => 'Falha ao excluir a ocorrência do banco de dados.' ) );
		}

		if ( $result > 0 ) {
			wp_send_json_success( array( 'message' => 'Ocorrência excluída com sucesso!' ) );
		} else {
			// Isso pode acontecer se a ocorrência já foi excluída em outra aba, por exemplo.
			wp_send_json_error( array( 'message' => 'A ocorrência não pôde ser encontrada para exclusão (pode já ter sido removida).' ) );
		}
	}


	/**
	 * Callback AJAX para incrementar o contador de uma ocorrência.
	 *
	 * @since 1.0.0
	 */
	public function ajax_increment_counter() {
		check_ajax_referer( 'gs_ajax_nonce', 'nonce' );

		global $wpdb;
		$id         = absint( $_POST['id'] );
		$table_name = $wpdb->prefix . 'gs_ocorrencias';

		if ( ! isset( $_POST['id'] ) ) {
			wp_send_json_error( array( 'message' => 'ID ausente.' ) );
		}

		// Lógica de permissão
		$ocorrencia_role = $wpdb->get_var( $wpdb->prepare( "SELECT user_role FROM {$table_name} WHERE id = %d", $id ) );
		$current_user    = wp_get_current_user();
		$user_role       = ! empty( $current_user->roles ) ? $current_user->roles[0] : null;

		$can_increment = false;
		if ( current_user_can( 'manage_options' ) ) {
			$can_increment = true; // Administradores podem incrementar qualquer ocorrência.
		} elseif ( $user_role && $ocorrencia_role === $user_role ) {
			$can_increment = true; // Usuários podem incrementar se a role for a mesma.
		}

		if ( ! $can_increment ) {
			wp_send_json_error( array( 'message' => 'Você não tem permissão para registrar repetições nesta ocorrência.' ) );
		}

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

		global $wpdb;
		$table_name = $wpdb->prefix . 'gs_ocorrencias';
		$id         = absint( $_POST['id'] );

		if ( ! isset( $_POST['id'], $_POST['solucao'] ) ) {
			wp_send_json_error( array( 'message' => 'Dados ausentes.' ) );
		}

		// Lógica de permissão
		$ocorrencia_role = $wpdb->get_var( $wpdb->prepare( "SELECT user_role FROM {$table_name} WHERE id = %d", $id ) );
		$current_user    = wp_get_current_user();
		$user_role       = ! empty( $current_user->roles ) ? $current_user->roles[0] : null;

		$can_edit = false;
		if ( current_user_can( 'manage_options' ) ) {
			$can_edit = true; // Administradores podem editar tudo.
		} elseif ( $user_role && $ocorrencia_role === $user_role ) {
			$can_edit = true; // Usuários podem editar se a role for a mesma.
		}

		if ( ! $can_edit ) {
			wp_send_json_error( array( 'message' => 'Você não tem permissão para modificar a solução desta ocorrência.' ) );
		}

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

		global $wpdb;
		$table_name = $wpdb->prefix . 'gs_ocorrencias';
		$id         = absint( $_POST['id'] );

		if ( ! isset( $_POST['id'] ) ) {
			wp_send_json_error( array( 'message' => 'Dados ausentes.' ) );
		}

		// Lógica de permissão
		$ocorrencia_role = $wpdb->get_var( $wpdb->prepare( "SELECT user_role FROM {$table_name} WHERE id = %d", $id ) );
		$current_user    = wp_get_current_user();
		$user_role       = ! empty( $current_user->roles ) ? $current_user->roles[0] : null;

		$can_edit = false;
		if ( current_user_can( 'manage_options' ) ) {
			$can_edit = true; // Administradores podem editar tudo.
		} elseif ( $user_role && $ocorrencia_role === $user_role ) {
			$can_edit = true; // Usuários podem editar se a role for a mesma.
		}

		if ( ! $can_edit ) {
			wp_send_json_error( array( 'message' => 'Você não tem permissão para modificar a solução desta ocorrência.' ) );
		}

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
			'edit_posts',       // Permissão alterada para ser mais abrangente
			'gs-ocorrencias',       // Slug do menu
			array( $this, 'render_admin_manager_page' ), // Função de callback para renderizar a página
			'dashicons-list-view',  // Ícone do menu
			25                      // Posição no menu
		);

		add_submenu_page(
			'gs-ocorrencias', // Slug do menu pai
			'Dashboard',
			'Dashboard',
			'edit_posts',       // Permissão alterada para ser mais abrangente
			'gs-dashboard', // Slug deste submenu
			array( $this, 'render_dashboard_page' ) // Função de callback
		);
	}

	/**
	 * Renderiza a página do dashboard.
	 *
	 * @since 1.0.0
	 */
	public function render_dashboard_page() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'gs_ocorrencias';

		// Lógica de filtragem por user_role
		$current_user = wp_get_current_user();
		$user_role    = ! empty( $current_user->roles ) ? $current_user->roles[0] : null;
		$where_clause = '';
		$role_arg     = array();

		if ( ! current_user_can( 'manage_options' ) && $user_role ) {
			$where_clause = ' WHERE user_role = %s';
			$role_arg[]   = $user_role;
		}

		// Métricas para os cards
		$ocorrencias_mes_total      = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM {$table_name}" . ( empty( $where_clause ) ? ' WHERE' : $where_clause . ' AND' ) . " MONTH(data_registro) = MONTH(CURDATE()) AND YEAR(data_registro) = YEAR(CURDATE())", $role_arg ) );
		$ocorrencias_abertas_total  = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM {$table_name}" . ( empty( $where_clause ) ? ' WHERE' : $where_clause . ' AND' ) . ' status = %s', array_merge( $role_arg, array( 'aberto' ) ) ) );
		$ocorrencias_solucionadas_total = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM {$table_name}" . ( empty( $where_clause ) ? ' WHERE' : $where_clause . ' AND' ) . ' status = %s', array_merge( $role_arg, array( 'solucionada' ) ) ) );

		// Métricas para o gráfico de pizza do mês atual
		$solucionadas_mes = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM {$table_name}" . ( empty( $where_clause ) ? ' WHERE' : $where_clause . ' AND' ) . " status = %s AND MONTH(data_registro) = MONTH(CURDATE()) AND YEAR(data_registro) = YEAR(CURDATE())", array_merge( $role_arg, array( 'solucionada' ) ) ) );
		$abertas_mes      = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM {$table_name}" . ( empty( $where_clause ) ? ' WHERE' : $where_clause . ' AND' ) . " status = %s AND MONTH(data_registro) = MONTH(CURDATE()) AND YEAR(data_registro) = YEAR(CURDATE())", array_merge( $role_arg, array( 'aberto' ) ) ) );

		// Métricas para o gráfico de linha (últimos 30 dias)
		$line_chart_data_raw = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DATE(data_registro) as dia, COUNT(id) as total 
				 FROM {$table_name} 
				 " . ( empty( $where_clause ) ? ' WHERE' : $where_clause . ' AND' ) . " data_registro >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) 
				 GROUP BY DATE(data_registro) 
				 ORDER BY DATE(data_registro) ASC",
				$role_arg
			),
			OBJECT_K 
		);

		$line_chart_labels = array();
		$line_chart_data   = array();
		for ( $i = 29; $i >= 0; $i-- ) {
			$date_key               = date( 'Y-m-d', strtotime( "-$i days" ) );
			$line_chart_labels[]    = date( 'd/m', strtotime( $date_key ) );
			$line_chart_data[]      = isset( $line_chart_data_raw[ $date_key ] ) ? $line_chart_data_raw[ $date_key ]->total : 0;
		}

		wp_localize_script(
			'gs-admin-main',
			'gs_dashboard_data',
			array(
				'pie_solucionadas'    => $solucionadas_mes,
				'pie_abertas'         => $abertas_mes,
				'line_labels'         => $line_chart_labels,
				'line_data'           => $line_chart_data,
			)
		);

		echo '<div class="wrap">';
		require_once GS_PLUGIN_PATH . 'app/assets/views/dashboard.php';
		echo '</div>';
	}

	/**
	 * Renderiza a página principal de gerenciamento no painel de administração.
	 *
	 * @since 1.0.0
	 */
	public function render_admin_manager_page() {
		echo '<div class="wrap">';
		echo '<div class="sna-gs-header-title-wrapper">'; 
		echo '<img src="' . esc_url( GS_PLUGIN_URL . 'app/assets/views/logoPrincipal.png' ) . '" alt="Logo" class="sna-gs-header-logo">';
		echo '<h1 class="wp-heading-inline">Gerenciar Ocorrências</h1>';
		echo '</div>';
		echo '<p class="sna-gs-page-description">Esta é uma ferramenta desenvolvida para registrar, e acompanhar problemas. Seu principal objetivo é centralizar as informações e permitir que cada ocorrência seja monitorada desde o momento em que é registrada até sua resolução.</p>';


	
		if ( isset( $_GET['ocorrencia_salva'] ) && '1' === $_GET['ocorrencia_salva'] ) {
			echo '<div class="notice notice-success is-dismissible"><p>Ocorrência salva com sucesso!</p></div>';
		}


		echo '<a href="#" id="sna-gs-load-form-btn" class="sna-gs-fab">
				<span class="sna-gs-fab-icon">+</span>
				<span class="sna-gs-fab-text">Novo</span>
				
			
			  </a>';


		// O contêiner agora é carregado vazio. O JS se encarregará de chamar a view 'list' via AJAX.
		echo '<div id="sna-gs-view-container"><p>Carregando...</p></div>';
		echo '</div>'; 
	}

	/**
	 * Enfileira os scripts e estilos do admin.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_admin_scripts( $hook ) {
		// Garante que o script só seja carregado na nossa página do plugin.
		if ( 'toplevel_page_gs-ocorrencias' !== $hook && 'ocorrencias_page_gs-dashboard' !== $hook ) {
			return;
		}
		// Enfileira a biblioteca Chart.js a partir de um CDN
		wp_enqueue_script( 'chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '4.4.0', true );
		// Usa a data de modificação do arquivo para versionamento, forçando o navegador a recarregar se houver mudanças.
		$style_version = filemtime( GS_PLUGIN_PATH . 'app/assets/style.css' );
		$script_version = filemtime( GS_PLUGIN_PATH . 'app/ajax/admin-main.js' );
		wp_enqueue_style( 'gs-admin-styles', GS_PLUGIN_URL . 'app/assets/style.css', array(), $style_version );
		wp_enqueue_script( 'gs-admin-main', GS_PLUGIN_URL . 'app/ajax/admin-main.js', array( 'jquery' ), $script_version, true );
		// Passa dados do PHP para o JavaScript (como o nonce de segurança, ID do usuário atual e status de admin).
		wp_localize_script( 'gs-admin-main', 'gs_ajax_object', array(
			'nonce'           => wp_create_nonce( 'gs_ajax_nonce' ),
			'current_user_id' => get_current_user_id(),
			'is_admin'        => current_user_can( 'manage_options' ),
			'logoOcorrencia'  => GS_PLUGIN_URL . 'app/assets/views/logoPrincipal.png',
			'logoProcesso'    => GS_PLUGIN_URL . 'app/assets/views/Processosinicial.png',
			'titles'          => array(
				'ocorrencia' => 'Gerenciar Ocorrências',
				'processo'   => 'Gerenciar Processos',
			),
			'descriptions'    => array(
				'ocorrencia' => 'Esta é uma ferramenta desenvolvida para registrar, e acompanhar problemas. Seu principal objetivo é centralizar as informações e permitir que cada ocorrência seja monitorada desde o momento em que é registrada até sua resolução.',
				'processo'   => 'Esta é a área para gestão de processos internos. Registre e acompanhe os fluxos de trabalho para otimizar as operações.',
				'ocorrencia' => 'Aqui você pode adicionar uma nova ocorrencia, sinta-se a vontade para adicionar detalhes e imagens. Certifique-se de que a ocorrencia já não foi registrada antes, para que não tenha duplicidade',
				'processo'   => 'Aqui você pode adicionar um novo processo, sinta-se a vontade para adicionar informação e imagens',
			),
		) );
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