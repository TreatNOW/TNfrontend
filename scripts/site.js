
//global class to hold site-specific utility functions and constants
var Site = new siteUtilities();
function siteUtilities() {

    //quick function to swap out / insert language codes
    //> assumes a straight split will yield predictable parts
    //> causes a reload of the page
    this.setLanguage = function(langCode) {
        //get uri (using location.href as window doesn't work with iOS)
        var uri = location.href;
        //take any params off first
        var qs = "";
        if (uri.indexOf('?') >= 0) {
            qs = new String(uri.split('?')[1]);
            uri = new String(uri.split('?')[0]);
        }
        //split out path
        var uriParts = uri.split("/");
        var domain = uriParts[0] + "//" + uriParts[2] + "/";
        var path = "";
        if (uriParts[3].length == 2) {
            uriParts[3] = langCode;
        }
        else {
            path = langCode + "/";
        }
        //re-assemble
        for (var i = 3; i < uriParts.length; i++) {
            if (uriParts[i] != "") {
                path += uriParts[i] + "/";
            }
        }
        //put the query back
        if (qs != "") { path += "?"+qs; }
        //go
        location.href = domain + path;
    }

    //position = LatLng
    //callback returns the countryCode and locationName as params
    this.getLocationName = function(position, callback) {
        var countryCode = null;
        var neighborhood = null;
        var locality = null;
        var postcodePrefix = null;  //this is appended for london returns without neighborhoods
        var adminArea = null;       //this seems to be more useful for european locations
        var locationName = null;
        //TODO: swap in treatnow google api key
        var uri = "https://maps.googleapis.com/maps/api/geocode/json" +
            "?latlng=" + position.lat() + "," + position.lng() +
            "&sensor=true" +
            "&key=AIzaSyD8TU2oMPUqgoEK6GZMHGdIsayxL2Mm-zo";
        console.log(uri);
        $.get(uri, function(geoLookup) {
            if (geoLookup.status == "OK") {
                for (var i = 0; i < geoLookup.results.length; i++) {
                    var result = geoLookup.results[i];
                    for (var j = 0; j < result.address_components.length; j++) {
                        var component = result.address_components[j];
                        for (var k = 0; k < component.types.length; k++) {
                            if (component.types[k] == "neighborhood")                { neighborhood   = component.long_name; }
                            if (component.types[k] == "locality")                    { locality       = component.long_name; }
                            if (component.types[k] == "postal_code_prefix")          { postcodePrefix = component.long_name; }
                            if (component.types[k] == "administrative_area_level_2") { adminArea      = component.long_name; }
                            if (component.types[k] == "country")                     { countryCode    = component.short_name; }
                        }
                    }
                    //break if everything found
                    if (countryCode != null && neighborhood != null && locality != null && postcodePrefix != null) {
                        break;
                    }
                }
                //build location name (hack for london)
                if (neighborhood != null) {
                    locationName = neighborhood + ", " + locality;
                }
                else if (locality != null) {
                    locationName = locality;
                    if (locationName == "London") {
                        locationName += " " + postcodePrefix;
                    }
                }
                else if (adminArea != null) {
                    locationName = adminArea;
                }
                if (locationName != null) {
                    locationName += ", ";
                }
                //append country code (swap gb for uk)
                locationName += countryCode == "GB" ? "UK" : countryCode;
            }
            else {
                alert("Error looking up location name: "+geoLookup.error_message);
            }
            callback(countryCode, locationName);

        });
    }

    /* CONTACT POPUP */
    var contactPopup;
    this.Contact = function() {
        $("#contactNameField").keydown(function() { Forms.clearErrors(); });
        $("#contactContentField").keydown(function() { Forms.clearErrors(); });
        this.contactPopup = $('#contactPopup').modal({
            overlayClose:true,
            opacity:80,
            overlayCss: {backgroundColor:"#cccccc"}
        });

    }
    this.selectContactType = function(typeId) {
        $(".contactTypeButton").css("background-color", "#dddddd");
        $(".contactTypeButton").css("border", "0");
        $("#contactType"+typeId).css("background-color", "#b8d742");
        Forms.setValue("contactForm.typeId", typeId);
    }
    this.submitContact = function() {
        //validate
        var valid = true;
        if (Forms.getValue("contactForm.typeId") == "") {
            $(".contactTypeButton").css("border", "solid 1px #b10909");
            valid = false;
        }
        if (Forms.getValue("contactForm.contactName") == "") {
            Forms.addError("contactForm.contactName", valid);
            valid = false;
        }
        if (Forms.getValue("contactForm.content") == "") {
            Forms.addError("contactForm.content", valid);
            valid = false;
        }
        if (valid) {
            $.ajax({
                type: "POST",
                url: "/api/contact/",
                data: $("#contactForm").serialize(),
                success: function(data) {
                    if (data.success) {
                        $("#contactContainer").hide();
                        $("#contactConfirmation").show();
                    }
                    else {
                        alert("Eek! Something went wrong:\n"+data.message);
                    }
                }
            });
        }
    }

}