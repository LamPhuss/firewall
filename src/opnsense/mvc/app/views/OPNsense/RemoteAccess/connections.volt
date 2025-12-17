<script>
$( document ).ready(function() {
    var data_get_map = {'frm_GeneralSettings':"/api/remoteaccess/settings/get"};
    mapDataToFormUI(data_get_map).done(function(data){
        formatTokenizersUI();
        $('.selectpicker').selectpicker('refresh');
    });

    // Define custom formatters
    var customFormatters = {
        // Protocol badge formatter
        "protocol_badge": function (column, row) {
            var protocol = row.protocol || '';
            var badges = {
                'ssh': '<span class="label label-info">SSH</span>',
                'vnc': '<span class="label label-warning">VNC</span>',
                'rdp': '<span class="label label-primary">RDP</span>',
                'telnet': '<span class="label label-default">Telnet</span>'
            };
            return badges[protocol.toLowerCase()] || '<span class="label label-default">' + protocol + '</span>';
        },
        
        // ✅ Commands formatter - Fixed button sizes
        "commands": function (column, row) {
            return '<button type="button" class="btn btn-xs btn-success command-connect" ' +
                      'data-row-id="' + row.uuid + '" ' +
                      'data-row-name="' + (row.name || '') + '" ' +
                      'title="Connect">' +
                      '<span class="fa fa-play fa-fw"></span>' +
                   '</button> ' +
                   '<button type="button" class="btn btn-xs btn-default command-edit" ' +
                      'data-row-id="' + row.uuid + '" ' +
                      'title="Edit">' +
                      '<span class="fa fa-pencil fa-fw"></span>' +
                   '</button> ' +
                   '<button type="button" class="btn btn-xs btn-default command-copy" ' +
                      'data-row-id="' + row.uuid + '" ' +
                      'title="Clone">' +
                      '<span class="fa fa-clone fa-fw"></span>' +
                   '</button> ' +
                   '<button type="button" class="btn btn-xs btn-default command-delete" ' +
                      'data-row-id="' + row.uuid + '" ' +
                      'title="Delete">' +
                      '<span class="fa fa-trash-o fa-fw"></span>' +
                   '</button>';
        }
    };

    // Initialize grid
    $("#{{formGridConnection['table_id']}}").UIBootgrid({
        search:'/api/remoteaccess/settings/searchConnection',
        get:'/api/remoteaccess/settings/getConnection/',
        set:'/api/remoteaccess/settings/setConnection/',
        add:'/api/remoteaccess/settings/addConnection/',
        del:'/api/remoteaccess/settings/delConnection/',
        toggle:'/api/remoteaccess/settings/toggleConnection/',
        options: {
            formatters: customFormatters
        }
    });

    // Bind Connect button
    $("#{{formGridConnection['table_id']}}").on("loaded.rs.jquery.bootgrid", function (e) {
        $(".command-connect").off('click').on("click", function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            var btn = $(this);
            var uuid = btn.data("row-id");
            var connectionName = btn.data("row-name");
            
            var originalHtml = btn.html();
            btn.prop('disabled', true).html('<span class="fa fa-spinner fa-spin fa-fw"></span>');
            
            ajaxGet("/api/remoteaccess/settings/getConnectionUrl/" + uuid, {}, function(data, status) {
                btn.prop('disabled', false).html(originalHtml);

                console.log(data);
                
                
                if (data.result === 'success' && data.url) {
                    var width = Math.floor(screen.width * 0.8);
                    var height = Math.floor(screen.height * 0.8);
                    var left = Math.floor((screen.width - width) / 2);
                    var top = Math.floor((screen.height - height) / 2);
                    var token = "B42AD764BC81447DF0504584F82D7895975A13E9BF5AF3BBD1C03A25C5BFF64E"; // Placeholder token
                    var popup = window.open(
                        'http://' + window.location.hostname + ":9443/guacamole/#/client/MQBjAHBvc3RncmVzcWw?token=" + token,
                        'Remote Access - ' + connectionName,
                        'width=' + width + ',height=' + height + ',left=' + left + ',top=' + top + 
                        ',resizable=yes,scrollbars=no,toolbar=no,menubar=no,location=no,status=no'
                    );
                    
                    if (!popup || popup.closed || typeof popup.closed == 'undefined') {
                        BootstrapDialog.show({
                            type: BootstrapDialog.TYPE_WARNING,
                            title: 'Popup Blocked',
                            message: 'Please allow popups for this site.<br><br>' +
                                    '<a href="' + data.url + '" target="_blank" class="btn btn-primary">' +
                                    '<span class="fa fa-external-link"></span> Open in new tab</a>'
                        });
                    }
                } else {
                    BootstrapDialog.show({
                        type: BootstrapDialog.TYPE_DANGER,
                        title: 'Connection Error',
                        message: data.error || 'Failed to get connection URL'
                    });
                }
            }, function(xhr, status, error) {
                btn.prop('disabled', false).html(originalHtml);
                BootstrapDialog.show({
                    type: BootstrapDialog.TYPE_DANGER,
                    title: 'Network Error',
                    message: 'Failed to connect to API'
                });
            });
        });
    });

    var actionBar = $("div.actionBar").parent();
    if (actionBar.length && !$("#heading-wrapper").length) {
        actionBar.prepend('<td id="heading-wrapper" class="col-sm-12"><h4 class="theading-text">Connections</h4></td>');
    }

    var defaultPorts = { 'ssh': 22, 'vnc': 5900, 'rdp': 3389, 'telnet': 23 };
    var protocolFields = {
        'ssh': ['username', 'password', 'private_key'],
        'vnc': ['password'],
        'rdp': ['username', 'password', 'domain'],
        'telnet': ['username', 'password']
    };

    function updateFieldVisibility(dialogId, protocol) {
        var allOptionalFields = ['username', 'password', 'private_key', 'domain'];
        var dialogElement = $('#' + dialogId);
        
        allOptionalFields.forEach(function(fieldName) {
            var field = dialogElement.find('[id*="connection.' + fieldName + '"]');
            if (field.length) field.closest('tr').hide();
        });
        
        if (protocol && protocolFields[protocol]) {
            protocolFields[protocol].forEach(function(fieldName) {
                var field = dialogElement.find('[id*="connection.' + fieldName + '"]');
                if (field.length) field.closest('tr').show();
            });
        }
    }

    var dialogId = '{{formGridConnection["edit_dialog_id"]}}';
    var lastProtocol = '';
    
    $('#' + dialogId)
        .on('show.bs.modal', function() { lastProtocol = ''; })
        .on('shown.bs.modal', function() {
            var dialog = $(this);
            var protocolSelect = dialog.find('select[id*="connection.protocol"]').first();
            var portInput = dialog.find('input[id*="connection.port"]').first();
            
            if (protocolSelect.length === 0) return;
            
            protocolSelect.off('change.remoteaccess');
            
            var initialProtocol = protocolSelect.val() || '';
            if (!initialProtocol) {
                protocolSelect.val('ssh');
                if (protocolSelect.hasClass('selectpicker')) {
                    protocolSelect.selectpicker('val', 'ssh');
                }
                initialProtocol = 'ssh';
            }
            
            lastProtocol = initialProtocol;
            updateFieldVisibility(dialogId, initialProtocol);
            
            if (defaultPorts[initialProtocol]) {
                var currentPort = portInput.val();
                var isDefaultPort = Object.values(defaultPorts).indexOf(parseInt(currentPort)) !== -1;
                if (!currentPort || isDefaultPort) {
                    portInput.val(defaultPorts[initialProtocol]);
                }
            }
            
            setTimeout(function() {
                protocolSelect.on('change.remoteaccess', function(e) {
                    var newProtocol = $(this).val() || '';
                    if (newProtocol === lastProtocol || !newProtocol) return;
                    
                    lastProtocol = newProtocol;
                    updateFieldVisibility(dialogId, newProtocol);
                    
                    if (defaultPorts[newProtocol]) {
                        portInput.val(defaultPorts[newProtocol]);
                    }
                });
            }, 300);
        })
        .on('hidden.bs.modal', function() {
            var dialog = $(this);
            var protocolSelect = dialog.find('select[id*="connection.protocol"]').first();
            protocolSelect.off('change.remoteaccess');
            lastProtocol = '';
        });

    $("#reconfigureAct").SimpleActionButton();
    updateServiceControlUI('remoteaccess');
});
</script>

