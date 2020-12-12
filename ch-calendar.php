<?php
/**
* Plugin Name: CH Calendar
* Plugin URI: https://www.philix.be/
* Description: Cluster Headache attacks calendar
* Version: 1.0.2
* Author: Philix
* Author URI: https://www.philix.be/
* Text Domain: ch-calendar
* Domain Path: /languages
**/


define( 'CH_CALENDAR_VERSION', '1.0.2' );
define( 'CH_CALENDAR_PATH', plugin_dir_path( __FILE__ ) );
date_default_timezone_set('Europe/Brussels');

/** Création de la table à l'activation du plugin
 * Vous devez placer chaque champ sur sa propre ligne dans votre instruction SQL.
 * Vous devez avoir deux espaces entre les mots PRIMARY KEY et la définition de votre clé primaire.
 * Vous devez utiliser le mot clé KEY plutôt que son synonyme INDEX et vous devez inclure au moins une KEY.
 * Vous ne devez pas utiliser d'apostrophes ou de bâtons autour des noms de champs.
 * Les types de champs doivent être tous en minuscules.
 * Les mots-clés SQL, comme CREATE TABLE et UPDATE, doivent être en majuscules.
 * Ils sont imposés par la fonction dbDelta(), et non par le SQL lui-même bien sûr.
**/

function ch_calendar_create_db() {
	global $wpdb;
	$charset_collate = $wpdb->get_charset_collate();
	$table_name = $wpdb->prefix . 'avf_crises';

	$sql = "CREATE TABLE IF NOT EXISTS $table_name (
          pid int(11) NOT NULL AUTO_INCREMENT COMMENT 'Primary Key: Unique crisis ID.',
          uid int(11) NOT NULL DEFAULT 0 COMMENT 'users.uid',
          cdate datetime NOT NULL COMMENT 'Crisis time',
          cduration time NOT NULL COMMENT 'Crisis duration',
          cintensity enum('1','2','3','4','5','6','7','8','9','10') NOT NULL DEFAULT '1' COMMENT 'Crisis intensity',
          ctreatment enum('None','Aucun','Geen','Keine','Oxygen 9 l/m','Oxygène 9 l/m','Zuurstof 9 l/m','Sauerstoff 9 l/m','Oxygen 12 l/m','Oxygène 12 l/m','Zuurstof 12 l/m','Sauerstoff 12 l/m','Oxygen 15 l/m','Oxygène 15 l/m','Zuurstof 15 l/m','Oxygen valve on demand','Valve d’oxygène à la demande','Zuurstof met on demand kranen','Sauerstoffventil auf Anfrage','Imitrex SC 1/3','Imitrex SC 1/2','Imitrex SC 2/3','Imitrex SC 1/1','Cafergot 1/4','Cafergot 1/2','Cafergot 1/1','Other','Autre','Andere') NOT NULL DEFAULT 'None' COMMENT 'Crisis treatment if any',
          ccomment longtext NOT NULL COMMENT 'Crisis comments',
          PRIMARY KEY  (pid),
          KEY uid (uid)
	) $charset_collate;";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );
}

register_activation_hook( __FILE__, 'ch_calendar_create_db' );


/** Ajout des CSS et JS
**/
function wpb_adding_scripts_css() {
    wp_register_script('ch-calendar', plugins_url('js/script.js', __FILE__), array('jquery'),'1.1', true);
    wp_enqueue_script('ch-calendar'); 
    wp_enqueue_style('ch-calendar', plugins_url().'/'.basename( dirname( __FILE__ ) ) .'/css/style.css');
}

add_action( 'wp_enqueue_scripts', 'wpb_adding_scripts_css' );  


/**
 * Pour accéder aux traductions situées dans le sous-répertoire /languages/ du plugin (fichiers .po et .mo)
**/ 
function ch_calendar_load_plugin_textdomain() {
    load_plugin_textdomain( 'ch-calendar', FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );
}

add_action( 'plugins_loaded', 'ch_calendar_load_plugin_textdomain' );


/** 
 * lecture de la table des crises d'AVF
 * La fonction accepte un paramètre avec deux valeurs possible :
 * "all" permet aux administrateurs de lire toute la table anonymisée
 * "user" permet à chaque utilisateur de lire les enregistrements qui le concerne uniquement
**/

// date_default_timezone_set('Europe/Brussels'); Ne peut pas être utilisé car perturbe le cœur de WordPress


