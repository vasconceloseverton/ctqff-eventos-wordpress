<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class MPE_Inscricao_Handler {
    public function __construct() {
        add_shortcode( 'ctqff_inscricao', [ $this, 'render_inscricao_form' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_action('template_redirect', [ $this, 'processar_envio' ]);

    }

    public function enqueue_assets() {
        wp_enqueue_style( 'mpe-styles', MPE_URL . 'assets/css/form.css' );
        wp_enqueue_script( 'mpe-scripts', MPE_URL . 'assets/js/form.js', ['jquery'], null, true );
    }

    public function render_inscricao_form( $atts ) {
        $atts = shortcode_atts( [
            'evento_id' => 0
        ], $atts );

        $evento_id = intval( $atts['evento_id'] );
        if ( get_post_type( $evento_id ) !== 'evento' ) {
            return '<p>Evento inválido.</p>';
        }

        // Carrega categorias do evento
        $categorias = get_post_meta( $evento_id, '_mpe_categorias_participantes', true ) ?: [];

        // Carrega patrocinadores com isenções disponíveis
        $patrocinadores = get_posts([
            'post_type' => 'patrocinador',
            'posts_per_page' => -1
        ]);

        ob_start();
        ?>
        <form id="form-inscricao-ctqff" method="post">
            <input type="hidden" name="evento_id" value="<?php echo esc_attr($evento_id); ?>" />

            <!-- Etapa 1: Categoria -->
            <div class="etapa" data-etapa="1">
                <h3>Escolha a Categoria de Participação</h3>
                <?php foreach ($categorias as $i => $cat): ?>
                    <label style="display:block;margin:10px 0;">
                        <input type="radio" name="categoria" value="<?php echo esc_attr($cat['nome']); ?>" required />
                        <strong><?php echo esc_html($cat['nome']); ?></strong> – R$ <?php echo number_format($cat['valor'], 2, ',', '.'); ?><br/>
                        <em><?php echo esc_html($cat['desc']); ?></em><br/>
                        <small><strong>Regras:</strong> <?php echo esc_html($cat['regras']); ?></small>
                    </label>
                <?php endforeach; ?>
                <button type="button" class="proximo">Próximo</button>
            </div>

            <!-- Etapa 2: Dados Pessoais -->
            <div class="etapa" data-etapa="2" style="display:none;">
                <h3>Seus Dados</h3>
                <label>Nome Completo: <input type="text" name="nome" required /></label><br>
                <label>Email: <input type="email" name="email" required /></label><br>
                <label>CPF: <input type="text" name="cpf" required /></label><br>
                <button type="button" class="voltar">Voltar</button>
                <button type="button" class="proximo">Próximo</button>
            </div>

            <!-- Etapa 3: Isenção e Revisão -->
            <div class="etapa" data-etapa="3" style="display:none;">
                <h3>Deseja usar uma isenção?</h3>
                <select name="patrocinador_id">
                    <option value="">Não usar isenção</option>
                    <?php foreach ($patrocinadores as $pat): 
                        $limite = (int) get_post_meta( $pat->ID, '_mpe_limite_isencoes', true );
                        $usadas = (int) get_post_meta( $pat->ID, '_mpe_isencoes_usadas', true );
                        $disponiveis = max(0, $limite - $usadas);
                        ?>
                        <option value="<?php echo $pat->ID; ?>" <?php disabled($disponiveis === 0); ?>>
                            <?php echo esc_html($pat->post_title); ?>
                            <?php echo $disponiveis === 0 ? ' – ESGOTADAS' : " – {$disponiveis} restantes"; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <br><br>
                <p>Ao clicar em enviar, você será redirecionado para o pagamento.</p>
                <button type="button" class="voltar">Voltar</button>
                <button type="submit">Finalizar Inscrição</button>
            </div>
        </form>
        <?php
        return ob_get_clean();
    }

    public function processar_envio() {
    if ( $_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['evento_id']) ) return;

    $evento_id = intval($_POST['evento_id']);
    $nome = sanitize_text_field($_POST['nome']);
    $email = sanitize_email($_POST['email']);
    $cpf = sanitize_text_field($_POST['cpf']);
    $categoria_nome = sanitize_text_field($_POST['categoria']);
    $patrocinador_id = intval($_POST['patrocinador_id'] ?? 0);

    $categorias = get_post_meta($evento_id, '_mpe_categorias_participantes', true);
    $categoria = array_filter($categorias, fn($c) => $c['nome'] === $categoria_nome);
    $categoria = reset($categoria);
    $valor = floatval($categoria['valor']);

    global $wpdb;
    $tabela = $wpdb->prefix . 'mpe_inscricoes';

    // ISENÇÃO?
    $is_isento = false;
    if ($patrocinador_id) {
        $limite = (int) get_post_meta($patrocinador_id, '_mpe_limite_isencoes', true);
        $usadas = (int) get_post_meta($patrocinador_id, '_mpe_isencoes_usadas', true);

        if ($usadas < $limite) {
            $is_isento = true;
            update_post_meta($patrocinador_id, '_mpe_isencoes_usadas', $usadas + 1);
        }
    }

    // SALVA INSCRIÇÃO
    $wpdb->insert($tabela, [
        'evento_id'       => $evento_id,
        'nome'            => $nome,
        'email'           => $email,
        'cpf'             => $cpf,
        'categoria'       => $categoria_nome,
        'valor'           => $valor,
        'patrocinador_id' => $patrocinador_id ?: null,
        'pedido_id'       => null,
        'status'          => $is_isento ? 'confirmado' : 'pendente',
    ]);

    if ( $is_isento ) {
        wp_redirect( add_query_arg('inscricao', 'ok', get_permalink($evento_id)) );
        exit;
    }

    // Caso contrário, cria produto temporário e redireciona para checkout Woo
    $product = new WC_Product_Simple();
    $product->set_name('Inscrição - ' . $categoria_nome);
    $product->set_regular_price($valor);
    $product->set_virtual(true);
    $product->set_catalog_visibility('hidden');
    $product->save();

    WC()->cart->empty_cart();
    WC()->cart->add_to_cart($product->get_id());

    wp_redirect(wc_get_checkout_url());
    exit;
}

}
