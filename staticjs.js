var COOLDOWN = 86400;

// Contains "uppercase" expression taken from http://kilianvalkhof.com/
jQuery.expr[':'].Contains = function(a,i,m){
      return (a.textContent || a.innerText || "").toUpperCase().indexOf(m[3].toUpperCase())>=0;
};

function initialize() {
    var id = $("#id").attr("value");
    if(id != null) {
        $.getJSON("/api/locations/" + id, function(data) {
            document.title = data.name + " - " + document.title;
            latlng = new google.maps.LatLng(data.latitude, data.longitude); 
            var myOptions = {
                zoom: 16,
                center: latlng,
                mapTypeId: google.maps.MapTypeId.HYBRID
            };

            var map = new google.maps.Map(document.getElementById("map_canvas"), myOptions);
            var centerIcon = new google.maps.MarkerImage("/images/blue-dot.png");
            
            var marker = new google.maps.Marker({
                position: latlng,
                icon: centerIcon,
                map: map,
                title: data.name
            });
            
            $.getJSON("/api/locations/close_to/" + data.id, function(data) {
                var infoWindow = new google.maps.InfoWindow();
                var bathrooms = data.bathrooms;

                for(var i = 0; i < bathrooms.length; i++) {
                    var latlng = new google.maps.LatLng(bathrooms[i].latitude, bathrooms[i].longitude);
                    content = bathrooms[i].name;
                    marker = new google.maps.Marker({
                        position: latlng,
                        map: map,
                        html: '<a href="/locations/' + bathrooms[i].slug + '">' + bathrooms[i].name + '<br>Score: ' + bathrooms[i].score + '</a>'

                    });
                    google.maps.event.addListener(marker, 'click', function() {
                        console.log(content[i]);
                        infoWindow.setContent(this.html);
                        infoWindow.open(map,this);
                    });
                }
            });
        });
        

    }
}

