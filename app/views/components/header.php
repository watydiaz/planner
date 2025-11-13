<nav class="toolbar py-3 mb-3">
  <div class="container-fluid">
    <!-- Fila 1: Header principal -->
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div class="d-flex align-items-center gap-3">
        <h4 class="m-0" style="color: #e2e8f0; font-weight: 800; font-size: 1.5rem;">
          📋 Planner Karol Diaz
        </h4>
        <select id="boardSelector" class="form-select form-select-sm" style="max-width: 220px; background: rgba(30, 41, 59, 0.8); border: 1px solid rgba(148, 163, 184, 0.3); color: #e2e8f0; font-weight: 500;">
          <option value="">Cargando tableros...</option>
        </select>
        <button class="btn btn-sm btn-success" id="btnNewBoard">
          ✨ Nuevo Tablero
        </button>
      </div>
      <div class="d-flex gap-2 align-items-center">
        <!-- Toggle Vista -->
        <div class="btn-group shadow-sm" role="group">
          <button class="btn btn-sm btn-primary active" id="btnVistaKanban" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); border: none;">
            📋 Kanban
          </button>
          <button class="btn btn-sm btn-outline-primary" id="btnVistaLista" style="border-color: #3b82f6; color: #3b82f6;">
            📊 Lista
          </button>
        </div>
        <button class="btn btn-sm btn-outline-danger" id="btnDeleteBoard">
          🗑️ Eliminar
        </button>
      </div>
    </div>
    
    <!-- Fila 2: Info del tablero y sprint -->
    <div class="d-flex justify-content-between align-items-center">
      <div class="d-flex align-items-center gap-4">
        <div>
          <h5 class="m-0" style="font-weight: 700; font-size: 1.3rem;" id="currentBoardName">Cargando...</h5>
          <small style="color: #94a3b8; font-weight: 500;" id="sprintTitle">🏃 Sprint 1</small>
        </div>
        <small style="color: #94a3b8; font-style: italic; font-weight: 500;" id="sprintObjetivo"></small>
        <button class="btn btn-sm btn-outline-info" id="btnNewSprint">
          ⚡ Nuevo Sprint
        </button>
      </div>
      <div class="d-flex gap-3">
        <div class="sprint-stat">
          📅 Días restantes: <b id="diasRestantes">-</b>
        </div>
        <div class="sprint-stat">
          📊 Puntos: <b id="puntosCompletados">0</b>/<b id="puntosTotales">0</b>
        </div>
        <div class="sprint-stat">
          ⚡ Progreso: <b id="porcentaje">0%</b>
        </div>
      </div>
    </div>
  </div>
</nav>
