$(document).ready(function() {
    $('#emailForm').bootstrapValidator({        
        feedbackIcons: {
            valid: 'glyphicon glyphicon-ok',
            invalid: 'glyphicon glyphicon-remove',
            validating: 'glyphicon glyphicon-refresh'
        },
        fields: {
            uname: {
                validators: {
                        stringLength: {
                        min: 2,
                    },
                        notEmpty: {
                        message: 'Please enter your name'
                    }
                }
            },             
            email: {
                validators: {
                    notEmpty: {
                        message: 'Please enter your email address'
                    },
                    emailAddress: {
                        message: 'Please enter a valid email address'
                    }
                }
            },
			attachFile: {
                validators: {
                    file: {
                        extension: 'jpeg,png,pdf,zip,gif,txt,doc',
                        type: 'image/jpeg,image/png,application/pdf,application/zip,image/gif,text/plain,application/msword',
                        maxSize: 2048 * 1024,
                        message: 'The selected file is not valid'
                    }
                }
            },
            message: {
                validators: {
                      stringLength: {
                        min: 10,
                        max: 200,
                        message:'Please enter at least 10 characters and no more than 200'
                    },
                    notEmpty: {
                        message: 'Please enter a message'
                    }
                }
            }
        }
    });
});