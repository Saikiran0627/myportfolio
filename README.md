# Sai Kiran Sikilammetla Portfolio

Production portfolio site for the CS Web Server Platforms final practical. The site is a static HTML/CSS/JavaScript portfolio with a server-side PHP AI endpoint that powers the "Sai Kiran AI Assistant" chatbot.

## Tech Stack Manifest

- OS: Ubuntu Linux VPS
- Web server: Nginx
- Runtime: PHP 8.x with cURL extension
- Frontend: HTML5, CSS3, JavaScript, jQuery, HTML5 UP Solid State template
- AI provider: OpenAI Chat Completions API (`gpt-4o-mini`)
- Security: OpenAI API key stored only in `api/chat.php`, never in HTML, JavaScript, or browser-visible code

## AI Feature

Sai Kiran AI Assistant is a contextual OpenAI GPT-4o mini chatbot for this portfolio. Visitors can open it from the dedicated AI Assistant section or from the bottom-right launcher and ask follow-up questions about Sai Kiran's skills, projects, education, certifications, 4.0 GPA, and role fit. Browser JavaScript keeps the chat history in memory and `localStorage`, then sends the conversation to a same-origin endpoint:

```text
POST /api/chat
```

Nginx should route that public path to:

```text
/api/chat.php
```

The request body uses OpenAI-style messages:

```json
{
  "messages": [
    { "role": "user", "content": "Which project shows web operations experience?" }
  ]
}
```

The PHP script validates the message history, ignores browser-provided system prompts, owns the full professional advocate prompt on the server, and calls OpenAI with `gpt-4o-mini`. If OpenAI fails, times out, returns a non-200 HTTP status, returns invalid JSON, or has no usable answer, the visitor receives this friendly JSON response instead of a PHP warning or blank page:

```text
I'm currently taking a quick coffee break! In the meantime, you can reach Sai Kiran directly at saikiran2706ssk@gmail.com.
```

## Environment

Set the API key in `api/chat.php` before deployment:

```bash
const OPENAI_API_KEY = 'sk-your-key-here';
```

Never commit real API keys to public repositories. For the class deployment, keep the real key only in the private production copy of `api/chat.php` on the server.

## Nginx Example

```nginx
server {
    listen 80;
    server_name saikirantech.xyz www.saikirantech.xyz;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name saikirantech.xyz www.saikirantech.xyz;
    root /var/www/portfolio;
    index index.html;

    ssl_certificate /etc/letsencrypt/live/saikirantech.xyz/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/saikirantech.xyz/privkey.pem;

    location / {
        try_files $uri $uri/ =404;
    }

    location = /api/chat {
        include snippets/fastcgi-php.conf;
        fastcgi_param SCRIPT_FILENAME $document_root/api/chat.php;
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
- Nginx server block and `/api/chat` API route configured
- OpenAI API key stored server-side in `api/chat.php`
- Screenshot of Sai Kiran AI Assistant working on the live URL
- LinkedIn project/post linking to the live URL and tagging: `#Linux #Nginx #SSL #WebOps #AI #APIIntegration #PromptEngineering`
