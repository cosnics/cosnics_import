dropzoneCallbacks.chamilo = {
    libraries : {
        single : {
            processUploadedFile : function(environment, file, serverResponse)
            {
                var fileData = {
                    name : file.name,
                    temporaryFileName : serverResponse.properties.temporaryFileName
                };
                
                if (environment.settings.titleInputName != null)
                {
                    var titleField = $('input[name=' + environment.settings.titleInputName + ']');
                    
                    if (titleField.val() == '')
                    {
                        titleField.val(file.name);
                    }
                }
                
                $('input[type=hidden][name=' + environment.settings.name + '_upload_data]').val(
                        JSON.stringify(fileData));
                
                $(file.previewElement).data('temporary-file-name', serverResponse.properties.temporaryFileName);
            },
            deleteUploadedFile : function(environment, file, serverResponse)
            {
                var ajaxUri = getPath('WEB_PATH') + 'index.php';
                var temporaryFileName = $(file.previewElement).data('temporary-file-name');
                
                var parameters = {
                    'application' : 'Chamilo\\Libraries\\Ajax',
                    'go' : 'DeleteTemporaryFile',
                    'file' : temporaryFileName
                };
                
                var response = $.ajax({
                    type : "POST",
                    url : ajaxUri,
                    data : parameters
                }).success(function(json)
                {
                    if (environment.settings.titleInputName != null)
                    {
                        var titleField = $('input[name=' + environment.settings.titleInputName + ']');
                        titleField.val('');
                    }
                    
                    $('input[type=hidden][name=' + environment.settings.name + '_upload_data]').val('');
                });
            }
        }
    }
};