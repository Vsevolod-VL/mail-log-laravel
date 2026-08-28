<?php

namespace Workbench\Database\Seeders;

use Illuminate\Database\Seeder;
use VsevolodVL\MailLogLaravel\Models\MailLogGroup;
use VsevolodVL\MailLogLaravel\Models\MailLog;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->createDemoMailLogs();
    }

    private function createDemoMailLogs(): void
    {
        // Order Confirmation - multiple sent
        $group1 = MailLogGroup::create([
            'fingerprint' => hash('sha1', 'OrderConfirmation|order|10423'),
            'subject' => 'Ihre Bestellung #10423 wurde bestätigt',
            'from' => 'shop@beispiel.de',
            'mailable_class' => 'App\\Mail\\OrderConfirmation',
            'model_type' => 'order',
            'model_id' => 10423,
            'mailer' => 'smtp',
            'html_body' => '<div style="font-family:sans-serif"><h2>Bestellung bestätigt ✓</h2><p>Vielen Dank für Ihre Bestellung.</p></div>',
            'text_body' => 'Bestellung bestätigt. Vielen Dank für Ihre Bestellung.',
            'sent_count' => 5,
            'failed_count' => 0,
            'latest_status' => 'sent',
        ]);

        for ($i = 1; $i <= 5; $i++) {
            MailLog::create([
                'group_id' => $group1->id,
                'to' => json_encode(['customer' . $i . '@example.com']),
                'cc' => json_encode(['cc@shop.de']),
                'bcc' => json_encode(['archive@shop.de']),
                'status' => 'sent',
                'seconds' => 0.5,
            ]);
        }

        // Password Reset - mixed outcomes
        $group2 = MailLogGroup::create([
            'fingerprint' => hash('sha1', 'PasswordReset|user|567'),
            'subject' => 'Passwort zurücksetzen',
            'from' => 'noreply@app.de',
            'mailable_class' => 'App\\Mail\\PasswordReset',
            'model_type' => 'user',
            'model_id' => 567,
            'mailer' => 'smtp',
            'html_body' => '<div><p>Klicken Sie auf den Link um Ihr Passwort zurückzusetzen.</p></div>',
            'text_body' => 'Link zum Zurücksetzen des Passworts',
            'sent_count' => 2,
            'failed_count' => 1,
            'latest_status' => 'failed',
        ]);

        MailLog::create([
            'group_id' => $group2->id,
            'to' => json_encode(['user@example.com']),
            'status' => 'sent',
            'seconds' => 0.3,
        ]);

        MailLog::create([
            'group_id' => $group2->id,
            'to' => json_encode(['user2@example.com']),
            'status' => 'sent',
            'seconds' => 0.4,
        ]);

        MailLog::create([
            'group_id' => $group2->id,
            'to' => json_encode(['user3@example.com']),
            'status' => 'failed',
            'error_message' => 'SMTP Error: Invalid recipient address',
        ]);

        // Newsletter
        $group3 = MailLogGroup::create([
            'fingerprint' => hash('sha1', 'Newsletter|newsletter|'),
            'subject' => 'August Newsletter',
            'from' => 'newsletter@beispiel.de',
            'mailable_class' => 'App\\Mail\\Newsletter',
            'mailer' => 'smtp',
            'html_body' => '<div><h3>Monatlicher Newsletter</h3><p>Inhalte für August...</p></div>',
            'text_body' => 'Monatlicher Newsletter - August',
            'sent_count' => 3,
            'failed_count' => 0,
            'latest_status' => 'sent',
        ]);

        MailLog::create([
            'group_id' => $group3->id,
            'to' => json_encode(['subscriber1@example.com', 'subscriber2@example.com', 'subscriber3@example.com']),
            'status' => 'sent',
            'seconds' => 0.6,
        ]);

        // Notification - pending
        $group4 = MailLogGroup::create([
            'fingerprint' => hash('sha1', 'OrderNotification|order|10424'),
            'subject' => 'Neue Bestellung eingegangen',
            'from' => 'system@beispiel.de',
            'notification_class' => 'App\\Notifications\\OrderNotification',
            'model_type' => 'order',
            'model_id' => 10424,
            'mailer' => 'smtp',
            'html_body' => '<div><p>Neue Bestellung #10424</p></div>',
            'text_body' => 'Neue Bestellung eingegangen',
            'sent_count' => 0,
            'failed_count' => 0,
            'latest_status' => 'pending',
        ]);

        MailLog::create([
            'group_id' => $group4->id,
            'to' => json_encode(['admin@beispiel.de']),
            'status' => 'pending',
        ]);

        // Invoice - multiple recipients with CC
        $group5 = MailLogGroup::create([
            'fingerprint' => hash('sha1', 'Invoice|invoice|INV-2026-001'),
            'subject' => 'Rechnung INV-2026-001',
            'from' => 'billing@beispiel.de',
            'mailable_class' => 'App\\Mail\\Invoice',
            'model_type' => 'invoice',
            'model_id' => '1212',
            'mailer' => 'smtp',
            'html_body' => '<div><p>Ihre Rechnung anbei</p></div>',
            'text_body' => 'Ihre Rechnung anbei',
            'sent_count' => 3,
            'failed_count' => 0,
            'latest_status' => 'sent',
        ]);

        for ($i = 0; $i < 3; $i++) {
            MailLog::create([
                'group_id' => $group5->id,
                'to' => json_encode(['customer@example.com']),
                'cc' => json_encode(['accounting@beispiel.de', 'archive@beispiel.de']),
                'status' => 'sent',
                'seconds' => 0.7,
            ]);
        }

        // Welcome Mail - some failed
        $group6 = MailLogGroup::create([
            'fingerprint' => hash('sha1', 'WelcomeMail|user|9999'),
            'subject' => 'Willkommen bei unserem Service!',
            'from' => 'welcome@beispiel.de',
            'mailable_class' => 'App\\Mail\\WelcomeMail',
            'model_type' => 'user',
            'model_id' => 9999,
            'mailer' => 'smtp',
            'html_body' => '<div><h2>Willkommen an Bord!</h2><p>Wir freuen uns Sie bei uns zu haben.</p></div>',
            'text_body' => 'Willkommen an Bord!',
            'sent_count' => 2,
            'failed_count' => 2,
            'latest_status' => 'failed',
        ]);

        MailLog::create([
            'group_id' => $group6->id,
            'to' => json_encode(['newuser@example.com']),
            'status' => 'sent',
            'seconds' => 0.2,
        ]);

        MailLog::create([
            'group_id' => $group6->id,
            'to' => json_encode(['newuser2@example.com']),
            'status' => 'sent',
            'seconds' => 0.2,
        ]);

        MailLog::create([
            'group_id' => $group6->id,
            'to' => json_encode(['invalid@example.com']),
            'status' => 'failed',
            'error_message' => 'Bounced: User unknown',
        ]);

        MailLog::create([
            'group_id' => $group6->id,
            'to' => json_encode(['blocked@example.com']),
            'status' => 'failed',
            'error_message' => 'SMTP: Relay access denied',
        ]);
    }
}
