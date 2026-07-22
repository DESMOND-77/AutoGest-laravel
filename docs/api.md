# API

Auto-GestBoard est avant tout une application web rendue côté serveur (Blade). Il n'existe pas, à ce stade, d'API REST publique versionnée destinée à des clients tiers.

Deux familles d'endpoints JSON existent cependant et sont documentées ici.

## Authentification

Tous les endpoints ci-dessous sont protégés par les mêmes sessions/cookies que le reste de l'application (`auth` middleware de Laravel) et par les Policies du domaine concerné. Aucune clé d'API distincte n'est actuellement fournie.

> **TODO : Compléter avec les informations spécifiques au projet** — si une API publique authentifiée par jeton (Laravel Sanctum) est ajoutée à l'avenir, documenter ici le schéma d'authentification, le versionnement (`/api/v1/...`) et la politique de rate limiting.

## Entraînement au code (quiz)

Endpoints internes utilisés par l'espace élève pour l'entraînement au code théorique. Le score est **toujours calculé côté serveur** : aucune réponse correcte n'est jamais transmise au client avant la soumission d'une tentative.

| Méthode | Route | Rôle requis | Description |
| -------- | ------ | ------------ | ------------ |
| `GET`  | `/quiz` | `eleve` | Retourne une sélection de 20 questions avec leurs options (sans indication de la bonne réponse) |
| `POST` | `/quiz` | `eleve` | Soumet les réponses `{ "answers": { "<question_id>": "<option_id>", ... } }` et retourne le score calculé côté serveur |
| `GET`  | `/quiz/results` | `eleve` | Historique des tentatives de l'élève connecté |
| `GET`  | `/quiz/students/{student}/results` | `admin`, `moniteur` | Historique des tentatives d'un élève donné (soumis à la même Policy que la fiche élève) |

Exemple de réponse à `GET /quiz` :

```json
[
  {
    "id": 12,
    "prompt": "Que signifie un panneau triangulaire à bordure rouge ?",
    "category": "signalisation",
    "options": [
      { "id": 45, "text": "Une obligation" },
      { "id": 46, "text": "Un danger" },
      { "id": 47, "text": "Une interdiction" }
    ]
  }
]
```

Exemple de réponse à `POST /quiz` :

```json
{
  "attempt_id": 8,
  "score": 17,
  "total_questions": 20
}
```

## Exports CSV (rapports)

| Méthode | Route | Rôle requis | Contenu |
| -------- | ------ | ------------ | -------- |
| `GET` | `/reports/revenue.csv` | `admin` | Chiffre d'affaires mensuel (12 derniers mois) |
| `GET` | `/reports/exams.csv` | `admin` | Synthèse des résultats d'examens |
| `GET` | `/reports/students-by-stage.csv` | `admin` | Répartition des élèves par étape du cycle de vie |

Ces exports sont scopés au tenant de l'utilisateur connecté (aucune donnée d'un autre établissement n'y figure).

## Documents

| Méthode | Route | Rôle requis | Description |
| -------- | ------ | ------------ | ------------ |
| `GET` | `/documents/{document}/download` | Authentifié, scope tenant | Téléchargement d'un document (streamé, jamais servi depuis le web root) |

## Liste exhaustive des routes

Pour consulter l'ensemble des routes enregistrées à un instant donné, y compris celles ne renvoyant pas de JSON :

```bash
php artisan route:list
```
