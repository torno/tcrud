var i18n = {};
var i18nUrl = '';
$(document).ready(function() {
    var crudTable = $('#crudTable');
    i18n = crudTable.data('i18n') || {};
    i18nUrl = crudTable.data('i18n-url') || '';

    // Inicializar DataTables
    if ($('#crudTable').length) {
        var table = $('#crudTable');
        var columnsConfig = table.data('columns');
        var savedState = table.data('state') || {};
        
        var pageLength = savedState.length || 15;
        var startIndex = savedState.start || 0;

        table.DataTable({
            processing: true,
            serverSide: true,
            orderCellsTop: true,
            ajax: {
                url: window.location.href,
                type: 'GET',
                data: function(d) {
                    d.tcrud_ajax = 1;
                },
                error: function(xhr, error, thrown) {
                    console.error("DataTables Error:", xhr.responseText);
                    alert(i18n.error_request || 'Error');
                }
            },
            columns: columnsConfig,
            language: {
                url: i18nUrl
            },
            pageLength: pageLength,
            lengthMenu: [10, 15, 25, 50, 100],
            displayStart: startIndex,
            order: [[savedState.order_column || 0, savedState.order_dir || 'desc']],
            search: {
                search: savedState.search || ''
            },
            initComplete: function() {
                $('.tcrud-filter-row').show();
            }
        });
    }

    // Confirmación para ELIMINAR con SweetAlert2
    $(document).on('click', 'a[href*="tcdelete"]', function(e) {
        e.preventDefault();
        var url = $(this).attr('href');
        
        Swal.fire({
            title: i18n.confirm_delete || 'Are you sure?',
            text: i18n.confirm_delete_text || '',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: i18n.confirm_delete_yes || 'Yes',
            cancelButtonText: i18n.cancel || 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = url;
            }
        });
    });

    // Confirmación para CLONAR con SweetAlert2
    $(document).on('click', 'a[href*="tcclone"]', function(e) {
        e.preventDefault();
        var url = $(this).attr('href');
        
        Swal.fire({
            title: i18n.confirm_clone || 'Clone',
            text: i18n.confirm_clone_text || '',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: i18n.confirm_clone_yes || 'Yes',
            cancelButtonText: i18n.cancel || 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = url;
            }
        });
    });

    // Mostrar mensajes de éxito/error con SweetAlert2
    var successMessage = $('.alert-success').text();
    var errorMessage = $('.alert-danger').text();
    
    if (successMessage && successMessage.trim() !== '') {
        Swal.fire({
            icon: 'success',
            title: i18n.success_save || 'Success',
            text: successMessage.trim(),
            timer: 3000,
            showConfirmButton: false
        });
        $('.alert-success').hide();
    }
    
    if (errorMessage && errorMessage.trim() !== '') {
        Swal.fire({
            icon: 'error',
            title: i18n.error_title || 'Error',
            text: errorMessage.trim()
        });
        $('.alert-danger').hide();
    }

    // Inicializar Quill en todos los editores wysiwyg
    if (typeof Quill !== 'undefined') {
        $('.wysiwyg-editor').each(function() {
            var editorId = $(this).attr('id');
            var fieldName = editorId.replace('editor_', '');
            var hiddenInput = $('#input_' + fieldName);
            
            var quill = new Quill('#' + editorId, {
                theme: 'snow',
                modules: {
                    toolbar: [
                        [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
                        [{ 'font': [] }],
                        [{ 'size': ['small', false, 'large', 'huge'] }],
                        ['bold', 'italic', 'underline', 'strike'],
                        [{ 'color': [] }, { 'background': [] }],
                        [{ 'script': 'sub' }, { 'script': 'super' }],
                        [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                        [{ 'indent': '-1' }, { 'indent': '+1' }],
                        [{ 'align': [] }],
                        ['blockquote', 'code-block'],
                        ['link', 'image'],
                        ['clean']
                    ]
                }
            });
            
            quill.on('text-change', function() {
                hiddenInput.val(quill.root.innerHTML);
            });
            
            $(this).closest('form').on('submit', function() {
                hiddenInput.val(quill.root.innerHTML);
            });
        });
    }

    // Inicializar TomSelect en multiselects
    if (typeof TomSelect !== 'undefined') {
        $('.crud-multiselect').each(function() {
            new TomSelect(this, {
                plugins: ['remove_button'],
                maxItems: null,
                placeholder: i18n.select_option || 'Select...'
            });
        });
    }
});

