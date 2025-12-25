<?php
/**
 * Email Service Class
 * Handles SMTP email delivery and email templates for order notifications
 * 
 * Requirements:
 * - 12.4: Use SMTP configuration for reliable email delivery
 * - 12.5: Include order details and company branding in all emails
 */

declare(strict_types=1);

class EmailService
{
    private string $smtpHost;
    private int $smtpPort;
    private string $smtpUser;
    private string $smtpPass;
    private string $fromEmail;
    private string $fromName;
    private bool $debug;

    /** @var array Company branding information */
    private array $companyInfo;

    public function __construct()
    {
        $this->smtpHost = SMTP_HOST;
        $this->smtpPort = SMTP_PORT;
        $this->smtpUser = SMTP_USER;
        $this->smtpPass = SMTP_PASS;
        $this->fromEmail = SMTP_FROM_EMAIL;
        $this->fromName = SMTP_FROM_NAME;
        $this->debug = APP_DEBUG;

        $this->companyInfo = [
            'name' => 'Ceylon Cinnamon Exports Ltd',
            'address' => '123 Spice Garden Road',
            'city' => 'Colombo',
            'country' => 'Sri Lanka',
            'postal_code' => '00100',
            'phone' => '+94 11 234 5678',
            'email' => 'orders@ceyloncinnamon.com',
            'website' => 'www.ceyloncinnamon.com',
            'logo_url' => APP_URL . '/assets/images/logo.png'
        ];
    }

    /**
     * Send an email using SMTP
     * Requirement 12.4: Use SMTP configuration for reliable email delivery
     * 
     * @param string $to Recipient email address
     * @param string $subject Email subject
     * @param string $htmlBody HTML email body
     * @param string|null $textBody Plain text email body (optional)
     * @return bool Success status
     */
    public function send(string $to, string $subject, string $htmlBody, ?string $textBody = null): bool
    {
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $this->logError("Invalid email address: {$to}");
            return false;
        }

        // Generate plain text version if not provided
        if ($textBody === null) {
            $textBody = $this->htmlToPlainText($htmlBody);
        }

        // Build email headers
        $boundary = md5(uniqid((string) time()));
        $headers = $this->buildHeaders($boundary);

        // Build multipart email body
        $body = $this->buildMultipartBody($htmlBody, $textBody, $boundary);

