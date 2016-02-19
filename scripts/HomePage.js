
function doSubscribe(formId) {
    //validate input
    var valid = true;
    if (Forms.getValue(formId+".emailAddress") == "") {
        Forms.addError(formId+".emailAddress", true);
        valid = false;
    }
    else if (!Util.isEmail(Forms.getValue(formId+".emailAddress"))) {
        Forms.addError(formId+".emailAddress", true);
        valid = false;
    }
    //post
    if (valid) {
        $.ajax({
            type: "POST",
            url: "/api/subscribe/",
            data: $("#"+formId).serialize(),
            success: function(data)
            {
                if (data.success) {
                    Forms.setValue("subscriberNameForm.subscriptionId", data.subscriptionId)
                    Forms.setValue("subscriberNameForm.name", "");
                    $('#subscribeConfirmation').modal({
                        overlayClose:true,
                        opacity:80,
                        overlayCss: {backgroundColor:"#fff"}
                    });
                    Forms.clearErrors();
                    Forms.setValue(formId+".emailAddress", "");
                }
                else {
                    alert("Something went wrong:\n\n"+data.message);
                }
            }
        });
    }
}
function addSubscriptionName() {
    //validate input
    var valid = true;
    if (Forms.getValue("subscriberNameForm.name") == "") {
        Forms.addError("subscriberNameForm.name", true);
        valid = false;
    }
    //post
    if (valid) {
        $.ajax({
            type: "POST",
            url: "/api/subscribe-name/",
            data: $("#subscriberNameForm").serialize(),
            success: function(data)
            {
                if (data.success) {
                    $.modal.close();
                }
                else {
                    alert("Something went wrong:\n\n"+data.message);
                }
            }
        });
    }
}