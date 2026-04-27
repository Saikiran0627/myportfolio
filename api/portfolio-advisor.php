<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	http_response_code(405);
	echo json_encode(['error' => 'Please send a chat message from the portfolio assistant.']);
	exit;
}

$rawInput = file_get_contents('php://input');
$payload = json_decode($rawInput ?: '', true);

if (!is_array($payload)) {
	http_response_code(400);
	echo json_encode(['error' => 'The assistant could not read that request. Please try again.']);
	exit;
}

$incomingMessages = $payload['messages'] ?? null;

if (!is_array($incomingMessages)) {
	http_response_code(422);
	echo json_encode(['error' => 'Please send the chat history as a messages array.']);
	exit;
}

if (count($incomingMessages) < 2) {
	http_response_code(422);
	echo json_encode(['error' => 'Please include at least one message for the assistant.']);
	exit;
}

if (count($incomingMessages) > 21) {
	$incomingMessages = array_merge(
		array_slice($incomingMessages, 0, 1),
		array_slice($incomingMessages, -20)
	);
}

$messages = [];

foreach ($incomingMessages as $message) {
	if (!is_array($message)) {
		continue;
	}

	$role = (string) ($message['role'] ?? '');
	$content = trim((string) ($message['content'] ?? ''));
	$content = preg_replace('/\s+/', ' ', $content) ?? '';

	if (!in_array($role, ['system', 'user', 'assistant'], true) || $content === '') {
		continue;
	}

	if (strlen($content) > 2000) {
		$content = substr($content, 0, 2000);
	}

	$messages[] = [
		'role' => $role,
		'content' => $content,
	];
}

$hasUserMessage = false;
foreach ($messages as $message) {
	if ($message['role'] === 'user') {
		$hasUserMessage = true;
		break;
	}
}

if (!$hasUserMessage) {
	http_response_code(422);
	echo json_encode(['error' => 'Please ask a question about Sai Kiran\'s skills, projects, or background.']);
	exit;
}

$apiKey = getenv('OPENAI_API_KEY');

if (!$apiKey) {
	http_response_code(503);
	echo json_encode(['error' => 'The AI advisor is configured server-side, but the API key is not available yet.']);
	exit;
}

$systemPrompt = <<<'PROMPT'
You are Sai Kiran Sikilammetla's portfolio advisor for recruiters and visitors.
Answer only from the portfolio facts below. If the visitor asks for something unsupported, say what is known and suggest a relevant question.

Portfolio facts:
- Sai Kiran Sikilammetla is a Computer Science master's student at Rowan University, Jan 2025 - Dec 2026.
- He has a B.Tech. in Computer Science from Lovely Professional University, 2020 - 2024, CGPA 7.75.
- Positioning: Java full stack developer, machine learning practitioner, and WebOps builder focused on practical user-facing systems.
- Career target: internship and entry-level software engineering opportunities involving backend services, data-driven features, API integrations, and production-minded web applications.
- Experience: Machine Learning Intern at Tekreant India Private Limited, Aug 2024 - Dec 2024. He applied Python and machine learning workflows to data challenges, including preprocessing, modeling, evaluation, pipeline improvement, and insight generation.
- Programming and frameworks: Java, Spring Boot, Python, NumPy, Pandas, Scikit-learn, JavaScript, ReactJS, C++.
- Web and development: HTML5, CSS3, Bootstrap, Node.js, responsive UI, RESTful APIs, API-driven web application development.
- Data and analytics: MySQL, MariaDB, Tableau, analytical modeling, data preprocessing, dashboard storytelling.
- Tools and infrastructure: Microsoft Azure AZ-900, Git/GitHub, CI/CD pipelines, Linux administration, DNS, SSL, Nginx.
- Projects: WebOps: The Spice Route, a live GreenGeeks deployment with DNS A records, Let's Encrypt SSL, server routing, and professional email for a reachable, secure, production-ready platform.
- Projects: Sales Performance Dashboard, an interactive Tableau dashboard that surfaced a 25% sales growth trend using calculated fields, visual segmentation, and business-focused reporting.
- Projects: Student Management System, a Spring Boot, ReactJS, and MySQL student records platform with RESTful services and authenticated access for student and instructor workflows.
- Projects: Online Voting System, a Python voting simulation focused on authentication, data integrity, transparent result handling, and secure design principles.

Style rules:
- Keep answers to 2-4 concise sentences.
- Be specific and recruiter-friendly.
- Do not invent links, dates, employers, awards, or unlisted skills.
- Mention one practical next step when useful.
PROMPT;

$requestBody = [
	'model' => 'gpt-4o-mini',
	'messages' => array_merge(
		[['role' => 'system', 'content' => $systemPrompt]],
		array_values(array_filter($messages, static function (array $message): bool {
			return $message['role'] !== 'system';
		}))
	),
	'temperature' => 0.35,
	'max_tokens' => 260,
];

$ch = curl_init('https://api.openai.com/v1/chat/completions');
curl_setopt_array($ch, [
	CURLOPT_POST => true,
	CURLOPT_HTTPHEADER => [
		'Content-Type: application/json',
		'Authorization: Bearer ' . $apiKey,
	],
	CURLOPT_POSTFIELDS => json_encode($requestBody),
	CURLOPT_RETURNTRANSFER => true,
	CURLOPT_TIMEOUT => 25,
]);

$responseBody = curl_exec($ch);
$curlError = curl_error($ch);
$httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($responseBody === false || $curlError !== '') {
	http_response_code(502);
	echo json_encode(['error' => 'The AI advisor could not reach the provider. Please try again in a moment.']);
	exit;
}

$response = json_decode($responseBody, true);
$answer = trim((string) ($response['choices'][0]['message']['content'] ?? ''));

if ($httpCode < 200 || $httpCode >= 300 || $answer === '') {
	http_response_code(502);
	echo json_encode(['error' => 'The AI advisor received an incomplete response. Please try a different question.']);
	exit;
}

echo json_encode(['ok' => true, 'answer' => $answer]);
