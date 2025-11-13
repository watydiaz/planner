/**
 * AI CHAT MODULE - Chat Flotante con Acceso a Base de Datos
 * Permite conversación contextual con el asistente IA sobre tareas, sprints y tableros
 */

class AIChat {
    constructor() {
        this.baseUrl = window.location.origin + window.location.pathname.replace(/\/[^/]*$/, '/');
        this.isProcessing = false;
        this.messageHistory = [];
        this.initializeElements();
        this.attachEventListeners();
        this.loadHistoryFromStorage();
    }

    initializeElements() {
        this.trigger = document.getElementById('aiChatTrigger');
        this.panel = document.getElementById('aiChatPanel');
        this.closeBtn = document.getElementById('aiChatClose');
        this.messagesContainer = document.getElementById('aiChatMessages');
        this.typingIndicator = document.getElementById('aiChatTyping');
        this.input = document.getElementById('aiChatInput');
        this.sendBtn = document.getElementById('aiChatSend');
        this.suggestionsContainer = document.getElementById('aiQuickSuggestions');
    }

    attachEventListeners() {
        // Toggle panel
        this.trigger.addEventListener('click', () => this.togglePanel());
        this.closeBtn.addEventListener('click', () => this.togglePanel());

        // Enviar mensaje
        this.sendBtn.addEventListener('click', () => this.handleSend());
        this.input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                this.handleSend();
            }
        });

        // Auto-resize textarea
        this.input.addEventListener('input', () => {
            this.input.style.height = 'auto';
            this.input.style.height = Math.min(this.input.scrollHeight, 120) + 'px';
        });

        // Sugerencias rápidas
        const suggestionBtns = this.suggestionsContainer.querySelectorAll('.ai-suggestion-btn');
        suggestionBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const question = btn.getAttribute('data-question');
                this.input.value = question;
                this.handleSend();
            });
        });
    }

    togglePanel() {
        const isVisible = this.panel.style.display !== 'none';
        
        if (isVisible) {
            this.panel.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => {
                this.panel.style.display = 'none';
            }, 300);
        } else {
            this.panel.style.display = 'flex';
            this.panel.style.animation = 'slideIn 0.3s ease';
            this.input.focus();
        }
    }

    async handleSend() {
        const question = this.input.value.trim();
        
        if (!question || this.isProcessing) {
            return;
        }

        // Limpiar input
        this.input.value = '';
        this.input.style.height = 'auto';

        // Mostrar mensaje del usuario
        this.displayMessage(question, 'user');

        // Ocultar sugerencias después del primer mensaje
        if (this.messageHistory.length === 1) {
            this.suggestionsContainer.style.display = 'none';
        }

        // Mostrar indicador de escritura
        this.showTyping(true);
        this.isProcessing = true;
        this.sendBtn.disabled = true;

        try {
            const response = await this.sendMessage(question);
            
            this.showTyping(false);
            
            if (response.success) {
                this.displayMessage(response.respuesta, 'assistant');
            } else {
                this.displayMessage(
                    '❌ Lo siento, hubo un error al procesar tu pregunta. Por favor intenta de nuevo.',
                    'assistant'
                );
                console.error('Error del chat:', response.error);
            }
        } catch (error) {
            this.showTyping(false);
            this.displayMessage(
                '❌ Error de conexión. Verifica tu conexión a internet e intenta nuevamente.',
                'assistant'
            );
            console.error('Error en chat:', error);
        } finally {
            this.isProcessing = false;
            this.sendBtn.disabled = false;
            this.input.focus();
        }
    }

    async sendMessage(pregunta) {
        console.log('💬 Enviando pregunta al chat:', pregunta);

        const response = await fetch(`${this.baseUrl}?action=ai_chat`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                csrf: this.getCSRF(),
                pregunta: pregunta
            })
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        console.log('📨 Respuesta del chat:', data);
        
        return data;
    }

    displayMessage(text, type = 'assistant') {
        const messageDiv = document.createElement('div');
        messageDiv.className = `ai-message ai-message-${type}`;

        const avatar = document.createElement('div');
        avatar.className = 'ai-message-avatar';
        avatar.textContent = type === 'user' ? '👤' : '🤖';

        const content = document.createElement('div');
        content.className = 'ai-message-content';

        const textDiv = document.createElement('div');
        textDiv.className = 'ai-message-text';
        textDiv.innerHTML = this.formatMessage(text);

        const time = document.createElement('div');
        time.className = 'ai-message-time';
        time.textContent = new Date().toLocaleTimeString('es-ES', { 
            hour: '2-digit', 
            minute: '2-digit' 
        });

        content.appendChild(textDiv);
        content.appendChild(time);
        messageDiv.appendChild(avatar);
        messageDiv.appendChild(content);

        this.messagesContainer.appendChild(messageDiv);
        this.scrollToBottom();

        // Guardar en historial
        this.messageHistory.push({ text, type, timestamp: Date.now() });
        this.saveHistoryToStorage();
    }

    formatMessage(text) {
        // Convertir markdown básico a HTML
        let formatted = text;

        // Listas con números
        formatted = formatted.replace(/^(\d+)\.\s+(.+)$/gm, '<li>$2</li>');
        formatted = formatted.replace(/(<li>.*<\/li>)/s, '<ol>$1</ol>');

        // Listas con viñetas
        formatted = formatted.replace(/^[-•]\s+(.+)$/gm, '<li>$1</li>');
        formatted = formatted.replace(/(<li>.*<\/li>)/s, (match) => {
            if (!match.includes('<ol>')) {
                return '<ul>' + match + '</ul>';
            }
            return match;
        });

        // Negrita
        formatted = formatted.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');

        // Código inline
        formatted = formatted.replace(/`(.+?)`/g, '<code>$1</code>');

        // Saltos de línea
        formatted = formatted.replace(/\n/g, '<br>');

        return formatted;
    }

    showTyping(show) {
        this.typingIndicator.style.display = show ? 'block' : 'none';
        if (show) {
            this.scrollToBottom();
        }
    }

    scrollToBottom() {
        setTimeout(() => {
            this.messagesContainer.scrollTop = this.messagesContainer.scrollHeight;
        }, 100);
    }

    getCSRF() {
        return window.CSRF || window.csrf || '';
    }

    saveHistoryToStorage() {
        try {
            // Guardar solo los últimos 50 mensajes
            const recentHistory = this.messageHistory.slice(-50);
            localStorage.setItem('aiChatHistory', JSON.stringify(recentHistory));
        } catch (e) {
            console.warn('No se pudo guardar el historial del chat:', e);
        }
    }

    loadHistoryFromStorage() {
        try {
            const saved = localStorage.getItem('aiChatHistory');
            if (saved) {
                const history = JSON.parse(saved);
                
                // Cargar solo mensajes de las últimas 24 horas
                const dayAgo = Date.now() - (24 * 60 * 60 * 1000);
                const recentMessages = history.filter(msg => msg.timestamp > dayAgo);

                // Mostrar solo los últimos 10 mensajes al cargar
                recentMessages.slice(-10).forEach(msg => {
                    if (msg.type !== 'assistant' || !msg.text.includes('¡Hola! Soy tu asistente')) {
                        this.displayMessage(msg.text, msg.type);
                    }
                });

                this.messageHistory = recentMessages;
            }
        } catch (e) {
            console.warn('No se pudo cargar el historial del chat:', e);
        }
    }

    clearHistory() {
        this.messageHistory = [];
        localStorage.removeItem('aiChatHistory');
        
        // Limpiar mensajes visibles excepto el de bienvenida
        const messages = this.messagesContainer.querySelectorAll('.ai-message');
        messages.forEach((msg, index) => {
            if (index > 0) { // Mantener mensaje de bienvenida
                msg.remove();
            }
        });

        console.log('✅ Historial del chat limpiado');
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    window.aiChat = new AIChat();
    console.log('✅ AI Chat inicializado');
});

// Exponer función para limpiar historial (útil para debugging)
window.clearAIChatHistory = () => {
    if (window.aiChat) {
        window.aiChat.clearHistory();
    }
};
