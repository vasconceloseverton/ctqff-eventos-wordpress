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
}
add_action( 'plugins_loaded', 'mpe_init_plugin' );
