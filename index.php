<?php
// Connexion à MySQL
$host     = "localhost";
$dbname   = "asrc_db";
$username = "asrc_user";
$password = "Emmanuel@51";

$message  = "";
$success  = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nom        = trim($_POST["nom"] ?? "");
    $mdp        = trim($_POST["mot_de_passe"] ?? "");

    if ($nom === "" || $mdp === "") {
        $message = "Veuillez remplir tous les champs.";
    } else {
        try {
            $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $check = $pdo->prepare("SELECT id FROM etudiants WHERE nom = :nom");
            $check->execute([":nom" => $nom]);

            if ($check->rowCount() > 0) {
                $message = "Cet étudiant est déjà enregistré.";
            } else {
                $mdp_hash = password_hash($mdp, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("INSERT INTO etudiants (nom, mot_de_passe) VALUES (:nom, :mdp)");
                $stmt->execute([":nom" => $nom, ":mdp" => $mdp_hash]);
                $success = true;
                $message = "✅ Vous avez bien été enregistré(e) ! Bienvenue, $nom.";
            }
        } catch (PDOException $e) {
            $message = "Erreur de connexion à la base de données.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Classe ASRC — IUC</title>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=Rajdhani:wght@400;600;700&family=Montserrat:wght@300;400;600&display=swap" rel="stylesheet"/>
  <style>
    *, *::before, *::after { 
      box-sizing: border-box; 
      margin: 0; 
      padding: 0; 
    }

    :root {
      --primary: #00f5ff;           /* Nouvelle couleur cyan électrique (remplace l'orange) */
      --primary-dark: #00b8c0;
      --glow: #7affff;
      --dark: #0a0a14;
      --glass: rgba(10, 10, 20, 0.75);
      --accent: #ff00aa;
    }

    html, body { 
      width:100%; 
      min-height:100%; 
      background: var(--dark); 
      color: #eee; 
      font-family:'Montserrat',sans-serif; 
      overflow-x: hidden;
    }

    /* ===================== BACKGROUND ANIMÉ SPECTACULAIRE ===================== */
    .bg-layer { 
      position:fixed; 
      inset:0; 
      z-index:0;
      background: 
        radial-gradient(circle at 30% 20%, rgba(0, 245, 255, 0.12) 0%, transparent 50%),
        radial-gradient(circle at 70% 80%, rgba(255, 0, 170, 0.1) 0%, transparent 50%),
        url('data:image/jpeg;base64,...') center/cover; /* ton image base64 reste intacte */
      animation: bgShift 35s linear infinite;
    }

    @keyframes bgShift {
      0% { transform: translate(0, 0) rotate(0deg); }
      100% { transform: translate(30px, -30px) rotate(8deg); }
    }

    /* ===================== ANIMATIONS AMÉLIORÉES ===================== */
    .badge {
      display: inline-block;
      padding: 8px 24px;
      background: rgba(0, 245, 255, 0.1);
      border: 1px solid var(--primary);
      border-radius: 50px;
      color: var(--primary);
      font-weight: 600;
      letter-spacing: 2px;
      animation: badgeGlow 3s ease-in-out infinite alternate;
    }

    @keyframes badgeGlow {
      from { box-shadow: 0 0 15px var(--glow); }
      to { box-shadow: 0 0 35px var(--glow), 0 0 55px var(--primary); }
    }

    .welcome-label {
      font-size: 1.35rem;
      color: var(--accent);
      margin: 15px 0;
      text-shadow: 0 0 20px var(--accent);
      animation: pulseText 2.5s infinite;
    }

    @keyframes pulseText {
      0%, 100% { opacity: 0.85; transform: scale(1); }
      50% { opacity: 1; transform: scale(1.08); }
    }

    h1 {
      font-family: 'Playfair Display', serif;
      font-size: 4.2rem;
      line-height: 1.1;
      margin: 10px 0 15px;
      background: linear-gradient(90deg, #fff, var(--primary), #fff);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      animation: titleEnter 1.8s ease forwards;
    }

    @keyframes titleEnter {
      from { opacity: 0; transform: translateY(60px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .subtitle {
      font-size: 1.35rem;
      opacity: 0.9;
      animation: fadeInUp 1.4s ease 0.6s backwards;
    }

    @keyframes fadeInUp {
      from { opacity: 0; transform: translateY(40px); }
      to { opacity: 1; transform: translateY(0); }
    }

    /* ===================== FORMULAIRE SPECTACULAIRE ===================== */
    .msg-box {
      padding: 16px 24px;
      border-radius: 12px;
      margin: 20px auto;
      max-width: 460px;
      font-weight: 500;
      animation: msgPop 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275);
      box-shadow: 0 10px 30px rgba(0, 245, 255, 0.2);
    }

    @keyframes msgPop {
      0% { transform: scale(0.6) translateY(30px); opacity: 0; }
      100% { transform: scale(1) translateY(0); opacity: 1; }
    }

    .form {
      background: var(--glass);
      backdrop-filter: blur(16px);
      border: 1px solid rgba(0, 245, 255, 0.2);
      border-radius: 20px;
      padding: 35px 40px;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.6);
      animation: formFloat 6s ease-in-out infinite;
    }

    @keyframes formFloat {
      0%, 100% { transform: translateY(0px); }
      50% { transform: translateY(-12px); }
    }

    .field {
      margin-bottom: 22px;
      position: relative;
    }

    .field label {
      display: block;
      margin-bottom: 8px;
      color: var(--primary);
      font-weight: 600;
      font-size: 0.95rem;
      text-transform: uppercase;
      letter-spacing: 1px;
    }

    input {
      width: 100%;
      padding: 16px 20px;
      background: rgba(255,255,255,0.06);
      border: 1px solid rgba(0, 245, 255, 0.3);
      border-radius: 12px;
      color: white;
      font-size: 1.05rem;
      transition: all 0.4s ease;
    }

    input:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 0 4px rgba(0, 245, 255, 0.15);
      transform: translateY(-2px);
    }

    .btn {
      width: 100%;
      padding: 18px;
      background: linear-gradient(90deg, var(--primary), var(--primary-dark));
      color: white;
      border: none;
      border-radius: 50px;
      font-size: 1.15rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 1.5px;
      cursor: pointer;
      position: relative;
      overflow: hidden;
      box-shadow: 0 10px 30px rgba(0, 245, 255, 0.4);
      transition: all 0.4s ease;
    }

    .btn:hover {
      transform: translateY(-6px) scale(1.04);
      box-shadow: 0 20px 45px rgba(0, 245, 255, 0.6);
    }

    .btn::after {
      content: '';
      position: absolute;
      top: -50%;
      left: -50%;
      width: 40%;
      height: 200%;
      background: linear-gradient(120deg, transparent, rgba(255,255,255,0.35), transparent);
      transform: skewX(-25deg);
      animation: shimmer 4s infinite linear;
    }

    @keyframes shimmer {
      0% { transform: translateX(-200%) skewX(-25deg); }
      100% { transform: translateX(400%) skewX(-25deg); }
    }

    .divider {
      margin: 30px 0;
      text-align: center;
      color: var(--primary);
      font-size: 1.1rem;
      position: relative;
    }

    .divider::before {
      content: '';
      position: absolute;
      top: 50%;
      left: 0;
      right: 0;
      height: 1px;
      background: linear-gradient(90deg, transparent, var(--primary), transparent);
    }

    /* ===================== EMBERS PLUS SPECTACULAIRES ===================== */
    .ember {
      position: absolute;
      background: var(--glow);
      border-radius: 50%;
      box-shadow: 0 0 18px var(--glow);
      animation: emberFloat linear infinite;
      z-index: 1;
    }

    @keyframes emberFloat {
      0% { transform: translateY(100vh) scale(1); opacity: 0.9; }
      100% { transform: translateY(-120px) scale(0.4); opacity: 0; }
    }
  </style>
</head>
<body>
  <!-- Le reste du code HTML reste EXACTEMENT IDENTIQUE -->
  <div id="embers" style="position:fixed;inset:0;z-index:1;pointer-events:none;"></div>
  
  <!-- ... tout le contenu que tu avais déjà ... -->

  <script>
    const container = document.getElementById('embers');
    for (let i = 0; i < 28; i++) {
      const e = document.createElement('div');
      e.className = 'ember';
      const size = Math.random() * 3.5 + 1.8;
      e.style.cssText = `left:${Math.random()*100}vw;width:${size}px;height:${size}px;--dx:${(Math.random()-0.5)*140}px;animation-duration:${Math.random()*7+6}s;animation-delay:${Math.random()*10}s;opacity:0;`;
      container.appendChild(e);
    }
  </script>
</body>
</html>