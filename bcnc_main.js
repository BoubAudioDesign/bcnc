/*

	BCNC : Belotte coinchée (Non coinchée !)


*/
var lastDeck = "**";
var lastTapis = "**";
var lastStatus = "**";
var lastPlis = "**";
var lastEncheres = "**";

var selContratLevel = 0;
var selContratCouleur = "*";

var PH=0;
var wait = false;
var pId = -1;
var noDrop = false;

const MODE_DEBUG = true;

// Machine à état

const PHASE_INIT = 0;
const PHASE_JOUEURS = 1;
const PHASE_START = 2;
const PHASE_MANCHE = 3;
const PHASE_DONNE = 4;
const PHASE_ENCHERES = 5;
const PHASE_ENCHERES_FIN = 55;
const PHASE_JEU = 6;
const PHASE_JEU_PAUSE = 65;
const PHASE_MARQUE = 7;
const PHASE_FIN = 8;
var phaseTxt = ['INIT','JOUEURS','START','MANCHE','DONNE','ENCHERES','JEU','MARQUE','FIN'];

var couleursAbr = ['T','K','C','P'];
var couleurs = ["Trefle","Carreau","Coeur","Pique"];
var Places = ['N','E','S','O'];

getState(); // Lancer la mise à jour automatique.

function pose(carte) {
	if (! noDrop) { // Test si on peut (doit) poser la carte.
        if (parseInt(PH) != PHASE_JEU) {
            $( "#dialog" ).dialog( "option", "title", "Erreur !" );
            $("#dialog").html("Impossible en dehors de la phase de jeu !");
            $( "#dialog" ).dialog( "open" );
        }

        if (wait && 1==2) {
            $( "#dialog" ).dialog( "option", "title", "Erreur !" );
            $("#dialog").html("Une carte est déjà posée !<br/>Attendre un peu...");
            $( "#dialog" ).dialog( "open" );

        } else {
					if ( ! $("#dcard_"+carte).hasClass("nogood")) {
            wait = true;
            //var pId = getpId();
            var cmd = "cmd.php?c=pose&p="+carte+";"+pId; // $('#pId').val()
            $.ajax({
                url: cmd,
                type: "GET",
                success: function(data) {
                    $('#log').html( $('#log').html() + "<br/>"+data);
                    $('#commands').html('---'+data);
                    //updateTapis();
                },
                error: function() {
                    $('#commands').html('Command error : '+cmd);
                }
            });
					} else {
						$( "#dialog" ).dialog( "option", "title", "Erreur !" );
            $("#dialog").html("Cette carte ne peut pas être jouée maintenant !");
            $( "#dialog" ).dialog( "open" );

					}
        }
        //$('#commands').html(getCmd(cmd));
    } else {
        noDrop = false; // Réinit
    }
}

function forceRefresh() {
	lastDeck="";
	lastTapis="";
}

