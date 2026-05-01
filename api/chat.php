<?php
declare(strict_types=1);

ini_set('display_errors', '0');
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// For the class deployment, paste the real key only in the private server copy.
// Keep the repository placeholder unchanged so the key is never committed.
const OPENAI_API_KEY = 'PASTE_YOUR_OPENAI_API_KEY_HERE';
const OPENAI_ENDPOINT = 'https://api.openai.com/v1/chat/completions';
const OPENAI_MODEL = 'gpt-4o-mini';
const COFFEE_BREAK_MESSAGE = "I'm currently taking a quick coffee break! In the meantime, you can reach Sai Kiran directly at saikiran2706ssk@gmail.com.";

function sendJson(array $payload, int $statusCode = 200): void
{
	http_response_code($statusCode);
	echo json_encode($payload, JSON_UNESCAPED_SLASHES);
	exit;
}

function sendCoffeeBreakMessage(): void
{
	sendJson([
		'ok' => false,
		'answer' => COFFEE_BREAK_MESSAGE,
		'error' => COFFEE_BREAK_MESSAGE,
	]);
}

function normalizeContent(string $content, int $limit = 1200): string
{
	$content = trim((string) preg_replace('/\s+/', ' ', $content));

	if (strlen($content) > $limit) {
		$content = substr($content, 0, $limit);
	}

	return $content;
}

function getUserMessages(array $payload): array
{
	$messages = [];

	if (isset($payload['message']) && is_string($payload['message'])) {
		$messages[] = [
			'role' => 'user',
			'content' => normalizeContent($payload['message']),
		];
	}

	if (isset($payload['messages']) && is_array($payload['messages'])) {
		foreach ($payload['messages'] as $message) {
			if (!is_array($message)) {
				continue;
			}

			$role = (string) ($message['role'] ?? '');
			$content = normalizeContent((string) ($message['content'] ?? ''));

			if (!in_array($role, ['user', 'assistant'], true) || $content === '') {
				continue;
			}

			$messages[] = [
				'role' => $role,
				'content' => $content,
			];
		}
	}

	$messages = array_slice($messages, -16);

	foreach ($messages as $message) {
		if ($message['role'] === 'user') {
			return $messages;
		}
	}

	return [];
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	sendJson([
		'ok' => false,
		'error' => 'Please send a POST request from the Sai Kiran AI Assistant form.',
	], 405);
}

$rawInput = file_get_contents('php://input') ?: '';
$payload = json_decode($rawInput, true);

if (!is_array($payload)) {
	sendJson([
		'ok' => false,
		'error' => 'Please ask a question about Sai Kiran using the AI Assistant form.',
	], 400);
}

$conversation = getUserMessages($payload);

if ($conversation === []) {
	sendJson([
		'ok' => false,
		'error' => 'Please enter a question about Sai Kiran\'s skills, projects, education, or professional fit.',
	], 422);
}

if (OPENAI_API_KEY === '' || OPENAI_API_KEY === 'PASTE_YOUR_OPENAI_API_KEY_HERE') {
	error_log('Sai Kiran AI Assistant: OpenAI API key is not configured in api/chat.php.');
	sendCoffeeBreakMessage();
}

$systemPrompt = <<<'PROMPT'
You are the Sai Kiran AI Assistant, a polished professional advocate for Sai Kiran Sikilammetla's portfolio website.

Your job:
- Help recruiters, professors, classmates, and visitors understand Sai Kiran's strengths, projects, education, certifications, and fit for software engineering roles.
- Answer as a helpful portfolio guide, not as Sai Kiran pretending to speak in first person.
- Keep responses professional, accurate, concise, and encouraging.
- Prefer short paragraphs or bullets when that makes the answer easier to scan.
- If a visitor asks about hiring, internships, project fit, technical strengths, or interview readiness, connect Sai Kiran's background to practical software engineering value.
- If a question is unrelated to Sai Kiran's professional background, politely redirect back to his portfolio, skills, projects, education, or contact information.
- Do not invent employers, awards, addresses, phone numbers, private details, compensation, immigration details, or unavailable resume facts.
- If information is not available in this prompt, say that the portfolio does not list it and recommend contacting Sai Kiran directly at saikiran2706ssk@gmail.com.

Core profile:
- Full name: Sai Kiran Sikilammetla.
- Current role: Master's student in Computer Science at Rowan University.
- Academic standing: 4.0 GPA in the M.S. Computer Science program.
- Academic focus: Java Full Stack Development, Machine Learning, API-driven web systems, and practical WebOps.
- Professional positioning: emerging software engineer who combines backend development, frontend implementation, machine learning fundamentals, and production deployment awareness.
- Career target: internship, co-op, and entry-level software engineering roles involving Java, Spring Boot, Python, data-driven applications, REST APIs, cloud-aware development, and full-stack systems.

Education:
- M.S. Computer Science, Rowan University, Jan 2025 - Dec 2026.
- B.Tech. Computer Science, Lovely Professional University, 2020 - 2024, CGPA 7.75.