/** 
 * Lecture de la table des crises
 * la fonction accepte un paramètre et deux valeurs
**/ 
function ch_calendar_read($attr) {

    // Récupérer le paramètre du shortcode (défaut : 'user') 
    $args = shortcode_atts( array('uid' => 'user'), $attr );
    
    // Sécurité : vérifier que le paramètre est égal à 'all' (réservé à la page "admin reserved") ou 'user' (utilisateur courant)
    $parmList = array('all', 'user');

    // Simuler une page d'erreur 403 "forbidden" si le paramètre est incorrecte et renvoyer une erreur 403
    if (!in_array($args['uid'], $parmList)) {
        $chHTMLCode = '<h1 style="font-weight: bold;font-size: 3vw; text-align: center;">Error 403 forbiden</h1>';
        return $chHTMLCode;
    }
    
    global $wpdb;

    // On vérifie que l'utilisateur est connecté, sinon, on renvoie un message signalant qu'il faut l'être
    if ( !is_user_logged_in() ) {
        $chMessage = '<span style="color:#ff4111; font-weight:bold;">' . __('You must be connected to access the calendar.', 'ch-calendar') . '</span>';
        return $chMessage;
    } 
    if ($args['uid'] == 'all') {
        // test if user is admin
        if(current_user_can('administrator')) {
            // Initialisation de la variable de retour (titre) et création de la requête SQL pour tous les enregistrements
            $chHTMLCode = '<h3>'.__('Full table:', 'ch-calendar').'</h3>';
            $sql = "SELECT * FROM {$wpdb->prefix}avf_crises ORDER BY uid ASC,cdate DESC";
        } else {
            // On renvoie un message signalant qu'il faut être administrateur pour la liste de tous les calendriers
            $chHTMLCode = '<h3>'.__('You must be an administrator to access this option.', 'ch-calendar').'</h3>';
            return $chHTMLCode;
        }
    } else {
        // Initialisation de la variable de retour (titre) et création de la requête SQL pour l'utilisateur courant
        $current_user = wp_get_current_user();
        $chUid = $current_user->ID;
        $chUserName = $current_user->display_name;
        $chHTMLCode = '<h3>'.__('Result for:', 'ch-calendar').' '.$chUserName.'</h3>';
        $sql = "SELECT * FROM {$wpdb->prefix}avf_crises WHERE uid = '$chUid' ORDER BY cdate DESC";
    }
    
    // Exécution de la requête SQL (on lit la table avec les paramètres ad hoc)
    $results = $wpdb->get_results($sql) ;
/*    if ($wpdb->last_error) {
        error_log('Error: ' . $wpdb->last_error);
    } */        
    
    // Parcours des resultats obtenus et stockage dans la variable de retour pour affichage des résultats
    if (empty($results)) {
        // Calendrier vide : mise à jour de la variable de retour avec le message ad hoc et renvoie du résultat
        $chHTMLCode .= '<h3 style="color:#ff4111; font-weight:bold;">' . __('Your calendar is empty.', 'ch-calendar') . '</h3>';
        return $chHTMLCode;
    } else {
        $SplitUid = null;
        $splitDate = new DateTime('2000-01-01');
        $splitMonth = 'MoisInit';
        $chCompte = false;
        $grid = '';
        $bubbles = '<p id="graph"></p>';
        $chNbrCrises = 0;
        
        foreach( $results as $results) {
            // Créer une table HTML par utilisateur (si paramètre "all") ou créer la table HTML pour l'utilisateur courant
            if ($results->uid != $SplitUid) {
                if (($args['uid'] != 'user') && ($chCompte)) {
                    $chHTMLCode .= "</table>";
                }
                $chHTMLCode .= 
                    '<table class="ch-calendar">
                        <tr>
                            <th>'.__('Date', 'ch-calendar').'</th>
                            <th>'.__('Hour', 'ch-calendar').'</th>
                            <th>'.__('Duration', 'ch-calendar').'</th>
                            <th>'.__('Intensity', 'ch-calendar').'</th>
                            <th>'.__('Treatment', 'ch-calendar').'</th>
                            <th>'.__('Comment', 'ch-calendar').'</th>
                        </tr>
                    ';
            }
            $chDate = date_create($results->cdate);
            
            // Créer une grille par mois pour le(s) graphe(s)
            if ($args['uid'] != 'all') {
                $month = date_format($chDate,"F"); 
                if ($month !== $splitMonth) {
                    if ($splitMonth != 'MoisInit') {
                        $grid .= '</div>';                          /* Fermer la grille précédente */
                        $grid .= '<p>'.__('Number of attacks in the month:', 'ch-calendar'). ' <strong>'.$chNbrCrises.'</strong></p>';
                        $bubbles .= $grid;                          /* Stocker la grille dans le div */
                        $bubbles .= '</div>';                       /* Fermer le div */
                        $chNbrCrises = 0;
                        $grid = '';
                    } 
                    $bubbles .= ch_dessiner_grille(date_format($chDate,"F"),date_format($chDate,"Y")); /* Dessiner la grille de fond du mois*/   
                } 
            } 
            
            $chHTMLCode .= '<tr><td>'.date_format($chDate,"d-m-Y").'</td>';
            
            $chHTMLCode .= '<td>'.date_format($chDate,"H:i").'</td>';
            
            $chDuration = date_create($results->cduration);
            $chHTMLCode .= '<td>'.date_format($chDuration,"H:i").'</td>';
            
            $circleIntensity = 6 + pow(intval($results->cintensity), 1.2); 
            $chHTMLCode .= '<td>'.$results->cintensity.'</td>';

            /* make the bubbles */
            if ($args['uid'] != 'all') {
                $bubbleTop = 10 + (intval(date_format($chDate,"H") * 60) + intval(date_format($chDate,"i"))) / 3.6;         /* ex: 9h12 => ((9*60)+12)/3.6 = 153.33px */
                $bubbleTop = $bubbleTop - ($circleIntensity / 2);
                $bubbleleft = (intval(date_format($chDate,"d")) * 11.84) - ($circleIntensity / 2) + (10.8 * intval(date_format($chDate,"d"))) - 10.8;
                $bubbles .= '<figure class="circle" title="'.date_format($chDate,"d-m-Y H:i").' &mdash; '.__('Duration', 'ch-calendar').' : '.date_format($chDuration,"H:i").' &mdash; '.__('Intensity', 'ch-calendar').' : '.$results->cintensity.'" style="height:'.$circleIntensity.'px;width:'.$circleIntensity.'px;position:absolute;left:'.$bubbleleft.'px;top:'.$bubbleTop.'px;"> </figure>';
            } 


            $chHTMLCode .= '<td>'.$results->ctreatment.'</td>';
            $chHTMLCode .= '<td class="comment">'.$results->ccomment.'</td></tr>';
            
            $splitMonth = $month;        /* stocker la date. Utiliser pour forcer la création d'un nouveau graphique */
            $SplitUid = $results->uid;   /* stocker l'utilisateur. Utiliser pour forcer la création d'une nouvelle table HTML (si paramètre "all") */
            $chCompte = true;            /* changer la valeur à true pour fermer la table */
            $chNbrCrises++;
        }       
    }

    // Fermeture de la table HTML
    $chHTMLCode .= '</table>';
    
    // Fermeture du div bubbles
    if ($args['uid'] != 'all') {
        $bubbles .= '</div><p>'.__('Number of attacks in the month:', 'ch-calendar'). ' <strong>'.$chNbrCrises.'</strong></p>';
        // Renvoyer les résultats pour affichage
        $chHTMLCode .= $bubbles;
    }
    
    return $chHTMLCode;

}


