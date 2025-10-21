<?php
/**
 * Funções de interação com o banco de dados.
 *
 * @package GS_Plugin
 * @since   1.0.0
 */

// Se este arquivo for chamado diretamente, aborte.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class GS_Plugin_DB {

	/**
	 * Cria a tabela de ocorrências no banco de dados.
	 *
	 * @since 1.0.0
	 */
	public static function create_table() {
		global $wpdb;
		$table_name      = $wpdb->prefix . 'gs_ocorrencias';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			user_id bigint(20) UNSIGNED NOT NULL,
			user_role varchar(255) NULL,
			titulo text NOT NULL,
			descricao longtext NOT NULL, 
			data_registro datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
			status varchar(50) DEFAULT 'aberto' NOT NULL,
			solucao longtext NULL,
			solucionado_por bigint(20) UNSIGNED NULL,
			data_hora_solucao datetime NULL,
			contador int(11) NOT NULL DEFAULT 0,
			PRIMARY KEY  (id)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

}