<?php
// Projet TraceGPS
// fichier : modele/Trace.class.php
// Rôle : la classe Trace représente une trace ou un parcours
// Dernière mise à jour : 9/9/2019 par JM CARTRON

include_once ('PointDeTrace.class.php');

class Trace
{
    // ATTENTION : on ne met pas de balise de fin de script pour ne pas prendre le risque
    // d'enregistrer d'espaces après la balise de fin de script !!!!!!!!!!!!
    // ------------------------------------------------------------------------------------------------------
    // ---------------------------------- Attributs privés de la classe -------------------------------------
    // ------------------------------------------------------------------------------------------------------
    
    private $id;				// identifiant de la trace
    private $dateHeureDebut;		// date et heure de début
    private $dateHeureFin;		// date et heure de fin
    private $terminee;			// true si la trace est terminée, false sinon
    private $idUtilisateur;		// identifiant de l'utilisateur ayant créé la trace
    private $lesPointsDeTrace;		// la collection (array) des objets PointDeTrace formant la trace
    // ------------------------------------------------------------------------------------------------------
    // ----------------------------------------- Constructeur -----------------------------------------------
    // ------------------------------------------------------------------------------------------------------
    
    public function __construct($unId, $uneDateHeureDebut, $uneDateHeureFin, $terminee, $unIdUtilisateur) {
        $this->id = $unId;
        $this->dateHeureDebut = $uneDateHeureDebut;
        $this->dateHeureFin = $uneDateHeureFin;
        $this->terminee = $terminee;
        $this->idUtilisateur = $unIdUtilisateur;
        $this->lesPointsDeTrace = array(); 
    }
    // ------------------------------------------------------------------------------------------------------
    // ---------------------------------------- Getters et Setters ------------------------------------------
    // ------------------------------------------------------------------------------------------------------
    
    public function getId() {return $this->id;}
    public function setId($unId) {$this->id = $unId;}
    
    public function getDateHeureDebut() {return $this->dateHeureDebut;}
    public function setDateHeureDebut($uneDateHeureDebut) {$this->dateHeureDebut = $uneDateHeureDebut;}
    
    public function getDateHeureFin() {return $this->dateHeureFin;}
    public function setDateHeureFin($uneDateHeureFin) {$this->dateHeureFin= $uneDateHeureFin;}
    
    public function getTerminee() {return $this->terminee;}
    public function setTerminee($terminee) {$this->terminee = $terminee;}
    
    public function getIdUtilisateur() {return $this->idUtilisateur;}
    public function setIdUtilisateur($unIdUtilisateur) {$this->idUtilisateur = $unIdUtilisateur;}
    
    public function getLesPointsDeTrace() {return $this->lesPointsDeTrace;}
    public function setLesPointsDeTrace($lesPointsDeTrace) {$this->lesPointsDeTrace = $lesPointsDeTrace;}
    
    // Fournit une chaine contenant toutes les données de l'objet
    public function toString() {
        $msg = "Id : " . $this->getId() . "<br>";
        $msg .= "Utilisateur : " . $this->getIdUtilisateur() . "<br>";
        if ($this->getDateHeureDebut() != null) {
            $msg .= "Heure de début : " . $this->getDateHeureDebut() . "<br>";
        }
        if ($this->getTerminee()) {
            $msg .= "Terminée : Oui  <br>";
        }
        else {
            $msg .= "Terminée : Non  <br>";
        }
        $msg .= "Nombre de points : " . $this->getNombrePoints() . "<br>";
        if ($this->getNombrePoints() > 0) {
            if ($this->getDateHeureFin() != null) {
                $msg .= "Heure de fin : " . $this->getDateHeureFin() . "<br>";
            }
            $msg .= "Durée en secondes : " . $this->getDureeEnSecondes() . "<br>";
            $msg .= "Durée totale : " . $this->getDureeTotale() . "<br>";
            $msg .= "Distance totale en Km : " . $this->getDistanceTotale() . "<br>";
            $msg .= "Dénivelé en m : " . $this->getDenivele() . "<br>";
            $msg .= "Dénivelé positif en m : " . $this->getDenivelePositif() . "<br>";
            $msg .= "Dénivelé négatif en m : " . $this->getDeniveleNegatif() . "<br>";
            $msg .= "Vitesse moyenne en Km/h : " . $this->getVitesseMoyenne() . "<br>";
            $msg .= "Centre du parcours : " . "<br>";
            $msg .= "   - Latitude : " . $this->getCentre()->getLatitude() . "<br>";
            $msg .= "   - Longitude : "  . $this->getCentre()->getLongitude() . "<br>";
            $msg .= "   - Altitude : " . $this->getCentre()->getAltitude() . "<br>";
        }
        return $msg;
    }
    
