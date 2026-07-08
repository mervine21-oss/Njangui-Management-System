# 📖 Guide: Join Group & Send Invites Feature

## 🎯 Nouvelles Fonctionnalités sur le Dashboard

### 1. **Bouton "+ Join Group"**
- Situé à côté du bouton "+ Create Group" dans le header du dashboard
- Clique → Une modale s'ouvre demandant un **code d'invitation**

### 2. **Modale "Join a Group"**
- **Champ:** Saisir le code d'invitation (32 caractères)
- **Message:** "Ask your group admin to send you an invitation code"
- **Bouton:** "Join Group" → Valide le code et ajoute l'utilisateur au groupe
- **Comportement:**
  - ✅ Si le code est valide → Rejoint le groupe automatiquement
  - ❌ Si le code est invalide/expiré → Message d'erreur
  - ❌ Si l'utilisateur est déjà membre → Message d'erreur
  - ❌ Si le groupe est plein → Message d'erreur

### 3. **Lien "Send invite code to others" (Pour les admins)**
- Visible seulement si l'utilisateur est admin d'au moins un groupe
- Clique → Ouvre la modale "Send Invitation Code"

### 4. **Modale "Send Invitation Code" (Admin only)**
- **Dropdown:** Sélectionner un groupe (parmi ceux où vous êtes admin)
- **Email:** Adresse email du destinataire (optionnel)
- **Expiration:** Nombre de jours avant expiration du code (0 = jamais)
- **Bouton:** "Generate & Send Code"
- **Résultat:** 
  - Code généré (32 caractères aléatoires)
  - Lien d'invitation optionnel
  - Email envoyé (si email fourni)

---

## 🧪 Workflow de Test

### Scénario 1: Admin envoie un code à un utilisateur

```
1. Connectez-vous comme ALICE (admin du groupe "Chama Alpha")
2. Dashboard → Bouton "+ Join Group"
3. Modale s'ouvre → Lien "Send invite code to others"
4. Modale "Send Invitation Code" ouvre
5. Sélectionnez "Chama Alpha"
6. Entrez email de JEAN (jean@example.com) - optionnel
7. Expiration: 7 jours
8. Cliquez "Generate & Send Code"
9. → Code généré et affiché
   → Lien d'invitation affiché
   → Email envoyé à jean@example.com (si SMTP configuré)
```

### Scénario 2: Utilisateur rejoint avec le code

```
1. Connectez-vous comme JEAN
2. Dashboard → Bouton "+ Join Group"
3. Modale "Join a Group" ouvre
4. Collez le code reçu (ex: a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6)
5. Cliquez "Join Group"
6. → ✅ "Successfully joined the group!"
7. Page se reload → "Chama Alpha" apparaît dans les groupes de JEAN
```

### Scénario 3: Code invalide/expiré

```
1. Connectez-vous comme MARIE
2. Dashboard → Bouton "+ Join Group"
3. Saisissez un code invalide
4. → ❌ "Invalid invitation code"
5. Saisissez un code expiré
6. → ❌ "Invitation code has expired"
```

---

## 📊 Données Stockées en DB

### Table `invites`
```sql
- token: code d'invitation unique (32 hex chars)
- group_id: ID du groupe
- expires_at: Date d'expiration (NULL = jamais expire)
- created_by: ID de l'admin qui a créé le code
- created_at: Timestamp de création
```

### Process de Jonction
1. Code validé (exists, not expired, group exists)
2. Vérifier utilisateur pas déjà membre
3. Vérifier groupe pas plein (member_count < capacity)
4. Transaction:
   - INSERT `group_members` (is_admin=0, is_new_member=1, status=active)
   - INSERT `wallets` (rotational_balance=0, savings_balance=0)
5. Retour JSON avec succès et seats_remaining

---

## 🔒 Sécurité

- ✅ PDO prepared statements (no SQL injection)
- ✅ Session validation (user must be logged in)
- ✅ Admin verification (only admins can send codes)
- ✅ XSS protection (htmlspecialchars on outputs)
- ✅ CSRF: POST method + session-based auth
- ✅ Token validation on join

---

## 📱 Responsive

- Desktop: Full 2-column modal layout
- Tablet: Adjusted spacing
- Mobile: Single-column, full-width modals

---

## 🚀 Endpoints API

### POST `/api/join_by_invite.php`
```json
Request:
{
  "invite_code": "a1b2c3d4..."
}

Response (Success):
{
  "ok": true,
  "message": "Successfully joined the group!",
  "seats_remaining": 8
}

Response (Error):
{
  "ok": false,
  "message": "Invitation code has expired"
}
```

### POST `/api/send_invite.php`
```
Request (form):
- group_id: 1
- send_email: recipient@example.com (optional)
- expires_days: 7

Response (Success):
{
  "ok": true,
  "message": "Invitation code generated and sent to recipient@example.com",
  "code": "a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6",
  "link": "http://localhost/DigiTon/public/join.php?token=...",
  "expires": "10/07/2026"
}
```

---

## ⚙️ Configuration

**Email Sending:**
- Situé dans `config/mailer.php`
- Utilise `send_invite_email($to, $subject, $body)`
- Sur WAMP Windows: Configurer SMTP dans php.ini
- Ou remplacer par PHPMailer

---

## ✨ Prochaines Améliorations

- [ ] Résend invite code (admin peut renvoyer)
- [ ] Invite expiry notification
- [ ] Bulk invite (upload CSV)
- [ ] QR code pour partager invite
- [ ] Invite acceptance with email confirmation
- [ ] Admin dashboard pour voir les invites envoyés
