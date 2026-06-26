<?php

namespace PicShare\Controllers;

use PicShare\Core\Database;
use PicShare\Services\ZipService;
use PicShare\Services\MailService;
use Stripe\Stripe;
use Stripe\Checkout\Session as StripeSession;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;

class PaymentController
{
    public function checkout(array $params): void
    {
        requireLogin();
        $album = Database::fetch('SELECT * FROM albums WHERE id = ?', [(int) $params['id']]);
        if (!$album) abort(404);
        if (!isAlbumManager($album)) abort(403);
        if ($album['payment_status'] === 'paid') {
            flash('info', 'Cet album a déjà été payé.');
            redirect('/albums/' . $album['id'] . '/manage');
        }

        view('payment.checkout', [
            'title'       => 'Télécharger l\'album',
            'album'       => $album,
            'price'       => (float) setting('album_price', 9.90),
            'stripeKey'   => setting('stripe_public_key'),
        ]);
    }

    public function createSession(array $params): void
    {
        requireLogin();
        $album = Database::fetch('SELECT * FROM albums WHERE id = ?', [(int) $params['id']]);
        if (!$album || !isAlbumManager($album)) { json(['error' => 'Accès refusé'], 403); }
        if ($album['payment_status'] === 'paid') { json(['error' => 'Déjà payé'], 400); }

        $promoCode   = null;
        $promoId     = null;
        $price       = (float) setting('album_price', 9.90);
        $finalPrice  = $price;
        $promoStr    = strtoupper(trim($_POST['promo_code'] ?? ''));

        if ($promoStr) {
            $promoCode = Database::fetch(
                'SELECT * FROM promo_codes WHERE code = ? AND is_active = 1
                 AND (expires_at IS NULL OR expires_at > NOW())
                 AND (max_uses IS NULL OR used_count < max_uses)',
                [$promoStr]
            );
            if ($promoCode) {
                $promoId = $promoCode['id'];
                if ($promoCode['discount_percent']) {
                    $finalPrice = round($price * (1 - $promoCode['discount_percent'] / 100), 2);
                } elseif ($promoCode['discount_fixed']) {
                    $finalPrice = max(0, round($price - $promoCode['discount_fixed'], 2));
                }
            }
        }

        if ($finalPrice <= 0) {
            // Free via promo code — skip Stripe
            $this->markPaid($album, $promoId, $price, 0, null, null);
            json(['free' => true, 'redirect' => BASE_URL . '/albums/' . $album['id'] . '/manage']);
        }

        Stripe::setApiKey(setting('stripe_secret_key'));
        $user = currentUser();
        $event = Database::fetch('SELECT title FROM events WHERE id = ?', [$album['event_id']]);

        $session = StripeSession::create([
            'payment_method_types' => ['card'],
            'line_items'           => [[
                'price_data' => [
                    'currency'     => 'eur',
                    'unit_amount'  => (int) round($finalPrice * 100),
                    'product_data' => [
                        'name'        => 'Album "' . $album['title'] . '"',
                        'description' => 'Événement : ' . ($event['title'] ?? ''),
                    ],
                ],
                'quantity' => 1,
            ]],
            'mode'              => 'payment',
            'customer_email'    => $user['email'],
            'success_url'       => BASE_URL . '/payment/success?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'        => BASE_URL . '/albums/' . $album['id'] . '/checkout',
            'metadata'          => [
                'album_id'      => $album['id'],
                'user_id'       => $user['id'],
                'promo_id'      => $promoId ?? '',
                'amount_original' => $price,
                'amount_paid'   => $finalPrice,
            ],
        ]);

        // Pre-register payment
        Database::insert(
            'INSERT INTO payments (album_id, user_id, promo_code_id, stripe_session_id, amount_original, amount_paid, status)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$album['id'], $user['id'], $promoId, $session->id, $price, $finalPrice, 'pending']
        );

        json(['url' => $session->url]);
    }

    public function success(array $params): void
    {
        $sessionId = $_GET['session_id'] ?? '';
        if (!$sessionId) abort(400);

        Stripe::setApiKey(setting('stripe_secret_key'));
        try {
            $session = StripeSession::retrieve($sessionId);
        } catch (\Exception $e) {
            abort(400);
        }

        if ($session->payment_status !== 'paid') {
            flash('error', 'Paiement non confirmé. Contactez le support.');
            redirect('/dashboard');
        }

        $albumId = (int) $session->metadata->album_id;
        $album   = Database::fetch('SELECT * FROM albums WHERE id = ?', [$albumId]);

        if ($album && $album['payment_status'] !== 'paid') {
            $meta = $session->metadata;
            $this->markPaid($album, $meta->promo_id ?: null, $meta->amount_original, $meta->amount_paid, $session->payment_intent, $session->id);
        }

        flash('success', 'Paiement confirmé ! Votre lien de téléchargement est prêt.');
        redirect('/albums/' . $albumId . '/manage');
    }

