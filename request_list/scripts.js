$.urlParam = function(name){
    var results = new RegExp('[\?&]' + name + '=([^&#]*)').exec(window.location.href);
    if (results==null) {
        results = new RegExp('[\?&]' + name + '([^&#]*)').exec(window.location.href);
        if (results==null) {
            return "";
        }
    }
    return decodeURI(results[1]) || 0;
}

function new_request(array){
	request_id = array.id;
	song_id = array.song_id;
	requestor = array.requestor;
	request_time = array.request_time;
	request_type = array.request_type;
	stepstype = array.stepstype;
	difficulty = array.difficulty;
	title = array.title;
	subtitle = array.subtitle;
	artist = array.artist;
	pack = array.pack;
    img = array.img;
    background = array.background;

    if(img){
        img = `<img class="songrow-bg" src="${img}" />`;
    }else{
        img = "";
    }

    if(request_type){
        request_type = `<img src="${request_type}" class="type">`;
    }else{
        request_type = "";
    }

    if(stepstype){
        stepstype_split = stepstype.split('-');
        stepstype = `<img src="images/${stepstype_split[1]}s.png" class="${stepstype_split[0]} ${stepstype_split[1]}">`;
    }else{
        stepstype = "";
    }

    if(difficulty){
        difficulty = `<div class="difficulty ${difficulty}"></div>`;
    }else{
        difficulty = `<div class="difficulty"></div>`;
    }

    if(!stepstype){difficulty = "";}

	console.log("Adding request "+request_id);

    data = `<div class="songrow" style="${background};" id="request_${request_id}">
    <h2>${title}<h2a>${subtitle}</h2a></h2>
    <h3>${pack}</h3>
    <h4>${requestor}</h4>\n
    ${request_type}\n
    ${difficulty}\n
    ${stepstype}\n
    ${img}\n
    <span id="request_${request_id}_time" style="display:none;">${request_time}</span>\n
    </div>
    `;
    console.log("admin= "+$.urlParam('admin'));
    if ($.urlParam('admin') === 0){
        data = data + `<div class=\"admindiv\" id=\"requestadmin_${request_id}\">
        <button class=\"adminbuttons\" style=\"margin-left:4vw; background-color:rgb(0, 128, 0);\" type=\"button\" onclick=\"MarkCompleted(${request_id})\">Mark Complete</button>\n
        <button class=\"adminbuttons\" style=\"background-color:rgb(153, 153, 0);\" type=\"button\" onclick=\"MarkSkipped(${request_id})\">Mark Skipped</button>
        <button class=\"adminbuttons\" style=\"margin-right:4vw; float:right; background-color:rgb(178, 34, 34);\" type=\"button\" onclick=\"MarkBanned(${request_id})\">Mark Banned</button>
        </div>`;
    }

        $("#lastid").html(request_id);
        $("#middle").prepend(data);
        $("#request_"+request_id).slideDown(600);
        $("#request_"+request_id).first().css("opacity", "0");
        $("#request_"+request_id).first().css("animation", "wiggle 1.5s forwards");
        $("#new")[0].play();

}

function new_cancel(id){
	request_id = id;
	if( $("#request_"+request_id).length ){
        console.log("Canceling request "+request_id);
        $("#request_"+request_id).slideUp(600, function() {this.remove(); });
        $("#requestadmin_"+request_id).slideUp(600, function() {this.remove(); });
        $("#cancel")[0].play();
	}
}

function completion(id){
        request_id = id;
	if( $("#request_"+request_id).length ){
		if( $("#request_"+request_id).hasClass("completed") ){
		}else{
            console.log("Completing request "+request_id);
            //$("#request_"+request_id).removeAttr("style");
            $("#request_"+request_id).first().css("opacity", "");
            $("#request_"+request_id).first().css("animation", "");
            $("#request_"+request_id).addClass("completed");
            $("#requestadmin_"+request_id).slideUp(600, function() {this.remove(); });
			$("#request_"+request_id).append("<img src=\"images/check.png\" class=\"check\" />");
		}
	}
}

function skipped(id){
        request_id = id;
	if( $("#request_"+request_id).length ){
        console.log("Skipping request "+request_id);
        $("#request_"+request_id).slideUp(600, function() {this.remove(); });
        $("#requestadmin_"+request_id).slideUp(600, function() {this.remove(); });
        $("#cancel")[0].play();
	}
}

function MarkCompleted(id){
    security_key = $.urlParam('security_key');
    url = `get_updates.php?security_key=${security_key}&func=MarkCompleted&id=${id}`;
        $.ajax({url: url, success: function(result){
            if(result){
                result = JSON.parse(result);
                if(result["requestsupdated"] > 0){
                    console.log(`Request ${id} marked as Completed`);
                    refresh_data();
                    }};
                }
        });
    }
    
function MarkSkipped(id){
    security_key = $.urlParam('security_key');
    url = `get_updates.php?security_key=${security_key}&func=MarkSkipped&id=${id}`;
    $.ajax({url: url, success: function(result){
        if(result){
            result = JSON.parse(result);
            if(result["requestsupdated"] > 0){
                console.log(`Request ${id} marked as Skipped`);
                refresh_data();
                }};
            }
        });
}

function MarkBanned(id){
    security_key = $.urlParam('security_key');
    url = `get_updates.php?security_key=${security_key}&func=MarkBanned&id=${id}`;
    $.ajax({url: url, success: function(result){
        if(result){
            result = JSON.parse(result);
            if(result["requestsupdated"] > 0){
                console.log(`Song from request ${id} marked as Banned`);
                refresh_data();
                }};
            }
        });
}

function refresh_data(){
lastid = $("#lastid").html();
oldid = $("#oldid").html();
security_key = $.urlParam('security_key');
broadcaster = $.urlParam('broadcaster');
url = `get_updates.php?security_key=${security_key}&broadcaster=${broadcaster}&id=${lastid}&oldid=${oldid}`;
    $.ajax({url: url, success: function(result){
		if(result = JSON.parse(result)){
			if(result["requests"].length > 0){
				howmany = result["requests"].length;
				console.log(howmany+" new request(s)");
                                $.each(result["requests"], function( key, value ) {
                                	new_request(value);
				});
			}else{
				console.log("No new requests");
			}
                        if(result["cancels"].length > 0){
                                $.each(result["cancels"], function( key, value ) {
                                        new_cancel(value);
                                });
                        }
                        if(result["completions"].length > 0){
                                $.each(result["completions"], function( key, value ) {
                                        completion(value);
                                });
						}
						if(result["skips"].length > 0){
                                $.each(result["skips"], function( key, value ) {
                                        skipped(value);
                                }); 
                        }

		}else{
			console.log("Json error downloading data");
		}
    }});
}

window.setInterval(function(){
	refresh_data();
}, 5000);    

$(function() {refresh_data();});