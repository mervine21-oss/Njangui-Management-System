# 📨 Auto-Generated Invitation Codes Feature

## 🎯 Overview

Quand un admin crée un groupe, **un code d'invitation est automatiquement généré**. Ce code peut être partagé avec d'autres utilisateurs pour qu'ils rejoignent le groupe.

---

## 🔄 Workflow Complet

### 1️⃣ Admin Crée Un Groupe

**Étapes:**
```
Dashboard → "+ Create Group" → Remplir le formulaire → "Create"
```

**Données requises:**
- Group Name (ex: "Chama Alpha")
- Capacity (ex: 12)
- Contribution Amount (ex: 250000 XAF)
- Cycle Interval (weekly/monthly/bimonthly/annually)
- Collateral Reserve % (ex: 5%)

### 2️⃣ Code Généré Automatiquement

**Dans le backend (`create_group.php`):**
```php
// Après insertion du groupe en DB
$token = bin2hex(random_bytes(16)); // Génère: a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6 (32 chars)
$inv = $pdo->prepare('INSERT INTO invites (group_id, token, expires_at, created_by) VALUES (?,?,NULL,?)');
$inv->execute([$groupId, $token, $userId]);
```

**Propriétés du code:**
- 32 caractères hex (aléatoires et sécurisés)
- Jamais expiré (expires_at = NULL)
- Lié au groupe créé
- Créé par l'admin

### 3️⃣ Message de Succès avec Code

**Admin voit:**
```
✅ Group created successfully! 
Invitation code: a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6
```

### 4️⃣ Code Affiché sur la Page du Groupe

**Page:** `http://localhost/DigiTon/public/group-details.php?id=1`

**Pour l'admin uniquement:**
```
┌─────────────────────────────────────────────┐
│ 📨 Invitation Code                          │
├─────────────────────────────────────────────┤
│ Share this code with others to invite them: │
│                                             │
│ [a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6] [Copy] │
│                                             │
│ ✅ Copied! (feedback après clic)            │
└─────────────────────────────────────────────┘
```

### 5️⃣ Admin Partage le Code

**Canaux de partage:**
- Via email (utiliser api/send_invite.php)
- Via WhatsApp/message
- Via lien copié (clipboard)
- Manuellement

### 6️⃣ Utilisateur Rejoint Avec le Code

**Étapes de l'utilisateur:**
```
Dashboard → "+ Join Group" → Saisir le code → "Join Group"
```

**API appelée:** `api/join_by_invite.php`

**Validations:**
- ✅ Code existe dans la DB
- ✅ Code n'est pas expiré
- ✅ Groupe existe
- ✅ Utilisateur pas déjà membre
- ✅ Groupe pas plein (member_count < capacity)

**Résultat:**
```json
{
  "ok": true,
  "message": "Successfully joined the group!",
  "seats_remaining": 8
}
```

**Page reloade → Groupe visible dans le dashboard**

---

## 📊 Base de Données

### Table `invites`
```sql
CREATE TABLE invites (
  id INT PRIMARY KEY AUTO_INCREMENT,
  group_id INT NOT NULL,
  token VARCHAR(255) UNIQUE NOT NULL,
  expires_at DATETIME NULL,
  created_by INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (group_id) REFERENCES tontine_groups(id),
  FOREIGN KEY (created_by) REFERENCES users(id)
);
```

### Exemple d'enregistrement
```sql
INSERT INTO invites (group_id, token, expires_at, created_by)
VALUES (1, 'a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6', NULL, 1);
-- group_id = 1 (Chama Alpha)
-- token = code de 32 caractères
-- expires_at = NULL (jamais expiré)
-- created_by = 1 (admin user_id)
```

---

## 🔐 Sécurité

| Aspect | Détail |
|--------|--------|
| **Token Generation** | `bin2hex(random_bytes(16))` - Cryptographiquement sécurisé |
| **Token Storage** | VARCHAR(255) UNIQUE dans la DB |
| **SQL Injection** | PDO prepared statements (aucun risque) |
| **XSS Protection** | htmlspecialchars() sur l'affichage du code |
| **Permission Check** | Vérifier que user est admin du groupe |
| **Token Validation** | SELECT FROM invites WHERE token AND group_id |
| **Expiry Check** | Optionnel (actuellement NULL = jamais expire) |

---

## 🎨 Interface Utilisateur

### Créer Groupe - Message de Succès
```
Dashboard → Create Group → Soumettre formulaire
        ↓
Page group-details avec message flash:
✅ Group created successfully! 
Invitation code: a1b2c3d4...
```

### Affichage du Code - Admin Only
```
Group Details Page → Section "Invitation Code" (pour admins)

📨 Invitation Code
Share this code with others to invite them to join:
[a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6] [Copy]
```

**Bouton Copy:**
- Copie le code au clipboard
- Affiche "✅ Copied!" en vert pendant 2 secondes
- Utilise document.execCommand('copy')

### Rejoindre Groupe - Modal Dialog
```
Dashboard → "+ Join Group" → Modal
[Invitation Code: _________________]
[Join Group Button]

Résultat:
❌ Invalid invitation code (si code incorrect)
❌ This group is full (si groupe plein)
✅ Successfully joined! (si succès + reload page)
```

---

## 📝 Workflow Récapitulatif

