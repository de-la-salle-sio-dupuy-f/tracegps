<?php
// Projet TraceGPS - services web
// fichier : api/services/RetirerUneAutorisation.php
// Dernière mise à jour : 17/11/2020 par Vincent GASNIER

// Rôle : ce service permet à un administrateur de supprimer une autorisation
// Le service web doit recevoir 4 paramètres :
//     pseudo : le pseudo de l'administrateur
//     mdp : le mot de passe hashé en sha1 de l'administrateur
//     pseudoARetirer : le pseudo de l'utilisateur à supprimer
//     texteMessage :  le texte d'un message accompagnant la suppresion
//     lang : le langage du flux de données retourné ("xml" ou "json") ; "xml" par défaut si le paramètre est absent ou incorrect
// Le service retourne un flux de données XML ou JSON contenant un compte-rendu d'exécution

// Les paramètres doivent être passés par la méthode GET :
//     http://<hébergeur>/tracegps/api/RetirerUneAutorisation?pseudo=admin&mdp=ff9fff929a1292db1c00e3142139b22ee4925177&pseudoARetirer=oxygen&lang=xml

// connexion du serveur web à la base MySQL

// connexion du serveur web à la base MySQL
$dao = new DAO();

// Récupération des données transmises
$pseudo = ( empty($this->request['pseudo'])) ? "" : $this->request['pseudo'];
$mdpSha1 = ( empty($this->request['mdp'])) ? "" : $this->request['mdp'];
$pseudoARetirer = ( empty($this->request['pseudoARetirer'])) ? "" : $this->request['pseudoARetirer'];
$lang = ( empty($this->request['lang'])) ? "" : $this->request['lang'];



// "xml" par défaut si le paramètre lang est absent ou incorrect
if ($lang != "json") $lang = "xml";

// La méthode HTTP utilisée doit être GET
if ($this->getMethodeRequete() != "GET")
{	$msg = "Erreur : méthode HTTP incorrecte.";
$code_reponse = 406;
}
else {
    // Les paramètres doivent être présents
    if ( $pseudo == "" || $mdpSha1 == "" || $pseudoARetirer == "" )
    {	$msg = "Erreur : données incomplètes.";
    $code_reponse = 400;
    }
    else
    {	// il faut être utilisateur pour supprimer une autorisation
        if ( $dao->getNiveauConnexion($pseudo, $mdpSha1) != 1 )
        {   $msg = "Erreur : authentification incorrecte.";
        $code_reponse = 401;
        }
        else
        {	// contrôle d'existence de pseudoAsupprimer
            $unUtilisateur = $dao->getUnUtilisateur($pseudoARetirer);
            if ($unUtilisateur == null)
            {  $msg = "Erreur : pseudo utilisateur inexistant.";
            $code_reponse = 400;
            }
            else
            {   // si cet utilisateur ne possède pas d'autorisation
                if ( $unUtilisateur->GetLesUtilisateursQueJautorise() > 0 ) {
                    $msg = "Erreur : l'autorisation n'était pas accordée.";
                    $code_reponse = 400;
                }
                else {
                    // suppression de l'autorisation dans la BDD
                    $ok = $dao->supprimerUneAutorisation($pseudoARetirer);
                    if ( ! $ok ) {
                        $msg = "Erreur : problème lors de la suppression de l'autorisation.";
                        $code_reponse = 500;
                    }
                    else {
                        // envoi d'un mail de confirmation de la suppression
                        $adrMail = $unUtilisateur->getAdrMail();
                        $sujet = "Autorisation supprimée.";
                        $contenuMail = "Cher ou chère " . $pseudoARetirer . "\n\nl'utilisateur ".$pseudo." du service TraceGPS vous retire l'autorisation de suivre ses parcours.";
                        
                        // cette variable globale est définie dans le fichier modele/parametres.php
                        global $ADR_MAIL_EMETTEUR;
                        
                        $ok = Outils::envoyerMail($adrMail, $sujet, $contenuMail, $ADR_MAIL_EMETTEUR);
                        if ( ! $ok ) {
                            // si l'envoi de mail a échoué, réaffichage de la vue avec un message explicatif
                            $msg = "Erreur : autorisation supprimée ; l'envoi du courriel de notification a rencontré un problème.";
                            $code_reponse = 500;
                        }
                        else {
                            // tout a fonctionné
                            $msg = "Autorisation supprimée ; ".$pseudoARetirer." va recevoir un courriel de notification.";
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
    $donnees = creerFluxXML($msg);
}
else {
    $content_type = "application/json; charset=utf-8";      // indique le format Json pour la réponse
    $donnees = creerFluxJSON($msg);
}

// envoi de la réponse HTTP
$this->envoyerReponse($code_reponse, $content_type, $donnees);

// fin du programme (pour ne pas enchainer sur les 2 fonctions qui suivent)
exit;

// ================================================================================================

// création du flux XML en sortie
function creerFluxXML($msg)
{	// crée une instance de DOMdocument (DOM : Document Object Model)
    $doc = new DOMDocument();
    
    // specifie la version et le type d'encodage
    $doc->version = '1.0';
    $doc->encoding = 'UTF-8';
    
    // crée un commentaire et l'encode en UTF-8
    $elt_commentaire = $doc->createComment('Service web RetirerUneAutorisation - BTS SIO - Lycée De La Salle - Rennes');
    // place ce commentaire à la racine du document XML
    $doc->appendChild($elt_commentaire);
    
    // crée l'élément 'data' à la racine du document XML
    $elt_data = $doc->createElement('data');
    $doc->appendChild($elt_data);
    
    // place l'élément 'reponse' dans l'élément 'data'
    $elt_reponse = $doc->createElement('reponse', $msg);
    $elt_data->appendChild($elt_reponse);
    
    // Mise en forme finale
    $doc->formatOutput = true;
    
    // renvoie le contenu XML
    return $doc->saveXML();
}

// ================================================================================================

// création du flux JSON en sortie
function creerFluxJSON($msg)
{
    /* Exemple de code JSON
     {
     "data": {
     "reponse": "Erreur : authentification incorrecte."
     }
     }
     */
    
    // construction de l'élément "data"
    $elt_data = ["reponse" => $msg];
    
    // construction de la racine
    $elt_racine = ["data" => $elt_data];
    
    // retourne le contenu JSON (l'option JSON_PRETTY_PRINT gère les sauts de ligne et l'indentation)
    return json_encode($elt_racine, JSON_PRETTY_PRINT);
}

// ================================================================================================
?>