    public function getNombrePoints()
    {
        return sizeof($this->lesPointsDeTrace);
    }
    
    public function getCentre()
    {
        if (sizeof($this->lesPointsDeTrace) == 0) return null;
        else
        {
            
            $firstPoint = $this->lesPointsDeTrace[0];
            
            $latitudeMax = $firstPoint->getLatitude();
            $latitudeMin = $firstPoint->getLatitude();
            $longitudeMax = $firstPoint->getLongitude();
            $longitudeMin = $firstPoint->getLongitude();
            
            for ($i = 0; $i <= sizeof($this->lesPointsDeTrace) - 1; $i++)
            {
                $lePointactuel = $this->lesPointsDeTrace[$i];
                if ($lePointactuel->getLatitude() > $latitudeMax) $latitudeMax = $lePointactuel->getLatitude();
                if ($lePointactuel->getLatitude() < $latitudeMin) $latitudeMin = $lePointactuel->getLatitude();
                if ($lePointactuel->getLongitude() > $longitudeMax) $longitudeMax = $lePointactuel->getLongitude();
                if ($lePointactuel->getLongitude() < $longitudeMin) $longitudeMin = $lePointactuel->getLongitude();
                
            }
            
            $centreLatitude = ($latitudeMax + $latitudeMin) / 2;
            $centreLongitude = ($longitudeMax + $longitudeMin) / 2;
            $centre = new Point($centreLatitude, $centreLongitude, 0);
            return $centre;
        }
    }
    
    public function getDenivele()
    {
        if (sizeof($this->lesPointsDeTrace) == 0)
            return 0;
            else
            {
                // au départ, les valeurs extrêmes sont celles du premier point
                $lePremierPoint = $this->lesPointsDeTrace[0];
                $altitudeMini = $lePremierPoint->getAltitude();
                $altitudeMaxi = $lePremierPoint->getAltitude();
                // parcours des autres points (à partir de la position 1)
                for ($i = 1; $i < sizeof($this->lesPointsDeTrace) ; $i++)
                {
                    $lePoint = $this->lesPointsDeTrace[$i];
                    if ($lePoint->getAltitude() < $altitudeMini) $altitudeMini = $lePoint->getAltitude();
                    if ($lePoint->getAltitude() > $altitudeMaxi) $altitudeMaxi = $lePoint->getAltitude();
                }
                $denivele = $altitudeMaxi - $altitudeMini;
                return $denivele;
            }
    }
    
    public function getDureeEnSecondes()
    {
        if (sizeof($this->lesPointsDeTrace) == 0) return 0;
        
        else
        {
            $pointDeb = $this->lesPointsDeTrace[0];
            
            $pointTot = sizeof($this->lesPointsDeTrace);
            $pointFin = $this->lesPointsDeTrace[$pointTot - 1];
            
            $duree = strtotime($pointFin->getDateHeure()) - strtotime($pointDeb->getDateHeure());
            return $duree;
        }
    }
    
