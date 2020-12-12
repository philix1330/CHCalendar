/** JavaScript du plugin ch-calendar
 * Remplir les champs du formulaire avec les valeurs de l'enregistrement sélectionné
**/
 
function getRadioValues(chPid,chDate,chDuration,chIntensity,chTreatment,chComment) {
    
     //alert('passage dans le JS : ' + chPid);
    // id="pid" 
    var pid = chPid; 
    let elementpid = document.getElementById('pid');
    elementpid.value = pid; 
    // id="edit-cdate-day" 
    var day = chDate.substr(0,2); 
    let elementday = document.getElementById('edit-cdate-day');
    elementday.value = day; 
    //id=edit-cdate-month
    var mon = chDate.substr(3,2); 
    let elementmon = document.getElementById('edit-cdate-month');
    elementmon.value = mon;
    //id=edit-cdate-year 
    var yea = chDate.substr(6,4); 
    let elementyea = document.getElementById('edit-cdate-year');
    elementyea.value = yea;
    //id=edit-cdate-hour 
    var hou = chDate.substr(11,2); 
    let elementhou = document.getElementById('edit-cdate-hour');
    elementhou.value = hou;
    //id=edit-cdate-minute 
    var min = chDate.substr(14,2); 
    let elementmin = document.getElementById('edit-cdate-minute');
    elementmin.value = min; 
    //id=edit-duree-hour 
    var dho = chDuration.substr(11,2); 
    let elementdho = document.getElementById('edit-duree-hour');
    elementdho.value = dho; 
    //id=edit-duree-minute 
    var dmi = chDuration.substr(14,2); 
    let elementdmi = document.getElementById('edit-duree-minute');
    elementdmi.value = dmi; 
    //id=edit-intensity 
    var ite = chIntensity; 
    let elementite = document.getElementById('edit-intensity');
    elementite.value = ite; 
    //id=edit-treatment 
    var tre = chTreatment; 
    let elementtre = document.getElementById('edit-treatment');
    elementtre.value = tre; 
    //id=edit-comments 
    var com = chComment; 
    let elementcom = document.getElementById('edit-comments');
    elementcom.value = com;
    // Changer de "hidden" à "submit" pour faire apparaître le bouton de modification
    document.getElementById('edit-submit').type = 'submit'; 
    
};