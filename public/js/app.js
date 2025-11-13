// ==========================
// Frontend JS – Kanban Application
// ==========================

// Variables globales (inyectadas desde el layout)
// CSRF, BASE_URL, ASSETS_URL están disponibles

// Elementos del DOM
const board = document.getElementById('board');
const cardModal = new bootstrap.Modal(document.getElementById('cardModal'));
const sprintModal = new bootstrap.Modal(document.getElementById('sprintModal'));
const elCardId = document.getElementById('cardId');
const elCardTitle = document.getElementById('cardTitle');
const elCardDesc = document.getElementById('cardDesc');
const elCardPoints = document.getElementById('cardPoints');
const elCardCategoria = document.getElementById('cardCategoria');

// Autocompletar IA al crear nueva tarea
elCardTitle.addEventListener('change', async () => {
  if (!elCardId.value && elCardTitle.value.trim().length > 3) {
    // Solo si es nueva tarea y el título tiene más de 3 caracteres
    // Descripción
    if (!elCardDesc.value.trim()) {
      elCardDesc.value = 'Generando descripción...';
      try {
        const desc = await window.AI.generarDescripcion(elCardTitle.value);
        elCardDesc.value = desc;
      } catch (e) {
        elCardDesc.value = '';
      }
    }
    // Story Points
    if (elCardPoints.value === '0') {
      try {
        const points = await window.AI.estimarStoryPoints(elCardTitle.value);
        if (points && points.story_points) {
          elCardPoints.value = points.story_points;
        }
      } catch (e) {}
    }
    // Categoría
    if (!elCardCategoria.value) {
      try {
        const cat = await window.AI.sugerirCategoria(elCardTitle.value);
        if (cat) elCardCategoria.value = cat;
      } catch (e) {}
    }
  }
});
const elCardProyectoLargo = document.getElementById('cardProyectoLargo');
const elCardFechaInicio = document.getElementById('cardFechaInicio');
const elCardFechaEntrega = document.getElementById('cardFechaEntrega');
const elCardFechaEntregaNormal = document.getElementById('cardFechaEntregaNormal');
const boardSelector = document.getElementById('boardSelector');

// Elementos del modal de sprint
const elSprintNombre = document.getElementById('sprintNombre');
const elSprintObjetivo = document.getElementById('sprintObjetivoInput');
const elSprintFechaInicio = document.getElementById('sprintFechaInicio');
const elSprintFechaFin = document.getElementById('sprintFechaFin');

// Elementos para actividades
const elNewActivityInput = document.getElementById('newActivityInput');
const elActivitiesTimeline = document.getElementById('activitiesTimeline');
const elBtnAddActivity = document.getElementById('btnAddActivity');

// Modales personalizados
const customPromptModal = new bootstrap.Modal(document.getElementById('customPromptModal'));
const customConfirmModal = new bootstrap.Modal(document.getElementById('customConfirmModal'));
const newBoardModal = new bootstrap.Modal(document.getElementById('newBoardModal'));

// Variables de estado
let currentBoard = null;
let currentSprint = null;
let allCards = [];
let allBoards = [];
let allLists = [];
let vistaActual = 'kanban'; // 'kanban' o 'lista'

// ==========================
// FUNCIONES UTILITARIAS
// ==========================

/**
 * Función personalizada de prompt
 */
function customPrompt(title, label, defaultValue = '') {
  return new Promise((resolve) => {
    const modal = document.getElementById('customPromptModal');
    const input = document.getElementById('customPromptInput');
    const titleEl = document.getElementById('customPromptTitle');
    const labelEl = document.getElementById('customPromptLabel');
    const confirmBtn = document.getElementById('customPromptConfirm');
    
    titleEl.textContent = title;
    labelEl.textContent = label;
    input.value = defaultValue;
    
    const handleConfirm = () => {
      const value = input.value.trim();
      customPromptModal.hide();
      resolve(value || null);
      confirmBtn.removeEventListener('click', handleConfirm);
      input.removeEventListener('keypress', handleKeyPress);
      modal.removeEventListener('hidden.bs.modal', handleCancel);
    };
    
    const handleCancel = () => {
      resolve(null);
      confirmBtn.removeEventListener('click', handleConfirm);
      input.removeEventListener('keypress', handleKeyPress);
      modal.removeEventListener('hidden.bs.modal', handleCancel);
    };
    
    const handleKeyPress = (e) => {
      if (e.key === 'Enter') handleConfirm();
    };
    
    confirmBtn.addEventListener('click', handleConfirm);
    input.addEventListener('keypress', handleKeyPress);
    modal.addEventListener('hidden.bs.modal', handleCancel, { once: true });
    
    customPromptModal.show();
    setTimeout(() => input.focus(), 300);
  });
}

/**
 * Función personalizada de confirm
 */
function customConfirm(title, message) {
  return new Promise((resolve) => {
    const modal = document.getElementById('customConfirmModal');
    const titleEl = document.getElementById('customConfirmTitle');
    const messageEl = document.getElementById('customConfirmMessage');
    const confirmBtn = document.getElementById('customConfirmYes');
    
    titleEl.textContent = title;
    messageEl.textContent = message;
    
    const handleConfirm = () => {
      customConfirmModal.hide();
      resolve(true);
      confirmBtn.removeEventListener('click', handleConfirm);
      modal.removeEventListener('hidden.bs.modal', handleCancel);
    };
    
    const handleCancel = () => {
      resolve(false);
      confirmBtn.removeEventListener('click', handleConfirm);
      modal.removeEventListener('hidden.bs.modal', handleCancel);
    };
    
    confirmBtn.addEventListener('click', handleConfirm);
    modal.addEventListener('hidden.bs.modal', handleCancel, { once: true });
    
    customConfirmModal.show();
  });
}

/**
 * API helper - Realiza llamadas POST con CSRF
 */
async function api(action, data = {}) {
  const form = new FormData();
  Object.entries({ ...data, csrf: CSRF }).forEach(([k, v]) => {
    if (Array.isArray(v)) {
      v.forEach(item => form.append(`${k}[]`, item));
    } else {
      form.append(k, v);
    }
  });
  const res = await fetch(`?action=${encodeURIComponent(action)}`, { method: 'POST', body: form });
  const js = await res.json();
  if (!js.ok) throw new Error(js.msg || 'Error API');
  return js;
}

/**
 * Escapar HTML para prevenir XSS
 */
function escapeHtml(str) {
  return (str || '').replace(/[&<>"']/g, m => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;'
  }[m]));
}

/**
 * Obtener icono según tipo de archivo
 */
function getFileIcon(mimeType, fileName) {
  if (!mimeType && !fileName) return '📄';
  
  const ext = fileName?.split('.').pop()?.toLowerCase();
  
  if (mimeType?.startsWith('image/')) return '🖼️';
  if (mimeType?.startsWith('video/')) return '🎥';
  if (mimeType?.startsWith('audio/')) return '🎵';
  if (mimeType?.includes('pdf')) return '📕';
  if (mimeType?.includes('word') || ext === 'doc' || ext === 'docx') return '📘';
  if (mimeType?.includes('excel') || ext === 'xls' || ext === 'xlsx') return '📗';
  if (mimeType?.includes('powerpoint') || ext === 'ppt' || ext === 'pptx') return '📙';
  if (mimeType?.includes('zip') || mimeType?.includes('rar') || ext === 'zip' || ext === 'rar') return '🗜️';
  if (ext === 'txt') return '📝';
  
  return '📄';
}

// ==========================
// CARGA Y RENDERIZADO DE DATOS
// ==========================

/**
 * Cargar datos del tablero
 */
async function load(boardId = null) {
  const url = boardId ? `?action=get_board&board_id=${boardId}` : '?action=get_board';
  const res = await fetch(url);
  const js = await res.json();
  if (!js.ok) throw new Error(js.msg || 'Error al cargar');
  
  currentBoard = js.board;
  currentSprint = js.sprintActivo;
  allCards = js.cards;
  allBoards = js.boards;
  allLists = js.lists;
  
  document.getElementById('currentBoardName').textContent = js.board?.nombre || 'Sin tablero';
  
  updateBoardSelector(js.boards, js.board?.id);
  render(js.lists, js.cards);
  updateSprintStats(js.cards);
  
  if (vistaActual === 'lista') {
    renderListaView();
  }
}

/**
 * Actualizar selector de tableros
 */
function updateBoardSelector(boards, currentId) {
  boardSelector.innerHTML = '';
  boards.forEach(b => {
    const option = document.createElement('option');
    option.value = b.id;
    option.textContent = b.nombre;
    if (b.id == currentId) option.selected = true;
    boardSelector.appendChild(option);
  });
}

/**
 * Actualizar estadísticas del sprint
 */
function updateSprintStats(cards) {
  const sprintTitleEl = document.getElementById('sprintTitle');
  const sprintObjetivoEl = document.getElementById('sprintObjetivo');
  
  // Si no existen los elementos (ej: en vista de Informe), salir
  if (!sprintTitleEl || !sprintObjetivoEl) {
    return;
  }
  
  if (!currentSprint) {
    sprintTitleEl.textContent = '🏃 Sin sprint activo';
    sprintObjetivoEl.textContent = '';
    return;
  }
  
  sprintTitleEl.textContent = `🏃 ${currentSprint.nombre}`;
  sprintObjetivoEl.textContent = currentSprint.objetivo || '';
  
  const hoy = new Date();
  const fin = new Date(currentSprint.fecha_fin);
  const diffTime = fin - hoy;
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
  document.getElementById('diasRestantes').textContent = diffDays >= 0 ? diffDays : '0 (vencido)';
  
  const hechoListId = document.querySelector('.list.hecho')?.dataset.listId;
  const completados = cards.filter(c => c.list_id == hechoListId).reduce((sum, c) => sum + (c.story_points || 0), 0);
  const totales = cards.reduce((sum, c) => sum + (c.story_points || 0), 0);
  
  document.getElementById('puntosCompletados').textContent = completados;
  document.getElementById('puntosTotales').textContent = totales;
  document.getElementById('porcentaje').textContent = totales > 0 ? Math.round((completados / totales) * 100) + '%' : '0%';
}

/**
 * Renderizar vista Kanban
 */
