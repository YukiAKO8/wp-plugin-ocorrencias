jQuery(document).ready(function ($) {
    const container = $('#sna-gs-view-container');

    // Armazenamento temporário para os arquivos de imagem selecionados
    let fileStore = [];
    // Armazenamento para os dados iniciais do formulário de edição
    let initialFormData = {};


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
                // Se for o formulário de edição, armazena os dados iniciais
                if (viewName === 'form' && data.id) {
                    initialFormData = {
                        titulo: $('#sna-gs-titulo-ocorrencia').val(),
                        descricao: $('#sna-gs-descricao-ocorrencia').val()
                    };
                }
                // Se a view carregada for a de detalhes, chama a verificação do botão de imagens.
                if (viewName === 'details') {
                    checkAndShowImagesButton();
                }

                // Lógica para mostrar/esconder botões de edição/exclusão na view de detalhes
                if (viewName === 'details') {
                    const creatorUserId = container.find('#sna-gs-edit-occurrence-btn').data('creator-id');
                    const currentUserId = gs_ajax_object.current_user_id;
                    const isAdmin = gs_ajax_object.is_admin;

                    if (parseInt(creatorUserId) === parseInt(currentUserId) || isAdmin) {
                        $('#sna-gs-edit-occurrence-btn, #sna-gs-delete-occurrence-btn').show();
                    } else {
                        $('#sna-gs-edit-occurrence-btn, #sna-gs-delete-occurrence-btn').hide();
                    }
                }
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
        fileStore = []; // Limpa o armazenamento de arquivos ao carregar o formulário
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

    container.on('click', '#sna-gs-edit-occurrence-btn', function (e) {
        e.preventDefault();
        const ocorrenciaId = $(this).data('id');

        $('.toplevel_page_gs-ocorrencias .wrap h1.wp-heading-inline').text('Editando Ocorrência');
        $('.toplevel_page_gs-ocorrencias .wrap .sna-gs-page-description').html(
            'Edite os detalhes da ocorrência existente. Você pode atualizar o título, a descrição e a imagem.<br>' +
            'Apenas o criador da ocorrência ou um administrador pode realizar esta ação.'
        );
        $('#sna-gs-load-form-btn').fadeOut(); // Hide "Nova Ocorrência" button
        fileStore = []; // Limpa o armazenamento de arquivos ao carregar o formulário de edição
        loadView('form', { id: ocorrenciaId }); // Load the form view with occurrence ID
    });

    // Manipulador para o botão de excluir ocorrência
    container.on('click', '#sna-gs-delete-occurrence-btn', function(e) {
        e.preventDefault();

        if (!confirm('Tem certeza de que deseja excluir esta ocorrência? Esta ação é irreversível e removerá também todas as imagens associadas.')) {
            return;
        }

        const button = $(this);
        const ocorrenciaId = button.data('id');

        button.prop('disabled', true).text('Excluindo...');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'gs_delete_ocorrencia',
                nonce: gs_ajax_object.nonce,
                id: ocorrenciaId
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    $('#sna-gs-load-form-btn').fadeIn();
                    loadView('list'); // Volta para a lista após a exclusão
                } else {
                    alert('Erro: ' + response.data.message);
                    button.prop('disabled', false).text('Excluir');
                }
            }
        });
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

    // Captura o clique no botão de submit dentro do formulário para garantir a execução
    container.on('click', '#sna-gs-form-ocorrencia-submit button[type="submit"]', function (e) {
        e.preventDefault();

        const submitButton = $(this);
        const form = submitButton.closest('form');
        submitButton.prop('disabled', true);

        const formData = new FormData();
        formData.append('nonce', gs_ajax_object.nonce);

        const ocorrenciaId = form.find('input[name="ocorrencia_id"]').val();
        const isEditing = !!ocorrenciaId; // Verifica se ocorrencia_id existe

        // Determina a ação com base se é uma edição ou nova ocorrência
        formData.append('action', isEditing ? 'gs_update_ocorrencia' : 'gs_save_ocorrencia');

        if (isEditing) {
            formData.append('ocorrencia_id', ocorrenciaId);
            submitButton.text('Atualizando...');
        } else {
            submitButton.text('Salvando...');
        }

        formData.append('titulo', form.find('input[name="sna-gs-titulo-ocorrencia"]').val());
        formData.append('descricao', form.find('textarea[name="sna-gs-descricao-ocorrencia"]').val());

        // Lida com múltiplos arquivos de imagem usando o arquivo do fileStore
        if (fileStore.length > 0) {
            for (let i = 0; i < fileStore.length; i++) {
                formData.append('imagem_ocorrencia[]', fileStore[i]);
            }
        }

        // Lógica de detecção de mudança forçada
        let hasChanged = false;
        if (isEditing) {
            const currentTitulo = form.find('#sna-gs-titulo-ocorrencia').val();
            const currentDescricao = form.find('#sna-gs-descricao-ocorrencia').val();
            const imagesToRemove = form.find('.sna-gs-remove-image-checkbox:checked').length > 0;
            const newImages = fileStore.length > 0;
            hasChanged = (currentTitulo !== initialFormData.titulo || currentDescricao !== initialFormData.descricao || imagesToRemove || newImages);
        }

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            processData: false, // Importante para FormData
            contentType: false, // Importante para FormData
            success: function (response) {
                // Se o JS detectou uma mudança, mas o backend não, força o sucesso.
                const forceSuccess = isEditing && hasChanged && response.data.message === 'Nenhuma alteração detectada.';

                if (response.success && (response.data.action_taken || forceSuccess)) {
                    alert(response.data.message);
                    $('#sna-gs-load-form-btn').fadeIn();
                    loadView('list');
                } else {
                    alert('Erro: ' + (response.data && response.data.message ? response.data.message : 'Erro desconhecido'));
                    submitButton.prop('disabled', false).text(isEditing ? 'Atualizar Ocorrência' : 'Salvar Ocorrência');
                }
            },
            error: function () {
                alert('Ocorreu um erro ao tentar ' + (isEditing ? 'atualizar' : 'salvar') + ' a ocorrência.');
                submitButton.prop('disabled', false).text(isEditing ? 'Atualizar Ocorrência' : 'Salvar Ocorrência');
            }
        });
    });

    // Função para renderizar os cards de pré-visualização a partir do fileStore
    function renderImagePreviews() {
        const previewContainer = $('#sna-gs-image-preview-container');
        previewContainer.html(''); // Limpa a visualização atual

        if (fileStore.length > 0) {
            previewContainer.append('<h4>Imagens a serem adicionadas:</h4>');
        }

        fileStore.forEach((file, index) => {
            const reader = new FileReader();
            reader.onload = function(e) {
                let fileSize = file.size / 1024; // em KB
                let fileSizeString = fileSize.toFixed(1) + ' KB';
                if (fileSize > 1024) {
                    fileSize = fileSize / 1024; // em MB
                    fileSizeString = fileSize.toFixed(1) + ' MB';
                }

                const cardElement = $(`
                    <div class="sna-gs-preview-card">
                        <span class="file-icon">🖼️</span>
                        <span class="file-name">${file.name} (${fileSizeString})</span>
                        <span class="remove-preview" data-index="${index}">&times;</span>
                        <img class="preview-thumbnail" src="${e.target.result}" alt="Pré-visualização">
                    </div>
                `);
                previewContainer.append(cardElement);
            }
            reader.readAsDataURL(file);
        });
    }

    /**
     * Redimensiona uma imagem no lado do cliente antes do upload.
     */
    function resizeImage(file, maxWidth, maxHeight, quality) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.readAsDataURL(file);
            reader.onerror = reject;
            reader.onload = event => {
                const img = new Image();
                img.src = event.target.result;
                img.onerror = reject;
                img.onload = () => {
                    const canvas = document.createElement('canvas');
                    let { width, height } = img;

                    if (width > height) {
                        if (width > maxWidth) {
                            height = Math.round(height * (maxWidth / width));
                            width = maxWidth;
                        }
                    } else {
                        if (height > maxHeight) {
                            width = Math.round(width * (maxHeight / height));
                            height = maxHeight;
                        }
                    }

                    canvas.width = width;
                    canvas.height = height;
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(img, 0, 0, width, height);

                    canvas.toBlob(blob => {
                        if (blob) {
                            const resizedFile = new File([blob], file.name, {
                                type: 'image/jpeg',
                                lastModified: Date.now(),
                            });
                            resolve(resizedFile);
                        } else {
                            reject(new Error('Falha ao criar o blob da imagem.'));
                        }
                    }, 'image/jpeg', quality);
                };
            };
        });
    }

    // Manipulador para quando novos arquivos são selecionados
    container.on('change', '#sna-gs-imagem-ocorrencia', async function(event) {
        const maxImages = 4;
        const previewContainer = $('#sna-gs-image-preview-container');
        const existingImagesCount = parseInt(previewContainer.data('existing-images') || 0, 10);

        const files = event.target.files;
        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            if (file.type.startsWith('image/')) {
                // Verifica se o limite total (existentes + novas) será excedido
                if ((existingImagesCount + fileStore.length) >= maxImages) {
                    alert(`Você pode adicionar no máximo ${maxImages} imagens por ocorrência.`);
                    break;
                }

                try {
                    const resizedFile = await resizeImage(file, 1280, 1280, 0.85);
                    fileStore.push(resizedFile);
                } catch (error) {
                    console.error('Erro ao redimensionar a imagem:', error);
                    alert('Ocorreu um erro ao processar a imagem: ' + file.name);
                }
            }
        }

        renderImagePreviews();
        $(this).val('');
    });

    // Remover preview temporário
    container.on('click', '.remove-preview', function() {
        const indexToRemove = $(this).data('index');

        if (indexToRemove > -1) {
            fileStore.splice(indexToRemove, 1);
        }

        renderImagePreviews();
    });

    // Manipulador para o novo botão "Excluir Imagens Selecionadas"
    container.on('click', '#sna-gs-delete-selected-images-btn', function(e) {
        e.preventDefault();

        const button = $(this);
        const form = button.closest('form');
        const ocorrenciaId = form.find('input[name="ocorrencia_id"]').val();
        const selectedImages = form.find('.sna-gs-delete-image-checkbox:checked');

        if (selectedImages.length === 0) {
            alert('Por favor, selecione pelo menos uma imagem para excluir.');
            return;
        }

        if (!confirm(`Tem certeza de que deseja excluir permanentemente as ${selectedImages.length} imagem(ns) selecionada(s)?`)) {
            return;
        }

        const imageIds = selectedImages.map(function() {
            return $(this).val();
        }).get();

        button.prop('disabled', true).text('Excluindo...');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'gs_delete_images_ajax',
                nonce: gs_ajax_object.nonce,
                ocorrencia_id: ocorrenciaId,
                image_ids: imageIds
            },
            success: function(response) {
                alert(response.data.message);
                if (response.success) {
                    // Remove as imagens da tela
                    imageIds.forEach(function(id) {
                        $(`.sna-gs-current-image-item[data-image-id="${id}"]`).fadeOut(300, function() { $(this).remove(); });
                    });
                }
            },
            complete: function() {
                button.prop('disabled', false).text('Excluir Imagens Selecionadas');
            }
        });
    });

    function updateSolutionMeta(solucionadoPorName, dataHoraSolucao) {
        const solutionMetaDiv = container.find('.sna-gs-solution-meta');
        if (solucionadoPorName && dataHoraSolucao) {
            const parts = dataHoraSolucao.split(' ');
            solutionMetaDiv.html(`<span>Solucionado por: <strong>${solucionadoPorName}</strong></span><span> | <strong>${parts[0] || ''}</strong></span><span> | <strong>${parts[1] || ''}</strong></span>`).show();
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

                    updateSolutionMeta(response.data.solucionado_por_name || '', response.data.data_hora_solucao || '');
                } else {
                    alert('Erro: ' + response.data.message);
                }
                button.prop('disabled', false).text('Salvar Solução');
            }
        });
    });

    container.on('click', '#sna-gs-edit-solution-btn', function (e) {
        e.preventDefault();
        container.find('#sna-gs-solution-display').hide();
        container.find('#sna-gs-notes-textarea').show();
        $(this).hide();
        container.find('#sna-gs-save-note-btn').text('Atualizar Solução').show();
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
                    container.find('#sna-gs-edit-solution-btn').hide();
                    container.find('.sna-gs-solution-meta').empty().hide();
                    container.find('#sna-gs-save-note-btn').text('Salvar Solução').show();
                } else {
                    alert('Erro: ' + response.data.message);
                }
                button.prop('disabled', false).text('Excluir Solução');
            }
        });
    });

    // Gráficos (se existirem)
    const chartCanvas = document.getElementById('gs-monthly-pie-chart');
    if (chartCanvas && typeof gs_dashboard_data !== 'undefined') {
        const ctx = chartCanvas.getContext('2d');
        new Chart(ctx, {
            type: 'pie',
            data: {
                labels: ['Solucionadas', 'Em Aberto'],
                datasets: [{
                    label: 'Ocorrências no Mês',
                    data: [gs_dashboard_data.pie_solucionadas, gs_dashboard_data.pie_abertas],
                    borderWidth: 2
                }]
            },
            options: {
                maintainAspectRatio: false,
                responsive: true,
                plugins: {
                    legend: { position: 'top' },
                    title: { display: true, text: 'Analise mensal por grafico' }
                }
            }
        });
    }

    const lineChartCanvas = document.getElementById('gs-line-chart');
    if (lineChartCanvas && typeof gs_dashboard_data !== 'undefined') {
        const ctx = lineChartCanvas.getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: gs_dashboard_data.line_labels,
                datasets: [{
                    label: 'Ocorrências por Dia (Últimos 30 dias)',
                    data: gs_dashboard_data.line_data,
                    fill: true,
                    tension: 0.1
                }]
            },
            options: {
                maintainAspectRatio: false,
                responsive: true,
                plugins: {
                    legend: { position: 'top' },
                    title: { display: true, text: 'Ocorrências por Dia' }
                },
                scales: { y: { beginAtZero: true } }
            }
        });
    }

    // 🗑️ NEW: excluir imagem individual via AJAX (chama case 'delete_image' do controller)
    container.on('click', '.sna-gs-delete-image-btn', function (e) {
        e.preventDefault();

        if (!confirm('Tem certeza de que deseja excluir esta imagem?')) {
            return;
        }

        const button = $(this);
        const imageId = button.data('id');

        button.prop('disabled', true).text('Excluindo...');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'gs_load_view',
                view: 'delete_image',
                nonce: gs_ajax_object.nonce,
                image_id: imageId
            },
            success: function (response) {
                if (response.success) {
                    alert('Imagem excluída com sucesso!');
                    $(`.sna-gs-image-chip[data-id="${imageId}"]`).fadeOut(300, function () {
                        $(this).remove();
                    });
                    // caso tenha checkbox/contador, atualize aqui (opcional)
                    $('#sna-gs-image-preview-container').data('existing-images', $('.sna-gs-current-image-item').length);
                } else {
                    alert('Erro: ' + response.data.message);
                    button.prop('disabled', false).text('Excluir');
                }
            },
            error: function () {
                alert('Erro na requisição de exclusão.');
                button.prop('disabled', false).text('Excluir');
            }
        });
    });

    // Lightbox para visualização de imagens na tela de detalhes
    container.on('click', '.sna-gs-gallery-thumbnail', function() {
        const imgSrc = $(this).attr('src');

        const lightboxHTML = `
            <div class="sna-gs-lightbox-overlay">
                <div class="sna-gs-lightbox-content">
                    <span class="sna-gs-lightbox-close">&times;</span>
                    <img src="${imgSrc}" class="sna-gs-lightbox-image" alt="Imagem ampliada">
                </div>
            </div>
        `;

        const $lightbox = $(lightboxHTML);

        $('body').append($lightbox);

        // Fecha o lightbox ao clicar no 'X' ou no fundo
        $lightbox.on('click', function(e) {
            if ($(e.target).is('.sna-gs-lightbox-overlay, .sna-gs-lightbox-close')) {
                $lightbox.remove();
            }
        });
    });

    // Manipulador para o botão "Visualizar Imagens Anexadas"
    container.on('click', '#sna-gs-view-images-btn', function(e) {
        e.preventDefault();
        const button = $(this);
        const ocorrenciaId = button.data('id');
        const galleryContainer = $('#sna-gs-image-gallery-container');

        // Se a galeria já estiver visível, esconde e para.
        if (galleryContainer.is(':visible')) {
            galleryContainer.slideUp();
            return;
        }

        galleryContainer.html('<p>Carregando imagens...</p>').slideDown();

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'gs_load_view',
                view: 'get_images',
                nonce: gs_ajax_object.nonce,
                id: ocorrenciaId
            },
            success: function(response) {
                galleryContainer.html(response);
            },
            error: function() {
                galleryContainer.html('<p>Erro ao carregar as imagens.</p>');
            }
        });
    });

    // Função para verificar se há imagens e mostrar o botão
    function checkAndShowImagesButton() {
        const viewImagesBtn = $('#sna-gs-view-images-btn');
        if (viewImagesBtn.length) {
            const ocorrenciaId = viewImagesBtn.data('id');
            $.ajax({
                url: ajaxurl, type: 'POST',
                data: { action: 'gs_load_view', view: 'count_images', nonce: gs_ajax_object.nonce, id: ocorrenciaId },
                success: function(response) {
                    const count = parseInt(response, 10);
                    if (count > 0) {
                        viewImagesBtn.text(`Visualizar Imagens Anexadas (${count})`).show();
                    }
                }
            });
        }
    }

});
