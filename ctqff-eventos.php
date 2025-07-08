<?php
/**
 * Plugin Name: CTQFF Eventos
 * Description: Sistema de inscrição de eventos com categorias, isenções, formulário multi-etapas e pagamentos via WooCommerce.
 * Version: 1.0.0
 * Author: Everton Vasconcelos
 * Author URI: https://github.com/vasconceloseverton/
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Evita acesso direto ao arquivo
}

// Define constantes
define( 'MPE_PATH', plugin_dir_path( __FILE__ ) );
define( 'MPE_URL', plugin_dir_url( __FILE__ ) );

// Inclui arquivos principais
require_once MPE_PATH . 'includes/class-event-manager.php';

// Inicializa o plugin
function mpe_init_plugin() {
    new MPE_Event_Manager();
    new MPE_Inscricao_Handler();
}

add_action( 'plugins_loaded', 'mpe_init_plugin' );

require_once MPE_PATH . 'includes/class-inscricao-handler.php';


register_activation_hook(__FILE__, 'mpe_ativar_plugin');

function mpe_ativar_plugin() {
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();
    $table_name = $wpdb->prefix . 'mpe_inscricoes';

    $sql = "CREATE TABLE $table_name (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        evento_id BIGINT UNSIGNED NOT NULL,
        nome VARCHAR(255),
        email VARCHAR(255),
        cpf VARCHAR(20),
        categoria VARCHAR(255),
        valor DECIMAL(10,2),
        patrocinador_id BIGINT UNSIGNED DEFAULT NULL,
        pedido_id BIGINT UNSIGNED DEFAULT NULL,
        status VARCHAR(20) DEFAULT 'pendente',
        criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// Hook do WooCommerce: pós-pagamento
add_action('woocommerce_thankyou', 'mpe_woocommerce_pedido_concluido', 10, 1);

function mpe_woocommerce_pedido_concluido( $order_id ) {
    $order = wc_get_order($order_id);
    $email = $order->get_billing_email();

    global $wpdb;
    $tabela = $wpdb->prefix . 'mpe_inscricoes';

    // Atualiza status da inscrição com esse email (a mais recente e pendente)
    $wpdb->query(
        $wpdb->prepare("
            UPDATE $tabela 
            SET status = 'confirmado', pedido_id = %d 
            WHERE email = %s AND status = 'pendente'
            ORDER BY id DESC LIMIT 1
        ", $order_id, $email)
    );
}