function render(lists, cards) {
  board.innerHTML = '';
  
  // 🤖 CALCULAR PRIORIDADES CON ALGORITMO IA
  const tareasActivas = cards.filter(c => {
    const list = lists.find(l => l.id === c.list_id);
    return list && (list.title === 'Por Hacer' || list.title === 'En Progreso');
  });
  
  const tareasConScore = tareasActivas.map(tarea => {
    const list = lists.find(l => l.id === tarea.list_id);
    const score = calcularScoreTarea(tarea, list);
    return { ...tarea, score, list };
  });
  
  tareasConScore.sort((a, b) => b.score.total - a.score.total);
  
  // Crear mapa de prioridades (id -> posición)
  const prioridadMap = {};
  tareasConScore.forEach((tarea, index) => {
    prioridadMap[tarea.id] = index + 1;
  });
  
  const fixedLists = [
    { id: 'pendiente', title: 'Por Hacer', cssClass: 'pendiente' },
    { id: 'en-progreso', title: 'En Progreso', cssClass: 'en-progreso' },
    { id: 'hecho', title: 'Completado', cssClass: 'hecho' }
  ];
  
  const cardsByList = {};
  lists.forEach(l => cardsByList[l.id] = []);
  cards.forEach(c => {
    (cardsByList[c.list_id] ||= []).push(c);
  });

  fixedLists.forEach((fixedList, index) => {
    const dbList = lists.find(l => l.title === fixedList.title);
    if (!dbList) return;
    
    const col = document.createElement('div');
    col.className = `list ${fixedList.cssClass}`;
    col.dataset.listId = dbList.id;

    const cardsInList = cardsByList[dbList.id] || [];
    const cardCount = cardsInList.length;

    col.innerHTML = `
      <div class="list-header">
        <div class="d-flex align-items-center justify-content-between w-100">
          <div class="d-flex align-items-center gap-2">
            <span class="list-title">${escapeHtml(fixedList.title)}</span>
            <span class="badge rounded-pill" style="background: rgba(59, 130, 246, 0.2); color: #60a5fa; font-size: 0.9rem; font-weight: 700; padding: 0.3rem 0.7rem;">${cardCount}</span>
          </div>
          <button class="btn btn-sm btn-add-card">✨ Tarea</button>
        </div>
      </div>
      <div class="dropzone"></div>
    `;

    const dz = col.querySelector('.dropzone');

    (cardsByList[dbList.id] || []).forEach(card => {
      const prioridad = prioridadMap[card.id];
      dz.appendChild(renderCard(card, prioridad));
    });

    setupDropzone(dz);

    col.querySelector('.btn-add-card').addEventListener('click', async () => {
      const title = await customPrompt('📝 Nueva Tarea', 'Título de la tarea:', 'Nueva tarea');
      if (!title) return;
      const sprintId = currentSprint ? currentSprint.id : null;
      await api('add_card', { list_id: dbList.id, title, sprint_id: sprintId });
      load();
    });

    board.appendChild(col);
  });
}

/**
 * Renderizar vista de Lista/Tabla con filtros
 */
function renderListaView() {
  const tbody = document.getElementById('listaTableBody');
  tbody.innerHTML = '';
  
  // Construir mapeo de list_id -> nombre de lista desde los datos del API
  const lists = {};
  allLists.forEach(l => {
    lists[l.id] = l.title;
  });
  
  // Obtener valores de filtros
  const filtroFechaInicio = document.getElementById('filtroFechaInicio')?.value;
  const filtroFechaFin = document.getElementById('filtroFechaFin')?.value;
  const filtroEstado = document.getElementById('filtroEstado')?.value;
  const filtroCategoria = document.getElementById('filtroCategoria')?.value;
  
  // Mapear estados a nombres de listas
  const estadoMap = {
    'pendiente': 'Por Hacer',
    'en_progreso': 'En Progreso',
    'hecho': 'Completado'
  };
  
  // Filtrar tarjetas
  let filteredCards = [...allCards];
  
  // Filtrar por rango de fechas (fecha de creación)
  if (filtroFechaInicio) {
    const fechaInicio = new Date(filtroFechaInicio);
    fechaInicio.setHours(0, 0, 0, 0);
    filteredCards = filteredCards.filter(card => {
      if (!card.created_at) return false;
      const cardDate = new Date(card.created_at);
      cardDate.setHours(0, 0, 0, 0);
      return cardDate >= fechaInicio;
    });
  }
  
  if (filtroFechaFin) {
    const fechaFin = new Date(filtroFechaFin);
    fechaFin.setHours(23, 59, 59, 999);
    filteredCards = filteredCards.filter(card => {
      if (!card.created_at) return false;
      const cardDate = new Date(card.created_at);
      return cardDate <= fechaFin;
    });
  }
  
  // Filtrar por estado
  if (filtroEstado) {
    const estadoNombre = estadoMap[filtroEstado];
    filteredCards = filteredCards.filter(card => {
      const cardEstado = lists[card.list_id] || '';
      return cardEstado.includes(estadoNombre);
    });
  }
  
  // Filtrar por categoría
  if (filtroCategoria) {
    filteredCards = filteredCards.filter(card => {
      return card.categoria === filtroCategoria;
    });
  }
  
  const sortedCards = filteredCards.sort((a, b) => b.id - a.id);
  
  // Mostrar mensaje si no hay resultados
  if (sortedCards.length === 0) {
    tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-4">📭 No se encontraron tareas con los filtros aplicados</td></tr>';
    return;
  }
  
  sortedCards.forEach(card => {
    const tr = document.createElement('tr');
    
    // Calcular tiempo transcurrido
    const elapsed = card.created_at ? getTimeElapsed(card.created_at) : null;
    const elapsedBadge = elapsed 
      ? `<span class="badge" style="background: ${elapsed.timeColor}; color: white; font-size: 0.75rem; font-weight: 600;">${elapsed.timeIcon} ${elapsed.timeText}</span>`
      : '';
    
    const estadoNombre = lists[card.list_id] || 'Sin estado';
    let estadoBadge = '';
    if (estadoNombre === 'Pendiente') {
      estadoBadge = '<span class="badge bg-secondary">⏳ Pendiente</span>';
    } else if (estadoNombre === 'En Progreso') {
      estadoBadge = '<span class="badge bg-primary">🔄 En Progreso</span>';
    } else if (estadoNombre === 'Hecho') {
      estadoBadge = '<span class="badge bg-success">✅ Hecho</span>';
    }
    
    let categoriaBadge = '<span class="badge bg-dark">Sin categoría</span>';
    if (card.categoria === 'soporte') {
      categoriaBadge = '<span class="badge bg-warning text-dark">🔧 Soporte</span>';
    } else if (card.categoria === 'desarrollo') {
      categoriaBadge = '<span class="badge bg-info text-dark">💻 Desarrollo</span>';
    }
    
    const tipoBadge = card.es_proyecto_largo == 1
      ? '<span class="badge" style="background: #8b5cf6;">🚀 Largo</span>'
      : '<span class="badge bg-dark">⚡ Sprint</span>';
    
    let fechaHtml = '<span class="text-muted">-</span>';
    if (card.fecha_entrega) {
      const fechaEntrega = new Date(card.fecha_entrega);
      const hoy = new Date();
      const diffDays = Math.ceil((fechaEntrega - hoy) / (1000 * 60 * 60 * 24));
      
      let colorFecha = '#10b981';
      let iconoFecha = '📅';
      if (diffDays < 0) {
        colorFecha = '#ef4444';
        iconoFecha = '🔴';
      } else if (diffDays <= 2) {
        colorFecha = '#f59e0b';
        iconoFecha = '⚠️';
      }
      
      const fechaFormateada = fechaEntrega.toLocaleDateString('es-ES', { day: '2-digit', month: 'short', year: 'numeric' });
      
      if (card.es_proyecto_largo == 1 && card.fecha_inicio) {
        const fechaInicio = new Date(card.fecha_inicio);
        const inicioFormateada = fechaInicio.toLocaleDateString('es-ES', { day: '2-digit', month: 'short' });
        fechaHtml = `<span style="color: ${colorFecha}; font-weight: 600;">${iconoFecha} ${inicioFormateada} → ${fechaFormateada}</span>`;
      } else {
        fechaHtml = `<span style="color: ${colorFecha}; font-weight: 600;">${iconoFecha} ${fechaFormateada}</span>`;
      }
    }
    
    tr.innerHTML = `
      <td><span class="badge bg-secondary">#${card.id}</span></td>
      <td>
        <div class="fw-semibold">${escapeHtml(card.title)}</div>
        ${card.description ? `<small class="text-muted">${escapeHtml(card.description)}</small>` : ''}
        <div class="mt-1">${elapsedBadge}</div>
      </td>
      <td>${estadoBadge}</td>
      <td>${categoriaBadge}</td>
      <td><span class="badge bg-primary">${card.story_points || 0} pts</span></td>
      <td>${fechaHtml}</td>
      <td>${tipoBadge}</td>
      <td>
        <button class="btn btn-sm btn-outline-primary btn-edit-lista" data-card-id="${card.id}">✏️</button>
        <button class="btn btn-sm btn-outline-danger btn-del-lista" data-card-id="${card.id}">🗑️</button>
      </td>
    `;
    
    // Doble click en la fila para abrir modal
    tr.addEventListener('dblclick', () => {
      openCardModal(card);
    });
    tr.style.cursor = 'pointer';
    
    tbody.appendChild(tr);
  });
  
  document.querySelectorAll('.btn-edit-lista').forEach(btn => {
    btn.addEventListener('click', function () {
      const cardId = parseInt(this.dataset.cardId);
      const card = allCards.find(c => c.id === cardId);
      if (card) openCardModal(card);
    });
  });
  
  document.querySelectorAll('.btn-del-lista').forEach(btn => {
    btn.addEventListener('click', async function () {
      const confirmed = await customConfirm('🗑️ Eliminar Tarea', '¿Estás seguro de eliminar esta tarea?');
      if (!confirmed) return;
      const cardId = parseInt(this.dataset.cardId);
      await api('delete_card', { id: cardId });
      load();
    });
  });
}

/**
 * Renderizar una tarjeta individual
 */
/**
 * Calcular tiempo transcurrido desde la creación de la tarjeta
 */
