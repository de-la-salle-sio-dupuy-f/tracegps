<?php
// Projet TraceGPS - services web
// fichier :  api/services/ChangerDeMdp.php
// Dernière mise à jour : 3/7/2019 par Jim

// Rôle : ce service permet à un utilisateur de changer son mot de passe
// Le service web doit recevoir 5 paramètres :
//     pseudo : le pseudo de l'utilisateur
//     mdp : l'ancien mot de passe hashé en sha1
//     nouveauMdp : le nouveau mot de passe
//     confirmationMdp : la confirmation du nouveau mot de passe
//     lang : le langage du flux de données retourné ("xml" ou "json") ; "xml" par défaut si le paramètre est absent ou incorrect
// Le service retourne un flux de données XML ou JSON contenant un compte-rendu d'exécution

// Les paramètres doivent être passés par la méthode GET :
//     http://<hébergeur>/tracegps/api/ChangerDeMdppseudo=europa&mdp=13e3668bbee30b004380052b086457b014504b3e&nouveauMdp=123&confirmationMdp=123&lang=xml

// connexion du serveur web à la base MySQL
$dao = new DAO();

$nouveauPointDeTrace = null;

// Récupération des données transmises
$pseudo = ( empty($this->request['pseudo'])) ? "" : $this->request['pseudo'];
$mdpSha1 = ( empty($this->request['mdp'])) ? "" : $this->request['mdp'];
$idTrace = ( empty($this->request['idTrace'])) ? "" : $this->request['idTrace'];
$dateHeure = ( empty($this->request['dateHeure'])) ? "" : $this->request['dateHeure'];
$latitude  = ( empty($this->request['latitude'])) ? "" : $this->request['latitude'];
$longitude = ( empty($this->request['longitude'])) ? "" : $this->request['longitude'];
$altitude = ( empty($this->request['altitude'])) ? "" : $this->request['altitude'];
$rythmeCardio  = ( empty($this->request['rythmeCardio'])) ? "" : $this->request['rythmeCardio'];
$lang = ( empty($this->request['lang'])) ? "" : $this->request['lang'];

// "xml" par défaut si le paramètre lang est absent ou incorrect
if ($lang != "json") $lang = "xml";

// La méthode HTTP utilisée doit être GET
if ($this->getMethodeRequete() != "GET")
{	$msg = "Erreur : méthode HTTP incorrecte.";
    $code_reponse = 406;
}
else 
{
    // Les paramètres doivent être présents
    if ( $pseudo == "" || $mdpSha1 == "" || $idTrace == "" || $dateHeure == "" || $latitude == "" || $longitude == "" || $altitude == "" || $rythmeCardio == "") 
    {
        $msg = "Erreur : données incomplètes.";        
        $code_reponse = 400;
    }
    else 
    {
        if ( $dao->getNiveauConnexion($pseudo, $mdpSha1) == 0 ) 
        {
            $msg = "Erreur : authentification incorrecte.";
            $code_reponse = 401;
        }
        else 
        {
            $uneTrace = $dao->getUneTrace($idTrace);
 
            if ($uneTrace == null) 
            {
                $msg = "Erreur : le numéro de trace n'existe pas.";
                $code_reponse = 500;
            }
            else 
            {
                $unUtilisateur = $dao->getUnUtilisateur($pseudo);
                $unIdUtilisateur = $unUtilisateur->getId();
                if ($unIdUtilisateur != $uneTrace->getIdUtilisateur()) 
                {
                    $msg = "Erreur : le numéro de trace ne correspond pas à cet utilisateur.";
                    $code_reponse = 500;
                }
                else 
                {
                    $ok = $dao->terminerUneTrace($idTrace);
                    if ($ok == FALSE) 
                    {
                        $msg = "Erreur : la trace est déjà terminée.";
                        $code_reponse = 500;
                    }
                    else 
                    {
                        $idPoint = sizeof($dao->getLesPointsDeTrace($idTrace)) + 1;     
                        $unTempsCumule = 0;
                        $uneDistanceCumulee = 0; 
                        $uneVitesse = 0;
                        
                        $nouveauPointDeTrace= new PointDeTrace($idTrace, $idPoint, $latitude, $longitude, $altitude, $dateHeure, $rythmeCardio, $unTempsCumule, $uneDistanceCumulee, $uneVitesse);
                                   
                        $ok = $dao->creerUnPointDeTrace($nouveauPointDeTrace);
                        if ( ! $ok ) 
                        {
                            $msg = "Erreur : problème lors de l'enregistrement du point.";
                            $code_reponse = 500;
                        }
                        else 
                        {
                            $msg = "Point créé.";
                            $code_reponse = 200;  
                        }
                    } 
                }
            }
        }
    }
}

