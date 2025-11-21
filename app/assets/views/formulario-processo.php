<?php
/**
 * Template do formulário de gestão de processos.
 *
 * @package GS_Plugin
 * @since   1.0.0
 *
 * @var stdClass|null $ocorrencia Os dados do processo a ser editado, ou null para novo.
 * @var bool          $is_editing Flag que indica se está editando.
 */

// Se este arquivo for chamado diretamente, aborte.
if ( ! defined( 'WPINC' ) ) {
	die;
}

$form_title_processo = $is_editing ? 'Atualizar Processo' : 'Salvar Processo';
?>

<div id="sna-gs-form-processo-wrapper" style="display: none;">
	<!-- O form ID e a action AJAX podem ser os mesmos, pois a lógica de salvamento já lida com o campo 'processos' -->
	<div>
		<label for="sna-gs-titulo-processo">Título do Processo</label>
		<input type="text" id="sna-gs-titulo-processo" name="sna-gs-titulo-ocorrencia" value="<?php echo esc_attr( $is_editing ? $ocorrencia->titulo : '' ); ?>" required>
	</div>

	<div>
		<label for="sna-gs-descricao-processo">Descrição do Processo</label>
		<textarea id="sna-gs-descricao-processo" name="sna-gs-descricao-ocorrencia" rows="8" required><?php echo esc_textarea( $is_editing ? $ocorrencia->descricao : '' ); ?></textarea>
	</div>

	<div>
		<label for="sna-gs-imagem-processo" class="button button-file-upload">
			+ Novo Anexo (Opcional)
		</label>
		<input type="file" id="sna-gs-imagem-processo" name="sna-gs-imagem-ocorrencia[]" accept="image/*" multiple style="display: none;">

		<div id="sna-gs-image-preview-container-processo" data-existing-images="<?php echo esc_attr( $is_editing && isset( $ocorrencia->imagens ) ? count( $ocorrencia->imagens ) : 0 ); ?>"></div>

		<?php if ( $is_editing && ! empty( $ocorrencia->imagens ) ) : ?>
			<div class="sna-gs-current-images-wrapper"> <!-- Contêiner principal para anexos existentes -->
				<h4>Anexos Atuais</h4>
				<div id="sna-gs-existing-images-list-processo"> <!-- Contêiner para a lista de anexos -->
					<?php foreach ( $ocorrencia->imagens as $image ) : ?>
						<div class="sna-gs-current-image-item" data-image-id="<?php echo esc_attr( $image->id ); ?>"> <!-- Item individual do anexo -->
							
							<!-- 1. Título do Anexo -->
							<?php if ( ! empty( $image->titulo ) ) : ?>
								<p class="gs-image-title"><strong><?php echo esc_html( $image->titulo ); ?></strong></p>
							<?php endif; ?>

							<!-- 2. Anexo (Imagem) -->
							<a href="<?php echo esc_url( $image->display_url ); ?>" target="_blank" rel="noopener noreferrer">
								<img src="<?php echo esc_url( $image->display_url ); ?>" alt="<?php echo esc_attr( $image->titulo ); ?>" style="max-width: 200px; height: auto; display: block; margin-bottom: 10px;">
							</a>

							<!-- 3. Descrição do Anexo -->
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
				<button type="button" id="sna-gs-delete-selected-images-btn-processo" class="button button-danger">Excluir Anexos Selecionados</button>
			</div>
		<?php endif; ?>
		<p class="description" style="margin-top: 5px;">
			<?php echo $is_editing ? 'Adicione novos anexos ou marque para remover os existentes.' : 'Selecione um ou mais arquivos de imagem.'; ?>
		</p>
	</div>
</div>