function getTimeElapsed(createdAt) {
  const created = new Date(createdAt);
  const now = new Date();
  const diffMs = now - created;
  const diffMins = Math.floor(diffMs / (1000 * 60));
  const diffHours = Math.floor(diffMs / (1000 * 60 * 60));
  const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));
  
  let timeText = '';
  let timeColor = '#10b981'; // Verde por defecto
  let timeIcon = '🕐';
  
  // Menos de 1 hora
  if (diffMins < 60) {
    timeText = `${diffMins} min`;
    timeColor = '#10b981';
    timeIcon = '🆕';
  }
  // Mismo día (menos de 24 horas)
  else if (diffHours < 24) {
    timeText = `${diffHours}h`;
    timeColor = '#3b82f6';
    timeIcon = '🕐';
  }
  // 1 día
  else if (diffDays === 1) {
    timeText = '1 día';
    timeColor = '#f59e0b';
    timeIcon = '📆';
  }
  // 2-7 días
  else if (diffDays <= 7) {
    timeText = `${diffDays} días`;
    timeColor = '#f59e0b';
    timeIcon = '📆';
  }
  // Más de 1 semana
  else if (diffDays <= 30) {
    const weeks = Math.floor(diffDays / 7);
    timeText = weeks === 1 ? '1 semana' : `${weeks} semanas`;
    timeColor = '#ef4444';
    timeIcon = '⚠️';
  }
  // Más de 1 mes
  else {
    const months = Math.floor(diffDays / 30);
    timeText = months === 1 ? '1 mes' : `${months} meses`;
    timeColor = '#dc2626';
    timeIcon = '🔴';
  }
  
  return { timeText, timeColor, timeIcon };
}

/**
 * Renderizar tarjeta individual en vista Kanban
 */
function renderCard(card, prioridad = null) {
  const el = document.createElement('div');
  el.className = 'card-item';
  el.draggable = true;
  el.dataset.cardId = card.id;
  
  const createdDate = card.created_at ? new Date(card.created_at) : new Date();
  const formattedDate = createdDate.toLocaleDateString('es-ES', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  });
  const formattedTime = createdDate.toLocaleTimeString('es-ES', {
    hour: '2-digit',
    minute: '2-digit'
  });
  
  // Calcular tiempo transcurrido
  const elapsed = card.created_at ? getTimeElapsed(card.created_at) : null;
  const elapsedBadge = elapsed 
    ? `<span class="badge" style="background: ${elapsed.timeColor}; color: white; font-size: 0.65rem; font-weight: 600; padding: 0.25rem 0.5rem; border-radius: 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.15);">${elapsed.timeIcon} ${elapsed.timeText}</span>`
    : '';
  
  const pointsBadge = card.story_points > 0
    ? `<span class="story-points-badge">${card.story_points} pts</span>`
    : '';
  
  // 🤖 BADGE DE PRIORIDAD IA
  let prioridadBadge = '';
  if (prioridad) {
    let bgColor = '#fbbf24'; // Oro por defecto
    let icon = '⭐';
    let label = 'Hacer';
    
    if (prioridad === 1) {
      bgColor = 'linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%)';
      icon = '🥇';
      label = 'TOP';
    } else if (prioridad === 2) {
      bgColor = 'linear-gradient(135deg, #94a3b8 0%, #64748b 100%)';
      icon = '🥈';
      label = 'ALTA';
    } else if (prioridad === 3) {
      bgColor = 'linear-gradient(135deg, #cd7f32 0%, #b87333 100%)';
      icon = '🥉';
      label = 'ALTA';
    } else if (prioridad <= 5) {
      bgColor = 'linear-gradient(135deg, #3b82f6 0%, #2563eb 100%)';
      icon = '🎯';
      label = 'Prio';
    } else if (prioridad <= 10) {
      bgColor = 'linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%)';
      icon = '📌';
      label = 'Hacer';
    } else {
      bgColor = 'linear-gradient(135deg, #6b7280 0%, #4b5563 100%)';
      icon = '📋';
      label = 'Normal';
    }
    
    prioridadBadge = `<span class="badge text-white" style="background: ${bgColor}; font-size: 0.7rem; font-weight: 700; padding: 0.35rem 0.6rem; border-radius: 6px; box-shadow: 0 3px 8px rgba(0,0,0,0.25); animation: pulse 2s ease-in-out infinite;">${icon} #${prioridad} ${label}</span>`;
  }
  
  let categoriaBadge = '';
  if (card.categoria === 'soporte') {
    categoriaBadge = '<span class="badge text-dark" style="font-size: 0.7rem; background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); font-weight: 600; box-shadow: 0 2px 6px rgba(251, 191, 36, 0.3);">🔧 Soporte</span>';
  } else if (card.categoria === 'desarrollo') {
    categoriaBadge = '<span class="badge text-dark" style="font-size: 0.7rem; background: linear-gradient(135deg, #22d3ee 0%, #06b6d4 100%); font-weight: 600; box-shadow: 0 2px 6px rgba(34, 211, 238, 0.3);">💻 Desarrollo</span>';
  }
  
  const proyectoLargoBadge = card.es_proyecto_largo == 1
    ? '<span class="badge text-white" style="font-size: 0.7rem; background: linear-gradient(135deg, #a855f7 0%, #7c3aed 100%); font-weight: 600; box-shadow: 0 2px 6px rgba(168, 85, 247, 0.3);">🚀 Largo Plazo</span>'
    : '';
  
  const activitiesBadge = card.activities_count > 0
    ? `<span class="badge text-white" style="font-size: 0.7rem; background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%); font-weight: 600; box-shadow: 0 2px 6px rgba(20, 184, 166, 0.3);">📋 ${card.activities_count}</span>`
    : '';
  
  let fechaBadge = '';
  if (card.fecha_entrega) {
    const fechaEntrega = new Date(card.fecha_entrega);
    const hoy = new Date();
    const diffDays = Math.ceil((fechaEntrega - hoy) / (1000 * 60 * 60 * 24));
    
    let colorFecha = '#10b981';
    let iconoFecha = '📅';
    if (diffDays < 0) {
      colorFecha = '#ef4444';
      iconoFecha = '🔴';
    } else if (diffDays <= 2) {
      colorFecha = '#f59e0b';
      iconoFecha = '⚠️';
    }
    
    const fechaFormateada = fechaEntrega.toLocaleDateString('es-ES', { day: '2-digit', month: 'short' });
    
    if (card.es_proyecto_largo == 1 && card.fecha_inicio) {
      const fechaInicio = new Date(card.fecha_inicio);
      const inicioFormateada = fechaInicio.toLocaleDateString('es-ES', { day: '2-digit', month: 'short' });
      fechaBadge = `<span style="color: ${colorFecha}; font-size: 0.7rem; font-weight: 600;">${iconoFecha} ${inicioFormateada} → ${fechaFormateada}</span>`;
    } else {
      fechaBadge = `<span style="color: ${colorFecha}; font-size: 0.7rem; font-weight: 600;">${iconoFecha} ${fechaFormateada}</span>`;
    }
  }
  
  el.innerHTML = `
    <div class="d-flex align-items-center justify-content-between mb-2">
      <div class="d-flex align-items-center gap-2 flex-wrap">
        <span class="badge" style="background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%); color: #fff; font-size: 0.72rem; font-weight: 700; padding: 0.3rem 0.6rem; border-radius: 6px; box-shadow: 0 2px 4px rgba(0,0,0,0.15);">#${card.id}</span>
        ${pointsBadge}
        ${elapsedBadge}
      </div>
      ${prioridad ? `<div>${prioridadBadge}</div>` : ''}
    </div>
    <div class="fw-bold mb-2" style="color: #111827; font-size: 0.95rem; line-height: 1.4;">${escapeHtml(card.title)}</div>
    ${card.description ? `<div class="small mb-2" style="color: #6b7280; line-height: 1.5;">${escapeHtml(card.description)}</div>` : ''}
    <div class="d-flex gap-2 mb-2 flex-wrap">
      ${categoriaBadge}
      ${proyectoLargoBadge}
      ${activitiesBadge}
    </div>
    <div class="d-flex justify-content-between align-items-end gap-2 mt-auto pt-2" style="border-top: 1px solid rgba(226, 232, 240, 0.8);">
      <div class="d-flex flex-column gap-1">
        <small style="color: #9ca3af; font-size: 0.65rem; font-weight: 500;">📅 ${formattedDate} • ${formattedTime}</small>
        ${fechaBadge ? `<div>${fechaBadge}</div>` : ''}
      </div>
      <div class="d-flex gap-1">
        <button class="btn btn-sm btn-outline-primary btn-edit" style="font-size: 0.7rem; padding: 0.25rem 0.5rem; border-radius: 6px; border-width: 1.5px;">✏️</button>
        <button class="btn btn-sm btn-outline-danger btn-del" style="font-size: 0.7rem; padding: 0.25rem 0.5rem; border-radius: 6px; border-width: 1.5px;">🗑️</button>
      </div>
    </div>
  `;

  // Doble click para abrir modal de edición
  el.addEventListener('dblclick', (e) => {
    // Evitar que se active si se hace doble click en los botones
    if (!e.target.closest('button')) {
      openCardModal(card);
    }
  });

  el.addEventListener('dragstart', e => {
    el.classList.add('dragging');
    e.dataTransfer.setData('text/plain', String(card.id));
    e.dataTransfer.effectAllowed = 'move';
  });
  
  el.addEventListener('dragend', () => {
    el.classList.remove('dragging');
    document.querySelectorAll('.ghost').forEach(g => g.remove());
  });

  el.querySelector('.btn-edit').addEventListener('click', () => openCardModal(card));
  el.querySelector('.btn-del').addEventListener('click', async () => {
    const confirmed = await customConfirm('🗑️ Eliminar Tarea', '¿Estás seguro de eliminar esta tarea?');
    if (!confirmed) return;
    await api('delete_card', { id: card.id });
    load();
  });

  return el;
}

// ==========================
// DRAG & DROP
// ==========================

/**
 * Configurar zona de drop para drag & drop
 */
