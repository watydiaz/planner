<!-- Vista Kanban -->
<div id="board" class="kanban"></div>
  
<!-- Vista Lista -->
<div id="vistaLista" style="display:none;">
  <!-- Filtros -->
  <div class="mb-3 p-3" style="background: rgba(30, 41, 59, 0.5); border-radius: 12px; border: 1px solid rgba(148, 163, 184, 0.2);">
    <div class="row g-3 align-items-end justify-content-center">
      <!-- Filtro de Rango de Fechas -->
      <div class="col-md-2">
        <label class="form-label text-light fw-semibold small">📅 Fecha Inicio</label>
        <input type="date" id="filtroFechaInicio" class="form-control form-control-sm bg-dark text-light border-secondary" style="border-radius: 8px;">
      </div>
      <div class="col-md-2">
        <label class="form-label text-light fw-semibold small">📅 Fecha Fin</label>
        <input type="date" id="filtroFechaFin" class="form-control form-control-sm bg-dark text-light border-secondary" style="border-radius: 8px;">
      </div>
      
      <!-- Filtro de Estado -->
      <div class="col-md-2">
        <label class="form-label text-light fw-semibold small">🎯 Estado</label>
        <select id="filtroEstado" class="form-select form-select-sm bg-dark text-light border-secondary" style="border-radius: 8px;">
          <option value="">Todos</option>
          <option value="pendiente">⏳ Pendiente</option>
          <option value="en_progreso">🔄 En Progreso</option>
          <option value="hecho">✅ Hecho</option>
        </select>
      </div>
      
      <!-- Filtro de Categoría -->
      <div class="col-md-2">
        <label class="form-label text-light fw-semibold small">🏷️ Categoría</label>
        <select id="filtroCategoria" class="form-select form-select-sm bg-dark text-light border-secondary" style="border-radius: 8px;">
          <option value="">Todas</option>
          <option value="soporte">🛠️ Soporte</option>
          <option value="desarrollo">💻 Desarrollo</option>
          <option value="reunion">👥 Reunión</option>
          <option value="bug">🐛 Bug</option>
        </select>
      </div>
      
      <!-- Botones de Selección Rápida -->
      <div class="col-md-4">
        <label class="form-label text-light fw-semibold small">⚡ Selección Rápida</label>
        <div class="d-flex gap-2">
          <button id="btnFiltroAyer" class="btn btn-sm btn-warning" style="border-radius: 8px; flex: 1;">⏮️ Ayer</button>
          <button id="btnFiltroHoy" class="btn btn-sm btn-success" style="border-radius: 8px; flex: 1;">📍 Hoy</button>
          <button id="btnFiltroSemanaActual" class="btn btn-sm btn-info" style="border-radius: 8px; flex: 1;">📆 Semana</button>
          <button id="btnFiltroMesActual" class="btn btn-sm btn-primary" style="border-radius: 8px; flex: 1;">📅 Mes</button>
          <button id="btnLimpiarFiltros" class="btn btn-sm btn-secondary" style="border-radius: 8px;">🔄</button>
        </div>
      </div>
    </div>
  </div>
  
  <!-- Tabla -->
  <div class="table-responsive">
    <table class="table table-dark table-striped table-hover">
      <thead>
        <tr>
          <th style="width: 60px;">#ID</th>
          <th>Título</th>
          <th style="width: 150px;">Estado</th>
          <th style="width: 130px;">Categoría</th>
          <th style="width: 80px;">Puntos</th>
          <th style="width: 150px;">Fecha Entrega</th>
          <th style="width: 120px;">Tipo</th>
          <th style="width: 100px;">Acciones</th>
        </tr>
      </thead>
      <tbody id="listaTableBody">
        <tr>
          <td colspan="8" class="text-center text-secondary">Cargando...</td>
        </tr>
      </tbody>
    </table>
  </div>
</div>
