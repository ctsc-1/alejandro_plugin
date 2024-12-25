<?php
if (!defined('ABSPATH')) {
    exit;
}

class Alejandro_Cache {
    private $table_name;
    private $max_cache_size = 10 * 1024 * 1024 * 1024; // 10 GB total

    // Quotas par type en pourcentage du cache total
    private $cache_quotas = [
        'voice' => 40,      // 4 GB pour les fichiers audio
        'weather' => 15,    // 1.5 GB pour les données météo
        'traffic' => 15,    // 1.5 GB pour les données trafic
        'translation' => 10, // 1 GB pour les traductions
        'images' => 10,     // 1 GB pour les images
        'default' => 10     // 1 GB pour les autres types
    ];

    // Durées de cache par type
    private $cache_durations = [
        'voice' => [
            'duration' => 2592000,     // 30 jours
            'min_hits' => 3            // Garder si utilisé au moins 3 fois
        ],
        'weather' => [
            'duration' => 3600,        // 1 heure
            'min_hits' => 1
        ],
        'traffic' => [
            'duration' => 900,         // 15 minutes
            'min_hits' => 1
        ],
        'translation' => [
            'duration' => 604800,      // 1 semaine
            'min_hits' => 2            // Garder si utilisé au moins 2 fois
        ],
        'images' => [
            'duration' => 1209600,     // 2 semaines
            'min_hits' => 2
        ],
        'default' => [
            'duration' => 86400,       // 24 heures
            'min_hits' => 1
        ]
    ];

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'alejandro_cache';
        
        // Ajouter la colonne hits si elle n'existe pas
        $this->maybe_add_hits_column();
        
        // Nettoyer le cache expiré toutes les heures
        add_action('wp_scheduled_delete', array($this, 'cleanup_expired_cache'));
        
