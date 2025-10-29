<?php
/**
 * Template para mostrar uma única ocorrência.
 *
 * @package GS_Plugin
 * @since   1.0.0
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}
?>

<div id="sna-gs-details-view">
	<div class="sna-gs-details-content">
		<div class="sna-gs-details-header">
			<h2 class="sna-gs-details-titulo"><?php echo esc_html( $ocorrencia->titulo ); ?></h2>
		</div>

		<div class="sna-gs-details-meta">
			<div class="sna-gs-meta-info">
				<span>Criado por: <strong><?php echo esc_html( $ocorrencia->display_name ?? 'Usuário não encontrado' ); ?></strong></span>
				<span> | <strong><?php echo esc_html( date( 'd/m/Y', strtotime( $ocorrencia->data_registro ) ) ); ?></strong></span>
				<span> | <strong><?php echo esc_html( date( 'H:i', strtotime( $ocorrencia->data_registro ) ) ); ?></strong></span>
			</div>
			<div class="sna-gs-details-counter">
				<button id="sna-gs-increment-btn" class="button button-increment" data-id="<?php echo esc_attr( $ocorrencia->id ); ?>">Registrar repetição</button>
				<span id="sna-gs-counter-display">Total de repetições: <?php echo esc_html( $ocorrencia->contador ?? 0 ); ?></span>
			</div>
		</div>

		<div class="sna-gs-details-descricao">
			<p><?php echo nl2br( esc_html( $ocorrencia->descricao ) ); ?></p>
		</div>

		<div class="sna-gs-details-imagens-wrapper">
			<div class="sna-gs-details-actions-container">
				<button id="sna-gs-view-images-btn" class="button button-secondary" data-id="<?php echo esc_attr( $ocorrencia->id ); ?>" style="display: none;">Visualizar Imagens Anexadas</button>
				<div class="sna-gs-header-actions">
					<?php
					$current_user_id      = get_current_user_id();
					$can_modify_occurrence = ( (int) $current_user_id === (int) $ocorrencia->user_id ) || current_user_can( 'manage_options' );
					if ( $can_modify_occurrence ) : ?>
						<a href="#" id="sna-gs-edit-occurrence-btn" class="button button-edit" data-id="<?php echo esc_attr( $ocorrencia->id ); ?>">Editar</a>
						<a href="#" id="sna-gs-delete-occurrence-btn" class="button button-delete" data-id="<?php echo esc_attr( $ocorrencia->id ); ?>">Excluir</a>
					<?php endif; ?>
				</div>
			</div>
			<div id="sna-gs-image-gallery-container" style="display: none; margin-top: 15px;"></div>
		</div>

	</div>
</div>

<br><br>

<div class="sna-container-solução">
	<h3>Solução</h3>
	<div class="sna-gs-notes-editor">
		<?php if ( ! empty( $ocorrencia->solucao ) ) : ?>
			<div class="sna-gs-solution-meta">
				<span>Solucionado por: <strong><?php echo esc_html( $ocorrencia->solucionado_por_name ?? 'Usuário não encontrado' ); ?></strong></span>
				<div class="sna-gs-solution-datetime">
					<?php if ( ! empty( $ocorrencia->data_hora_solucao ) ) : ?>
						<span><strong><?php echo esc_html( date( 'd/m/Y', strtotime( $ocorrencia->data_hora_solucao ) ) ); ?></strong></span>
						<span>&nbsp;|&nbsp;<strong><?php echo esc_html( date( 'H:i', strtotime( $ocorrencia->data_hora_solucao ) ) ); ?></strong></span>
					<?php endif; ?>
				</div>
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $ocorrencia->solucao ) ) : ?>
			<div id="sna-gs-solution-display" class="sna-gs-solution-text">
				<?php echo nl2br( esc_html( $ocorrencia->solucao ) ); ?>
			</div>
			<textarea id="sna-gs-notes-textarea" placeholder="Adicione uma Solução..." rows="5" style="display: none;"><?php echo esc_textarea( $ocorrencia->solucao ); ?></textarea>
		<?php else : ?>
			<textarea id="sna-gs-notes-textarea" placeholder="Adicione uma Solução..." rows="5"><?php echo esc_textarea( $ocorrencia->solucao ); ?></textarea>
			<div id="sna-gs-solution-display" class="sna-gs-solution-text" style="display: none;"></div>
		<?php endif; ?>

		<div class="sna-gs-notes-actions">
			<div class="sna-gs-notes-actions-right">
				<button id="sna-gs-delete-note-btn" class="button button-delete" data-id="<?php echo esc_attr( $ocorrencia->id ); ?>" <?php echo empty( $ocorrencia->solucao ) ? 'style="display: none;"' : ''; ?>>Excluir Solução</button>
				<button id="sna-gs-edit-solution-btn" class="button page-title-action" data-id="<?php echo esc_attr( $ocorrencia->id ); ?>" <?php echo empty( $ocorrencia->solucao ) ? 'style="display: none;"' : ''; ?>>Editar Solução</button>
				<button id="sna-gs-save-note-btn" class="button page-title-action" data-id="<?php echo esc_attr( $ocorrencia->id ); ?>" <?php echo ! empty( $ocorrencia->solucao ) ? 'style="display: none;"' : ''; ?>>Salvar Solução</button>
			</div>
		</div>
	</div>
</div>

<br><br><br>
<a href="#" id="sna-gs-load-list-btn" class="button button-secondary">&larr; Voltar para a Lista</a>
