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
$is_processo_on_load = ( $is_editing && $ocorrencia->processos == 1 ) || ( ! $is_editing && isset( $_POST['type'] ) && 'processo' === $_POST['type'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

$form_title = $is_editing ? 'Atualizar' : 'Salvar';
?>

<div id="sna-gs-form-view">

	<form method="post" id="sna-gs-form-ocorrencia-submit" enctype="multipart/form-data">

		<div class="sna-gs-type-switch-container">
			<label class="sna-gs-type-switch">
				<input type="checkbox" id="sna-gs-type-toggle" name="processos" value="1" <?php checked( $is_processo_on_load ); ?>>
				<span class="sna-gs-switch-slider">
					<span class="sna-gs-switch-label-off">Ocorrência</span>
					<span class="sna-gs-switch-label-on">Processo</span>
				</span>
			</label>
		</div>

		<?php wp_nonce_field( 'gs_ajax_nonce', 'nonce' ); ?>
		<?php if ( $is_editing ) : ?>
			<input type="hidden" name="ocorrencia_id" value="<?php echo esc_attr( $ocorrencia->id ); ?>">
		<?php endif; ?>
		
		<!-- Wrapper para o formulário de Ocorrência -->
		<div id="sna-gs-form-ocorrencia-wrapper">
			<div>
				<label for="sna-gs-titulo-ocorrencia">Título da Ocorrência</label>
				<input type="text" id="sna-gs-titulo-ocorrencia" name="sna-gs-titulo-ocorrencia" value="<?php echo esc_attr( $is_editing ? $ocorrencia->titulo : '' ); ?>" required>
			</div>

			<div>
				<label for="sna-gs-descricao-ocorrencia">Descrição da Ocorrência</label>
				<textarea id="sna-gs-descricao-ocorrencia" name="sna-gs-descricao-ocorrencia" rows="8" required><?php echo esc_textarea( $is_editing ? $ocorrencia->descricao : '' ); ?></textarea>
			</div>

			<div>
				<label for="sna-gs-imagem-ocorrencia" class="button button-file-upload">
					+ Nova Imagem (Opcional)
				</label>
				<input type="file" id="sna-gs-imagem-ocorrencia" name="sna-gs-imagem-ocorrencia[]" accept="image/*" multiple style="display: none;">

				<div id="sna-gs-image-preview-container" data-existing-images="<?php echo esc_attr( $is_editing && isset( $ocorrencia->imagens ) ? count( $ocorrencia->imagens ) : 0 ); ?>"></div>

				<?php if ( $is_editing && ! empty( $ocorrencia->imagens ) ) : ?>
					<div class="sna-gs-current-images-wrapper"> <!-- Contêiner principal para imagens existentes -->
						<h4>Imagens Anexadas</h4>
						<div id="sna-gs-existing-images-list"> <!-- Contêiner para a lista de imagens -->
							<?php foreach ( $ocorrencia->imagens as $image ) : ?>
								<div class="sna-gs-current-image-item" data-image-id="<?php echo esc_attr( $image->id ); ?>"> <!-- Item individual da imagem -->
									
									<!-- 1. Título da Imagem -->
									<?php if ( ! empty( $image->titulo ) ) : ?>
										<p class="gs-image-title"><strong><?php echo esc_html( $image->titulo ); ?></strong></p>
									<?php endif; ?>

									<!-- 2. Imagem -->
									<a href="<?php echo esc_url( $image->display_url ); ?>" target="_blank" rel="noopener noreferrer">
										<img src="<?php echo esc_url( $image->display_url ); ?>" alt="<?php echo esc_attr( $image->titulo ); ?>" style="max-width: 200px; height: auto; display: block; margin-bottom: 10px;">
									</a>

									<!-- 3. Descrição da Imagem -->
									<?php if ( ! empty( $image->descricao ) ) : ?>
										<p class="gs-image-description"><?php echo nl2br( esc_html( $image->descricao ) ); ?></p>
									<?php endif; ?>

									<!-- Checkbox para exclusão -->
									<label>
										<input type="checkbox" name="images_to_delete[]" value="<?php echo esc_attr( $image->id ); ?>" class="sna-gs-delete-image-checkbox">
										Marcar para remover
									</label>
								</div>
							<?php endforeach; ?>
						</div>
						<button type="button" id="sna-gs-delete-selected-images-btn" class="button button-danger">Excluir Imagens Selecionadas</button>
					</div>
				<?php endif; ?>
				<p class="description" style="margin-top: 5px;">
					<?php echo $is_editing ? 'Adicione novas imagens ou marque para remover as existentes.' : 'Selecione um ou mais arquivos de imagem.'; ?>
				</p>
			</div>
		</div>

		<!-- Inclui o formulário de Processo -->
		<?php include GS_PLUGIN_PATH . 'app/assets/views/formulario-processo.php'; ?>

		<div class="sna-gs-form-actions">
			<a href="#" id="sna-gs-load-list-btn" class="button button-secondary">Cancelar</a>
			<button type="submit" name="gs_submit_ocorrencia" class="button button-primary">
				<?php echo esc_html( $form_title ); ?> <span id="sna-gs-submit-label"></span>
			</button>
		</div>

	</form>
</div>

<script>
jQuery(document).ready(function($) {
    // Função para lidar com a seleção de arquivos
    function handleFileSelection(fileInput, previewContainer, type) {
        const files = fileInput.files;
        if (!files.length) {
            return;
        }

        // Define os nomes dos campos com base no tipo (ocorrencia ou processo)
        const titleName = type === 'ocorrencia' ? 'imagem_titulo_ocorrencia[]' : 'imagem_titulo_processo[]';
        const descName = type === 'ocorrencia' ? 'imagem_descricao_ocorrencia[]' : 'imagem_descricao_processo[]';

        // Limpa apenas as pré-visualizações de NOVAS imagens
        $(previewContainer).find('.sna-gs-new-image-preview-item').remove();

        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            if (!file.type.startsWith('image/')){ continue; } // Pula arquivos que não são imagens

            const reader = new FileReader();

            reader.onload = function(e) {
                const previewWrapper = `
                    <div class="sna-gs-new-image-preview-item">
                        <img src="${e.target.result}" alt="Pré-visualização da imagem">
                        <div class="sna-gs-image-meta-fields">
                            <input type="text" name="${titleName}" placeholder="Título do anexo" class="widefat">
                            <textarea name="${descName}" placeholder="Descrição do anexo" class="widefat" rows="2"></textarea>
                        </div>
                    </div>
                `;
                $(previewContainer).append(previewWrapper);
            };

            reader.readAsDataURL(file);
        }
    }

    // Delegação de evento para o input de arquivo de Ocorrências
    $(document).on('change', '#sna-gs-imagem-ocorrencia', function() {
        handleFileSelection(this, '#sna-gs-image-preview-container', 'ocorrencia');
    });

    // Delegação de evento para o input de arquivo de Processos
    $(document).on('change', '#sna-gs-imagem-processo', function() {
        handleFileSelection(this, '#sna-gs-image-preview-container-processo', 'processo');
    });
});
</script>