// Bulk Delete - Mostrar/ocultar botón según selección
$(document).on('change', '.row-checkbox', function() {
    var checked = $('.row-checkbox:checked').length;
    if (checked > 0) {
        $('#bulkDeleteBtn').show();
        $('#selectedCount').text(checked);
    } else {
        $('#bulkDeleteBtn').hide();
        $('#selectedCount').text(0);
    }
});

// Select All
$(document).on('change', '#selectAll', function() {
    var isChecked = $(this).prop('checked');
    $('.row-checkbox').prop('checked', isChecked);
    var checked = $('.row-checkbox:checked').length;
    if (checked > 0) {
        $('#bulkDeleteBtn').show();
        $('#selectedCount').text(checked);
    } else {
        $('#bulkDeleteBtn').hide();
        $('#selectedCount').text(0);
    }
});

// Bulk Delete action
$(document).on('click', '#bulkDeleteBtn', function() {
    var crudTable = $('#crudTable');
    var i18n = crudTable.data('i18n') || {};
    
    var ids = [];
    $('.row-checkbox:checked').each(function() {
        ids.push($(this).val());
    });
    
    if (ids.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: i18n.error_no_selection_title || 'No selection',
            text: i18n.error_no_selection || ''
        });
        return;
    }
    
    Swal.fire({
        title: i18n.confirm_bulk_delete || 'Delete?',
        text: i18n.confirm_bulk_delete_text || '',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: i18n.confirm_bulk_delete_yes || 'Yes',
        cancelButtonText: i18n.cancel || 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: window.location.pathname + '/tcbulkdelete',
                type: 'POST',
                data: { ids: ids },
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: i18n.deleted_title || 'Deleted',
                            text: response.message,
                            timer: 2000,
                            showConfirmButton: false
                        });
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: i18n.error_title || 'Error',
                            text: response.message || ''
                        });
                    }
                },
                error: function(xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: i18n.error_title || 'Error',
                        text: i18n.error_request || ''
                    });
                }
            });
        }
    });
});

// Abrir modal al hacer clic en thumbnail
$(document).on('click', '.image-thumbnail', function(e) {
    e.preventDefault();
    var src = $(this).data('full');
    $('#modalImage').attr('src', src);
    $('#imageModal').modal('show');
});

// Tooltips para iconos de archivos
$(document).on('mouseenter', '.file-icon', function() {
    var filename = $(this).data('filename');
    if (filename) {
        $(this).attr('title', filename);
    }
});

// ============================================================
// FILTROS DE BÚSQUEDA CON DEBOUNCE
// ============================================================

var filterTimeout = null;

$(document).on('input', '.tcrud-filter-input', function() {
    var input = $(this);
    var field = input.data('field');
    var value = input.val();
    var clearBtn = $('.tcrud-filter-clear[data-field="' + field + '"]');
    
    if (value.length > 0) {
        clearBtn.show();
    } else {
        clearBtn.hide();
    }
    
    clearTimeout(filterTimeout);
    filterTimeout = setTimeout(function() {
        saveFilterAndReload(field, value);
    }, 500);
});

$(document).on('click', '.tcrud-filter-clear', function() {
    var field = $(this).data('field');
    var input = $('.tcrud-filter-input[data-field="' + field + '"]');
    
    input.val('');
    $(this).hide();
    
    saveFilterAndReload(field, '');
});

$(document).on('click', '#tcrudClearAllFilters', function() {
    $.ajax({
        url: window.location.pathname + '/tcclearfilters',
        type: 'POST',
        data: { tcrud_ajax: 1 },
        success: function() {
            $('.tcrud-filter-input').val('');
            $('.tcrud-filter-clear').hide();
            
            if ($.fn.DataTable.isDataTable('#crudTable')) {
                $('#crudTable').DataTable().ajax.reload();
            }
        }
    });
});

function saveFilterAndReload(field, value) {
    $.ajax({
        url: window.location.pathname + '/tcfilter',
        type: 'POST',
        data: { 
            field: field, 
            value: value,
            tcrud_ajax: 1 
        },
        success: function() {
            if ($.fn.DataTable.isDataTable('#crudTable')) {
                $('#crudTable').DataTable().ajax.reload();
            }
        }
    });
}

// ============================================================
// EDICIÓN EN LÍNEA
// ============================================================

