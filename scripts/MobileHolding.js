
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
                    $("#mobileHomeContent").hide();
                    $("#subscribeConfContent").show();
                }
                else {
                    alert("Something went wrong:\n\n"+data.message);
                }
            }
        });
    }
}