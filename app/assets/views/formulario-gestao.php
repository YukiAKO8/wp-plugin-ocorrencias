<?php
/**
 * Template do formulário de gestão de ocorrências.
 *
 * @package GS_Plugin
 * @since   1.0.0
 *
 * @var stdClass|null $ocorrencia Os dados da ocorrência a ser editada, ou null para nova ocorrência.
 */

// Se este arquivo for chamado diretamente, aborte.
if ( ! defined( 'WPINC' ) ) {
	die;
}

$is_editing = isset( $ocorrencia ) && ! empty( $ocorrencia->id );
$form_title = $is_editing ? 'Atualizar Ocorrência' : 'Salvar Ocorrência';
?>

<div id="sna-gs-form-view">

	<form method="post" id="sna-gs-form-ocorrencia-submit" enctype="multipart/form-data">

		<?php wp_nonce_field( 'gs_ajax_nonce', 'nonce' ); ?>
		<?php if ( $is_editing ) : ?>
			<input type="hidden" name="ocorrencia_id" value="<?php echo esc_attr( $ocorrencia->id ); ?>">
		<?php endif; ?>

		<div>
			<label for="sna-gs-titulo-ocorrencia">Título da Ocorrência</label>
			<input type="text" id="sna-gs-titulo-ocorrencia" name="sna-gs-titulo-ocorrencia" value="<?php echo esc_attr( $is_editing ? $ocorrencia->titulo : '' ); ?>" required>
		</div>

		<div>
			<label for="sna-gs-descricao-ocorrencia">Descrição</label>
			<textarea id="sna-gs-descricao-ocorrencia" name="sna-gs-descricao-ocorrencia" rows="8" required><?php echo esc_textarea( $is_editing ? $ocorrencia->descricao : '' ); ?></textarea>
		</div>

		<div>
			<label for="sna-gs-imagem-ocorrencia" class="button button-file-upload">
				+ Nova Imagem (Opcional)
			</label>
			<input type="file" id="sna-gs-imagem-ocorrencia" name="sna-gs-imagem-ocorrencia[]" accept="image/*" multiple style="display: none;">

			<div id="sna-gs-image-preview-container" data-existing-images="<?php echo esc_attr( $is_editing && isset( $ocorrencia->imagens ) ? count( $ocorrencia->imagens ) : 0 ); ?>"></div>

			<?php if ( $is_editing && ! empty( $ocorrencia->imagens ) ) : ?>
				<div class="sna-gs-current-images-wrapper">
					<p>Imagens atuais:</p>
					<div id="sna-gs-existing-images-list">
						<?php foreach ( $ocorrencia->imagens as $image ) : ?>
							<div class="sna-gs-current-image-item" data-image-id="<?php echo esc_attr( $image->id ); ?>">
								<input type="checkbox" name="images_to_delete[]" value="<?php echo esc_attr( $image->id ); ?>" class="sna-gs-delete-image-checkbox">
								<img src="<?php echo esc_url( $image->display_url ); ?>" alt="Imagem existente">
							</div>
						<?php endforeach; ?>
					</div>
					<button type="button" id="sna-gs-delete-selected-images-btn" class="button button-danger">Excluir Imagens Selecionadas</button>
				</div>
			<?php endif; ?>
			<p class="description" style="margin-top: 5px;">
				<?php echo $is_editing ? 'Adicione novas imagens ou clique no "X" para remover as existentes.' : 'Selecione até 4 arquivos de imagem para esta ocorrência.'; ?>
			</p>
		</div>

		<div class="sna-gs-form-actions">
			<a href="#" id="sna-gs-load-list-btn" class="button button-secondary">Cancelar</a>
			<button type="submit" name="gs_submit_ocorrencia" class="button button-primary"><?php echo esc_html( $form_title ); ?></button>
		</div>

	</form>
</div>