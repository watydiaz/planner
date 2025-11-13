<nav class="toolbar py-2 mb-3">
  <div class="container-fluid">
    <!-- Fila única: Todo en una línea optimizada -->
    <div class="row align-items-center g-3">
      
      <!-- Columna 1: Logo + Selector de Tablero -->
      <div class="col-md-3">
        <div class="d-flex align-items-center gap-2">
          <h4 class="m-0" style="color: #e2e8f0; font-weight: 800; font-size: 1.3rem; white-space: nowrap;">
            📋 Karol Diaz
          </h4>
        </div>
        <div class="d-flex align-items-center gap-2 mt-1">
          <small style="color: #94a3b8; font-weight: 600; font-size: 0.75rem;">TABLERO:</small>
          <span style="color: #60a5fa; font-weight: 700; font-size: 0.9rem;" id="currentBoardName">Cargando...</span>
        </div>
        <select id="boardSelector" class="form-select form-select-sm mt-1" style="background: rgba(30, 41, 59, 0.8); border: 1px solid rgba(148, 163, 184, 0.3); color: #e2e8f0; font-weight: 500; font-size: 0.85rem;">
          <option value="">Cargando tableros...</option>
        </select>
      </div>
      
      <!-- Columna 2: Sprint Info -->
      <div class="col-md-4">
        <div class="d-flex align-items-center gap-3">
          <div class="d-flex flex-column">
            <small style="color: #94a3b8; font-weight: 600; font-size: 0.75rem;">SPRINT ACTUAL</small>
            <span style="color: #e2e8f0; font-weight: 700; font-size: 0.95rem;" id="sprintTitle">🏃 Sprint 1</span>
          </div>
          <div class="vr" style="opacity: 0.3;"></div>
          <div class="d-flex gap-3" style="font-size: 0.85rem;">
            <span style="color: #94a3b8;">📅 <b style="color: #60a5fa;" id="diasRestantes">-</b> días</span>
            <span style="color: #94a3b8;">📊 <b style="color: #34d399;" id="puntosCompletados">0</b>/<b id="puntosTotales">0</b></span>
            <span style="color: #94a3b8;">⚡ <b style="color: #fbbf24;" id="porcentaje">0%</b></span>
          </div>
        </div>
      </div>
      
      <!-- Columna 3: Acciones -->
      <div class="col-md-5">
        <div class="d-flex justify-content-end align-items-center gap-2">
          <!-- Toggle Vista -->
          <div class="btn-group shadow-sm" role="group">
            <button class="btn btn-sm btn-primary active" id="btnVistaKanban" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); border: none; font-size: 0.85rem; padding: 0.35rem 0.75rem;">
              📋 Kanban
            </button>
            <button class="btn btn-sm btn-outline-primary" id="btnVistaLista" style="border-color: #3b82f6; color: #3b82f6; font-size: 0.85rem; padding: 0.35rem 0.75rem;">
              � Lista
            </button>
          </div>
          
          <div class="vr" style="opacity: 0.3;"></div>
          
          <button class="btn btn-sm btn-success" id="btnNewBoard" style="font-size: 0.85rem; padding: 0.35rem 0.75rem;">
            ✨ Nuevo Tablero
          </button>
          <button class="btn btn-sm btn-info" id="btnNewSprint" style="font-size: 0.85rem; padding: 0.35rem 0.75rem;">
            ⚡ Nuevo Sprint
          </button>
          <button class="btn btn-sm btn-outline-danger" id="btnDeleteBoard" style="font-size: 0.85rem; padding: 0.35rem 0.75rem;">
            🗑️
          </button>
        </div>
      </div>
      
    </div>
  </div>
</nav>
