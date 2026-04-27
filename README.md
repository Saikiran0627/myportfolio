# Sai Kiran Sikilammetla Portfolio

Production portfolio site for the CS Web Server Platforms final practical. The site is a static HTML/CSS/JavaScript portfolio with a server-side PHP AI endpoint that powers the "AI Portfolio Advisor" section.

## Tech Stack Manifest

- OS: Ubuntu Linux VPS
- Web server: Nginx
- Runtime: PHP 8.x with cURL extension
- Frontend: HTML5, CSS3, JavaScript, jQuery, HTML5 UP Solid State template
- AI provider: OpenAI Chat Completions API (`gpt-4o-mini`)
- Security: API key stored in a server environment variable, never in browser code

## AI Feature

The AI Portfolio Advisor lets a visitor ask focused questions about Sai Kiran's skills, projects, education, certifications, and role fit. Browser JavaScript sends the visitor's question to a same-origin endpoint:

```text
POST /api/portfolio-advisor
```

Nginx should route that public path to:

```text
/api/portfolio-advisor.php
```

The PHP script validates the request, loads `OPENAI_API_KEY` from the server environment, and calls OpenAI with a custom system prompt grounded in the portfolio content. If the API key is missing, the request is invalid, or OpenAI is unavailable, the visitor receives a clear JSON error message instead of a PHP warning or blank page.

## Environment

Set the API key on the server, for example in the PHP-FPM pool or service environment:

```bash
OPENAI_API_KEY=sk-your-key-here
```

Never commit real API keys to this repository.

## Nginx Example

```nginx
server {
    listen 80;
    server_name example.com www.example.com;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name example.com www.example.com;
    root /var/www/portfolio;
    index index.html;

    ssl_certificate /etc/letsencrypt/live/example.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/example.com/privkey.pem;

    location / {
        try_files $uri $uri/ =404;
    }

    location = /api/portfolio-advisor {
        include snippets/fastcgi-php.conf;
        fastcgi_param SCRIPT_FILENAME $document_root/api/portfolio-advisor.php;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }

    location ~ \.php$ {
        return 404;
    }
}
```

Also configure UFW or an equivalent firewall so only ports 22, 80, and 443 are open.

## Final Practical Documentation Checklist

- Live URL with DNS pointed at the server
- Let's Encrypt SSL/TLS certificate and HTTP-to-HTTPS 301 redirect
- Nginx server block and API route configured
- `OPENAI_API_KEY` stored server-side
- Screenshot of the AI Portfolio Advisor working on the live URL
- LinkedIn project/post linking to the live URL and tagging: `#Linux #Nginx #SSL #WebOps #AI #APIIntegration #PromptEngineering`
