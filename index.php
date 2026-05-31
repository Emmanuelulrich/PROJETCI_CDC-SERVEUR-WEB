<?php
$db_host = "localhost";
$db_user = "asrc_user";
$db_pass = "Emmanuel@51";
$db_name = "asrc_db";

$message      = "";
$message_type = "";
$student_name = "";

function getDB($h,$u,$p,$n){
    $c = new mysqli($h,$u,$p,$n);
    if($c->connect_error) return null;
    $c->set_charset("utf8mb4");
    return $c;
}

if($_SERVER["REQUEST_METHOD"]==="POST"){
    $nom = trim($_POST["nom"] ?? "");
    $pwd = $_POST["password"] ?? "";
    if($nom===""||$pwd===""){
        $message_type="error"; $message="Tous les champs sont obligatoires.";
    } elseif(strlen($nom)<3){
        $message_type="error"; $message="Le nom doit comporter au moins 3 caractères.";
    } elseif(strlen($pwd)<6){
        $message_type="error"; $message="Le mot de passe doit comporter au moins 6 caractères.";
    } else {
        $conn=getDB($db_host,$db_user,$db_pass,$db_name);
        if(!$conn){
            $message_type="error"; $message="Connexion à la base de données impossible.";
        } else {
            $s=$conn->prepare("SELECT id FROM etudiants WHERE nom=?");
            $s->bind_param("s",$nom); $s->execute(); $s->store_result();
            if($s->num_rows>0){
                $message_type="error"; $message="Ce nom est déjà enregistré. Choisissez un autre nom.";
            } else {
                $h2=password_hash($pwd,PASSWORD_BCRYPT);
                $i=$conn->prepare("INSERT INTO etudiants (nom,mot_de_passe) VALUES (?,?)");
                $i->bind_param("ss",$nom,$h2);
                if($i->execute()){
                    $message_type="success";
                    $student_name=htmlspecialchars($nom);
                    $message="Bienvenue, <strong>$student_name</strong> ! Compte créé avec succès.";
                } else {
                    $message_type="error"; $message="Erreur : ".htmlspecialchars($conn->error);
                }
                $i->close();
            }
            $s->close(); $conn->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>ASRC · IUC — Portail</title>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet"/>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --c1:#00fff7;--c2:#ff2d78;--c3:#ffe600;
  --dark:#04060f;--card-bg:rgba(255,255,255,0.04);
  --border:rgba(255,255,255,0.09);
  --ff-title:'Bebas Neue',sans-serif;
  --ff-body:'Outfit',sans-serif;
}
html,body{height:100%;overflow:hidden}
body{font-family:var(--ff-body);background:var(--dark);color:#fff;position:relative}

/* ── CANVAS BG ── */
#bg{position:fixed;inset:0;z-index:0}

/* ── AURORA BLOBS ── */
.aurora{position:fixed;border-radius:50%;filter:blur(120px);opacity:.18;z-index:1;pointer-events:none}
.a1{width:700px;height:700px;background:var(--c1);top:-200px;left:-200px;animation:aFloat 18s ease-in-out infinite alternate}
.a2{width:600px;height:600px;background:var(--c2);bottom:-180px;right:-180px;animation:aFloat 14s ease-in-out infinite alternate-reverse}
.a3{width:400px;height:400px;background:var(--c3);top:40%;left:45%;animation:aFloat 22s ease-in-out infinite alternate}
@keyframes aFloat{0%{transform:translate(0,0) scale(1)}100%{transform:translate(60px,40px) scale(1.2)}}

/* ── GRID LINES ── */
.grid-overlay{
  position:fixed;inset:0;z-index:1;pointer-events:none;
  background-image:
    linear-gradient(rgba(0,255,247,.04) 1px,transparent 1px),
    linear-gradient(90deg,rgba(0,255,247,.04) 1px,transparent 1px);
  background-size:80px 80px;
  animation:gridMove 25s linear infinite;
}
@keyframes gridMove{to{background-position:80px 80px}}

/* ── SCAN LINE ── */
.scanline{
  position:fixed;inset:0;z-index:2;pointer-events:none;
  background:repeating-linear-gradient(0deg,transparent,transparent 3px,rgba(0,0,0,.06) 3px,rgba(0,0,0,.06) 4px);
}

/* ── LAYOUT ── */
.page{
  position:relative;z-index:10;
  width:100%;height:100vh;
  display:grid;grid-template-columns:1fr 480px;
  overflow:hidden;
}

/* ── LEFT HERO ── */
.hero{
  display:flex;flex-direction:column;
  justify-content:center;padding:60px 64px;
  position:relative;overflow:hidden;
}
.hero-line{
  position:absolute;top:0;bottom:0;right:0;
  width:1px;background:linear-gradient(180deg,transparent,rgba(0,255,247,.4),transparent);
}

.iuc-pill{
  display:inline-flex;align-items:center;gap:8px;
  background:rgba(0,255,247,.07);border:1px solid rgba(0,255,247,.2);
  border-radius:40px;padding:7px 18px;
  font-size:.72rem;letter-spacing:.2em;text-transform:uppercase;color:var(--c1);
  margin-bottom:36px;width:fit-content;
  animation:pillIn .8s .1s both;
}
.pill-dot{width:6px;height:6px;border-radius:50%;background:var(--c1);animation:dotPulse 2s infinite}
@keyframes dotPulse{0%,100%{box-shadow:0 0 0 0 rgba(0,255,247,.6)}50%{box-shadow:0 0 0 6px rgba(0,255,247,0)}}

/* Giant title */
.hero-title{
  font-family:var(--ff-title);
  line-height:.92;letter-spacing:.02em;
  animation:titleIn .9s .2s both;
}
.hero-title .line1{font-size:clamp(70px,8vw,120px);color:#fff;display:block}
.hero-title .line2{
  font-size:clamp(70px,8vw,120px);display:block;
  background:linear-gradient(90deg,var(--c1),var(--c2));
  -webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;
  position:relative;
}
/* Glitch effect on line2 */
.hero-title .line2::before,.hero-title .line2::after{
  content:attr(data-text);position:absolute;top:0;left:0;
  background:linear-gradient(90deg,var(--c1),var(--c2));
  -webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;
}
.hero-title .line2::before{animation:glitch1 4s infinite;clip-path:polygon(0 0,100% 0,100% 35%,0 35%)}
.hero-title .line2::after{animation:glitch2 4s infinite;clip-path:polygon(0 65%,100% 65%,100% 100%,0 100%)}
@keyframes glitch1{0%,90%,100%{transform:translate(0)}92%{transform:translate(-3px,1px)}94%{transform:translate(3px,-1px)}96%{transform:translate(-2px,0)}}
@keyframes glitch2{0%,90%,100%{transform:translate(0)}93%{transform:translate(3px,2px)}95%{transform:translate(-3px,-1px)}97%{transform:translate(2px,0)}}

.hero-title .line3{font-size:clamp(32px,4vw,56px);color:rgba(255,255,255,.18);display:block}

.hero-sub{
  margin-top:28px;font-size:1rem;font-weight:300;line-height:1.65;
  color:rgba(255,255,255,.45);max-width:400px;
  animation:subIn .8s .4s both;
}
.hero-sub strong{color:rgba(255,255,255,.8);font-weight:500}

/* Mascot area */
.mascot-zone{
  margin-top:48px;display:flex;align-items:center;gap:20px;
  animation:subIn .8s .55s both;
}
.mascot-frame{
  width:80px;height:80px;border-radius:50%;
  background:radial-gradient(circle,rgba(0,255,247,.15),transparent);
  border:1px solid rgba(0,255,247,.25);
  display:flex;align-items:center;justify-content:center;
  position:relative;flex-shrink:0;
}
.mascot-frame::before{
  content:'';position:absolute;inset:-8px;border-radius:50%;
  border:1px dashed rgba(0,255,247,.2);
  animation:spinRing 12s linear infinite;
}
@keyframes spinRing{to{transform:rotate(360deg)}}

/* Counter strip */
.counters{display:flex;gap:36px;margin-top:48px;animation:subIn .8s .7s both}
.ctr{text-align:center}
.ctr-num{
  font-family:var(--ff-title);font-size:2.4rem;
  background:linear-gradient(135deg,#fff,var(--c1));
  -webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;
  display:block;
}
.ctr-lbl{font-size:.65rem;letter-spacing:.18em;text-transform:uppercase;color:rgba(255,255,255,.3)}

/* ── RIGHT FORM PANEL ── */
.form-side{
  background:rgba(4,6,15,.7);
  border-left:1px solid var(--border);
  backdrop-filter:blur(40px) saturate(1.5);
  display:flex;align-items:center;justify-content:center;
  padding:40px 48px;
  position:relative;overflow:hidden;
  animation:panelIn 1s .3s both;
}
@keyframes panelIn{from{transform:translateX(60px);opacity:0}to{transform:translateX(0);opacity:1}}

/* Top glow line */
.form-side::before{
  content:'';position:absolute;top:0;left:10%;right:10%;height:1px;
  background:linear-gradient(90deg,transparent,var(--c2),var(--c1),transparent);
  animation:topGlow 3s ease-in-out infinite alternate;
}
@keyframes topGlow{0%{opacity:.4;transform:scaleX(.6)}100%{opacity:1;transform:scaleX(1)}}

/* Corner brackets */
.form-side::after{
  content:'';position:absolute;bottom:20px;right:20px;
  width:40px;height:40px;
  border-right:1px solid rgba(0,255,247,.3);
  border-bottom:1px solid rgba(0,255,247,.3);
}

.form-inner{width:100%;max-width:340px;position:relative;z-index:1}

/* Form heading */
.f-eyebrow{
  font-size:.68rem;letter-spacing:.25em;text-transform:uppercase;
  color:var(--c2);margin-bottom:10px;
  display:flex;align-items:center;gap:10px;
  animation:fadeD .6s .5s both;
}
.f-eyebrow::after{content:'';flex:1;height:1px;background:linear-gradient(90deg,var(--c2),transparent);opacity:.4}

.f-title{
  font-family:var(--ff-title);font-size:3rem;line-height:1;letter-spacing:.03em;
  color:#fff;margin-bottom:6px;
  animation:fadeD .6s .6s both;
}
.f-title span{
  background:linear-gradient(90deg,var(--c1),var(--c3));
  -webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;
}

.f-desc{font-size:.85rem;color:rgba(255,255,255,.35);margin-bottom:28px;line-height:1.5;animation:fadeD .6s .7s both}

/* Message */
.msg{
  border-radius:8px;padding:12px 16px;margin-bottom:22px;
  font-size:.85rem;line-height:1.5;display:flex;gap:10px;align-items:flex-start;
  animation:msgIn .4s cubic-bezier(.22,1,.36,1) both;
}
@keyframes msgIn{from{opacity:0;transform:translateY(-10px) scale(.97)}to{opacity:1;transform:none}}
.msg.success{background:rgba(0,255,136,.08);border:1px solid rgba(0,255,136,.3);color:#00ff88}
.msg.error{background:rgba(255,45,120,.08);border:1px solid rgba(255,45,120,.3);color:#ff6fa0}

/* Fields */
.field{margin-bottom:18px;animation:fadeU .6s both}
.field:nth-child(1){animation-delay:.75s}
.field:nth-child(2){animation-delay:.88s}

.field label{
  display:block;font-size:.65rem;letter-spacing:.18em;
  text-transform:uppercase;color:rgba(255,255,255,.3);margin-bottom:7px;
}

.inp-wrap{position:relative}
.inp-ico{position:absolute;left:14px;top:50%;transform:translateY(-50%);font-size:.95rem;pointer-events:none;transition:filter .25s}

input[type=text],input[type=password]{
  width:100%;
  background:rgba(255,255,255,.04);
  border:1px solid rgba(255,255,255,.1);
  border-radius:8px;padding:13px 14px 13px 44px;
  font-family:var(--ff-body);font-size:.95rem;color:#fff;outline:none;
  transition:border-color .25s,background .25s,box-shadow .25s;
  caret-color:var(--c1);
}
input::placeholder{color:rgba(255,255,255,.18)}
input:focus{
  border-color:rgba(0,255,247,.5);
  background:rgba(0,255,247,.05);
  box-shadow:0 0 0 3px rgba(0,255,247,.1),0 0 30px rgba(0,255,247,.06);
}
.inp-wrap:focus-within .inp-ico{filter:drop-shadow(0 0 6px var(--c1))}

/* Animated underline */
.inp-line{
  height:1px;margin-top:4px;
  background:linear-gradient(90deg,var(--c1),var(--c2));
  transform:scaleX(0);transform-origin:left;
  transition:transform .35s cubic-bezier(.22,1,.36,1);
  border-radius:1px;
}
.inp-wrap:focus-within ~ .inp-line,
.inp-wrap:focus-within+.inp-line{transform:scaleX(1)}

.toggle-p{
  position:absolute;right:12px;top:50%;transform:translateY(-50%);
  background:none;border:none;cursor:pointer;color:rgba(255,255,255,.3);
  font-size:.9rem;transition:color .2s;
}
.toggle-p:hover{color:var(--c1)}

/* Strength bar */
.sbar{height:2px;background:rgba(255,255,255,.07);border-radius:1px;margin-top:5px;overflow:hidden}
.sfill{height:100%;width:0;border-radius:1px;transition:width .4s,background .4s}

/* Submit */
.btn-area{margin-top:8px;animation:fadeU .6s 1s both}
.btn{
  width:100%;border:none;border-radius:8px;
  padding:15px;cursor:pointer;position:relative;overflow:hidden;
  font-family:var(--ff-title);font-size:1.3rem;letter-spacing:.1em;
  color:var(--dark);
  background:linear-gradient(90deg,var(--c1),#00c8e0,var(--c1));
  background-size:200% auto;
  transition:background-position .5s,transform .2s,box-shadow .3s;
  box-shadow:0 6px 30px rgba(0,255,247,.25);
}
.btn:hover{background-position:right center;transform:translateY(-2px);box-shadow:0 12px 40px rgba(0,255,247,.4)}
.btn:active{transform:translateY(0)}
.btn::after{
  content:'';position:absolute;top:0;left:-80%;width:50%;height:100%;
  background:linear-gradient(90deg,transparent,rgba(255,255,255,.4),transparent);
  transition:left .55s;
}
.btn:hover::after{left:140%}
.btn.ld{pointer-events:none;color:transparent}
.btn.ld::before{
  content:'';position:absolute;top:50%;left:50%;
  width:22px;height:22px;margin:-11px;
  border:2.5px solid rgba(4,6,15,.3);
  border-top-color:var(--dark);
  border-radius:50%;animation:spin .7s linear infinite;
}
@keyframes spin{to{transform:rotate(360deg)}}

/* Footer */
.f-foot{
  margin-top:24px;text-align:center;
  font-size:.68rem;letter-spacing:.1em;color:rgba(255,255,255,.2);
  animation:fadeU .6s 1.1s both;
}
.f-foot strong{color:rgba(0,255,247,.5)}

/* Animations */
@keyframes pillIn{from{opacity:0;transform:translateY(-14px)}to{opacity:1;transform:none}}
@keyframes titleIn{from{opacity:0;transform:translateY(30px)}to{opacity:1;transform:none}}
@keyframes subIn{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:none}}
@keyframes fadeD{from{opacity:0;transform:translateY(-12px)}to{opacity:1;transform:none}}
@keyframes fadeU{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:none}}

/* ── CONFETTI ── */
#cbox{position:fixed;inset:0;pointer-events:none;z-index:999;overflow:hidden}
.cp{position:absolute;top:-10px;border-radius:2px;animation:cfall linear forwards}
@keyframes cfall{to{transform:translateY(110vh) rotate(720deg);opacity:0}}

/* ── RESPONSIVE ── */
@media(max-width:860px){
  .page{grid-template-columns:1fr;grid-template-rows:auto 1fr;overflow:auto}
  html,body{overflow:auto}
  .hero{padding:40px 28px 30px;border-right:none;border-bottom:1px solid var(--border)}
  .hero-line{display:none}
  .hero-title .line1,.hero-title .line2{font-size:clamp(52px,12vw,90px)}
  .counters{gap:20px}
  .form-side{padding:32px 28px;border-left:none}
}
</style>
</head>
<body>

<canvas id="bg"></canvas>
<div class="aurora a1"></div>
<div class="aurora a2"></div>
<div class="aurora a3"></div>
<div class="grid-overlay"></div>
<div class="scanline"></div>
<div id="cbox"></div>

<div class="page">

  <!-- ══ LEFT HERO ══ -->
  <div class="hero">
    <div class="hero-line"></div>

    <div class="iuc-pill"><span class="pill-dot"></span>Institut Universitaire de la Côte · IUC</div>

    <div class="hero-title">
      <span class="line1">PORTAIL</span>
      <span class="line2" data-text="ASRC">ASRC</span>
      <span class="line3">IUC · DOUALA</span>
    </div>

    <p class="hero-sub">
      Classe <strong>ASRC</strong> · Administration Systèmes<br/>
      Réseaux &amp; Cloud — IUC Douala Logbessou
    </p>

    <!-- Mascot conservé -->
    <div class="mascot-zone">
      <div class="mascot-frame">
        <svg width="52" height="52" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
          <circle cx="72" cy="22" r="14" stroke="rgba(0,255,247,0.3)" stroke-width="1.5"/>
          <circle cx="72" cy="22" r="8" fill="rgba(0,255,247,0.12)"/>
          <circle cx="72" cy="22" r="3" fill="rgba(0,255,247,0.7)"/>
          <rect x="70" y="5" width="4" height="6" rx="1" fill="rgba(0,255,247,0.35)" transform="rotate(0 72 22)"/>
          <rect x="70" y="5" width="4" height="6" rx="1" fill="rgba(0,255,247,0.35)" transform="rotate(45 72 22)"/>
          <rect x="70" y="5" width="4" height="6" rx="1" fill="rgba(0,255,247,0.35)" transform="rotate(90 72 22)"/>
          <rect x="70" y="5" width="4" height="6" rx="1" fill="rgba(0,255,247,0.35)" transform="rotate(135 72 22)"/>
          <rect x="70" y="5" width="4" height="6" rx="1" fill="rgba(0,255,247,0.35)" transform="rotate(180 72 22)"/>
          <rect x="70" y="5" width="4" height="6" rx="1" fill="rgba(0,255,247,0.35)" transform="rotate(225 72 22)"/>
          <rect x="70" y="5" width="4" height="6" rx="1" fill="rgba(0,255,247,0.35)" transform="rotate(270 72 22)"/>
          <rect x="70" y="5" width="4" height="6" rx="1" fill="rgba(0,255,247,0.35)" transform="rotate(315 72 22)"/>
          <circle cx="55" cy="32" r="8" stroke="rgba(255,230,0,0.5)" stroke-width="1.5"/>
          <circle cx="55" cy="32" r="4" fill="rgba(255,230,0,0.15)"/>
          <circle cx="55" cy="32" r="1.5" fill="rgba(255,230,0,0.9)"/>
          <rect x="53.5" y="22" width="3" height="4" rx="1" fill="rgba(255,230,0,0.4)" transform="rotate(0 55 32)"/>
          <rect x="53.5" y="22" width="3" height="4" rx="1" fill="rgba(255,230,0,0.4)" transform="rotate(60 55 32)"/>
          <rect x="53.5" y="22" width="3" height="4" rx="1" fill="rgba(255,230,0,0.4)" transform="rotate(120 55 32)"/>
          <rect x="53.5" y="22" width="3" height="4" rx="1" fill="rgba(255,230,0,0.4)" transform="rotate(180 55 32)"/>
          <rect x="53.5" y="22" width="3" height="4" rx="1" fill="rgba(255,230,0,0.4)" transform="rotate(240 55 32)"/>
          <rect x="53.5" y="22" width="3" height="4" rx="1" fill="rgba(255,230,0,0.4)" transform="rotate(300 55 32)"/>
          <circle cx="42" cy="44" r="9" fill="#f5c5a3"/>
          <path d="M28 80 C28 64 56 64 56 80 L56 88 L28 88 Z" fill="rgba(0,255,247,0.7)"/>
          <path d="M28 68 L18 76" stroke="#f5c5a3" stroke-width="5" stroke-linecap="round"/>
          <path d="M56 68 L66 72" stroke="#f5c5a3" stroke-width="5" stroke-linecap="round"/>
          <path d="M66 72 L70 62" stroke="rgba(255,230,0,0.9)" stroke-width="2.5" stroke-linecap="round"/>
          <polygon points="70,58 67,64 73,64" fill="rgba(255,230,0,0.9)"/>
          <rect x="10" y="68" width="14" height="10" rx="2" fill="rgba(255,255,255,0.15)" stroke="rgba(0,255,247,0.4)" stroke-width="1"/>
          <line x1="12" y1="71" x2="22" y2="71" stroke="var(--c1)" stroke-width="1"/>
          <line x1="12" y1="74" x2="19" y2="74" stroke="rgba(0,255,247,0.5)" stroke-width="1"/>
          <ellipse cx="42" cy="36" rx="9" ry="5" fill="#2a1a0e" opacity=".8"/>
          <circle cx="39" cy="44" r="1" fill="#1a1a1a" opacity=".7"/>
          <circle cx="45" cy="44" r="1" fill="#1a1a1a" opacity=".7"/>
          <path d="M39 48 Q42 51 45 48" stroke="#1a1a1a" stroke-width="1" fill="none" stroke-linecap="round" opacity=".5"/>
        </svg>
      </div>
      <div>
        <div style="font-size:.85rem;font-weight:500;color:rgba(255,255,255,.7)">Administration Systèmes</div>
        <div style="font-size:.75rem;color:rgba(255,255,255,.3);margin-top:2px">Réseaux &amp; Cloud · 2025</div>
      </div>
    </div>

    <div class="counters">
      <div class="ctr"><span class="ctr-num" id="c1">0</span><span class="ctr-lbl">Étudiants</span></div>
      <div class="ctr"><span class="ctr-num">2025</span><span class="ctr-lbl">Promotion</span></div>
      <div class="ctr"><span class="ctr-num">AWS</span><span class="ctr-lbl">Cloud</span></div>
    </div>
  </div>

  <!-- ══ RIGHT FORM ══ -->
  <div class="form-side">
    <div class="form-inner">

      <div class="f-eyebrow">Enregistrement</div>
      <div class="f-title">Créez<br/><span>Votre Compte</span></div>
      <p class="f-desc">Rejoignez la plateforme ASRC · IUC Douala Logbessou</p>

      <?php if($message): ?>
      <div class="msg <?= $message_type ?>">
        <span><?= $message_type==='success'?'✅':'⚠️' ?></span>
        <span><?= $message ?></span>
      </div>
      <?php endif; ?>

      <form method="POST" action="" novalidate>

        <div class="field">
          <label for="nom">Nom de l'étudiant</label>
          <div class="inp-wrap">
            <span class="inp-ico">👤</span>
            <input type="text" id="nom" name="nom"
              placeholder="Votre nom complet"
              value="<?= htmlspecialchars($_POST['nom']??'') ?>"
              autocomplete="name" required/>
            <button type="button" class="toggle-p" style="opacity:0;pointer-events:none">·</button>
          </div>
          <div class="inp-line"></div>
        </div>

        <div class="field">
          <label for="password">Mot de passe</label>
          <div class="inp-wrap">
            <span class="inp-ico">🔑</span>
            <input type="password" id="password" name="password"
              placeholder="Minimum 6 caractères" required/>
            <button type="button" class="toggle-p" id="tpwd">👁</button>
          </div>
          <div class="sbar"><div class="sfill" id="sfill"></div></div>
        </div>

        <div class="btn-area">
          <button type="submit" class="btn" id="sbtn">S'ENREGISTRER</button>
        </div>
      </form>

      <div class="f-foot">
        ASRC © 2025 · IUC · <strong>Powered by AWS EC2</strong>
      </div>
    </div>
  </div>

</div>

<script>
/* ── PARTICLE CANVAS ── */
const cv=document.getElementById('bg'),ctx=cv.getContext('2d');
let W,H,pts=[];
function resize(){W=cv.width=innerWidth;H=cv.height=innerHeight}
window.addEventListener('resize',resize);resize();
const cols=['rgba(0,255,247,','rgba(255,45,120,','rgba(255,230,0,'];
for(let i=0;i<90;i++)pts.push({
  x:Math.random()*W,y:Math.random()*H,
  vx:(Math.random()-.5)*.4,vy:(Math.random()-.5)*.35,
  r:Math.random()*1.6+.4,
  col:cols[Math.floor(Math.random()*3)],
  a:Math.random()*.45+.15
});
function draw(){
  ctx.clearRect(0,0,W,H);
  for(let i=0;i<pts.length;i++){
    const p=pts[i];
    ctx.beginPath();ctx.arc(p.x,p.y,p.r,0,Math.PI*2);
    ctx.fillStyle=p.col+p.a+')';ctx.fill();
    p.x+=p.vx;p.y+=p.vy;
    if(p.x<0)p.x=W;if(p.x>W)p.x=0;
    if(p.y<0)p.y=H;if(p.y>H)p.y=0;
    for(let j=i+1;j<pts.length;j++){
      const q=pts[j],dx=p.x-q.x,dy=p.y-q.y,d=Math.sqrt(dx*dx+dy*dy);
      if(d<110){
        ctx.beginPath();ctx.moveTo(p.x,p.y);ctx.lineTo(q.x,q.y);
        ctx.strokeStyle='rgba(0,255,247,'+(0.06*(1-d/110))+')';
        ctx.lineWidth=.5;ctx.stroke();
      }
    }
  }
  requestAnimationFrame(draw);
}
draw();

/* ── COUNTER ANIM ── */
(function(){
  const el=document.getElementById('c1');
  let n=0,target=42;
  const t=setInterval(()=>{n+=1;el.textContent=n;if(n>=target)clearInterval(t);},40);
})();

/* ── PASSWORD TOGGLE ── */
document.getElementById('tpwd').addEventListener('click',function(){
  const i=document.getElementById('password'),s=i.type==='password';
  i.type=s?'text':'password';this.textContent=s?'🙈':'👁';
});

/* ── STRENGTH BAR ── */
document.getElementById('password').addEventListener('input',function(){
  const v=this.value,f=document.getElementById('sfill');
  let sc=0;
  if(v.length>=6)sc++;if(v.length>=10)sc++;
  if(/[A-Z]/.test(v))sc++;if(/[0-9]/.test(v))sc++;if(/[^a-zA-Z0-9]/.test(v))sc++;
  f.style.width=['0%','20%','40%','60%','80%','100%'][sc];
  f.style.background=['#222','#ef4444','#f97316','#eab308','#22c55e','#00fff7'][sc];
});

/* ── BTN RIPPLE + LOADING ── */
document.getElementById('sbtn').addEventListener('click',function(e){
  const r=this.getBoundingClientRect(),sp=document.createElement('span'),sz=Math.max(r.width,r.height);
  sp.style.cssText=`position:absolute;border-radius:50%;width:${sz}px;height:${sz}px;left:${e.clientX-r.left-sz/2}px;top:${e.clientY-r.top-sz/2}px;background:rgba(255,255,255,.25);transform:scale(0);animation:ripAnim .6s linear;pointer-events:none`;
  this.appendChild(sp);
  const s=document.createElement('style');s.textContent='@keyframes ripAnim{to{transform:scale(4);opacity:0}}';
  document.head.appendChild(s);
  setTimeout(()=>sp.remove(),650);
  this.classList.add('ld');
  setTimeout(()=>this.classList.remove('ld'),1800);
});

/* ── CONFETTI ON SUCCESS ── */
<?php if($message_type==='success'): ?>
(function(){
  const b=document.getElementById('cbox');
  const cs=['#00fff7','#ff2d78','#ffe600','#ffffff','#00ff88','#c084fc'];
  for(let i=0;i<100;i++){
    const p=document.createElement('div');p.className='cp';
    p.style.cssText=`left:${Math.random()*100}vw;width:${Math.random()*7+4}px;height:${Math.random()*12+6}px;background:${cs[Math.floor(Math.random()*cs.length)]};animation-duration:${Math.random()*2+1.5}s;animation-delay:${Math.random()*1}s;opacity:1`;
    b.appendChild(p);p.addEventListener('animationend',()=>p.remove());
  }
})();
<?php endif; ?>

/* ── MOUSE PARALLAX on hero ── */
document.querySelector('.hero').addEventListener('mousemove',function(e){
  const rx=(e.clientX/innerWidth-.5)*8,ry=(e.clientY/innerHeight-.5)*8;
  document.querySelector('.hero-title').style.transform=`translate(${rx*.4}px,${ry*.4}px)`;
  document.querySelectorAll('.aurora').forEach((a,i)=>{
    a.style.transform=`translate(${rx*(i+1)*2}px,${ry*(i+1)*1.5}px) scale(${1+i*.05})`;
  });
});
</script>
</body>
</html>