function setupDropzone(dz) {
  dz.addEventListener('dragover', e => {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
    
    document.querySelectorAll('.ghost').forEach(g => g.remove());
    
    const after = getDragAfterElement(dz, e.clientY);
    const ghost = document.createElement('div');
    ghost.className = 'ghost';
    
    if (after == null) dz.appendChild(ghost);
    else dz.insertBefore(ghost, after);
  });
  
  dz.addEventListener('drop', async e => {
    e.preventDefault();
    const cardId = parseInt(e.dataTransfer.getData('text/plain'), 10);
    const listId = parseInt(dz.closest('.list').dataset.listId, 10);
    
    const ghost = dz.querySelector('.ghost');
    const index = ghost ? [...dz.children].indexOf(ghost) : 0;
    
    document.querySelectorAll('.ghost').forEach(g => g.remove());
    
    await api('move_card', { id: cardId, to_list: listId, to_index: index < 0 ? 0 : index });
    load();
  });
  
  dz.addEventListener('dragleave', e => {
    if (!e.relatedTarget || !dz.contains(e.relatedTarget)) {
      const ghost = dz.querySelector('.ghost');
      if (ghost) ghost.remove();
    }
  });
}

/**
 * Obtener elemento después del cual insertar
 */
function getDragAfterElement(container, y) {
  const els = [...container.querySelectorAll('.card-item:not(.dragging)')];
  return els.reduce((closest, child) => {
    const box = child.getBoundingClientRect();
    const offset = y - box.top - box.height / 2;
    if (offset < 0 && offset > closest.offset) return { offset, element: child };
    else return closest;
  }, { offset: Number.NEGATIVE_INFINITY }).element;
}

// ==========================
// MODALES Y FORMULARIOS
// ==========================

/**
 * Abrir modal de edición de tarjeta
 */
function openCardModal(card) {
  elCardId.value = card.id;
  elCardTitle.value = card.title;
  elCardDesc.value = card.description || '';
  elCardPoints.value = card.story_points || 0;
  elCardCategoria.value = card.categoria || '';
  
  const esProyectoLargo = card.es_proyecto_largo == 1;
  elCardProyectoLargo.checked = esProyectoLargo;
  
  if (esProyectoLargo) {
    elCardFechaInicio.value = card.fecha_inicio || '';
    elCardFechaEntrega.value = card.fecha_entrega || '';
    document.getElementById('fechasProyecto').style.display = 'flex';
    document.getElementById('fechaNormal').style.display = 'none';
  } else {
    elCardFechaEntregaNormal.value = card.fecha_entrega || '';
    document.getElementById('fechasProyecto').style.display = 'none';
    document.getElementById('fechaNormal').style.display = 'block';
  }
  
  loadActivities(card.id);
  cardModal.show();
}

/**
 * Cargar actividades de una tarjeta
 */
async function loadActivities(cardId) {
  try {
    const res = await fetch(`?action=get_activities&card_id=${cardId}`);
    const data = await res.json();
    
    if (data.ok && data.activities.length > 0) {
      renderActivities(data.activities);
    } else {
      elActivitiesTimeline.innerHTML = '<p class="text-center text-secondary small mb-0">No hay actividades registradas</p>';
    }
  } catch (e) {
    console.error('Error cargando actividades:', e);
  }
}

/**
 * Renderizar timeline de actividades
 */
function renderActivities(activities) {
  elActivitiesTimeline.innerHTML = activities.map(act => {
    const fecha = new Date(act.created_at);
    const fechaFormateada = fecha.toLocaleDateString('es-ES', {
      day: '2-digit',
      month: 'short',
      hour: '2-digit',
      minute: '2-digit'
    });
    
    let attachmentHtml = '';
    if (act.archivo_nombre) {
      const isImage = act.archivo_tipo?.startsWith('image/');
      const sizeMB = (act.archivo_tamano / 1024 / 1024).toFixed(2);
      const icon = getFileIcon(act.archivo_tipo, act.archivo_nombre);
      
      if (isImage) {
        attachmentHtml = `
          <a href="${act.archivo_ruta}" target="_blank" class="attachment-preview">
            <img src="${act.archivo_ruta}" alt="${act.archivo_nombre}">
            <div class="attachment-info">
              <span class="attachment-name">${escapeHtml(act.archivo_nombre)}</span>
              <span class="attachment-size">${sizeMB} MB</span>
            </div>
          </a>
        `;
      } else {
        attachmentHtml = `
          <a href="${act.archivo_ruta}" target="_blank" download class="attachment-preview">
            <span class="attachment-icon">${icon}</span>
            <div class="attachment-info">
              <span class="attachment-name">${escapeHtml(act.archivo_nombre)}</span>
              <span class="attachment-size">${sizeMB} MB</span>
            </div>
          </a>
        `;
      }
    }
    
    return `
      <div class="activity-item">
        <div class="activity-content">
          <div class="d-flex justify-content-between align-items-start mb-1">
            <span class="activity-time">⏰ ${fechaFormateada}</span>
            <div class="d-flex gap-1">
              <button class="btn btn-sm btn-outline-secondary" onclick="editActivity(${act.id}, '${escapeHtml(act.contenido).replace(/'/g, "\\'")}');" style="padding: 0.1rem 0.4rem; font-size: 0.7rem; border-radius: 4px;">✏️</button>
              ${!act.archivo_nombre ? `<button class="btn btn-sm btn-outline-info" onclick="attachFile(${act.id})" style="padding: 0.1rem 0.4rem; font-size: 0.7rem; border-radius: 4px;">📎</button>` : ''}
              <button class="btn btn-sm btn-outline-danger" onclick="deleteActivity(${act.id})" style="padding: 0.1rem 0.4rem; font-size: 0.7rem; border-radius: 4px;">🗑️</button>
            </div>
          </div>
          <div class="activity-text">${escapeHtml(act.contenido)}</div>
          ${attachmentHtml}
        </div>
      </div>
    `;
  }).join('');
}

/**
 * Eliminar actividad
 */
async function deleteActivity(activityId) {
  const confirmed = await customConfirm('🗑️ Eliminar Actividad', '¿Estás seguro de eliminar esta actividad?');
  if (!confirmed) return;
  
  await api('delete_activity', { id: activityId });
  loadActivities(elCardId.value);
}

/**
 * Editar descripción de actividad
 */
async function editActivity(activityId, currentText) {
  const newText = await customPrompt('✏️ Editar Actividad', 'Descripción:', currentText);
  if (!newText || newText === currentText) return;
  
  const formData = new FormData();
  formData.append('csrf', CSRF);
  formData.append('id', activityId);
  formData.append('descripcion', newText);
  
  try {
    const res = await fetch('?action=update_activity', {
      method: 'POST',
      body: formData
    });
    const data = await res.json();
    if (data.ok) loadActivities(elCardId.value);
  } catch (e) {
    console.error('Error editando actividad:', e);
  }
}

/**
 * Adjuntar archivo a actividad
 */
async function attachFile(activityId) {
  const input = document.createElement('input');
  input.type = 'file';
  input.accept = 'image/*,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip,.rar';
  
  input.onchange = async (e) => {
    const file = e.target.files[0];
    if (!file) return;
    
    const formData = new FormData();
    formData.append('csrf', CSRF);
    formData.append('id', activityId);
    formData.append('archivo', file);
    
    try {
      const res = await fetch('?action=update_activity', {
        method: 'POST',
        body: formData
      });
      const data = await res.json();
      if (data.ok) loadActivities(elCardId.value);
    } catch (err) {
      console.error('Error adjuntando archivo:', err);
    }
  };
  
  input.click();
}

// ==========================
// EVENT LISTENERS
// ==========================

// Toggle para mostrar campos de proyecto largo
elCardProyectoLargo.addEventListener('change', function () {
  const esProyectoLargo = this.checked;
  document.getElementById('fechasProyecto').style.display = esProyectoLargo ? 'flex' : 'none';
  document.getElementById('fechaNormal').style.display = esProyectoLargo ? 'none' : 'block';
});

// Botones de duración rápida en modal sprint
document.querySelectorAll('.btn-duracion').forEach(btn => {
  btn.addEventListener('click', function () {
    document.querySelectorAll('.btn-duracion').forEach(b => b.classList.remove('active'));
    this.classList.add('active');
    
    const days = parseInt(this.dataset.days);
    const inicio = new Date(elSprintFechaInicio.value || new Date());
    const fin = new Date(inicio);
    fin.setDate(fin.getDate() + days);
    elSprintFechaFin.value = fin.toISOString().split('T')[0];
  });
});

// Cuando cambia fecha inicio, actualizar fecha fin
elSprintFechaInicio.addEventListener('change', function () {
  const btnActive = document.querySelector('.btn-duracion.active');
  if (btnActive) {
    const days = parseInt(btnActive.dataset.days);
    const inicio = new Date(this.value);
    const fin = new Date(inicio);
    fin.setDate(fin.getDate() + days);
    elSprintFechaFin.value = fin.toISOString().split('T')[0];
  }
});

// Toggle entre vistas Kanban / Lista
document.getElementById('btnVistaKanban').addEventListener('click', function () {
  vistaActual = 'kanban';
  document.getElementById('board').style.display = 'grid';
  document.getElementById('vistaLista').style.display = 'none';
  
  // Activar Kanban
  this.classList.add('active');
  this.classList.remove('btn-outline-primary');
  this.classList.add('btn-primary');
  this.style.background = 'linear-gradient(135deg, #3b82f6 0%, #2563eb 100%)';
  this.style.borderColor = '';
  this.style.color = 'white';
  
  // Desactivar Lista
  const btnLista = document.getElementById('btnVistaLista');
  btnLista.classList.remove('active', 'btn-primary');
  btnLista.classList.add('btn-outline-primary');
  btnLista.style.background = '';
  btnLista.style.borderColor = '#3b82f6';
  btnLista.style.color = '#3b82f6';
});

document.getElementById('btnVistaLista').addEventListener('click', function () {
  vistaActual = 'lista';
  document.getElementById('board').style.display = 'none';
  document.getElementById('vistaLista').style.display = 'block';
  
  // Activar Lista
  this.classList.add('active');
  this.classList.remove('btn-outline-primary');
  this.classList.add('btn-primary');
  this.style.background = 'linear-gradient(135deg, #3b82f6 0%, #2563eb 100%)';
  this.style.borderColor = '';
  this.style.color = 'white';
  
  // Desactivar Kanban
  const btnKanban = document.getElementById('btnVistaKanban');
  btnKanban.classList.remove('active', 'btn-primary');
  btnKanban.classList.add('btn-outline-primary');
  btnKanban.style.background = '';
  btnKanban.style.borderColor = '#3b82f6';
  btnKanban.style.color = '#3b82f6';
  
  renderListaView();
});

