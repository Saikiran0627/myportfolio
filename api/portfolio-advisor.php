<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	http_response_code(405);
	echo json_encode(['error' => 'Please submit a question with the portfolio advisor form.']);
	exit;
}

$rawInput = file_get_contents('php://input');
$payload = json_decode($rawInput ?: '', true);

if (!is_array($payload)) {
	http_response_code(400);
	echo json_encode(['error' => 'The advisor could not read that request. Please try again.']);
	exit;
}

$question = trim((string) ($payload['question'] ?? ''));
$question = preg_replace('/\s+/', ' ', $question) ?? '';

if ($question === '') {
	http_response_code(422);
	echo json_encode(['error' => 'Please ask a question about Sai Kiran\'s skills, projects, or background.']);
	exit;
}

if (mb_strlen($question) > 600) {
	http_response_code(422);
	echo json_encode(['error' => 'Please keep the question under 600 characters.']);
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
- Interests: Java full stack development, machine learning, data analysis, web development, and solving real-world problems.
- Experience: Machine Learning Intern at Tekreant India Private Limited, Aug 2024 - Dec 2024, applying Python and machine learning algorithms to data challenges, analytical models, and data-processing pipelines.
- Programming and frameworks: Java, Spring Boot, Python, NumPy, Pandas, Scikit-learn, JavaScript, ReactJS, C++.
- Web and development: HTML5, CSS3, Bootstrap, Node.js, RESTful APIs, web application development.
- Data and analytics: MySQL, MariaDB, Tableau, analytical modeling, data preprocessing.
- Tools and infrastructure: Microsoft Azure AZ-900, Git/GitHub, CI/CD pipelines, Linux system administration.
- Projects: WebOps: The Spice Route, a live deployment on GreenGeeks with DNS A records, Let's Encrypt SSL, and professional email routing.
- Projects: Sales Performance Dashboard, a Tableau dashboard that analyzed sales growth and identified 25% growth trends.
- Projects: Student Management System, a Spring Boot, ReactJS, and MySQL application with secure RESTful backend and access authentication.
- Projects: Online Voting System, a Python-based voting simulation focused on authentication, transparency, and data integrity.

Style rules:
- Keep answers to 2-4 concise sentences.
- Be specific and recruiter-friendly.
- Do not invent links, dates, employers, awards, or unlisted skills.
- Mention one practical next step when useful.
PROMPT;

$requestBody = [
	'model' => getenv('OPENAI_MODEL') ?: 'gpt-4o-mini',
	'messages' => [
		['role' => 'system', 'content' => $systemPrompt],
		['role' => 'user', 'content' => $question],
	],
	'temperature' => 0.35,
	'max_tokens' => 220,
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
