(function() {
	'use strict';

	var STORAGE_KEY = 'saiPortfolioAdvisorMessages';
	var WIDGET_STATE_KEY = 'saiPortfolioAdvisorWidgetOpen';
	var WELCOME_MESSAGE = 'Hi! I’m Sai Kiran AI Assistant. I can help you explore Sai Kiran’s portfolio—feel free to ask about skills, projects, education, certifications, or role fit.';

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
					&& ['user', 'assistant'].indexOf(message.role) !== -1
					&& typeof message.content === 'string'
					&& message.content.trim() !== '';
			})
			.slice(-16);

		normalizeWelcomeMessage(cleanMessages);
		return cleanMessages;
	}

	function normalizeWelcomeMessage(cleanMessages) {
		for (var i = 1; i < cleanMessages.length; i += 1) {
			if (cleanMessages[i].role === 'user')
				return;

			if (cleanMessages[i].role === 'assistant') {
				cleanMessages[i].content = WELCOME_MESSAGE;
				return;
			}
		}
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
		launcherText.textContent = open ? 'Close' : 'Sai Kiran AI Assistant';

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

		label.textContent = message.role === 'user' ? 'You' : 'Sai Kiran AI Assistant';
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
		label.textContent = 'Sai Kiran AI Assistant';
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

	async function handleSubmit(event) {
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
		setStatus('Sai Kiran AI Assistant is typing...');
		showTypingIndicator();

		try {
			const response = await fetch('/api/chat', {
				method: 'POST',
				headers: { 'Content-Type': 'application/json' },
				body: JSON.stringify({ messages: getConversationForApi() })
			});

			const rawResponse = await response.text();
			let data = {};

			try {
				data = rawResponse ? JSON.parse(rawResponse) : {};
			} catch (parseError) {
				data = {
					error: 'Sai Kiran AI Assistant received an invalid server response. Please check the API endpoint configuration.'
				};
			}

			const reply = getAssistantReply(data, response.status);

			hideTypingIndicator();
			addMessage('assistant', reply);

			if (response.ok && data.ok) {
				setStatus('Answer generated from the server-side AI endpoint.');
			} else {
				setStatus('Unable to complete the AI request.');
			}
		} catch (error) {
			hideTypingIndicator();
			addMessage('assistant', 'I\'m currently taking a quick coffee break! In the meantime, you can reach Sai Kiran directly at saikiran2706ssk@gmail.com.');
			setStatus('Unable to complete the AI request.');
		} finally {
			setLoading(false);
			input.focus();
		}
	}

	function getAssistantReply(data, statusCode) {
		if (typeof data === 'string' && data.trim() !== '')
			return data;

		if (data && typeof data.answer === 'string' && data.answer.trim() !== '')
			return data.answer;

		if (data && typeof data.error === 'string' && data.error.trim() !== '')
			return data.error;

		if (data && data.error && typeof data.error.message === 'string' && data.error.message.trim() !== '')
			return data.error.message;

		if (data && typeof data.message === 'string' && data.message.trim() !== '')
			return data.message;

		return 'Sai Kiran AI Assistant could not complete the request. Server returned HTTP ' + statusCode + '.';
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
