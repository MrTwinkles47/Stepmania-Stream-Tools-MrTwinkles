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
    
    switch (request_type){
		case "normal":
			request_type = '';
			break;
		case "random":
			request_type = '<img src="images/d205.png" class="type">';
			break;
		case "top":
			request_type = '<img src="images/top.png" class="type">';
			break;
		case "portal":
			request_type = '<img src="images/portal.png" class="type">';
			break;
		case "gitgud":
			request_type = '<img src="images/gitgud.png" class="type">';
            break;
        case "theusual":
			request_type = '<img src="images/theusual.png" class="type">';
            break;
        case "itg":
            request_type = '<img src="images/itg.png" class="type">';
            break;
        case "ddr":
            request_type = '<img src="images/ddr.png" class="type">';
            break;
        case "gimmick":
            request_type = '<img src="images/gimmick.png" class="type">';
            break;
        case "ben":
            request_type = '<img src="images/ben.png" class="type">';
            break;
        case "bgs":
            request_type = '<img src="images/bgs.png" class="type">';
            break;
        case "hkc":
            request_type = '<img src="images/hkc.png" class="type">';
            break;
        case "weeb":
            request_type = '<img src="images/weeb.png" class="type">';
            break;
        case "miku":
            request_type = '<img src="images/miku.png" class="type">';
            break;
		default:
			request_type = '<img src="images/d205.png" class="type">';;
			break;
	}

	switch (stepstype){
		case "dance-single":
            stepstype = '<img src="images/singles.png" class="dance single">';
			break;
		case "dance-double":
            stepstype = '<img src="images/doubles.png" class="dance double">';
			break;
		default:
			stepstype = "";
			break;
	}

    switch (difficulty){
        case "Beginner":
            difficulty = '<div class="difficulty beginner"></div>';
            break;
        case "Easy":
            difficulty = '<div class="difficulty easy"></div>';
            break;
        case "Medium":
            difficulty = '<div class="difficulty medium"></div>';
            break;
        case "Hard":
            difficulty = '<div class="difficulty hard"></div>';
            break;
        case "Challenge":
            difficulty = '<div class="difficulty challenge"></div>';
            break;
        case "Edit":
            difficulty = '<div class="difficulty edit"></div>';
            break;
        default:
            difficulty = '<div class="difficulty"></div>';
        break;
    }

    if(!stepstype){difficulty = "";}

	console.log("Adding request "+request_id);

    data = `<div class="songrow" style="display:none" id="request_${request_id}">
    <h2>${title}<h2a>${subtitle}</h2a></h2>
    <h3>${pack}</h3>
    <h4>${requestor}</h4>\n
    ${request_type}\n
    ${difficulty}\n
    ${stepstype}\n
    <img class="songrow-bg" src="${img}" />
    </div>
    `;
    if ($("#admin").html()){
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

function new_demand(array){
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
    
    switch (request_type){
		case "normal":
			request_type = '';
			break;
		case "random":
			request_type = '<img src="images/d205.png" class="type">';
			break;
		case "top":
			request_type = '<img src="images/top.png" class="type">';
			break;
		case "portal":
			request_type = '<img src="images/portal.png" class="type">';
			break;
		case "gitgud":
			request_type = '<img src="images/gitgud.png" class="type">';
            break;
        case "theusual":
			request_type = '<img src="images/theusual.png" class="type">';
            break;
        case "itg":
            request_type = '<img src="images/itg.png" class="type">';
            break;
        case "ddr":
            request_type = '<img src="images/ddr.png" class="type">';
            break;
        case "gimmick":
            request_type = '<img src="images/gimmick.png" class="type">';
            break;
        case "ben":
            request_type = '<img src="images/ben.png" class="type">';
            break;
        case "bgs":
            request_type = '<img src="images/bgs.png" class="type">';
            break;
        case "hkc":
            request_type = '<img src="images/hkc.png" class="type">';
            break;
        case "weeb":
            request_type = '<img src="images/weeb.png" class="type">';
            break;
        case "miku":
            request_type = '<img src="images/miku.png" class="type">';
            break;
		default:
			request_type = '<img src="images/d205.png" class="type">';;
			break;
	}

	switch (stepstype){
		case "dance-single":
            stepstype = '<img src="images/singles.png" class="dance single">';
			break;
		case "dance-double":
            stepstype = '<img src="images/doubles.png" class="dance double">';
			break;
		default:
			stepstype = "";
			break;
	}

    switch (difficulty){
        case "Beginner":
            difficulty = '<div class="difficulty beginner"></div>';
            break;
        case "Easy":
            difficulty = '<div class="difficulty easy"></div>';
            break;
        case "Medium":
            difficulty = '<div class="difficulty medium"></div>';
            break;
        case "Hard":
            difficulty = '<div class="difficulty hard"></div>';
            break;
        case "Challenge":
            difficulty = '<div class="difficulty challenge"></div>';
            break;
        case "Edit":
            difficulty = '<div class="difficulty edit"></div>';
            break;
        default:
            difficulty = '<div class="difficulty"></div>';
        break;
    }

    if(!stepstype){difficulty = "";}

	console.log("Upgrading request to demand "+request_id);

    data = `<div class="songrow" style="display:none" id="request_${request_id}">
    <h2>${title}<h2a>${subtitle}</h2a></h2>
    <h3>${pack}</h3>
    <h4>${requestor}</h4>\n
    ${request_type}\n
    ${difficulty}\n
    ${stepstype}\n
    <img class="songrow-bg" src="${img}" />
    </div>
    `;
    if ($("#admin").html()){
        data = data + `<div class=\"admindiv\" id=\"requestadmin_${request_id}\">
        <button class=\"adminbuttons\" style=\"margin-left:4vw; background-color:rgb(0, 128, 0);\" type=\"button\" onclick=\"MarkCompleted(${request_id})\">Mark Complete</button>\n
        <button class=\"adminbuttons\" style=\"background-color:rgb(153, 153, 0);\" type=\"button\" onclick=\"MarkSkipped(${request_id})\">Mark Skipped</button>
        <button class=\"adminbuttons\" style=\"margin-right:4vw; float:right; background-color:rgb(178, 34, 34);\" type=\"button\" onclick=\"MarkBanned(${request_id})\">Mark Banned</button>
        </div>`;
    }

    //"cancel" original request
    if( $("#request_"+request_id).length ){
        //console.log("Canceling request "+request_id);
        $("#request_"+request_id).slideUp(600);
        $("#requestadmin_"+request_id).slideUp(600);
        //$("#cancel")[0].play();
	}
        //append new demand to <top>
        //$("#lastid").html(request_id);
        $("#top").prepend(data);
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
            $("#request_"+request_id).removeAttr("style");
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
    security_key = $("#security_key").html();
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
    security_key = $("#security_key").html();
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
    security_key = $("#security_key").html();
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
security_key = $("#security_key").html();
broadcaster = $("#broadcaster").html();
url = `get_updates.php?security_key=${security_key}&broadcaster=${broadcaster}&id=${lastid}&oldid=${oldid}`;
    $.ajax({url: url, success: function(result){
		if(result){
			result = JSON.parse(result);
			if(result["requests"].length > 0){
				howmany = result["requests"].length;
				console.log(howmany+" new request(s)");
                                $.each(result["requests"], function( key, value ) {
                                	new_request(value);
				});
            }else if(result["demands"].length > 0){
				howmany = result["demands"].length;
				console.log(howmany+" new demand(s)");
                                $.each(result["demands"], function( key, value ) {
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