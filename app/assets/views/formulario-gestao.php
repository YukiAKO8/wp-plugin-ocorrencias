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
			<?php if ( $is_editing && ! empty( $ocorrencia->imagem_url ) ) : ?>
				<p>Imagem atual:</p>
				<img src="<?php echo esc_url( $ocorrencia->imagem_url ); ?>" style="max-width: 200px; height: auto; display: block; margin-bottom: 10px; border-radius: 8px;">
				<input type="checkbox" id="sna-gs-remover-imagem" name="sna-gs-remover-imagem" value="1">
				<label for="sna-gs-remover-imagem" style="display: inline-block; margin-bottom: 15px;">Remover imagem atual</label>
				<br>
			<?php endif; ?>
			<input type="file" id="sna-gs-imagem-ocorrencia" name="sna-gs-imagem-ocorrencia" accept="image/*" style="display: none;">
			<?php if ( $is_editing ) : ?>
				<p class="description" style="margin-top: 5px;">Selecione um novo arquivo para substituir a imagem atual.</p>
			<?php endif; ?>
		</div>

		<div class="sna-gs-form-actions">
			<a href="#" id="sna-gs-load-list-btn" class="button button-secondary">Cancelar</a>
			<button type="submit" name="gs_submit_ocorrencia" class="button button-primary"><?php echo esc_html( $form_title ); ?></button>
		</div>

	</form>
</div>