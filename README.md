# QCM — Système d'accès basé sur les rôles

## Vue d'ensemble

Le système QCM a été amélioré avec un contrôle d'accès basé sur les rôles. Les réponses et la vérification des réponses sont maintenant accessibles **uniquement aux professeurs et administrateurs**.

## Rôles et permissions

### 👤 Élève (Student)
- ✅ Peut voir les questions
- ✅ Peut répondre aux questions
- ✅ Peut soumettre le QCM
- ❌ **Ne peut PAS voir les réponses correctes**
- ❌ **Ne peut PAS revoir ses réponses après soumission**
- ❌ **Perd l'accès après validation du QCM**
- ❌ **Impossible de revenir en arrière ou recommencer**

### 👨‍🏫 Professeur (Teacher)
- ✅ Peut voir les questions
- ✅ Peut répondre aux questions
- ✅ Peut soumettre le QCM
- ✅ **Peut voir TOUTES les réponses correctes**
- ✅ **Peut voir la comparaison détaillée des réponses**
- ✅ **Peut recommencer le QCM**

### 🔒 Administrateur (Admin)
- ✅ Les mêmes droits que les professeurs
- ✅ Accès complet à toutes les fonctionnalités

## Identifiants de test

### Élève
- **Utilisateur**: `eleve1`
- **Mot de passe**: `pass123`
- **Rôle**: Élève

### Professeur
- **Utilisateur**: `prof1`
- **Mot de passe**: `prof123`
- **Rôle**: Professeur

### Administrateur
- **Utilisateur**: `admin1`
- **Mot de passe**: `admin123`
- **Rôle**: Administrateur

## Fonctionnalités implémentées

### 1. **Écran de connexion**
- Les utilisateurs doivent se connecter avant d'accéder au QCM
- Les rôles sont vérifiés lors de l'authentification

### 2. **Affichage du profil**
- Le nom d'utilisateur et le rôle s'affichent en haut à droite
- Bouton "Déconnexion" pour quitter

### 3. **Restriction d'accès pour les élèves**
- Après soumission du QCM, les élèves voient uniquement leur score global
- Les détails des réponses correctes ne sont **pas affichés**
- Les élèves reçoivent un message les invitant à contacter leur professeur

### 4. **Accès complet pour les professeurs/administrateurs**
- Vue détaillée de toutes les réponses
- Comparaison entre les réponses données et les bonnes réponses
- Bouton "Recommencer" pour revérifier le QCM

### 5. **Blocage de la navigation post-soumission**
- Les élèves ne peuvent pas naviguer entre les questions après soumission
- Les boutons "Précédent" et "Suivant" deviennent inactifs pour les élèves

## Architecture

### Frontend
- `index.html`: Écran de connexion + Interface QCM
- `style.css`: Styles incluant le formulaire de connexion et les restrctions d'accès
- `script.js`: Logique d'authentification et contrôle d'accès

### Base de données (actuelle)
Actuellement, les utilisateurs sont stockés dans une variable JavaScript pour le prototypage.

**À faire pour la production**:
- Implémenter une véritable authentification serveur (OAuth, JWT, etc.)
- Stocker les réponses dans une base de données
- Utiliser une connexion sécurisée (HTTPS)
- Hacher les mots de passe avec bcrypt ou similaire

## Fichiers modifiés

1. **index.html**
   - Ajout d'un écran de connexion
   - Ajout du formulaire d'authentification

2. **style.css**
   - Styles pour l'écran de connexion
   - Styles pour les messages d'accès restreint
   - Styles pour les informations utilisateur

3. **script.js**
   - Système complet d'authentification
   - Gestion des sessions
   - Contrôle d'accès basé sur les rôles
   - Différenciation du contenu affiché selon le rôle

## Comment utiliser

1. Ouvrir `index.html` dans un navigateur
2. Se connecter avec l'un des identifiants de test
3. Répondre au QCM
4. Cliquer sur "Vérifier" pour soumettre
5. Voir les résultats appropriés selon le rôle

## Personnalisation

Pour ajouter de nouvelles questions, modifiez le tableau `questions` dans `script.js`:

```javascript
{
    id: 4,
    type: 'single', // ou 'multiple'
    text: 'Votre question ?',
    options: ['Option 1', 'Option 2', 'Option 3'],
    answer: [0] // Index de la bonne réponse
}
```

Pour ajouter des utilisateurs, modifiez l'objet `users` dans `script.js`:

```javascript
'nouveau_user': { 
    password: 'motdepasse', 
    role: 'student', // ou 'teacher', 'admin'
    name: 'Nom Complet' 
}
```

## Notes de sécurité

⚠️ **IMPORTANT**: Cette implémentation est pour le développement/prototypage uniquement.

Pour une utilisation en production:
- Ne pas stocker les mots de passe en clair dans le code
- Implémenter une authentification serveur sécurisée
- Utiliser HTTPS
- Valider et sécuriser tous les données côté serveur
- Implémenter une gestion des sessions robuste
- Ajouter une protection CSRF
- Utiliser une base de données sécurisée

