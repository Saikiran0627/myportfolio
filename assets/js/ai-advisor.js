(function() {
	'use strict';

	var STORAGE_KEY = 'saiPortfolioAdvisorMessages';
	var SYSTEM_PROMPT = [
		'You are Sai Kiran Sikilammetla\'s portfolio assistant for recruiters and visitors.',
		'Answer only from the portfolio facts provided by the server. Keep replies concise, specific, and recruiter-friendly.'
	].join(' ');
	var WIDGET_STATE_KEY = 'saiPortfolioAdvisorWidgetOpen';
	var WELCOME_MESSAGE = 'Hi! I am Sai Kiran\'s AI Portfolio Advisor. Ask me about his skills, projects, education, or fit for a technical role.';

	var widget = document.getElementById('ai-chat-widget');
	var launcher = document.getElementById('ai-chat-launcher');
	var launcherText = launcher ? launcher.querySelector('.ai-chat-launcher-text') : null;
	var inlineOpenButton = document.getElementById('ai-widget-open-inline');
	var closeButton = document.getElementById('ai-chat-close');
	var panel = document.getElementById('ai-chat-panel');
	var chatWindow = document.getElementById('ai-chat-window');
	var form = document.getElementById('ai-chat-form');
	var input = document.getElementById('ai-chat-input');
	var sendButton = document.getElementById('ai-chat-send');
	var clearButton = document.getElementById('ai-chat-clear');
	var status = document.getElementById('ai-chat-status');

	if (!widget || !launcher || !launcherText || !closeButton || !panel || !chatWindow || !form || !input || !sendButton || !clearButton || !status)
		return;

	var messages = loadMessages();
	var isWaiting = false;
	var isOpen = false;

	function createInitialMessages() {
		return [
			{ role: 'system', content: SYSTEM_PROMPT },
			{ role: 'assistant', content: WELCOME_MESSAGE, timestamp: new Date().toISOString() }
		];
	}

	function loadMessages() {
		try {
			var saved = JSON.parse(localStorage.getItem(STORAGE_KEY) || 'null');

			if (Array.isArray(saved) && saved.length > 0)
				return sanitizeMessages(saved);
		} catch (error) {
			localStorage.removeItem(STORAGE_KEY);
		}

		return createInitialMessages();
	}

	function sanitizeMessages(rawMessages) {
		var cleanMessages = rawMessages
			.filter(function(message) {
				return message
					&& ['system', 'user', 'assistant'].indexOf(message.role) !== -1
					&& typeof message.content === 'string'
					&& message.content.trim() !== '';
			})
			.slice(-21);

		if (!cleanMessages.length || cleanMessages[0].role !== 'system')
			cleanMessages.unshift({ role: 'system', content: SYSTEM_PROMPT });

		cleanMessages[0].content = SYSTEM_PROMPT;
		return cleanMessages;
	}

	function saveMessages() {
		try {
			localStorage.setItem(STORAGE_KEY, JSON.stringify(messages));
		} catch (error) {
			setStatus('Chat is working, but this browser could not save history.');
		}
	}

	function setStatus(message) {
		status.textContent = message || '';
	}

	function setLoading(isLoading) {
		isWaiting = isLoading;
		input.disabled = isLoading;
		sendButton.disabled = isLoading;
		clearButton.disabled = isLoading;
	}

	function scrollToLatest() {
		chatWindow.scrollTop = chatWindow.scrollHeight;
	}

	function setWidgetOpen(open) {
		isOpen = open;
		widget.classList.toggle('is-open', open);
		launcher.classList.toggle('is-open', open);
		launcher.setAttribute('aria-expanded', open ? 'true' : 'false');
		panel.setAttribute('aria-hidden', open ? 'false' : 'true');
		launcherText.textContent = open ? 'Close' : 'Ask AI';

		try {
			localStorage.setItem(WIDGET_STATE_KEY, open ? 'open' : 'closed');
		} catch (error) {
			// The widget still works if the browser blocks localStorage.
		}

		if (open) {
			window.setTimeout(function() {
				scrollToLatest();
				input.focus();
			}, 180);
		} else {
			launcher.focus();
		}
	}

	function formatTime(timestamp) {
		var date = timestamp ? new Date(timestamp) : new Date();

		if (Number.isNaN(date.getTime()))
			date = new Date();

		return date.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
	}

	function renderMessages() {
		chatWindow.innerHTML = '';

		messages.forEach(function(message) {
			if (message.role === 'system')
				return;

			appendMessageBubble(message);
		});

		scrollToLatest();
	}

	function appendMessageBubble(message) {
		var row = document.createElement('div');
		var bubble = document.createElement('div');
		var label = document.createElement('span');
		var text = document.createElement('p');
		var time = document.createElement('span');

		row.className = 'ai-chat-message is-' + message.role;
		bubble.className = 'ai-chat-bubble';
		label.className = 'ai-chat-label';
		text.className = 'ai-chat-text';
		time.className = 'ai-chat-time';

		label.textContent = message.role === 'user' ? 'You' : 'AI Advisor';
		text.textContent = message.content;
		time.textContent = formatTime(message.timestamp);

		bubble.appendChild(label);
		bubble.appendChild(text);
		bubble.appendChild(time);
		row.appendChild(bubble);
		chatWindow.appendChild(row);
	}

	function addMessage(role, content) {
		var message = {
			role: role,
			content: content,
			timestamp: new Date().toISOString()
		};

		messages.push(message);
		saveMessages();
		appendMessageBubble(message);
		scrollToLatest();
		updateLauncherBadge();
		return message;
	}

	function updateLauncherBadge() {
		var hasUserMessage = messages.some(function(message) {
			return message.role === 'user';
		});

		launcher.classList.toggle('has-history', hasUserMessage);
	}

	function showTypingIndicator() {
		var row = document.createElement('div');
		var bubble = document.createElement('div');
		var label = document.createElement('span');
		var dots = document.createElement('span');

		row.className = 'ai-chat-message is-assistant';
		row.id = 'ai-chat-typing';
		bubble.className = 'ai-chat-bubble ai-chat-bubble--typing';
		label.className = 'ai-chat-label';
		dots.className = 'ai-chat-typing-dots';
		label.textContent = 'AI Advisor';
		dots.innerHTML = '<span></span><span></span><span></span>';

		bubble.appendChild(label);
		bubble.appendChild(dots);
		row.appendChild(bubble);
		chatWindow.appendChild(row);
		scrollToLatest();
	}

	function hideTypingIndicator() {
		var typing = document.getElementById('ai-chat-typing');

		if (typing)
			typing.remove();
	}

	function getConversationForApi() {
		return sanitizeMessages(messages).map(function(message) {
			return {
				role: message.role,
				content: message.content
			};
		});
	}

	function sendConversation() {
		return fetch('/api/portfolio-advisor', {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'Accept': 'application/json'
			},
			body: JSON.stringify({ messages: getConversationForApi() })
		})
			.then(function(result) {
				return result.json().catch(function() {
					throw new Error('The server returned an unreadable response.');
				}).then(function(payload) {
					if (!result.ok || !payload.ok)
						throw new Error(payload.error || 'The AI advisor is temporarily unavailable.');

					return payload.answer;
				});
			});
	}

	function typeAssistantReply(reply) {
		return new Promise(function(resolve) {
			var message = addMessage('assistant', '');
			var bubble = chatWindow.lastElementChild.querySelector('.ai-chat-text');
			var index = 0;

			function typeNextCharacter() {
				if (index >= reply.length) {
					message.content = reply;
					saveMessages();
					resolve();
					return;
				}

				message.content += reply.charAt(index);
				bubble.textContent = message.content;
				index += 1;
				scrollToLatest();
				window.setTimeout(typeNextCharacter, 12);
			}

			typeNextCharacter();
		});
	}

	function handleSubmit(event) {
		event.preventDefault();

		if (isWaiting)
			return;

		var prompt = input.value.trim();

		if (prompt.length < 2) {
			setStatus('Ask a specific question about Sai Kiran\'s portfolio.');
			input.focus();
			return;
		}

		addMessage('user', prompt);
		input.value = '';
		input.style.height = '';
		setLoading(true);
		setStatus('AI Advisor is typing...');
		showTypingIndicator();

		sendConversation()
			.then(function(answer) {
				hideTypingIndicator();
				return typeAssistantReply(answer);
			})
			.then(function() {
				setStatus('Answer generated from the server-side AI endpoint.');
			})
			.catch(function(error) {
				hideTypingIndicator();
				addMessage('assistant', error.message || 'The AI advisor is temporarily unavailable. Please try again shortly.');
				setStatus('Unable to complete the AI request.');
			})
			.finally(function() {
				setLoading(false);
				input.focus();
			});
	}

	function clearChat() {
		if (isWaiting)
			return;

		messages = createInitialMessages();
		saveMessages();
		renderMessages();
		updateLauncherBadge();
		setStatus('Chat cleared.');
		input.focus();
	}

	function resizeInput() {
		input.style.height = 'auto';
		input.style.height = Math.min(input.scrollHeight, 130) + 'px';
	}

	launcher.addEventListener('click', function() {
		setWidgetOpen(!isOpen);
	});
	if (inlineOpenButton) {
		inlineOpenButton.addEventListener('click', function() {
			setWidgetOpen(true);
		});
	}
	closeButton.addEventListener('click', function() {
		setWidgetOpen(false);
	});
	form.addEventListener('submit', handleSubmit);
	clearButton.addEventListener('click', clearChat);
	input.addEventListener('input', resizeInput);
	input.addEventListener('keydown', function(event) {
		if (event.key === 'Enter' && !event.shiftKey) {
			event.preventDefault();
			form.dispatchEvent(new Event('submit', { cancelable: true }));
		}
	});
	document.addEventListener('keydown', function(event) {
		if (event.key === 'Escape' && isOpen)
			setWidgetOpen(false);
	});

	renderMessages();
	updateLauncherBadge();
	setWidgetOpen(localStorage.getItem(WIDGET_STATE_KEY) === 'open');
})();
