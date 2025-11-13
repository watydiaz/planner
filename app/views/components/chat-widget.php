<!-- Chat Flotante IA -->
<div id="aiChatWidget" class="ai-chat-widget">
  <!-- Botón Flotante -->
  <button id="aiChatTrigger" class="ai-chat-trigger" title="Asistente IA">
    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
      <line x1="9" y1="10" x2="15" y2="10"></line>
      <line x1="12" y1="7" x2="12" y2="13"></line>
    </svg>
    <span class="ai-chat-badge" id="aiChatBadge" style="display: none;">1</span>
  </button>

  <!-- Panel de Chat -->
  <div id="aiChatPanel" class="ai-chat-panel" style="display: none;">
    <!-- Header -->
    <div class="ai-chat-header">
      <div class="ai-chat-title">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="12" cy="12" r="3"></circle>
          <path d="M12 1v6m0 6v6m5.2-14.2l-4.2 4.2m0 6l4.2 4.2m-8.4-14.4l4.2 4.2m0 6l-4.2 4.2"></path>
        </svg>
        <span>Asistente IA</span>
      </div>
      <button id="aiChatClose" class="ai-chat-close" title="Cerrar">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <line x1="18" y1="6" x2="6" y2="18"></line>
          <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
      </button>
    </div>

    <!-- Sugerencias Rápidas -->
    <div id="aiQuickSuggestions" class="ai-quick-suggestions">
      <button class="ai-suggestion-btn" data-question="¿Qué tareas tengo pendientes hoy?">
        📋 Tareas de hoy
      </button>
      <button class="ai-suggestion-btn" data-question="¿Cuánto trabajo me queda en este sprint?">
        ⚡ Estado del sprint
      </button>
      <button class="ai-suggestion-btn" data-question="Organiza mi trabajo por prioridad">
        🎯 Organizar trabajo
      </button>
      <button class="ai-suggestion-btn" data-question="¿Qué tareas están bloqueadas o atrasadas?">
        ⚠️ Tareas bloqueadas
      </button>
    </div>

    <!-- Mensajes -->
    <div id="aiChatMessages" class="ai-chat-messages">
      <!-- Mensaje de Bienvenida -->
      <div class="ai-message ai-message-assistant">
        <div class="ai-message-avatar">🤖</div>
        <div class="ai-message-content">
          <div class="ai-message-text">
            ¡Hola! Soy tu asistente IA. Tengo acceso completo a todas tus tareas, sprints y tableros. 
            Pregúntame sobre tu trabajo pendiente, pídeme que organice tus tareas, o consulta el estado de tu proyecto.
          </div>
          <div class="ai-message-time"><?php echo date('H:i'); ?></div>
        </div>
      </div>
    </div>

    <!-- Loading Indicator -->
    <div id="aiChatTyping" class="ai-chat-typing" style="display: none;">
      <div class="ai-message ai-message-assistant">
        <div class="ai-message-avatar">🤖</div>
        <div class="ai-message-content">
          <div class="ai-typing-indicator">
            <span></span>
            <span></span>
            <span></span>
          </div>
        </div>
      </div>
    </div>

    <!-- Input -->
    <div class="ai-chat-input-container">
      <textarea 
        id="aiChatInput" 
        class="ai-chat-input" 
        placeholder="Pregúntame sobre tu trabajo..."
        rows="1"
      ></textarea>
      <button id="aiChatSend" class="ai-chat-send" title="Enviar">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <line x1="22" y1="2" x2="11" y2="13"></line>
          <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
        </svg>
      </button>
    </div>
  </div>
</div>
