<?php
/**
 * Template para mostrar uma única ocorrência.
 *
 * @package GS_Plugin
 * @since   1.0.0
 *
 * @var stdClass $ocorrencia Os dados da ocorrência.
 */

// Se este arquivo for chamado diretamente, aborte.
if ( ! defined( 'WPINC' ) ) {
	die;
}
?>

<div id="sna-gs-details-view">
	<a href="#" id="sna-gs-load-list-btn" class="page-title-action">
		&larr; Voltar para a Lista
	</a>

	<hr class="wp-header-end">

	<div class="sna-gs-details-content">
		<h2 class="sna-gs-details-titulo"><?php echo esc_html( $ocorrencia->titulo ); ?></h2>

		<div class="sna-gs-details-meta">
			<span>Criado por: <strong><?php echo esc_html( $ocorrencia->display_name ?? 'Usuário não encontrado' ); ?></strong></span>
			<span> | <strong><?php echo esc_html( date( 'd/m/Y', strtotime( $ocorrencia->data_registro ) ) ); ?></strong></span>
			<span> | <strong><?php echo esc_html( date( 'H:i', strtotime( $ocorrencia->data_registro ) ) ); ?></strong></span>
			<div class="sna-gs-details-counter">
				<button id="sna-gs-increment-btn" class="button button-increment" data-id="<?php echo esc_attr( $ocorrencia->id ); ?>">Registrar repetição</button>
				<span id="sna-gs-counter-display">Total de repetições: <?php echo esc_html( $ocorrencia->contador ?? 0 ); ?></span>
			</div>
		</div>

		<div class="sna-gs-details-descricao">
			<?php echo nl2br( esc_html( $ocorrencia->descricao ) ); ?>
		</div>
	</div>

</div>