<?php
// Funzione per ottenere i dati dalla Google Fact Check API
function getIndiceNotiziaTestuale($query) {
    $api_url = 'https://factchecktools.googleapis.com/v1alpha1/claims:search';
    $api_key = 'AIzaSyBBZdy_6pR_sIDLxuCxAFGMT-mfBKQi5p8'; // Inserisci la tua chiave API qui

    $params = array(
        'key' => $api_key,
        'query' => $query,
        //'languageCode' => 'it'
    );

    $url = $api_url . '?' . http_build_query($params);
    //echo "url" . $url . "\n";
    $response = file_get_contents($url);
    
    if ($response !== false) {
        $data = json_decode($response, true);
        return $data;
    }

    return null;
}

/*
function getIndiceImmagine($apiKey, $imageContent, $features = array()) {
    // Configurazione della richiesta per l'API di Google Cloud Vision
    $apiKey = 'AIzaSyBBZdy_6pR_sIDLxuCxAFGMT-mfBKQi5p8'; // Inserisci la tua chiave API qui
    $url = 'https://vision.googleapis.com/v1/images:annotate'; // Costruisci l'URL della richiesta

    // Costruisci la richiesta JSON
    $request = array(   
        'requests' => array(   
            array( 
                'image' => array(
                    'content' => $imageContent
                ), 
                'features' => $features // Specifica il tipo di verifica desiderato
            )
        )
    );

    // Esegui la richiesta all'API di Google Cloud Vision utilizzando file_get_contents
    $url = $url . '?key=' . $apiKey;

    $options = array(
        'http' => array(
            'header'  => 'Content-Type: application/json',
            'method'  => 'POST',
            'content' => json_encode($request),
        ),
    );

    $context  = stream_context_create($options);
    $response = file_get_contents($url, false, $context);

    // Elabora la risposta JSON
    $responseData = json_decode($response, true);
    return $responseData;
}
*/

function fonteVerificata($riferimento) {
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

function getReviews($riferimento) {
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
                    /*
                    $numeroRecensioni = count($reviews);
                    
                    if ($numeroRecensioni > 5) {
                        $numeroRecensioni = 5;
                    }
                    
                    echo "Le recensioni più rilevanti:\n";

                    for ($i = 0; $i < $numeroRecensioni; $i++) {
                        $userRating = $reviews[$i]['rating'];
                        $reviewDate = $reviews[$i]['date'];
                        $reviewText = $reviews[$i]['snippet'];

                        echo "Valutazione utente: " . $userRating . "\n";
                        echo "Data: " . $reviewDate . "\n";
                        echo "Recensione: " . $reviewText . "\n\n";
                    }*/
                } else {
                    $reviews = null;
                }
                
            }
            
            return [$rating, $reviews];
        }
    }

    curl_close($ch);
}

    // Esempio di utilizzo
   if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        
        if (isset($_POST['q7_cosaVuoi']) && !empty($_POST['q7_cosaVuoi'])) {
            $opzione_selezionata = $_POST['q7_cosaVuoi'];
            
            if ($opzione_selezionata == 'Notizia testuale') {
                        $indiceAttendibilita = 5;
                        $trovate = 0;

                // Ottieni il testo o l'URL dal form inviato
                $query= $_POST['riferimento'];

                // Esegui la verifica sulla notizia
                $result = getIndiceNotiziaTestuale($query);
                
                $indiceAttendibilita = 0;
                
                if ($result) {
                    // Processa i dati ottenuti dalla API
                    foreach ($result['claims'] as $claim) {
                        $claim_text = $claim['text'];
                        $claim_url = $claim['claimReview'][0]['url'];
                        $claim_rating = $claim['claimReview'][0]['textualRating'];

                        echo 'Dichiarazione: ' . $claim_text . '<br>';
                        echo 'Valutazione: ' . $claim_rating . '<br>';

                        $words_to_check = array("fake", "fals", "bufala", "modif");

                        foreach ($words_to_check as $word) {
                            if (str_contains(strtolower($claim_rating), $word) !== false) {
                                $trovate++;
                                //echo "Trovato: " . $word . "<br>";
                                //echo "paroleTrovate = " . $trovate . "<br>";
                            }
                        }
                        echo "<br>";
                    }
                
                    $indiceAttendibilita = $indiceAttendibilita - (2 * $trovate);

                    if ($indiceAttendibilita < 1) {
                        $indiceAttendibilita = 1;
                    }

                    echo 'Indice di attendibilità: ' . $indiceAttendibilita . '<br>';

                } else {
                    echo 'Nessun risultato.';
                }
            }

            if ($opzione_selezionata == 'Fonte') {
                $riferimento = 'https://www.ilsole24ore.com';

                [$rating, $reviews] = getReviews($riferimento);

                if ($rating != -1) {
                    echo "Indice di attendibilità: " . round($rating) . "\n\n";
                } else {
                    echo "Valutazione non disponibile\n";
                }


                if (fonteVerificata($riferimento)) {
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
                }
            }

            if ($opzione_selezionata == 'Immagine') {
                if(isset($_FILE['q6_caricareLimmagine']['name']) && !empty($_FILE['q6_caricareLimmagine']['name'])){
                    // Ottieni il contenuto dell'immagine
                    $imageContent = base64_encode(file_get_contents($_FILES['q6_caricareLimmagine']['name']));
                    $features = array(
                        array(
                            'type' => 'LABEL_DETECTION',
                            'maxResults' => 5
                        )
                    );

                    $responseData = getIndiceImmagine($apiKey, $features);

                    // Elabora le risposte dell'API di Google Cloud Vision
                    if (isset($responseData['responses'][0]['labelAnnotations'])) {
                        $labels = $responseData['responses'][0]['labelAnnotations'];
                        echo "Etichette:\n";

                        foreach ($labels as $label) {
                            $description = $label['description'];
                            $score = $label['score'];
                            echo "Etichetta: $description, Punteggio: $score <br>";
                        }
                    } else {
                        echo "Nessuna etichetta trovata.";
                    }
                } else {
                    echo "Nessuna immagine caricata.";
                }
            }

        } else {
            echo "Nessuna opzione selezionata";
        }
    }
?>