<?php
if (!defined('ABSPATH')) {
    exit;
}

class Alejandro_Anthropic_Client {
    private $api_key;
    private $api_url = 'https://api.anthropic.com/v1/messages';
    private $model = 'claude-3-sonnet-20240229';

    public function __construct($api_key) {
        $this->api_key = $api_key;
    }

    public function send_message($message, $options = array()) {
        try {
            $headers = array(
                'anthropic-version' => '2023-06-01',
                'x-api-key' => $this->api_key,
                'content-type' => 'application/json'
            );

            $body = array(
                'model' => $this->model,
                'messages' => array(
                    array(
                        'role' => 'user',
                        'content' => $message
                    )
                ),
                'max_tokens' => 1024
            );

            if (!empty($options['system'])) {
                $body['system'] = $options['system'];
            }

            $response = wp_remote_post($this->api_url, array(
                'headers' => $headers,
                'body' => json_encode($body),
                'timeout' => 30
            ));

            if (is_wp_error($response)) {
                throw new Exception($response->get_error_message());
            }

            $body = json_decode(wp_remote_retrieve_body($response), true);

            if (empty($body['content'][0]['text'])) {
                throw new Exception(__('Réponse invalide de l\'API', 'alejandro-monitor'));
            }

            return $body['content'][0]['text'];

        } catch (Exception $e) {
            alejandro_debug_log('Erreur Anthropic API: ' . $e->getMessage());
            throw $e;
        }
    }
} 