// ferme la connexion à MySQL :
unset($dao);

// création du flux en sortie
if ($lang == "xml") {
    $content_type = "application/xml; charset=utf-8";      // indique le format XML pour la réponse
    $donnees = creerFluxXML ($msg, $nouveauPointDeTrace);
}
else {
    $content_type = "application/json; charset=utf-8";      // indique le format Json pour la réponse
    $donnees = creerFluxJSON ($msg, $nouveauPointDeTrace);
}

// envoi de la réponse HTTP
$this->envoyerReponse($code_reponse, $content_type, $donnees);

// fin du programme (pour ne pas enchainer sur les 2 fonctions qui suivent)
exit;

// ================================================================================================

// création du flux XML en sortie
function creerFluxXML($msg, $nouveauPointDeTrace)
{
    /* Exemple de code XML
        <?xml version="1.0" encoding="UTF-8"?>
        <data>
          <reponse>............. (message retourné par le service web) ...............</reponse>
          <donnees/>
        </data>
     ou bien, si le point a été créé :
        <?xml version="1.0" encoding="UTF-8"?>
        <data>
          <reponse>Point créé.</reponse>
          <donnees>
              <id>6</id>
          </donnees>
        </data>
     */
    
    // crée une instance de DOMdocument (DOM : Document Object Model)
    $doc = new DOMDocument();
    
    // specifie la version et le type d'encodage
    $doc->version = '1.0';
    $doc->encoding = 'UTF-8';
    
    // crée un commentaire et l'encode en UTF-8
    $elt_commentaire = $doc->createComment('Service web ChangerDeMdp - BTS SIO - Lycée De La Salle - Rennes');
    // place ce commentaire à la racine du document XML
    $doc->appendChild($elt_commentaire);
    
    // crée l'élément 'data' à la racine du document XML
    $elt_data = $doc->createElement('data');
    $doc->appendChild($elt_data);
    
    // place l'élément 'reponse' juste après l'élément 'data'
    $elt_reponse = $doc->createElement('reponse', $msg);
    $elt_data->appendChild($elt_reponse);
    
    $elt_donnees = $doc->createElement('donnees');
    $elt_data->appendChild($elt_donnees);
    
    if($nouveauPointDeTrace != NULL)
    {
        $elt_id         = $doc->createElement('id', $nouveauPointDeTrace->getId());
        $elt_data->appendChild($elt_id);
    }
    
    
    // Mise en forme finale
    $doc->formatOutput = true;
    
    // renvoie le contenu XML
    return $doc->saveXML();
}

// ================================================================================================

// création du flux JSON en sortie
function creerFluxJSON($msg, $nouveauPointDeTrace)
{
    /* Exemple de code JSON
        {
            "data": {
                "reponse": "............. (message retourné par le service web) ...............",
                "donnees": [ ]
                }
            }
        }
     ou bien, si le point a été créé :
        {
            "data": {
                "reponse": "Point créé."
                "donnees": {
                    "id": 7
                }
            }
        }
     */
    
    $elt_idTrace = [];
    
    if($nouveauPointDeTrace != NULL)
    {
        // construction de l'élément "lesUtilisateurs"
        $elt_idTrace = ["id" => $nouveauPointDeTrace->getId()];
    }
    
    // construction de l'élément "data"
    $elt_data = ["reponse" => $msg, "donnees" => $elt_idTrace];
    
    // construction de la racine
    $elt_racine = ["data" => $elt_data];
    
    // retourne le contenu JSON (l'option JSON_PRETTY_PRINT gère les sauts de ligne et l'indentation)
    return json_encode($elt_racine, JSON_PRETTY_PRINT);
}

// ================================================================================================
?>