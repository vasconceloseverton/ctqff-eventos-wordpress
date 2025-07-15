<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class MPE_Event_Manager {
    public function __construct() {
        add_action( 'init', [ $this, 'register_event_post_type' ] );
        add_action( 'add_meta_boxes', [ $this, 'register_meta_boxes' ] );
        add_action( 'save_post_evento', [ $this, 'save_categorias_participantes' ] );
        add_action( 'init', [ $this, 'register_patrocinador_post_type' ] );
        add_action( 'add_meta_boxes', [ $this, 'register_patrocinador_meta_boxes' ] );
        add_action( 'save_post_patrocinador', [ $this, 'save_patrocinador_meta' ] );
        add_action( 'admin_menu', [ $this, 'register_admin_menu' ] );

    }

    public function register_admin_menu() {
    add_submenu_page(
        'edit.php?post_type=evento',
        'Inscrições',
        'Inscrições',
        'manage_options',
        'inscricoes',
        [ $this, 'render_inscricoes_page' ]
    );
    }

    public function register_event_post_type() {
        $labels = [
            'name' => 'Eventos',
            'singular_name' => 'Evento',
            'menu_name' => 'Eventos',
            'name_admin_bar' => 'Evento',
            'add_new' => 'Adicionar Novo',
            'add_new_item' => 'Adicionar Novo Evento',
            'new_item' => 'Novo Evento',
            'edit_item' => 'Editar Evento',
            'view_item' => 'Ver Evento',
            'all_items' => 'Todos os Eventos',
            'search_items' => 'Buscar Eventos',
            'not_found' => 'Nenhum evento encontrado.',
        ];

        $args = [
            'labels' => $labels,
            'public' => true,
            'show_in_menu' => true,
            'menu_icon' => 'dashicons-calendar-alt',
            'supports' => [ 'title', 'editor', 'thumbnail' ],
            'has_archive' => true,
            'rewrite' => [ 'slug' => 'eventos' ],
            'show_in_rest' => true,
        ];

        register_post_type( 'evento', $args );
    }

    public function register_patrocinador_post_type() {
    $labels = [
        'name' => 'Patrocinadores',
        'singular_name' => 'Patrocinador',
        'menu_name' => 'Patrocinadores',
        'name_admin_bar' => 'Patrocinador',
        'add_new' => 'Adicionar Novo',
        'add_new_item' => 'Adicionar Patrocinador',
        'new_item' => 'Novo Patrocinador',
        'edit_item' => 'Editar Patrocinador',
        'view_item' => 'Ver Patrocinador',
        'all_items' => 'Todos os Patrocinadores',
        'search_items' => 'Buscar Patrocinador',
        'not_found' => 'Nenhum patrocinador encontrado.',
    ];

    $args = [
        'labels' => $labels,
        'public' => true,
        'show_in_menu' => true,
        'menu_icon' => 'dashicons-groups',
        'supports' => [ 'title' ],
        'has_archive' => false,
        'rewrite' => false,
        'show_in_rest' => true,
    ];

    register_post_type( 'patrocinador', $args );
    }

    public function register_patrocinador_meta_boxes() {
    add_meta_box(
        'mpe_patrocinador_info',
        'Detalhes do Patrocinador',
        [ $this, 'render_patrocinador_metabox' ],
        'patrocinador',
        'normal',
        'high'
    );
    }

    public function register_meta_boxes() {
    add_meta_box(
        'mpe_categorias_participantes',
        'Categorias de Participantes',
        [ $this, 'render_categorias_metabox' ],
        'evento',
        'normal',
        'high'
    );
    }

    public function render_categorias_metabox( $post ) {
        wp_nonce_field( 'mpe_categorias_nonce_action', 'mpe_categorias_nonce' );
        $categorias = get_post_meta( $post->ID, '_mpe_categorias_participantes', true ) ?: [];

        ?>
        <div id="categorias-wrapper">
            <?php foreach ( $categorias as $i => $cat ) : ?>
                <div class="categoria-box" style="border:1px solid #ccc; padding:10px; margin-bottom:10px;">
                <label>Nome: <input type="text" name="mpe_categoria_nome[]" value="<?php echo esc_attr($cat['nome']); ?>" /></label><br>
                <label>Descrição: <textarea name="mpe_categoria_desc[]"><?php echo esc_textarea($cat['desc']); ?></textarea></label><br>
                <label>Regras: <textarea name="mpe_categoria_regras[]"><?php echo esc_textarea($cat['regras']); ?></textarea></label><br>
                <label>Valor (R$): <input type="number" step="0.01" name="mpe_categoria_valor[]" value="<?php echo esc_attr($cat['valor']); ?>" /></label><br>
                <label>Limite de Vagas: <input type="number" name="mpe_categoria_limite[]" value="<?php echo esc_attr($cat['limite']); ?>" /></label>
            </div>
        <?php endforeach; ?>
    </div>
    <button type="button" id="add-categoria">+ Adicionar Categoria</button>

    <script>
    document.getElementById('add-categoria').addEventListener('click', function () {
        const wrapper = document.getElementById('categorias-wrapper');
        const html = `
            <div class="categoria-box" style="border:1px solid #ccc; padding:10px; margin-bottom:10px;">
                <label>Nome: <input type="text" name="mpe_categoria_nome[]" /></label><br>
                <label>Descrição: <textarea name="mpe_categoria_desc[]"></textarea></label><br>
                <label>Regras: <textarea name="mpe_categoria_regras[]"></textarea></label><br>
                <label>Valor (R$): <input type="number" step="0.01" name="mpe_categoria_valor[]" /></label><br>
                <label>Limite de Vagas: <input type="number" name="mpe_categoria_limite[]" /></label>
            </div>
        `;
        wrapper.insertAdjacentHTML('beforeend', html);
    });
    </script>
    <?php
    }
    public function save_categorias_participantes( $post_id ) {
    if ( ! isset( $_POST['mpe_categorias_nonce'] ) || ! wp_verify_nonce( $_POST['mpe_categorias_nonce'], 'mpe_categorias_nonce_action' ) ) {
        return;
    }

    $nomes   = $_POST['mpe_categoria_nome'] ?? [];
    $descr   = $_POST['mpe_categoria_desc'] ?? [];
    $regras  = $_POST['mpe_categoria_regras'] ?? [];
    $valores = $_POST['mpe_categoria_valor'] ?? [];
    $limites = $_POST['mpe_categoria_limite'] ?? [];

    $categorias = [];

    for ( $i = 0; $i < count( $nomes ); $i++ ) {
        if ( empty( $nomes[$i] ) ) continue;

        $categorias[] = [
            'nome'   => sanitize_text_field( $nomes[$i] ),
            'desc'   => sanitize_textarea_field( $descr[$i] ),
            'regras' => sanitize_textarea_field( $regras[$i] ),
            'valor'  => floatval( $valores[$i] ),
            'limite' => intval( $limites[$i] ),
        ];
    }

    update_post_meta( $post_id, '_mpe_categorias_participantes', $categorias );
}

public function render_patrocinador_metabox( $post ) {
    wp_nonce_field( 'mpe_patrocinador_nonce_action', 'mpe_patrocinador_nonce' );

    $plano = get_post_meta( $post->ID, '_mpe_plano', true );
    $limite = get_post_meta( $post->ID, '_mpe_limite_isencoes', true );

    ?>
    <p>
        <label>Plano:
            <select name="mpe_plano">
                <option value="ouro" <?php selected($plano, 'ouro'); ?>>Ouro</option>
                <option value="prata" <?php selected($plano, 'prata'); ?>>Prata</option>
                <option value="bronze" <?php selected($plano, 'bronze'); ?>>Bronze</option>
            </select>
        </label>
    </p>
    <p>
        <label>Quantidade de Isenções Contratuais:
            <input type="number" name="mpe_limite_isencoes" value="<?php echo esc_attr($limite ?: 0); ?>" min="0" />
        </label>
    </p>
    <?php
}

public function save_patrocinador_meta( $post_id ) {
    if ( ! isset( $_POST['mpe_patrocinador_nonce'] ) || ! wp_verify_nonce( $_POST['mpe_patrocinador_nonce'], 'mpe_patrocinador_nonce_action' ) ) {
        return;
    }

    update_post_meta( $post_id, '_mpe_plano', sanitize_text_field( $_POST['mpe_plano'] ) );
    update_post_meta( $post_id, '_mpe_limite_isencoes', intval( $_POST['mpe_limite_isencoes'] ) );

    // Se ainda não existir, inicializa o contador de uso com 0
    if ( get_post_meta( $post_id, '_mpe_isencoes_usadas', true ) === '' ) {
        update_post_meta( $post_id, '_mpe_isencoes_usadas', 0 );
    }
}

public function render_inscricoes_page() {

    if (isset($_GET['export']) && $_GET['export'] == 1) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="inscricoes.csv"');
    $fh = fopen('php://output', 'w');
    fputcsv($fh, ['Evento', 'Nome', 'Email', 'CPF', 'Categoria', 'Status', 'Valor', 'Patrocinador', 'Pedido ID', 'Data']);

    foreach ($inscricoes as $i) {
        fputcsv($fh, [
            get_the_title($i->evento_id),
            $i->nome,
            $i->email,
            $i->cpf,
            $i->categoria,
            $i->status,
            $i->valor,
            $i->patrocinador_id ? get_the_title($i->patrocinador_id) : '',
            $i->pedido_id ?: '',
            $i->criado_em
        ]);
    }

    exit;
    }
    
    global $wpdb;
    $tabela = $wpdb->prefix . 'mpe_inscricoes';

    // Filtros
    $eventos = get_posts([ 'post_type' => 'evento', 'numberposts' => -1 ]);
    $patrocinadores = get_posts([ 'post_type' => 'patrocinador', 'numberposts' => -1 ]);
    
    $evento_id = intval($_GET['evento_id'] ?? 0);
    $categoria = sanitize_text_field($_GET['categoria'] ?? '');
    $status = sanitize_text_field($_GET['status'] ?? '');
    $patrocinador_id = intval($_GET['patrocinador_id'] ?? 0);

    // Monta query SQL
    $where = "WHERE 1=1";
    if ($evento_id) $where .= $wpdb->prepare(" AND evento_id = %d", $evento_id);
    if ($categoria) $where .= $wpdb->prepare(" AND categoria = %s", $categoria);
    if ($status) $where .= $wpdb->prepare(" AND status = %s", $status);
    if ($patrocinador_id) $where .= $wpdb->prepare(" AND patrocinador_id = %d", $patrocinador_id);

    $inscricoes = $wpdb->get_results("SELECT * FROM $tabela $where ORDER BY criado_em DESC");

    ?>
    <div class="wrap">
        <h1>Inscrições</h1>

        <form method="get" style="margin-bottom:20px;">
            <input type="hidden" name="post_type" value="evento">
            <input type="hidden" name="page" value="inscricoes">

            <label>Evento:
                <select name="evento_id">
                    <option value="">Todos</option>
                    <?php foreach ($eventos as $e): ?>
                        <option value="<?php echo $e->ID; ?>" <?php selected($e->ID, $evento_id); ?>><?php echo esc_html($e->post_title); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label>Status:
                <select name="status">
                    <option value="">Todos</option>
                    <option value="pendente" <?php selected('pendente', $status); ?>>Pendente</option>
                    <option value="confirmado" <?php selected('confirmado', $status); ?>>Confirmado</option>
                </select>
            </label>

            <label>Categoria:
                <input type="text" name="categoria" value="<?php echo esc_attr($categoria); ?>" />
            </label>

            <label>Patrocinador:
                <select name="patrocinador_id">
                    <option value="">Todos</option>
                    <?php foreach ($patrocinadores as $p): ?>
                        <option value="<?php echo $p->ID; ?>" <?php selected($p->ID, $patrocinador_id); ?>><?php echo esc_html($p->post_title); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <button type="submit" class="button button-primary">Filtrar</button>
            <a href="<?php echo admin_url('edit.php?post_type=evento&page=inscricoes&export=1'); ?>" class="button">Exportar CSV</a>
        </form>

        <table class="widefat striped">
            <thead>
                <tr>
                    <th>Evento</th>
                    <th>Nome</th>
                    <th>Email</th>
                    <th>CPF</th>
                    <th>Categoria</th>
                    <th>Status</th>
                    <th>Valor</th>
                    <th>Patrocinador</th>
                    <th>Pedido Woo</th>
                    <th>Data</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($inscricoes as $i): ?>
                    <tr>
                        <td><?php echo get_the_title($i->evento_id); ?></td>
                        <td><?php echo esc_html($i->nome); ?></td>
                        <td><?php echo esc_html($i->email); ?></td>
                        <td><?php echo esc_html($i->cpf); ?></td>
                        <td><?php echo esc_html($i->categoria); ?></td>
                        <td><?php echo esc_html($i->status); ?></td>
                        <td>R$ <?php echo number_format($i->valor, 2, ',', '.'); ?></td>
                        <td><?php echo $i->patrocinador_id ? get_the_title($i->patrocinador_id) : '-'; ?></td>
                        <td><?php echo $i->pedido_id ? "<a href='".admin_url("post.php?post={$i->pedido_id}&action=edit")."'>#{$i->pedido_id}</a>" : '-'; ?></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($i->criado_em)); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}



}