$(document).ready(function() {
	$("#vote").hide().fadeIn('medium');
	$("#submit").hide().fadeIn('medium');
	$("#submitoptional").hide().fadeIn('medium');
	$("#submitcommentbutton").hide().fadeIn('medium');
	$("#result").hide().fadeIn('medium');
    $("#optionalvote").hide();

    $("select.nav").change(function() {
        var id = $("select.nav option:selected").val();
        var url = "/locations/" + id;
        window.location = url;
    });

    $("#show_search").click(function() {
        $("#show_search").hide("fast");
        $("input").show("slow");
    });

    $("#find_close").click(function() {
        if(navigator.geolocation) {
            var timeoutVal = 10 * 1000 * 1000;
            navigator.geolocation.getCurrentPosition(
                      displayPosition, 
                      displayError,
                      { enableHighAccuracy: true, timeout: timeoutVal, maximumAge: 0 }
            );
        }
    });

	$("#submit").click(function() {
        var status_message_text; 
        var status_message_class;
        history = JSON.parse($.cookie("main_history"));
        if(!history) history = {};

		var id = $("#id").attr("value");
        var current_time = Date.parse(new Date()) / 1000;
        var rating = $('input[name=rating]:checked', '#vote_main').val();

        if(!rating) {
            status_message_text = "You forgot to pick a rating!";
            status_message_class = "warning";
            showStatusMessage($("#vote"), status_message_text, status_message_class);
        } else if(history[id] - current_time > 0) {
            console.log("You've already voted today for this location!");
            status_message_text = "You already voted today!";
            status_message_class = "warning";
            showStatusMessage($("#vote"), status_message_text, status_message_class);
            $("#vote").slideUp('fast');
            $("#optionalvote").delay(1000).slideDown('fast');
        } else {
            var data_to_send = JSON.stringify({
                "rating": rating
            });
            
            var exp_time = current_time + COOLDOWN;
            history[id] = exp_time;
            $.cookie("main_history", JSON.stringify(history), {expires: 1});

            $.ajax({
                type: "PUT",
                url: "/api/locations/vote_main/" + id,
                dataType: "json",
                data: data_to_send,
                error: function(jqXHR, textStatus, errorThrown){
                            console.log('error: ' + textStatus + " " + errorThrown + " " + jqXHR.responseText);
                            status_message_text = "Something went terribly wrong!";
                            status_message_class = "error";
                            showStatusMessage($("#vote"), status_message_text, status_message_class);
                        },
                success: function(msg) {
                        switch(msg.status) {
                            case "200":
                                console.log("Looks good to me!");
                                $.getJSON("/api/locations/" + id, function(data) {
                                    $("#main_score").html(data.score);
                                    $("#main_votes").html(data.votes);
                                });
                                status_message_text = "Vote recorded!";
                                status_message_class = "success";
                                break;
                            case "400":
                                status_message_text = "Something went wrong!";
                                status_message_class = "error";
                                console.log("Something went wrong :(");
                                break;
                            default:
                                console.log("default");
                                break;
                        }
                    showStatusMessage($("#vote"), status_message_text, status_message_class);
                }
            });
                $("#vote").slideUp('fast');
            $("#optionalvote").delay(1000).slideDown('fast');
        }
    });

    $("#submitoptional").click(function() {
        var optional_history = JSON.parse($.cookie("optional_history"));
        if(!optional_history) optional_history = {};

		var id = $("#id").attr("value");
        var current_time = Date.parse(new Date()) / 1000;

        var smellRating;
        var cleanRating;
        var crowdRating;
        
        smellRating = $("input[name=smell]:checked").val();
        cleanRating = $("input[name=clean]:checked").val();
        crowdRating = $("input[name=crowd]:checked").val();
        
        if(!smellRating && !cleanRating && !crowdRating) {
            status_message_text = "You forgot to pick a rating!";
            status_message_class = "warning";
            showStatusMessage($("#optionalvote"), status_message_text, status_message_class);
        }
        else if(optional_history[id] - current_time > 0) {
            console.log("You've already voted today for this location!");
            status_message_text = "You already voted today!";
            status_message_class = "warning";
            showStatusMessage($("#vote"), status_message_text, status_message_class);
            $("#optionalvote").slideUp('fast');
        } else {
            var exp_time = current_time + COOLDOWN;
            optional_history[id] = exp_time;
            $.cookie("optional_history", JSON.stringify(optional_history), {expires: 1});

            
            var data_to_send = JSON.stringify({
                "smelliness": smellRating,
                "cleanliness": cleanRating,
                "crowdedness": crowdRating
            });

            $.ajax({
                type: "PUT",
                url: "/api/locations/vote_extra/" + id,
                dataType: "json",
                data: data_to_send,
                error: function(jqXHR, textStatus, errorThrown){
                            status_message_text = "Something went terribly wrong!";
                            status_message_class = "error";
                            showStatusMessage($("#optionalvote"), status_message_text, status_message_class);
                                    },
                success: function(msg) {
                    switch(msg.status) {
                        case "200":
                            console.log("Looks good to me!");
                            $.getJSON("/api/locations/" + id, function(data) {
                                $("#smell_score").html(data.smell_score);
                                $("#clean_score").html(data.clean_score);
                                $("#crowd_score").html(data.crowd_score);
                            });
                            status_message_text = "Votes recorded!";
                            status_message_class = "success";
                            break;
                        case "400":
                            console.log("Something went wrong :(");
                            status_message_text = "Something went wrong :(";
                            status_message_class = "error"
                            break;
                        default:
                            console.log("default");
                            break;
                    }
                    showStatusMessage($("#vote"), status_message_text, status_message_class);
                    $("#optionalvote").slideUp('fast');
                }
            });
         }
    });
    

	$("#submitcomment").click(function() {
		var id = $("#id").attr("value");
		if(!$("#commentname").val() || !$("#commentbody").val()) {
			alert("Please fill out both fields!");
		} else {
			var commentname = $("#commentname").val();
			var commentbody = $("#commentbody").val();
		    
            var data_to_send = JSON.stringify({
                "author": commentname,
                "comment": commentbody
            });

			$.post("/api/comments/" + id, data_to_send, function(data) {
                var msg = JSON.parse(data);
                switch(msg.status) {
                    case "200":
                        console.log("Looks good to me!");
                        $.getJSON("/api/comments/" + id, function(data) {
                            $("#commentname").val('');
                            $("#commentbody").val('');

                            var comment = data.comments[0];
                            var new_comment = $('<div class="comment">' +
                                                   '<div class="slug"><span class="blue commentname">' + comment.author + '</span><span class="orange"> says...</span></div>' +
                                                   '<div><p class="blue commenttext">' + comment.comment + '</p></div>' +
                                                   '</div>');
                            new_comment.hide();
                            $("#commentsform").after(new_comment);
                            new_comment.slideDown('fast');

                           
                        });
                        break;
                    case "400":
                        console.log("Something went wrong :(");
                        break;
                    default:
                        console.log("default");
                        break;
                }
            });
        }
    });
    
    $("#search")
          .change( function () {

            var filter = $(this).val();
            if(filter) {
              // this finds all links in a list that contain the input,
              // and hide the ones not containing the input while showing the ones that do
              $($("#ol_nav")).find("a:not(:Contains(" + filter + "))").parent().hide();
              $($("#ol_nav")).find("a:Contains(" + filter + ")").parent().show();
            } else {
              $($("#ol_nav")).find("li").show();
            }
            return false;
          })
        .keyup( function () {
            // fire the above change event after every letter
            $(this).change();
        });
});


function displayPosition(position) {
            var latitude = position.coords.latitude;
            var longitude = position.coords.longitude;

            $.getJSON("/api/locations/close", {latitude: latitude, longitude: longitude}).done( function(data) {
                console.log(data);
                if(data) {
                    var url = "/index.php?id=" + data.id;
                    window.location = url;
                } else {
                    alert("Something went wrong! :(");
                }
            });
}

function displayError(error) {

}

function formToJson() {
return JSON.stringify({
        "latitude": $('#admin_latitude').val(),
        "longitude": $('#admin_longitude').val()
    });
}

function showStatusMessage(element, status_message_text, status_message_class) {
    var status_message = $("<span>" + status_message_text + "</span>").attr('class', 'status_message ' + status_message_class).hide();
    element.before(status_message);
    status_message.slideDown('fast', function() {
        status_message.delay(1000).slideUp('fast');
    });
}

