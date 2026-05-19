<?php
/**
 * Génère un e-mail HTML pour la confirmation de commande
 */
function generate_order_confirmation_email(array $orderData): string
{
    $orderId = (int) ($orderData['order_id'] ?? 0);
    $customerName = htmlspecialchars((string) ($orderData['customer_name'] ?? 'Client'));
    $customerEmail = htmlspecialchars((string) ($orderData['customer_email'] ?? ''));
    $formattedTotal = htmlspecialchars((string) ($orderData['formatted_total'] ?? '0 FCFA'));
    $articlesText = htmlspecialchars((string) ($orderData['articles_text'] ?? ''));
    $address = htmlspecialchars((string) ($orderData['address'] ?? ''));
    $city = htmlspecialchars((string) ($orderData['city'] ?? ''));
    $profilePhone = htmlspecialchars((string) ($orderData['profile_phone'] ?? ''));
    $payerPhone = htmlspecialchars((string) ($orderData['payer_phone'] ?? ''));
    $orderDate = date('d/m/Y H:i');

    return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmation de Commande - Dribbleur Store</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Rajdhani', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #020811 0%, #0a1628 100%);
            color: #e0f7ff;
            line-height: 1.6;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background: rgba(0, 20, 40, 0.95);
            border: 1px solid rgba(0, 207, 255, 0.2);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
        }
        .email-header {
            background: linear-gradient(135deg, #00ff88 0%, #00cfff 100%);
            padding: 30px 20px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .email-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: repeating-linear-gradient(
                90deg,
                transparent,
                transparent 2px,
                rgba(0, 0, 0, 0.05) 2px,
                rgba(0, 0, 0, 0.05) 4px
            );
            pointer-events: none;
        }
        .email-header h1 {
            font-size: 28px;
            font-weight: 900;
            letter-spacing: 2px;
            color: #001a0d;
            position: relative;
            z-index: 1;
            text-transform: uppercase;
        }
        .email-header p {
            color: rgba(0, 26, 13, 0.7);
            font-size: 14px;
            margin-top: 8px;
            position: relative;
            z-index: 1;
            letter-spacing: 1px;
        }
        .email-content {
            padding: 40px 30px;
        }
        .greeting {
            font-size: 16px;
            margin-bottom: 24px;
            color: #e0f7ff;
        }
        .order-number {
            background: rgba(0, 255, 136, 0.1);
            border: 2px solid #00ff88;
            border-radius: 12px;
            padding: 20px;
            margin: 24px 0;
            text-align: center;
            position: relative;
        }
        .order-number::before {
            content: '✓';
            display: block;
            font-size: 32px;
            color: #00ff88;
            margin-bottom: 8px;
        }
        .order-number-label {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.5);
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 8px;
        }
        .order-number-value {
            font-size: 36px;
            font-weight: 900;
            color: #00ff88;
            font-family: 'Courier New', monospace;
            letter-spacing: 2px;
        }
        .section {
            margin: 28px 0;
            padding: 20px;
            background: rgba(0, 207, 255, 0.05);
            border-left: 3px solid #00cfff;
            border-radius: 8px;
        }
        .section-title {
            font-size: 14px;
            font-weight: 700;
            color: #00cfff;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 12px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid rgba(0, 207, 255, 0.1);
            font-size: 14px;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            color: rgba(255, 255, 255, 0.6);
            font-weight: 600;
        }
        .info-value {
            color: #e0f7ff;
            text-align: right;
            font-weight: 500;
        }
        .articles-list {
            background: rgba(0, 255, 136, 0.05);
            border: 1px solid rgba(0, 255, 136, 0.2);
            border-radius: 8px;
            padding: 16px;
            margin: 12px 0;
        }
        .article-item {
            font-size: 14px;
            color: #e0f7ff;
            padding: 8px 0;
            border-bottom: 1px solid rgba(0, 255, 136, 0.1);
        }
        .article-item:last-child {
            border-bottom: none;
        }
        .article-item::before {
            content: '▸ ';
            color: #00ff88;
            font-weight: 700;
            margin-right: 6px;
        }
        .total-section {
            background: linear-gradient(135deg, rgba(0, 255, 136, 0.15) 0%, rgba(0, 207, 255, 0.15) 100%);
            border: 2px solid rgba(0, 255, 136, 0.3);
            border-radius: 12px;
            padding: 20px;
            margin: 24px 0;
            text-align: center;
        }
        .total-label {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.6);
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 8px;
        }
        .total-amount {
            font-size: 32px;
            font-weight: 900;
            color: #00ff88;
            font-family: 'Courier New', monospace;
        }
        .cta-section {
            text-align: center;
            margin: 32px 0;
            padding: 24px;
            background: rgba(0, 207, 255, 0.08);
            border-radius: 12px;
        }
        .cta-text {
            font-size: 14px;
            color: #e0f7ff;
            margin-bottom: 16px;
        }
        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #00ff88 0%, #00b86b 100%);
            color: #001a0d;
            padding: 14px 32px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 700;
            font-size: 14px;
            letter-spacing: 1px;
            text-transform: uppercase;
            box-shadow: 0 8px 24px rgba(0, 255, 136, 0.3);
            transition: all 0.3s ease;
        }
        .cta-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 32px rgba(0, 255, 136, 0.5);
        }
        .email-footer {
            background: rgba(0, 0, 0, 0.3);
            padding: 24px;
            text-align: center;
            border-top: 1px solid rgba(0, 207, 255, 0.1);
            font-size: 12px;
            color: rgba(255, 255, 255, 0.4);
            letter-spacing: 0.5px;
        }
        .footer-logo {
            font-size: 18px;
            font-weight: 900;
            color: #00ff88;
            margin-bottom: 12px;
            letter-spacing: 2px;
        }
        .footer-contact {
            margin: 12px 0;
            font-size: 13px;
        }
        .footer-contact a {
            color: #00cfff;
            text-decoration: none;
        }
        .divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, #00ff88, #00cfff, transparent);
            margin: 20px 0;
            opacity: 0.3;
        }
        @media (max-width: 600px) {
            .email-container {
                border-radius: 0;
            }
            .email-content {
                padding: 24px 16px;
            }
            .order-number-value {
                font-size: 28px;
            }
            .total-amount {
                font-size: 24px;
            }
            .info-row {
                flex-direction: column;
            }
            .info-value {
                text-align: left;
                margin-top: 4px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- HEADER -->
        <div class="email-header">
            <h1>🛒 Commande Confirmée</h1>
            <p>Dribbleur Store - eFootball Premium</p>
        </div>

        <!-- CONTENT -->
        <div class="email-content">
            <!-- GREETING -->
            <div class="greeting">
                Bonjour <strong>{$customerName}</strong>,<br><br>
                Merci pour votre commande ! Nous avons bien reçu votre demande et nous la traitons en priorité.
            </div>

            <!-- ORDER NUMBER -->
            <div class="order-number">
                <div class="order-number-label">Numéro de Commande</div>
                <div class="order-number-value">#{$orderId}</div>
            </div>

            <div class="divider"></div>

            <!-- CUSTOMER INFO -->
            <div class="section">
                <div class="section-title">📋 Informations Client</div>
                <div class="info-row">
                    <span class="info-label">Nom :</span>
                    <span class="info-value">{$customerName}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Email :</span>
                    <span class="info-value">{$customerEmail}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Téléphone :</span>
                    <span class="info-value">{$profilePhone}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Adresse :</span>
                    <span class="info-value">{$address}, {$city}</span>
                </div>
            </div>

            <!-- ARTICLES -->
            <div class="section">
                <div class="section-title">📦 Articles Commandés</div>
                <div class="articles-list">
                    {$articlesText}
                </div>
            </div>

            <!-- TOTAL -->
            <div class="total-section">
                <div class="total-label">Montant Total</div>
                <div class="total-amount">{$formattedTotal}</div>
            </div>

            <div class="divider"></div>

            <!-- CTA -->
            <div class="cta-section">
                <div class="cta-text">
                    ✓ Votre commande a été enregistrée avec succès.<br>
                    Un message WhatsApp vous a été envoyé pour confirmer votre paiement.
                </div>
                <a href="https://wa.me/221775072936" class="cta-button">Contacter le Support</a>
            </div>

            <div class="divider"></div>

            <!-- INFO MESSAGE -->
            <div style="background: rgba(0, 207, 255, 0.08); border-left: 3px solid #00cfff; padding: 16px; border-radius: 8px; font-size: 13px; color: rgba(255, 255, 255, 0.8); line-height: 1.8;">
                <strong style="color: #00cfff;">ℹ️ Prochaines étapes :</strong><br><br>
                1️⃣ Vous recevrez un message WhatsApp pour confirmer votre paiement<br>
                2️⃣ Après confirmation du paiement, votre compte sera livré immédiatement<br>
                3️⃣ Vous recevrez tous les identifiants par email<br><br>
                <strong style="color: #00ff88;">Besoin d'aide ?</strong> Contactez-nous sur WhatsApp au +221 77 507 29 36
            </div>
        </div>

        <!-- FOOTER -->
        <div class="email-footer">
            <div class="footer-logo">DRIBBLEUR STORE</div>
            <p style="margin: 12px 0;">La meilleure boutique pour acheter des comptes eFootball premium</p>
            <div class="divider"></div>
            <div class="footer-contact">
                📧 <a href="mailto:diagneibeu14@gmail.com">diagneibeu14@gmail.com</a><br>
                💬 Discord: BEST DRIBBLEUR SN<br>
                📱 WhatsApp: <a href="https://wa.me/221775072936">+221 77 507 29 36</a>
            </div>
            <p style="margin-top: 16px; opacity: 0.6;">© 2025 Dribbleur Store. Tous droits réservés.</p>
        </div>
    </div>
</body>
</html>
HTML;
}

/**
 * Envoie un e-mail de confirmation de commande via la fonction mail() native de PHP.
 */
function send_order_confirmation_email(array $orderData): bool
{
    $customerEmail = trim((string) ($orderData['customer_email'] ?? ''));
    if ($customerEmail === '' || !filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $orderId = (int) ($orderData['order_id'] ?? 0);
    $subject = 'Confirmation de Commande #' . $orderId . ' - ' . STORE_NAME;
    $body = generate_order_confirmation_email($orderData);

    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= sprintf("From: %s <%s>\r\n", STORE_NAME, STORE_SENDER_EMAIL);
    $headers .= sprintf("Reply-To: %s\r\n", STORE_SENDER_EMAIL);

    return mail($customerEmail, $subject, $body, $headers);
}