function sendCmd(theCmd='') {
	var pId = getpId();
	if (theCmd == '') {
		// Prendre la commande du formulaire.
		theCmd = $("#cmd").val();
	}
	var cmd = "cmd.php?c=" + theCmd;
    if (debugID >0){
        cmd = cmd + "&p=" + debugID;
    }
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

function anValide() {
	if ( (selContratLevel>0 && selContratCouleur != '') || selContratLevel==70) {
		sendCmd('ENCHERE:'+selContratCouleur+";"+selContratLevel+";"+pId);
	}
}

function goCoupe() {
    sendCmd('DISTRIB');
}


function getState() {
	var r="?";

	setTimeout( function() {
		update();
		getState();
	}, 2000);
}

function update() {
	//var pId = getpId();
	var r="";
	$.ajax({
		url: "cmd.php?c=STATE&p=" + debugID, //pId, //
		type: "GET",
		success: function(data) {


			var s = data.toString;
			var rSplit = data.split(/[:]+/);
			var cards="";
			var sstat = "";

			for(i=0; i<rSplit.length; i++) {
				r = r+'"'+rSplit[i]+'" - ';
			}

			$('#commands').html(r);
			if (rSplit[0]=="200") {
				//$('#state').html('--');
				deckCards = rSplit[1].trim().toLowerCase();

				// Actualiser status

				sstat = rSplit[3].trim();
				if (sstat != lastStatus) {
					wait = false;
                    var s="";
					selements = sstat.split(',');
					for (i=0; i<selements.length; i++) {
						var key = selements[i].substring(0,2);
						var val = selements[i].substring(2);
						s = val; //s + key + "=" + val+" / ";
						window[key] = val;
						//console.log('Global state >> '+key + ' = '+s);
					}

					var scores = ST.split('-');
                    $("#scores").html( scores[0] + ' / ' + scores[1]);

                    if (PL != "" ) {
                        $("#selPlayer").addClass("hide");
                        pId = Places.indexOf(PL);
                    } else {
                        s = "/bcnc/rejoindre.php";
                        if (debugID) {
                            s = s + '?d='+debugID;
                        }
                        window.location = s;
                    }

                    //console.log("PL="+PL+ "- pid="+pId);
					if (parseInt(PO)-1==pId) {

						if (TC<4) {
							if ( parseInt(PH) == PHASE_JEU){
								s = "C'est à toi de jouer.";
							} else if ( parseInt(PH) == PHASE_ENCHERES ){
								s = "Fais ton enchère.";
							}
							mclass="joue";
						}
					} else {
						s = "On attend <b>"+Places[parseInt(PO)-1]+"</b>";
						mclass = "attente";
					}

                    updateCentre(parseInt(TK), AT, CO, PR);

					switch (parseInt(PH)) {
						case PHASE_ENCHERES:
							$("#SelAnnonces").removeClass("hide");
							$("#SelAnnonces").addClass("clearfix");
							break;
						case PHASE_JEU:
							$("#SelAnnonces").addClass("hide");
							$("#SelAnnonces").removeClass("clearfix");
							break;
					}

					//$('#state').html(s);
					lastStatus = sstat;

					$('#state').html(s);
				}

				//	Actualiser la main

				if (lastDeck != deckCards) {

					var mainImg = "";
					var list = "";
					var l = "[" + deckCards + "]";

					for (i=0; i<deckCards.length/2;i++) {
						var idx = i*2;
						//list = list + i + ':'+cards.substring(idx,idx+2) + ' / ';
						var filename = deckCards.substring(i*2,i*2+2);
						var cclass = "card";
						var oc = "";
						var couleur = filename.substring(1,0);

						oc = 'onClick=pose(\''+filename+'\')';
						var alt = filename.substring(2,1).toUpperCase()+filename.substring(1,0).toUpperCase();
						//mainImg = mainImg + '<div class="'+cclass+'"><img src="images/Cartes/j1/' + filename+'.png" '+oc+'  alt="'+alt+'"/></div>'+"\n";
						mainImg = mainImg + '<li class="'+cclass+'" id="dcard_' + filename + '"><img src="images/Cartes/j1/' + filename+'.png" '+oc+'  alt="'+alt+'"/></li>'+"\n";
					}

					mainImg = '<ul id="sortable">' +mainImg + '</ul>';
					$('#deck').html(mainImg);
          // Permettre le classement des cartes - Invalider le drop en cas de mouvement.
          $( function() {
              $( "#sortable" ).sortable({
                  update: function(event, ui) {
                      //alert('Updated')
                      noDrop = true;
                  }
              });
              $( "#sortable" ).disableSelection();
          } );
			    lastDeck = deckCards;
        }


				//	Actualiser le tapis


				cards = rSplit[2].trim().toLowerCase();
				if (lastTapis != cards) {

					var list = "";
					var tapisImg = "";
					var s="";
					var places = ["n","e","s","o"];
					for (i=0; i<4; i++) {
						var place = '#place_' + places[i];
						$(place).html('<img class="cardBorder" src="images/Cartes/misc/SH.png"/>');
					}
					for (i=0; i<cards.length/3;i++) {
						var idx = i*3;
						list = list + i + ':'+cards.substring(idx,idx+3) + ' / ';
						var filename = cards.substring(idx,idx+2);
						var place = '#place_'+cards.substring(idx+2,idx+3);
						//s = s + place + "/ ";

						$(place).html('<img class="cardBorder" src="images/Cartes/j1/' + filename+'.png"/>');

					}

					// dévalider les cartes qui ne peuvent pas être jouées.
					var checkCouleur = false;
					var checkAtout = false;
					var l = "-deck : ";
					if (PC != '') { // Couleur demandée (Première Couleur)

						for (i=0; i<deckCards.length/2;i++) {
							var filename = deckCards.substring(i*2,i*2+2);
							var couleur = filename.substring(1,0);

							l = l+"c="+couleur+" ";

							if ( PC.toUpperCase() == couleur.toUpperCase() ) {
								checkCouleur = true; // La couleur demandée est présente
							}
							if ( couleur.toUpperCase().trim() == AT) {
									checkAtout = true; // L'atout est présent
							}
						}
						console.log(l);
					}

					var l = "[" + deckCards + "] (Check couleur/atout "+checkCouleur+" / "+checkAtout+")";
					for (i=0; i<deckCards.length/2;i++) {
						var idx = i*2;
						//list = list + i + ':'+cards.substring(idx,idx+2) + ' / ';
						var filename = deckCards.substring(i*2,i*2+2);
						var cclass = "card";
						var oc = "";
						var couleur = filename.substring(1,0);

						var cPlace = ""; //cards.substring(i*3,i*3+2);	// A qui est la carte ?
						var p = partenaire(PL, MA); 					// Est-ce mon partenaire qui est maitre ?


						l = l + couleur + " (" + p + ")";

            if (
                ( couleur.toUpperCase() == PC.toUpperCase() && PC !='' && checkCouleur)
                ||
                ( couleur.toUpperCase() == AT && !checkCouleur )
                ||
                (PC !='' && !checkCouleur && !checkAtout) // Ni couleur, ni atout
                ||
                (PC=='') // Première carte
                ||
                ( p && !checkCouleur ) // Partenaire maitre et pas la couleur

               ) {
							//oc = 'onClick=pose(\''+filename+'\')';
							l = l+"OK ";
							$("#dcard_"+filename).removeClass("nogood");
						} else {
							l = l+"NG ";
							$("#dcard_"+filename).addClass("nogood");
						}

					}
					console.log(l);

					updateJoueur(PO);
					//$('#tapis').html(tapisImg);
					//--$('#state').html(list);
					lastTapis = cards;
				}

				switch (parseInt(PH)) {
                    case PHASE_JEU:
						//$("#bDonne").addClass("hide");
                        plis = rSplit[4].trim().toLowerCase();
						if (lastPlis != plis){
							updatePlis(plis, false);
							lastPlis = plis;
						}
						break;
                    case PHASE_MARQUE:
                        plis = rSplit[4].trim().toLowerCase();
						if (lastPlis != plis){
							updatePlis(plis, true);
							lastPlis = plis;
						}
                        // Afficher le bouton Donne
                        $("#deck").html('<div class="bDonne" id="bDonne" onClick="goCoupe()">Couper</div>');
                        break;
					case PHASE_ENCHERES:
						encheres = rSplit[4].trim().toLowerCase();
						if (lastEncheres != encheres) {
							updateCLevels(AT, CO);
							updateEncheresList(encheres);
							lastEncheres = encheres;
						}
            $("#bDonne").addClass("hide");
				}

			}
		},
		error: function() {
			$('#state').html('Command error : '+cmd);
		}
	});

}

function partenaire(moi, cPlace) {
	var moiOE = Places.indexOf(moi) % 2;
	var placeOE = Places.indexOf(cPlace) % 2;
	var r = false;

	if (moiOE == placeOE) {
		r = true;
	}

	return r;
}

function updatePlis(plis, allplis) {
	const regex = /([NOSE]).([a-z1-9]+)/ig;
	//const str = `N.CANC8ECJSC7O;S.P7NPTEPASP8O;E.KQNKAEK9SK8O;O.T8NT7ETTSTAO;N.CTNCQEPJSCKO;O.PQNK7ET9SPKO`;
	var m;
	var s = "";
    var l = "";
    var plisCount = (plis.length+1) / 15;
	var idPlis = 1;
	$('#pTitre').html("Dernier pli");
    $("#plis").html("");
	while ((m = regex.exec(plis)) !== null) {
		// This is necessary to avoid infinite loops with zero-width matches
		if (m.index === regex.lastIndex) {
			regex.lastIndex++;
		}
        if (idPlis == plisCount || allplis) {
            l = '<div class="plisLigne"><div class="plisIdx">' + idPlis + '</div><div class="plisPlace">'+m[1].toUpperCase()+'</div><div class="plisCartes">';
            // The result can be accessed through the `m`-variable.

            l = l + getCartesImages(m[2],'lcard');
            m.forEach((match, groupIndex) => {

            });
            l = l + '</div></div>';
            $("#plis").prepend(l);
        }
		idPlis++;
	}
	//$("#plis").append(plisCount);

}

/*

    Mise à jour du milieu de table :

        - Bouchon (Premier joueur)
        - Début de partie ou réinit manche
        - Passage à la donne suivante si besoin

*/
function updateCentre(currentPlayer, at, co , pr) {
    for(var i=0; i<4; i++){
        $("#tc"+(i+1)).html(Places[i]);
    }
    if (currentPlayer != 0) {
        $("#tc"+currentPlayer).html('<img src="images/decaps.png"/>');
    }
    if (co>70 && at != "" && pr!="-1"  ) { // && (parseInt(pr) >= 0)
        $("#tcC").html(co + ' <img src="images/Cartes/misc/' + at +'.png" height="16px"/> / '+ pr );
    }
}

function updateEncheresList(encheres) {
	const regex = /([NESO])([*TKCP])([0-9]{1,3})/ig;
	//Modèle : `N.CANC8ECJSC7O;S.P7NPTEPASP8O;E.KQNKAEK9SK8O;O.T8NT7ETTSTAO;N.CTNCQEPJSCKO;O.PQNK7ET9SPKO`;
	var m;
	var s = "";
	var idEnchere = 1;
	$('#pTitre').html("ENCHERES");
	while ((m = regex.exec(encheres)) !== null) {
		// This is necessary to avoid infinite loops with zero-width matches
		if (m.index === regex.lastIndex) {
			regex.lastIndex++;
		}
		s = s + '<div class="enLigne"><div class="enIdx">' + idEnchere + '</div>'
			+ '<div class="enPlace">'+m[1].toUpperCase()+'</div>';
		if (m[3]>0) {
			if (m[3]==170) {
				var level = "Capot";
			} else {
				var level = m[3];
			}
			s = s + '<div class="enCouleur"><img src="images/Cartes/misc/'+m[2].toUpperCase()+'.png" height="16px"/></div>'
				+ '<div class="enLevel">'+level+'</div>';
		} else {
			s = s + '<div class="enCouleur">&nbsp;</div><div class="enLevel">Passe</div>';
		}
		s = s + '</div>';

		idEnchere++;
	}
	$("#plis").html(s);
}

function getCartesImages(cartes, imgclass) {
	const regex = /([A-Z1-9]{2})([NOSE])/ig;
	//const str = `CANC8ECJSC7O`;
	var m;
	var s = "";
	while ((m = regex.exec(cartes)) !== null) {
		if (m.index === regex.lastIndex) {
			regex.lastIndex++;
		}
		s = s + '<img class="' + imgclass + '" src="images/Cartes/j1/' + m[1].toLowerCase() + '.png"/>';
	}

	return s;
}

var dText = $('#messages');
function ping() {
	setTimeout( function() {
		$.ajax({
			url : "ping.php?id=" + $('#pId').val(),
			type : "GET",
			async: true,
			contentType: 'text/plain',
			success : function(data) {
				//console.log(data);
				dText.html(data);
				var rSplit = datat.split(":");
			},
			error : function() {
				dText.html('error');
			}
		});

		ping();

	}, 5000);

}

function getpId() {
	/*
    var rPlayers = document.getElementsByName('ppId');


    for (var i = 0; i<4; i++) {
	  if ($("#ppId"+(i+1)).prop("checked")) {
		return i+1;
	  }
	}
    */
    return pId;
}
function updateJoueur(position) {
	$('#pId'+position).prop("checked", true);
}

function selContrat(id) {
	var selector = "#anC"+id;
	for (i=7; i<170; i++) {
		var s= "#anC"+(i*10);
		if (s==selector) {
			$(s).addClass("anSelected");
			selContratLevel = id;
		} else {
			$(s).removeClass("anSelected");
		}
	}
}

function selCouleur(id) {
	var selector = "#anC"+id;
	for (i=0; i<4; i++) {
		var s = "#anC" + couleursAbr[i];
		if (id==couleursAbr[i]) {
			$(s).addClass("anSelected");
			selContratCouleur = id;
		} else {
			$(s).removeClass("anSelected");
		}
	}
	//updateCLevels();
}

function updateCLevels (currentCouleur, currentLevel) {
	var _label = "";
	var _class = "";
	if (currentCouleur == "") {
		var pond = 0;
	} else {
		var pond = couleursAbr.indexOf(currentCouleur);
	}
	var pondLevel = currentLevel * pond;

	//console.log('UPATE Contrat levels : Coul='+currentCouleur+' - Level='+currentLevel+ ' - PondLevel='+pondLevel);
	for (var i=0; i<10; i++) {
		var id = '#anC'+((i+7)*10);
		if ($(id).html == 'PASSE' ) {
			_level = 0;
		} else if ($(id).html == 'CAPOT') {
			_level = 170;
		} else {
			_level = parseInt($(id).html);
		}
		if (_level <= pondLevel) {
			$('#anC'+((i+7)*10)).addClass("anNotAvailable");
		} else {
			$('#anC'+((i+7)*10)).removeClass("anNotAvailable");
		}
	}
}
