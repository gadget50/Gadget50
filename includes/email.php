<?php

declare(strict_types=1);

function smtpProviderDefaults(string $provider): array
{
    return match (strtolower($provider)) {
        'gmail' => ['host'=>'smtp.gmail.com','port'=>587,'encryption'=>'tls'],
        'zoho' => ['host'=>'smtp.zoho.com','port'=>587,'encryption'=>'tls'],
        'custom' => ['host'=>'','port'=>587,'encryption'=>'tls'],
        default => ['host'=>'','port'=>587,'encryption'=>'tls'],
    };
}

function activeMailProvider(): string
{
    $provider = strtolower(trim(getSetting('active_email_provider', 'none')));
    return in_array($provider, ['gmail','zoho','custom'], true) ? $provider : 'none';
}

function getMailConfig(?string $provider = null): array
{
    $provider = $provider === null ? activeMailProvider() : strtolower(trim($provider));
    if (!in_array($provider, ['gmail','zoho','custom'], true)) return ['provider'=>'none','enabled'=>false,'validated'=>false];
    $defaults = smtpProviderDefaults($provider);
    $prefix = $provider . '_smtp_';
    return [
        'provider'=>$provider,
        'enabled'=>getSetting('email_service_enabled','0') === '1',
        'validated'=>getSetting('email_smtp_validated','0') === '1',
        'host'=>trim(getSetting($prefix.'host', (string)$defaults['host'])),
        'port'=>(int)getSetting($prefix.'port', (string)$defaults['port']),
        'encryption'=>strtolower(trim(getSetting($prefix.'encryption', (string)$defaults['encryption']))),
        'username'=>trim(getSetting($prefix.'username','')),
        'password'=>getSetting($prefix.'password',''),
        'from_email'=>trim(getSetting($prefix.'from_email','')),
        'from_name'=>trim(getSetting($prefix.'from_name','Gadget 50')),
    ];
}

function emailServiceAvailable(): bool
{
    $c=getMailConfig();
    return $c['provider'] !== 'none' && $c['enabled'] && $c['validated'] && $c['host'] !== '' && $c['username'] !== '' && $c['password'] !== '' && filter_var($c['from_email'], FILTER_VALIDATE_EMAIL) !== false;
}

function smtpRead($socket, array $codes): void
{
    $response='';
    while (($line=fgets($socket, 515)) !== false) { $response.=$line; if (isset($line[3]) && $line[3] === ' ') break; }
    $code=(int)substr($response,0,3);
    if (!in_array($code,$codes,true)) throw new RuntimeException('SMTP server returned '.$code);
}
function smtpWrite($socket,string $command,array $codes): void { fwrite($socket,$command."\r\n"); smtpRead($socket,$codes); }

function smtpSend(string $to,string $subject,string $body,array $c): bool
{
    if (!filter_var($to,FILTER_VALIDATE_EMAIL) || $c['host']==='' || $c['username']==='' || $c['password']==='' || !filter_var($c['from_email'],FILTER_VALIDATE_EMAIL)) return false;
    $host=$c['encryption']==='ssl' ? 'ssl://'.$c['host'] : $c['host'];
    $socket=@stream_socket_client($host.':'.(int)$c['port'],$errno,$error,15,STREAM_CLIENT_CONNECT);
    if (!$socket) return false;
    stream_set_timeout($socket,15);
    try {
        smtpRead($socket,[220]); smtpWrite($socket,'EHLO gadget50.local',[250]);
        if ($c['encryption']==='tls') { smtpWrite($socket,'STARTTLS',[220]); if (!stream_socket_enable_crypto($socket,true,STREAM_CRYPTO_METHOD_TLS_CLIENT)) throw new RuntimeException('TLS failed'); smtpWrite($socket,'EHLO gadget50.local',[250]); }
        smtpWrite($socket,'AUTH LOGIN',[334]); smtpWrite($socket,base64_encode($c['username']),[334]); smtpWrite($socket,base64_encode($c['password']),[235]);
        smtpWrite($socket,'MAIL FROM:<'.$c['from_email'].'>',[250]); smtpWrite($socket,'RCPT TO:<'.$to.'>',[250,251]); smtpWrite($socket,'DATA',[354]);
        $fromName=$c['from_name']!==''?$c['from_name']:'Gadget 50';
        $headers='From: '.mb_encode_mimeheader($fromName,'UTF-8').' <'.$c['from_email'].'>\r\nTo: <'.$to.'>\r\nSubject: '.mb_encode_mimeheader($subject,'UTF-8').'\r\nMIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\n';
        fwrite($socket,$headers."\r\n".$body."\r\n.\r\n"); smtpRead($socket,[250]); fwrite($socket,"QUIT\r\n"); return true;
    } catch (Throwable $e) { error_log('SMTP error: '.$e->getMessage()); return false; } finally { fclose($socket); }
}

function testSmtpConnection(array $config): bool
{
    $copy=$config; $copy['from_email']=$copy['from_email'] ?: $copy['username']; return smtpSend($copy['username'],'Gadget 50 SMTP test','<p>SMTP connection successful.</p>',$copy);
}
function sendEmail(string $to,string $subject,string $body): bool { return emailServiceAvailable() && smtpSend($to,$subject,$body,getMailConfig()); }
function sendUserEmailVerification(array $user): bool { if (!emailServiceAvailable() || empty($user['email']) || getSetting('email_verification_enabled','0')!=='1') return false; $token=generateSecureToken(); $pdo=Database::getInstance(); $pdo->prepare('UPDATE users SET email_verification_token=:token,email_verification_expires_at=DATE_ADD(UTC_TIMESTAMP(),INTERVAL 24 HOUR) WHERE id=:id')->execute([':token'=>$token,':id'=>(int)$user['id']]); return sendEmail((string)$user['email'],'Verify your email','<p><a href="'.e(appUrl('verify-email.php?token='.$token)).'">Verify your email</a></p>'); }
function sendPasswordResetEmail(array $user): bool { if (!emailServiceAvailable() || empty($user['email']) || getSetting('password_reset_email_enabled','0')!=='1') return false; $token=generateSecureToken(); $pdo=Database::getInstance(); $pdo->prepare('UPDATE users SET password_reset_token=:token,password_reset_expires_at=DATE_ADD(UTC_TIMESTAMP(),INTERVAL 60 MINUTE) WHERE id=:id')->execute([':token'=>$token,':id'=>(int)$user['id']]); return sendEmail((string)$user['email'],'Reset your password','<p><a href="'.e(appUrl('reset-password.php?token='.$token)).'">Reset password</a></p>'); }
function sendAdminNewUserEmail(array $user): bool { $to=trim(getSetting('admin_alert_email','')); return $to==='' || !emailServiceAvailable() ? true : sendEmail($to,'New user registration','<p>Username: '.e((string)$user['username']).'</p>'); }
function sendTwoFactorCodeEmail(array $user,string $code): bool { return !empty($user['email']) && sendEmail((string)$user['email'],'Your two-factor login code','<p>Code: <strong>'.e($code).'</strong></p>'); }
