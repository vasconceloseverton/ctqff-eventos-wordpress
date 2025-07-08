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


}
