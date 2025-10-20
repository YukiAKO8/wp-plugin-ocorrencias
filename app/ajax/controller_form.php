<?php
if ( ! defined( 'WPINC' ) ) {
    die;
}

class GS_Ajax_Controller {

    public static function load_view() {
        check_ajax_referer( 'gs_ajax_nonce', 'nonce' );

        global $wpdb;

        $view = isset( $_POST['view'] ) ? sanitize_key( $_POST['view'] ) : 'list';

        if ( 'form' === $view ) {
            require_once GS_PLUGIN_PATH . 'app/assets/views/formulario-gestao.php';

        } elseif ( 'details' === $view && isset( $_POST['id'] ) ) {

            $id          = absint( $_POST['id'] );
            $table_name  = $wpdb->prefix . 'gs_ocorrencias';

            $ocorrencia  = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT o.*, u.display_name, o.contador
                     FROM {$table_name} o
                     LEFT JOIN {$wpdb->users} u ON o.user_id = u.ID 
                     WHERE o.id = %d",
                    $id
                )
            );

            if ( $ocorrencia ) {
                require_once GS_PLUGIN_PATH . 'app/assets/views/mostrar-ocorrencia.php';
            }

        } else {
            $table_name     = $wpdb->prefix . 'gs_ocorrencias';
            $items_per_page = 8;
            $current_page   = isset( $_POST['paged'] ) ? absint( $_POST['paged'] ) : 1;
            $offset         = ( $current_page - 1 ) * $items_per_page;

            // Lógica de busca
            $search_term  = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';
            $where_clause = '';
            $prepare_args = array();

            if ( ! empty( $search_term ) ) {
                $like_term      = '%' . $wpdb->esc_like( $search_term ) . '%';
                $where_clause   = ' WHERE (o.titulo LIKE %s OR o.descricao LIKE %s)';
                $prepare_args[] = $like_term;
                $prepare_args[] = $like_term;
            }

            $total_items_query = "SELECT COUNT(o.id) FROM {$table_name} o" . $where_clause;
            $total_items       = (int) $wpdb->get_var( $wpdb->prepare( $total_items_query, $prepare_args ) );
            $total_pages = ceil( $total_items / $items_per_page );

            $prepare_args[] = $items_per_page;
            $prepare_args[] = $offset;

            $query = "SELECT o.*, u.display_name, o.contador FROM {$table_name} o 
                      LEFT JOIN {$wpdb->users} u ON o.user_id = u.ID" . 
                      $where_clause . ' ORDER BY o.data_registro DESC LIMIT %d OFFSET %d';

            $ocorrencias = $wpdb->get_results( $wpdb->prepare( $query, $prepare_args ) );

            require GS_PLUGIN_PATH . 'app/assets/views/lista-ocorrencias.php';
        }

        wp_die();
    }
}

// =======================
// AÇÃO PARA INCREMENTAR CONTADOR
// =======================
add_action('wp_ajax_gs_increment_counter', 'gs_increment_counter');

function gs_increment_counter() {
    check_ajax_referer('gs_ajax_nonce', 'nonce');

    if ( ! isset($_POST['id']) ) {
        wp_send_json_error(['message' => 'ID inválido.']);
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'gs_ocorrencias';
    $id = absint($_POST['id']);


    $exists = $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*) FROM {$table_name} WHERE id = %d",
        $id
    ) );

    if ( ! $exists ) {
        wp_send_json_error(['message' => 'Ocorrência não encontrada.']);
    }

  
    $wpdb->query( $wpdb->prepare(
        "UPDATE {$table_name} SET contador = COALESCE(contador, 0) + 1 WHERE id = %d",
        $id
    ) );


    $new_count = $wpdb->get_var( $wpdb->prepare(
        "SELECT contador FROM {$table_name} WHERE id = %d",
        $id
    ) );

    wp_send_json_success(['new_count' => $new_count]);
}
