<?php
// =============================================
//  Configuration base de données
//  (valeurs tirées du playbook Ansible ASRC)
// =============================================
$db_host = "localhost";
$db_user = "asrc_user";
$db_pass = "Emmanuel@51";
$db_name = "asrc_db";

$message      = "";
$message_type = "";
$student_name = "";

function getDB($host, $user, $pass, $name) {
    $conn = new mysqli($host, $user, $pass, $name);
    if ($conn->connect_error) return null;
    $conn->set_charset("utf8mb4");
    return $conn;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nom      = trim($_POST["nom"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($nom === "" || $password === "") {
        $message_type = "error";
        $message      = "Tous les champs sont obligatoires.";
    } elseif (strlen($nom) < 3) {
        $message_type = "error";
        $message      = "Le nom doit comporter au moins 3 caractères.";
    } elseif (strlen($password) < 6) {
        $message_type = "error";
        $message      = "Le mot de passe doit comporter au moins 6 caractères.";
    } else {
        $conn = getDB($db_host, $db_user, $db_pass, $db_name);
        if (!$conn) {
            $message_type = "error";
            $message      = "Connexion à la base de données impossible.";
        } else {
            $stmt = $conn->prepare("SELECT id FROM etudiants WHERE nom = ?");
            $stmt->bind_param("s", $nom);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $message_type = "error";
                $message      = "Ce nom est déjà enregistré. Choisissez un autre nom.";
            } else {
                $hashed = password_hash($password, PASSWORD_BCRYPT);
                $ins    = $conn->prepare("INSERT INTO etudiants (nom, mot_de_passe) VALUES (?, ?)");
                $ins->bind_param("ss", $nom, $hashed);
                if ($ins->execute()) {
                    $message_type = "success";
                    $student_name = htmlspecialchars($nom);
                    $message      = "Bienvenue, <strong>$student_name</strong> ! Votre compte a été créé avec succès.";
                } else {
                    $message_type = "error";
                    $message      = "Erreur lors de l'enregistrement : " . htmlspecialchars($conn->error);
                }
                $ins->close();
            }
            $stmt->close();
            $conn->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>ASRC · IUC — Enregistrement</title>
<link rel="preconnect" href="https://fonts.googleapis.com"/>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet"/>
<style>
/* ===== RESET & VARS ===== */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root {
  --green:      #0d5c3a;
  --green-mid:  #127a4e;
  --green-light:#1aad6e;
  --cream:      #f7f3ec;
  --cream-dark: #ede8df;
  --gold:       #c9a84c;
  --gold-light: #e8c96a;
  --text-dark:  #1a1a18;
  --text-mid:   #4a5568;
  --text-light: #8a9ab0;
  --white:      #ffffff;
  --error-bg:   #fff1f2;
  --error-bdr:  #fca5a5;
  --error-txt:  #b91c1c;
  --ok-bg:      #f0fdf7;
  --ok-bdr:     #6ee7b7;
  --ok-txt:     #065f46;
  --ff-display: 'Syne', sans-serif;
  --ff-body:    'DM Sans', sans-serif;
}

html, body {
  height: 100%;
  font-family: var(--ff-body);
  background: var(--cream);
  color: var(--text-dark);
}

/* ===== LAYOUT SPLIT ===== */
.split {
  display: grid;
  grid-template-columns: 1fr 1fr;
  min-height: 100vh;
}

/* ===== LEFT PANEL ===== */
.panel-left {
  background: var(--green);
  position: relative;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  padding: 52px 48px;
  overflow: hidden;
}

/* Hex grid background */
.panel-left::before {
  content: '';
  position: absolute; inset: 0;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='56' height='100'%3E%3Cpath d='M28 66L0 50V18L28 2l28 16v32z' fill='none' stroke='rgba(255,255,255,0.05)' stroke-width='1'/%3E%3Cpath d='M28 100L0 84V52l28-16 28 16v32z' fill='none' stroke='rgba(255,255,255,0.05)' stroke-width='1'/%3E%3C/svg%3E");
  background-size: 56px 100px;
  animation: hexDrift 30s linear infinite;
}
@keyframes hexDrift {
  from { background-position: 0 0; }
  to   { background-position: 56px 200px; }
}

/* Radial glow */
.panel-left::after {
  content: '';
  position: absolute;
  width: 600px; height: 600px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(26,173,110,0.22) 0%, transparent 65%);
  top: 50%; left: 50%;
  transform: translate(-50%, -50%);
  animation: glowPulse 5s ease-in-out infinite alternate;
}
@keyframes glowPulse {
  from { opacity: 0.6; transform: translate(-50%,-50%) scale(0.9); }
  to   { opacity: 1;   transform: translate(-50%,-50%) scale(1.1); }
}

.left-top { position: relative; z-index: 2; }
.left-top .iuc-tag {
  display: inline-flex; align-items: center; gap: 8px;
  background: rgba(255,255,255,0.08);
  border: 1px solid rgba(255,255,255,0.15);
  border-radius: 30px;
  padding: 6px 16px;
  font-family: var(--ff-display);
  font-size: .68rem; letter-spacing: .18em;
  color: rgba(255,255,255,0.75);
  text-transform: uppercase;
  animation: fadeDown .7s .1s both;
}
.iuc-tag-dot {
  width: 6px; height: 6px; border-radius: 50%;
  background: var(--gold-light);
  animation: blink 2s ease-in-out infinite;
}
@keyframes blink { 0%,100%{opacity:1;} 50%{opacity:0.2;} }

/* Center hero area */
.left-center {
  position: relative; z-index: 2;
  text-align: center;
  animation: fadeUp .9s .25s both;
}

/* The mascot / logo image */
.mascot-wrap {
  position: relative;
  display: inline-block;
  margin-bottom: 32px;
}
.mascot-ring {
  position: absolute; inset: -18px;
  border-radius: 50%;
  border: 1px dashed rgba(201,168,76,0.35);
  animation: spinSlow 20s linear infinite;
}
.mascot-ring-2 {
  position: absolute; inset: -36px;
  border-radius: 50%;
  border: 1px solid rgba(255,255,255,0.07);
  animation: spinSlow 35s linear infinite reverse;
}
@keyframes spinSlow { to { transform: rotate(360deg); } }

.mascot-bg {
  width: 130px; height: 130px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(26,173,110,0.35), rgba(13,92,58,0.0));
  display: flex; align-items: center; justify-content: center;
  position: relative;
  box-shadow: 0 0 60px rgba(26,173,110,0.3);
}

/* Inline SVG mascot — engrenages tech */
.mascot-svg { width: 88px; height: 88px; }

.left-headline {
  font-family: var(--ff-display);
  font-size: 2.9rem; font-weight: 800; line-height: 1.05;
  color: var(--white);
  letter-spacing: -.01em;
}
.left-headline em {
  font-style: normal;
  color: var(--gold-light);
}
.left-sub {
  margin-top: 14px;
  font-size: .95rem; font-weight: 300; line-height: 1.6;
  color: rgba(255,255,255,0.55);
}
.left-sub strong { color: rgba(255,255,255,0.85); font-weight: 500; }

/* Stats strip */
.stats-strip {
  position: relative; z-index: 2;
  display: flex; gap: 0;
  border-top: 1px solid rgba(255,255,255,0.08);
  padding-top: 28px;
  animation: fadeUp .8s .5s both;
}
.stat {
  flex: 1; text-align: center;
  border-right: 1px solid rgba(255,255,255,0.08);
}
.stat:last-child { border-right: none; }
.stat-num {
  font-family: var(--ff-display);
  font-size: 1.6rem; font-weight: 800;
  color: var(--gold-light);
  display: block;
}
.stat-lbl {
  font-size: .68rem; letter-spacing: .12em; text-transform: uppercase;
  color: rgba(255,255,255,0.4);
}

/* ===== RIGHT PANEL ===== */
.panel-right {
  background: var(--cream);
  display: flex; align-items: center; justify-content: center;
  padding: 48px 52px;
  position: relative;
}

/* Subtle grain */
.panel-right::before {
  content: '';
  position: absolute; inset: 0; pointer-events: none;
  opacity: 0.025;
  background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)' opacity='1'/%3E%3C/svg%3E");
  background-size: 200px 200px;
}

.form-panel {
  width: 100%; max-width: 400px;
  position: relative; z-index: 1;
}

/* Form header */
.form-eyebrow {
  font-family: var(--ff-display);
  font-size: .68rem; letter-spacing: .22em; text-transform: uppercase;
  color: var(--green-light);
  margin-bottom: 10px;
  display: flex; align-items: center; gap: 10px;
  animation: fadeDown .7s .2s both;
}
.form-eyebrow::after {
  content: ''; flex: 1; height: 1px;
  background: linear-gradient(90deg, var(--green-light), transparent);
  opacity: .4;
}

.form-title {
  font-family: var(--ff-display);
  font-size: 2.2rem; font-weight: 800; line-height: 1.1;
  color: var(--text-dark);
  margin-bottom: 8px;
  animation: fadeDown .7s .3s both;
}
.form-title span { color: var(--green); }

.form-desc {
  font-size: .9rem; color: var(--text-mid); line-height: 1.55;
  margin-bottom: 32px;
  animation: fadeDown .7s .4s both;
}

/* Message */
.msg {
  border-radius: 10px;
  padding: 14px 16px;
  margin-bottom: 24px;
  font-size: .9rem; line-height: 1.5;
  display: flex; gap: 10px; align-items: flex-start;
  animation: slideIn .45s cubic-bezier(.22,1,.36,1) both;
}
@keyframes slideIn {
  from { opacity:0; transform: translateY(-12px); }
  to   { opacity:1; transform: translateY(0); }
}
.msg.success {
  background: var(--ok-bg);
  border: 1px solid var(--ok-bdr);
  color: var(--ok-txt);
}
.msg.error {
  background: var(--error-bg);
  border: 1px solid var(--error-bdr);
  color: var(--error-txt);
}
.msg-icon { font-size: 1.1rem; flex-shrink: 0; margin-top: 1px; }

/* Fields */
.field { margin-bottom: 20px; animation: fadeUp .6s both; }
.field:nth-child(1) { animation-delay: .45s; }
.field:nth-child(2) { animation-delay: .55s; }

.field label {
  display: block;
  font-size: .72rem; font-weight: 500; letter-spacing: .1em; text-transform: uppercase;
  color: var(--text-mid);
  margin-bottom: 7px;
}

.input-row {
  position: relative;
  display: flex; align-items: center;
}

.field-icon {
  position: absolute; left: 14px;
  color: var(--text-light);
  font-size: .95rem; pointer-events: none;
  transition: color .25s;
}

.field input {
  width: 100%;
  background: var(--white);
  border: 1.5px solid var(--cream-dark);
  border-radius: 10px;
  padding: 13px 14px 13px 42px;
  font-family: var(--ff-body);
  font-size: .95rem; color: var(--text-dark);
  outline: none;
  transition: border-color .25s, box-shadow .25s;
}
.field input::placeholder { color: #c4c9d4; }
.field input:focus {
  border-color: var(--green-mid);
  box-shadow: 0 0 0 3px rgba(18,122,78,0.12);
}
.field input:focus ~ .field-icon,
.input-row:focus-within .field-icon { color: var(--green-mid); }

.toggle-btn {
  position: absolute; right: 12px;
  background: none; border: none; cursor: pointer;
  color: var(--text-light); font-size: .9rem;
  transition: color .25s;
}
.toggle-btn:hover { color: var(--green-mid); }

/* Password strength bar */
.strength-bar {
  height: 3px; border-radius: 2px;
  background: var(--cream-dark);
  margin-top: 6px; overflow: hidden;
}
.strength-fill {
  height: 100%; width: 0; border-radius: 2px;
  transition: width .4s, background .4s;
}

/* Submit */
.btn-wrap {
  margin-top: 8px;
  animation: fadeUp .6s .65s both;
}
.btn-submit {
  width: 100%;
  background: var(--green);
  color: var(--white);
  border: none; border-radius: 10px;
  padding: 15px 24px;
  font-family: var(--ff-display);
  font-size: .95rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase;
  cursor: pointer;
  position: relative; overflow: hidden;
  transition: background .25s, transform .2s, box-shadow .25s;
  box-shadow: 0 4px 20px rgba(13,92,58,0.25);
}
.btn-submit:hover {
  background: var(--green-mid);
  transform: translateY(-2px);
  box-shadow: 0 8px 30px rgba(13,92,58,0.35);
}
.btn-submit:active { transform: translateY(0); }

/* Gold shimmer on hover */
.btn-submit::after {
  content: '';
  position: absolute; top: 0; left: -80%; width: 50%; height: 100%;
  background: linear-gradient(90deg, transparent, rgba(232,201,106,0.25), transparent);
  transition: left .5s;
}
.btn-submit:hover::after { left: 130%; }

.btn-submit.loading {
  pointer-events: none; color: transparent;
}
.btn-submit.loading::before {
  content: '';
  position: absolute; top: 50%; left: 50%;
  width: 20px; height: 20px; margin: -10px;
  border: 2.5px solid rgba(255,255,255,0.3);
  border-top-color: #fff;
  border-radius: 50%;
  animation: spin .7s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* Footer info */
.form-footer {
  margin-top: 28px; text-align: center;
  font-size: .75rem; color: var(--text-light);
  animation: fadeUp .6s .8s both;
}
.form-footer a { color: var(--green-mid); text-decoration: none; }
.form-footer strong { color: var(--gold); }

/* Divider */
.form-divider {
  display: flex; align-items: center; gap: 14px;
  margin: 28px 0;
  font-size: .7rem; letter-spacing: .12em; text-transform: uppercase;
  color: var(--text-light);
  animation: fadeUp .6s .7s both;
}
.form-divider::before, .form-divider::after {
  content:''; flex:1; height:1px; background: var(--cream-dark);
}

/* ===== KEYFRAMES ===== */
@keyframes fadeDown {
  from { opacity:0; transform: translateY(-16px); }
  to   { opacity:1; transform: translateY(0); }
}
@keyframes fadeUp {
  from { opacity:0; transform: translateY(16px); }
  to   { opacity:1; transform: translateY(0); }
}

/* ===== RESPONSIVE ===== */
@media (max-width: 900px) {
  .split { grid-template-columns: 1fr; }
  .panel-left { padding: 40px 32px 36px; }
  .left-headline { font-size: 2rem; }
  .left-center { margin: 20px 0; }
  .stats-strip { gap: 0; }
  .panel-right { padding: 40px 28px; }
}

/* ===== CONFETTI ===== */
#confetti-box { position:fixed;inset:0;pointer-events:none;z-index:999;overflow:hidden; }
.cp {
  position:absolute; top:-12px;
  border-radius: 3px;
  animation: fall linear forwards;
}
@keyframes fall {
  0%   { transform: translateY(0) rotate(0deg); opacity: 1; }
  100% { transform: translateY(105vh) rotate(600deg); opacity: 0; }
}
</style>
</head>
<body>

<div id="confetti-box"></div>

<div class="split">

  <!-- ===== LEFT PANEL ===== -->
  <div class="panel-left">

    <div class="left-top">
      <span class="iuc-tag"><span class="iuc-tag-dot"></span>Institut Universitaire de la Côte · IUC</span>
    </div>

    <div class="left-center">
      <!-- MASCOT / LOGO (bonhomme engrenages conservé) -->
      <div class="mascot-wrap">
        <div class="mascot-ring"></div>
        <div class="mascot-ring-2"></div>
        <div class="mascot-bg">
          <!-- SVG inline reproduisant le style bonhomme + engrenages de l'original -->
          <svg class="mascot-svg" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
            <!-- Gears background -->
            <circle cx="72" cy="22" r="14" fill="none" stroke="rgba(255,255,255,0.18)" stroke-width="2"/>
            <circle cx="72" cy="22" r="8"  fill="rgba(255,255,255,0.12)"/>
            <circle cx="72" cy="22" r="3"  fill="rgba(255,255,255,0.5)"/>
            <!-- Gear teeth -->
            <rect x="70" y="5"  width="4" height="6" rx="1" fill="rgba(255,255,255,0.25)" transform="rotate(0  72 22)"/>
            <rect x="70" y="5"  width="4" height="6" rx="1" fill="rgba(255,255,255,0.25)" transform="rotate(45 72 22)"/>
            <rect x="70" y="5"  width="4" height="6" rx="1" fill="rgba(255,255,255,0.25)" transform="rotate(90 72 22)"/>
            <rect x="70" y="5"  width="4" height="6" rx="1" fill="rgba(255,255,255,0.25)" transform="rotate(135 72 22)"/>
            <rect x="70" y="5"  width="4" height="6" rx="1" fill="rgba(255,255,255,0.25)" transform="rotate(180 72 22)"/>
            <rect x="70" y="5"  width="4" height="6" rx="1" fill="rgba(255,255,255,0.25)" transform="rotate(225 72 22)"/>
            <rect x="70" y="5"  width="4" height="6" rx="1" fill="rgba(255,255,255,0.25)" transform="rotate(270 72 22)"/>
            <rect x="70" y="5"  width="4" height="6" rx="1" fill="rgba(255,255,255,0.25)" transform="rotate(315 72 22)"/>
            <!-- Small gear -->
            <circle cx="55" cy="32" r="8"  fill="none" stroke="rgba(201,168,76,0.4)" stroke-width="1.5"/>
            <circle cx="55" cy="32" r="4"  fill="rgba(201,168,76,0.2)"/>
            <circle cx="55" cy="32" r="1.5" fill="rgba(201,168,76,0.8)"/>
            <rect x="53.5" y="22" width="3" height="4" rx="1" fill="rgba(201,168,76,0.35)" transform="rotate(0  55 32)"/>
            <rect x="53.5" y="22" width="3" height="4" rx="1" fill="rgba(201,168,76,0.35)" transform="rotate(60 55 32)"/>
            <rect x="53.5" y="22" width="3" height="4" rx="1" fill="rgba(201,168,76,0.35)" transform="rotate(120 55 32)"/>
            <rect x="53.5" y="22" width="3" height="4" rx="1" fill="rgba(201,168,76,0.35)" transform="rotate(180 55 32)"/>
            <rect x="53.5" y="22" width="3" height="4" rx="1" fill="rgba(201,168,76,0.35)" transform="rotate(240 55 32)"/>
            <rect x="53.5" y="22" width="3" height="4" rx="1" fill="rgba(201,168,76,0.35)" transform="rotate(300 55 32)"/>
            <!-- Person body -->
            <!-- Head -->
            <circle cx="42" cy="44" r="9" fill="#f5c5a3"/>
            <!-- Body / shirt -->
            <path d="M28 80 C28 64 56 64 56 80 L56 88 L28 88 Z" fill="rgba(26,173,110,0.85)"/>
            <!-- Arms -->
            <path d="M28 68 L18 76" stroke="#f5c5a3" stroke-width="5" stroke-linecap="round"/>
            <path d="M56 68 L66 72" stroke="#f5c5a3" stroke-width="5" stroke-linecap="round"/>
            <!-- Right hand holding arrow up -->
            <path d="M66 72 L70 62" stroke="rgba(201,168,76,0.9)" stroke-width="2.5" stroke-linecap="round"/>
            <polygon points="70,58 67,64 73,64" fill="rgba(201,168,76,0.9)"/>
            <!-- Screen / tablet in left hand -->
            <rect x="10" y="68" width="14" height="10" rx="2" fill="rgba(255,255,255,0.25)" stroke="rgba(255,255,255,0.5)" stroke-width="1"/>
            <line x1="12" y1="71" x2="22" y2="71" stroke="rgba(26,173,110,0.9)" stroke-width="1"/>
            <line x1="12" y1="74" x2="19" y2="74" stroke="rgba(26,173,110,0.6)" stroke-width="1"/>
            <!-- Hair -->
            <ellipse cx="42" cy="36" rx="9" ry="5" fill="#4a2e1a" opacity=".7"/>
            <!-- Face -->
            <circle cx="39" cy="44" r="1" fill="#333" opacity=".6"/>
            <circle cx="45" cy="44" r="1" fill="#333" opacity=".6"/>
            <path d="M39 48 Q42 51 45 48" stroke="#333" stroke-width="1" fill="none" stroke-linecap="round" opacity=".5"/>
          </svg>
        </div>
      </div>

      <h2 class="left-headline">Sur Notre<br/><em>Site Web</em></h2>
      <p class="left-sub">
        Classe <strong>ASRC</strong> · Administration Systèmes<br/>
        Réseaux &amp; Cloud — IUC Douala Logbessou
      </p>
    </div>

    <div class="stats-strip">
      <div class="stat"><span class="stat-num">ASRC</span><span class="stat-lbl">Classe</span></div>
      <div class="stat"><span class="stat-num">2025</span><span class="stat-lbl">Promotion</span></div>
      <div class="stat"><span class="stat-num">AWS</span><span class="stat-lbl">Cloud</span></div>
    </div>

  </div><!-- /panel-left -->

  <!-- ===== RIGHT PANEL ===== -->
  <div class="panel-right">
    <div class="form-panel">

      <div class="form-eyebrow">Portail étudiant</div>
      <h1 class="form-title">Créez votre<br/><span>compte</span></h1>
      <p class="form-desc">Entrez vos informations pour rejoindre la plateforme ASRC · IUC.</p>

      <?php if ($message): ?>
      <div class="msg <?= $message_type ?>">
        <span class="msg-icon"><?= $message_type === 'success' ? '✅' : '⚠️' ?></span>
        <span><?= $message ?></span>
      </div>
      <?php endif; ?>

      <form id="regForm" method="POST" action="" novalidate>

        <div class="field">
          <label for="nom">Nom de l'étudiant</label>
          <div class="input-row">
            <span class="field-icon">👤</span>
            <input
              type="text" id="nom" name="nom"
              placeholder="Votre nom complet"
              value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>"
              autocomplete="name" required/>
          </div>
        </div>

        <div class="field">
          <label for="password">Mot de passe</label>
          <div class="input-row">
            <span class="field-icon">🔑</span>
            <input type="password" id="password" name="password"
              placeholder="Minimum 6 caractères" required/>
            <button type="button" class="toggle-btn" id="togglePwd">👁</button>
          </div>
          <div class="strength-bar"><div class="strength-fill" id="strengthFill"></div></div>
        </div>

        <div class="btn-wrap">
          <button type="submit" class="btn-submit" id="submitBtn">
            <span class="btn-text">S'enregistrer</span>
          </button>
        </div>

      </form>

      <div class="form-divider">Informations</div>

      <div class="form-footer">
        Plateforme sécurisée · <strong>Powered by AWS EC2</strong><br/>
        ASRC © 2025 · IUC Douala Logbessou
      </div>

    </div>
  </div><!-- /panel-right -->

</div><!-- /split -->

<script>
// Password show/hide
document.getElementById('togglePwd').addEventListener('click', function() {
  const inp = document.getElementById('password');
  const show = inp.type === 'password';
  inp.type = show ? 'text' : 'password';
  this.textContent = show ? '🙈' : '👁';
});

// Password strength
document.getElementById('password').addEventListener('input', function() {
  const v = this.value;
  const fill = document.getElementById('strengthFill');
  let score = 0;
  if (v.length >= 6)  score++;
  if (v.length >= 10) score++;
  if (/[A-Z]/.test(v)) score++;
  if (/[0-9]/.test(v)) score++;
  if (/[^a-zA-Z0-9]/.test(v)) score++;
  const pct   = ['0%','25%','45%','70%','85%','100%'][score];
  const color = ['#e5e7eb','#ef4444','#f97316','#eab308','#22c55e','#16a34a'][score];
  fill.style.width = pct;
  fill.style.background = color;
});

// Button loading + ripple
document.getElementById('submitBtn').addEventListener('click', function(e) {
  // ripple
  const rect   = this.getBoundingClientRect();
  const circle = document.createElement('span');
  const sz     = Math.max(rect.width, rect.height);
  circle.style.cssText = `
    position:absolute;border-radius:50%;
    width:${sz}px;height:${sz}px;
    left:${e.clientX-rect.left-sz/2}px;
    top:${e.clientY-rect.top-sz/2}px;
    background:rgba(255,255,255,0.2);
    transform:scale(0);animation:rippleAnim .55s linear;
    pointer-events:none;
  `;
  this.appendChild(circle);
  if (!document.getElementById('rippleStyle')) {
    const s = document.createElement('style');
    s.id = 'rippleStyle';
    s.textContent = '@keyframes rippleAnim{to{transform:scale(4);opacity:0;}}';
    document.head.appendChild(s);
  }
  setTimeout(() => circle.remove(), 600);
  this.classList.add('loading');
  setTimeout(() => this.classList.remove('loading'), 1800);
});

// Confetti on success
<?php if ($message_type === 'success'): ?>
(function() {
  const box    = document.getElementById('confetti-box');
  const colors = ['#0d5c3a','#1aad6e','#c9a84c','#e8c96a','#ffffff','#f7f3ec'];
  for (let i = 0; i < 80; i++) {
    const p = document.createElement('div');
    p.className = 'cp';
    p.style.cssText = `
      left:${Math.random()*100}vw;
      width:${Math.random()*7+4}px;
      height:${Math.random()*11+6}px;
      background:${colors[Math.floor(Math.random()*colors.length)]};
      animation-duration:${Math.random()*2+1.5}s;
      animation-delay:${Math.random()*.8}s;
    `;
    box.appendChild(p);
    p.addEventListener('animationend', () => p.remove());
  }
})();
<?php endif; ?>

// Gear rotation animation on mascot SVG
const gearBig = document.querySelector('.mascot-svg circle[r="14"]');
let angle = 0;
function animateGears() {
  angle += 0.4;
  document.querySelectorAll('.mascot-svg rect').forEach((r, i) => {
    // Already handled by CSS spin on .mascot-ring
  });
  requestAnimationFrame(animateGears);
}
animateGears();
</script>

</body>
</html>