        // Vérifier la taille du cache toutes les 6 heures
        if (!wp_next_scheduled('alejandro_check_cache_size')) {
            wp_schedule_event(time(), 'sixhours', 'alejandro_check_cache_size');
        }
        add_action('alejandro_check_cache_size', array($this, 'cleanup_cache_size'));
    }

    /**
     * Ajoute la colonne hits si nécessaire
     */
    private function maybe_add_hits_column() {
        global $wpdb;
        $column = $wpdb->get_results($wpdb->prepare(
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = 'hits'",
            DB_NAME,
            $this->table_name
        ));

        if (empty($column)) {
            $wpdb->query("ALTER TABLE {$this->table_name} ADD COLUMN hits INT DEFAULT 1");
        }
    }

    /**
     * Obtient une valeur du cache
     */
    public function get($key, $type = 'default', $language = 'es') {
        global $wpdb;

        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} 
            WHERE cache_key = %s 
            AND cache_type = %s 
            AND cache_language = %s 
            AND (expires_at IS NULL OR expires_at > NOW())",
            $key,
            $type,
            $language
        ));

        if (!$result) {
            return false;
        }

        // Incrémenter le compteur d'utilisation
        $wpdb->query($wpdb->prepare(
            "UPDATE {$this->table_name} SET hits = hits + 1 WHERE id = %d",
            $result->id
        ));

        // Si c'est un fichier, vérifier qu'il existe toujours
        if ($result->file_path && !file_exists($result->file_path)) {
            $this->delete($key, $type, $language);
            return false;
        }

        return json_decode($result->cache_data, true);
    }

    /**
     * Stocke une valeur dans le cache
     */
    public function set($key, $data, $type = 'default', $language = 'es', $file_path = null) {
        global $wpdb;

        // Vérifier le quota pour ce type
        if (!$this->check_type_quota($type, $file_path ? filesize($file_path) : 0)) {
            $this->cleanup_type_cache($type);
        }

        $duration = $this->cache_durations[$type]['duration'] ?? $this->cache_durations['default']['duration'];
        $expires_at = date('Y-m-d H:i:s', time() + $duration);
        
        $file_size = $file_path && file_exists($file_path) ? filesize($file_path) : 0;

        if (!$this->ensure_cache_space($file_size)) {
            return false;
        }

        $wpdb->replace(
            $this->table_name,
            array(
                'cache_key' => $key,
                'cache_type' => $type,
                'cache_language' => $language,
                'cache_data' => json_encode($data),
                'expires_at' => $expires_at,
                'file_path' => $file_path,
                'file_size' => $file_size,
                'hits' => 1
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d')
        );

        return true;
    }

    /**
     * Vérifie si un type de cache a atteint son quota
     */
    private function check_type_quota($type, $additional_size = 0) {
        global $wpdb;
        
        $quota_percentage = $this->cache_quotas[$type] ?? $this->cache_quotas['default'];
        $quota_size = ($this->max_cache_size * $quota_percentage) / 100;

        $current_size = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(file_size) FROM {$this->table_name} WHERE cache_type = %s",
            $type
        ));

        return ($current_size + $additional_size) <= $quota_size;
    }

    /**
     * Nettoie le cache d'un type spécifique
     */
    private function cleanup_type_cache($type) {
        global $wpdb;

        $min_hits = $this->cache_durations[$type]['min_hits'] ?? 1;

        // Supprimer d'abord les entrées peu utilisées
        $entries_to_delete = $wpdb->get_results($wpdb->prepare(
            "SELECT id, file_path FROM {$this->table_name} 
            WHERE cache_type = %s AND hits < %d 
            ORDER BY hits ASC, created_at ASC",
            $type,
            $min_hits
        ));

        foreach ($entries_to_delete as $entry) {
            if ($entry->file_path && file_exists($entry->file_path)) {
                @unlink($entry->file_path);
            }
            $wpdb->delete($this->table_name, array('id' => $entry->id));
        }

        // Si toujours pas assez d'espace, supprimer les plus anciennes entrées
        if (!$this->check_type_quota($type)) {
            $quota_size = ($this->max_cache_size * ($this->cache_quotas[$type] ?? 10)) / 100;
            $current_size = 0;

            $entries = $wpdb->get_results($wpdb->prepare(
                "SELECT id, file_path, file_size FROM {$this->table_name} 
                WHERE cache_type = %s ORDER BY hits DESC, created_at DESC",
                $type
            ));

            foreach ($entries as $entry) {
                if (($current_size + $entry->file_size) > $quota_size) {
                    if ($entry->file_path && file_exists($entry->file_path)) {
                        @unlink($entry->file_path);
                    }
                    $wpdb->delete($this->table_name, array('id' => $entry->id));
                } else {
                    $current_size += $entry->file_size;
                }
            }
        }
    }

    /**
     * Supprime une entrée du cache
     */
    public function delete($key, $type = 'default', $language = 'es') {
        global $wpdb;

        // Récupérer d'abord le fichier associé s'il existe
        $cache_item = $wpdb->get_row($wpdb->prepare(
            "SELECT file_path FROM {$this->table_name} 
            WHERE cache_key = %s AND cache_type = %s AND cache_language = %s",
            $key,
            $type,
            $language
        ));

        // Supprimer le fichier si présent
        if ($cache_item && $cache_item->file_path && file_exists($cache_item->file_path)) {
            @unlink($cache_item->file_path);
        }

        // Supprimer l'entrée de la base de données
        return $wpdb->delete(
            $this->table_name,
            array(
                'cache_key' => $key,
                'cache_type' => $type,
                'cache_language' => $language
            ),
            array('%s', '%s', '%s')
        );
    }

    /**
     * Nettoie le cache expiré
     */
    public function cleanup_expired_cache() {
        global $wpdb;

        // Récupérer les fichiers à supprimer
        $expired_files = $wpdb->get_col($wpdb->prepare(
            "SELECT file_path FROM {$this->table_name} 
            WHERE expires_at <= NOW() AND file_path IS NOT NULL"
        ));

        // Supprimer les fichiers
        foreach ($expired_files as $file) {
            if (file_exists($file)) {
                @unlink($file);
            }
        }

        // Supprimer les entrées expirées
        $wpdb->query("DELETE FROM {$this->table_name} WHERE expires_at <= NOW()");
    }

    /**
     * S'assure que le cache ne dépasse pas la taille maximale
     */
    public function ensure_cache_space($needed_space) {
        global $wpdb;

        $total_size = $wpdb->get_var("SELECT SUM(file_size) FROM {$this->table_name}");
        
        if (($total_size + $needed_space) <= $this->max_cache_size) {
            return true;
        }

        // Supprimer les entrées les plus anciennes jusqu'à avoir assez d'espace
        $entries_to_delete = $wpdb->get_results(
            "SELECT id, file_path, file_size FROM {$this->table_name} 
            ORDER BY created_at ASC"
        );

        foreach ($entries_to_delete as $entry) {
            if ($entry->file_path && file_exists($entry->file_path)) {
                @unlink($entry->file_path);
            }
            
            $wpdb->delete($this->table_name, array('id' => $entry->id));
            $total_size -= $entry->file_size;

            if (($total_size + $needed_space) <= $this->max_cache_size) {
                return true;
            }
        }

        return false;
    }

    /**
     * Nettoie le cache si la taille dépasse la limite
     */
    public function cleanup_cache_size() {
        $this->ensure_cache_space(0);
    }

    /**
     * Définit la taille maximale du cache
     */
    public function set_max_cache_size($size_in_mb) {
        $this->max_cache_size = $size_in_mb * 1024 * 1024;
    }

    /**
     * Définit la durée de cache pour un type spécifique
     */
    public function set_cache_duration($type, $duration_in_seconds) {
        $this->cache_durations[$type] = $duration_in_seconds;
    }
}