/** 
 * Ajout d'une crise dans la table des crises d'AVF
 * la fonction n'accepte pas de paramètre et utilise l'uid de l'utilisateur courant
**/ 
function ch_calendar_insert() {

    $chHTMLCode = '';
    // On vérifie que l'utilisateur est connecté, sinon, on renvoie un message signalant qu'il faut l'être
    if ( !is_user_logged_in() ) {
        $chMessage = '<span style="color:#ff4111; font-weight:bold;">' . __('You must be connected to access the calendar.', 'ch-calendar') . '</span>';
        return $chMessage;
    } 
    /* Test de retour d'encodage */
    /* Si encodage => vérification des inputs et insertion dans la table */
    if (isset($_POST["cdate-day"])) {

        $current_user = wp_get_current_user();
        $chUid = $current_user->ID;

        $cdate_day = str_pad(sanitize_text_field($_POST["cdate-day"]), 2, '0', STR_PAD_LEFT);
        /*$cdate_day = (is_numeric($_POST["cdate-day"])) ? str_pad(sanitize_text_field($_POST["cdate-day"]), 2, '0', STR_PAD_LEFT) : 'Error'; */
        $cdate_month = str_pad(sanitize_text_field($_POST["cdate-month"]), 2, '0', STR_PAD_LEFT);
        $cdate_year = sanitize_text_field($_POST["cdate-year"]);
        $cdate_hour = str_pad(sanitize_text_field($_POST["cdate-hour"]), 2, '0', STR_PAD_LEFT);
        $cdate_minute = str_pad(sanitize_text_field($_POST["cdate-minute"]), 2, '0', STR_PAD_LEFT);
        $chDate = date("Y-m-d H:i:s", mktime($cdate_hour, $cdate_minute, 0, $cdate_month, $cdate_day, $cdate_year)); 

        $duree_hour = str_pad(sanitize_text_field($_POST["duree-hour"]), 2, '0', STR_PAD_LEFT);
        $duree_minute = str_pad(sanitize_text_field($_POST["duree-minute"]), 2, '0', STR_PAD_LEFT);
        $chDuree = date("Y-m-d H:i:s", mktime($duree_hour, $duree_minute, 0, $cdate_month, $cdate_day, $cdate_year)); 

        $intensity = sanitize_textarea_field($_POST["intensity"]);
        $treatment = sanitize_textarea_field($_POST["treatment"]);
        $comment = sanitize_textarea_field($_POST["comment"]);
        $comment = str_replace ("\'","’",$comment);     /* Transforme l'escape de ' créer par sanitize */
        $comment = str_replace (";","",$comment);       /* suppress ; */
        $comment = str_replace ("(","",$comment);       /* suppress ( */
        $comment = str_replace (")","",$comment);       /* suppress ) */
        $comment = str_replace( array("\r\n","\r","\n") , ' ' , $comment);
        
        $chHTMLCode = '<div class="divnote"><strong>'.__('Record added to your calendar:', 'ch-calendar').'</strong><br />'.__('Date: ', 'ch-calendar').$chDate.'<br />'.__('Duration: ', 'ch-calendar').$chDuree.'<br />'.__('Intensity: ', 'ch-calendar').$intensity.'<br />'.__('Treatment: ', 'ch-calendar').$treatment.'<br />'.__('Comment: ', 'ch-calendar').$comment.'</div>'; 

        global $wpdb;
        $table = $wpdb->prefix."avf_crises";
        $wpdb->insert($table,array(
            "uid"           => $chUid,
            "cdate"         => $chDate,
            "cduration"     => $chDuree,
            "cintensity"    => $intensity,
            "ctreatment"    => $treatment,
            "ccomment"      => $comment
        ));        

    } 

    /* Affichage du formulaire */
    /* Titre de la page  */
    $chHTMLCode .= '<h3>'.__('Add a new attack in your calendar', 'ch-calendar').'</h3>';
    /* Appel de la fonction */
    $chHTMLCode .= ch_formulaire('Add', 'Visible');
    /* Fin du formulaire */
    $chHTMLCode .= '</form>';
    $chHTMLCode .= '</div>';

    /* Affichage du contenu de la page */
    return $chHTMLCode;
}


/** 
 * Dessiner la grille de fond du graphique
 * la fonction accepte deux paramètres : mois et année
**/ 
function ch_dessiner_grille($month,$year) {

    $parmList = array('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');
    // crash si month n'est pas un mois
    if (!in_array($month, $parmList)) {
        die;
    }
    // crash si year n'est pas un entier de 4 digit
    $year = intval($year);
    if (strlen($year) != 4) {
        die;
    }
    $bubbles = '<h3 class="graphic">'.__('Graphic for', 'ch-calendar').' '.__($month).' '.$year.'</h3>';
    $bubbles .= '<div class="bubbles">';
    
    $h = $g = 1;
    $grid = '<div id="grid">';
    for($i = 1; $i <= 25; $i++) {
      for($j = 1; $j <= 31; $j++) {
          $grid .= '<div class="cell"></div>';
      }
      if ($h != 25) {
            $grid .= '<div class="cell hour">&nbsp;'.$h.'</div>';
      } else {
            $grid .= '<div class="cell hour">&nbsp;&nbsp;</div>';
      }
      $h++;
    }
    for($i = 1; $i <= 31; $i++) {
        $grid .= '<div class="cell day">'.$g.'</div>';
        $g++;
    }
    $bubbles .= $grid;
    return $bubbles;
}


