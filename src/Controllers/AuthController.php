<?php

namespace PicShare\Controllers;

use PicShare\Core\Database;
use PicShare\Core\Session;
use PicShare\Services\MailService;

class AuthController
{
    public function showLogin(array $params): void
    {
        if (isLoggedIn()) redirect('/dashboard');
        view('auth.login', ['title' => 'Connexion']);
    }

    public function postLogin(array $params): void
    {
        $email = trim(strtolower($_POST['email'] ?? ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Adresse email invalide.');
            redirect('/login');
        }

        $user = Database::fetch('SELECT * FROM users WHERE email = ? AND is_active = 1', [$email]);
        if (!$user) {
            // Fake delay to prevent enumeration
            usleep(300000);
            flash('error', 'Aucun compte associé à cet email.');
            redirect('/login');
        }

        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expires = date('Y-m-d H:i:s', time() + OTP_LIFETIME);

        Database::execute(
            'UPDATE users SET otp_code = ?, otp_expires_at = ? WHERE id = ?',
            [password_hash($otp, PASSWORD_DEFAULT), $expires, $user['id']]
        );

        $smtpConfigured = !empty(setting('smtp_host')) && !empty(setting('smtp_user'));
        if ($smtpConfigured) {
            MailService::sendOtp($user['email'], $user['name'], $otp);
            $msg = 'Un code à 6 chiffres a été envoyé à ' . $email;
        } else {
            // SMTP not configured — log OTP so admin can bootstrap the system
            error_log('[PicShare OTP] ' . $email . ' → code: ' . $otp);
            $msg = 'SMTP non configuré — le code OTP a été écrit dans le log PHP (error_log).';
        }

        Session::set('otp_user_id', $user['id']);
        Session::set('otp_email', $email);
        flash('success', $msg);
        redirect('/login/verify');
    }

    public function showVerify(array $params): void
    {
        if (!Session::has('otp_user_id')) redirect('/login');
        view('auth.verify', [
            'title' => 'Vérification',
            'email' => Session::get('otp_email'),
        ]);
    }

    public function postVerify(array $params): void
    {
        $userId = Session::get('otp_user_id');
        if (!$userId) redirect('/login');

        $code = trim($_POST['otp'] ?? '');
        $user = Database::fetch('SELECT * FROM users WHERE id = ? AND is_active = 1', [$userId]);

        if (!$user || !$user['otp_code'] || !$user['otp_expires_at']) {
            flash('error', 'Session invalide.');
            redirect('/login');
        }

        if (strtotime($user['otp_expires_at']) < time()) {
            flash('error', 'Le code a expiré. Veuillez recommencer.');
            redirect('/login');
        }

        if (!password_verify($code, $user['otp_code'])) {
            flash('error', 'Code incorrect.');
            redirect('/login/verify');
        }

        Database::execute(
            'UPDATE users SET otp_code = NULL, otp_expires_at = NULL, last_login_at = NOW() WHERE id = ?',
            [$user['id']]
        );

        Session::remove('otp_user_id');
        Session::remove('otp_email');
        Session::set('user_id', $user['id']);

        flash('success', 'Bienvenue, ' . $user['name'] . ' !');
        redirect('/dashboard');
    }

    public function showRegister(array $params): void
    {
        if (isLoggedIn()) redirect('/dashboard');
        view('auth.register', ['title' => 'Créer un compte']);
    }

    public function postRegister(array $params): void
    {
        $name  = trim($_POST['name'] ?? '');
        $email = trim(strtolower($_POST['email'] ?? ''));

        if (!$name || strlen($name) < 2) {
            flash('error', 'Le prénom/nom est requis (min. 2 caractères).');
            redirect('/register');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Adresse email invalide.');
            redirect('/register');
        }

        $existing = Database::fetch('SELECT id FROM users WHERE email = ?', [$email]);
        if ($existing) {
            flash('error', 'Cette adresse email est déjà utilisée.');
            redirect('/register');
        }

        // First user becomes admin
        $count = Database::fetch('SELECT COUNT(*) as cnt FROM users');
        $role = ($count['cnt'] == 0) ? 'admin' : 'user';

        Database::insert(
            'INSERT INTO users (email, name, role) VALUES (?, ?, ?)',
            [$email, $name, $role]
        );

        flash('success', 'Compte créé ! Connectez-vous maintenant.');
        redirect('/login');
    }

    public function logout(array $params): void
    {
        Session::destroy();
        redirect('/');
    }
}
