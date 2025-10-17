<?php
/**
 * Template do formulário de gestão de ocorrências.
 *
 * @package GS_Plugin
 * @since   1.0.0
 */

// Se este arquivo for chamado diretamente, aborte.
if ( ! defined( 'WPINC' ) ) {
	die;
}
?>

<div id="sna-gs-form-view">
	<a href="#" id="sna-gs-load-list-btn" class="page-title-action">
		Ver Ocorrências
	</a>

	<hr class="wp-header-end">

	<form method="post" id="sna-gs-form-ocorrencia-submit">

		<?php // Adiciona um campo de segurança nonce ?>
		<?php wp_nonce_field( 'gs_salvar_ocorrencia_action', 'gs_ocorrencia_nonce' ); ?>

		<div>
			<label for="sna-gs-titulo-ocorrencia">Título da Ocorrência</label>
			<input type="text" id="sna-gs-titulo-ocorrencia" name="sna-gs-titulo-ocorrencia" value="" required>
		</div>

		<div>
			<label for="sna-gs-descricao-ocorrencia">Descrição</label>
			<textarea id="sna-gs-descricao-ocorrencia" name="sna-gs-descricao-ocorrencia" rows="8" required></textarea>
		</div>

		<div>
			<button type="submit" name="gs_salvar_ocorrencia" class="button button-primary">Salvar Ocorrência</button>
		</div>

	</form>
</div>