        // Send email
        try {
            $result = $this->sendViaSMTP($to, $subject, $body, $headers);
            
            if ($result) {
                $this->logSuccess("Email sent to: {$to}, Subject: {$subject}");
            }
            
            return $result;
        } catch (Exception $e) {
            $this->logError("Failed to send email to {$to}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Build email headers
     */
    private function buildHeaders(string $boundary): string
    {
        $headers = [];
        $headers[] = "From: {$this->fromName} <{$this->fromEmail}>";
        $headers[] = "Reply-To: {$this->fromEmail}";
        $headers[] = "MIME-Version: 1.0";
        $headers[] = "Content-Type: multipart/alternative; boundary=\"{$boundary}\"";
        $headers[] = "X-Mailer: Ceylon Cinnamon E-commerce";
        
        return implode("\r\n", $headers);
    }

    /**
     * Build multipart email body with HTML and plain text versions
     */
    private function buildMultipartBody(string $htmlBody, string $textBody, string $boundary): string
    {
        $body = "";
        
        // Plain text part
        $body .= "--{$boundary}\r\n";
        $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
        $body .= $textBody . "\r\n\r\n";
        
        // HTML part
        $body .= "--{$boundary}\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
        $body .= $htmlBody . "\r\n\r\n";
        
        // End boundary
        $body .= "--{$boundary}--";
        
        return $body;
    }

    /**
     * Send email via SMTP or fallback to mail()
     */
    private function sendViaSMTP(string $to, string $subject, string $body, string $headers): bool
    {
        // If SMTP credentials are configured, use socket-based SMTP
        if (!empty($this->smtpUser) && !empty($this->smtpPass)) {
            return $this->sendSMTPSocket($to, $subject, $body, $headers);
        }
        
        // Fallback to PHP mail() function
        return mail($to, $subject, $body, $headers);
    }

    /**
     * Send email using SMTP socket connection
     */
    private function sendSMTPSocket(string $to, string $subject, string $body, string $headers): bool
    {
        $socket = @fsockopen($this->smtpHost, $this->smtpPort, $errno, $errstr, 30);
        
        if (!$socket) {
            throw new Exception("Could not connect to SMTP server: {$errstr} ({$errno})");
        }

        try {
            // Read server greeting
            $this->smtpRead($socket);
            
            // Send EHLO
            $this->smtpCommand($socket, "EHLO " . gethostname());
            
            // Start TLS if port 587
            if ($this->smtpPort === 587) {
                $this->smtpCommand($socket, "STARTTLS");
                stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                $this->smtpCommand($socket, "EHLO " . gethostname());
            }
            
            // Authenticate
            $this->smtpCommand($socket, "AUTH LOGIN");
            $this->smtpCommand($socket, base64_encode($this->smtpUser));
            $this->smtpCommand($socket, base64_encode($this->smtpPass));
            
            // Send email
            $this->smtpCommand($socket, "MAIL FROM:<{$this->fromEmail}>");
            $this->smtpCommand($socket, "RCPT TO:<{$to}>");
            $this->smtpCommand($socket, "DATA");
            
            // Send headers and body
            $message = "To: {$to}\r\n";
            $message .= "Subject: {$subject}\r\n";
            $message .= $headers . "\r\n\r\n";
            $message .= $body . "\r\n.";
            
            $this->smtpCommand($socket, $message);
            $this->smtpCommand($socket, "QUIT");
            
            fclose($socket);
            return true;
            
        } catch (Exception $e) {
            fclose($socket);
            throw $e;
        }
    }

    /**
     * Send SMTP command and check response
     */
    private function smtpCommand($socket, string $command): string
    {
        fwrite($socket, $command . "\r\n");
        return $this->smtpRead($socket);
    }

    /**
     * Read SMTP response
     */
    private function smtpRead($socket): string
    {
        $response = '';
        while ($line = fgets($socket, 515)) {
            $response .= $line;
            if (substr($line, 3, 1) === ' ') {
                break;
            }
        }
        return $response;
    }

    /**
     * Convert HTML to plain text
     */
    private function htmlToPlainText(string $html): string
    {
        // Remove style and script tags
        $text = preg_replace('/<style[^>]*>.*?<\/style>/is', '', $html);
        $text = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $text);
        
        // Convert line breaks
        $text = preg_replace('/<br\s*\/?>/i', "\n", $text);
        $text = preg_replace('/<\/p>/i', "\n\n", $text);
        $text = preg_replace('/<\/tr>/i', "\n", $text);
        $text = preg_replace('/<\/td>/i', "\t", $text);
        
        // Strip remaining HTML tags
        $text = strip_tags($text);
        
        // Decode HTML entities
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        
        // Clean up whitespace
        $text = preg_replace('/[ \t]+/', ' ', $text);
        $text = preg_replace('/\n\s*\n\s*\n/', "\n\n", $text);
        
        return trim($text);
    }

    /**
     * Get company information for email templates
     * Requirement 12.5: Include company branding in all emails
     */
    public function getCompanyInfo(): array
    {
        return $this->companyInfo;
    }

    /**
     * Get email header HTML with company branding
     * Requirement 12.5: Include company branding in all emails
     */
    public function getEmailHeader(): string
    {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f5f5f5; }
        .email-container { max-width: 600px; margin: 0 auto; background-color: #ffffff; }
        .email-header { background-color: #8B4513; padding: 20px; text-align: center; }
        .email-header h1 { color: #ffffff; margin: 0; font-size: 24px; }
        .email-header p { color: #f5deb3; margin: 5px 0 0 0; font-size: 14px; }
        .email-body { padding: 30px; }
        .email-footer { background-color: #f5f5f5; padding: 20px; text-align: center; font-size: 12px; color: #666; }
        .btn { display: inline-block; padding: 12px 24px; background-color: #8B4513; color: #ffffff; text-decoration: none; border-radius: 4px; margin: 10px 0; }
        .order-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .order-table th, .order-table td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        .order-table th { background-color: #f9f9f9; }
        .text-right { text-align: right; }
        .total-row { font-weight: bold; background-color: #f9f9f9; }
        .status-badge { display: inline-block; padding: 4px 12px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .status-pending { background-color: #fff3cd; color: #856404; }
        .status-processing { background-color: #cce5ff; color: #004085; }
        .status-shipped { background-color: #d4edda; color: #155724; }
        .status-delivered { background-color: #d4edda; color: #155724; }
        .status-cancelled { background-color: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <h1>{$this->companyInfo['name']}</h1>
            <p>Premium Ceylon Cinnamon Products</p>
        </div>
        <div class="email-body">
HTML;
    }

    /**
     * Get email footer HTML with company branding
     * Requirement 12.5: Include company branding in all emails
     */
    public function getEmailFooter(): string
    {
        $company = $this->companyInfo;
        return <<<HTML
        </div>
        <div class="email-footer">
            <p><strong>{$company['name']}</strong></p>
            <p>{$company['address']}, {$company['city']}, {$company['country']}</p>
            <p>Phone: {$company['phone']} | Email: {$company['email']}</p>
            <p><a href="https://{$company['website']}">{$company['website']}</a></p>
            <p style="margin-top: 15px; font-size: 11px; color: #999;">
                This email was sent by {$company['name']}. If you have any questions, please contact us.
            </p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Log successful email send
     */
    private function logSuccess(string $message): void
    {
        if ($this->debug) {
            error_log("[EmailService] SUCCESS: {$message}");
        }
    }

    /**
     * Log email error
     */
    private function logError(string $message): void
    {
        error_log("[EmailService] ERROR: {$message}");
    }

    /**
     * Validate email address
     */
    public function isValidEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Get from email address
     */
    public function getFromEmail(): string
    {
        return $this->fromEmail;
    }

    /**
     * Get from name
     */
    public function getFromName(): string
    {
        return $this->fromName;
    }
}