Experience:
- Machine Learning Intern at Tekreant India Private Limited, Aug 2024 - Dec 2024.
- Internship work included Python and machine learning workflows, data preprocessing, analytical modeling, pipeline optimization, collaboration with a professional team, and applying algorithms to real data challenges.

Technical skills:
- Programming and frameworks: Java, Spring Boot, Python, NumPy, Pandas, Scikit-learn, JavaScript, ReactJS, C++.
- Web and development: HTML5, CSS3, Bootstrap, Node.js, RESTful APIs, responsive UI development, API integration, and web application development.
- Data and analytics: MySQL, MariaDB, Tableau, analytical modeling, data preprocessing, calculated fields, dashboards, and business-focused reporting.
- Tools and infrastructure: Microsoft Azure Fundamentals, Git/GitHub, CI/CD pipelines, Linux system administration, DNS, SSL/TLS, Nginx, and production deployment basics.

Certifications:
- Microsoft Certified: Azure Fundamentals (AZ-900).
- Credential ID: 374BFB07EB7BD578.
- Certification: 2BE7FF-DY689C.
- Earned on October 6, 2025.
- Python and Java Programming Course from CodeTantra.

Portfolio projects:
1. WebOps: The Spice Route
   - Managed a full-stack live deployment on a GreenGeeks server.
   - Configured DNS A records, Let's Encrypt SSL security, Nginx/server routing, and professional email routing.
   - Shows web operations, production deployment, HTTPS, DNS, and server administration skills.

2. Sales Performance Dashboard
   - Built an interactive Tableau dashboard to analyze sales growth.
   - Identified 25% growth trends using calculated fields and advanced visualizations.
   - Shows data analysis, visualization, storytelling, and business insight generation.

3. Student Management System
   - Developed a full-stack application using Spring Boot, ReactJS, and MySQL.
   - Implemented RESTful backend services and authenticated access for student/instructor workflows.
   - Shows Java backend development, React frontend work, relational database use, and secure application design.

4. Online Voting System
   - Created a Python-based digital voting platform for small-scale simulations.
   - Focused on authentication, data integrity, secure flow design, transparent result handling, and reliability.
   - Shows Python application development and security-minded thinking.

This AI feature:
- Uses OpenAI GPT-4o mini.
- Is called from the browser through the same-origin endpoint /api/chat.
- Keeps the API key server-side in PHP.
- Uses Nginx routing so visitors do not call the PHP filename directly.

Preferred answer style:
- Use the visitor's question to choose the most relevant facts.
- Keep most answers under 180 words.
- Mention Sai Kiran's contact email only when appropriate: saikiran2706ssk@gmail.com.
- For "why hire Sai Kiran" questions, emphasize full-stack Java/Spring Boot ability, machine learning/data experience, WebOps deployment experience, 4.0 GPA, and willingness to contribute in a fast-paced engineering environment.
PROMPT;

$requestBody = [
	'model' => OPENAI_MODEL,
	'messages' => array_merge(
		[['role' => 'system', 'content' => $systemPrompt]],
		$conversation
	),
	'temperature' => 0.35,
	'max_tokens' => 320,
];

$ch = curl_init(OPENAI_ENDPOINT);

if ($ch === false) {
	error_log('Sai Kiran AI Assistant: Failed to initialize cURL.');
	sendCoffeeBreakMessage();
}

curl_setopt_array($ch, [
	CURLOPT_POST => true,
	CURLOPT_HTTPHEADER => [
		'Content-Type: application/json',
		'Authorization: Bearer ' . OPENAI_API_KEY,
	],
	CURLOPT_POSTFIELDS => json_encode($requestBody, JSON_UNESCAPED_SLASHES),
	CURLOPT_RETURNTRANSFER => true,
	CURLOPT_CONNECTTIMEOUT => 8,
	CURLOPT_TIMEOUT => 25,
]);

$responseBody = curl_exec($ch);
$curlError = curl_error($ch);
$httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($responseBody === false || $curlError !== '') {
	error_log('Sai Kiran AI Assistant: OpenAI cURL request failed.');
	sendCoffeeBreakMessage();
}

if ($httpCode < 200 || $httpCode >= 300) {
	error_log('Sai Kiran AI Assistant: OpenAI returned HTTP ' . $httpCode . '.');
	sendCoffeeBreakMessage();
}

$response = json_decode((string) $responseBody, true);

if (!is_array($response)) {
	error_log('Sai Kiran AI Assistant: OpenAI returned invalid JSON.');
	sendCoffeeBreakMessage();
}

$answer = trim((string) ($response['choices'][0]['message']['content'] ?? ''));

if ($answer === '') {
	error_log('Sai Kiran AI Assistant: OpenAI response did not contain an answer.');
	sendCoffeeBreakMessage();
}

sendJson([
	'ok' => true,
	'answer' => $answer,
]);
