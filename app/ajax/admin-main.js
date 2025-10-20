jQuery(document).ready(function ($) {
    const container = $('#sna-gs-view-container');

    // Função para carregar a view via AJAX
    function loadView(viewName, data = {}) {
        container.html('<p>Carregando...</p>'); 

        const ajaxData = Object.assign({
            action: 'gs_load_view', 
            nonce: gs_ajax_object.nonce, 
            view: viewName 
        }, data);

        $.ajax({
            url: ajaxurl, 
            type: 'POST',
            data: ajaxData,
            success: function (response) {
                container.html(response);
            },
            error: function () {
                container.html('<p>Ocorreu um erro ao carregar o conteúdo.</p>');
            }
        });
    }


    $(document).on('click', '#sna-gs-load-form-btn', function (e) {
        e.preventDefault();
        // Altera o título e a descrição para o contexto do formulário
        $('.toplevel_page_gs-ocorrencias .wrap h1.wp-heading-inline').text('Adicionando Nova Ocorrência');
        $('.toplevel_page_gs-ocorrencias .wrap .sna-gs-page-description').html(
            'Preencha os campos abaixo para registrar uma nova ocorrência no sistema.<br>' +
            'Descreva de forma clara o problema, erro ou situação identificada quanto mais detalhes forem informados, mais fácil será acompanhar e resolver.<br>' +
            'Após salvar, a ocorrência ficará disponível na lista principal para consulta e atualização.'
        );
        $(this).fadeOut();
        loadView('form');
    });

    container.on('click', '#sna-gs-load-list-btn', function (e) {
        e.preventDefault();
        // Reverte o título e a descrição para o contexto da lista
        $('.toplevel_page_gs-ocorrencias .wrap h1.wp-heading-inline').text('Gerenciar Ocorrências');
        $('.toplevel_page_gs-ocorrencias .wrap .sna-gs-page-description').html(
            'Esta é uma ferramenta desenvolvida para registrar, e acompanhar problemas. Seu principal objetivo é centralizar as informações e permitir que cada ocorrência seja monitorada desde o momento em que é registrada até sua resolução.'
        );
        $('#sna-gs-load-form-btn').fadeIn();
        loadView('list');
    });

    container.on('click', '.sna-gs-view-details-link', function (e) {
        e.preventDefault();
        const ocorrenciaId = $(this).data('id');
        $('#sna-gs-load-form-btn').fadeOut(); 
        loadView('details', { id: ocorrenciaId });
    });

   
    container.on('click', '#sna-gs-increment-btn', function (e) {
        e.preventDefault();
        const button = $(this);
        const ocorrenciaId = button.data('id');

        button.prop('disabled', true).text('Aguarde...');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'gs_increment_counter',
                nonce: gs_ajax_object.nonce,
                id: ocorrenciaId
            },
            success: function (response) {
                if (response.success) {
                    $('#sna-gs-counter-display').text('Interações: ' + response.data.new_count);
                }
                button.prop('disabled', false).text('Registrar repetição');
            }
        });
    });

    // Função para executar a busca
    function performSearch() {
        const searchTerm = container.find('#sna-gs-search-input').val();
        const listView = container.find('#sna-gs-list-view'); 
        listView.css('opacity', 0.5); 

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'gs_load_view',
                nonce: gs_ajax_object.nonce,
                view: 'list',
                search: searchTerm,
                paged: 1 
            },
            success: function (response) {
              
                container.html(response);
            }
        });
    }

   
    container.on('click', '#sna-gs-search-submit', performSearch); 
    container.on('keypress', '#sna-gs-search-input', function (e) { 
        if (e.which === 13) { 
            e.preventDefault();
            performSearch();
        }
    });


    container.on('click', '#sna-gs-search-clear', function () { 
        loadView('list'); 
    });



container.on('click', '.sna-gs-pagination-arrow:not(:disabled)', function (e) {
    e.preventDefault();
    const pageNum = $(this).data('page'); 
    const searchTerm = container.find('#sna-gs-search-input').val(); 
    const listView = container.find('#sna-gs-list-view'); 

    listView.css('opacity', 0.5); 

    const ajaxData = {
        action: 'gs_load_view',
        nonce: gs_ajax_object.nonce,
        view: 'list',
        paged: pageNum,
        search: searchTerm 
    };

    $.ajax({
        url: ajaxurl,
        type: 'POST',
        data: ajaxData,
        success: function (response) {
        
            container.html(response);
        }
    });
});


container.on('submit', '#sna-gs-form-ocorrencia-submit', function (e) {
    e.preventDefault();

    const form = $(this);
    const submitButton = form.find('button[type="submit"]');
    submitButton.prop('disabled', true).text('Salvando...');

    $.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
            action: 'gs_save_ocorrencia',
            nonce: gs_ajax_object.nonce,
            titulo: form.find('#sna-gs-titulo-ocorrencia').val(),
            descricao: form.find('#sna-gs-descricao-ocorrencia').val()
        },
        success: function (response) {
            if (response.success) {
                $('#sna-gs-load-form-btn').fadeIn();
                loadView('list'); 
            } else {
                alert('Erro: ' + response.data.message);
                submitButton.prop('disabled', false).text('Salvar Ocorrência');
            }
        }
    });
});
});