jQuery(document).ready(function ($) {
    const container = $('#sna-gs-view-container');

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
 
        $('.toplevel_page_gs-ocorrencias .wrap .sna-gs-page-description').html(
            'Aqui você pode visualizar os detalhes de uma ocorrência já registrada.<br>' +
            'Ao final da página, é possível adicionar uma solução, editá-la ou excluí-la, conforme necessário.<br>' +
            'Para retornar à lista de ocorrências, basta clicar no botão laranja “Voltar para a Lista”.'
        );
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

    function updateSolutionMeta(solucionadoPorName, dataHoraSolucao) {
        const solutionMetaDiv = container.find('.sna-gs-solution-meta');
        if (solucionadoPorName && dataHoraSolucao) {
            solutionMetaDiv.html(`<span>Solucionado por: <strong>${solucionadoPorName}</strong></span><span> | <strong>${dataHoraSolucao.split(' ')[0]}</strong></span><span> | <strong>${dataHoraSolucao.split(' ')[1]}</strong></span>`).show();
        } else {
            solutionMetaDiv.empty().hide();
        }
    }

   
    container.on('click', '#sna-gs-save-note-btn', function (e) {
        e.preventDefault();

        const button = $(this);
        const ocorrenciaId = button.data('id');
        const solucaoText = container.find('#sna-gs-notes-textarea').val();

        button.prop('disabled', true).text('Salvando...');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'gs_save_solution',
                nonce: gs_ajax_object.nonce,
                id: ocorrenciaId,
                solucao: solucaoText
            },
            success: function (response) {
                if (response.success) {
                    alert(response.data.message);
                  
                    container.find('#sna-gs-solution-display').html(solucaoText.replace(/\n/g, '<br>')).show();
                    container.find('#sna-gs-notes-textarea').hide();
                    button.hide();
                    container.find('#sna-gs-edit-solution-btn').show();
                    container.find('#sna-gs-delete-note-btn').show();

                 
                } else {
                    alert('Erro: ' + response.data.message); // Exibe um alerta de erro
                }
                button.prop('disabled', false).text('Salvar Solução'); // Reset button text
            }
        });
    });

  
    container.on('click', '#sna-gs-edit-solution-btn', function (e) {
        e.preventDefault();
        container.find('#sna-gs-solution-display').hide();
        container.find('#sna-gs-notes-textarea').show();
        $(this).hide(); // Hide edit button
        container.find('#sna-gs-save-note-btn').text('Atualizar Solução').show(); // Show save button with updated text
    });

  
    container.on('click', '#sna-gs-delete-note-btn', function (e) {
        e.preventDefault();

        if (!confirm('Tem certeza de que deseja excluir esta solução? Esta ação não pode ser desfeita.')) {
            return;
        }

        const button = $(this);
        const ocorrenciaId = button.data('id');

        button.prop('disabled', true).text('Excluindo...');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'gs_delete_solution',
                nonce: gs_ajax_object.nonce,
                id: ocorrenciaId
            },
            success: function (response) {
                if (response.success) {
                    alert(response.data.message);
                   
                    container.find('#sna-gs-solution-display').empty().hide();
                    container.find('#sna-gs-notes-textarea').val('').show();
                    button.hide(); 
                    container.find('#sna-gs-edit-solution-btn').hide(); // Oculta o botão de editar
                    container.find('.sna-gs-solution-meta').empty().hide(); // Limpa e oculta a meta da solução
                    container.find('#sna-gs-save-note-btn').text('Salvar Solução').show(); // Mostra o botão de salvar
                } else {
                    alert('Erro: ' + response.data.message);
                }
                button.prop('disabled', false).text('Excluir Solução');
            }
        });
    });
});