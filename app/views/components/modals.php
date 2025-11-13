<!-- Modal: Editar tarjeta -->
<div class="modal fade" id="cardModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content bg-dark text-light border-0" style="border-radius: 16px; box-shadow: 0 20px 60px rgba(0,0,0,0.6);">
      <div class="modal-header" style="border-bottom: 1px solid rgba(148, 163, 184, 0.2);">
        <h5 class="modal-title fw-bold">✏️ Editar Tarea</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" style="padding: 1.5rem;">
        <input type="hidden" id="cardId">
        <div class="mb-3">
          <label class="form-label fw-semibold">📝 Título</label>
          <input type="text" id="cardTitle" class="form-control bg-dark text-light border-secondary" placeholder="Título de la tarea" style="border-radius: 10px;">
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">📄 Descripción</label>
          <textarea id="cardDesc" class="form-control bg-dark text-light border-secondary" rows="3" placeholder="Detalles o notas" style="border-radius: 10px;"></textarea>
        </div>
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label fw-semibold">📂 Categoría</label>
            <select id="cardCategoria" class="form-select bg-dark text-light border-secondary" style="border-radius: 10px;">
              <option value="">Sin categoría</option>
              <option value="soporte">🔧 Soporte</option>
              <option value="desarrollo">💻 Desarrollo</option>
              <option value="reunion">👥 Reunión</option>
              <option value="bug">🐛 Bug</option>
            </select>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label fw-semibold">⏱️ Story Points</label>
            <select id="cardPoints" class="form-select bg-dark text-light border-secondary" style="border-radius: 10px;">
              <option value="0">Sin estimar</option>
              <option value="1">1 - Muy rápida</option>
              <option value="2">2 - Rápida</option>
              <option value="3">3 - Media</option>
              <option value="5">5 - Compleja</option>
              <option value="8">8 - Muy compleja</option>
              <option value="13">13 - Épica</option>
            </select>
          </div>
        </div>
        
        <div class="mb-3">
          <div class="form-check" style="background: rgba(139, 92, 246, 0.1); padding: 0.75rem; border-radius: 10px; border: 1px solid rgba(139, 92, 246, 0.3);">
            <input type="checkbox" class="form-check-input" id="cardProyectoLargo" style="cursor: pointer;">
            <label class="form-check-label fw-semibold" for="cardProyectoLargo" style="cursor: pointer;">
              🚀 Proyecto de largo plazo
            </label>
          </div>
        </div>
        
        <div id="fechasProyecto" class="row mb-3" style="display:none;">
          <div class="col-md-6">
            <label class="form-label fw-semibold">📅 Fecha Inicio</label>
            <input type="date" id="cardFechaInicio" class="form-control bg-dark text-light border-secondary" style="border-radius: 10px; color-scheme: dark; cursor: pointer;">
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">🏁 Fecha Entrega</label>
            <input type="date" id="cardFechaEntrega" class="form-control bg-dark text-light border-secondary" style="border-radius: 10px; color-scheme: dark; cursor: pointer;">
          </div>
        </div>
        
        <div id="fechaNormal" class="mb-3">
          <label class="form-label fw-semibold">📅 Fecha Entrega</label>
          <input type="date" id="cardFechaEntregaNormal" class="form-control bg-dark text-light border-secondary" style="border-radius: 10px; color-scheme: dark; cursor: pointer;">
        </div>
        
        <hr style="border-color: rgba(148, 163, 184, 0.2); margin: 1.5rem 0;">
        
        <div class="mb-3">
          <label class="form-label fw-bold" style="font-size: 1.1rem; color: #60a5fa;">📋 Bitácora de Actividades</label>
          <p class="small text-secondary mb-3">Documenta procesos, acciones y adjunta archivos</p>
          
          <div class="mb-2">
            <div class="d-flex gap-2 align-items-center mb-2">
              <input type="text" id="newActivityInput" class="form-control bg-dark text-light border-secondary" placeholder="Describe la actividad..." style="border-radius: 10px; flex: 1;">
              <button class="btn btn-success" id="btnAddActivity" style="border-radius: 8px; white-space: nowrap;">➕ Agregar</button>
            </div>
          </div>
          
          <div id="activitiesTimeline" style="max-height: 300px; overflow-y: auto; background: rgba(15, 23, 42, 0.5); border-radius: 10px; padding: 1rem;">
            <p class="text-center text-secondary small">No hay actividades registradas</p>
          </div>
        </div>
      </div>
      <div class="modal-footer" style="border-top: 1px solid rgba(148, 163, 184, 0.2);">
        <button class="btn btn-danger me-auto" id="btnDeleteCard" style="border-radius: 10px;">🗑️ Eliminar</button>
        <button class="btn btn-primary" id="btnSaveCard" style="border-radius: 10px;">💾 Guardar</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Crear Sprint -->