/** 
 * Construction du formulaire
 * la fonction accepte deux paramètres : 
 * - action : add, modify ou delete
 * - parm : hid (pour type=hidden du bouton d'action qui sera remis à type=submit par JS)
**/ 
function ch_formulaire($action,$parm) {
    $parmList = array('Add', 'Modify', 'Delete');
    // crash si mauvaise commande
    if (!in_array($action, $parmList)) {
        die;
    }
    $parmList = array('Visible', 'Hid', '');
    // crash si mauvaise commande
    if (!in_array($parm, $parmList)) {
        die;
    }
    /* Début du formulaire */
    $chHTMLCode = '<div class="chform">';
    $chHTMLCode .= '<form action="" method="POST">';
    /* sous-titre */
    $chHTMLCode .= '<div style="font-weight:bold;">';
    $chHTMLCode .= __('Date and time of the attack', 'ch-calendar');
    $chHTMLCode .= '</div>';

    /* Sélection du jour et l'heure de la crise */
    /* Jour */
    $chHTMLCode .= '<div style="float:left">';
    $chHTMLCode .= '<label for="edit-cdate-day">'.__('Day', 'ch-calendar').'</label><br />';
    $chHTMLCode .= '<select id="edit-cdate-day" name="cdate-day">';
    $start = 1;
    $end = intval(date("t",date('m')));   /* dernier jour du mois courant */
    for($i = $start; $i <= $end; $i++){
        if ((intval(date('j')) == $i) && ($action == 'Add')) {
            $chHTMLCode .= '<option selected="selected">'.str_pad($i, 2, '0', STR_PAD_LEFT).'</option>';
        } else {
            $chHTMLCode .= '<option>'.str_pad($i, 2, '0', STR_PAD_LEFT).'</option>';
        }
    }
    $chHTMLCode .= '</select>';
    $chHTMLCode .= '</div>';
    /* Mois */
    $chHTMLCode .= '<div style="float:left">';
    $chHTMLCode .= '<label for="edit-cdate-month">'.__('Month', 'ch-calendar').'</label><br />';
    $chHTMLCode .= '<select id="edit-cdate-month" name="cdate-month">';
    $start = 1;
    $end = 12;
    for($i = $start; $i <= $end; $i++){
        if ((intval(date('n')) == $i) && ($action == 'Add')) {
            $chHTMLCode .= '<option selected="selected">'.str_pad($i, 2, '0', STR_PAD_LEFT).'</option>';
        } else {
            $chHTMLCode .= '<option>'.str_pad($i, 2, '0', STR_PAD_LEFT).'</option>';
        }
    }
    $chHTMLCode .= '</select>';
    $chHTMLCode .= '</div>';
    /* Année */
    $chHTMLCode .= '<div style="float:left">';
    $chHTMLCode .= '<label for="edit-cdate-year">'.__('Year', 'ch-calendar').'</label><br />';
    $chHTMLCode .= '<select id="edit-cdate-year" name="cdate-year">';
    $start = 2019;
    $end = intval(date("Y"));
    for($i = $start; $i <= $end; $i++){
        if ((intval(date('Y')) == $i) && ($action == 'Add')) {
            $chHTMLCode .= '<option selected="selected">'.$i.'</option>';
        } else {
            $chHTMLCode .= '<option>'.$i.'</option>';
        }
    }
    $chHTMLCode .= '</select>';
    $chHTMLCode .= '</div>';
    /* Heure */
    $chHTMLCode .= '<div style="float:left">';
    $chHTMLCode .= '<label for="edit-cdate-hour">'.__('Hour', 'ch-calendar').'</label><br />';
    $chHTMLCode .= '<select id="edit-cdate-hour" name="cdate-hour">';
    $start = 0;
    $end = 23;
    for($i = $start; $i <= $end; $i++){
        if ((intval(date('G')) == $i) && ($action == 'Add')) {
            $chHTMLCode .= '<option selected="selected">'.str_pad($i, 2, '0', STR_PAD_LEFT).'</option>';
        } else {
            $chHTMLCode .= '<option>'.str_pad($i, 2, '0', STR_PAD_LEFT).'</option>';
        }
    }
    $chHTMLCode .= '</select>';
    $chHTMLCode .= '</div>';
    /* Minute */
    $chHTMLCode .= '<div style="float:left">';
    $chHTMLCode .= '<label for="edit-cdate-minute">'.__('Minute', 'ch-calendar').'</label><br />';
    $chHTMLCode .= '<select id="edit-cdate-minute" name="cdate-minute">';
    $start = 0;
    $end = 59;
    for($i = $start; $i <= $end; $i++){
        if ((intval(date('i')) == $i) && ($action == 'Add')) {
            $chHTMLCode .= '<option selected="selected">'.str_pad($i, 2, '0', STR_PAD_LEFT).'</option>';
        } else {
            $chHTMLCode .= '<option>'.str_pad($i, 2, '0', STR_PAD_LEFT).'</option>';
        }
    }
    $chHTMLCode .= '</select>';
    $chHTMLCode .= '</div>';

    /* Sous-titre pour la durée de la crise */
    /* Sélection de l'heure de la crise */
    $chHTMLCode .= '<div style="clear:both; font-weight:bold;">';
    $chHTMLCode .= __('Duration of the attack', 'ch-calendar');
    $chHTMLCode .= '</div>';
    /* Heure */
    $chHTMLCode .= '<div style="float:left">';
    $chHTMLCode .= '<label for="edit-duree-hour">'.__('Hour', 'ch-calendar').'</label><br />';
    $chHTMLCode .= '<select id="edit-duree-hour" name="duree-hour">';
    $start = 0;
    $end = 23;
    for($i = $start; $i <= $end; $i++){
        $chHTMLCode .= '<option>'.str_pad($i, 2, '0', STR_PAD_LEFT).'</option>';
    }
    $chHTMLCode .= '</select>';
    $chHTMLCode .= '</div>';
    /* Minute */
    $chHTMLCode .= '<div style="float:left">';
    $chHTMLCode .= '<label for="edit-duree-minute">'.__('Minute', 'ch-calendar').'</label><br />';
    $chHTMLCode .= '<select id="edit-duree-minute" name="duree-minute">';
    $start = 0;
    $end = 59;
    for($i = $start; $i <= $end; $i++){
        $chHTMLCode .= '<option>'.str_pad($i, 2, '0', STR_PAD_LEFT).'</option>';
    }
    $chHTMLCode .= '</select>';
    $chHTMLCode .= '</div>';

    /* Sous-titre pour l'intensité de la crise */
    $chHTMLCode .= '<div style="clear:both; font-weight:bold;">';
    $chHTMLCode .= __('Intensity of the attack', 'ch-calendar');
    $chHTMLCode .= '</div>';
    /* Sélection de 1 à 10 (0 = pas de crise) */
    $chHTMLCode .= '<div style="float:left">';
    $chHTMLCode .= '<label for="edit-intensity">'.__('Intensity', 'ch-calendar').'</label><br />';
    $chHTMLCode .= '<select id="edit-intensity" name="intensity">';
    $start = 1;
    $end = 10;
    for($i = $start; $i <= $end; $i++){
        $chHTMLCode .= '<option>'.$i.'</option>';
    }
    $chHTMLCode .= '</select>';
    $chHTMLCode .= '</div>';

    /* Sous-titre pour le traitement de la crise */
    $chHTMLCode .= '<div style="clear:both; font-weight:bold;">';
    $chHTMLCode .= __('Treatment of the attack', 'ch-calendar');
    $chHTMLCode .= '</div>';
    /* Sélection du traitement */
    $chHTMLCode .= '<div style="float:left">';
    $chHTMLCode .= '<label for="edit-treatment">'.__('Treatment', 'ch-calendar').'</label><br />';
    $chHTMLCode .= '<select id="edit-treatment" name="treatment">';
    $start = 1;
    $end = 13;    /* à adapter en fonction du nombre de traitements définis ci-dessous */
    $treatments = array(1 => 
        __('None', 'ch-calendar'), 
        __('Oxygen', 'ch-calendar').' 9 l/m', 
        __('Oxygen', 'ch-calendar').' 12 l/m', 
        __('Oxygen', 'ch-calendar').' 15 l/m', 
        __('Oxygen valve on demand', 'ch-calendar'), 
        'Imitrex SC 1/3',
        'Imitrex SC 1/2',
        'Imitrex SC 2/3',
        'Imitrex SC 1/1',
        'Cafergot 1/4',
        'Cafergot 1/2',
        'Cafergot 1/1',
        __('Other', 'ch-calendar')
        ); 
    for($i = $start; $i <= $end; $i++){
        $chHTMLCode .= '<option>'.$treatments[$i].'</option>';
    }
    $chHTMLCode .= '</select>';
    $chHTMLCode .= '</div>';

    /* Sous-titre pour les commentaires */
    $chHTMLCode .= '<div style="clear:both; font-weight:bold;">';
    $chHTMLCode .= __('Comments', 'ch-calendar');
    $chHTMLCode .= '</div>';
    /* Commentaires */
    $chHTMLCode .= '<div style="float:left">';
    $chHTMLCode .= '<label for="edit-comments">'.__('Describe the characteristics of the attack', 'ch-calendar').'</label><br />';
    $chHTMLCode .= '<textarea id="edit-comments" rows="3" name="comment"></textarea>';
    $chHTMLCode .= '<input type="hidden" id="pid" name="pid" value=" " />';
    $chHTMLCode .= '</div>';

    /* Bouton Ajouter / Modifier */
    $chHTMLCode .= '<div style="clear:both; margin-bottom: 10px; font-weight:bold;">';
    $add = __('Add', 'ch-calendar');
    $del = __('Delete', 'ch-calendar');
    $mod = __('Modify', 'ch-calendar');
    if ($parm == 'Hid') {
        $type = 'type="hidden"';
    } else {
        $type = 'type="submit"';
    }
    $chHTMLCode .= '<input '.$type.' id="edit-submit" name="edit-submit" value="'.__($action, 'ch-calendar').'" />';
    $chHTMLCode .= '</div>';
    return $chHTMLCode;
}


