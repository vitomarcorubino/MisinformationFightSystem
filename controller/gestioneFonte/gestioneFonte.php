<?php
if (is_user_logged_in()) {
    // Contenuto o azioni per gli utenti autenticati
} else {
    wp_die('Devi autenticarti per accedere a questa opzione.');
}


?>