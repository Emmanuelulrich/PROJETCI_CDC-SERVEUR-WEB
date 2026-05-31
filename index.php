<?php
// ===================== CONNEXION BASE DE DONNÉES =====================
$host     = "localhost";
$dbname   = "asrc_db";
$username = "asrc_user";
$password = "Emmanuel@51";

$message  = "";
$success  = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nom = trim($_POST["nom"] ?? "");
    $mdp = trim($_POST["mot_de_passe"] ?? "");

    if ($nom === "" || $mdp === "") {
        $message = "❌ Veuillez remplir tous les champs.";
    } elseif (strlen($mdp) < 6) {
        $message = "❌ Le mot de passe doit contenir au moins 6 caractères.";
    } else {
        try {
            $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $check = $pdo->prepare("SELECT id FROM etudiants WHERE nom = :nom");
            $check->execute([":nom" => $nom]);

            if ($check->rowCount() > 0) {
                $message = "⚠️ Cet étudiant est déjà enregistré.";
            } else {
                $mdp_hash = password_hash($mdp, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("INSERT INTO etudiants (nom, mot_de_passe) VALUES (:nom, :mdp)");
                $stmt->execute([":nom" => $nom, ":mdp" => $mdp_hash]);
                
                $success = true;
                $message = "🎉 Félicitations $nom ! Vous avez été enregistré avec succès.";
            }
        } catch (PDOException $e) {
            $message = "❌ Erreur de connexion à la base de données.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ASRC IUC - Inscription</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Inter:wght@400;500&display=swap" rel="stylesheet">
  <style>
    :root {
      --primary: #00d4ff;
      --accent: #ff00c8;
      --dark: #0a0a1f;
      --glass: rgba(15, 15, 40, 0.85);
    }

    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
      font-family: 'Inter', sans-serif;
      background: linear-gradient(135deg, #0a0a1f 0%, #1a0033 100%);
      color: #fff;
      min-height: 100vh;
      overflow: hidden;
      display: flex;
      align-items: center;
      justify-content: center;
      position: relative;
    }

    /* Particules de fond */
    .particles {
      position: absolute;
      top: 0; left: 0; width: 100%; height: 100%;
      pointer-events: none;
      z-index: 1;
    }

    .particle {
      position: absolute;
      background: var(--primary);
      border-radius: 50%;
      box-shadow: 0 0 15px var(--primary);
      animation: floatParticle linear infinite;
      opacity: 0.6;
    }

    @keyframes floatParticle {
      0% { transform: translateY(100vh) scale(0.8); }
      100% { transform: translateY(-100px) scale(0.2); }
    }

    .container {
      background: var(--glass);
      backdrop-filter: blur(20px);
      border: 1px solid rgba(0, 212, 255, 0.3);
      border-radius: 24px;
      padding: 50px 45px;
      width: 100%;
      max-width: 460px;
      box-shadow: 0 25px 70px rgba(0, 0, 0, 0.6);
      z-index: 10;
      animation: cardPop 1s ease;
    }

    @keyframes cardPop {
      from { opacity: 0; transform: scale(0.7) translateY(50px); }
      to { opacity: 1; transform: scale(1) translateY(0); }
    }

    h1 {
      text-align: center;
      font-family: 'Poppins', sans-serif;
      font-size: 2.8rem;
      margin-bottom: 10px;
      background: linear-gradient(90deg, #fff, var(--primary));
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }

    .subtitle {
      text-align: center;
      color: #aaa;
      margin-bottom: 35px;
      font-size: 1.1rem;
    }

    .form-group {
      margin-bottom: 22px;
    }

    label {
      display: block;
      margin-bottom: 8px;
      color: var(--primary);
      font-weight: 600;
    }

    input {
      width: 100%;
      padding: 16px 20px;
      background: rgba(255,255,255,0.08);
      border: 1px solid rgba(0, 212, 255, 0.4);
      border-radius: 14px;
      color: white;
      font-size: 1.05rem;
      transition: all 0.3s ease;
    }

    input:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 0 4px rgba(0, 212, 255, 0.2);
      transform: translateY(-3px);
    }

    .btn {
      width: 100%;
      padding: 18px;
      margin-top: 10px;
      background: linear-gradient(90deg, var(--primary), #0099cc);
      color: white;
      border: none;
      border-radius: 50px;
      font-size: 1.2rem;
      font-weight: 700;
      cursor: pointer;
      transition: all 0.4s ease;
      position: relative;
      overflow: hidden;
    }

    .btn:hover {
      transform: translateY(-5px);
      box-shadow: 0 15px 35px rgba(0, 212, 255, 0.5);
    }

    .message {
      padding: 16px;
      margin: 20px 0;
      border-radius: 12px;
      text-align: center;
      font-weight: 500;
      animation: msgAnim 0.6s ease;
    }

    .success { background: rgba(0, 255, 100, 0.15); color: #00ff88; border: 1px solid #00ff88; }
    .error   { background: rgba(255, 50, 50, 0.15); color: #ff6666; border: 1px solid #ff6666; }

    @keyframes msgAnim {
      from { opacity: 0; transform: translateY(-20px); }
      to { opacity: 1; transform: translateY(0); }
    }
  </style>
</head>
<body>

  <div class="particles" id="particles"></div>

  <div class="container">
    <h1>ASRC IUC</h1>
    <p class="subtitle">Inscription Étudiant</p>

    <?php if ($message): ?>
      <div class="message <?= $success ? 'success' : 'error' ?>">
        <?= htmlspecialchars($message) ?>
      </div>
    <?php endif; ?>

    <form method="POST">
      <div class="form-group">
        <label>Nom Complet</label>
        <input type="text" name="nom" placeholder="Votre nom et prénom" required>
      </div>

      <div class="form-group">
        <label>Mot de Passe</label>
        <input type="password" name="mot_de_passe" placeholder="••••••••" required>
      </div>

      <button type="submit" class="btn">S'INSCRIRE</button>
    </form>
  </div>

  <script>
    // Création des particules animées
    const particlesContainer = document.getElementById('particles');
    for (let i = 0; i < 45; i++) {
      const p = document.createElement('div');
      p.className = 'particle';
      const size = Math.random() * 4 + 2;
      p.style.width = `${size}px`;
      p.style.height = `${size}px`;
      p.style.left = `${Math.random() * 100}vw`;
      p.style.animationDuration = `${Math.random() * 8 + 6}s`;
      p.style.animationDelay = `-${Math.random() * 10}s`;
      p.style.opacity = Math.random() * 0.7 + 0.3;
      particlesContainer.appendChild(p);
    }
  </script>
</body>
</html>