/** 
 * Afficher les enregistrement de la table pour un utilisateur en ajoutant un bouton radio
 * la fonction accepte deux seul paramètre : l'uid et la valeur du bouton : modify ou delete
**/ 
function ch_read_for_mod_or_del($chUid,$bouton) {

    global $wpdb;

    // On vérifie que l'utilisateur est connecté, sinon, on renvoie un message signalant qu'il faut l'être
    if ( !is_user_logged_in() ) {
        $chMessage = '<span style="color:#ff4111; font-weight:bold;">' . __('You must be connected to access the calendar.', 'ch-calendar') . '</span>';
        return $chMessage;
    } 
    $current_user = wp_get_current_user();
    // crash si l'uid n'est pas celui de l'utilisateur
    if ($chUid != $current_user->ID) {
        die;
    }
    // Création de la requête SQL (on lit la table avec les paramètres ad hoc)
    $sql = "SELECT * FROM {$wpdb->prefix}avf_crises WHERE uid = '$chUid' ORDER BY cdate DESC";
    // Exécution de la requête SQL (on lit la table avec les paramètres ad hoc)
    $results = $wpdb->get_results($sql) ;
/*    if ($wpdb->last_error) {
        error_log('Error: ' . $wpdb->last_error);
    }*/        
    
    // Parcours des resultats obtenus et stockage dans la variable de retour pour affichage des résultats
    if (empty($results)) {
        // Calendrier vide : mise à jour de la variable de retour avec le message ad hoc et renvoie du résultat
        $chHTMLCode = '<h3 style="color:#ff4111; font-weight:bold;">' . __('Your calendar is empty.', 'ch-calendar') . '</h3>';
        return $chHTMLCode;
    } else {
        /* Affichage du formulaire avant la liste */
        $chHTMLCode = ch_formulaire($bouton,'Hid');
        // Entête de la table HTML
        $chHTMLCode .= 
            '<table class="ch-calendar">
                <tr>
                    <th>'.__('Choice', 'ch-calendar').'</th>
                    <th>'.__('Date', 'ch-calendar').'</th>
                    <th>'.__('Hour', 'ch-calendar').'</th>
                    <th>'.__('Duration', 'ch-calendar').'</th>
                    <th>'.__('Intensity', 'ch-calendar').'</th>
                    <th>'.__('Treatment', 'ch-calendar').'</th>
                    <th>'.__('Comment', 'ch-calendar').'</th>
                </tr>
        ';
        // Loop dans la table
        foreach( $results as $results) {
            $chPid = $results->pid;  /* numéro unique de l'enregistrement dans la table */
            $chDate = date_create($results->cdate);
            $chDuration = date_create($results->cduration);
            /* Création des variables pour la fonction javascript "getRadioValues" (valeurs entre quote') */  
            $jschPid = "'".$results->pid."'";  
            $jschDate = "'".date_format($chDate,"d-m-Y H:i")."'";
            $jschDuration = "'".date_format($chDuration,"d-m-Y H:i")."'";
            $jschIntensity = "'".$results->cintensity."'"; 
            $jschTreatment = "'".$results->ctreatment."'"; 
            $jschComment = "'".$results->ccomment."'"; 

            $chHTMLCode .= '<tr>';
            $chHTMLCode .= '<td><input type="radio" id="record-'.strval($chPid).'" name="table" value="'.strval($chPid).'" onclick="getRadioValues('.strval($jschPid).','.$jschDate.','.$jschDuration.','.$jschIntensity.','.$jschTreatment.','.$jschComment.');"></td>';
            $chHTMLCode .= '<td>'.date_format($chDate,"d-m-Y").'</td>';
            $chHTMLCode .= '<td>'.date_format($chDate,"H:i").'</td>';
            $chHTMLCode .= '<td>'.date_format($chDuration,"H:i").'</td>';
            $chHTMLCode .= '<td>'.$results->cintensity.'</td>';
            $chHTMLCode .= '<td>'.$results->ctreatment.'</td>';
            $chHTMLCode .= '<td>'.$results->ccomment.'</td>';
            $chHTMLCode .= '</tr>';
        }
    }
    $chHTMLCode .='</table>';
    return $chHTMLCode;
}


