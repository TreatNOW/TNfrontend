
var slideSpeed = 300;
var currentPosition = null;
var currentCategoryId = null;
var providerData = null;
var map = null;
var currentPane = null;
var paneStack = new Array();
var locationInfoText;
var categories;

//get geocode from browser
$(function() {
    //save info content
    //TODO: put some thought into this > maybe a cms object to hold this stuff?
    locationInfoText = $("#locationInfoText").html();
    //wire up form fields
    $("input[placeholder]").inputHints();
    //get location
    //> check for override first (debug)
    //> then go to browser (normal)
    var llOverride = Util.getQsParam(location.href, "ll");
    if (llOverride != "") {
        currentPosition = new google.maps.LatLng(llOverride.split(',')[0], llOverride.split(',')[1]);
        init();
    }
    else {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    currentPosition = new google.maps.LatLng(position.coords.latitude, position.coords.longitude);
                    init();
                },
                function() {
                    currentPosition = new google.maps.LatLng(51.5133705,-0.1355852);
                    init();
                }
            );
        }
        else {
            currentPosition = new google.maps.LatLng(51.5133705,-0.1355852);
        }
    }
});

function showPane(pane) {
    if (currentPane) {
        paneStack[paneStack.length] = currentPane;
        $(currentPane).hide();
    }
    currentPane = pane;
    $(currentPane).show();
    setPaneStyles(currentPane);
}
function goBack() {
    if (paneStack.length > 0) {
        $(currentPane).hide();
        currentPane = paneStack.pop();
        $(currentPane).show();
        setPaneStyles(currentPane);
    }
}
function setPaneStyles(pane) {
    if (pane == "#category-view-1") {
        $("#main").css('background-color', '#ffffff');
        $("#intro-text").show();
        $("#location").show();
    }
    else if (pane == "#category-view-beauty" || pane == "#category-view-wellness" || pane == "#category-view-male") {
        $("#main").css('background-color', '#000000');
        $("body").css('background-color', '#000000');
        $("#intro-text").hide();
        $("#location").show();
    }
    else if (pane == "#category-subView-beauty" || pane == "#category-subView-wellness" || pane == "#category-subView-male") {
        $("#main").css('background-color', '#000000');
        $("#intro-text").hide();
        $("#location").hide();
    }
    else if (pane == "#noVenuesView" || pane == "#mapView" || pane == "#noVenuesInCategoryView") {
        $("#main").css('background-color', '#ffffff');
        $("#intro-text").hide();
        $("#location").show();
    }
}
function showMain(pane) {
    $("#splash").hide();
    $("#main").show();
    showPane(pane);
}
function init() {
    //get location
    Site.getLocationName(currentPosition, function(countryCode, locationName) {
        if (locationName != null) {
            Forms.setValue("locationSearchForm.locationName", locationName);
            //get providers in location
            getProviders(currentPosition, null, function(data) {
                providerData = data;
                displayProviderCount(providerData.requestableCount);
                if (providerData.totalCount == 0) {
                    showMain("#noVenuesView");
                    Forms.setFocus("signupForm.emailAddress");
                }
                else if (providerData.requestableCount == 0) {
                    showMain("#asleepView");
                }
                else {
                    showMain("#category-view-1");
                }
                $("#startup").hide();
                $("#main").show();
            });
        }
        //get categories
        $.get("/api/categories/?lang="+dictionaryCode, function(data) {
            categories = data.categories;
        });
    });
    //hook up autocomplete
    var input = document.getElementById('locationSearchBox');
    autocomplete = new google.maps.places.Autocomplete(input);
    google.maps.event.addListener(autocomplete, 'place_changed', function() {
        var place = autocomplete.getPlace();
        if (place.geometry) {
            currentPosition = place.geometry.location;
            if (map) {
                map.panTo(currentPosition);
            }
            updateProviderCount();
            if (currentPane == "#noVenuesView" || currentPane == "#asleepView") {
                showPane("#category-view-1");
            }
        }
    });
}
function updateProviderCount() {
    getProviders(currentPosition, currentCategoryId, function(data) {
        providerData = data;
        if (providerData.totalCount == 0) {
            switch (currentPane) {
                case "#category-view-1":
                case "#category-view-beauty":
                case "#category-view-wellness":
                case "#category-view-male":
                case "#category-subView-beauty":
                case "#category-subView-wellness":
                case "#category-subView-male":
                    showPane("#noVenuesView");
            }
        }
        else {
            if (currentPane == "#noVenuesView") {
                showPane("#category-view-1");
            }
        }
        displayProviderCount(providerData.requestableCount);
    });
}
//TODO: this is hard-coded for english
function displayProviderCount(count) {
    if (count == 0) {
        $("#locationInfoText").html(locationInfoText.replace("?", "NO VENUES ONLINE"));
    }
    else if (count == 1) {
        $("#locationInfoText").html(locationInfoText.replace("?", "1 VENUE ONLINE"));
    }
    else {
        $("#locationInfoText").html(locationInfoText.replace("?", count + " VENUES ONLINE"));
    }
}
//TODO: Make this work again
var langListActive = false;
function selectLang() {
    $("#langList").css("left", $(window).width() - $("#langList").width() - 20);
    $("#langList").show();
    langListActive = true;
}
function selectCategory(id) {
    //get category info
    var category      = getCategory(id);
    var level         = getCategoryLevel(id);
    var providerCount = getCategoryProviderCount(id);
    //if no providers match the request category jump to the noProviders pane
    if (providerCount == 0) {
        //set header text
        $("#nviCatName").html(Format.Html(category.name));
        //build list of available categories
        var categories = getAvailableCategories();
        $("#nviCat-list button").remove();
        for (var i = 0; i < categories.length; i++) {
            var displayName = categories[i].name;
            if (categories[i].path) {
                displayName = categories[i].path + " > " + displayName;
            }
            var button = document.createElement("button");
            button.setAttribute("onclick", "selectCategory("+categories[i].id+");");
            button.innerHTML = Format.Html(displayName);
            $("#nviCat-list").append(button);
        }
        showPane("#noVenuesInCategoryView");
    }
    else {
        //if no children then submit to zidmi (could happen at level 2 or 3)
        if (category.children.length == 0) {
            Forms.setValue("zidmiForm.providers", getProviderList(id));
            Forms.setValue("zidmiForm.categoryId", id);
            Forms.setValue("zidmiForm.ll", currentPosition.lat()+","+currentPosition.lng());
            Forms.setValue("zidmiForm.locationName", Forms.getValue("locationSearchForm.locationName"));
            Forms.setValue("zidmiForm.lang", dictionaryCode);
            Forms.setValue("zidmiForm.backUri", Util.setQsParam(Forms.getValue("zidmiForm.backUri"), "ll", Forms.getValue("zidmiForm.ll")));
            $("#zidmiForm").submit();
        }
        else {
            //level 1
            if (level == 1) {
                if      (id == 1)  { showPane("#category-view-beauty"); }
                else if (id == 45) { showPane("#category-view-wellness"); }
                else if (id == 75) { showPane("#category-view-male"); }
            }
            //level 2
            else {
                //set sub-title
                $(".subView-title").html(category.name);
                //TODO: sort out html formatting issues.
                //$(".subView-title").html(Util.Html(category.name));
                //load categories
                $(".categoryButton").remove();
                for (var i = 0; i < category.children.length; i++) {
                    var button = document.createElement("button");
                    button.setAttribute("class", "categoryButton");
                    button.setAttribute("onclick", "selectCategory("+category.children[i].id+");");
                    button.innerHTML = category.children[i].name;
                    if      (category.parentId == 1)  { $("#subView-beauty-list").append(button); }
                    else if (category.parentId == 45) { $("#subView-wellness-list").append(button); }
                    else if (category.parentId == 75) { $("#subView-male-list").append(button); }
                }
                if      (category.parentId == 1)  { showPane("#category-subView-beauty"); }
                else if (category.parentId == 45) { showPane("#category-subView-wellness"); }
                else if (category.parentId == 75) { showPane("#category-subView-male"); }
            }
        }
    }
}
//parses the tree to return a specific category
function getCategory(categoryId) {
    for (var i = 0; i < categories.length; i++) {
        if (categories[i].id == categoryId) { return categories[i]; }
        for (j = 0; j < categories[i].children.length; j++) {
            if (categories[i].children[j].id == categoryId) { return categories[i].children[j]; }
            for (k = 0; k < categories[i].children[j].children.length; k++) {
                if (categories[i].children[j].children[k].id == categoryId) { return categories[i].children[j].children[k]; }
            }
        }
    }
    return null;
}
//parses the tree to return a specific category level
function getCategoryLevel(categoryId) {
    for (var i = 0; i < categories.length; i++) {
        if (categories[i].id == categoryId) { return 1; }
        for (j = 0; j < categories[i].children.length; j++) {
            if (categories[i].children[j].id == categoryId) { return 2; }
            for (k = 0; k < categories[i].children[j].children.length; k++) {
                if (categories[i].children[j].children[k].id == categoryId) { return 3; }
            }
        }
    }
    return null;
}
//gets a count of all providers that match a given category
function getCategoryProviderCount(categoryId) {
    var count = 0;
    if (providerData) {
        for (var i = 0; i < providerData.providers.length; i++) {
            var provider = providerData.providers[i];
            if (provider.requestable) {
                for (var j = 0; j < provider.categories.length; j++) {
                    if (provider.categories[j] == categoryId) {
                        count++;
                        break;
                    }
                }
            }
        }
    }
    return count;
}
//makes a simple list of all the categories which have matching providers
//> only returns categories with no children
function getAvailableCategories() {
    var categories = new Array();
    //merge all provider lists
    if (providerData) {
        for (var i = 0; i < providerData.providers.length; i++) {
            var provider = providerData.providers[i];
            for (var j = 0; j < provider.categories.length; j++) {
                var category = getCategory(provider.categories[j])
                if (category.children.length == 0) {
                    categories[categories.length] = getCategory(provider.categories[j]);
                }
            }
        }
        categories = Util.dedupe(categories);
    }
    return categories;
}
function showMap() {
    if (currentPane != "mapView") {
        //position button
        $("#mapSelect").css("top", $(document).height() - ($("#mapSelect").height() * 3));
        $("#mapSelect").css("left", ($(document).width() - $("#mapSelect").width()) / 3);
        //alert($("#map").position().top);
        //$("#map").height($(document).innerHeight() - ($("#header").height() + $("#location").height()));
        //$("#map").width($(document).innerWidth());
        $(currentPane).hide("slide", { direction: "left" }, slideSpeed, function() {
            //$("#locationInfo").hide();
            showPane("#mapView");
            var mapOptions = {
                center: currentPosition,
                zoom: 15,
                draggableCursor:"crosshair",
                panControl:false,
                zoomControl:false,
                streetViewControl:false
            };
            map = new google.maps.Map(document.getElementById("map"), mapOptions);
            drawProviderMarkers(true);
            //hookup map change capture
            google.maps.event.addListener(map, 'idle', function() {
                getProvidersByBounds(map.getBounds(), currentCategoryId, function(data) {
                    providerData = data;
                    drawProviderMarkers();
                    displayProviderCount(providerData.requestableCount);
                });
            });
        });
    }
}
function drawProviderMarkers(scaleToFit) {
    if (providerData) {
        if (providerData.providers.length > 0) {
            var bounds = new google.maps.LatLngBounds();
            for (var i = 0; i < providerData.providers.length; i++) {
                var provider = providerData.providers[i];
                var iconUri = "/images/map-provider-marker.png";
                if (!provider.requestable) {
                    iconUri = "/images/map-provider-closed-marker.png";
                }
                var marker = new google.maps.Marker({
                    position: new google.maps.LatLng(provider.latitude,provider.longitude),
                    map: map,
                    icon: iconUri,
                    title: provider.name,
                    providerId: provider.id
                });
                google.maps.event.addListener(marker, 'click', function() {
                    showProviderInfo(this);
                });
                bounds.extend(marker.position);
            }
            if (scaleToFit) {
                map.fitBounds(bounds);
                map.setZoom(map.getZoom() - 2);
            }
        }
    }
}
function showProviderInfo(marker) {
    if (providerData) {
        //get provider from saved list
        for (var i = 0; i < providerData.providers.length; i++) {
            if (providerData.providers[i].id == marker.providerId) {
                provider = providerData.providers[i];
                break;
            }
        }
        //configure infoTemplate
        var html = $("#infoTemplate").html();
        html = html.replace("{$providerId}", provider.id);
        html = html.replace("{$providerName}", provider.name);
        html = html.replace("{$providerAddress}", provider.address);
        //open
        var infowindow = new google.maps.InfoWindow({
            content: html
        });
        infowindow.open(map,marker);
    }
}
function closeMap() {
    if (providerData) {
        if (providerData.providers.length > 0) {
            showPane("#category-view-1");
        }
        else {
            showPane("#noVenuesView");
        }
    }
    else {
        showPane("#noVenuesView");
    }
}
//this is used when we're working from a single point
function getProviders(position, categoryId, callback) {
    var uri = "/api/providers/?ll="+position.lat()+","+position.lng();
    if (categoryId) {
        uri += "&category="+categoryId;
    }
    console.log(uri);
    $.get(uri, function(data) {
        callback(data);
    });
}
//this is used when moving the map around
function getProvidersByBounds(bounds, categoryId, callback) {
    var ll2 = bounds.getNorthEast().lat()+","+bounds.getNorthEast().lng();
    var ll1 = bounds.getSouthWest().lat()+","+bounds.getSouthWest().lng();
    var uri = "/api/providers/?ll1="+ll1+"&ll2="+ll2;
    if (categoryId) {
        uri += "&category="+categoryId;
    }
    $.get(uri, function(data) {
        callback(data);
    });
}

