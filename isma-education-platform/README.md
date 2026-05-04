# ISMA Education Platform - Plugin WordPress

## Description
Plateforme complète de gestion éducative pour ISMA avec cours, exercices, paiements, forum alumni et dashboards personnalisés.

## Fonctionnalités

### Pour les Étudiants
- Consultation des cours (PDF protégé contre captures)
- Dépôt des réponses aux exercices avec deadline
- Dashboard personnel: paiement, cours, exercices, notes, actualités
- Historique de paiement avec rappels et notifications
- Accès au forum alumni et offres d'emploi

### Pour les Formateurs
- Publication de cours et exercices (format PDF)
- Soumission pour charte avant publication
- Gestion des soumissions étudiants avec notation
- Dashboard formateur: espace cours, exercices, paiements, rapports
- Affichage en rouge des réponses en retard
- Espace de propositions d'idées
- Rapports de formation générés automatiquement

### Pour l'Administration (Super Admin ISMA + 3 Modérateurs)
- Charte et validation des cours/exercices
- Gestion des paiements étudiants
- Suivi de l'activité
- Validation des propositions formateurs
- Envoi de notifications et emails

### Club Alumni
- Forum de discussion
- Publication d'offres d'emploi

## Installation

1. Téléchargez le dossier `isma-education-platform`
2. Uploadez-le dans `/wp-content/plugins/`
3. Activez le plugin via l'admin WordPress
4. Configurez les rôles et attribuez les utilisateurs

## Shortcodes Disponibles

- `[isma_student_dashboard]` - Dashboard étudiant
- `[isma_trainer_dashboard]` - Dashboard formateur
- `[isma_course_list]` - Liste des cours
- `[isma_course_viewer]` - Visualiseur de cours
- `[isma_exercise_list]` - Liste des exercices
- `[isma_exercise_submission]` - Formulaire de dépôt
- `[isma_student_payments]` - Historique paiements étudiant
- `[isma_trainer_payments]` - Paiements formateur
- `[isma_alumni_forum]` - Forum alumni
- `[isma_job_offers]` - Offres d'emploi
- `[isma_notifications]` - Notifications
- `[isma_trainer_proposals]` - Propositions formateur

## Rôles Utilisateurs

- **Super Admin ISMA**: Accès complet
- **Modérateur**: Charte, publication, suivi (3 comptes)
- **Formateur**: Création cours/exercices, propositions
- **Étudiant**: Consultation, dépôt exercices, paiement
- **Alumni**: Forum, offres d'emploi

## Structure des Bases de Données

Tables créées:
- `wp_isma_payments` - Historique paiements
- `wp_isma_exercise_submissions` - Soumissions exercices
- `wp_isma_course_charters` - Charte cours
- `wp_isma_exercise_charters` - Charte exercices
- `wp_isma_proposals` - Propositions formateurs
- `wp_isma_forum_posts` - Posts forum alumni
- `wp_isma_notifications` - Notifications in-app

## Processus de Publication

1. Formateur crée cours/exercice → statut "En attente"
2. Notification envoyée à l'administration
3. Admin révise et charte le contenu
4. Admin publie → notification aux étudiants et formateur

## Licence
GPL v2 or later
