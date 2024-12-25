# Alejandro IA - Plugin WordPress

Plugin d'intelligence artificielle conversationnel pour le Club Costa Tropical.

## Fonctionnalités

- Chat interactif avec intelligence artificielle
- Support vocal (synthèse et reconnaissance vocale)
- Mode mains libres pour une conversation naturelle
- Support multilingue (Français, Espagnol, Anglais)
- Services météo intégrés (OpenWeather, AEMET)
- Traduction en temps réel (DeepL)
- Intégration Google Maps

## Installation

1. Téléchargez le plugin et décompressez-le dans le répertoire `wp-content/plugins/` de votre installation WordPress
2. Activez le plugin dans l'interface d'administration WordPress
3. Configurez les clés API nécessaires dans les réglages du plugin (menu "Alejandro IA" > "API & Services") :
   - Clé API Google (Gemini et Cloud Text-to-Speech)
   - Clé API OpenWeather
   - Clé API AEMET (optionnel, pour la météo en Espagne)
   - Clé API DeepL

## Configuration requise

- WordPress 5.0 ou supérieur
- PHP 7.4 ou supérieur
- Extensions PHP : curl, json
- Navigateur moderne avec support WebSpeech API pour les fonctionnalités vocales

## Administration

Le plugin propose trois sections de configuration :

1. **Général**
   - Langue par défaut
   - Comportement du chatbot
   - Options d'affichage

2. **API & Services**
   - Configuration des clés API
   - Activation/désactivation des services

3. **Personnalisation**
   - Avatar du chatbot
   - Messages de bienvenue
   - Style et apparence

## Services intégrés

- **Intelligence Artificielle**
  - Google Gemini pour le traitement du langage naturel
  - Gestion du contexte pour des conversations cohérentes

- **Services Vocaux**
  - Synthèse vocale via Google Cloud Text-to-Speech
  - Reconnaissance vocale via WebSpeech API
  - Mode mains libres pour une conversation naturelle

- **Services Météo**
  - OpenWeather pour la météo mondiale
  - AEMET pour des données météo détaillées en Espagne

- **Traduction**
  - Service DeepL pour une traduction précise
  - Détection automatique de la langue
  - Support multilingue complet

## État actuel du projet (23 décembre 2024)

### Dernières modifications

1. **Optimisation de l'initialisation**
   - Réorganisation du chargement des traductions pour respecter les bonnes pratiques WordPress
   - Amélioration de la séquence d'initialisation des services
   - Correction des problèmes de chargement précoce des traductions

2. **Simplification des services**
   - Concentration sur Google Gemini comme service IA principal
   - Retrait des dépendances non essentielles (DeepL, etc.)
   - Amélioration de la gestion des erreurs

### Problèmes connus

1. **Avertissements de dépréciation**
   - Avertissements liés à la création de propriétés dynamiques dans certaines bibliothèques tierces
   - Ces avertissements n'affectent pas le fonctionnement du plugin mais doivent être surveillés

2. **Chargement des traductions**
   - Les traductions sont maintenant chargées correctement via le hook 'init'
   - Vérification nécessaire pour s'assurer que tous les textes sont bien traduits

3. **Dépendances**
   - Nécessité de maintenir la compatibilité avec PHP 7.4+
   - Surveillance des mises à jour des APIs tierces (Google Gemini)

### Prochaines étapes

1. **Optimisations**
   - Tests approfondis du nouveau système d'initialisation
   - Vérification de la compatibilité avec WordPress 6.7+
   - Documentation des nouvelles modifications

2. **Améliorations prévues**
   - Mise en place d'un système de cache plus efficace
   - Amélioration de la gestion des erreurs API
   - Optimisation des performances

3. **Documentation**
   - Mise à jour de la documentation technique
   - Ajout d'exemples d'utilisation des nouveaux hooks
   - Guide de dépannage pour les problèmes courants

## Support

Pour toute question ou assistance :
- Documentation technique : [lien vers la documentation]
- Support technique : support@clubcostatropical.es