/** 
 * Modification d'une crise dans la table des crises d'AVF
 * la fonction n'accepte pas de paramètre et utilise l'uid de l'utilisateur courant
**/ 
function ch_calendar_mod() {

    global $wpdb;

    // On vérifie que l'utilisateur est connecté, sinon, on renvoie un message signalant qu'il faut l'être
    if ( !is_user_logged_in() ) {
        $chMessage = '<span style="color:#ff4111; font-weight:bold;">' . __('You must be connected to access the calendar.', 'ch-calendar') . '</span>';
        return $chMessage;
    } 

    /* Test de retour d'encodage */
    /* Si encodage => vérification des inputs et insertion dans la table */
    if (isset($_POST["cdate-day"])) {
        $cdate_day = str_pad(sanitize_text_field($_POST["cdate-day"]), 2, '0', STR_PAD_LEFT);
        $cdate_month = str_pad(sanitize_text_field($_POST["cdate-month"]), 2, '0', STR_PAD_LEFT);
        $cdate_year = sanitize_text_field($_POST["cdate-year"]);
        $cdate_hour = str_pad(sanitize_text_field($_POST["cdate-hour"]), 2, '0', STR_PAD_LEFT);
        $cdate_minute = str_pad(sanitize_text_field($_POST["cdate-minute"]), 2, '0', STR_PAD_LEFT);
        $chDate = date("Y-m-d H:i:s", mktime($cdate_hour, $cdate_minute, 0, $cdate_month, $cdate_day, $cdate_year)); 

        $duree_hour = str_pad(sanitize_text_field($_POST["duree-hour"]), 2, '0', STR_PAD_LEFT);
        $duree_minute = str_pad(sanitize_text_field($_POST["duree-minute"]), 2, '0', STR_PAD_LEFT);
        $chDuree = date("Y-m-d H:i:s", mktime($duree_hour, $duree_minute, 0, $cdate_month, $cdate_day, $cdate_year)); 

        $intensity = sanitize_textarea_field($_POST["intensity"]);
        $treatment = sanitize_textarea_field($_POST["treatment"]);
        $comment = sanitize_textarea_field($_POST["comment"]);
        $comment = str_replace ("\'","’",$comment);     /* Transforme l'escape de ' créer par sanitize */
        $comment = str_replace (";","",$comment);       /* suppress ; */
        $comment = str_replace ("(","",$comment);       /* suppress ( */
        $comment = str_replace (")","",$comment);       /* suppress ) */
        $comment = str_replace( array("\r\n","\r","\n") , ' ' , $comment);
        
        /* Création de la requête SQL */
        $pid = $_POST["pid"]; 
        $sql = "UPDATE {$wpdb->prefix}avf_crises SET `cdate` = '$chDate', `cduration` = '$chDuree', `cintensity` = '$intensity', `ctreatment` = '$treatment', `ccomment` = '$comment' WHERE pid = '$pid'";
        // Exécution de la requête SQL (mise à jour)
        $results = $wpdb->get_results($sql) ;
/*        if ($wpdb->last_error) {
            error_log('Error: ' . $wpdb->last_error);
        }  */      
        $chHTMLCode = '<div class="divnote"><strong>'.__('Record modified:', 'ch-calendar').'</strong><br />'.__('Date: ', 'ch-calendar').$chDate.'<br />'.__('Duration: ', 'ch-calendar').$duree_hour.':'.$duree_minute.'<br />'.__('Intensity: ', 'ch-calendar').$intensity.'<br />'.__('Treatment: ', 'ch-calendar').$treatment.'<br />'.__('Comment: ', 'ch-calendar').$comment.'</div>'; 
    }
    if (isset($chHTMLCode)) {
        $chHTMLCode .= '<h3>'.__('Select the attack to be modified. Then modify it in the form.', 'ch-calendar').'</h3>'; 
    } else {
        $chHTMLCode = '<h3>'.__('Select the attack to be modified. Then modify it in the form.', 'ch-calendar').'</h3>'; 
    } 
    $current_user = wp_get_current_user();
    $chUid = $current_user->ID;
    $chHTMLCode .= ch_read_for_mod_or_del($chUid,'Modify');
    /* Fin du formulaire (contient également la <table>) */
    $chHTMLCode .= '</form>';
    $chHTMLCode .= '</div>';
    return $chHTMLCode;
}


