jQuery(document).ready(function($) {
    $('.proximo').click(function() {
        $(this).closest('.etapa').hide().next().show();
    });
    $('.voltar').click(function() {
        $(this).closest('.etapa').hide().prev().show();
    });
});
