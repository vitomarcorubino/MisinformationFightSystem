<?php

function verificaFontiVerificate($riferimento) {
    $riferimento = strtolower($riferimento);
    $riferimento = str_replace(' ', '', $riferimento);

    $riferimento = filter_var($riferimento, FILTER_SANITIZE_URL);

    // Verifico se il riferimento alla fonte è un URL valido
    if (filter_var($riferimento, FILTER_VALIDATE_URL) !== false) {
        //$API_key = '79b67dc8a2b74a6bac9c2e5fa6bdffdd';
        //$API_key = 'aeae37e28316464ba3bf5b2392ee5edd';
        $API_key = 'b6cd9be2f12443889312279f2adfebbb';
        $api_url = 'https://newsapi.org/v2/top-headlines/sources';

        $params = [
            "apiKey" => $API_key,
        ];

        $url = $api_url . '?' . http_build_query($params);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'ListaFontiAttendibili');

        $response = curl_exec($ch);

        curl_close($ch);

        if ($response !== false) {
            $data = json_decode($response, true);
            
            if ($data['status'] === 'ok') {
                $sources = $data['sources'];

                $numeroFonti = count($sources);

                $i = 0;
                $trovata = false;
                while ($i < $numeroFonti && !$trovata) {
                    $nomeFonte = $sources[$i]['name'];
                    $urlFonte = $sources[$i]['url'];
    
                    if ($riferimento == $urlFonte) {
                        $trovata = true;
                    }
    
                    $i++;
                }
    
                return $trovata;
                /*
                if ($trovata) {
                    echo "Fonte trovata: " . $riferimento . "\n";
                } else {
                    echo "Fonte non trovata\n";
                }
                */
            }
        }
    } else {
        echo $riferimento . "non è un riferimento URL valido";
    }
}

function ottieniValutazioniEsterne($riferimento) {
    $API_key = '3b56194a8edb10bd34ca1ad88f08fcd5f93d1db81014278d0c8de79d9cbe5358';
    $api_url_place = 'https://serpapi.com/search.json';
    
    $parts = parse_url($riferimento);
    $host = $parts['host'];
    // Rimuovi il prefisso "www."
    $host = str_replace('www.', '', $host);
    // Ottieni solo il nome del dominio senza il suffisso ".com"
    $dominio = explode('.', $host)[0];

    $place = $dominio;
    $place = str_replace(' ', '', $place);

    $params_place = [
        "key" => $API_key,
        "engine" => "google_maps",
        "q" => $place,
        "type" => "search",
        "hl" => "it",
    ];

    $url_place = $api_url_place . '?' . http_build_query($params_place);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url_place);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

    $response_place = curl_exec($ch);

    if ($response_place !== false) {
        $data_place = json_decode($response_place, true);
        if (isset($data_place['local_results'])) {
            $local_results = $data_place['local_results'];
            $data_id = $local_results[0]['data_id'];

            $api_url_reviews = 'https://serpapi.com/search.json';

            $params_reviews = [
                "key" => $API_key,
                "engine" => "google_maps_reviews",
                "data_id" => $data_id,
            ];

            $url_reviews = $api_url_reviews . '?' . http_build_query($params_reviews);

            curl_setopt($ch, CURLOPT_URL, $url_reviews);

            $response_reviews = curl_exec($ch);

            if ($response_reviews !== false) {
                $data_reviews = json_decode($response_reviews, true);

                if ($data_reviews['search_metadata']['status'] == 'Success') {

                    if (isset($data_reviews['place_info']['rating'])) {
                        $rating = $data_reviews['place_info']['rating'];

                        //echo "Valutazione media: " . $rating . "\n";
                    } else {
                        $rating = -1; // Valutazione non disponibile
                    }

                    if (isset($data_reviews['reviews'])) {
                        $reviews = $data_reviews['reviews'];
                    } else {
                        $reviews = null;
                    }
                    
                }
                
                return [$rating, $reviews];
            }
        }
        
    }

    curl_close($ch);
}

function getIndiceFonte($riferimento) {
    $indiceFonte = 1;
    
    [$rating, $reviews] = ottieniValutazioniEsterne($riferimento);

    if ($rating > 0) {
        $indiceFonte = round($rating);
    }

}

$riferimento = 'https://www.ilsole24ore.com';
//$riferimento = 'https://larefubblica.it/';

[$rating, $reviews] = ottieniValutazioniEsterne($riferimento);

if ($rating > 0) {
    echo "Indice di attendibilità: " . round($rating) . "\n\n";
} else {
    echo "Indice di attendibilità 1\n\n";
}


if (verificaFontiVerificate($riferimento)) {
    echo "'". $riferimento . "' è presente in una lista di fonti verificate\n\n";
} else {
    echo "Fonte non verificata\n\n";
}

if ($reviews != null) {
    $numeroRecensioni = count($reviews);
                    
    if ($numeroRecensioni > 5) {
        $numeroRecensioni = 5;
    }
    
    echo "RECENSIONI PIÙ RILEVANTI:\n\n";

    for ($i = 0; $i < $numeroRecensioni; $i++) {
        $userRating = $reviews[$i]['rating'];
        $reviewDate = $reviews[$i]['date'];
        $reviewText = $reviews[$i]['snippet'];

        echo "Valutazione utente: " . $userRating . "\n";
        echo "Data: " . $reviewDate . "\n";
        echo "Recensione: " . $reviewText . "\n\n";
    }
} else {
    echo "Non sono disponibili recensioni\n";
}

?>