// Cambiar de tablero
boardSelector.addEventListener('change', () => {
  load(boardSelector.value);
});

// Agregar nueva actividad
elBtnAddActivity.addEventListener('click', async () => {
  const contenido = elNewActivityInput.value.trim();
  if (!contenido) return;
  
  const cardId = elCardId.value;
  const res = await api('add_activity', {
    card_id: cardId,
    contenido
  });
  
  if (res.ok) {
    elNewActivityInput.value = '';
    loadActivities(cardId);
  }
});

// Enter para agregar actividad
elNewActivityInput.addEventListener('keypress', (e) => {
  if (e.key === 'Enter') {
    elBtnAddActivity.click();
  }
});

// Guardar tarjeta
document.getElementById('btnSaveCard').addEventListener('click', async () => {
  const esProyectoLargo = elCardProyectoLargo.checked;
  const fechaEntrega = esProyectoLargo ? elCardFechaEntrega.value : elCardFechaEntregaNormal.value;
  
  await api('update_card', {
    id: elCardId.value,
    title: elCardTitle.value,
    description: elCardDesc.value,
    story_points: elCardPoints.value,
    fecha_entrega: fechaEntrega,
    categoria: elCardCategoria.value,
    es_proyecto_largo: esProyectoLargo ? 1 : 0,
    fecha_inicio: esProyectoLargo ? elCardFechaInicio.value : '',
    asignado_a: ''
  });
  cardModal.hide();
  load();
});

// Eliminar tarjeta
document.getElementById('btnDeleteCard').addEventListener('click', async () => {
  const confirmed = await customConfirm('🗑️ Eliminar Tarea', '¿Estás seguro de eliminar esta tarea?');
  if (!confirmed) return;
  await api('delete_card', { id: elCardId.value });
  cardModal.hide();
  load();
});

// Nuevo sprint
document.getElementById('btnNewSprint').addEventListener('click', () => {
  const hoy = new Date();
  elSprintFechaInicio.value = hoy.toISOString().split('T')[0];
  const fin = new Date(hoy);
  fin.setDate(fin.getDate() + 14);
  elSprintFechaFin.value = fin.toISOString().split('T')[0];
  
  elSprintNombre.value = '';
  elSprintObjetivo.value = '';
  
  sprintModal.show();
});

// Guardar sprint
document.getElementById('btnSaveSprint').addEventListener('click', async () => {
  const nombre = elSprintNombre.value.trim();
  const objetivo = elSprintObjetivo.value.trim();
  const fechaInicio = elSprintFechaInicio.value;
  const fechaFin = elSprintFechaFin.value;
  
  if (!nombre) {
    alert('El nombre del sprint es requerido');
    return;
  }
  
  if (!fechaInicio || !fechaFin) {
    alert('Las fechas de inicio y fin son requeridas');
    return;
  }
  
  if (new Date(fechaFin) <= new Date(fechaInicio)) {
    alert('La fecha de fin debe ser posterior a la fecha de inicio');
    return;
  }
  
  await api('add_sprint', {
    nombre,
    objetivo,
    fecha_inicio: fechaInicio,
    fecha_fin: fechaFin,
    board_id: currentBoard?.id
  });
  
  sprintModal.hide();
  load();
});

// Nuevo tablero
document.getElementById('btnNewBoard').addEventListener('click', () => {
  document.getElementById('newBoardNameInput').value = '';
  newBoardModal.show();
  setTimeout(() => document.getElementById('newBoardNameInput').focus(), 300);
});

// Enter en input de nuevo tablero
document.getElementById('newBoardNameInput').addEventListener('keypress', (e) => {
  if (e.key === 'Enter') {
    document.getElementById('btnCreateNewBoard').click();
  }
});

// Confirmar creación de nuevo tablero
document.getElementById('btnCreateNewBoard').addEventListener('click', async () => {
  const nombre = document.getElementById('newBoardNameInput').value.trim();
  if (!nombre) return;
  
  await api('add_board', { nombre, descripcion: '' });
  newBoardModal.hide();
  load();
});

// Eliminar tablero
document.getElementById('btnDeleteBoard').addEventListener('click', async () => {
  if (!currentBoard) return;
  const confirmed = await customConfirm('🗑️ Eliminar Tablero', `¿Estás seguro de eliminar el tablero "${currentBoard.nombre}" y todas sus tareas?`);
  if (!confirmed) return;
  
  try {
    await api('delete_board', { id: currentBoard.id });
    load();
  } catch (e) {
    alert(e.message);
  }
});

// ==========================
// INICIALIZACIÓN
// ==========================

// Forzar estilo blanco en iconos de calendario
function fixDatePickerIcons() {
  const dateInputs = document.querySelectorAll('input[type="date"]');
  dateInputs.forEach(input => {
    input.style.colorScheme = 'dark';
  });
}

// Ejecutar al cargar
fixDatePickerIcons();

// Ejecutar cada vez que se abre un modal
document.getElementById('cardModal').addEventListener('shown.bs.modal', fixDatePickerIcons);
document.getElementById('sprintModal').addEventListener('shown.bs.modal', fixDatePickerIcons);

// ==========================
// FILTROS VISTA LISTA
// ==========================

// Función helper para obtener el lunes de la semana actual
function getMondayOfCurrentWeek() {
  const today = new Date();
  const day = today.getDay();
  const diff = today.getDate() - day + (day === 0 ? -6 : 1); // Ajustar cuando es domingo
  const monday = new Date(today.setDate(diff));
  monday.setHours(0, 0, 0, 0);
  return monday;
}

// Función helper para obtener el sábado de la semana actual
function getSaturdayOfCurrentWeek() {
  const monday = getMondayOfCurrentWeek();
  const saturday = new Date(monday);
  saturday.setDate(monday.getDate() + 5); // Lunes + 5 días = Sábado
  saturday.setHours(23, 59, 59, 999);
  return saturday;
}

// Botón: Ayer
document.getElementById('btnFiltroAyer')?.addEventListener('click', () => {
  const yesterday = new Date();
  yesterday.setDate(yesterday.getDate() - 1);
  
  const year = yesterday.getFullYear();
  const month = String(yesterday.getMonth() + 1).padStart(2, '0');
  const day = String(yesterday.getDate()).padStart(2, '0');
  const dateStr = `${year}-${month}-${day}`;
  
  document.getElementById('filtroFechaInicio').value = dateStr;
  document.getElementById('filtroFechaFin').value = dateStr;
  
  if (vistaActual === 'lista') {
    renderListaView();
  }
});

// Botón: Hoy
document.getElementById('btnFiltroHoy')?.addEventListener('click', () => {
  const today = new Date();
  
  const year = today.getFullYear();
  const month = String(today.getMonth() + 1).padStart(2, '0');
  const day = String(today.getDate()).padStart(2, '0');
  const dateStr = `${year}-${month}-${day}`;
  
  document.getElementById('filtroFechaInicio').value = dateStr;
  document.getElementById('filtroFechaFin').value = dateStr;
  
  if (vistaActual === 'lista') {
    renderListaView();
  }
});

// Botón: Esta Semana (Lunes a Sábado)
document.getElementById('btnFiltroSemanaActual')?.addEventListener('click', () => {
  const monday = getMondayOfCurrentWeek();
  const saturday = getSaturdayOfCurrentWeek();
  
  document.getElementById('filtroFechaInicio').value = monday.toISOString().split('T')[0];
  document.getElementById('filtroFechaFin').value = saturday.toISOString().split('T')[0];
  
  if (vistaActual === 'lista') {
    renderListaView();
  }
});

// Botón: Este Mes
document.getElementById('btnFiltroMesActual')?.addEventListener('click', () => {
  const today = new Date();
  const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
  const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);
  
  document.getElementById('filtroFechaInicio').value = firstDay.toISOString().split('T')[0];
  document.getElementById('filtroFechaFin').value = lastDay.toISOString().split('T')[0];
  
  if (vistaActual === 'lista') {
    renderListaView();
  }
});

// Botón: Limpiar Filtros
document.getElementById('btnLimpiarFiltros')?.addEventListener('click', () => {
  document.getElementById('filtroFechaInicio').value = '';
  document.getElementById('filtroFechaFin').value = '';
  document.getElementById('filtroEstado').value = '';
  document.getElementById('filtroCategoria').value = '';
  
  if (vistaActual === 'lista') {
    renderListaView();
  }
});

// Aplicar filtros automáticamente cuando cambian
document.getElementById('filtroFechaInicio')?.addEventListener('change', () => {
  if (vistaActual === 'lista') renderListaView();
});

document.getElementById('filtroFechaFin')?.addEventListener('change', () => {
  if (vistaActual === 'lista') renderListaView();
});

document.getElementById('filtroEstado')?.addEventListener('change', () => {
  if (vistaActual === 'lista') renderListaView();
});

document.getElementById('filtroCategoria')?.addEventListener('change', () => {
  if (vistaActual === 'lista') renderListaView();
});

// ====================================
// VISTA INFORME
// ====================================

// Toggle Vista Kanban
document.getElementById('btnVistaKanban')?.addEventListener('click', () => {
  vistaActual = 'kanban';
  const boardElement = document.getElementById('board');
  boardElement.style.display = '';
  boardElement.classList.add('kanban');
  document.getElementById('vistaLista').style.display = 'none';
  document.getElementById('vistaInforme').style.display = 'none';
  
  document.getElementById('btnVistaKanban').classList.add('active');
  document.getElementById('btnVistaKanban').classList.remove('btn-outline-primary');
  document.getElementById('btnVistaKanban').classList.add('btn-primary');
  document.getElementById('btnVistaKanban').style.background = 'linear-gradient(135deg, #3b82f6 0%, #2563eb 100%)';
  
  document.getElementById('btnVistaLista').classList.remove('active');
  document.getElementById('btnVistaLista').classList.add('btn-outline-primary');
  document.getElementById('btnVistaLista').classList.remove('btn-primary');
  document.getElementById('btnVistaLista').style.background = '';
  
  document.getElementById('btnVistaInforme').classList.remove('active');
  document.getElementById('btnVistaInforme').classList.add('btn-outline-primary');
  document.getElementById('btnVistaInforme').classList.remove('btn-primary');
  document.getElementById('btnVistaInforme').style.background = '';
});