$(document).on('click', '.tcrud-inline-edit-icon', function(e) {
    e.stopPropagation();
    var icon = $(this);
    var td = icon.closest('td');
    var id = icon.data('id');
    var field = icon.data('field');
    var value = icon.data('value');
    var type = icon.data('type') || 'text';
    var options = icon.data('options');
    
    var originalHtml = td.html();
    var input;
    
    if ((type === 'dropdown' || type === 'relation') && options) {
        input = $('<select>').attr({ class: 'tcrud-inline-input' });
        $.each(options, function(key, label) {
            var opt = $('<option>').attr('value', key).text(label);
            if (key == value) opt.attr('selected', true);
            input.append(opt);
        });
    } else {
        var inputType = 'text';
        if (type === 'number') inputType = 'number';
        else if (type === 'email') inputType = 'email';
        else if (type === 'url') inputType = 'url';
        else if (type === 'date') inputType = 'date';
        else if (type === 'time') inputType = 'time';
        else if (type === 'datetime') inputType = 'datetime-local';
        else if (type === 'color') inputType = 'color';
        
        input = $('<input>').attr({ type: inputType, class: 'tcrud-inline-input', value: value });
    }
    
    td.html(input);
    input.focus();
    
    input.on('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            saveInlineEdit(td, id, field, input.val(), originalHtml);
        } else if (e.key === 'Escape') {
            td.html(originalHtml);
        }
    });
    
    if (input.is('select')) {
        input.on('change', function() {
            saveInlineEdit(td, id, field, input.val(), originalHtml);
        });
    }
    
    input.on('blur', function() {
        if (td.find('.tcrud-inline-input').length) {
            saveInlineEdit(td, id, field, input.val(), originalHtml);
        }
    });
});

function saveInlineEdit(td, id, field, value, originalHtml) {
    var crudTable = $('#crudTable');
    var csrfName = crudTable.data('csrf-name') || 'csrf_test_name';
    var csrfHash = crudTable.data('csrf-hash') || '';
    var i18n = crudTable.data('i18n') || {};
    
    var data = { field: field, value: value };
    if (csrfHash) data[csrfName] = csrfHash;
    
    $.ajax({
        url: window.location.pathname + '/tcinlineedit/' + id,
        type: 'POST',
        data: data,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                if ($.fn.DataTable.isDataTable('#crudTable')) {
                    $('#crudTable').DataTable().ajax.reload(null, false);
                }
            } else {
                td.html(originalHtml);
                Swal.fire({
                    icon: 'error',
                    title: i18n.error_title || 'Error',
                    text: response.message || ''
                });
            }
        },
        error: function() {
            td.html(originalHtml);
            Swal.fire({
                icon: 'error',
                title: i18n.error_title || 'Error',
                text: i18n.error_request || ''
            });
        }
    });
}

// ============================================================
// RELACIONES DEPENDIENTES
// ============================================================

$(document).ready(function() {
    $('.crud-dependent-select').each(function() {
        var childSelect = $(this);
        var parentField = childSelect.data('depends-on');
        var parentSelect = $('select[name="' + parentField + '"]');
        
        if (parentSelect.length && parentSelect.val()) {
            loadDependentOptions(childSelect, parentSelect.val());
        } else {
            childSelect.prop('disabled', true);
        }
    });
    
    $(document).on('change', 'select[data-dependent-child]', function() {
        var parentSelect = $(this);
        var childField = parentSelect.data('dependent-child');
        var childSelect = $('select[name="' + childField + '"]');
        
        if (childSelect.length) {
            childSelect.val('').trigger('change');
            loadDependentOptions(childSelect, parentSelect.val());
        }
    });
});

$(document).ready(function() {
    $('.crud-dependent-select').each(function() {
        var parentField = $(this).data('depends-on');
        $('select[name="' + parentField + '"]').attr('data-dependent-child', $(this).data('dependent-field'));
    });
});

function loadDependentOptions(childSelect, parentValue) {
    var field = childSelect.data('dependent-field');
    var url = $('#crudTable').data('dependent-url') || window.location.pathname.replace(/\/tc.*$/, '') + '/tcdependent';
    
    var currentValue = childSelect.val();
    
    if (!parentValue) {
        childSelect.html('<option value="">' + (i18n.select_option || 'Select...') + '</option>').prop('disabled', true);
        return;
    }
    
    $.ajax({
        url: url,
        type: 'POST',
        data: { field: field, parent_value: parentValue },
        dataType: 'json',
        success: function(response) {
            if (response.success && response.options) {
                var html = '<option value="">' + (i18n.select_option || 'Select...') + '</option>';
                $.each(response.options, function(i, opt) {
                    var selected = (opt.value == currentValue) ? ' selected' : '';
                    html += '<option value="' + opt.value + '"' + selected + '>' + opt.label + '</option>';
                });
                childSelect.html(html).prop('disabled', false);
            }
        }
    });
}