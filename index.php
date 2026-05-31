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
                $message = "🎉 Inscription réussie ! Bienvenue parmi nous, $nom.";
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
  <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <style>
    :root {
      --neon: #00ffff;
      --purple: #9d00ff;
      --dark: #0d001a;
    }

    * { margin:0; padding:0; box-sizing:border-box; }
    body {
      font-family: 'Inter', sans-serif;
      background: var(--dark);
      color: white;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
      position: relative;
    }

    /* Effet de grille futuriste */
    body::before {
      content: '';
      position: absolute;
      top: 0; left: 0; right: 0; bottom: 0;
      background: linear-gradient(rgba(157, 0, 255, 0.03) 1px, transparent 1px),
                  linear-gradient(90deg, rgba(0, 255, 255, 0.03) 1px, transparent 1px);
      background-size: 50px 50px;
      z-index: 1;
      animation: gridMove 40s linear infinite;
    }

    @keyframes gridMove {
      0% { background-position: 0 0; }
      100% { background-position: 100px 100px; }
    }

    .card {
      background: rgba(20, 10, 45, 0.85);
      border: 2px solid var(--neon);
      border-radius: 20px;
      padding: 50px 40px;
      width: 100%;
      max-width: 480px;
      box-shadow: 0 0 60px rgba(0, 255, 255, 0.3),
                  inset 0 0 40px rgba(157, 0, 255, 0.1);
      z-index: 10;
      animation: cardGlow 4s ease-in-out infinite alternate;
    }

    @keyframes cardGlow {
      from { box-shadow: 0 0 40px rgba(0, 255, 255, 0.3); }
      to { box-shadow: 0 0 80px rgba(157, 0, 255, 0.5); }
    }

    .logo {
      text-align: center;
      margin-bottom: 20px;
    }

    .logo h1 {
      font-family: 'Orbitron', sans-serif;
      font-size: 2.8rem;
      background: linear-gradient(90deg, var(--neon), var(--purple));
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      text-shadow: 0 0 30px var(--neon);
    }

    h2 {
      text-align: center;
      margin-bottom: 30px;
      color: #ccc;
      font-size: 1.4rem;
    }

    .input-group {
      margin-bottom: 25px;
    }

    label {
      display: block;
      margin-bottom: 8px;
      color: var(--neon);
      font-size: 0.95rem;
    }

    input {
      width: 100%;
      padding: 16px 20px;
      background: rgba(255,255,255,0.05);
      border: 1px solid var(--neon);
      border-radius: 12px;
      color: white;
      font-size: 1.1rem;
    }

    input:focus {
      outline: none;
      border-color: var(--purple);
      box-shadow: 0 0 15px var(--purple);
    }

    .btn {
      width: 100%;
      padding: 18px;
      background: linear-gradient(90deg, var(--neon), var(--purple));
      color: black;
      font-weight: bold;
      font-size: 1.2rem;
      border: none;
      border-radius: 50px;
      cursor: pointer;
      margin-top: 10px;
      transition: all 0.4s;
    }

    .btn:hover {
      transform: scale(1.05);
      box-shadow: 0 0 30px var(--neon);
    }

    .message {
      padding: 15px;
      margin: 20px 0;
      border-radius: 12px;
      text-align: center;
      font-weight: 500;
    }

    .success { background: rgba(0, 255, 100, 0.2); color: #00ff88; border: 1px solid #00ff88; }
    .error   { background: rgba(255, 80, 80, 0.2); color: #ff6666; border: 1px solid #ff6666; }
  </style>
</head>
<body>

  <div class="card">
    <div class="logo">
      <h1>ASRC</h1>
    </div>
    <h2>Inscription Étudiant</h2>

    <?php if ($message): ?>
      <div class="message <?= $success ? 'success' : 'error' ?>">
        <?= htmlspecialchars($message) ?>
      </div>
    <?php endif; ?>

    <form method="POST">
      <div class="input-group">
        <label>Nom Complet</label>
        <input type="text" name="nom" placeholder="Entrez votre nom complet" required>
      </div>

      <div class="input-group">
        <label>Mot de Passe</label>
        <input type="password" name="mot_de_passe" placeholder="Créez un mot de passe" required>
      </div>

      <button type="submit" class="btn">S'INSCRIRE</button>
    </form>
  </div>

</body>
</html>