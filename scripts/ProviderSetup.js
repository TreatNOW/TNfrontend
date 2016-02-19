

//VERIFICATION
function openVerify() {
    Forms.setValue("verifyForm.verifierName", "");
    $('#verifyPopup').modal({
        overlayClose:true,
        opacity:80,
        overlayCss: {backgroundColor:"#cccccc"}
    });
}
function doVerify() {
    if (Forms.getValue("verifyForm.verifierName") == "") {
        Forms.addError("verifyForm.verifierName", true);
    }
    else if (Forms.getValue("verifyForm.verifierName").length <= 3) {
        Forms.addError("verifyForm.verifierName", true);
    }
    else {
        $.ajax({
            type: "POST",
            url: "/api/verify-provider/",
            data: $("#verifyForm").serialize(),
            success: function(data)
            {
                if (data.success) {
                    $.modal.close();
                    $('#verifyCompletePopup').modal({
                        overlayClose:true,
                        opacity:80,
                        overlayCss: {backgroundColor:"#cccccc"}
                    });
                }
                else {
                    alert("Something went wrong:\n\n"+data.message);
                }
            }
        });
    }
}

//FEEDBACK
function openFeedback() {
    Forms.setValue("feedbackForm.feedback", "");
    $('#feedbackPopup').modal({
        overlayClose:true,
        opacity:80,
        overlayCss: {backgroundColor:"#cccccc"}
    });
}
function doFeedback() {
    valid = true;
    if (valid && Forms.getValue("feedbackForm.verifierName") == "") {
        Forms.addError("feedbackForm.verifierName", true);
        valid = false;
    }
    if (valid && Forms.getValue("feedbackForm.verifierName").length <= 3) {
        Forms.addError("feedbackForm.verifierName", true);
        valid = false;
    }
    if (valid && Forms.getValue("feedbackForm.feedback") == "") {
        Forms.addError("feedbackForm.feedback", true);
    }
    if (valid) {
        $.ajax({
            type: "POST",
            url: "/api/verify-feedback/",
            data: $("#feedbackForm").serialize(),
            success: function(data)
            {
                if (data.success) {
                    $.modal.close();
                    $('#feedbackCompletePopup').modal({
                        overlayClose:true,
                        opacity:80,
                        overlayCss: {backgroundColor:"#cccccc"}
                    });
                }
                else {
                    alert("Something went wrong:\n\n"+data.message);
                }
            }
        });
    }


}