<style>
    .theading-text {
        font-weight: 800;
        font-style: italic;
    }
    
    /* ✅ Fixed button sizing */
    .command-connect {
        background-color: #5cb85c !important;
        border-color: #4cae4c !important;
        color: white !important;
        min-width: 28px;
    }
    
    .command-connect:hover:not(:disabled) {
        background-color: #449d44 !important;
        border-color: #398439 !important;
    }
    
    .command-connect:disabled {
        opacity: 0.65;
        cursor: not-allowed;
    }
    
    /* ✅ Ensure all command buttons have same size */
    .command-edit,
    .command-copy,
    .command-delete {
        min-width: 28px;
    }
    
    /* ✅ Icons with fixed width */
    .fa-fw {
        width: 1.28571429em;
        text-align: center;
    }
    
    /* Protocol labels (use label instead of badge) */
    .label {
        display: inline-block;
        padding: 4px 8px;
        font-size: 11px;
        font-weight: bold;
        border-radius: 3px;
        text-transform: uppercase;
    }
    
    .label-info {
        background-color: #5bc0de;
        color: white;
    }
    
    .label-warning {
        background-color: #f0ad4e;
        color: white;
    }
    
    .label-primary {
        background-color: #337ab7;
        color: white;
    }
    
    .label-default {
        background-color: #777;
        color: white;
    }
</style>

<div class="content-box __mb">
    {{ partial('layout_partials/base_bootgrid_table', formGridConnection) }}
</div>

{{ partial('layout_partials/base_apply_button', {'data_endpoint': '/api/remoteaccess/service/reconfigure', 'data_service_widget': 'remoteaccess'}) }}

{{ partial("layout_partials/base_dialog",['fields':formDialogConnection,'id':formGridConnection['edit_dialog_id'],'label':'Edit Connection'])}}