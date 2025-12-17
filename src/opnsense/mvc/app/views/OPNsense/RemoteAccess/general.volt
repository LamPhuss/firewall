<script>
    $( document ).ready(function() {
        var data_get_map = {'frm_GeneralSettings':"/api/remoteaccess/settings/get"};

        mapDataToFormUI(data_get_map).done(function(data) {
            // place actions to run after load, for example update form styles.
        });

        // link save button to API set action
        $("#saveAct").click(function(){
            saveFormToEndpoint("/api/remoteaccess/settings/set",'frm_GeneralSettings',function(){
                // action to run after successful save, for example reconfigure service.
                ajaxCall(url="/api/remoteaccess/service/reconfigure", sendData={},callback=function(data,status) {
                    // update service widget
                });
            });
        });

        updateServiceControlUI('remoteaccess');
    });
</script>

<div class="content-box __mb">
    {{ partial("layout_partials/base_form",
        [
            'fields':generalForm,
            'id':'frm_GeneralSettings'
        ])
    }}
</div>
<button class="btn btn-primary" id="saveAct" type="button">
    <b>{{ lang._('Save') }}</b>
</button>