function getProviderList(categoryId) {
    var providerList = "";
    if (providerData) {
        for (var i = 0; i < providerData.providers.length; i++) {
            var provider = providerData.providers[i];
            if (provider.requestable) {
                for (var j = 0; j < provider.categories.length; j++) {
                    if (provider.categories[j] == categoryId) {
                        providerList += providerList.length > 0 ? "," : "";
                        providerList += providerData.providers[i].reference;
                    }
                }
            }
        }
    }
    return providerList;
}
function subscribe() {
    //validate input
    var valid = true;
    if (Forms.getValue("signupForm.emailAddress") == "") {
        Forms.addError("signupForm.emailAddress", true);
        valid = false;
    }
    else if (!Util.isEmail(Forms.getValue("signupForm.emailAddress"))) {
        Forms.addError("signupForm.emailAddress", true);
        valid = false;
    }
    //post
    if (valid) {
        $.ajax({
            type: "POST",
            url: "/api/subscribe/",
            data: $("#signupForm").serialize(),
            success: function(data)
            {
                if (data.success) {
                    $("#noVenuesView").hide("slide", { direction: "left" }, slideSpeed, function() {
                        showPane("#subscribedView");
                    });
                }
            }
        });
    }
}
function selectHexCategory(category1, event) {
    var grid = "#"+category1+"Grid";
    var hexId = getGridHex(grid, event);
    var categoryId = 0;
    //map categories
    if (hexId > 0) {
        if (category1 == "beauty") {
            if      (hexId == 1) { categoryId = 28; }
            else if (hexId == 2) { categoryId = 2;  }
            else if (hexId == 3) { categoryId = 29; }
            else if (hexId == 4) { categoryId = 25; }
            else if (hexId == 5) { categoryId = 39; }
            else if (hexId == 6) { categoryId = 16; }
        }
        else if (category1 == "wellness") {
            if      (hexId == 1) { categoryId = 46; }
            else if (hexId == 2) { categoryId = 69; }
            else if (hexId == 3) { categoryId = 50; }
            else if (hexId == 4) { categoryId = 65; }
            else if (hexId == 5) { categoryId = 58; }
            else if (hexId == 6) { categoryId = 55; }
        }
        else if (category1 == "male") {
            if      (hexId == 1) { categoryId = 80; }
            else if (hexId == 2) { categoryId = 88; }
            else if (hexId == 3) { categoryId = 83; }
            else if (hexId == 4) { categoryId = 90; }
            else if (hexId == 5) { categoryId = 89; }
            else if (hexId == 6) { categoryId = 76; }
        }
    }
    //select
    if (categoryId > 0) {
        selectCategory(categoryId);
    }
}
function getGridHex(grid, e) {
    var point = {x:((e.pageX - $(grid).offset().left) / $(grid).width()) * 1000,
                 y:((e.pageY - $(grid).offset().top) / $(grid).height()) * 1000}
    var hex1Poly = [{x:490,y:19},{x:677,y:19},{x:772, y:174},{x:677,y:327},{x:487,y:327},{x:395,y:172}];
    var hex2Poly = [{x:797,y:183},{x:983,y:183},{x:1080,y:337},{x:985,y:493},{x:795,y:493},{x:702,y:337}];
    var hex3Poly = [{x:794,y:516},{x:983,y:516},{x:1078,y:668},{x:984,y:825},{x:795,y:822},{x:701,y:669}];
    var hex4Poly = [{x:489,y:681},{x:675,y:681},{x:772,y:836},{x:675,y:990},{x:486,y:990},{x:394,y:837}];
    var hex5Poly = [{x:185,y:514},{x:374,y:514},{x:468,y:668},{x:374,y:824},{x:184,y:824},{x:91,y:668}];
    var hex6Poly = [{x:184,y:186},{x:373,y:186},{x:468,y:340},{x:373,y:494},{x:186,y:494},{x:91,y:340}];
    if      (Util.isPointInPoly(hex1Poly, point)) { return 1; }
    else if (Util.isPointInPoly(hex2Poly, point)) { return 2; }
    else if (Util.isPointInPoly(hex3Poly, point)) { return 3; }
    else if (Util.isPointInPoly(hex4Poly, point)) { return 4; }
    else if (Util.isPointInPoly(hex5Poly, point)) { return 5; }
    else if (Util.isPointInPoly(hex6Poly, point)) { return 6; }
    else    { return 0; }
}
function goToNearestVenue() {
    if (providerData.nearestProvider) {
        currentPosition = new google.maps.LatLng(providerData.nearestProvider.latitude,
                                                 providerData.nearestProvider.longitude);
        $("#noVenuesView").hide();
        $("#subscribedView").hide();
        init();
    }
}
function goToLocation(lat, lng) {
    currentPosition = new google.maps.LatLng(lat,lng);
    $("#noVenuesView").hide();
    $("#subscribedView").hide();
    showPane("#category-view-1");
    init();
}