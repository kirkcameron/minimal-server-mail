# Minimal Server Mail

Simple PHP mail sender with enterprise-grade security. No frameworks, no databases, just email.

## Files

- `secure-mail-handler.php` - Secure mail handler (token + rate limiting + validation)
- `config.php` - Email configuration
- `secure-example.html` - Working form example
- `apache_example.conf` - Apache server configuration
- `nginx_example.conf` - Nginx server configuration
- `caddy_example.conf` - Caddy server configuration

## Setup

### Prerequisites

- **msmtp** installed: `apt install msmtp` (Ubuntu/Debian) or `yum install msmtp` (CentOS/RHEL) - [msmtp GitHub Repository](https://github.com/marlam/msmtp)
- **PHP** with mail() function enabled
- **Web server** (Apache/Nginx/Caddy)

### Installation

1. Install msmtp: `apt install msmtp` or `yum install msmtp`
2. Configure msmtp: `~/.msmtprc` (see msmtp documentation)
3. Create symlink: `sudo ln -s /usr/bin/msmtp /usr/sbin/sendmail`
4. Configure `config.php` with your email
5. Deploy files to web server
6. Configure your web server using the provided examples

### msmtp Configuration

```bash
# ~/.msmtprc
defaults
auth           on
tls            on
tls_trust_file /etc/ssl/certs/ca-certificates.crt
logfile        ~/.msmtp.log

account        your-provider
host           smtp.your-provider.com
port           587
from           your.email@yourdomain.com
user           your.email@yourdomain.com
password       your-password

account default : your-provider
```

**Credit:** This project uses [msmtp](https://marlam.de/msmtp/) for reliable email delivery.

## Security Features

- **Daily rotating tokens** - Prevents token reuse attacks
- **Rate limiting** - 1 minute cooldown between submissions
- **Input validation** - Length limits, email format validation
- **Input sanitization** - XSS and injection prevention
- **Session-based protection** - Prevents spam and abuse
- **Server-level protection** - Direct access blocked via web server config

## Usage

### Form Submission

```bash
curl -X POST -d "name=John&email=john@example.com&message=Hello&_token=minimal_secure_2024-01-15" \
     https://yoursite.com/secure-mail-handler.php
```

### Token Generation

The token format is: `minimal_secure_YYYY-MM-DD`

- Changes daily automatically
- Prevents token reuse attacks
- Hidden from attackers

## Web Server Configuration

### **Apache Servers**

Use `apache_example.conf` as a template for your Apache virtual host configuration.

### **Nginx Servers**

Use `nginx_example.conf` as a template for your Nginx server configuration.

### **Caddy Servers**

Use `caddy_example.conf` as a template for your Caddyfile configuration.

All examples include:

- Blocking direct access to `config.php`
- Allowing access to `secure-mail-handler.php`
- Security headers
- HTTPS configuration

## Security Testing

For comprehensive security testing, use the separate [minimal-pen-tester](https://github.com/kirkcameron/minimal-pen-tester) toolkit:

```bash
git clone https://github.com/kirkcameron/minimal-pen-tester.git
cd minimal-pen-tester
./quick-security-check.sh https://yoursite.com/mail
./pen-test.sh https://yoursite.com/mail
```

## License

MIT License - see LICENSE file.