/** 
 * Supprime l'enregistrement sélectionné pour l'utilisateur courant
 * la fonction n'accepte pas de paramètre et utilise l'uid de l'utilisateur courant
**/ 
function ch_calendar_del_rec() {
    global $wpdb;

    // On vérifie que l'utilisateur est connecté, sinon, on renvoie un message signalant qu'il faut l'être
    if ( !is_user_logged_in() ) {
        $chMessage = '<span style="color:#ff4111; font-weight:bold;">' . __('You must be connected to access the calendar.', 'ch-calendar') . '</span>';
        return $chMessage;
    } 

    /* Test de retour d'encodage */
    /* Si encodage => vérification des inputs et insertion dans la table */
    if (isset($_POST["cdate-day"])) {
        $cdate_day = str_pad(sanitize_text_field($_POST["cdate-day"]), 2, '0', STR_PAD_LEFT);
        $cdate_month = str_pad(sanitize_text_field($_POST["cdate-month"]), 2, '0', STR_PAD_LEFT);
        $cdate_year = sanitize_text_field($_POST["cdate-year"]);
        $cdate_hour = str_pad(sanitize_text_field($_POST["cdate-hour"]), 2, '0', STR_PAD_LEFT);
        $cdate_minute = str_pad(sanitize_text_field($_POST["cdate-minute"]), 2, '0', STR_PAD_LEFT);
        $chDate = date("Y-m-d H:i:s", mktime($cdate_hour, $cdate_minute, 0, $cdate_month, $cdate_day, $cdate_year)); 

        $duree_hour = str_pad(sanitize_text_field($_POST["duree-hour"]), 2, '0', STR_PAD_LEFT);
        $duree_minute = str_pad(sanitize_text_field($_POST["duree-minute"]), 2, '0', STR_PAD_LEFT);
        $chDuree = date("Y-m-d H:i:s", mktime($duree_hour, $duree_minute, 0, $cdate_month, $cdate_day, $cdate_year)); 

        $intensity = sanitize_textarea_field($_POST["intensity"]);
        $treatment = sanitize_textarea_field($_POST["treatment"]);
        $comment = sanitize_textarea_field($_POST["comment"]);
        $comment = str_replace ("\'","’",$comment);     /* Transforme l'escape de ' créer par sanitize */
        $comment = str_replace (";","",$comment);       /* suppress ; */
        $comment = str_replace ("(","",$comment);       /* suppress ( */
        $comment = str_replace (")","",$comment);       /* suppress ) */
        $comment = str_replace( array("\r\n","\r","\n") , ' ' , $comment);
        /* Création de la requête SQL */
        $pid = $_POST["pid"]; 
        $sql = "DELETE FROM {$wpdb->prefix}avf_crises WHERE pid = '$pid'";
        // Exécution de la requête SQL (mise à jour)
        $results = $wpdb->get_results($sql) ;
/*        if ($wpdb->last_error) {
            error_log('Error: ' . $wpdb->last_error);
        } */      
        $chHTMLCode = '<div class="divnote"><strong>'.__('Record deleted:', 'ch-calendar').'</strong><br />'.__('Date: ', 'ch-calendar').$chDate.'<br />'.__('Duration: ', 'ch-calendar').$duree_hour.':'.$duree_minute.'<br />'.__('Intensity: ', 'ch-calendar').$intensity.'<br />'.__('Treatment: ', 'ch-calendar').$treatment.'<br />'.__('Comment: ', 'ch-calendar').$comment.'</div>'; 
    }
    if (isset($chHTMLCode)) {
        $chHTMLCode .= '<h3>'.__('Select the attack to be deleted. Then confirm it in the form.', 'ch-calendar').'</h3>'; 
    } else {
        $chHTMLCode = '<h3>'.__('Select the attack to be deleted. Then confirm it in the form.', 'ch-calendar').'</h3>'; 
    } 
    $current_user = wp_get_current_user();
    $chUid = $current_user->ID;
    $chHTMLCode .= ch_read_for_mod_or_del($chUid,'Delete');
    /* Fin du formulaire (contient également la <table>) */
    $chHTMLCode .= '</form>';
    $chHTMLCode .= '</div>';
    return $chHTMLCode;
}


