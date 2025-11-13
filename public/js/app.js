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
  if (!currentSprint) {
    document.getElementById('sprintTitle').textContent = '🏃 Sin sprint activo';
    document.getElementById('sprintObjetivo').textContent = '';
    return;
  }
  
  document.getElementById('sprintTitle').textContent = `🏃 ${currentSprint.nombre}`;
  document.getElementById('sprintObjetivo').textContent = currentSprint.objetivo || '';
  
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

    col.innerHTML = `
      <div class="list-header">
        <div class="list-title">${escapeHtml(fixedList.title)}</div>
        <button class="btn btn-sm btn-add-card">✨ Tarea</button>
      </div>
      <div class="dropzone"></div>
    `;

    const dz = col.querySelector('.dropzone');

    (cardsByList[dbList.id] || []).forEach(card => {
      dz.appendChild(renderCard(card));
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
function renderCard(card) {
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

load();
