<?php
/**
 * Classe pour gérer les interactions avec l'API Gemini
 */
class Alejandro_Gemini_Provider {
    private $api_key;
    private $api_url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent';
    private $context = [];

    public function __construct($api_key) {
        $this->api_key = $api_key;
    }

    /**
     * Envoie une requête à l'API Gemini
     */
    public function generate_response($message, $language = 'fr') {
        // Construire le contexte du système
        $system_prompt = $this->get_system_prompt($language);
        
        // Construire la requête
        $data = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $system_prompt],
                        ['text' => $message]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.7,
                'topK' => 40,
                'topP' => 0.95,
                'maxOutputTokens' => 1024,
            ],
            'safetySettings' => [
                [
                    'category' => 'HARM_CATEGORY_HARASSMENT',
                    'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                ],
                [
                    'category' => 'HARM_CATEGORY_HATE_SPEECH',
                    'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                ],
                [
                    'category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT',
                    'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                ],
                [
                    'category' => 'HARM_CATEGORY_DANGEROUS_CONTENT',
                    'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                ]
            ]
        ];

        // Envoyer la requête
        $response = wp_remote_post($this->api_url . '?key=' . $this->api_key, [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode($data),
            'timeout' => 30,
        ]);

        // Vérifier la réponse
        if (is_wp_error($response)) {
            throw new Exception('Erreur de communication avec Gemini: ' . $response->get_error_message());
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['error'])) {
            throw new Exception('Erreur Gemini: ' . $body['error']['message']);
        }

        // Extraire la réponse
        if (isset($body['candidates'][0]['content']['parts'][0]['text'])) {
            $response_text = $body['candidates'][0]['content']['parts'][0]['text'];
            
            // Ajouter au contexte
            $this->context[] = [
                'role' => 'user',
                'content' => $message
            ];
            $this->context[] = [
                'role' => 'assistant',
                'content' => $response_text
            ];

            return $response_text;
        }

        throw new Exception('Format de réponse Gemini invalide');
    }

    /**
     * Obtient le prompt système selon la langue
     */
    private function get_system_prompt($language) {
        $prompts = [
            'fr' => "Tu es Alejandro, un assistant virtuel sympathique et serviable créé pour le Club Costa Tropical. 
                    Tu dois toujours répondre en français de manière naturelle et conviviale.
                    Tu as accès à des informations sur la météo, le trafic et les points d'intérêt locaux.
                    Tu peux aider avec la traduction entre le français, l'espagnol et l'anglais.
                    Garde tes réponses concises mais utiles.",
            
            'es' => "Eres Alejandro, un asistente virtual amigable y servicial creado para el Club Costa Tropical.
                    Debes responder siempre en español de manera natural y amigable.
                    Tienes acceso a información sobre el clima, el tráfico y los puntos de interés locales.
                    Puedes ayudar con la traducción entre francés, español e inglés.
                    Mantén tus respuestas concisas pero útiles.",
            
            'en' => "You are Alejandro, a friendly and helpful virtual assistant created for Club Costa Tropical.
                    You must always respond in English in a natural and friendly way.
                    You have access to information about weather, traffic and local points of interest.
                    You can help with translation between French, Spanish and English.
                    Keep your answers concise but helpful."
        ];

        return $prompts[$language] ?? $prompts['fr'];
    }

    /**
     * Réinitialise le contexte de la conversation
     */
    public function reset_context() {
        $this->context = [];
    }
}
