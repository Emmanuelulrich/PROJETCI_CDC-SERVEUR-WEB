<?php
// =============================================
//  Configuration base de données
//  (valeurs tirées du playbook Ansible ASRC)
// =============================================
$db_host = "localhost";
$db_user = "asrc_user";       // mysql_user
$db_pass = "Emmanuel@51";     // mysql_user_password
$db_name = "asrc_db";         // mysql_db

$message      = "";
$message_type = "";
$student_name = "";

// =============================================
//  Connexion MySQL
// =============================================
function getDB($host, $user, $pass, $name) {
    $conn = new mysqli($host, $user, $pass, $name);
    if ($conn->connect_error) {
        return null;
    }
    $conn->set_charset("utf8mb4");
    return $conn;
}

// =============================================
//  Traitement du formulaire POST
// =============================================
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nom      = trim($_POST["nom"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($nom === "" || $password === "") {
        $message_type = "error";
        $message      = "⚠️ Tous les champs sont obligatoires.";
    } elseif (strlen($nom) < 3) {
        $message_type = "error";
        $message      = "⚠️ Le nom doit comporter au moins 3 caractères.";
    } elseif (strlen($password) < 6) {
        $message_type = "error";
        $message      = "⚠️ Le mot de passe doit comporter au moins 6 caractères.";
    } else {
        $conn = getDB($db_host, $db_user, $db_pass, $db_name);
        if (!$conn) {
            $message_type = "error";
            $message      = "❌ Connexion à la base de données impossible. Vérifiez la configuration du serveur.";
        } else {
            // Vérifier si le nom est déjà pris (colonne UNIQUE dans la table)
            $stmt = $conn->prepare("SELECT id FROM etudiants WHERE nom = ?");
            $stmt->bind_param("s", $nom);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $message_type = "error";
                $message      = "⚠️ Ce nom est déjà enregistré. Veuillez en choisir un autre.";
            } else {
                // Hachage du mot de passe avant stockage
                $hashed = password_hash($password, PASSWORD_BCRYPT);
                // Colonne correcte : mot_de_passe (définie dans le playbook Ansible)
                $ins = $conn->prepare("INSERT INTO etudiants (nom, mot_de_passe) VALUES (?, ?)");
                $ins->bind_param("ss", $nom, $hashed);
                if ($ins->execute()) {
                    $message_type = "success";
                    $student_name = htmlspecialchars($nom);
                    $message      = "✅ Enregistrement réussi ! Bienvenue, <strong>$student_name</strong>. Votre compte a bien été créé.";
                } else {
                    $message_type = "error";
                    $message      = "❌ Erreur lors de l'enregistrement : " . htmlspecialchars($conn->error);
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
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>IUC · ASRC — Enregistrement</title>

<!-- Google Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com"/>
<link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@400;600;700&family=Cormorant+Garamond:ital,wght@0,300;0,600;1,300&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet"/>

<style>
/* ============================
   VARIABLES & RESET
============================ */
:root {
  --bg-deep:      #050810;
  --bg-card:      rgba(10, 14, 28, 0.82);
  --accent1:      #00d4ff;   /* cyan électrique */
  --accent2:      #ff4d6d;   /* rose-rouge */
  --accent3:      #ffd166;   /* or */
  --text-main:    #e8eaf6;
  --text-muted:   #8892b0;
  --border:       rgba(0, 212, 255, 0.18);
  --success-bg:   rgba(0, 255, 136, 0.08);
  --success-border: rgba(0, 255, 136, 0.45);
  --success-text: #00ff88;
  --error-bg:     rgba(255, 77, 109, 0.08);
  --error-border: rgba(255, 77, 109, 0.45);
  --error-text:   #ff4d6d;
  --glow-cyan:    0 0 30px rgba(0,212,255,0.35);
  --glow-red:     0 0 30px rgba(255,77,109,0.35);
  --font-display: 'Rajdhani', sans-serif;
  --font-body:    'Cormorant Garamond', serif;
  --font-mono:    'JetBrains Mono', monospace;
}

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

html { scroll-behavior: smooth; }

body {
  font-family: var(--font-body);
  background: var(--bg-deep);
  color: var(--text-main);
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow-x: hidden;
  position: relative;
}

/* ============================
   BACKGROUND ANIMATED GRID
============================ */
.bg-grid {
  position: fixed; inset: 0; z-index: 0;
  background-image:
    linear-gradient(rgba(0,212,255,0.04) 1px, transparent 1px),
    linear-gradient(90deg, rgba(0,212,255,0.04) 1px, transparent 1px);
  background-size: 60px 60px;
  animation: gridDrift 20s linear infinite;
}
@keyframes gridDrift {
  from { background-position: 0 0; }
  to   { background-position: 60px 60px; }
}

/* Floating orbs */
.orb {
  position: fixed; border-radius: 50%;
  filter: blur(90px); opacity: 0.18; z-index: 0;
  animation: orbFloat 12s ease-in-out infinite alternate;
}
.orb-1 { width: 500px; height: 500px; background: var(--accent1); top: -150px; left: -150px; animation-duration: 14s; }
.orb-2 { width: 400px; height: 400px; background: var(--accent2); bottom: -100px; right: -100px; animation-duration: 10s; animation-delay: -5s; }
.orb-3 { width: 300px; height: 300px; background: var(--accent3); top: 50%; left: 60%; animation-duration: 18s; animation-delay: -3s; }

@keyframes orbFloat {
  from { transform: translate(0,0) scale(1); }
  to   { transform: translate(40px, 30px) scale(1.15); }
}

/* Scan line overlay */
body::after {
  content: '';
  position: fixed; inset: 0; z-index: 1; pointer-events: none;
  background: repeating-linear-gradient(
    0deg,
    transparent,
    transparent 2px,
    rgba(0,0,0,0.08) 2px,
    rgba(0,0,0,0.08) 4px
  );
}

/* ============================
   PARTICLES canvas
============================ */
#particles { position: fixed; inset: 0; z-index: 0; }

/* ============================
   CARD
============================ */
.card-wrapper {
  position: relative; z-index: 10;
  width: 100%; max-width: 520px;
  padding: 24px;
  animation: cardEntry 1s cubic-bezier(.22,1,.36,1) both;
}
@keyframes cardEntry {
  from { opacity: 0; transform: translateY(60px) scale(.95); }
  to   { opacity: 1; transform: translateY(0) scale(1); }
}

.card {
  background: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: 20px;
  padding: 48px 44px 40px;
  backdrop-filter: blur(28px) saturate(1.4);
  box-shadow:
    0 0 0 1px rgba(0,212,255,0.06),
    0 30px 80px rgba(0,0,0,0.7),
    inset 0 1px 0 rgba(255,255,255,0.05);
  position: relative;
  overflow: hidden;
}

/* Animated corner accents */
.card::before, .card::after {
  content: '';
  position: absolute;
  width: 60px; height: 60px;
  border-color: var(--accent1);
  border-style: solid;
  opacity: 0.6;
}
.card::before {
  top: 14px; left: 14px;
  border-width: 2px 0 0 2px;
  border-radius: 6px 0 0 0;
  animation: cornerPulse 3s ease-in-out infinite;
}
.card::after {
  bottom: 14px; right: 14px;
  border-width: 0 2px 2px 0;
  border-radius: 0 0 6px 0;
  animation: cornerPulse 3s ease-in-out infinite reverse;
}
@keyframes cornerPulse {
  0%,100% { opacity: 0.4; }
  50%      { opacity: 1; }
}

/* Glowing top line */
.card-topline {
  position: absolute; top: 0; left: 10%; right: 10%; height: 2px;
  background: linear-gradient(90deg, transparent, var(--accent1), var(--accent2), transparent);
  border-radius: 0 0 4px 4px;
  animation: lineShimmer 3s ease-in-out infinite;
}
@keyframes lineShimmer {
  0%,100% { opacity: 0.6; transform: scaleX(0.7); }
  50%      { opacity: 1;   transform: scaleX(1); }
}

/* ============================
   LOGO / BADGE
============================ */
.logo-area {
  text-align: center;
  margin-bottom: 28px;
  animation: fadeSlideDown .8s .15s both;
}
@keyframes fadeSlideDown {
  from { opacity: 0; transform: translateY(-20px); }
  to   { opacity: 1; transform: translateY(0); }
}

.badge {
  display: inline-flex; align-items: center; gap: 8px;
  background: rgba(0,212,255,0.06);
  border: 1px solid rgba(0,212,255,0.22);
  border-radius: 40px;
  padding: 6px 18px;
  font-family: var(--font-display);
  font-size: .72rem; letter-spacing: .2em; text-transform: uppercase;
  color: var(--accent1);
}
.badge-dot {
  width: 6px; height: 6px; border-radius: 50%;
  background: var(--accent1);
  animation: dotBlink 1.6s ease-in-out infinite;
}
@keyframes dotBlink {
  0%,100% { opacity: 1; transform: scale(1); }
  50%      { opacity: 0.3; transform: scale(0.5); }
}

/* ============================
   HEADINGS
============================ */
.heading-block {
  text-align: center;
  margin-bottom: 32px;
  animation: fadeSlideDown .8s .3s both;
}

.welcome-label {
  font-family: var(--font-mono);
  font-size: .68rem; letter-spacing: .25em; text-transform: uppercase;
  color: var(--accent3);
  margin-bottom: 10px;
  display: flex; align-items: center; justify-content: center; gap: 10px;
}
.welcome-label::before, .welcome-label::after {
  content: ''; flex: 1; max-width: 50px;
  height: 1px; background: linear-gradient(90deg, transparent, var(--accent3));
}
.welcome-label::after { background: linear-gradient(270deg, transparent, var(--accent3)); }

h1 {
  font-family: var(--font-display);
  font-size: 2.6rem; font-weight: 700; line-height: 1.1;
  letter-spacing: .03em;
  background: linear-gradient(135deg, #ffffff 30%, var(--accent1) 70%, var(--accent2));
  -webkit-background-clip: text; -webkit-text-fill-color: transparent;
  background-clip: text;
  margin-bottom: 10px;
}

.subtitle {
  font-size: 1rem; color: var(--text-muted);
  line-height: 1.5; font-style: italic;
}
.subtitle span { color: var(--accent2); font-style: normal; font-weight: 600; }

/* ============================
   MESSAGE BOX
============================ */
.message-box {
  border-radius: 10px;
  padding: 14px 18px;
  margin-bottom: 26px;
  font-family: var(--font-display);
  font-size: .95rem; letter-spacing: .02em;
  display: flex; align-items: flex-start; gap: 12px;
  animation: messageEntry .5s cubic-bezier(.22,1,.36,1) both;
  position: relative; overflow: hidden;
}
@keyframes messageEntry {
  from { opacity: 0; transform: translateX(-20px); max-height: 0; }
  to   { opacity: 1; transform: translateX(0);    max-height: 200px; }
}

.message-box.success {
  background: var(--success-bg);
  border: 1px solid var(--success-border);
  color: var(--success-text);
  box-shadow: 0 0 20px rgba(0,255,136,0.12);
}
.message-box.error {
  background: var(--error-bg);
  border: 1px solid var(--error-border);
  color: var(--error-text);
  box-shadow: 0 0 20px rgba(255,77,109,0.12);
}

/* Shimmer scan on message */
.message-box::after {
  content: '';
  position: absolute; top: 0; left: -100%; width: 60%; height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,0.06), transparent);
  animation: msgScan 2.5s ease-in-out infinite;
}
@keyframes msgScan {
  from { left: -60%; }
  to   { left: 120%; }
}

/* ============================
   DIVIDER
============================ */
.divider {
  display: flex; align-items: center; gap: 14px;
  margin-bottom: 24px;
  font-family: var(--font-mono);
  font-size: .65rem; letter-spacing: .2em;
  color: var(--text-muted); text-transform: uppercase;
  animation: fadeIn .8s .45s both;
}
.divider::before, .divider::after {
  content: ''; flex: 1; height: 1px;
  background: linear-gradient(90deg, transparent, var(--border));
}
.divider::after { background: linear-gradient(270deg, transparent, var(--border)); }

/* ============================
   FORM
============================ */
.form-group {
  margin-bottom: 22px;
  animation: fadeSlideUp .7s both;
}
.form-group:nth-child(1) { animation-delay: .5s; }
.form-group:nth-child(2) { animation-delay: .65s; }
.form-group:nth-child(3) { animation-delay: .8s; }

@keyframes fadeSlideUp {
  from { opacity: 0; transform: translateY(18px); }
  to   { opacity: 1; transform: translateY(0); }
}

label {
  display: block;
  font-family: var(--font-mono);
  font-size: .68rem; letter-spacing: .18em; text-transform: uppercase;
  color: var(--text-muted);
  margin-bottom: 8px;
}

.input-wrap {
  position: relative;
}

.input-icon {
  position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
  font-size: 1rem; pointer-events: none;
  transition: color .3s;
}

input[type="text"],
input[type="password"] {
  width: 100%;
  background: rgba(0,212,255,0.04);
  border: 1px solid var(--border);
  border-radius: 10px;
  padding: 14px 14px 14px 44px;
  font-family: var(--font-display);
  font-size: 1rem; letter-spacing: .04em;
  color: var(--text-main);
  outline: none;
  transition: border-color .3s, box-shadow .3s, background .3s;
  caret-color: var(--accent1);
}

input:focus {
  border-color: var(--accent1);
  background: rgba(0,212,255,0.07);
  box-shadow: var(--glow-cyan), inset 0 0 0 1px rgba(0,212,255,0.1);
}
input:focus + .focus-line { transform: scaleX(1); }

.focus-line {
  position: absolute; bottom: 0; left: 10%; right: 10%; height: 2px;
  background: linear-gradient(90deg, var(--accent1), var(--accent2));
  border-radius: 0 0 4px 4px;
  transform: scaleX(0); transform-origin: center;
  transition: transform .35s cubic-bezier(.22,1,.36,1);
}

input::placeholder { color: rgba(136,146,176,0.45); }

/* Password toggle */
.toggle-pwd {
  position: absolute; right: 14px; top: 50%; transform: translateY(-50%);
  background: none; border: none; cursor: pointer;
  color: var(--text-muted); font-size: 1rem;
  transition: color .3s;
}
.toggle-pwd:hover { color: var(--accent1); }

/* ============================
   SUBMIT BUTTON
============================ */
.btn-submit {
  width: 100%; position: relative; overflow: hidden;
  background: linear-gradient(135deg, #0099cc, #00d4ff 50%, #00a8cc);
  border: none; border-radius: 10px;
  padding: 16px 28px;
  font-family: var(--font-display);
  font-size: 1rem; font-weight: 700; letter-spacing: .2em; text-transform: uppercase;
  color: #050810;
  cursor: pointer;
  transition: transform .2s, box-shadow .3s;
  box-shadow: 0 8px 32px rgba(0,212,255,0.28), 0 0 0 1px rgba(0,212,255,0.15);
  animation: fadeSlideUp .7s .9s both;
}

.btn-submit:hover {
  transform: translateY(-3px);
  box-shadow: 0 14px 40px rgba(0,212,255,0.45), 0 0 0 2px rgba(0,212,255,0.3);
}
.btn-submit:active { transform: translateY(0); }

/* Shimmer sweep on hover */
.btn-submit::before {
  content: '';
  position: absolute; top: 0; left: -100%; width: 60%; height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,0.35), transparent);
  transition: left .45s;
}
.btn-submit:hover::before { left: 140%; }

/* Ripple */
.btn-submit .ripple {
  position: absolute; border-radius: 50%;
  background: rgba(255,255,255,0.35);
  transform: scale(0); animation: ripple .6s linear;
  pointer-events: none;
}
@keyframes ripple {
  to { transform: scale(4); opacity: 0; }
}

/* ============================
   FOOTER
============================ */
.card-footer {
  text-align: center; margin-top: 28px;
  font-family: var(--font-mono); font-size: .65rem;
  letter-spacing: .12em; color: rgba(136,146,176,0.4);
  animation: fadeIn 1s 1.1s both;
}
.card-footer span { color: var(--accent2); }

@keyframes fadeIn {
  from { opacity: 0; }
  to   { opacity: 1; }
}

/* ============================
   LOADING STATE
============================ */
.btn-submit.loading .btn-text { opacity: 0; }
.btn-submit.loading::after {
  content: '';
  position: absolute; top: 50%; left: 50%;
  width: 22px; height: 22px; margin: -11px;
  border: 3px solid rgba(5,8,16,0.3);
  border-top-color: #050810;
  border-radius: 50%;
  animation: spin .7s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* ============================
   SUCCESS CONFETTI OVERLAY
============================ */
.confetti-container {
  position: fixed; inset: 0; pointer-events: none; z-index: 100;
  overflow: hidden;
}
.confetti-piece {
  position: absolute; top: -10px;
  width: 8px; height: 14px;
  border-radius: 2px;
  animation: confettiFall linear forwards;
}
@keyframes confettiFall {
  0%   { transform: translateY(0) rotate(0deg); opacity: 1; }
  100% { transform: translateY(110vh) rotate(720deg); opacity: 0; }
}

/* ============================
   RESPONSIVE
============================ */
@media (max-width: 560px) {
  .card { padding: 36px 24px 32px; }
  h1 { font-size: 2rem; }
}
</style>
</head>
<body>

<!-- Background layers -->
<div class="bg-grid"></div>
<div class="orb orb-1"></div>
<div class="orb orb-2"></div>
<div class="orb orb-3"></div>
<canvas id="particles"></canvas>

<!-- Confetti container (populated by JS on success) -->
<div class="confetti-container" id="confetti"></div>

<!-- ============================
     CARD
============================ -->
<div class="card-wrapper">
  <div class="card">
    <div class="card-topline"></div>

    <!-- Logo -->
    <div class="logo-area">
      <span class="badge">
        <span class="badge-dot"></span>
        Institut Universitaire de la Côte · IUC
      </span>
    </div>

    <!-- Heading -->
    <div class="heading-block">
      <div class="welcome-label">Bienvenue</div>
      <h1>Portail ASRC</h1>
      <p class="subtitle">
        Classe <span>ASRC</span> · Administration Systèmes<br/>
        Réseaux &amp; Cloud — IUC Douala Logbessou
      </p>
    </div>

    <!-- Message (success / error) -->
    <?php if ($message): ?>
    <div class="message-box <?= $message_type ?>">
      <?= $message ?>
    </div>
    <?php endif; ?>

    <!-- Divider -->
    <div class="divider">Enregistrement</div>

    <!-- Form -->
    <form id="regForm" method="POST" action="" novalidate>

      <div class="form-group">
        <label for="nom">Nom de l'étudiant</label>
        <div class="input-wrap">
          <span class="input-icon">👤</span>
          <input
            type="text"
            id="nom"
            name="nom"
            autocomplete="name"
            placeholder="Entrez votre nom complet"
            value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>"
            required
          />
          <div class="focus-line"></div>
        </div>
      </div>

      <div class="form-group">
        <label for="password">Mot de passe</label>
        <div class="input-wrap">
          <span class="input-icon">🔐</span>
          <input
            type="password"
            id="password"
            name="password"
            placeholder="Minimum 6 caractères"
            required
          />
          <button type="button" class="toggle-pwd" id="togglePwd" title="Afficher/Masquer">👁</button>
          <div class="focus-line"></div>
        </div>
      </div>

      <div class="form-group">
        <button type="submit" class="btn-submit" id="submitBtn">
          <span class="btn-text">S'enregistrer</span>
        </button>
      </div>

    </form>

    <!-- Footer -->
    <div class="card-footer">
      ASRC © 2025 · IUC · <span>Powered by AWS EC2</span>
    </div>
  </div>
</div>

<!-- ============================
     JAVASCRIPT
============================ -->
<script>
// ----------------------------------------
// Particle system (floating dots)
// ----------------------------------------
(function() {
  const canvas = document.getElementById('particles');
  const ctx    = canvas.getContext('2d');
  let W, H, particles = [];

  function resize() {
    W = canvas.width  = window.innerWidth;
    H = canvas.height = window.innerHeight;
  }
  window.addEventListener('resize', resize);
  resize();

  const colors = ['rgba(0,212,255,', 'rgba(255,77,109,', 'rgba(255,209,102,'];
  for (let i = 0; i < 70; i++) {
    particles.push({
      x: Math.random() * (W || 1000),
      y: Math.random() * (H || 800),
      r: Math.random() * 1.8 + 0.4,
      dx: (Math.random() - .5) * 0.5,
      dy: (Math.random() - .5) * 0.4,
      color: colors[Math.floor(Math.random() * colors.length)],
      alpha: Math.random() * 0.5 + 0.2
    });
  }

  function draw() {
    ctx.clearRect(0, 0, W, H);
    particles.forEach(p => {
      ctx.beginPath();
      ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
      ctx.fillStyle = p.color + p.alpha + ')';
      ctx.fill();
      p.x += p.dx; p.y += p.dy;
      if (p.x < 0) p.x = W; if (p.x > W) p.x = 0;
      if (p.y < 0) p.y = H; if (p.y > H) p.y = 0;
    });

    // Draw lines between close particles
    for (let i = 0; i < particles.length; i++) {
      for (let j = i + 1; j < particles.length; j++) {
        const dx = particles[i].x - particles[j].x;
        const dy = particles[i].y - particles[j].y;
        const dist = Math.sqrt(dx*dx + dy*dy);
        if (dist < 100) {
          ctx.beginPath();
          ctx.moveTo(particles[i].x, particles[i].y);
          ctx.lineTo(particles[j].x, particles[j].y);
          ctx.strokeStyle = `rgba(0,212,255,${0.07 * (1 - dist/100)})`;
          ctx.lineWidth = 0.5;
          ctx.stroke();
        }
      }
    }
    requestAnimationFrame(draw);
  }
  draw();
})();

// ----------------------------------------
// Button ripple effect
// ----------------------------------------
document.getElementById('submitBtn').addEventListener('click', function(e) {
  const rect   = this.getBoundingClientRect();
  const ripple = document.createElement('span');
  const size   = Math.max(rect.width, rect.height);
  ripple.className = 'ripple';
  ripple.style.cssText =
    `width:${size}px;height:${size}px;left:${e.clientX-rect.left-size/2}px;top:${e.clientY-rect.top-size/2}px`;
  this.appendChild(ripple);
  setTimeout(() => ripple.remove(), 700);

  // Loading state
  const btn = this;
  btn.classList.add('loading');
  setTimeout(() => btn.classList.remove('loading'), 1800);
});

// ----------------------------------------
// Password visibility toggle
// ----------------------------------------
document.getElementById('togglePwd').addEventListener('click', function() {
  const input = document.getElementById('password');
  const show  = input.type === 'password';
  input.type  = show ? 'text' : 'password';
  this.textContent = show ? '🙈' : '👁';
});

// ----------------------------------------
// Confetti on success
// ----------------------------------------
<?php if ($message_type === 'success'): ?>
(function spawnConfetti() {
  const container = document.getElementById('confetti');
  const colors    = ['#00d4ff','#ff4d6d','#ffd166','#00ff88','#ffffff'];
  for (let i = 0; i < 90; i++) {
    const piece = document.createElement('div');
    piece.className = 'confetti-piece';
    piece.style.left     = Math.random() * 100 + 'vw';
    piece.style.background = colors[Math.floor(Math.random() * colors.length)];
    piece.style.width    = (Math.random() * 6 + 5) + 'px';
    piece.style.height   = (Math.random() * 10 + 8) + 'px';
    piece.style.animationDuration = (Math.random() * 2 + 1.5) + 's';
    piece.style.animationDelay   = (Math.random() * 1.2) + 's';
    container.appendChild(piece);
    piece.addEventListener('animationend', () => piece.remove());
  }
})();
<?php endif; ?>

// ----------------------------------------
// Input focus glow on icon
// ----------------------------------------
document.querySelectorAll('input').forEach(input => {
  const icon = input.closest('.input-wrap').querySelector('.input-icon');
  input.addEventListener('focus',  () => { if(icon) icon.style.filter = 'drop-shadow(0 0 6px rgba(0,212,255,.9))'; });
  input.addEventListener('blur',   () => { if(icon) icon.style.filter = ''; });
});
</script>

</body>
</html>
