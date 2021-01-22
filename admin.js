/*
    Page admin.php -> Administration du jeu
*/

var lastStatus = "**";

// Machine à état
const PHASE_INIT = 0;
const PHASE_JOUEURS = 1;
const PHASE_START = 2;
const PHASE_MANCHE = 3;
const PHASE_DONNE = 4;
const PHASE_ENCHERES = 5;
const PHASE_JEU = 6;
const PHASE_MARQUE = 7;
const PHASE_FIN = 8;
var phaseTxt = ['INIT','JOUEURS','START','MANCHE','DONNE','ENCHERES','JEU','MARQUE','FIN'];

var couleursAbr = ['T','K','C','P'];
var couleurs = ["Trefle","Carreau","Coeur","Pique"];
var Places = ['N','E','S','O'];

function sendCmd(theCmd='') {
	var pId = getpId();

	var cmd = "cmd.php?c=" + theCmd;
	$.ajax({
		url: cmd,
		type: "GET",
		success: function(data) {
			$('#commands').html(data);
		},
		error: function() {
			$('#commands').html('Command error : '+cmd);
		}
	});
}

function getClients() {
	var cmd = "?o=clients";
	$.ajax({
		url: cmd,
		type: "GET",
        dataType : "json",
		success: function(data) {
			
            var s = "";
            
            //console.log(data)
            //$('#log').html(data[0]);
            for(var i=0; i<data.length; i++){
                var d = data[i];
                s = s + "<ul class='clientlist'><li class='clientip'>" + d.ip + "</li>" +
                    "<li class='clientname'>" + d.nom + " : "+d.place+"</li>"+
                    "<li class='clientid'>" + d.pId + "</li>"+
                    "<li class='clientaction' onClick=delClient('"+d.pId+"')>DEL</li>" +
                    "</ul>";
                
                
                
            }
            $("#clients").html(s);
		},
		error: function() {
			$('#log').html('Command error : '+cmd);
		}
	});    
}

function delClient(id) {
	var cmd = "?o=delclient&p="+id;
    console.log("cmd="+cmd);
	$.ajax({
		url: cmd,
		type: "GET",
		success: function(data) {
			$('#log').html('Return : '+data);
		},
		error: function() {
			$('#log').html('Command error : '+cmd);
		}
	});    
}


function getState() {
	var r="?";
	
	setTimeout( function() {
		update();
        getClients();
		getState();
	}, 2000);
}

function getpId() {
	var rPlayers = document.getElementsByName('pId');

	for (var i = 0; i<4; i++) {
	  if ($("#pId"+(i+1)).prop("checked")) {
		return i+1;
	  }
	}
}

function update() {
	var pId = getpId();
	var r="";
	$.ajax({
		url: "cmd.php?c=STATE&p=" + pId,
		type: "GET",
		success: function(data) {
			
			
			var s = data.toString;
			var rSplit = data.split(/[:]+/);
			var cards="";
			var sstat = "";
			
			for(i=0; i<rSplit.length; i++) {
				r = r+'"'+rSplit[i]+'" - ';
			}
			
			$('#state').html(r);
			if (rSplit[0]=="200") {
                
            }
        },
        error: function() {
			$('#state').html('Command error : '+cmd);
		}
	});

}

getState();