<?php

/**
 * Costruisce un URL a partire dall'URL della directory del tema e un appendice variabile.
 * Se non viene fornito alcun appendice, verrà usato un valore di default modificabile tramite filtro.
 *
 * @param string $appendice L'appendice da aggiungere all'URL della directory del tema.
 * @return string L'URL completo.
 */
function lp_el_widets_base_uri($appendice = '') {

    // Ottieni l'URL della directory del tema o del tema child
    $base_url = get_stylesheet_directory_uri();
    
    // Ottieni il percorso predefinito dal filtro
    $default_appendice = apply_filters('lp_el_widets_custom_base_uri', 'lp-elementor-widgets/');
    
    // Usa il percorso predefinito se l'appendice non è fornito
    $appendice = $appendice ? $appendice : $default_appendice;
    
    // Assicurati che l'appendice sia un percorso relativo, non un URL assoluto
    if (filter_var($appendice, FILTER_VALIDATE_URL)) {
        return $appendice; // Se è un URL assoluto, lo restituisce così com'è
    }
    
    // Aggiungi l'appendice all'URL base (percorso relativo)
    $full_url = trailingslashit($base_url) . ltrim($appendice, '/');
    
    return $full_url;
}