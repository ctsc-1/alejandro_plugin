<?php
if (!defined('ABSPATH')) {
    exit;
}

interface Weather_Provider_Interface {
    /**
     * Obtient les prévisions météo actuelles
     * @param float $lat Latitude
     * @param float $lon Longitude
     * @return array|WP_Error
     */
    public function get_current_weather($lat, $lon);

    /**
     * Obtient les prévisions météo pour les prochains jours
     * @param float $lat Latitude
     * @param float $lon Longitude
     * @param int $days Nombre de jours (max 7)
     * @return array|WP_Error
     */
    public function get_forecast($lat, $lon, $days = 5);

    /**
     * Obtient les alertes météo pour une zone
     * @param float $lat Latitude
     * @param float $lon Longitude
     * @return array|WP_Error
     */
    public function get_alerts($lat, $lon);

    /**
     * Obtient les conditions marines
     * @param float $lat Latitude
     * @param float $lon Longitude
     * @return array|WP_Error
     */
    public function get_marine_conditions($lat, $lon);
}