```
┌─────────────────────────────────────────────────────────────┐
│ ADMIN CÔTÉ                          │ UTILISATEUR CÔTÉ      │
├─────────────────────────────────────────────────────────────┤
│ 1. Crée un groupe                   │                       │
│    - Form de création               │                       │
│    - Soumet                         │                       │
│                                     │                       │
│ 2. Code généré AUTO                 │                       │
│    - bin2hex(random_bytes(16))      │                       │
│    - Inséré dans DB                 │                       │
│                                     │                       │
│ 3. Reçoit message de succès         │                       │
│    - Code affiché                   │                       │
│                                     │                       │
│ 4. Copie le code via bouton         │                       │
│    - clipboard ready                │                       │
│                                     │                       │
│ 5. Partage le code                  │                       │
│    - Email, WhatsApp, SMS, etc.     │ 👈 Reçoit le code    │
│                                     │                       │
│                                     │ 6. Va au dashboard   │
│                                     │    Clique "+ Join"   │
│                                     │                       │
│                                     │ 7. Saisit le code    │
│                                     │    Clique "Join"     │
│                                     │                       │
│                                     │ 8. Validé en DB      │
│                                     │    - check token     │
│                                     │    - check capacity  │
│                                     │    - add member      │
│                                     │                       │
│                                     │ 9. ✅ Rejoint!       │
│                                     │    Page reload       │
│                                     │    Voir le groupe    │
└─────────────────────────────────────────────────────────────┘
```

---

## 🧪 Test Scenarios

### Scenario 1: Création et Copie du Code
```bash
1. Login as alice@
2. Dashboard → "+ Create Group"
3. Fill form:
   - Name: "Test Group"
   - Capacity: 10
   - Contribution: 100000
   - Cycle: monthly
   - Collateral: 5%
4. Click "Create"
5. ✅ Message shows: "Invitation code: abc123..."
6. Click "Copy" button
7. ✅ Button shows: "✅ Copied!" (green)
8. Paste anywhere (Ctrl+V) → code aparece
```

### Scenario 2: Rejoindre avec Code Valide
```bash
1. Login as jean@
2. Dashboard → "+ Join Group"
3. Modal opens
4. Paste code from alice
5. Click "Join Group"
6. ✅ "Successfully joined the group!"
7. Page reloads
8. "Test Group" appears in jean@'s groups
```

### Scenario 3: Code Invalide
```bash
1. Login as marie@
2. Dashboard → "+ Join Group"
3. Enter: "invalidcode123"
4. Click "Join Group"
5. ❌ "Invalid invitation code"
6. Modal stays open
```

### Scenario 4: Groupe Plein
```bash
1. Groupe "Test" a capacity=2
2. Déjà 2 membres (alice, jean)
3. marie@ essaie de joindre
4. ❌ "This group is full"
```

---

## 🚀 Endpoints API

### POST `/api/join_by_invite.php`
```json
Request:
{
  "invite_code": "a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6"
}

Response (Success - 200):
{
  "ok": true,
  "message": "Successfully joined the group!",
  "seats_remaining": 8
}

Response (Error - 404):
{
  "ok": false,
  "message": "Invalid invitation code"
}

Response (Error - 409):
{
  "ok": false,
  "message": "This group is full"
}
```

### POST `/api/send_invite.php` (Optional - For Emailing)
```json
Request:
{
  "group_id": 1,
  "send_email": "recipient@example.com",
  "expires_days": 7
}

Response (Success):
{
  "ok": true,
  "message": "Invitation code generated and sent",
  "code": "a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6",
  "link": "http://localhost/DigiTon/public/join.php?token=...",
  "expires": "10/07/2026"
}
```

---

## 📁 Files Modified

| File | Changes |
|------|---------|
| `public/create_group.php` | Generate token after group insert; display in flash message |
| `public/group-details.php` | Query invite code; display for admins only; "Copy" button |
| `includes/navbar.php` | Display flash messages on all pages |
| `public/api/join_by_invite.php` | Validate code, join group (existing) |
| `public/api/send_invite.php` | Generate & email code (existing) |

---

## ✅ Checklist - Fonctionnalités

- ✅ Code généré AUTO lors de création de groupe
- ✅ Code affiché au moment de la création (message flash)
- ✅ Code affiché sur la page du groupe (pour l'admin)
- ✅ Bouton "Copy" pour copier le code
- ✅ Modal pour rejoindre avec un code
- ✅ Validation du code (token check, expiry, capacity)
- ✅ Membre ajouté au groupe après validation
- ✅ Wallet créé automatiquement pour le nouveau membre
- ✅ Message de succès/erreur affiché à l'utilisateur
- ✅ PDO prepared statements (sécurité SQL)
- ✅ Permissions vérifiées (admin only pour afficher code)

---

## 🔄 Prochaines Améliorations

- [ ] QR code pour le code d'invitation
- [ ] Lien court (bit.ly style) pour partager
- [ ] Email avec lien d'invitation automatique
- [ ] Admin peut voir qui a utilisé le code
- [ ] Révoquer un code (set expires_at = now)
- [ ] Générer plusieurs codes avec expiration différente
- [ ] Analytics: Combien de personnes ont joint via chaque code
- [ ] Bulk invite (CSV upload)

---

## 🆘 Troubleshooting

| Problème | Solution |
|----------|----------|
| Code n'apparaît pas | Vérifier que user est admin |
| Copy button ne marche pas | Navigateur outdated (use latest Chrome/Firefox) |
| "Invalid invitation code" | Vérifier le code saisi (case-sensitive) |
| Groupe plein | Augmenter capacity dans group settings |
| Token n'est pas généré | Vérifier invites table existe en DB |

---

**Générée le:** 2026-07-03  
**Statut:** ✅ Fully Functional  
**Prêt pour production:** Oui