// Toggle Vista Lista
document.getElementById('btnVistaLista')?.addEventListener('click', () => {
  vistaActual = 'lista';
  document.getElementById('board').style.display = 'none';
  document.getElementById('vistaLista').style.display = 'block';
  document.getElementById('vistaInforme').style.display = 'none';
  
  document.getElementById('btnVistaLista').classList.add('active');
  document.getElementById('btnVistaLista').classList.remove('btn-outline-primary');
  document.getElementById('btnVistaLista').classList.add('btn-primary');
  document.getElementById('btnVistaLista').style.background = 'linear-gradient(135deg, #3b82f6 0%, #2563eb 100%)';
  
  document.getElementById('btnVistaKanban').classList.remove('active');
  document.getElementById('btnVistaKanban').classList.add('btn-outline-primary');
  document.getElementById('btnVistaKanban').classList.remove('btn-primary');
  document.getElementById('btnVistaKanban').style.background = '';
  
  document.getElementById('btnVistaInforme').classList.remove('active');
  document.getElementById('btnVistaInforme').classList.add('btn-outline-primary');
  document.getElementById('btnVistaInforme').classList.remove('btn-primary');
  document.getElementById('btnVistaInforme').style.background = '';
  
  renderListaView();
});

// Toggle Vista Informe
document.getElementById('btnVistaInforme')?.addEventListener('click', () => {
  vistaActual = 'informe';
  document.getElementById('board').style.display = 'none';
  document.getElementById('vistaLista').style.display = 'none';
  document.getElementById('vistaInforme').style.display = 'block';
  
  document.getElementById('btnVistaInforme').classList.add('active');
  document.getElementById('btnVistaInforme').classList.remove('btn-outline-primary');
  document.getElementById('btnVistaInforme').classList.add('btn-primary');
  document.getElementById('btnVistaInforme').style.background = 'linear-gradient(135deg, #3b82f6 0%, #2563eb 100%)';
  
  document.getElementById('btnVistaKanban').classList.remove('active');
  document.getElementById('btnVistaKanban').classList.add('btn-outline-primary');
  document.getElementById('btnVistaKanban').classList.remove('btn-primary');
  document.getElementById('btnVistaKanban').style.background = '';
  
  document.getElementById('btnVistaLista').classList.remove('active');
  document.getElementById('btnVistaLista').classList.add('btn-outline-primary');
  document.getElementById('btnVistaLista').classList.remove('btn-primary');
  document.getElementById('btnVistaLista').style.background = '';
  
  renderInformeView();
});

/**
 * Renderizar vista de informe con estadísticas y insights
 */
function renderInformeView() {
  if (!allCards || !allLists) return;
  
  // Calcular estadísticas básicas
  const totalTareas = allCards.length;
  const completadas = allCards.filter(c => {
    const list = allLists.find(l => l.id === c.list_id);
    return list && list.title === 'Completado';
  }).length;
  
  const enProgreso = allCards.filter(c => {
    const list = allLists.find(l => l.id === c.list_id);
    return list && list.title === 'En Progreso';
  }).length;
  
  const pendientes = allCards.filter(c => {
    const list = allLists.find(l => l.id === c.list_id);
    return list && list.title === 'Por Hacer';
  }).length;
  
  const tasaCompletitud = totalTareas > 0 ? Math.round((completadas / totalTareas) * 100) : 0;
  
  // Actualizar tarjetas de resumen
  document.getElementById('statTotalTareas').textContent = totalTareas;
  document.getElementById('statCompletadas').textContent = completadas;
  document.getElementById('statEnProgreso').textContent = enProgreso;
  document.getElementById('statTasaCompletitud').textContent = tasaCompletitud + '%';
  
  // Actualizar distribución por estado
  document.getElementById('statPendientes').textContent = pendientes;
  document.getElementById('statEnProgresoDetalle').textContent = enProgreso;
  document.getElementById('statCompletadasDetalle').textContent = completadas;
  
  // Actualizar barra de progreso general
  document.getElementById('progresoGeneral').textContent = tasaCompletitud + '%';
  const barraProgreso = document.getElementById('barraProgresoGeneral');
  barraProgreso.style.width = tasaCompletitud + '%';
  barraProgreso.textContent = tasaCompletitud + '%';
  barraProgreso.setAttribute('aria-valuenow', tasaCompletitud);
  
  // Calcular story points
  const puntosTotales = allCards.reduce((sum, c) => sum + (parseInt(c.story_points) || 0), 0);
  const puntosCompletados = allCards.filter(c => {
    const list = allLists.find(l => l.id === c.list_id);
    return list && list.title === 'Completado';
  }).reduce((sum, c) => sum + (parseInt(c.story_points) || 0), 0);
  
  const tasaPuntos = puntosTotales > 0 ? Math.round((puntosCompletados / puntosTotales) * 100) : 0;
  
  document.getElementById('puntosCompletadosInforme').textContent = puntosCompletados;
  document.getElementById('puntosTotalesInforme').textContent = puntosTotales;
  
  const barraPuntos = document.getElementById('barraPuntos');
  barraPuntos.style.width = tasaPuntos + '%';
  barraPuntos.textContent = tasaPuntos + '%';
  barraPuntos.setAttribute('aria-valuenow', tasaPuntos);
  
  // Generar insights IA
  generarInsights(totalTareas, completadas, enProgreso, pendientes, tasaCompletitud);
  
  // Generar categorías
  generarCategorias();
  
  // Generar productividad diaria
  generarProductividadDiaria();
  
  // Generar tareas atrasadas
  generarTareasAtrasadas();
  
  // Generar recomendaciones
  generarRecomendaciones(totalTareas, completadas, enProgreso, pendientes, tasaCompletitud);
  
  // Generar plan de acción inteligente
  generarPlanAccion();
}

/**
 * Generar insights con IA
 */
function generarInsights(total, completadas, enProgreso, pendientes, tasa) {
  const container = document.getElementById('insightsContainer');
  const insights = [];
  
  // Análisis de tasa de completitud
  if (tasa >= 80) {
    insights.push({
      icon: '🔥',
      title: '¡Excelente Ritmo!',
      text: `Tu tasa de completitud es del ${tasa}%. ¡Sigue así!`,
      color: '#10b981'
    });
  } else if (tasa >= 50) {
    insights.push({
      icon: '💪',
      title: 'Buen Progreso',
      text: `Vas por buen camino con un ${tasa}% completado. Puedes mejorar un poco más.`,
      color: '#f59e0b'
    });
  } else {
    insights.push({
      icon: '⚠️',
      title: 'Necesitas Enfocarte',
      text: `Solo un ${tasa}% completado. Prioriza tus tareas pendientes.`,
      color: '#ef4444'
    });
  }
  
  // Análisis de tareas en progreso
  if (enProgreso > 5) {
    insights.push({
      icon: '🎯',
      title: 'Demasiadas Tareas en Paralelo',
      text: `Tienes ${enProgreso} tareas en progreso. Intenta enfocarte en menos tareas a la vez.`,
      color: '#f59e0b'
    });
  } else if (enProgreso === 0 && pendientes > 0) {
    insights.push({
      icon: '🚀',
      title: 'Comienza Algo Nuevo',
      text: 'No tienes tareas en progreso. ¡Es momento de empezar!',
      color: '#3b82f6'
    });
  }
  
  // Análisis de pendientes
  if (pendientes === 0 && completadas > 0) {
    insights.push({
      icon: '🎉',
      title: '¡Todo Limpio!',
      text: 'No tienes tareas pendientes. ¡Increíble trabajo!',
      color: '#10b981'
    });
  } else if (pendientes > 10) {
    insights.push({
      icon: '📋',
      title: 'Backlog Grande',
      text: `Tienes ${pendientes} tareas pendientes. Considera dividirlas en sprints.`,
      color: '#6366f1'
    });
  }
  
  // Velocidad (story points por tarea)
  const puntosPromedio = total > 0 ? (allCards.reduce((sum, c) => sum + (parseInt(c.story_points) || 0), 0) / total).toFixed(1) : 0;
  insights.push({
    icon: '⚡',
    title: 'Complejidad Promedio',
    text: `Tus tareas tienen en promedio ${puntosPromedio} story points.`,
    color: '#8b5cf6'
  });
  
  // Renderizar insights
  container.innerHTML = insights.map(insight => `
    <div class="mb-3 p-3" style="background: rgba(255, 255, 255, 0.05); border-radius: 12px; border-left: 4px solid ${insight.color};">
      <div class="d-flex align-items-start gap-2">
        <span style="font-size: 1.5rem;">${insight.icon}</span>
        <div class="flex-grow-1">
          <div class="fw-bold text-light mb-1">${insight.title}</div>
          <div class="small text-secondary">${insight.text}</div>
        </div>
      </div>
    </div>
  `).join('');
}

/**
 * Generar distribución de categorías
 */
function generarCategorias() {
  const container = document.getElementById('categoriasContainer');
  
  const categorias = {
    'soporte': { count: 0, icon: '🛠️', name: 'Soporte', color: '#3b82f6' },
    'desarrollo': { count: 0, icon: '💻', name: 'Desarrollo', color: '#8b5cf6' },
    'reunion': { count: 0, icon: '👥', name: 'Reunión', color: '#f59e0b' },
    'bug': { count: 0, icon: '🐛', name: 'Bug', color: '#ef4444' },
    'otros': { count: 0, icon: '📌', name: 'Sin Categoría', color: '#6b7280' }
  };
  
  allCards.forEach(card => {
    const cat = card.categoria || 'otros';
    if (categorias[cat]) {
      categorias[cat].count++;
    } else {
      categorias.otros.count++;
    }
  });
  
  const total = allCards.length;
  
  container.innerHTML = Object.entries(categorias)
    .filter(([_, cat]) => cat.count > 0)
    .sort((a, b) => b[1].count - a[1].count)
    .map(([key, cat]) => {
      const porcentaje = total > 0 ? Math.round((cat.count / total) * 100) : 0;
      return `
        <div class="mb-3">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="text-light fw-semibold">${cat.icon} ${cat.name}</span>
            <span class="text-light fw-bold">${cat.count} (${porcentaje}%)</span>
          </div>
          <div class="progress" style="height: 12px; border-radius: 6px; background: rgba(148, 163, 184, 0.2);">
            <div class="progress-bar" role="progressbar" style="width: ${porcentaje}%; background: ${cat.color};" aria-valuenow="${porcentaje}" aria-valuemin="0" aria-valuemax="100"></div>
          </div>
        </div>
      `;
    }).join('');
}