<div class="modal fade" id="sprintModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content bg-dark text-light border-0" style="border-radius: 16px; box-shadow: 0 20px 60px rgba(0,0,0,0.6);">
      <div class="modal-header" style="border-bottom: 1px solid rgba(148, 163, 184, 0.2);">
        <h5 class="modal-title fw-bold">🏃 Crear Nuevo Sprint</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" style="padding: 1.5rem;">
        <div class="mb-3">
          <label class="form-label fw-semibold">✍️ Nombre del Sprint</label>
          <input type="text" id="sprintNombre" class="form-control bg-dark text-light border-secondary" placeholder="Ej: Sprint 1" style="border-radius: 10px;">
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">🎯 Objetivo del Sprint</label>
          <textarea id="sprintObjetivoInput" class="form-control bg-dark text-light border-secondary" rows="2" placeholder="¿Qué quieres lograr?" style="border-radius: 10px;"></textarea>
        </div>
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label fw-semibold">📅 Fecha Inicio</label>
            <input type="date" id="sprintFechaInicio" class="form-control bg-dark text-light border-secondary" style="border-radius: 10px; color-scheme: dark; cursor: pointer;">
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label fw-semibold">📅 Fecha Fin</label>
            <input type="date" id="sprintFechaFin" class="form-control bg-dark text-light border-secondary" style="border-radius: 10px; color-scheme: dark; cursor: pointer;">
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">⏱️ Duración rápida</label>
          <div class="btn-group w-100" role="group">
            <button type="button" class="btn btn-outline-info btn-duracion" data-days="7" style="border-radius: 10px 0 0 10px;">1 semana</button>
            <button type="button" class="btn btn-outline-info btn-duracion active" data-days="14" style="background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%); color: white; border-color: #06b6d4;">2 semanas</button>
            <button type="button" class="btn btn-outline-info btn-duracion" data-days="21">3 semanas</button>
            <button type="button" class="btn btn-outline-info btn-duracion" data-days="30" style="border-radius: 0 10px 10px 0;">1 mes</button>
          </div>
        </div>
      </div>
      <div class="modal-footer" style="border-top: 1px solid rgba(148, 163, 184, 0.2);">
        <button class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius: 10px;">Cancelar</button>
        <button class="btn btn-success" id="btnSaveSprint" style="border-radius: 10px;">✅ Crear Sprint</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Personalizado: Prompt -->
<div class="modal fade" id="customPromptModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content bg-dark text-light border-0" style="border-radius: 16px; box-shadow: 0 20px 60px rgba(0,0,0,0.6);">
      <div class="modal-header" style="border-bottom: 1px solid rgba(148, 163, 184, 0.2);">
        <h5 class="modal-title fw-bold" id="customPromptTitle">📝 Ingresa información</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" style="padding: 1.5rem;">
        <label class="form-label fw-semibold" id="customPromptLabel">Valor:</label>
        <input type="text" id="customPromptInput" class="form-control bg-dark text-light border-secondary" style="border-radius: 10px;">
      </div>
      <div class="modal-footer" style="border-top: 1px solid rgba(148, 163, 184, 0.2);">
        <button class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius: 10px;">Cancelar</button>
        <button class="btn btn-primary" id="customPromptConfirm" style="border-radius: 10px;">✅ Aceptar</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Personalizado: Confirm -->
<div class="modal fade" id="customConfirmModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content bg-dark text-light border-0" style="border-radius: 16px; box-shadow: 0 20px 60px rgba(0,0,0,0.6);">
      <div class="modal-header" style="border-bottom: 1px solid rgba(148, 163, 184, 0.2);">
        <h5 class="modal-title fw-bold" id="customConfirmTitle">⚠️ Confirmar acción</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" style="padding: 1.5rem;">
        <p id="customConfirmMessage" class="mb-0"></p>
      </div>
      <div class="modal-footer" style="border-top: 1px solid rgba(148, 163, 184, 0.2);">
        <button class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius: 10px;">Cancelar</button>
        <button class="btn btn-danger" id="customConfirmYes" style="border-radius: 10px;">✅ Confirmar</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Personalizado: Nuevo Tablero -->
<div class="modal fade" id="newBoardModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content bg-dark text-light border-0" style="border-radius: 16px; box-shadow: 0 20px 60px rgba(0,0,0,0.6);">
      <div class="modal-header" style="border-bottom: 1px solid rgba(148, 163, 184, 0.2);">
        <h5 class="modal-title fw-bold">📋 Crear Nuevo Tablero</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" style="padding: 1.5rem;">
        <label class="form-label fw-semibold">✍️ Nombre del tablero:</label>
        <input type="text" id="newBoardNameInput" class="form-control bg-dark text-light border-secondary" placeholder="Ej: Proyecto Cliente X" style="border-radius: 10px;">
      </div>
      <div class="modal-footer" style="border-top: 1px solid rgba(148, 163, 184, 0.2);">
        <button class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius: 10px;">Cancelar</button>
        <button class="btn btn-success" id="btnCreateNewBoard" style="border-radius: 10px;">✅ Crear Tablero</button>
      </div>
    </div>
  </div>
</div>
