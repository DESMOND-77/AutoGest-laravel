# Prompt - PWA avec cache offline (espace élève, agenda moniteur)

## Contexte

`docs/audit/etude-marche-fonctionnalites.md` §2.5 : la connectivité mobile reste chère et instable pour une partie des usagers gabonais (élèves, moniteurs en déplacement). Une PWA avec cache offline (Service Worker + IndexedDB) est recommandée **avant** une application mobile native, moins coûteuse à développer/maintenir pour un bénéfice similaire sur les écrans les plus consultés en mobilité : l'espace élève (planning, quiz) et l'agenda moniteur.

## Objectif

Rendre installable et partiellement utilisable hors-ligne : le planning déjà chargé (élève et moniteur) et l'app shell (navigation, mise en page) doivent rester consultables sans connexion, avec un indicateur clair de mode hors-ligne.

**Hors périmètre explicite** : les actions d'écriture (réservation, quiz, marquage de présence) ne sont **pas** à rendre fonctionnelles hors-ligne dans une première itération - la synchronisation différée est un problème de cohérence de données à part entière (conflits, double-écriture) qui ne doit pas être improvisé dans ce prompt. Se limiter à la lecture seule hors-ligne des données déjà chargées.

## Périmètre exact

- `public/manifest.json` : nom, icônes, couleurs de thème, `display: standalone`.
- Service Worker (`public/sw.js` ou généré via un plugin Vite PWA - vérifier si `vite.config.js` a déjà un plugin PWA disponible avant d'en ajouter un nouveau ; si un nouveau paquet npm est nécessaire, le proposer et le faire valider avant `npm install`, cf. contrainte projet de ne pas ajouter de dépendance sans accord).
- Stratégie de cache : `Cache API` pour les assets statiques (JS/CSS/icônes) en `stale-while-revalidate` ; `IndexedDB` (ou `Cache API` sur les réponses JSON/HTML déjà rendues) pour les dernières données de planning consultées par l'utilisateur courant.
- Indicateur UI de mode hors-ligne (bandeau visible, réutilisant le composant de bannière déjà généralisé au projet - cf. `docs/audit/roadmap.md` étape 10 point 5 UX-05).
- Scope d'application : cibler en priorité les routes `eleve.planning`, `moniteur.agenda`, `quiz.*` (lecture des questions déjà chargées) - pas l'ensemble de l'app admin, dont l'usage hors-ligne n'est pas le besoin identifié.

## Contraintes

- Ne jamais mettre en cache des données sensibles au-delà de ce que l'utilisateur voit déjà à l'écran (pas de pré-chargement agressif de données d'autres élèves/tenants).
- Le Service Worker doit être scopé pour ne jamais servir de contenu périmé silencieusement sans indication visuelle - toujours signaler à l'utilisateur qu'il consulte une version en cache.
- Pas de nouvelle dépendance npm sans validation explicite.
- Tester le comportement de désinstallation/mise à jour du Service Worker (un déploiement ne doit jamais laisser un utilisateur bloqué sur une version JS obsolète en cache).

## Étapes suggérées

1. Vérifier l'outillage front existant (`vite.config.js`, `package.json`) pour éviter de dupliquer un plugin déjà présent.
2. Définir précisément la liste des routes/données à mettre en cache (lecture seule) avec le métier/produit si le périmètre proposé ici n'est pas suffisant ou trop large.
3. Implémenter `manifest.json` + Service Worker + stratégie de cache.
4. Implémenter l'indicateur UI de mode hors-ligne.
5. Test manuel obligatoire (pas uniquement automatisé) : couper la connexion réseau dans les DevTools, vérifier que le planning déjà consulté reste lisible, que l'indicateur s'affiche, et qu'aucune action d'écriture ne semble silencieusement fonctionner hors-ligne (elle doit échouer clairement, pas être mise en file d'attente sans que l'utilisateur le sache).
6. Mesurer le gain réel de poids de données via l'onglet Network des DevTools sur une deuxième visite (objectif indicatif du marché : jusqu'à ~70% de données en moins sur les visites répétées).

## Critères d'acceptation

- L'app est installable (prompt d'installation navigateur disponible).
- Le planning déjà consulté par l'utilisateur reste lisible hors-ligne, avec indicateur visuel explicite.
- Aucune action d'écriture ne donne une fausse impression de succès hors-ligne.
- Une mise à jour de déploiement ne laisse aucun utilisateur bloqué sur une version obsolète du Service Worker.