/**
 * Generar productividad diaria últimos 7 días
 */
function generarProductividadDiaria() {
  const container = document.getElementById('productividadDiariaContainer');
  
  // Obtener tareas completadas con fecha de actualización
  const tareasConFecha = allCards.filter(c => {
    const list = allLists.find(l => l.id === c.list_id);
    return list && list.title === 'Completado' && c.updated_at;
  });
  
  // Agrupar por día (últimos 7 días)
  const hoy = new Date();
  const dias = [];
  
  for (let i = 6; i >= 0; i--) {
    const fecha = new Date(hoy);
    fecha.setDate(fecha.getDate() - i);
    fecha.setHours(0, 0, 0, 0);
    
    const fechaStr = fecha.toISOString().split('T')[0];
    const diaStr = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'][fecha.getDay()];
    
    const tareasDelDia = tareasConFecha.filter(t => {
      const updated = new Date(t.updated_at);
      updated.setHours(0, 0, 0, 0);
      return updated.toISOString().split('T')[0] === fechaStr;
    }).length;
    
    dias.push({ dia: diaStr, fecha: fechaStr, count: tareasDelDia });
  }
  
  const maxCount = Math.max(...dias.map(d => d.count), 1);
  
  container.innerHTML = `
    <div class="d-flex justify-content-between align-items-end gap-2" style="height: 150px;">
      ${dias.map(d => {
        const altura = (d.count / maxCount) * 100;
        const esHoy = d.fecha === hoy.toISOString().split('T')[0];
        return `
          <div class="text-center flex-grow-1">
            <div class="d-flex align-items-end justify-content-center" style="height: 120px;">
              <div class="w-100" style="height: ${altura}%; background: ${esHoy ? 'linear-gradient(180deg, #3b82f6, #2563eb)' : 'linear-gradient(180deg, #6b7280, #4b5563)'}; border-radius: 6px 6px 0 0; min-height: 5px; position: relative;">
                ${d.count > 0 ? `<div style="position: absolute; top: -20px; left: 50%; transform: translateX(-50%); font-size: 12px; font-weight: 700; color: #e2e8f0;">${d.count}</div>` : ''}
              </div>
            </div>
            <div class="small text-secondary mt-2 fw-semibold">${d.dia}</div>
          </div>
        `;
      }).join('')}
    </div>
  `;
}

/**
 * Generar tareas atrasadas
 */
function generarTareasAtrasadas() {
  const container = document.getElementById('tareasAtrasadasContainer');
  const section = document.getElementById('tareasAtrasadasSection');
  
  const hoy = new Date();
  hoy.setHours(0, 0, 0, 0);
  
  const atrasadas = allCards.filter(c => {
    const list = allLists.find(l => l.id === c.list_id);
    if (!list || list.title === 'Completado') return false;
    
    if (c.fecha_entrega) {
      const vencimiento = new Date(c.fecha_entrega);
      vencimiento.setHours(0, 0, 0, 0);
      return vencimiento < hoy;
    }
    return false;
  });
  
  if (atrasadas.length === 0) {
    section.style.display = 'none';
    return;
  }
  
  section.style.display = 'block';
  
  container.innerHTML = atrasadas.map(tarea => {
    const diasAtrasado = Math.floor((hoy - new Date(tarea.fecha_entrega)) / (1000 * 60 * 60 * 24));
    const list = allLists.find(l => l.id === tarea.list_id);
    
    return `
      <div class="col-md-6">
        <div class="card bg-dark border-danger" style="border-radius: 12px;">
          <div class="card-body p-3">
            <h6 class="card-title text-light mb-2">${tarea.title}</h6>
            <div class="d-flex justify-content-between align-items-center">
              <span class="badge bg-danger">${diasAtrasado} día(s) de retraso</span>
              <span class="badge" style="background: #f59e0b;">${list?.title || 'Sin estado'}</span>
            </div>
            <div class="mt-2 small text-secondary">
              Vencimiento: ${new Date(tarea.fecha_entrega).toLocaleDateString()}
            </div>
          </div>
        </div>
      </div>
    `;
  }).join('');
}

/**
 * Generar recomendaciones personalizadas
 */
function generarRecomendaciones(total, completadas, enProgreso, pendientes, tasa) {
  const container = document.getElementById('recomendacionesContainer');
  const recomendaciones = [];
  
  // Recomendación 1: Enfoque
  if (enProgreso > 3) {
    recomendaciones.push({
      icon: '🎯',
      title: 'Reduce el Multitasking',
      text: 'Tienes demasiadas tareas en progreso. La ciencia demuestra que enfocarse en 1-3 tareas aumenta la productividad en un 40%.',
      action: 'Completa 2 tareas antes de empezar nuevas',
      color: '#f59e0b'
    });
  }
  
  // Recomendación 2: Priorización
  if (pendientes > 5 && tasa < 50) {
    recomendaciones.push({
      icon: '🚀',
      title: 'Aplica la Regla 80/20',
      text: 'El 20% de tus tareas generarán el 80% de resultados. Identifica las tareas de mayor impacto.',
      action: 'Marca 2-3 tareas críticas y hazlas primero',
      color: '#8b5cf6'
    });
  }
  
  // Recomendación 3: Momentum
  if (completadas > 0 && tasa >= 50) {
    recomendaciones.push({
      icon: '⚡',
      title: 'Mantén el Momentum',
      text: '¡Vas muy bien! Los estudios muestran que completar tareas libera dopamina y te motiva a seguir.',
      action: 'Completa 1 tarea pequeña ahora para seguir el impulso',
      color: '#10b981'
    });
  }
  
  // Recomendación 4: Bloqueo de tiempo
  if (enProgreso > 0) {
    recomendaciones.push({
      icon: '⏰',
      title: 'Usa Time Blocking',
      text: 'Dedica bloques de 90 minutos sin interrupciones a tus tareas en progreso. Es la técnica favorita de Elon Musk.',
      action: 'Agenda tu próximo bloque de trabajo profundo',
      color: '#3b82f6'
    });
  }
  
  // Recomendación 5: Descanso
  if (completadas >= 3) {
    recomendaciones.push({
      icon: '🧘',
      title: 'Toma un Descanso',
      text: 'Has completado varias tareas. Los descansos de 5-10 minutos mejoran la concentración en un 30%.',
      action: 'Respira, camina o estírate antes de continuar',
      color: '#06b6d4'
    });
  }
  
  // Recomendación 6: Siguiente acción
  if (pendientes > 0) {
    const siguienteTarea = allCards.find(c => {
      const list = allLists.find(l => l.id === c.list_id);
      return list && list.title === 'Por Hacer';
    });
    
    if (siguienteTarea) {
      recomendaciones.push({
        icon: '✨',
        title: 'Tu Próxima Acción',
        text: `"${siguienteTarea.title}" - Comienza con esto y genera impulso.`,
        action: 'Muévela a "En Progreso" ahora',
        color: '#f59e0b'
      });
    }
  }
  
  container.innerHTML = recomendaciones.slice(0, 4).map(rec => `
    <div class="col-md-6">
      <div class="card bg-dark border-0" style="border-radius: 12px; border-left: 4px solid ${rec.color};">
        <div class="card-body p-3">
          <div class="d-flex align-items-start gap-3 mb-2">
            <span style="font-size: 2rem;">${rec.icon}</span>
            <div class="flex-grow-1">
              <h6 class="text-light fw-bold mb-2">${rec.title}</h6>
              <p class="text-secondary small mb-2">${rec.text}</p>
              <div class="d-flex align-items-center gap-2">
                <span class="badge" style="background: ${rec.color};">💡 ${rec.action}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  `).join('');
}

/**
 * 🤖 MOTOR DE RECOMENDACIÓN INTELIGENTE
 * Algoritmo multi-factor que analiza y prioriza tareas
 */
function generarPlanAccion() {
  const container = document.getElementById('planAccionContainer');
  
  // Obtener tareas pendientes y en progreso
  const tareasActivas = allCards.filter(c => {
    const list = allLists.find(l => l.id === c.list_id);
    return list && (list.title === 'Por Hacer' || list.title === 'En Progreso');
  });
  
  if (tareasActivas.length === 0) {
    container.innerHTML = `
      <div class="text-center py-5">
        <div style="font-size: 4rem;">🎉</div>
        <h4 class="text-light mt-3">¡Felicidades! No tienes tareas pendientes</h4>
        <p class="text-secondary">Es momento de crear nuevas metas o disfrutar un merecido descanso.</p>
      </div>
    `;
    return;
  }
  
  // Calcular score para cada tarea usando algoritmo multi-factor
  const tareasConScore = tareasActivas.map(tarea => {
    const list = allLists.find(l => l.id === tarea.list_id);
    const score = calcularScoreTarea(tarea, list);
    return { ...tarea, score, list };
  });
  
  // Ordenar por score (mayor primero)
  tareasConScore.sort((a, b) => b.score.total - a.score.total);
  
  // Tomar las top 5 tareas recomendadas
  const topTareas = tareasConScore.slice(0, 5);
  
  // Generar HTML
  container.innerHTML = `
    <div class="row g-3">
      ${topTareas.map((tarea, index) => generarTareaCard(tarea, index)).join('')}
    </div>
    
    <!-- Explicación del algoritmo -->
    <div class="mt-4 p-3" style="background: rgba(148, 163, 184, 0.05); border-radius: 12px; border: 1px solid rgba(148, 163, 184, 0.2);">
      <div class="d-flex align-items-start gap-2">
        <span style="font-size: 1.2rem;">ℹ️</span>
        <div class="flex-grow-1">
          <strong class="text-light small">¿Cómo funciona el algoritmo?</strong>
          <p class="mb-0 small text-secondary mt-1">
            El sistema analiza múltiples factores: <strong>Urgencia</strong> (fecha límite), 
            <strong>Impacto</strong> (story points), <strong>Estado actual</strong> (en progreso = prioridad), 
            <strong>Tiempo sin atención</strong> (antigüedad), <strong>Tipo de tarea</strong> (bugs = urgente), 
            y <strong>Momentum</strong> (completar lo que ya empezaste). Cada factor aporta puntos que determinan el orden óptimo.
          </p>
        </div>
      </div>
    </div>
  `;
}