    public function webhook(array $params): void
    {
        $payload = @file_get_contents('php://input');
        $sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
        $secret = setting('stripe_webhook_secret');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $secret);
        } catch (\UnexpectedValueException | SignatureVerificationException $e) {
            http_response_code(400);
            exit;
        }

        if ($event->type === 'checkout.session.completed') {
            $session  = $event->data->object;
            $albumId  = (int) $session->metadata->album_id;
            $album    = Database::fetch('SELECT * FROM albums WHERE id = ?', [$albumId]);

            if ($album && $album['payment_status'] !== 'paid') {
                $meta = $session->metadata;
                $this->markPaid($album, $meta->promo_id ?: null, $meta->amount_original, $meta->amount_paid, $session->payment_intent, $session->id);
            }

            Database::execute(
                "UPDATE payments SET status = 'completed', stripe_payment_intent_id = ? WHERE stripe_session_id = ?",
                [$session->payment_intent, $session->id]
            );
        }

        http_response_code(200);
        echo 'ok';
    }

    public function validatePromo(array $params): void
    {
        $code  = strtoupper(trim($_POST['code'] ?? ''));
        $price = (float) setting('album_price', 9.90);

        $promo = Database::fetch(
            'SELECT * FROM promo_codes WHERE code = ? AND is_active = 1
             AND (expires_at IS NULL OR expires_at > NOW())
             AND (max_uses IS NULL OR used_count < max_uses)',
            [$code]
        );

        if (!$promo) { json(['valid' => false, 'message' => 'Code invalide ou expiré.']); }

        $final = $price;
        $label = '';
        if ($promo['discount_percent']) {
            $final = round($price * (1 - $promo['discount_percent'] / 100), 2);
            $label = '-' . $promo['discount_percent'] . '%';
        } elseif ($promo['discount_fixed']) {
            $final = max(0, round($price - $promo['discount_fixed'], 2));
            $label = '-' . number_format($promo['discount_fixed'], 2) . '€';
        }

        json([
            'valid'       => true,
            'label'       => $label,
            'price_orig'  => number_format($price, 2),
            'price_final' => number_format($final, 2),
            'message'     => "Code appliqué : {$label}",
        ]);
    }

    private function markPaid(array $album, ?int $promoId, float $priceOrig, float $pricePaid, ?string $paymentIntent, ?string $sessionId): void
    {
        $downloadToken  = generateToken(32);
        $downloadExpiry = date('Y-m-d H:i:s', strtotime('+' . ARCHIVE_LIFETIME_DAYS . ' days'));
        $deleteScheduled = $downloadExpiry;

        Database::execute(
            'UPDATE albums SET payment_status = ?, payment_date = NOW(), download_token = ?, download_expires_at = ?, delete_scheduled_at = ?, is_ended = 1 WHERE id = ?',
            ['paid', $downloadToken, $downloadExpiry, $deleteScheduled, $album['id']]
        );

        // Update promo code usage
        if ($promoId) {
            Database::execute('UPDATE promo_codes SET used_count = used_count + 1 WHERE id = ?', [$promoId]);
        }

        // Update payment record
        if ($sessionId) {
            Database::execute(
                "UPDATE payments SET status = 'completed', stripe_payment_intent_id = ? WHERE stripe_session_id = ?",
                [$paymentIntent, $sessionId]
            );
        } else {
            Database::insert(
                'INSERT INTO payments (album_id, promo_code_id, amount_original, amount_paid, status) VALUES (?, ?, ?, ?, ?)',
                [$album['id'], $promoId, $priceOrig, $pricePaid, 'completed']
            );
        }

        // Generate archive in background (we skip async for simplicity, generate on first download)
        // Send email to album owner
        $event = Database::fetch(
            'SELECT e.*, u.email, u.name FROM events e JOIN users u ON u.id = e.owner_id WHERE e.id = ?',
            [$album['event_id']]
        );
        if ($event) {
            $downloadUrl = BASE_URL . '/download/' . $downloadToken;
            MailService::sendDownloadReady($event['email'], $event['name'], $album['title'], $downloadUrl);
        }
    }
}
