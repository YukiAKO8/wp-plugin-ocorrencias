<?php
/**
 * Template da lista de ocorrências.
 *
 * @package GS_Plugin
 * @since   1.0.0
 *
 * @var array $ocorrencias Os dados das ocorrências vindos do banco.
 */

// Se este arquivo for chamado diretamente, aborte.
if ( ! defined( 'WPINC' ) ) {
	die;
}
?>

<div id="sna-gs-list-view">

	<div class="sna-gs-search-bar">
		<input type="search" id="sna-gs-search-input" placeholder="Buscar ocorrências..." value="<?php echo esc_attr( $search_term ?? '' ); ?>">
		<button id="sna-gs-search-submit" class="button page-title-action">Buscar</button>
		<button id="sna-gs-search-clear" class="button button-clear">Limpar</button>
		<?php
		// Determina a classe e o texto do botão com base no filtro atual
		$is_showing_processos = ( isset( $processos_filter ) && 1 === $processos_filter );
		$button_class         = $is_showing_processos ? 'button-warning' : 'button-success';
		$button_text          = $is_showing_processos ? 'Ver Ocorrências' : 'Ver Processos';
		$data_showing         = $is_showing_processos ? 'processos' : 'ocorrencias';
		?>
		<button id="sna-gs-toggle-processos" class="button <?php echo esc_attr( $button_class ); ?>" data-showing="<?php echo esc_attr( $data_showing ); ?>"><?php echo esc_html( $button_text ); ?></button>
	</div>

	<?php if ( ! empty( $ocorrencias ) ) : ?>
		<table class="sna-gs-tabela-ocorrencias">
			<thead>
				<tr>
					<th>Título</th>
					<th>Criado por</th>
					<th>Status</th>
					<th>Data de Registro</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $ocorrencias as $ocorrencia ) : ?>
					<tr 
						class="gs-table-row"
						data-type="<?php echo esc_attr( $ocorrencia->processos ); ?>"
					>
						<td>
							<a href="#" class="sna-gs-view-details-link" data-id="<?php echo esc_attr( $ocorrencia->id ); ?>">
								<?php echo esc_html( $ocorrencia->titulo ); ?>
							</a>
						</td>
						<td><?php echo esc_html( $ocorrencia->display_name ?? 'Usuário não encontrado' ); ?></td>
						<td>
							<?php if ( 'aberto' === $ocorrencia->status ) : ?>
								<span class="status-indicator status-aberto" title="Status: Aberto"></span>
							<?php elseif ( 'solucionada' === $ocorrencia->status ) : ?>
								<span class="status-indicator status-solucionada" title="Status: Solucionada"></span>
							<?php else : ?>
								<?php echo esc_html( ucfirst( $ocorrencia->status ) ); ?>
							<?php endif; ?>
						</td>
						<td><?php echo esc_html( date( 'd/m/Y H:i', strtotime( $ocorrencia->data_registro ) ) ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<?php if ( $total_pages > 1 ) : ?>
			<div class="sna-gs-pagination">
				<button class="sna-gs-pagination-arrow prev" data-page="<?php echo $current_page - 1; ?>" <?php disabled( $current_page, 1 ); ?>>&lt;</button>
				<button class="sna-gs-pagination-current" disabled><?php echo esc_html( $current_page ); ?></button>
				<button class="sna-gs-pagination-arrow next" data-page="<?php echo $current_page + 1; ?>" <?php disabled( $current_page, $total_pages ); ?>>&gt;</button>
			</div>
		<?php endif; ?>
	<?php else : ?>
		<p>Nenhuma ocorrência registrada ainda.</p>
	<?php endif; ?>

	<a href="#" id="sna-gs-open-manual-modal" class="sna-gs-help-btn" title="Manual do sistema">
		?
	</a>

	<!-- Modal do Manual -->
	<div id="sna-gs-manual-modal" class="sna-gs-modal-overlay" style="display: none;">
		<div class="sna-gs-modal-content">
			<span class="sna-gs-modal-close">&times;</span>
			<iframe src="<?php echo esc_url( GS_PLUGIN_URL . 'app/assets/views/ManualOcorrencias.pdf' ); ?>" width="100%" height="100%" style="border: none;"></iframe>
		</div>
	</div>
</div>