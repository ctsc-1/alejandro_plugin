# Technique de nettoyage pour Alejandro IA

Ce document décrit la technique la plus efficace pour nettoyer le code du plugin Alejandro IA.

## Étapes de nettoyage

1. **Nettoyage des require_once**
   - Identifie les fichiers inclus en utilisant une table de hachage
   - Conserve uniquement la première occurrence de chaque fichier
   - Préserve l'ordre des includes pour maintenir les dépendances

2. **Nettoyage des sections shortcode**
   - Supprime toutes les sections de shortcode
   - Ajoute une seule section propre à la fin du formulaire
   - Maintient la structure HTML cohérente

3. **Nettoyage des hooks WordPress**
   - Utilise une table de hachage pour suivre les hooks vus
   - Évite les doublons d'enregistrement de hooks
   - Préserve l'ordre d'exécution original

4. **Nettoyage des éléments vides**
   - Supprime les div vides
   - Maintient la structure du document

## Bonnes pratiques

1. **Gestion de l'encodage**
   - Toujours utiliser UTF-8 pour la lecture et l'écriture
   - Éviter les problèmes de caractères spéciaux

2. **Sauvegarde**
   - Toujours faire une sauvegarde avant le nettoyage
   - Vérifier le fichier après chaque modification

3. **Validation**
   - Tester le plugin après le nettoyage
   - Vérifier que toutes les fonctionnalités marchent

## Commandes PowerShell utiles

```powershell
# Vérifier les duplications
Get-Content alejandro-ia.php | Select-String -Pattern 'pattern'

# Nettoyer le fichier
./clean_master.ps1

# Vérifier l'encodage
[System.IO.File]::ReadAllText("alejandro-ia.php", [System.Text.Encoding]::UTF8)
```
