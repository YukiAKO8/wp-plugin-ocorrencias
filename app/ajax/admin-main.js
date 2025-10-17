jQuery(document).ready(function ($) {
    const container = $('#sna-gs-view-container');

    // Função para carregar a view via AJAX
    function loadView(viewName, data = {}) {
        container.html('<p>Carregando...</p>'); // Mostra um feedback de carregamento

        const ajaxData = Object.assign({
            action: 'gs_load_view', // Ação registrada no PHP
            nonce: gs_ajax_object.nonce, // Nonce de segurança
            view: viewName // 'list', 'form', ou 'details'
        }, data);

        $.ajax({
            url: ajaxurl, // URL global do WordPress para AJAX
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

    // Delegação de eventos para os botões que podem ser carregados dinamicamente
    // O botão flutuante agora está fora do container, então o listener é no document.
    $(document).on('click', '#sna-gs-load-form-btn', function (e) {
        e.preventDefault();
        // Esconde o botão flutuante ao ir para o formulário
        $(this).fadeOut();
        loadView('form');
    });

    container.on('click', '#sna-gs-load-list-btn', function (e) {
        e.preventDefault();
        // Mostra o botão flutuante ao voltar para a lista
        $('#sna-gs-load-form-btn').fadeIn();
        loadView('list');
    });

    // Listener para o link de detalhes da ocorrência
    container.on('click', '.sna-gs-view-details-link', function (e) {
        e.preventDefault();
        const ocorrenciaId = $(this).data('id');
        $('#sna-gs-load-form-btn').fadeOut(); // Esconde o FAB na tela de detalhes
        loadView('details', { id: ocorrenciaId });
    });

    // Função para executar a busca
    function performSearch() {
        const searchTerm = container.find('#sna-gs-search-input').val();
        const listView = container.find('#sna-gs-list-view'); // O container da lista
        listView.css('opacity', 0.5); // Efeito de carregamento no container inteiro

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'gs_load_view',
                nonce: gs_ajax_object.nonce,
                view: 'list',
                search: searchTerm,
                paged: 1 // Sempre volta para a primeira página ao fazer uma nova busca
            },
            success: function (response) {
                // Substitui todo o conteúdo da lista, incluindo a barra de busca e paginação.
                container.html(response);
            }
        });
    }

    // Listener para o botão de busca e tecla Enter
    container.on('click', '#sna-gs-search-submit', performSearch); // Delegação de evento
    container.on('keypress', '#sna-gs-search-input', function (e) { // Delegação de evento
        if (e.which === 13) { // 13 é o código da tecla Enter
            e.preventDefault();
            performSearch();
        }
    });

    // Listener para o botão de limpar
    container.on('click', '#sna-gs-search-clear', function () { // Delegação de evento
        loadView('list'); // Simplesmente recarrega a lista sem filtros.
    });


// Listener para os links de paginação
container.on('click', '.sna-gs-pagination-arrow:not(:disabled)', function (e) {
    e.preventDefault();
    const pageNum = $(this).data('page'); // Pega o número da página
    const searchTerm = container.find('#sna-gs-search-input').val(); // Pega o termo de busca atual
    const listView = container.find('#sna-gs-list-view'); // O container da lista

    listView.css('opacity', 0.5); // Efeito de carregamento

    const ajaxData = {
        action: 'gs_load_view',
        nonce: gs_ajax_object.nonce,
        view: 'list',
        paged: pageNum,
        search: searchTerm // Envia o termo de busca junto com a paginação
    };

    $.ajax({
        url: ajaxurl,
        type: 'POST',
        data: ajaxData,
        success: function (response) {
            // Substitui todo o conteúdo da lista, mantendo a busca e a página atual.
            container.html(response);
        }
    });
});

// Delegação de evento para o envio do formulário
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
                loadView('list'); // Se salvou com sucesso, carrega a lista
            } else {
                alert('Erro: ' + response.data.message);
                submitButton.prop('disabled', false).text('Salvar Ocorrência');
            }
        }
    });
});
});