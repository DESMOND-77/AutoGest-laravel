# Politique de sécurité

## Versions supportées

Auto-GestBoard est actuellement en développement actif sur la branche `main`. Les correctifs de sécurité sont appliqués sur la dernière version publiée.

| Version | Support sécurité   |
| ------- | ------------------ |
| 1.x     | :white_check_mark: |
| < 1.0   | :x:                |

> **TODO :** Compléter ce tableau à chaque nouvelle version majeure publiée, en indiquant la dernière version mineure supportée par branche majeure.

## Signalement d'une vulnérabilité

Merci de **ne jamais signaler une vulnérabilité de sécurité via une Issue GitHub publique**. Une divulgation publique avant correction expose les instances en production de tous les établissements utilisant Auto-GestBoard (plateforme multi-tenant hébergeant des données personnelles d'élèves et de moniteurs).

Pour signaler une vulnérabilité de manière responsable :

1. Utilisez l'onglet **[Security Advisories](https://github.com/DESMOND-77/AutoGest/security/advisories/new)** du dépôt GitHub (recommandé — canal chiffré et privé), **ou**
2. Envoyez un e-mail à **TODO: Complete with project-specific information** (adresse de contact sécurité à définir) en incluant :
   - une description claire de la vulnérabilité et de son impact potentiel (fuite de données inter-tenant, contournement d'authentification, injection, etc.) ;
   - les étapes de reproduction (requête HTTP, payload, compte de test utilisé) ;
   - la version ou le commit concerné ;
   - si possible, un correctif ou une piste de correction.

Merci de fournir suffisamment d'informations pour reproduire le problème (URL, rôle utilisateur concerné — Super-Admin, Admin, Moniteur ou Élève — et `structure_id` de test).

## Délai de réponse

| Étape                                   | Délai cible          |
| ---------------------------------------- | --------------------- |
| Accusé de réception du signalement       | 48 heures ouvrées      |
| Évaluation initiale et sévérité (CVSS)   | 5 jours ouvrés         |
| Correctif ou plan de mitigation          | 30 jours (critique), 90 jours (autre) |
| Publication de l'avis de sécurité        | Après déploiement du correctif |

Ces délais sont des objectifs et peuvent varier selon la complexité de la vulnérabilité et les ressources disponibles du mainteneur.

## Divulgation responsable

Nous demandons aux personnes qui signalent une vulnérabilité de :

- nous laisser un délai raisonnable pour corriger le problème avant toute divulgation publique ;
- ne pas exploiter la vulnérabilité au-delà de ce qui est strictement nécessaire pour la démontrer (pas d'accès, de modification ou de suppression de données réelles appartenant à un tenant autre que le vôtre) ;
- ne pas utiliser d'outils d'attaque automatisés à fort impact (scan de masse, déni de service) contre les environnements de démonstration ou de production ;
- agir de bonne foi et respecter la vie privée des utilisateurs des établissements utilisant la plateforme.

En contrepartie, nous nous engageons à :

- accuser réception rapidement et vous tenir informé de l'avancement ;
- vous créditer (si vous le souhaitez) dans l'avis de sécurité une fois le correctif publié ;
- ne pas engager de poursuites contre les personnes respectant cette politique de divulgation responsable.

## Politique CVE

Pour toute vulnérabilité confirmée d'un impact significatif (élévation de privilèges, contournement de l'isolation multi-tenant, exécution de code arbitraire, injection SQL), une demande de CVE sera effectuée via la fonctionnalité **GitHub Security Advisories**, qui permet la publication coordonnée d'un identifiant CVE une fois le correctif disponible.

Les vulnérabilités mineures (bugs de configuration, dépendances obsolètes sans exploit connu) sont traitées via une mise à jour standard, documentée dans le [CHANGELOG](CHANGELOG.md), sans nécessairement faire l'objet d'un CVE dédié.

## Bonnes pratiques déjà en place

Ce projet applique par construction, au niveau architectural, plusieurs mesures de défense en profondeur :

- isolation multi-tenant structurelle (`structure_id` appliqué via un *global scope* Eloquent, et non par convention manuelle) ;
- autorisation systématique via des **Policies** Laravel vérifiant à la fois l'appartenance au tenant et la relation métier (ex. un moniteur ne peut consulter que ses élèves assignés) ;
- protection CSRF active par défaut sur toutes les routes web ;
- mots de passe hashés (bcrypt) et jamais stockés en clair, y compris en session ;
- requêtes préparées systématiques via Eloquent/Query Builder (pas de SQL brut concaténé) ;
- analyse de sécurité automatisée du code via **CodeQL** (voir [`.github/workflows/codeql.yml`](.github/workflows/codeql.yml)) ;
- mise à jour automatisée des dépendances via **Dependabot** (voir [`.github/dependabot.yml`](.github/dependabot.yml)).
