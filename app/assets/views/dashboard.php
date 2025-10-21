<?php
/**
 * Template do Dashboard de Ocorrências.
 *
 * @package GS_Plugin
 * @since   1.0.0
 *
 * @var int $ocorrencias_mes_total      Total de ocorrências no mês atual.
 * @var int $ocorrencias_abertas_total  Total de ocorrências com status 'aberto'.
 * @var int $ocorrencias_solucionadas_total Total de ocorrências com status 'solucionada'.
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}
?>

<div class="gs-dashboard-wrapper">
	<div class="sna-gs-header-title-wrapper">
		<img src="<?php echo esc_url( GS_PLUGIN_URL . 'app/assets/views/logoDash.png' ); ?>" alt="Logo Dashboard" class="sna-gs-dashboard-logo">
		<h1 class="wp-heading-inline gs-dashboard-title">Dashboard de Ocorrências</h1>
	</div>
	<p class="sna-gs-page-description">Este painel apresenta um resumo completo das ocorrências registradas no sistema. Aqui é possível acompanhar o volume total de registros, o status de cada ocorrência, identificar tendências e avaliar o desempenho das ações corretivas ao longo do tempo.</p>

	<div class="gs-dashboard-container">
		<div class="gs-dashboard-card status-mes">
			<h3 class="gs-dashboard-card-title">Ocorrências no Mês</h3>
			<p class="gs-dashboard-card-metric"><?php echo esc_html( $ocorrencias_mes_total ); ?></p>
		</div>

		<div class="gs-dashboard-card status-aberto">
			<h3 class="gs-dashboard-card-title">Total em Aberto</h3>
			<p class="gs-dashboard-card-metric"><?php echo esc_html( $ocorrencias_abertas_total ); ?></p>
		</div>

		<div class="gs-dashboard-card status-solucionada">
			<h3 class="gs-dashboard-card-title">Total Solucionadas</h3>
			<p class="gs-dashboard-card-metric"><?php echo esc_html( $ocorrencias_solucionadas_total ); ?></p>
		</div>
	</div>

	<div class="gs-charts-wrapper">
		<div class="gs-chart-container">
			<canvas id="gs-monthly-pie-chart"></canvas>
		</div>
		<div class="gs-chart-container">
			<canvas id="gs-line-chart"></canvas>
		</div>
	</div>
</div>