    public function getDureeTotale()
    {
        if (sizeof($this->lesPointsDeTrace) == 0) return "00:00:00";
        
        else
        {
            $dateHeureDebut = $this->getDateHeureDebut();
            $dateHeureFin = $this->getDateHeureFin();
            
            $duree = strtotime($dateHeureFin) - strtotime($dateHeureDebut);
            
            
            $heures = $duree / 3600;
            $reste = $duree % 3600;
            $minutes = $reste / 60;
            $restesec = $duree % 60;
            $secondes = $restesec;
            
            return $heures.":". $minutes.":".$secondes;
        }
    }
    
    public function getDistanceTotale()
    {
        if (sizeof($this->lesPointsDeTrace) == 0) return 0;
        else
        {
            
            $lePoint = sizeof($this->lesPointsDeTrace);
            $lastPoint = $this->lesPointsDeTrace[$lePoint - 1];
            
            return $lastPoint->getDistanceCumulee();
            
        }
    }
    
    public function getDenivelePositif()
    {
        $denivele = 0;
        if (sizeof($this->lesPointsDeTrace) == 0) return 0;
        
        else
        {
            for ($i = 0; $i <= sizeof($this->lesPointsDeTrace)-2; $i++)
            {
                $this->lePoint1 = $this->lesPointsDeTrace[$i];
                $this->lePoint2 = $this->lesPointsDeTrace[$i + 1];
                
                if ($this->lePoint1->getAltitude() < $this->lePoint2->getAltitude())
                {
                    $denivele = $denivele + ($this->lePoint2->getAltitude() - $this->lePoint1->getAltitude());
                }
            }
            return $denivele;
        }
        
    }
    
    public function getDeniveleNegatif()
    {
        $denivele = 0;
        if (sizeof($this->lesPointsDeTrace) == 0) return 0;
        
        else
        {
            for ($i = 0; $i <= sizeof($this->lesPointsDeTrace)-2; $i++)
            {
                $this->lePoint1 = $this->lesPointsDeTrace[$i];
                $this->lePoint2 = $this->lesPointsDeTrace[$i + 1];
                
                if ($this->lePoint1->getAltitude() > $this->lePoint2->getAltitude())
                {
                    $denivele = $denivele + ($this->lePoint1->getAltitude() - $this->lePoint2->getAltitude());
                }
            }
            return $denivele;
        }
        
    }
    
    public function getVitesseMoyenne()
    {
        if (sizeof($this->lesPointsDeTrace) == 0) return 0;
        
        else
        {
            $distTrace = $this->getDistanceTotale();
            $tempsEnSec = $this->getDureeEnSecondes();
            
            $vitesseMoyenne = $distTrace / ($tempsEnSec / 3600);
            
            return $vitesseMoyenne;
        }
    }
    
    public function ajouterPoint($nouveauPoint)
    {
        
        if (sizeof($this->lesPointsDeTrace) == 0)
        {
            $nouveauPoint->setVitesse(0);
            $nouveauPoint->setTempsCumule(0);
            $nouveauPoint->setDistanceCumulee(0);
        }
        else
        {
            $dernierPoint = $this->lesPointsDeTrace[$this->getNombrePoints()-1];
            
            $this->distCumPointAv = $dernierPoint->getDistanceCumulee();
            $this->tempsCumPointAv = $dernierPoint->getTempsCumule();
            $this->vitessePointAv = $dernierPoint->getVitesse();
            
            $duree = strtotime($nouveauPoint->getDateHeure()) - strtotime($dernierPoint->getDateHeure());
            
            
            $diffDist = Point::getDistance($dernierPoint, $nouveauPoint);
            
            $nouveauPoint->setDistanceCumulee($dernierPoint->getDistanceCumulee() + $diffDist);
            $nouveauPoint->setTempsCumule($dernierPoint->getTempsCumule() + $duree);
            $nouveauPoint->setVitesse(Point::getDistance($dernierPoint, $nouveauPoint)/($duree/3600));
            
        }
        $this->lesPointsDeTrace[] = $nouveauPoint;
    }
} // fin de la classe Trace
    