/**
 * Calcular score de prioridad para una tarea
 * Algoritmo multi-factor sistémico
 */
function calcularScoreTarea(tarea, list) {
  let score = {
    urgencia: 0,
    impacto: 0,
    estado: 0,
    antiguedad: 0,
    tipo: 0,
    total: 0
  };
  
  // 1. URGENCIA - Fecha de entrega (40 puntos máx)
  if (tarea.fecha_entrega) {
    const hoy = new Date();
    hoy.setHours(0, 0, 0, 0);
    const vencimiento = new Date(tarea.fecha_entrega);
    vencimiento.setHours(0, 0, 0, 0);
    const diasRestantes = Math.ceil((vencimiento - hoy) / (1000 * 60 * 60 * 24));
    
    if (diasRestantes < 0) {
      score.urgencia = 40; // Atrasada = máxima prioridad
    } else if (diasRestantes === 0) {
      score.urgencia = 38; // Hoy
    } else if (diasRestantes <= 2) {
      score.urgencia = 35; // 1-2 días
    } else if (diasRestantes <= 7) {
      score.urgencia = 25; // Esta semana
    } else if (diasRestantes <= 14) {
      score.urgencia = 15; // Próximas 2 semanas
    } else {
      score.urgencia = 5; // Más de 2 semanas
    }
  } else {
    score.urgencia = 10; // Sin fecha = prioridad media-baja
  }
  
  // 2. IMPACTO - Story Points (30 puntos máx)
  const points = parseInt(tarea.story_points) || 0;
  if (points === 0) {
    score.impacto = 5; // Sin estimar
  } else if (points <= 2) {
    score.impacto = 25; // Rápidas = hazlas YA (quick wins)
  } else if (points === 3) {
    score.impacto = 20; // Media
  } else if (points === 5) {
    score.impacto = 15; // Compleja
  } else {
    score.impacto = 10; // Muy compleja = dividir
  }
  
  // 3. ESTADO - Ya en progreso (20 puntos máx)
  if (list && list.title === 'En Progreso') {
    score.estado = 20; // Termina lo que empezaste (momentum)
  } else {
    score.estado = 0;
  }
  
  // 4. ANTIGÜEDAD - Tiempo sin atención (15 puntos máx)
  if (tarea.created_at) {
    const creacion = new Date(tarea.created_at);
    const hoy = new Date();
    const diasVida = Math.floor((hoy - creacion) / (1000 * 60 * 60 * 24));
    
    if (diasVida > 30) {
      score.antiguedad = 15; // Más de 1 mes = atención
    } else if (diasVida > 14) {
      score.antiguedad = 10; // Más de 2 semanas
    } else if (diasVida > 7) {
      score.antiguedad = 5; // Más de 1 semana
    } else {
      score.antiguedad = 2; // Reciente
    }
  }
  
  // 5. TIPO - Categoría de tarea (15 puntos máx)
  switch (tarea.categoria) {
    case 'bug':
      score.tipo = 15; // Bugs = prioridad alta
      break;
    case 'soporte':
      score.tipo = 12; // Soporte = importante
      break;
    case 'desarrollo':
      score.tipo = 8; // Desarrollo = normal
      break;
    case 'reunion':
      score.tipo = 5; // Reuniones = pueden esperar
      break;
    default:
      score.tipo = 7; // Sin categoría
  }
  
  // 6. PROYECTO LARGO - Bonus si es proyecto estratégico
  if (tarea.es_proyecto_largo == 1) {
    score.tipo += 5; // Bonus proyectos importantes
  }
  
  // CALCULAR TOTAL
  score.total = score.urgencia + score.impacto + score.estado + score.antiguedad + score.tipo;
  
  return score;
}

/**
 * Generar card HTML para tarea recomendada
 */
function generarTareaCard(tarea, index) {
  const medals = ['🥇', '🥈', '🥉', '🏅', '🎯'];
  const colors = ['#fbbf24', '#94a3b8', '#cd7f32', '#3b82f6', '#8b5cf6'];
  const priorities = ['CRÍTICA', 'ALTA', 'ALTA', 'MEDIA', 'MEDIA'];
  
  const medal = medals[index] || '📌';
  const color = colors[index] || '#6b7280';
  const priority = priorities[index] || 'NORMAL';
  
  // Calcular días restantes si tiene fecha
  let tiempoInfo = '';
  if (tarea.fecha_entrega) {
    const hoy = new Date();
    hoy.setHours(0, 0, 0, 0);
    const vencimiento = new Date(tarea.fecha_entrega);
    vencimiento.setHours(0, 0, 0, 0);
    const dias = Math.ceil((vencimiento - hoy) / (1000 * 60 * 60 * 24));
    
    if (dias < 0) {
      tiempoInfo = `<span class="badge bg-danger">⏰ Atrasada ${Math.abs(dias)} día(s)</span>`;
    } else if (dias === 0) {
      tiempoInfo = `<span class="badge bg-warning text-dark">⚡ Vence HOY</span>`;
    } else if (dias <= 2) {
      tiempoInfo = `<span class="badge bg-warning text-dark">⏳ ${dias} día(s)</span>`;
    } else if (dias <= 7) {
      tiempoInfo = `<span class="badge bg-info">📅 ${dias} días</span>`;
    } else {
      tiempoInfo = `<span class="badge bg-secondary">📆 ${dias} días</span>`;
    }
  }
  
  // Badge de categoría
  const categoriasIcons = {
    'soporte': '🛠️ Soporte',
    'desarrollo': '💻 Desarrollo',
    'reunion': '👥 Reunión',
    'bug': '🐛 Bug'
  };
  const categoriaText = categoriasIcons[tarea.categoria] || '📌 General';
  
  // Estado actual
  const estadoBadge = tarea.list?.title === 'En Progreso' 
    ? '<span class="badge bg-success">🔄 En Progreso</span>'
    : '<span class="badge bg-primary">⏳ Pendiente</span>';
  
  // Story points
  const pointsText = tarea.story_points > 0 
    ? `${tarea.story_points} pts` 
    : 'Sin estimar';
  
  // Desglose del score
  const scoreBreakdown = `
    <div class="small text-secondary mt-2">
      <strong class="text-light">Score: ${tarea.score.total}</strong> pts
      <div class="mt-1" style="font-size: 0.75rem;">
        Urgencia: ${tarea.score.urgencia} | 
        Impacto: ${tarea.score.impacto} | 
        Estado: ${tarea.score.estado} | 
        Antigüedad: ${tarea.score.antiguedad} | 
        Tipo: ${tarea.score.tipo}
      </div>
    </div>
  `;
  
  // Razón de recomendación
  let razon = '';
  if (tarea.score.urgencia >= 35) {
    razon = '<div class="alert alert-danger p-2 mt-2 mb-0 small">🚨 <strong>Urgente:</strong> Vence pronto o está atrasada</div>';
  } else if (tarea.score.estado === 20) {
    razon = '<div class="alert alert-success p-2 mt-2 mb-0 small">⚡ <strong>Momentum:</strong> Ya la empezaste, termínala ahora</div>';
  } else if (tarea.score.impacto >= 25) {
    razon = '<div class="alert alert-info p-2 mt-2 mb-0 small">🎯 <strong>Quick Win:</strong> Tarea rápida, gana momentum</div>';
  } else if (tarea.score.tipo >= 15) {
    razon = '<div class="alert alert-warning p-2 mt-2 mb-0 small">🐛 <strong>Bug Crítico:</strong> Afecta a usuarios</div>';
  } else if (tarea.score.antiguedad >= 15) {
    razon = '<div class="alert alert-secondary p-2 mt-2 mb-0 small">⏰ <strong>Olvidada:</strong> Lleva más de 1 mes pendiente</div>';
  }
  
  return `
    <div class="col-12">
      <div class="card bg-dark border-0" style="border-left: 5px solid ${color}; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.3);">
        <div class="card-body p-3">
          <div class="d-flex align-items-start gap-3">
            <!-- Medalla de posición -->
            <div class="text-center" style="min-width: 60px;">
              <div style="font-size: 2.5rem;">${medal}</div>
              <div class="badge" style="background: ${color}; font-size: 0.7rem;">#${index + 1}</div>
            </div>
            
            <!-- Contenido de la tarea -->
            <div class="flex-grow-1">
              <div class="d-flex justify-content-between align-items-start mb-2">
                <h6 class="text-light mb-0 fw-bold">${tarea.title}</h6>
                <span class="badge" style="background: ${color};">${priority}</span>
              </div>
              
              ${tarea.description ? `<p class="text-secondary small mb-2">${tarea.description.substring(0, 100)}${tarea.description.length > 100 ? '...' : ''}</p>` : ''}
              
              <div class="d-flex flex-wrap gap-2 mb-2">
                ${estadoBadge}
                <span class="badge bg-secondary">${categoriaText}</span>
                <span class="badge bg-info">${pointsText}</span>
                ${tiempoInfo}
              </div>
              
              ${razon}
              ${scoreBreakdown}
              
              <!-- Acciones -->
              <div class="mt-3 d-flex gap-2">
                <button class="btn btn-sm btn-success" onclick="moverTareaAProgreso(${tarea.id})">
                  ▶️ Comenzar Ahora
                </button>
                <button class="btn btn-sm btn-outline-light" onclick="editCard(${tarea.id})">
                  ✏️ Ver Detalles
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  `;
}

/**
 * Mover tarea a "En Progreso" desde el plan de acción
 */
async function moverTareaAProgreso(tareaId) {
  try {
    // Buscar la lista "En Progreso"
    const listaEnProgreso = allLists.find(l => l.title === 'En Progreso');
    
    if (!listaEnProgreso) {
      alert('No se encontró la lista "En Progreso"');
      return;
    }
    
    // Mover la tarea
    await api('move_card', {
      id: tareaId,
      new_list_id: listaEnProgreso.id,
      new_position: 0
    });
    
    // Recargar datos
    await load();
    
    // Mostrar mensaje de éxito
    alert('✅ ¡Excelente! Tarea movida a "En Progreso". ¡A trabajar!');
    
    // Refrescar la vista de informe
    if (vistaActual === 'informe') {
      renderInformeView();
    }
  } catch (error) {
    console.error('Error al mover tarea:', error);
    alert('❌ Error al mover la tarea');
  }
}

load();