/** 
 * Supprime tous les enregistrements pour l'utilisateur courant
 * la fonction n'accepte pas de paramètre et utilise l'uid de l'utilisateur courant
**/ 
function ch_calendar_del_all() {
    global $wpdb;

    // On vérifie que l'utilisateur est connecté, sinon, on renvoie un message signalant qu'il faut l'être
    if ( !is_user_logged_in() ) {
        $chMessage = '<span style="color:#ff4111; font-weight:bold;">' . __('You must be connected to access the calendar.', 'ch-calendar') . '</span>';
        return $chMessage;
    }
    if (isset($_POST["edit-submit"])) {
        $current_user = wp_get_current_user();
        $chUid = $current_user->ID;
        // Vérifier que le calendrier existe (au moins un record ) */
        /* Création de la commande SQL */
        $sql = "Select * FROM {$wpdb->prefix}avf_crises WHERE uid = '$chUid'";
        $results = $wpdb->get_results($sql) ;
        if (empty($results)) {
            // Calendrier vide : mise à jour de la variable de retour avec le message ad hoc et renvoie du résultat
            $chHTMLCode .= '<h3 style="color:#ff4111; font-weight:bold;">' . __('Your calendar is empty.', 'ch-calendar') . '</h3>';
            return $chHTMLCode;
        } else {
            /* Création de la commande SQL pour supprimer les données */
            $sql = "DELETE FROM {$wpdb->prefix}avf_crises WHERE uid = '$chUid'";
            // Exécution de la requête SQL (on supprime tous les enregistrements pour l'utilisateur courant)
            $results = $wpdb->get_results($sql) ;  
/*            if ($wpdb->last_error) {
                error_log('Error: ' . $wpdb->last_error);
            }  */
            $chHTMLCode = '<div class="divnote"><strong>'.__('Your calendar has been deleted.', 'ch-calendar').'</strong></div>';
            return $chHTMLCode; 
        }
    }
    $chHTMLCode = '<h3>'.__('Erase all your data', 'ch-calendar').'</h3>';
    $chHTMLCode .= '<p>'.__('Are you sure you want to delete your calendar?', 'ch-calendar').'</p>';
    $chHTMLCode .= '<div class="chform">';
    $chHTMLCode .= '<form action="" method="POST">';
    $chHTMLCode .= '<input type="submit" id="edit-submit" name="edit-submit" value="'.__('I confirm', 'ch-calendar').'" />';
    $chHTMLCode .= '</form>';
    $chHTMLCode .= '</div>';
    return $chHTMLCode;
}


/** 
 * Affichage d'une carte de membre à imprimer
**/ 
function ch_calendar_user_data() {

    // On vérifie que l'utilisateur est connecté, sinon, on renvoie un message signalant qu'il faut l'être
    if ( !is_user_logged_in() ) {
        $chMessage = '<p style="transform: rotate(0.95turn);"><span style="color:#ff4111; font-weight:bold;">' . __('You must be connected to create your member card.', 'ch-calendar') . '</span></p>';
        return $chMessage;
    }
    $current_user = wp_get_current_user();
    $chUid = $current_user->ID;
    $roles = (array) $current_user->roles;
    if ($roles[0] = 'administrator') {
        $roles[0] = __('administrator', 'ch-calendar');
    }
    $userData = '<p><strong>'.__('Member N°: ', 'ch-calendar').$chUid.'</strong><br />'.$roles[0].'</p>';
    $userData .= '<p>';
    
     if ( $current_user->first_name ) {
         if ( $current_user->last_name ) {
             $userData .= __('First name: ', 'ch-calendar').'<strong>'.$current_user->first_name.'</strong><br />'.__('Name: ', 'ch-calendar').'<strong>'.$current_user->last_name.'</strong>';
         } else {
             $userData .= $current_user->first_name;
         }
     } else {
         $userData .= $current_user->display_name;
     }
     
     $userData .= '</p>';
     
     return $userData;
}

/* ------------------------------------------------------------------------------------------------- */

/**
 * ajout des shortcodes  pour lire, insérer, modifier et supprimer
**/

// appel de la fonction 'ch_calendar_read' pour exécution du shortcode '[ch_read_table]'
add_shortcode('ch_read_table', 'ch_calendar_read');

// appel de la fonction 'ch_calendar_insert' pour exécution du shortcode '[ch_insert_table]'
add_shortcode('ch_insert_table', 'ch_calendar_insert');

// appel de la fonction 'ch_calendar_mod' pour exécution du shortcode '[ch_mod_table]'
add_shortcode('ch_mod_table', 'ch_calendar_mod');

// appel de la fonction 'ch_calendar_del_rec' pour exécution du shortcode '[ch_del_rec_table]'
add_shortcode('ch_del_rec_table', 'ch_calendar_del_rec');

// appel de la fonction 'ch_calendar_del_all' pour exécution du shortcode '[ch_del_all_table]'
// note: supprime tous les enregistrements pour l'utilisateur courant
add_shortcode('ch_del_all_table', 'ch_calendar_del_all');

// appel de la fonction 'ch_calendar_user_data' pour exécution du shortcode '[ch_user_data]'
add_shortcode('ch_user_data', 'ch_calendar_user_data');

/* ------------------------------------------------------------------------------------------------- */

/** Debugging : écrire dans le fichier debug.log :
 * error_log(string);
**/

?>