<?php
/**
 * ShopMax — Admin Login
 */
session_start();
if (isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit;
}

require_once __DIR__ . '/../DB/db.php';
require_once __DIR__ . '/../includes/config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = "Veuillez remplir tous les champs.";
    } else {
        $pdo = getPDO();
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = :u AND actif = 1 LIMIT 1");
        $stmt->execute(['u' => $username]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password_hash'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_role'] = $admin['role'];
            $_SESSION['admin_permissions'] = $admin['permissions'] ? json_decode($admin['permissions'], true) : [];

            // Update last login
            $pdo->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?")->execute([$admin['id']]);

            header("Location: index.php");
            exit;
        } else {
            $error = "Identifiants incorrects.";
        }
    }
}

$pageTitle = 'Connexion Administration';
require_once __DIR__ . '/includes/admin_header.php';
?>

<div class="login-page">
    <div class="login-container">
        <div class="login-left">
            <div class="login-logo">
                <i class="fas fa-shopping-basket"></i>
                <span>ShopMax</span>
            </div>
            
            <div class="login-left-content">
                <h1>Bienvenue<br>chez ShopMax</h1>
                <p>Connectez-vous à votre compte et gérez votre boutique facilement.</p>
            </div>
            
            <div class="login-illustration">
                <img src="../assets/images/login-illustration.png" alt="ShopMax Illustration">
            </div>
        </div>
        
        <div class="login-right">
            <div class="login-form-content">
                <h2>Se connecter</h2>
                <p class="subtitle">Entrez vos informations pour accéder à votre compte</p>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= e($error) ?></div>
                <?php endif; ?>

                <form method="POST" action="login.php">
                    <div class="form-group">
                        <label class="form-label">Nom d'utilisateur</label>
                        <div class="input-group">
                            <i class="far fa-user"></i>
                            <input type="text" name="username" class="form-control" placeholder="Admin" required autofocus>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Mot de passe</label>
                        <div class="input-group">
                            <i class="fas fa-lock"></i>
                            <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                            <i class="far fa-eye-slash toggle-pwd"></i>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-login">
                        Se connecter
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>