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
	</div>

	<?php if ( ! empty( $ocorrencias ) ) : ?>
		<table class="sna-gs-tabela-ocorrencias">
			<thead>
				<tr>
					<th>ID</th>
					<th>Título</th>
					<th>Criado por</th>
					<th>Data de Registro</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $ocorrencias as $ocorrencia ) : ?>
					<tr>
						<td><?php echo esc_html( $ocorrencia->id ); ?></td>
						<td>
							<a href="#" class="sna-gs-view-details-link" data-id="<?php echo esc_attr( $ocorrencia->id ); ?>">
								<?php echo esc_html( $ocorrencia->titulo ); ?>
							</a>
						</td>
						<td><?php echo esc_html( $ocorrencia->display_name ?? 'Usuário não encontrado' ); ?></td>
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
</div>